<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("class_traits.php");
include_once("inventory.php");

class Shopping {

	public $commands = [];

	public function generateInputFragments(&$charData, &$mapData) {

		// To be in here, we need to have a shop at our current location.
		$room = $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
		$shop = $room->occupant;

		$this->commands[] = new InputFragment("check", function($charData, $mapData) use ($shop) {

			$shopContents = $shop->getContentsAsString();

			echo $shopContents . "\n";
		});

		foreach ( $shop->stock as $itemName => $quantity ) {

			$item = findItem($itemName);

			$this->commands[] = new InputFragment($itemName, function($charData, $mapData) use ($item, $itemName, $shop, $room) {
				
				// Can we afford it?
				$affordable = $item->gpCost <= $charData->gold;
				if ( !$affordable ) {

					$goldMissing = $item->gpCost - $charData->gold;
					echo "\"Sorry, looks like you'll need another $goldMissing GP for that!\"\n";
					return;
				}

				// Remove item from shop.
				$shop->removeStockItem($itemName);

				// Add item to player inventory, and deduct gold.
				$inventory = lazyGetInventory($charData);
				$inventory->addItem($item);

				$charData->gold -= $item->gpCost;

				if ( !empty($shop->stock) ) {
					echo "\"Aha! A fine purchase! Anything else for you today?\"\n";
				}
				else {
					echo "\"You've cleaned me out!\", the shopkeeper says with a grin. It looks like this shop is closed.\n";

					// Remove the shop.
					unset($room->occupant);

					StateManager::ChangeState($charData, GameStates::Adventuring);
				}
			});
		}

		$this->commands[] = new InputFragment("leave", function($charData, $mapData) {

			echo "\"Maybe next time, eh?\", the shopkeeper drawls at you as you walk away.\n";

			StateManager::ChangeState($charData, GameStates::Adventuring);
		});

		// Add unique identifiers to commands.
		$allocator = new UIDAllocator($this->commands);
		$allocator->Allocate();
	}
}

$shopping = new Shopping();

// Add unique identifiers to commands.
$allocator = new UIDAllocator($shopping->commands);
$allocator->Allocate();
