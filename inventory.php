<?php

include_once("spell_list.php");

class Inventory {

	public $items;			// array of Item names (strings)
	public $inventorySize;	// int

	public function addItem($item) {

		$addedOk = false;

		if ( count($this->items) < ($this->inventorySize + 1) ) {

			$addedOk = true;

			// We store the item NAME, not the item itself.
			// This is because we cannot serialise the item callback.
			$this->items[] = $item->name;
		}

		return $addedOk;
	}

	public function removeItem($itemName) {

		
	}

	public function getContentsAsString() {

		$output = "";

		$itemNames = [];

		foreach( $this->items as $itemName ) {

			if ( !isset($itemNames[$itemName]) ) {
			
				$itemNames[$itemName] = 0;
			}

			$itemNames[$itemName]++;
		}

		foreach( $itemNames as $itemName => $itemCount ) {

			$output .= "$itemCount $itemName, ";
		}

		$output = rtrim($output, ", ") . "\n";

		return $output;
	}

	public function isFull() {

		return count($this->items) >= $this->inventorySize;
	}

	function __construct() {

		$BASE_SIZE = 5;

		$this->inventorySize = $BASE_SIZE;
		$this->items = [];

		$healthPotion = findItem("health potion");

		if ( $healthPotion ) {

			$this->addItem($healthPotion);
		}
	}
}

function lazyGetInventory(&$charData) {

	if ( !isset($charData->inventory) || is_null($charData->inventory) ) {

		$charData->inventory = new Inventory();
	}

	return $charData->inventory;
}
