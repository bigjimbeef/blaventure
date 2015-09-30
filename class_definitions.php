<?php

include_once("name_generator.php");
include_once("procedural_generator.php");

include_once("item_list.php");

class StateManager {

	static function ChangeState(&$charData, $newState) {

		// TODO: Validate state?

		$charData->previousState 	= $charData->state;
		$charData->state			= $newState;
	}
}

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

	// Used for Barbarians
	public $weapon2		= null;		// str
	public $weapon2Val	= 0;

	public $armour		= null;		// str
	public $armourVal	= 0;		// int
	public $gold		= 0;		// int
	public $inventory	= null;

	public $state			= GameStates::NameSelect;
	public $previousState	= null;

	// Used for procedural generation.
	public $randomSeed 	= 0;		// int

	public $restStart	= 0;
	public $restEnd		= 0;
	public $lastInputD	= 0;		// int (timestamp)

	public $spellbook	= null;
	public $nick		= null;
	public $kills		= 0;

	public $rageTurns	= 0;
	public $tentUseCount = 0;

	// Abilities are locked after use in combat (and unlocked on leaving combat)
	public $lockedAbilities = null;

	public $manaCostReductions = null;
}

class MapSaveData {

	public $playerX			= 0;		// int
	public $playerY			= 0;		// int

	// Used for retreating.
	public $lastPlayerX		= 0;		// int
	public $lastPlayerY		= 0;		// int

	public $map				= null;		// Map
}

class DynastySaveData {

	public $level			= 0;

	public $hpBonus 		= 0;
	public $mpBonus			= 0;

	public $atkBonus		= 0;
	public $defBonus		= 0;
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

