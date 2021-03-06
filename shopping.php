<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("class_traits.php");
include_once("inventory.php");
include_once("adventuring.php");

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

		$charData->gold -= $item->getCost($charData);

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

	public function getShopString($shop, $charData, $mapData) {

		if ( empty($this->commands) ) {
			
			$this->generateInputFragments($charData, $mapData, true);			
		}

		$contents = "For sale: ";
		foreach ( $this->commands as $fragment ) {
			 
			$token = $fragment->token;

			if ( !$shop->isItemOrEquipment($token) ) {
				continue;
			}

			list($quantity, $cost, $level) = $shop->getStockDetailsForItem($token, $charData);

			// Item
			if ( is_null($level) ) {

				$contents .= "$quantity $fragment->displayString ($cost GP), ";
			}
			// Equipment
			else {

				$contents .= "Level $level $fragment->displayString ($cost GP), ";
			}
		}

		$contents = rtrim($contents, ", ");

		$contents .= "  [You have $charData->gold GP]\n";

		return $contents;
	}

	public function generateInputFragments(&$charData, &$mapData, $justItems = false) {

		// To be in here, we need to have a shop at our current location.
		$room = $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
		$shop = $room->occupant;

		if ( !$justItems ) {

			$this->commands[] = new InputFragment("check stock", function($charData, $mapData) use ($shop) {

				global $shopping;
				$shopStr = $shopping->getShopString($shop, $charData, $mapData);

				echo $shopStr;
			});
		}
		if ( !$justItems ) {

			$this->commands[] = new InputFragment("leave", function($charData, $mapData) {

				echo "\"Maybe next time, eh?\", the shopkeeper drawls at you as you walk away.\n";

				StateManager::ChangeState($charData, GameStates::Adventuring);
			});
		}
		if ( !$justItems ) {

			$this->commands[] = new InputFragment("equipment", function($charData, $mapData) {

				global $adventuring;
				$equipment = $adventuring->getEquippedItemsStr($charData);

				echo $equipment . "\n";
			});
		}

		foreach ( $shop->stock as $itemName => $quantity ) {

			$item = findItem($itemName);

			$this->commands[] = new InputFragment($itemName, function($charData, $mapData) use ($item, $itemName, $shop, $room) {
				
				global $shopping;

				if ( !$shopping->canAffordItem($charData, $item->getCost($charData)) ) {
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


				if ( !$shopping->canAffordItem($charData, $equipment->getCost($charData)) ) {
					return;
				}

				$equipString = "You equip your new $equipment->name immediately";
				$isBarbarian = strcasecmp($charData->class, "Barbarian") == 0;
				
				if ( $equipment->type == ShopEquipment::Weapon ) {

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

					$armourBetter = $charData->armourVal >= $equipment->level;

					if ( $isBarbarian ) {

						echo "\"Not sure you'd know what to do with that!\"\n";
						return;
					}
					else if ( $armourBetter ) {
						
						echo "\"No point buying my wares if you've got better yourself!\"\n";
						return;
					}

					$charData->armour 		= $equipment->name;
					$charData->armourVal	= $equipment->level;
				}

				$equipString .= ". ";

				// Remove equipment from shop.
				$shop->removeEquipment($equipment->name);

				$shopping->takeMoney($equipment, $shop, $charData, $room, $equipString);
			});
		}

		// Add unique identifiers to commands.
		$allocator = new UIDAllocator($this->commands);
		$allocator->Allocate();
	}
}

$shopping = new Shopping();

// Add unique identifiers to commands.
$allocator = new UIDAllocator($shopping->commands);
$allocator->Allocate();
