<?php

class Item {

	public $name;
	public $useLocation;	// ItemUse
	public $consumeCallback;

	function __construct($name, $useLocation, $callback) {

		$this->name = $name;
		$this->useLocation = $useLocation;
		$this->consumeCallback = $callback;
	}

	public function useItem(&$charData, &$mapData) {
		
		call_user_func($this->consumeCallback, $charData, $mapData);
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

$allItems[] = new Item("Health Potion", ItemUse::Either, function($charData) {

	if ( $charData->hp >= $charData->hpMax ) {
		echo "You're already at max HP!\n";

		return false;
	}

	$HEAL_AMOUNT 	= 50;

	$currentHP 		= $charData->hp;
	$charData->hp 	+= $HEAL_AMOUNT;

	$charData->hp 	= min($charData->hp, $charData->hpMax);
	$restoredHP 	= $charData->hp - $currentHP;

	echo "You drink the Health Potion, restoring $restoredHP HP.\n";

	return true;
});
