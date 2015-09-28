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
$adventuring->commands[] = new InputFragment("char", function($charData, $mapData) {

	$status = "Level $charData->level $charData->class    HP $charData->hp/$charData->hpMax    MP $charData->mp/$charData->mpMax    @[$mapData->playerX, $mapData->playerY]\n";

	echo $status;
});

// Get the character's equipped items
$adventuring->commands[] = new InputFragment("equipment", function($charData, $mapData) {

	$inventory = "$charData->weapon ($charData->weaponVal)    ";

	//---------------------------------------
	// Barbarian trait.
	global $traitMap;
	$isBarbarian = $traitMap->ClassHasTrait($charData, TraitName::DualWield);

	if ( !$isBarbarian ) {
		$inventory .= "$charData->armour ($charData->armourVal)";
	}
	else {
		$inventory .= "$charData->weapon2 ($charData->weapon2Val)";
	}

	$inventory .= "    $charData->gold GP\n";

	echo $inventory;
});

// Use an item from the inventory
$adventuring->commands[] = new InputFragment("use item", function($charData, $mapData) {

	global $usingItem;

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

	$charData->state = GameStates::UsingItem;
});

// Check your inventory.
$adventuring->commands[] = new InputFragment("inventory", function($charData, $mapData) {

	$inventory = lazyGetInventory($charData);

	$itemStr = $inventory->getContentsAsString();

	echo $itemStr;
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
	$charData->state = GameStates::NonCombatSpellcasting;
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

		$restDuration 		= $hpDeficit > $mpDeficit ? $hpDeficit : $mpDeficit;

		if ( !$isPray ) {
			echo "You curl up in a ball and go to sleep. It will take $restDuration minutes to fully restore.\n";
		}
		else {
			$restDuration	= ceil($restDuration / 2);
			echo "You kneel down on the ground, and pray fervently to your God(s). It will take $restDuration minutes.\n";
		}

		$charData->state 		= GameStates::Resting;

		$date					= new DateTime();
		$charData->restStart 	= $date->getTimeStamp();
		$date->add(new DateInterval('PT' . $restDuration . 'M'));

		$charData->restEnd		= $date->getTimeStamp();
	}
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
	
	if ( isset($room->occupant) ) {

		$monster 		= $room->occupant;
		$monsterName 	= $monster->name;

		$article		= NameGenerator::GetArticle($monsterName);

		if ( !$seenBefore ) {
			$moveText .= "and encounter $article level $monster->level $monsterName! It attacks!\n";

			// Combat!
			$charData->state = GameStates::Combat;
		}
		else {
			$moveText .= "and encounter the level $monster->level $monsterName again! It attacks again!\n";

			// Combat!
			$charData->state = GameStates::Combat;
		}
	}
	else {
		$moveText .= "but this room appears to be empty.\n";
	}

	echo $moveText;
}

// Add unique identifiers to commands.
$allocator = new UIDAllocator($adventuring->commands);
$allocator->Allocate();
