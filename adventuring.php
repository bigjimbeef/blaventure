<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("procedural_generator.php");
include_once("name_generator.php");

include_once("spell_list.php");
include_once("spellcasting.php");

include_once("item_list.php");
include_once("usingItem.php");

include_once("class_traits.php");

class Adventuring {

	public function getEquippedItemsStr($charData) {

		//---------------------------------------
		// Barbarian trait.
		global $traitMap;
		$isBarbarian = $traitMap->ClassHasTrait($charData, TraitName::DualWield);

		$equipment = "Weapon: $charData->weapon ($charData->weaponVal)    ";
		if ( $isBarbarian ) {
			$equipment = "Main-hand " . $equipment;
		}

		if ( !$isBarbarian ) {
			$equipment .= "Armour: $charData->armour ($charData->armourVal)";
		}
		else {
			$equipment .= "Off-hand Weapon: $charData->weapon2 ($charData->weapon2Val)";
		}

		return $equipment;
	}

	public $commands = [];
}

$adventuring = new Adventuring();

$adventuring->commands[] = new InputFragment("north", function($charData, $mapData) {

	$x = $mapData->playerX;
	$y = $mapData->playerY;

	$moveText = "You move to the North, ";

	// North means y--
	moveToRoom($x, $y, 0, -1, $mapData, $charData, $moveText);
});
$adventuring->commands[] = new InputFragment("south", function($charData, $mapData) {

	$x = $mapData->playerX;
	$y = $mapData->playerY;

	$moveText = "You move to the South, ";

	// South means y++
	moveToRoom($x, $y, 0, 1, $mapData, $charData, $moveText);
});
$adventuring->commands[] = new InputFragment("east", function($charData, $mapData) {

	$x = $mapData->playerX;
	$y = $mapData->playerY;
	
	$moveText = "You move to the East, ";

	// East means x++
	moveToRoom($x, $y, 1, 0, $mapData, $charData, $moveText);
});
$adventuring->commands[] = new InputFragment("west", function($charData, $mapData) {

	$x = $mapData->playerX;
	$y = $mapData->playerY;

	$moveText = "You move to the West, ";

	// West means x--
	moveToRoom($x, $y, -1, 0, $mapData, $charData, $moveText);
});

// Get the character status
// e.g. Level 3 Barbarian    HP 3/10    MP 2/5
$adventuring->commands[] = new InputFragment("char", function($charData, $mapData, $dynData) {

	function getColouredStats($statName, $charData, $dynData) {

		// This /includes/ the bonus from our dynasty...
		$charStat	= $charData->{$statName};
		$outStr		= $charStat;

		// ... so we remove it if needs be.
		$dynStat	= $dynData->{$statName};
		if ( $dynStat > 0 ) {
			$charStat	-= $dynStat;

			
			$outStr		= "$charStat\x0311+$dynStat\x03\x03";
		}

		return $outStr;
	}

	$char		= "$charData->name $dynData->name, Level $charData->level $charData->class, $charData->hp/$charData->hpMax HP $charData->mp/$charData->mpMax MP. ";

	$persona 	= "";
	$persona	.= ( "P(" . getColouredStats("precision", $charData, $dynData) . "), " );
	$persona	.= ( "E(" . getColouredStats("endurance", $charData, $dynData) . "), " );
	$persona	.= ( "R(" . getColouredStats("reflexes", $charData, $dynData) . "), " );
	$persona	.= ( "S(" . getColouredStats("strength", $charData, $dynData) . "), " );
	$persona	.= ( "O(" . getColouredStats("oddness", $charData, $dynData) . "), " );
	$persona	.= ( "N(" . getColouredStats("nerve", $charData, $dynData) . "), " );
	$persona	.= ( "A(" . getColouredStats("acuity", $charData, $dynData) . ")" );

	$location	= "  @[$mapData->playerX, $mapData->playerY]\n";

	echo ($char . $persona . $location);
});

// Get the character's equipped items
$adventuring->commands[] = new InputFragment("equipment", function($charData, $mapData) {

	global $adventuring;
	$equipment = $adventuring->getEquippedItemsStr($charData);

	echo $equipment . "\n";
});

