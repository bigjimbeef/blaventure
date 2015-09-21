<?php 

include_once("statics.php");
include_once("class_definitions.php");

class Combat {

	public $commands = [];
}

$combat = new Combat();

function monsterDamaged($monster, $room, $damage, $charData) {

	$survived = true;

	$monster->hp -= $damage;

	if ( $monster->hp <= 0 ) {

		$survived = false;
	}

	return $survived;
}

function playerDamaged(&$charData, $damage) {

	$charData->hp -= $damage;

	if ( $charData->hp <= 0 ) {

		// TODO: death.
	}
}

function attackDamage($level, $attack, $isMonster = false) { 

	if ( !$isMonster ) {

		$minDamage = $level;
		$maxDamage = ceil(1.5 * $level);
	}
	else {

		$minDamage 	= $level - floor( 0.3 * $level ) + $attack;
		$maxDamage 	= $level + ceil( 0.3 * $level ) + $attack;
	}

	$damage		= rand($minDamage, $maxDamage);
	$crit		= false;

	$chanceIn20	= rand(1, 20);
	if ( $chanceIn20 == 20 ) {
		$crit = true;

		$damage *= 2;
	}

	return array($damage, $crit);
}

function monsterAttack(&$charData, $monster) {

	list($damage, $crit) = attackDamage($monster->level, $monster->attack, true);
	
	$attackType = $crit ? "CRIT" : "hit";

	playerDamaged($charData, $damage);

	return array($attackType, $damage);
}

function playerAttack(&$charData, &$room, &$monster) {

	list($damage, $crit) = attackDamage($charData->level, $charData->weaponVal);

	$attackType = $crit ? "CRIT" : "hit";
	$fightOutput = "You $attackType the $monster->name for $damage!";

	if ( monsterDamaged($monster, $room, $damage, $charData) ) {

		// It survived. Attacks back.
		list ($attackType, $damage) = monsterAttack($charData, $monster);

		$fightOutput .= (" It $attackType" . "s back for $damage damage!\n");
	}
	else {

		$fightOutput .= " It dies!\n";

		// Move to the looting state.
		$charData->state = GameStates::Looting;
	}

	$fightOutput .= "\n";

	echo $fightOutput;
}

$combat->commands[] = new InputFragment(array("", "status"), function($charData, $mapData) {

	$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
	$monster 	= $room->occupant;

	$status = "Level $monster->level $monster->name ($monster->hp/$monster->hpMax)    You ($charData->hp/$charData->hpMax)\n";

	echo $status;
});

$combat->commands[] = new InputFragment(array("attack", "a"), function($charData, $mapData) {

	$room = $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);

	// Must have an occupant to be in combat.
	$monster = $room->occupant;
	
	playerAttack($charData, $room, $monster);
});

$combat->commands[] = new InputFragment(array("spellbook"), function($charData, $mapData) {

	
});

$combat->commands[] = new InputFragment(array("run"), function($charData, $mapData) {

	$chanceInSix = rand(1,6);
	// 5+ to escape
	if ( $chanceInSix < 5 ) {

		$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
		$monster 	= $room->occupant;

		list ($attackType, $damage) = monsterAttack($charData, $monster);

		echo "You get caught and $attackType for $damage damage!\n";
	}
	else {

		echo "You scurry back to the last room!\n";

		$charData->state = GameStates::Adventuring;

		$mapData->playerX = $mapData->lastPlayerX;
		$mapData->playerY = $mapData->lastPlayerY;
	}
	
});
