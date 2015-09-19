<?php

/*
	!adventure - a Blatech adventure

	Input:
		!adventure or !adv "action"

	Actions:
		North, South, East, West, Attack, Spell, Status, Inventory

		N,S,E,W 	- Go in the direction specified
		Attack		- Attack the enemy in the room (if there is one)
		Spell		- Attack using MP with a random spell
		Status		- Check stats, output in the form:
			Level 3 Barbarian    HP 3/10    MP 2/5
		Inventory	- Check what you're equipped with
			 Dog Sword (3A)    Massive Iron Armour (6D)    1204GP
*/

//-----------------------------------------------
include_once("statics.php");
include_once("class_definitions.php");

// Game mode InputFragments.
include_once("class_select.php");
include_once("adventuring.php");

define("DEBUG", 1);

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

	$initialSaveData = new CharacterSaveData();
	$initialSaveData->name = $nick;

	return $initialSaveData;
}
function initMapSaveData($nick) {

	DEBUG_echo("initMapSave");

	/*
	$initialSaveData = new CharacterSaveData();
	$initialSaveData->name = $nick;

	return $initialSaveData;
	*/

	return array();
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

	echo "nick: $nick\n";

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

function checkInputFragments( $fragments, $input, $data ) {

	foreach ( $fragments as $fragment ) {

		if ( $fragment->Matches($input) ) {

			$fragment->FireCallback($data);
			break;
		}
	}
}

function classSelect($input, $data, $charName) {

	global $classSelect;

	checkInputFragments($classSelect->classes, $input, $data);

	// This should be set in a callback.
	if ( !isset($data->class) ) {
		echo "Enter a valid selection: 1 (Barbarian) 2 (Fighter) 3 (Monk) 4 (Ranger) 5 (Rogue) 6 (Wizard)\n";
	}
	else {
		echo "Greetings $charName, the level 1 $data->class! Your adventure begins now! ('help' for commands)\n";
	}
}

function firstPlay($data) {

	$data->hp = $data->hpMax;
	$data->mp = $data->mpMax;

	$data->level = 1;

	$data->weapon = "Stick";
	$data->weaponVal = 1;
	
	$data->armour = "Skin";
	$data->armourVal = 1;
}

function adventuring($input, $data) {

	global $adventuring;

	checkInputFragments($adventuring->commands, $input, $data);
}

// Input of the form !adv "action", with nick supplied from args
function main() {

	$nick = getNickFromArgs();
	$data = null;

	if ( !checkIfNewGame($nick) ) {

		// Load character save data.
		$filePath 	= getSaveFilePath($nick, true);
		$data 		= readSave($filePath);

		// Ensure it's sane.
		if ( empty($data) ) {
			echo "ERROR: Save data's fucked.\n";
			exit(3);
		}

		switch ( $data->state ) {

			case GameStates::NameSelect: {

				DEBUG_echo("NameSelect");

				// Read input into name.
				$name = readStdin();

				echo "$nick, pick a class for $name: 1 (Barbarian) 2 (Fighter) 3 (Monk) 4 (Ranger) 5 (Rogue) 6 (Wizard):\n";

				$data->name 	= $name;
				$data->state 	= GameStates::ClassSelect;
			}
			break;

			case GameStates::ClassSelect: {

				DEBUG_echo("ClassSelect");

				// Read class choice.
				$class = readStdin();
				$class = strtolower($class);

				classSelect($class, $data, $data->name);

				$data->state = GameStates::FirstPlay;
			}
			break;

			// Initialise the characters
			case GameStates::FirstPlay: {

				DEBUG_echo("FirstPlay");

				firstPlay($data);

				$data->state = GameStates::Adventuring;
			} // purposeful fall-through!

			// The main loop for when we're romping around.
			case GameStates::Adventuring: {

				DEBUG_echo("Adventuring");

				$input = readStdin();

				adventuring($input, $data);
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

	if ( isset($data) ) {

		// TODO: Only saved /changed/ data

		saveGame($nick, true, $data);
		
		saveGame($nick, false, $data);
	}
}

main();