// Use an item from the inventory
$adventuring->commands[] = new InputFragment("use item", function($charData, $mapData) {

	global $usingItem;

	$inventory = lazyGetInventory($charData);
	if ( empty($inventory->items) ) {

		echo "After a quick rummage in your bag, it looks like you don't have anything of use.\n";
		return;
	}

	$output = "Choose an item: ";

	// We are not in combat, hence the "true"
	$usingItem->generateInputFragments($charData, true);

	foreach ( $usingItem->commands as $fragment ) {

		$item = findItem($fragment->token);

		if ( is_null($item) ) {
			continue;
		}

		// Don't show combat-only items.
		if ( $item->useLocation == ItemUse::CombatOnly ) {
			continue;
		}

		$output .= "$fragment->displayString, ";
	}

	$output = rtrim($output, ", ") . "\n";

	echo $output;

	StateManager::ChangeState($charData, GameStates::UsingItem);
});

// Check your inventory.
$adventuring->commands[] = new InputFragment("inventory", function($charData, $mapData) {

	$inventory = lazyGetInventory($charData);

	$itemStr = $inventory->getContentsAsString();

	if ( !is_null($itemStr) ) {
		$itemStr = rtrim($itemStr, "\n") . ", ";
	}

	$itemStr .= "$charData->gold GP";

	echo $itemStr . "\n";
});

// Get the character's spells
$adventuring->commands[] = new InputFragment("book", function($charData, $mapData) {

	if ( empty($charData->spellbook) ) {
		echo "You don't have any spells in your spellbook.\n";
		return;
	}

	$spells = "";

	global $spellcasting;

	foreach ( $charData->spellbook as $spellName ) {

		$spell = $spellcasting->findSpellOrAbility($spellName, $charData);

		$spells .= "$spellName ($spell->mpCost MP), ";
	}

	$spells = rtrim($spells, ", ") . "\n";

	echo $spells;
});

// Cast a non-combat spell
$adventuring->commands[] = new InputFragment("magic", function($charData, $mapData) {

	if ( empty($charData->spellbook) ) {
		echo "You don't have any spells in your spellbook.\n";
		return;
	}

	// Check if we have a heal spell.
	$haveNonCombat = false;
	$nonCombatSpells = array();

	global $spellcasting;

	foreach ( $charData->spellbook as $spellName ) {

		$spell = $spellcasting->findSpellOrAbility($spellName, $charData);
		if ( $spell->isHeal ) {

			$haveNonCombat = true;
			$nonCombatSpells[] = $spellName;
		}
	}

	if ( !$haveNonCombat ) {
		echo "You don't have any spells you can cast outside of combat!\n";
		return;
	}

	// Check if we have enough mana for one of the spells.
	global $spellcasting;

	$canCastOne = $spellcasting->canCastSpell($charData, true);
	if ( !$canCastOne ) {
		echo "You don't have enough MP to cast any spells!\n";
		return;
	}

	$output = "Choose a spell: ";

	$spellcasting->generateInputFragments($charData);

	foreach ( $spellcasting->commands as $fragment ) {

		$matchingSpell = $spellcasting->findSpellOrAbility($fragment->token, $charData);
		if ( $matchingSpell && !$matchingSpell->isHeal ) {
			continue;
		}

		$output .= "$fragment->displayString, ";
	}

	$output = rtrim($output, ", ") . "\n";

	echo $output;
	
	// Begin non-combat casting.
	StateManager::ChangeState($charData, GameStates::Spellcasting);
});

// Begin resting. 
// 
// Resting is tied into real time. It takes 1 real minute to regen one HP and MP.
$adventuring->commands[] = new InputFragment("rest", function($charData, $mapData) {

	global $traitMap;
	$isPray			= $traitMap->ClassHasTrait($charData, TraitName::Pray);

	$hpDeficit		= $charData->hpMax - $charData->hp;
	$mpDeficit		= $charData->mpMax - $charData->mp;

	// Can't rest at max HP and MP.
	if ( $hpDeficit == 0 && $mpDeficit == 0 ) {

		if ( !$isPray ) {
			echo "You're not really tired. Better find something else to do.\n";			
		}
		else {
			echo "You're not feeling very pious at the minute. I guess you should go look around.\n";
		}
	}
	else {

		// Target output:
		// HP, MP, coords, rest time (mins), wakeup time (hh:mm)

		$restString 		= "";
		$restDuration 		= $hpDeficit > $mpDeficit ? $hpDeficit : $mpDeficit;
		if ( $isPray ) {
			$restDuration	= ceil($restDuration / 2);
		}

		// Reset the streak.
		$streak	= $charData->lazyGetStreak();
		$streak->reset();

		StateManager::ChangeState($charData, GameStates::Resting);

		$date					= new DateTime();
		$charData->restStart 	= $date->getTimeStamp();
		$date->add(new DateInterval('PT' . $restDuration . 'M'));

		$charData->restEnd		= $date->getTimeStamp();
		
		$wakeUpTime = $date->format("H:i");
		if ( !$isPray ) {
			$restString = "You go to sleep for $restDuration minutes. You will awake at $wakeUpTime.";
		}
		else {
			$restString = "You begin praying for $restDuration minutes. You will finish at $wakeUpTime.";
		}

		$restString .= "  $charData->hp/$charData->hpMax HP  $charData->mp/$charData->mpMax MP @[$mapData->playerX, $mapData->playerY]";

		echo $restString . "\n";
	}
});

