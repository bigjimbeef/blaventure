<?php

/*
	!adventure - a Blatech adventure

	Input:
		!adventure or !adv "action"

	Actions:
		North, South, East, West, Attack, Spell, Status, Inventory

		N,S,E,W 	- Go in the direction specified
		Attack		- Attack the enemy in the room (if there is one)
		Spell		- Attack using MP with a spell from the spellbook
		Status		- Check stats, output in the form:
			Level 3 Barbarian    HP 3/10    MP 2/5
		Inventory	- Check what you're equipped with
			 Dog Sword (3A)    Massive Iron Armour (6D)    1204GP
*/

//-----------------------------------------------
include_once("statics.php");
include_once("class_definitions.php");
include_once("procedural_generator.php");

// Game mode InputFragments.
include_once("class_select.php");
include_once("adventuring.php");
include_once("resting.php");
include_once("combat.php");
include_once("spellcasting.php");
include_once("looting.php");
include_once("levelup.php");
include_once("usingItem.php");

// DEBUG FLAG
define("DEBUG", 0);

// We're in Europe!
date_default_timezone_set("Europe/London");

function DEBUG_echo($string) {

	if ( constant("DEBUG") ) {
		echo "$string\n";
	}
}

function getSaveFilePath($nick, $isCharSave) {

	$saveSuffix = null;
	if ( $isCharSave ) {
		$saveSuffix = SaveFileType::Character;
	}
	else {
		$saveSuffix = SaveFileType::Map;
	}

	$home 		= getenv("HOME");
	$filePath 	= "$home/.blaventure/$nick.$saveSuffix";

	return $filePath;
}

function initCharacterSaveData($nick) {

	DEBUG_echo("initCharacterSave");

	global $procGen;

	$initialSaveData = new CharacterSaveData();
	$initialSaveData->name = $nick;
	$initialSaveData->nick = $nick;

	$initialSaveData->randomSeed = rand();
	$procGen->InitFromSeed($initialSaveData->randomSeed);

	return $initialSaveData;
}
function initMapSaveData($nick) {

	DEBUG_echo("initMapSave");

	global $procGen;

	$initialSaveData 			= new MapSaveData();
	$mapHalfSize				= floor(ProcGen::GetMapSize() / 2);

	$x = $y = $mapHalfSize;
	$initialSaveData->playerX = $initialSaveData->playerY = $x;

	$map						= new Map();
	// Definitely do not spawn a monster in the first room.
	$procGen->GenerateRoomForMap($map, $x, $y, 1, true);

	$initialSaveData->map		= $map;

	return $initialSaveData;
}

function writeSave($saveData, $filePath) {

	$handle		= fopen($filePath, "w");
	$serialData = serialize($saveData);

	fwrite($handle, $serialData);

	fclose($handle);
}

function readSave($filePath) {

	$handle		= fopen($filePath, "r");
	$serialData = fread($handle, filesize($filePath));

	$saveData 	= unserialize($serialData);

	fclose($handle);

	return $saveData;
}

function checkIfNewGame($nick) {

	$charFilePath 	= getSaveFilePath($nick, true);
	$mapFilePath 	= getSaveFilePath($nick, false);

	return !file_exists($charFilePath) || !file_exists($mapFilePath);
}

/*
 *	Called every time the player moves, or gains an item.
 * 	Writes to two files at $HOME/.blaventure/$nick.[char/map]
 */
function saveGame($nick, $isCharSave, $data = null) {

	$saveData	= null;
	
	$newGame	= checkIfNewGame($nick);
	$filePath 	= getSaveFilePath($nick, $isCharSave);

	if ( $newGame ) {
		$saveData = $isCharSave ? initCharacterSaveData($nick) : initMapSaveData($nick);
	}
	else {
		
		if ( !isset($data) ) {
			echo "ERROR: No save data supplied!\n";
			exit(5);
		}

		$saveData = $data;
	}

	writeSave($saveData, $filePath);
}


