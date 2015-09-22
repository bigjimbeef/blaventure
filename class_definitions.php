<?php

include_once("name_generator.php");

class CharacterSaveData {
	public $name		= null;		// str
	public $class		= null;		// str
	public $xp			= 0;
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

	public $spellbook	= null;

	public $nick		= null;
}

class MapSaveData {

	public $playerX			= 0;		// int
	public $playerY			= 0;		// int

	// Used for retreating.
	public $lastPlayerX		= 0;		// int
	public $lastPlayerY		= 0;		// int

	public $map				= null;		// Map
}

// Function Matches is called on each InputFragment, and the callback is called if it does match the input.
class InputFragment {

	public 			$token; // str
	public			$uid;	// str

	public			$displayString;	// str

	public function Matches($input) {
		
		$tokenMatch = strcasecmp($input, $this->token) == 0;
		$uidMatch = strcasecmp($input, $this->uid) == 0;

		return $tokenMatch || $uidMatch;
	}

	public function FireCallback($charData, $mapData) {

		call_user_func($this->callback, $charData, $mapData);
	}

	// ctor
	function __construct($inToken, $inCallback) {
		$this->token 	= $inToken;
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

		if ( $this->elite ) {
			$this->name = "ELITE " . $this->name;
		}
	}

	public		$level;			// int
	public 		$name;			// str
	public		$hp;			// int
	public		$hpMax;			// int
	public 		$elite; 		// bool
	public		$attack = 0;	// 0, unless elite.

	private function InitLevel($playerLevel) {

		$chanceInHundred = rand(1, 100);

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

class Map {

	public 	$grid;		// 2D array of Rooms

	function __construct() {

		$grid = array();
	}

	public function GetRoom($x, $y) {

		$room = null;
		if ( isset($this->grid[$x][$y]) ) {
			$room = $this->grid[$x][$y];
		}

		return $room;
	}
}

class Room {

	public 	$x = 0;				// int
	public 	$y = 0;				// int
	public	$occupant = null;	// Monster

	function __construct($x, $y) {

		$this->x = $x;
		$this->y = $y;
	}
}

class Spell {

	public $name;

	public $mpCost;	
	public $isHeal;

	private $damageCallback;

	public function Cast($charData) {

		return call_user_func($this->damageCallback, $charData);
	}

	function __construct($name, $mp, $isHeal, $damageCallback) {

		$this->name				= $name;
		$this->mpCost 			= $mp;
		$this->isHeal 			= $isHeal;
		$this->damageCallback 	= $damageCallback;
	}
}

class UIDAllocator {

	public $fragments;

	function __construct(&$fragments) {

		$this->fragments = $fragments;
	}

	public function Allocate() {

		$uids = array();

		foreach( $this->fragments as $fragment ) {

			$fragmentName 	= $fragment->token;

			$uidSet 		= false;
			$currentChar 	= 0;

			do {
				if ( $currentChar > ( strlen($fragmentName) - 1 ) ) {
					echo "ERROR: Cannot set up UID for $fragmentName!\n";
					exit(12);
				}

				$char = strtolower($fragmentName[$currentChar]);

				// UNIQUE identifier.
				if ( !in_array($char, $uids) ) {

					$fragment->uid 	= $char;
					$uidSet 		= true;

					$uids[]			= $char;

					$this->SetNameWithUID($fragment);
				}

				$currentChar++;

			} while ( !$uidSet );
		}
	}

	private function SetNameWithUID(&$fragment) {

		$str = $fragment->token;
		$uid = $fragment->uid;

		$uidIndicator = "($uid)";

		$pos = stripos($str, $uid);
		if ($pos !== false) {

    		$fragment->displayString = substr_replace($str, $uidIndicator, $pos, strlen($uid));
		}
	}
}