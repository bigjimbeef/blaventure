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

		// Remove the enemy, and go back to adventuring.
		unset($room->occupant);
		$charData->state = GameStates::Adventuring;

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

function attackDamage($level, $attack) { 

	$minDamage 	= $level - floor( 0.3 * $level ) + $attack;
	$maxDamage 	= $level + ceil( 0.3 * $level ) + $attack;

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

	list($damage, $crit) = attackDamage($monster->level, $monster->attack);
	
	$attackType = $crit ? "CRIT" : "hit";
	$fightOutput = " It $attackType" . "s back for $damage!\n";

	playerDamaged($charData, $damage);

	return $fightOutput;
}

function playerAttack(&$charData, &$room, &$monster) {

	list($damage, $crit) = attackDamage($charData->level, $charData->weaponVal);

	$attackType = $crit ? "CRIT" : "hit";
	$fightOutput = "You $attackType the $monster->name for $damage!";

	if ( monsterDamaged($monster, $room, $damage, $charData) ) {

		// It survived. Attacks back.
		$fightOutput .= monsterAttack($charData, $monster);
	}
	else {

		$fightOutput .= " It dies!\n";

		// TODO: Loot

		// TODO: XP
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

$combat->commands[] = new InputFragment(array("attack"), function($charData, $mapData) {

	$room = $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);

	// Must have an occupant to be in combat.
	$monster = $room->occupant;
	
	playerAttack($charData, $room, $monster);
});

$combat->commands[] = new InputFragment(array("spellbook"), function($charData, $mapData) {

	
});
