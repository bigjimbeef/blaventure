<?php 

include_once("statics.php");
include_once("class_definitions.php");

class Combat {

	public $commands = [];

	public function monsterDamaged($monster, $room, $damage, $charData) {

		$survived = true;

		$monster->hp -= $damage;

		if ( $monster->hp <= 0 ) {

			$survived = false;
		}

		return $survived;
	}

	public function playerDamaged(&$charData, $damage) {

		$charData->hp -= $damage;

		if ( $charData->hp <= 0 ) {

			// TODO: death.
		}
	}

	// Note: check spreadsheet for reference to these arcane formulae!
	public function attackDamage($level, $attack) { 

		$minDamage = floor(1.5 * ($level + 1)) + $attack;
		$maxDamage = (2 * ($level + 1)) + $attack;

		$damage		= rand($minDamage, $maxDamage);
		$crit		= false;

		$chanceIn20	= rand(1, 20);
		if ( $chanceIn20 == 20 ) {
			$crit = true;

			$damage *= 2;
		}

		return array($damage, $crit);
	}

	public function monsterAttack(&$charData, $monster) {

		list($damage, $crit) = $this->attackDamage($monster->level, $monster->attack);
		
		$attackType = $crit ? "CRIT" : "hit";

		$this->playerDamaged($charData, $damage);

		return array($attackType, $damage);
	}

	public function playerAttack(&$charData, &$room, &$monster, $spellDmg = null, $spellText = null) {

		$changedState = false;

		list($damage, $crit) = $this->attackDamage($charData->level, $charData->weaponVal);

		// Used when spellcasting.
		if ( $spellDmg ) {
			$damage = $spellDmg;
		}

		$attackType = $crit ? "CRIT" : "hit";
		$fightOutput = "You $attackType the $monster->name for $damage!";

		// Used when spellcasting.
		if ( $spellText ) {
			$fightOutput = $spellText;
		}

		if ( $this->monsterDamaged($monster, $room, $damage, $charData) ) {

			// It survived. Attacks back.
			list ($attackType, $damage) = $this->monsterAttack($charData, $monster);

			$fightOutput .= (" It $attackType" . "s back for $damage!\n");
		}
		else {

			$fightOutput .= " It dies! Check the body for loot!\n";

			// Move to the looting state.
			$charData->state = GameStates::Looting;

			$changedState = true;
		}

		$fightOutput .= "\n";

		echo $fightOutput;

		return $changedState;
	}
}

$combat = new Combat();

$combat->commands[] = new InputFragment(array("", "status"), function($charData, $mapData) {

	$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
	$monster 	= $room->occupant;

	$status = "Level $monster->level $monster->name ($monster->hp/$monster->hpMax)    You ($charData->hp/$charData->hpMax)\n";

	echo $status;
});

$combat->commands[] = new InputFragment(array("attack", "a"), function($charData, $mapData) use($combat) {

	$room = $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);

	// Must have an occupant to be in combat.
	$monster = $room->occupant;
	
	$combat->playerAttack($charData, $room, $monster);
});

$combat->commands[] = new InputFragment(array("spell", "s"), function($charData, $mapData) {

	// Barbarian check time!
	if ( empty($charData->spellbook) ) {
		echo "You don't have any spells to cast!\n";
		return;
	}

	$spellList = $charData->spellbook;

	// Check we have enough mana to cast one of them.
	$canCast = false;
	$mp = $charData->mp;
	foreach ($spellList as $spellName) {

		$spell = findSpell($spellName);

		if ( $spell->mpCost <= $mp ) {
			$canCast = true;
			break;
		}
	}

	if ( !$canCast ) {
		echo "You don't have enough MP to cast any spells!\n";
		return;
	}

	$output = "Choose a spell: ";

	$spellNum = 1;
	foreach ($spellList as $spell) {

		$output .= " $spell ($spellNum) ";

		++$spellNum;
	}

	$output = rtrim($output) . "\n";
	echo $output;

	$charData->state = GameStates::Spellcasting;
});

$combat->commands[] = new InputFragment(array("run"), function($charData, $mapData) use($combat) {

	$chanceInSix = rand(1,6);
	// 5+ to escape
	if ( $chanceInSix < 5 ) {

		$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
		$monster 	= $room->occupant;

		list ($attackType, $damage) = $combat->monsterAttack($charData, $monster);

		echo "You get caught and $attackType for $damage damage!\n";
	}
	else {

		echo "You scurry back to the last room!\n";

		$charData->state = GameStates::Adventuring;

		$mapData->playerX = $mapData->lastPlayerX;
		$mapData->playerY = $mapData->lastPlayerY;
	}
});
