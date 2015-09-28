<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("item_list.php");
include_once("inventory.php");

class UsingItem {

	public $commands = [];

	public function useItem($itemName, &$charData, &$mapData) {

		$item = findItem($itemName);

		if ( is_null($item) ) {
			echo "ERROR: Bad item in inventory ($itemName)!\n";
			exit(14);
		}

		$usedItem = $item->useItem($charData, $mapData);

		if ( $usedItem ) {

			$inventory = $charData->inventory;
		}
	}

	public function generateInputFragments(&$charData, $nonCombat = false) {

		$inventory 		= lazyGetInventory($charData);
		$inventoryItems = $inventory->items;

		foreach ( $inventoryItems as $itemName ) {

			$item = findItem($itemName);

			if ( is_null($item) ) {
				echo "ERROR: Bad item in inventory ($itemName)!\n";
				exit(14);
			}

			$useLocation = $item->useLocation;

			if ( $nonCombat && $useLocation == ItemUse::CombatOnly ) {
				continue;
			}
			if ( !$nonCombat && $useLocation == ItemUse::NonCombatOnly ) {
				continue;
			}

			$this->commands[] = new InputFragment($itemName, function($charData, $mapData) use ($itemName, $nonCombat) {
				
				$this->useItem($itemName, $charData, $mapData, $nonCombat);
			});
		}

		$this->commands[] = new InputFragment("cancel", function($charData, $mapData) use ($nonCombat) {
		
			echo $nonCombat . "\n\n";

			if ( !$nonCombat ) {
				echo "You close your bag, and go back to the fight.\n";

				StateManager::ChangeState($charData, GameStates::Combat);
			}
			else {
				echo "Deciding against using an item, you go back to Adventuring.\n";

				StateManager::ChangeState($charData, GameStates::Adventuring);
			}
		});

		// Add unique identifiers to commands.
		$allocator = new UIDAllocator($this->commands);
		$allocator->Allocate();
	}
}

$usingItem = new UsingItem();
