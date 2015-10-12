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
include_once("shopping.php");
include_once("dynasty.php");

// DEBUG FLAG
define("DEBUG", 1);

// We're in Europe!
date_default_timezone_set("Europe/London");

function DEBUG_echo($string) {

	if ( constant("DEBUG") ) {
		echo "$string\n";
	}
}

function getSaveFilePath($nick, $saveFileType) {

	$home 		= getenv("HOME");
	$filePath 	= "$home/.blaventure/$nick.$saveFileType";

	return $filePath;
}

function initDynastySaveData($nick) {

	DEBUG_echo("initDynastySave");

	$initialSaveData = new DynastySaveData();

	return $initialSaveData;
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

function checkIfFileExists($nick, $fileType) {

	$filePath = getSaveFilePath($nick, $fileType);

	return file_exists($filePath);
}

function checkIfNewGame($nick) {

	$characterFileExists 	= checkIfFileExists($nick, SaveFileType::Character);
	$mapFileExists 			= checkIfFileExists($nick, SaveFileType::Map);
	$dynastyFileExists 		= checkIfFileExists($nick, SaveFileType::Dynasty);

	return !$characterFileExists || !$mapFileExists || !$dynastyFileExists;
}

/*
 *	Called every time the player moves, or gains an item.
 * 	Writes to two files at $HOME/.blaventure/$nick.[char/map]
 */
function saveGame($nick, $saveFileType, $data = null) {

	$saveData	= null;
	
	$newGame	= checkIfNewGame($nick);
	$filePath 	= getSaveFilePath($nick, $saveFileType);

	if ( $newGame ) {

		switch ( $saveFileType ) {

			case SaveFileType::Character: {
				$saveData = initCharacterSaveData($nick);
			}
			break;

			case SaveFileType::Map: {
				$saveData = initMapSaveData($nick);
			}
			break;

			case SaveFileType::Dynasty: {
				$saveData = initDynastySaveData($nick);
			}
			break;

			default:
			break;
		}
	}
	else {
		
		if ( !isset($data) ) {
			echo "ERROR: No save data supplied!\n";
			exit(5);
		}

		$saveData = $data;
	}

	if ( !is_null($saveData) ) {
		writeSave($saveData, $filePath);		
	}
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

function checkInputFragments( $fragments, $input, $charData, $mapData, $dynData = null) {

	$match	= false;

	$isHelp = strcasecmp($input, "help") == 0;

	foreach ( $fragments as $key => $fragment ) {

		if ( !$isHelp && $fragment->Matches($input, $charData) ) {

			$fragment->FireCallback($charData, $mapData, $dynData);
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

function classSelect($input, $charData, $dynData, $charName) {

	global $classSelect;

	checkInputFragments($classSelect->commands, $input, $charData, null);

	$setClass = false;

	// This should be set in a callback.
	if ( isset($charData->class) ) {

		echo "Greetings $charName $dynData->name, the level 1 $charData->class! Your adventure begins now! ('help' for commands)\n";
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

function adventuring($input, $charData, $mapData, $dynData) {

	global $adventuring;

	addShopFragmentIfNeeded($charData, $mapData);

	checkInputFragments($adventuring->commands, $input, $charData, $mapData, $dynData);
}

function resting($input, $charData, $mapData) {

	global $resting;

	checkInputFragments($resting->commands, $input, $charData, $mapData);
}

function combat($input, $charData, $mapData, $dynData) {

	global $combat;

	checkInputFragments($combat->commands, $input, $charData, $mapData, $dynData);
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

function shopping($input, $charData, $mapData) {

	global $shopping;

	$shopping->generateInputFragments($charData, $mapData);

	checkInputFragments($shopping->commands, $input, $charData, $mapData);
}

function dynasty($input, $charData, $mapData, $dynData) {

	global $dynasty;

	$dynasty->generateInputFragments($charData, $dynData);

	checkInputFragments($dynasty->commands, $input, $charData, $mapData, $dynData);
}

// Input of the form !adv "action", with nick supplied from args
function main() {

	$nick 		= getNickFromArgs();
	$mapData 	= null;
	$charData 	= null;

	// This will force everyone into creating a Dynasty save.
	// We patching shit, yo.
	$dynPatch	= false;

	$dynPath 	= getSaveFilePath($nick, SaveFileType::Dynasty);
	if ( !file_exists($dynPath) ) {

		saveGame($nick, SaveFileType::Dynasty);

		$dynPatch = true;
	}

	if ( !checkIfNewGame($nick) ) {

		// Load character save data.
		$charFilePath 	= getSaveFilePath($nick, SaveFileType::Character);
		$charData 		= readSave($charFilePath);
		$charDataDirty	= false;

		// Load map save data.
		$mapFilePath 	= getSaveFilePath($nick, SaveFileType::Map);
		$mapData 		= readSave($mapFilePath);
		$mapDataDirty	= false;

		// Load dynasty save data.
		$dynFilePath 	= getSaveFilePath($nick, SaveFileType::Dynasty);
		$dynData 		= readSave($dynFilePath);
		$dynDataDirty	= false;

		// Put everyone into the dynasty initialisation state, just this once.
		$noName 	= strcasecmp($dynData->name, "") == 0;
		$wrongState = $charData->state != GameStates::DynastyInit;

		if ( empty($dynData) || ( $noName && $wrongState ) ) {

			if ( is_null($charData->patchState) ) {
				$charData->patchState 		= $charData->state;
				$charData->patchPrevState 	= $charData->previousState;				
			}

			DEBUG_echo("Dynasty patching...");
			StateManager::ChangeState($charData, GameStates::DynastyInit);
		}

		// Ensure it's sane.
		if ( empty($charData) || empty($mapData) ) {
			echo "ERROR: Save data's fucked.\n";
			exit(3);
		}

		// Read STDIN for input.
		$input = readStdin();

		switch ( $charData->state ) {

			case GameStates::DynastySplash: {
				
				DEBUG_echo("DynastySplash");

				echo "Your Dynasty begins, and needs a name. Choose your name wisely - you cannot alter history.\n";

				StateManager::ChangeState($charData, GameStates::DynastyInit);

				$charDataDirty = true;
			}
			break;

			case GameStates::DynastyInit: {
				
				DEBUG_echo("DynastyInit");

				// Validate input.
				$validName = preg_match("/^[a-zA-Z]{1,16}$/", $input, $output);

				if ( !$validName ) {
					echo "Please enter a valid name. Letters only, between 1 and 16 characters.\n";
					return;
				}

				$dynData->name = $input;

				$output = "The Dynasty of $input begins! Onwards, to adventure!";
				if ( strcasecmp($charData->name, $nick) == 0 ) {
					$output .= " Please choose a name for your character!";
				}

				echo "$output\n";

				// Hook back up to where we were.
				$charData->state 			= $charData->patchState;
				$charData->previousState 	= $charData->patchPrevState;

				$charDataDirty 	= true;
				$dynDataDirty	= true;
			}
			break;

			case GameStates::NameSelect: {

				DEBUG_echo("NameSelect");

				if ( strcmp($input, "") == 0 ) {
					echo "Please enter a name!\n";
					exit(13);
				}

				$output = "Please choose a class for $input $dynData->name: ";

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

				$setClass = classSelect($input, $charData, $dynData, $charData->name);

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

				adventuring($input, $charData, $mapData, $dynData);

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

				combat($input, $charData, $mapData, $dynData);

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

				$nonCombat = isset($charData->previousState) && ( $charData->previousState != GameStates::Combat );

				usingItem($input, $charData, $mapData, $nonCombat);

				$charDataDirty	= true;
			}
			break;

			case GameStates::Shopping: {

				DEBUG_echo("Shopping");

				shopping($input, $charData, $mapData);

				$charDataDirty	= true;
				$mapDataDirty 	= true;
			}
			break;

			case GameStates::Dynasty: {

				DEBUG_echo("Dynasty");

				dynasty($input, $charData, $mapData, $dynData);

				$charDataDirty	= true;
				$dynDataDirty	= true;
			}
			break;

			default:
			break;
		}
	}
	else {
		// Initialise the character save.
		saveGame($nick, SaveFileType::Character);

		// Initialise the map save.
		saveGame($nick, SaveFileType::Map);

		// Prompt for name/dynasty select.
		if ( !$dynPatch ) {
			echo "Welcome! Please choose a name for your character:\n";			
		}
		else {
			echo "Your Dynasty begins, and needs a name. Choose your name wisely - you cannot alter history.\n";
		}
	}

	if ( isset($charData) && $charDataDirty ) {

		// Regenerate health based on time since last input.
		passiveHealthRegen($charData);

		saveGame($nick, SaveFileType::Character, $charData);
	}
	if ( isset($mapData) && $mapDataDirty ) {

		saveGame($nick, SaveFileType::Map, $mapData);
	}
	if ( isset($dynData) && $dynDataDirty ) {

		saveGame($nick, SaveFileType::Dynasty, $dynData);
	}
}

main();