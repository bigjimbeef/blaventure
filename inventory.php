<?php

include_once("spell_list.php");

class Inventory {

	public $items;			// array of Item names (strings)

	public function addItem($item) {

		// We store the item NAME, not the item itself.
		// This is because we cannot serialise the item callback.
		$this->items[] = $item->name;
	}

	public function removeItem($itemName) {

		
	}

	public function getContentsAsString() {

		$output = null;
		if ( empty($this->items) ) {

			return $output;
		}

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

	function __construct() {

		$this->items = [];
	}
}

function lazyGetInventory(&$charData) {

	if ( !isset($charData->inventory) || is_null($charData->inventory) ) {

		$charData->inventory = new Inventory();
	}

	return $charData->inventory;
}
