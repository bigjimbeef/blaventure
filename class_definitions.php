<?php

include_once("name_generator.php");

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

	// Used for procedural generation.
	public $randomSeed 	= 0;		// int

	public $restStart	= 0;
	public $restEnd		= 0;
}

class MapSaveData {

	public $playerX		= 0;		// int
	public $playerY		= 0;		// int

	public $map			= null;		// Map
}

// Function Matches is called on each InputFragment, and the callback is called if it does match the input.
class InputFragment {

	public 			$tokens;
	public function Matches($input) {
		
		$matchFound = false;
		foreach ( $this->tokens as $token ) {

			if ( strcasecmp($input, $token) == 0 ) {
				$matchFound = true;
				break;
			}
		}

		return $matchFound;
	}

	public function FireCallback($data) {

		call_user_func($this->callback, $data);
	}

	// ctor
	function __construct($inTokens, $inCallback) {
		$this->tokens 	= $inTokens;
		$this->callback = $inCallback;
	}

	private 		$callback;
}

class Monster {

	function __construct($playerLevel) {

		// Randomly generate monster level based on player level.
		$this->InitLevel($playerLevel);

		$this->InitStats($playerLevel);

		$this->name = NameGenerator::Monster($playerLevel);
	}

	public		$level;			// int
	public 		$name;			// str
	public		$hp;			// int
	public		$hpMax;			// int
	public 		$elite; 		// bool
	public		$attack = 0;	// 0, unless elite.

	private function InitLevel($playerLevel) {

		$chanceInHundred = rand(1, 100);
		echo "Roll: $chanceInHundred\n";

		// Level +3 - 5%
		if ( $chanceInHundred > 95 ) {
			$this->level = $playerLevel + 3;
		}
		// Level +2 - 10%
		else if ( $chanceInHundred > 85 ) {
			$this->level = $playerLevel + 2;
		}
		// Level +1 - 20%
		else if ( $chanceInHundred > 70 ) {
			$this->level = $playerLevel + 1;
		}
		// Level == - 50%
		else if ( $chanceInHundred > 20 ) {
			$this->level = $playerLevel;
		}
		// Level -1 - 15%
		else {
			$this->level = $playerLevel - 1;
			// Don't want level 0 monsters :P
			$this->level = max($this->level, 1);
		}
	}

	private function InitStats($playerLevel) {

		// Firstly, determine if it's elite.
		$chanceInHundred 	= rand(1, 100);
		$this->elite		= $chanceInHundred > 90;

		// Elite monsters simply hit much harder.
		if ( $this->elite ) {
			$this->attack = $this->level;
		}

		$this->hpMax		= $this->level * 10;
		$this->hp			= $this->hpMax;
	}
}

class Room {

	public 	$id;		// int
	public	$occupant;	// Monster
}

class Map {

	public 	$grid;		// 2D array.

	function __construct() {

		$grid = array();
	}
}