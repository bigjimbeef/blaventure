<?php

class Item {

	public $name;
	public $useLocation;	// ItemUse
	public $consumeCallback;

	public $gpCost;

	function __construct($name, $useLocation, $cost, $callback) {

		$this->name = $name;
		$this->useLocation = $useLocation;
		$this->gpCost = $cost;

		$this->consumeCallback = $callback;
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

$allItems[] = new Item("Health Potion", ItemUse::Either, 100, function(&$charData) {

	$output = "";

	if ( $charData->hp >= $charData->hpMax ) {
		$output = "You're already at max HP!\n";

		return array(false, $output);
	}

	$HEAL_AMOUNT 	= 50;

	$currentHP 		= $charData->hp;
	$charData->hp 	+= $HEAL_AMOUNT;

	$charData->hp 	= min($charData->hp, $charData->hpMax);
	$restoredHP 	= $charData->hp - $currentHP;

	$output = "You drink the Health Potion, restoring $restoredHP HP.";

	return array(true, $output);
});

$allItems[] = new Item("Magic Potion", ItemUse::Either, 150, function(&$charData) {

	$output = "";

	if ( $charData->mp >= $charData->mpMax ) {
		$output = "You're already at max MP!\n";

		return array(false, $output);
	}

	$HEAL_AMOUNT 	= 50;

	$currentMP 		= $charData->mp;
	$charData->mp 	+= $HEAL_AMOUNT;

	$charData->mp 	= min($charData->mp, $charData->mpMax);
	$restoredMP 	= $charData->mp - $currentMP;

	$output = "You drink the Magic Potion, restoring $restoredMP MP.";

	return array(true, $output);
});

$allItems[] = new Item("Tent", ItemUse::NonCombatOnly, 1000, function(&$charData) {

	$output = "";

	$atMaxHP = $charData->hp >= $charData->hpMax;
	$atMaxMP = $charData->mp >= $charData->mpMax;

	if ( $atMaxMP && $atMaxHP ) {
		$output = "You don't want to get in your Tent at the minute.\n";

		return array(false, $output);
	}

	$charData->mp = $charData->mpMax;
	$charData->hp = $charData->hpMax;

	$output = "You get in your tent and have a nap. A few hours later you emerge, fully rested!";

	return array(true, $output);
});
