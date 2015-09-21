<?php

include_once("statics.php");
include_once("class_definitions.php");

class Looting {

	public $commands = [];
}

$looting = new Looting();

function checkForLootDrop($monster, &$charData) {

	$isElite	= isset($monster->elite);

	// TODO
}

// True on level up, false otherwise
function giveXP($monster, &$charData) {

	$isElite	= isset($monster->elite);

	// FLOOR.MATH(POWER(1.8, level))
	$xpAwarded	= floor(pow(1.8, $monster->level));

	$xpForNextLevel	= pow(2, $charData->level);
	$currentXP		= $charData->xp;

	$currentXP += $xpAwarded;
	
	// Level up!
	if ( $currentXP >= $xpForNextLevel ) {

		$charData->level++;

		$carryOverXP 	= $xpForNextLevel - $currentXP;
		$charData->xp 	= $carryOverXP;

		echo "LEVEL UP! Choose to increase your HP (1) or MP (2):\n";

		return true;
	}
	// Just get some XP, yo.
	else {

		$xpToLevel = $xpForNextLevel - $currentXP;
		$nextLevel = $charData->level + 1;
		echo "You gained $xpAwarded XP. $xpToLevel until level $nextLevel.\n";

		$charData->xp = $currentXP;
	}

	return false;
}

$looting->commands[] = new InputFragment(array("loot"), function($charData, $mapData) {

	$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
	$monster 	= $room->occupant;
	
	$levelledUp	= giveXP($monster, $charData);

	if ( $levelledUp ) {
		$charData->state = GameStates::LevelUp;
	}
	else {
		$charData->state = GameStates::Adventuring;
	}

	unset($room->occupant);
});