function getNickFromArgs() {

	global $argv;

	if ( !isset($argv[1]) ) {
		echo "ERROR: No nick found. What?!\n";
		exit(2);
	}

	// Convention states that the nick is the first input parameter.
	$nick = $argv[1];

	return $nick;
}

function readStdin() {

	$input = fgets(STDIN);

	if ( $input === FALSE ) {
		echo "No input supplied!\n";
		exit(4);
	}

	$input = rtrim($input, "\n");

	return $input;
}

function checkInputFragments( $fragments, $input, $charData, $mapData ) {

	$match	= false;

	$isHelp = strcasecmp($input, "help") == 0;

	foreach ( $fragments as $key => $fragment ) {

		if ( !$isHelp && $fragment->Matches($input, $charData) ) {

			$fragment->FireCallback($charData, $mapData, $key);
			$match = true;

			break;
		}
	}

	if ( $isHelp || !$match ) {
		$warning = "Commands: ";

		foreach ( $fragments as $fragment ) {

			$warning .= "$fragment->displayString, ";
		}

		$warning = rtrim($warning, ", ");
		$warning .= ".\n";

		echo $warning;
	}

	return $match;
}

function passiveHealthRegen( &$charData ) {

	// We don't passively regenerate in combat.
	if ( $charData->state != GameStates::Combat ) {
		return;
	}

	$date		= new DateTime();
	$timestamp	= $date->getTimeStamp();

	// We haven't performed an action before.
	if ( $charData->lastInputD <= 0 ) {

		// Now we have.
		$charData->lastInputD 	= $timestamp;
		return;
	}

	$deltaTime	= $timestamp - $charData->lastInputD;

	// Regen at rate of 1 HP/MP per 3 minutes
	$sIn3m		= 3 * 60;
	$regen		= floor($deltaTime / $sIn3m);

	$charData->hp += $regen;
	$charData->hp = min($charData->hp, $charData->hpMax);

	$charData->mp += $regen;
	$charData->mp = min($charData->mp, $charData->mpMax);
	
	// Mark when we last interacted.
	$charData->lastInputD 	= $timestamp;
}

function classSelect($input, $charData, $charName) {

	global $classSelect;

	checkInputFragments($classSelect->commands, $input, $charData, null);

	$setClass = false;

	// This should be set in a callback.
	if ( isset($charData->class) ) {

		echo "Greetings $charName, the level 1 $charData->class! Your adventure begins now! ('help' for commands)\n";
		$setClass = true;
	}

	return $setClass;
}

function firstPlay($data) {

	$data->hp = $data->hpMax;
	$data->mp = $data->mpMax;

	$data->level = 1;

	$data->weapon = "Stick";
	$data->weaponVal = 1;
	
	$data->armour = "Skin";
	$data->armourVal = 1;

	$data->weapon2 = "Smaller Stick";
	$data->weapon2Val = 1;
}

function adventuring($input, $charData, $mapData) {

	global $adventuring;

	checkInputFragments($adventuring->commands, $input, $charData, $mapData);
}

function resting($input, $charData, $mapData) {

	global $resting;

	checkInputFragments($resting->commands, $input, $charData, $mapData);
}

function combat($input, $charData, $mapData) {

	global $combat;

	checkInputFragments($combat->commands, $input, $charData, $mapData);
}

function spellcasting($input, $charData, $mapData, $nonCombat = false) {

	global $spellcasting;

	$spellcasting->generateInputFragments($charData, $nonCombat);

	checkInputFragments($spellcasting->commands, $input, $charData, $mapData);
}

function looting($input, $charData, $mapData) {

	global $looting;

	checkInputFragments($looting->commands, $input, $charData, $mapData);
}

function levelUp($input, $charData, $mapData) {

	global $levelUp;

	checkInputFragments($levelUp->commands, $input, $charData, $mapData);
}

function usingItem($input, $charData, $mapData, $nonCombat = false) {

	global $usingItem;

	$usingItem->generateInputFragments($charData, $nonCombat);

	checkInputFragments($usingItem->commands, $input, $charData, $mapData);
}