$adventuring->commands[] = new InputFragment("streak", function($charData, $mapData) {

	$streak 	= $charData->lazyGetStreak();
	$streakStr 	= $streak->getStreakString();

	echo "$streakStr\n";
});

//
// MOVEMENT
//
//

function checkBounds($x, $y) {
	$max = ProcGen::GetMapSize() - 1;

	$inBounds = true;
	if ( $x < 0 || $x > $max ) {
		$inBounds = false;
	}
	if ( $y < 0 || $y > $max ) {
		$inBounds = false;
	}

	return $inBounds;
}

function moveToRoom($x, $y, $xDelta, $yDelta, $mapData, $charData, $moveText) {

	global $procGen;

	$newX = $x + $xDelta;
	$newY = $y + $yDelta;

	if ( !checkBounds($newX, $newY) ) {
		echo "Looks like there's nothing that way...\n";
		return;
	}

	// Cache the current position, for running away.
	$mapData->lastPlayerX = $mapData->playerX;
	$mapData->lastPlayerY = $mapData->playerY;

	$mapData->playerX = $newX;
	$mapData->playerY = $newY;

	$room = $mapData->map->GetRoom($newX, $newY);

	$seenBefore = isset($room);

	if ( !$seenBefore ) {
		$room = $procGen->GenerateRoomForMap($mapData->map, $mapData->playerX, $mapData->playerY, $charData->level);		
	}

	$containsMonster 	= isset($room->occupant) && get_class($room->occupant) == "Monster";
	$containsShop 		= isset($room->occupant) && get_class($room->occupant) == "Shop";
	
	if ( $containsMonster ) {

		$monster 		= $room->occupant;
		$monsterName 	= $monster->name;

		$article		= NameGenerator::GetArticle($monsterName);
		$connedName		= $monster->getConnedNameStr($charData->level);

		if ( !$seenBefore ) {
			$moveText .= "and encounter a Level $monster->level $connedName! It attacks!\n";

			// Combat!
			StateManager::ChangeState($charData, GameStates::Combat);
		}
		else {
			$moveText .= "and encounter the Level $monster->level $connedName again! It attacks again!\n";

			// Combat!
			StateManager::ChangeState($charData, GameStates::Combat);
		}
	}
	else if ( $containsShop ) {
		$moveText .= "and discover a small shop. \"Feel free to br(o)wse!\", the shopkeeper yells.\n";
	}
	else {
		$moveText .= "but this room appears to be empty.\n";
	}

	echo $moveText;
}

//
// Special case: If we are at a shop, we need to add the "browse" option.
//
function addShopFragmentIfNeeded($charData, $mapData) {

	global $adventuring;

	$currentRoom	= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
	if ( is_null($currentRoom) ) {
		return;
	}
	$containsShop 	= isset($currentRoom->occupant) && get_class($currentRoom->occupant) == "Shop";

	if ( $containsShop ) {
	
		$adventuring->commands[] = new InputFragment("browse shop", function($charData, $mapData) use($currentRoom) {

			$shop = $currentRoom->occupant;
			if ( $shop->isEmpty() ) {
				echo "\"Sorry, I'm all sold out right now!\"\n";
				return;
			}

			global $shopping;
			$shopStr = $shopping->getShopString($shop, $charData, $mapData);
			echo $shopStr;

			StateManager::ChangeState($charData, GameStates::Shopping);
		});

		$allocator = new UIDAllocator($adventuring->commands);
		$allocator->Allocate();
	}
}

// Add unique identifiers to commands.
$allocator = new UIDAllocator($adventuring->commands);
$allocator->Allocate();
