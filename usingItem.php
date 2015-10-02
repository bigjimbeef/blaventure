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

		list($wasUsed, $result) = $item->useItem($charData, $mapData);

		if ( $wasUsed ) {

			$inventory = $charData->inventory;

			$inventory->removeItem($itemName);
		}

		// Combat special case.
		if ( $wasUsed && $charData->previousState == GameStates::Combat ) {

			// Enemy attacks back.
			global $combat;

			$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
			$monster 	= $room->occupant;

			$dummyOutput = "";
			list ($attackType, $damage) = $combat->monsterAttack($charData, $monster, $dummyOutput);

			if ( $damage > 0 ) {
				$result .= " The $monster->name attacks for $damage!\n";
			}
			else {
				$result .= " It swings at you, but misses!\n";
			}
		}

		echo $result;

		// Only change state if we /use/ an item.
		if ( $wasUsed ) {

			if ( $charData->previousState == GameStates::Combat ) {

				StateManager::ChangeState($charData, GameStates::Combat);
			}
			else {

				StateManager::ChangeState($charData, GameStates::Adventuring);
			}	
		}		
	}

	private function deDupeItemFragments($itemName, &$dedupedNames) {

		$existsAlready = false;

		if ( !isset($dedupedNames[$itemName] ) ) {

			$dedupedNames[$itemName] = true;
		}
		else {

			$existsAlready = true;
		}

		return $existsAlready;
	}

	public function generateInputFragments(&$charData, $nonCombat = false) {

		$inventory 		= lazyGetInventory($charData);
		$inventoryItems = $inventory->items;

		// We only want one InputFragment per item TYPE.
		$dedupedNames	= [];

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

			if ( $this->deDupeItemFragments($itemName, $dedupedNames) ) {
				continue;
			}

			$this->commands[] = new InputFragment($itemName, function($charData, $mapData) use ($itemName, $nonCombat) {
				
				global $usingItem;
				return $usingItem->useItem($itemName, $charData, $mapData, $nonCombat);
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
