<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("spell_list.php");
include_once("combat.php");

class Spellcasting {

	public $commands = [];

	private function castSpell($spellName, $charData, $mapData) {

		$spell = findSpell($spellName);

		if ( $charData->mp < $spell->mpCost ) {
			echo "You don't have enough MP to cast $spellName! ($charData->mp of $spell->mpCost needed)\n";
			return;
		}

		$spellDmg = $spell->Cast($charData);

		// Damage spells.
		if ( !$spell->isHeal ) {

			global $combat;

			$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
			$monster 	= $room->occupant;

			$spellText = "You cast $spellName on the $monster->name for $spellDmg damage!";
			$killedEnemy = $combat->playerAttack($charData, $room, $monster, $spellDmg, $spellText);

			if ( !$killedEnemy ) {

				$charData->state = GameStates::Combat;
			}
		}
		// Healing spells.
		else {

			if ( $charData->hp == $charData->hpMax ) {
				echo "You're already at full health!\n";
				return;
			}

			$beforeHP = $charData->hp;

			$charData->hp += $spellDmg;
			$charData->hp = min($charData->hp, $charData->hpMax);

			$totalHeal = $charData->hp - $beforeHP;
			$fightOutput =  "You heal yourself for $totalHeal! Now at $charData->hp/$charData->hpMax.";

			// Combat step.
			global $combat;

			$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
			$monster 	= $room->occupant;

			list ($attackType, $damage) = $combat->monsterAttack($charData, $monster);

			$fightOutput .= (" It $attackType" . "s back for $damage!\n");

			echo $fightOutput;

			$charData->state = GameStates::Combat;
		}

		$charData->mp -= $spell->mpCost;
	}

	public function generateInputFragments($charData) {

		$spellList = $charData->spellbook;

		$spellNum = 1;
		foreach( $spellList as $spell ) {

			$this->commands[] = new InputFragment(array($spell, strval($spellNum)), function($charData, $mapData) use ($spell) {
				
				$this->castSpell($spell, $charData, $mapData);
			});

			++$spellNum;
		}
	}
}

$spellcasting = new Spellcasting();

$spellcasting->commands[] = new InputFragment(array("list"), function($charData, $mapData) {
	
	$spellList = $charData->spellbook;

	$output = "";

	$spellNum = 1;
	foreach( $spellList as $spellName ) {

		$spell = findSpell($spellName);
		if ( is_null($spell) ) {
			continue;
		}

		$output .= "$spellNum: $spellName ($spell->mpCost/$charData->mp MP)  ";

		$spellNum++;
	}

	$output = rtrim($output) . "\n";

	echo $output;
});
