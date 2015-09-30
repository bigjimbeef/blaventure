<?php

class Item {

	public $name;
	public $useLocation;	// ItemUse
	public $consumeCallback;

	public $gpCost;			// usually int, can be function

	function __construct($name, $useLocation, $cost, $callback) {

		$this->name = $name;
		$this->useLocation = $useLocation;
		$this->gpCost = $cost;

		$this->consumeCallback = $callback;
	}

	public function getCost($charData) {

		$outCost = $this->gpCost;

		if ( is_callable($this->gpCost) ) {

			$outCost = call_user_func($this->gpCost, $charData);
		}

		return $outCost;
	}

	public function useItem(&$charData, &$mapData) {
		
		return call_user_func($this->consumeCallback, $charData, $mapData);
	}
}

$allItems = array();

function findItem($itemName) {

	global $allItems;

	$outItem = null;

	foreach ( $allItems as $item ) {

		if ( strcasecmp($item->name, $itemName) == 0 ) {

			$outItem = $item;
			break;
		}
	}

	// We return a clone of the item, as you might need more
	// than one of them in your inventory at one time.
	if ( !is_null($outItem) ) {
		$outItem = clone $outItem;
	}
	
	return $outItem;
}

// NOTE: Item callbacks return true or false depending on if they were used or not.

function healthPotion($restoreAmount, &$charData) {

	$output = "";

	if ( $charData->hp >= $charData->hpMax ) {
		$output = "You're already at max HP!\n";

		return array(false, $output);
	}

	$currentHP 		= $charData->hp;
	$charData->hp 	+= $restoreAmount;

	$charData->hp 	= min($charData->hp, $charData->hpMax);
	$restoredHP 	= $charData->hp - $currentHP;

	$output = "You drink the Health Potion, restoring $restoredHP HP.";

	return array(true, $output);
}

function magicPotion($restoreAmount, &$charData) {

	$output = "";

	if ( $charData->mp >= $charData->mpMax ) {
		$output = "You're already at max MP!\n";

		return array(false, $output);
	}

	$currentMP 		= $charData->mp;
	$charData->mp 	+= $restoreAmount;

	$charData->mp 	= min($charData->mp, $charData->mpMax);
	$restoredMP 	= $charData->mp - $currentMP;

	$output = "You drink the Magic Potion, restoring $restoredMP MP.";

	return array(true, $output);
}

// Level 1-10
$allItems[] = new Item("Minor Health Potion", ItemUse::Either, 50, function(&$charData) {

	return healthPotion(50, $charData);
});
$allItems[] = new Item("Minor Magic Potion", ItemUse::Either, 75, function(&$charData) {

	return magicPotion(25, $charData);
});

// Level 11-20
$allItems[] = new Item("Lesser Health Potion", ItemUse::Either, 200, function(&$charData) {

	return healthPotion(100, $charData);
});
$allItems[] = new Item("Lesser Magic Potion", ItemUse::Either, 300, function(&$charData) {

	return magicPotion(50, $charData);
});

// Level 21-30
$allItems[] = new Item("Health Potion", ItemUse::Either, 1000, function(&$charData) {

	return healthPotion(150, $charData);
});
$allItems[] = new Item("Magic Potion", ItemUse::Either, 1500, function(&$charData) {

	return magicPotion(75, $charData);
});

// Level 31-40
$allItems[] = new Item("Greater Health Potion", ItemUse::Either, 4000, function(&$charData) {

	return healthPotion(200, $charData);
});
$allItems[] = new Item("Greater Magic Potion", ItemUse::Either, 6000, function(&$charData) {

	return magicPotion(100, $charData);
});

// Level 41-50
$allItems[] = new Item("Major Health Potion", ItemUse::Either, 10000, function(&$charData) {

	return healthPotion(250, $charData);
});
$allItems[] = new Item("Major Magic Potion", ItemUse::Either, 15000, function(&$charData) {

	return magicPotion(125, $charData);
});

// Level 51-60
$allItems[] = new Item("Super Health Potion", ItemUse::Either, 40000, function(&$charData) {

	return healthPotion(300, $charData);
});
$allItems[] = new Item("Super Magic Potion", ItemUse::Either, 60000, function(&$charData) {

	return magicPotion(150, $charData);
});

// Level 61+
$allItems[] = new Item("Giant Health Potion", ItemUse::Either, 100000, function(&$charData) {

	return healthPotion(350, $charData);
});
$allItems[] = new Item("Giant Magic Potion", ItemUse::Either, 150000, function(&$charData) {

	return magicPotion(175, $charData);
});

$allItems[] = new Item("Tent", ItemUse::NonCombatOnly, 
	// Cost function
	function($charData) {

		$BASE 		= 500;
		$usedFactor = pow(2, $charData->tentUseCount);

		return $BASE * $usedFactor;
	},
	// Use callback
	function(&$charData) {

		$output = "";

		$atMaxHP = $charData->hp >= $charData->hpMax;
		$atMaxMP = $charData->mp >= $charData->mpMax;

		if ( $atMaxMP && $atMaxHP ) {
			$output = "You don't want to get in your Tent at the minute.\n";

			return array(false, $output);
		}

		$charData->mp = $charData->mpMax;
		$charData->hp = 1;//$charData->hpMax;

		$output = "You get in your tent and have a nap. A few hours later you emerge, fully rested!";

		// We've used a tent. Up the count!
		$charData->tentUseCount++;

		return array(true, $output);
	}
);
