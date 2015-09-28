<?php

include_once("spell_list.php");

class Inventory {

	public $items;			// array of Item names (strings)

	public function addItem($item) {

		// We store the item NAME, not the item itself.
		// This is because we cannot serialise the item callback.
		$this->items[] = $item->name;
	}

	public function removeItem($input) {

		$targetKey = -1;

		foreach( $this->items as $key => $itemName ) {

			if ( strcasecmp($itemName, $input) == 0 ) {

				$targetKey = $key;
				break;
			}
		}

		if ( $targetKey != -1 ) {

			array_splice($this->items, $targetKey, 1);
		}
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