// Input of the form !adv "action", with nick supplied from args
function main() {

	$nick 		= getNickFromArgs();
	$mapData 	= null;
	$charData 	= null;

	if ( !checkIfNewGame($nick) ) {

		// Load character save data.
		$charFilePath 	= getSaveFilePath($nick, true);
		$charData 		= readSave($charFilePath);
		$charDataDirty	= false;

		// Load map save data.
		$mapFilePath 	= getSaveFilePath($nick, false);
		$mapData 		= readSave($mapFilePath);
		$mapDataDirty	= false;

		// Ensure it's sane.
		if ( empty($charData) || empty($mapData) ) {
			echo "ERROR: Save data's fucked.\n";
			exit(3);
		}

		// Read STDIN for input.
		$input = readStdin();

		switch ( $charData->state ) {

			case GameStates::NameSelect: {

				DEBUG_echo("NameSelect");

				if ( strcmp($input, "") == 0 ) {
					echo "Please enter a name!\n";
					exit(13);
				}

				$output = "Please choose a class for $input: ";

				global $classSelect;
				foreach ( $classSelect->commands as $fragment ) {

					$output .= "$fragment->displayString, ";
				}
				$output = rtrim($output, ", ") . "\n";

				echo $output;

				$charData->name 	= $input;
				StateManager::ChangeState($charData, GameStates::ClassSelect);

				$charDataDirty		= true;
			}
			break;

			case GameStates::ClassSelect: {

				DEBUG_echo("ClassSelect");

				$input = strtolower($input);

				$setClass = classSelect($input, $charData, $charData->name);

				if ( $setClass ) {
					StateManager::ChangeState($charData, GameStates::FirstPlay);
					$charDataDirty		= true;
				}
			}
			break;

			// Initialise the characters
			case GameStates::FirstPlay: {

				DEBUG_echo("FirstPlay");

				firstPlay($charData);

				StateManager::ChangeState($charData, GameStates::Adventuring);
			} // purposeful fall-through!

			// The main loop for when we're romping around.
			case GameStates::Adventuring: {

				DEBUG_echo("Adventuring");

				adventuring($input, $charData, $mapData);

				$charDataDirty		= true;
				$mapDataDirty		= true;
			}
			break;

			// Sleepy nap time.
			case GameStates::Resting: {

				DEBUG_echo("Resting");

				resting($input, $charData, $mapData);

				$charDataDirty		= true;
			}
			break;

			// IT'S CLOBBERING TIME
			case GameStates::Combat: {

				DEBUG_echo("Combat");

				combat($input, $charData, $mapData);

				$charDataDirty	= true;
				$mapDataDirty	= true;
			}
			break;

			case GameStates::Spellcasting: {

				DEBUG_echo("Spellcasting");

				$nonCombat = isset($charData->previousState) && ( $charData->previousState != GameStates::Combat );

				spellcasting($input, $charData, $mapData, $nonCombat);

				$charDataDirty	= true;
				$mapDataDirty	= true;
			}
			break;

			case GameStates::Looting: {

				DEBUG_echo("Looting");

				looting($input, $charData, $mapData);

				$charDataDirty	= true;
				$mapDataDirty	= true;
			}
			break;

			case GameStates::LevelUp: {

				DEBUG_echo("LevelUp");

				levelUp($input, $charData, $mapData);

				$charDataDirty	= true;
			}
			break;

			case GameStates::UsingItem: {

				DEBUG_echo("UsingItem");

				usingItem($input, $charData, $mapData, true);

				$charDataDirty	= true;
			}
			break;

			default:
			break;
		}
	}
	else {
		// Initialise the character save.
		saveGame($nick, true);

		// Initialise the map save.
		saveGame($nick, false);

		// Prompt for name select.
		echo "Welcome, $nick! Please choose a name for your character:\n";
	}

	if ( isset($charData) && $charDataDirty ) {

		// Regenerate health based on time since last input.
		passiveHealthRegen($charData);

		saveGame($nick, true, $charData);
	}
	if ( isset($mapData) && $mapDataDirty ) {

		saveGame($nick, false, $mapData);
	}
}

main();