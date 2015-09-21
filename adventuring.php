<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("procedural_generator.php");
include_once("name_generator.php");

include_once("spell_list.php");

class Adventuring {

	public $commands = [];
}

$adventuring = new Adventuring();

// Get the character status
// e.g. Level 3 Barbarian    HP 3/10    MP 2/5
$adventuring->commands[] = new InputFragment(array("status"), function($charData, $mapData) {

	$status = "Level $charData->level $charData->class    HP $charData->hp/$charData->hpMax    MP $charData->mp/$charData->mpMax    @[$mapData->playerX, $mapData->playerY]\n";

	echo $status;
});

// Get the character inventory
// e.g. Level 3 Barbarian    HP 3/10    MP 2/5
$adventuring->commands[] = new InputFragment(array("inventory", "items"), function($charData, $mapData) {

	$inventory = "$charData->weapon ($charData->weaponVal)    $charData->armour ($charData->armourVal)    $charData->gold GP\n";

	echo $inventory;
});

// Get the character's spells
// e.g. Level 3 Barbarian    HP 3/10    MP 2/5
$adventuring->commands[] = new InputFragment(array("spellbook"), function($charData, $mapData) {

	if ( empty($charData->spellbook) ) {
		echo "You don't have any spells in your spellbook.\n";
		return;
	}

	$spells = "";

	foreach ( $charData->spellbook as $spellName ) {

		$spell = findSpell($spellName);

		$spells .= "$spellName ($spell->mpCost MP)  ";
	}

	$spells = rtrim($spells) . "\n";

	echo $spells;
});

// Begin resting. 
// 
// Resting is tied into real time. It takes 1 real minute to regen one HP and MP.
$adventuring->commands[] = new InputFragment(array("rest", "sleep"), function($charData, $mapData) {

	$hpDeficit		= $charData->hpMax - $charData->hp;
	$mpDeficit		= $charData->mpMax - $charData->mp;

	// Can't rest at max HP and MP.
	if ( $hpDeficit == 0 && $mpDeficit == 0 ) {

		echo "You're not really tired. Better find something else to do.\n";
	}
	else {

		$restDuration 		= $hpDeficit > $mpDeficit ? $hpDeficit : $mpDeficit;

		echo "You curl up in a ball and go to sleep. It will take $restDuration minutes to fully restore.\n";
		$charData->state 		= GameStates::Resting;

		$charData->restStart 	= time();

		$toMinutes				= 60;
		$charData->restEnd		= time() + ( $restDuration * $toMinutes );
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
			$moveText .= "and encounter the $monsterName again! It attacks again!\n";

			// Combat!
			$charData->state = GameStates::Combat;
		}
	}
	else {
		$moveText .= "but this room appears to be empty.\n";
	}

	echo $moveText;
}

$adventuring->commands[] = new InputFragment(array("north", "n"), function($charData, $mapData) {

	$x = $mapData->playerX;
	$y = $mapData->playerY;

	$moveText = "You move to the North, ";

	// North means y--
	moveToRoom($x, $y, 0, -1, $mapData, $charData, $moveText);
});
$adventuring->commands[] = new InputFragment(array("south", "s"), function($charData, $mapData) {

	$x = $mapData->playerX;
	$y = $mapData->playerY;

	$moveText = "You move to the South, ";

	// South means y++
	moveToRoom($x, $y, 0, 1, $mapData, $charData, $moveText);
});
$adventuring->commands[] = new InputFragment(array("east", "e"), function($charData, $mapData) {

	$x = $mapData->playerX;
	$y = $mapData->playerY;
	
	$moveText = "You move to the East, ";

	// East means x++
	moveToRoom($x, $y, 1, 0, $mapData, $charData, $moveText);
});
$adventuring->commands[] = new InputFragment(array("west", "w"), function($charData, $mapData) {

	$x = $mapData->playerX;
	$y = $mapData->playerY;

	$moveText = "You move to the West, ";

	// West means x--
	moveToRoom($x, $y, -1, 0, $mapData, $charData, $moveText);
});