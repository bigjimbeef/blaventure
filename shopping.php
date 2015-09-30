<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("class_traits.php");
include_once("inventory.php");

class Shopping {

	public $commands = [];

	private function canAffordItem(&$charData, $gpCost) {

		$affordable = $gpCost <= $charData->gold;
		if ( !$affordable ) {

			$goldMissing = $gpCost - $charData->gold;
			echo "\"Sorry, looks like you'll need another $goldMissing GP for that!\"\n";
			return false;
		}

		return true;
	}

	private function takeMoney($item, $shop, &$charData, $room, $inputString = null) {

		$charData->gold -= $item->gpCost;

		$output = !is_null($inputString) ? $inputString : "";

		if ( !empty($shop->stock) ) {
			$output .= "\"Aha! A fine purchase! Anything else for you today?\"\n";
		}
		else {
			$output .= "\"You've cleaned me out!\", the shopkeeper says with a grin. It looks like this shop is closed.\n";

			// Remove the shop.
			unset($room->occupant);

			StateManager::ChangeState($charData, GameStates::Adventuring);
		}

		echo $output;
	}

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
				
				global $shopping;

				if ( !$shopping->canAffordItem($charData, $item->gpCost) ) {
					return;
				}

				// Remove item from shop.
				$shop->removeStockItem($itemName);

				// Add item to player inventory, and deduct gold.
				$inventory = lazyGetInventory($charData);
				$inventory->addItem($item);

				$shopping->takeMoney($item, $shop, $charData, $room);
			});
		}

		foreach ( $shop->equipment as $equipment ) {

			$this->commands[] = new InputFragment($equipment->name, function($charData, $mapData) use ($equipment, $shop, $room) {

				global $shopping;


				if ( !$shopping->canAffordItem($charData, $equipment->gpCost) ) {
					return;
				}

				$equipString = "You equip your new $equipment->name immediately";
				
				if ( $equipment->type == ShopEquipment::Weapon ) {

					$isBarbarian = strcasecmp($charData->class, "Barbarian") == 0;

					$weapon1Better = $charData->weaponVal >= $equipment->level;
					$weapon2Better = $charData->weapon2Val >= $equipment->level;

					if ( ( !$isBarbarian && $weapon1Better ) || ( $isBarbarian && $weapon1Better && $weapon2Better ) ) {

						echo "\"No point buying my wares if you've got better yourself!\"\n";
						return;
					}

					// Bloody complicated Barbarians
					if ( $isBarbarian ) {

						// Going in the off-hand
						if ( $weapon1Better ) {

							$charData->weapon2 		= $equipment->name;
							$charData->weapon2Val	= $equipment->level;	
						}
						// Weapon 1 isn't better, so move weapon 1 to weapon 2.
						else {

							$equipString .= ", moving your $charData->weapon to your off-hand";

							$charData->weapon2 = $charData->weapon;
							$charData->weapon2Val = $charData->weaponVal;

							$charData->weapon 		= $equipment->name;
							$charData->weaponVal	= $equipment->level;
						}
					}
					else {
						$charData->weapon 		= $equipment->name;
						$charData->weaponVal	= $equipment->level;						
					}
				}
				else if ( $equipment->type == ShopEquipment::Armour ) {

					$charData->armour 		= $equipment->name;
					$charData->armourVal	= $equipment->level;
				}

				$equipString .= ". ";

				// Remove equipment from shop.
				$shop->removeEquipment($equipment->name);

				$shopping->takeMoney($equipment, $shop, $charData, $room, $equipString);
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