	function __construct($playerLevel, $distance) {

		// Randomly generate monster level based on player level and distance to the origin.
		$this->InitLevel($playerLevel, $distance);

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

	private function InitLevel($playerLevel, $distance) {

		$chanceInHundred = rand(1, 100);

		// e.g. 0 at 0 distance, 5 at max distance
		$mapHalfSize 	= floor(ProcGen::GetMapSize() / 2);	// 50
		$boundary 		= floor($mapHalfSize / 5);			// 10
		$distanceFactor = floor($distance / $boundary);		// 

		// Level +3 - 5%
		if ( $chanceInHundred > 95 ) {
			$this->level = $playerLevel + 3 + $distanceFactor;
		}
		// Level +2 - 10%
		else if ( $chanceInHundred > 85 ) {
			$this->level = $playerLevel + 2 + $distanceFactor;
		}
		// Level +1 - 20%
		else if ( $chanceInHundred > 70 ) {
			$this->level = $playerLevel + 1 + $distanceFactor;
		}
		// Level == - 50%
		else if ( $chanceInHundred > 20 ) {
			$this->level = $playerLevel + $distanceFactor;
		}
		// Level -1 - 15%
		else {
			$this->level = $playerLevel - 1 + $distanceFactor;
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

		// Monsters scale in a very slightly non-linear way.
		$this->hpMax		= floor(pow($this->level, 1.1) * 10);
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

class ShopEquipment {

	public $name;
	public $level;
	public $gpCost;

	public $type;	// ShopEquipment::Armour or ShopEquipment::Weapon

	function __construct($name, $level, $type) {
		$this->name = $name;
		$this->level = $level;

		// This complicated formula was retrofitted from excel.
		// ROUND(19*EXP(0.15*B21), -1)
		$this->gpCost = round(19 * exp(0.15 * $level), -1);

		$this->type = $type;
	}

	public function getCost() {
		return $this->gpCost;
	}

	const Armour = 0;
	const Weapon = 1;
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

class Shop {

	public $stock = null;
	public $equipment = null;

	public function isItemOrEquipment($itemName) {

		$isItem 		= isset($this->stock[$itemName]);
		$isEquipment 	= isset($this->equipment[$itemName]);

		return $isItem || $isEquipment;
	}

	public function getStockDetailsForItem($itemName, $charData) {

		$output = [];

		if ( isset($this->stock[$itemName]) ) {

			$output[] = $this->stock[$itemName];

			$item = findItem($itemName);
			$output[] = $item->getCost($charData);
			$output[] = null;
		}
		else if ( isset($this->equipment[$itemName]) ) {

			$output[] = 1;

			$shopEquip = $this->equipment[$itemName];
			$output[] = $shopEquip->getCost($charData);
			$output[] = $shopEquip->level;
		}

		return $output;
	}

	private function addStockItem($item) {

		$itemName = $item->name;

		if ( !isset($this->stock[$itemName]) ) {
			$this->stock[$itemName] = 0;
		}

		$this->stock[$itemName]++;
	}

	public function removeStockItem($itemName) {

		$this->stock[$itemName]--;

		if ( $this->stock[$itemName] <= 0 ) {

			// Remove the item from our stock completely if we run out.
			unset($this->stock[$itemName]);
		}
	}

	public function addEquipment($itemName, $itemLevel, $type) {

		$this->equipment[$itemName] = new ShopEquipment($itemName, $itemLevel, $type);
	}

	public function removeEquipment($itemName) {

		unset($this->equipment[$itemName]);
	}

	private function InitRandomEquipment($playerLevel, $distance) {

		// e.g. 0 at 0 distance, 10 at max distance
		$mapHalfSize 	= floor(ProcGen::GetMapSize() / 2);	// 50
		$boundary 		= floor($mapHalfSize / 10);			// 10
		$distanceFactor = floor($distance / $boundary);

		// NB: Can spawn both armour AND weapon.

		// Weapon
		$oneInHundred	= rand(1, 100);
		if ( true || $oneInHundred > 50 ) {

			$weaponName = NameGenerator::Weapon($playerLevel);

			$randomFactor	= rand(-2, 2);
			$weaponLvl		= rand($playerLevel, $playerLevel + $distanceFactor + $randomFactor);
			$weaponLvl		= max(1, $weaponLvl);

			$this->addEquipment($weaponName, $weaponLvl, ShopEquipment::Weapon);
		}

		// Armour
		$oneInHundred	= rand(1, 100);
		if ( $oneInHundred > 50 ) {
		
			$armourName = NameGenerator::Armour($playerLevel);

			$randomFactor	= rand(-2, 2);
			$armourLvl		= rand($playerLevel, $playerLevel + $distanceFactor + $randomFactor);
			$armourLvl		= max(1, $armourLvl);

			$this->addEquipment($armourName, $armourLvl, ShopEquipment::Armour);
		}
	}

	private function getPotionPrefix($playerLevel) {

		$prefixes 		= ["minor ", "lesser ", "", "greater ", "major ", "super ", "giant "];

		$prefixIndex 	= floor($playerLevel / 10);
		$remainder 		= $playerLevel % 10;

		$oneInTen		= rand(1, 10);

		// This is a bit confusing:
		// If we're at level 14, remainder will be 4, and prefixIndex 1
		// If we roll less than a 4, we will use prefixIndex 0 instead
		// Chance to roll >= remainder goes up as level does, so we see higher-level
		// potions as level increases.
		if ( $oneInTen < $remainder ) {
			
			$prefixIndex = max(0, $prefixIndex - 1);
		}

		return $prefixes[$prefixIndex];
	}

	private function InitItemList($playerLevel, $distance) {

		// Potion quality is based on player level.
		$prefix 		= getPotionPrefix($playerLevel);

		// Every shop has these items...
		$healthPotion 	= findItem($prefix . "health potion");
		$magicPotion 	= findItem($prefix . "magic potion");
		$tent			= findItem("tent");

		// ... but in random quantities.
		$numInStock 	= rand(0, 5);
		for ( $i = 0; $i < $numInStock; ++$i ) {
			$this->addStockItem($healthPotion);
		}
		$numInStock 	= rand(0, 3);
		for ( $i = 0; $i < $numInStock; ++$i ) {
			$this->addStockItem($magicPotion);
		}
		$numInStock 	= rand(0, 1);
		for ( $i = 0; $i < $numInStock; ++$i ) {
			$this->addStockItem($tent);
		}

		// Random weapons/armour.
		$this->InitRandomEquipment($playerLevel, $distance);
	}

	public function isEmpty() {

		return empty($this->stock);
	}

	function __construct($playerLevel, $distance) {

		$this->stock = [];

		$this->InitItemList($playerLevel, $distance);
	}
}

class Spell {

	public $name;

	public $mpCost;	
	public $isHeal;

	protected $damageCallback;
	public $isAbility;

	public function Cast(&$charData) {

		return call_user_func($this->damageCallback, $charData);
	}

	function __construct($name, $mp, $isHeal, $damageCallback) {

		$this->name				= $name;
		$this->mpCost 			= $mp;
		$this->isHeal 			= $isHeal;
		$this->damageCallback 	= $damageCallback;

		$this->isAbility 		= false;
	}
}

class Ability extends Spell {

	function __construct($name, $mp, $isHeal, $damageCallback) {

		$this->name				= $name;
		$this->mpCost 			= $mp;
		$this->isHeal 			= $isHeal;
		$this->damageCallback 	= $damageCallback;
		
		$this->isAbility 		= true;
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