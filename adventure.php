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
			 Dog Sword (**)    Massive Iron Armour (***)    1204GP
*/

//-----------------------------------------------
// statics

abstract class SaveFileType {
	const Character	= "char";
	const Map		= "map";
}

// game state
abstract class GameStates {
	const NameSelect	= 0;
	const ClassSelect	= 1;
	const FirstPlay		= 2;
	const Adventuring	= 3;
}

// classes
abstract class Wizard {
	const Name		= "Wizard";
	const HP		= 10;
	const MP		= 90;
}
abstract class Rogue {
	const Name		= "Rogue";
	const HP		= 70;
	const MP		= 30;
}
abstract class Fighter {
	const Name		= "Fighter";
	const HP		= 90;
	const MP		= 10;
}
abstract class Barbarian {
	const Name		= "Barbarian";
	const HP		= 100;
	const MP		= 0;
}
abstract class Ranger {
	const Name		= "Ranger";
	const HP		= 50;
	const MP		= 50;
}
abstract class Monk {
	const Name		= "Monk";
	const HP		= 30;
	const MP		= 70;
}
// /classes

// /statics
//-----------------------------------------------

class CharacterSaveData {
	public $name		= null;		// str
	public $class		= null;		// str
	public $level		= 0;		// int
	public $hp			= 0;		// int
	public $hpMax		= 0;		// int
	public $mp			= 0;		// int
	public $mpMax		= 0;		// int
	public $weapon		= null;		// str
	public $weaponVal	= 0;		// int
	public $armour		= null;		// str
	public $armourVal	= 0;		// int
	public $gold		= 0;		// int

	public $state		= GameStates::NameSelect;
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

function initSaveData($nick) {

	$initialSaveData = new CharacterSaveData();
	$initialSaveData->name = $nick;

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
function saveGame($nick, $isCharSave) {

	$saveData	= null;
	
	$newGame	= checkIfNewGame($nick);
	$filePath 	= getSaveFilePath($nick, $isCharSave);

	if ( $newGame ) {
		$saveData = initSaveData($nick);
	}
	else {
		$saveData = readSave($filePath);	
	}

	// Add new data to the save file, and write it out.

	// TODO: Add new

	writeSave($saveData, $filePath);
}


function getNickFromArgs() {

	global $argv;

	if ( !isset($argv) ) {
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

	return $input;
}

// Input of the form !adv "action", with nick supplied from args
function main() {

	$nick = getNickFromArgs();

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

				// Read input into name.
				$name = readStdin();

				echo "NAME: $name\n\n";
			}
			break;

			case GameStates::ClassSelect: {

				// Read class choice.
			}
			break;

			case GameStates::FirstPlay: {

				// TODO: Necessary?!
			}
			break;

			case GameStates::Adventuring: {

				// TODO: A game.
			}
			break;

			default: {
				echo "poo";
			}
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
}

main();