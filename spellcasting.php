<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("spell_list.php");
include_once("combat.php");

class Spellcasting {

	public $commands = [];

	public function getOverflowSpellNum($spellNum) {
		$spellNumText = strval($spellNum);

		if ( $spellNum == 10 ) {
			$spellNumText = "0";
		}
		else if ( $spellNum > 10 ) {
			$overflow = array("q","w","e","r","t","y","u","i","o","p");

			// 11 should be q
			$overflowVal = $spellNum - 11;
			$spellNumText = $overflow[$overflowVal];
		}

		return $spellNumText;
	}

	public function canCastSpell($charData, $nonCombatOnly = false) {
		
		// Check we have enough mana to cast one of them.
		$canCast = false;
		$mp = $charData->mp;

		foreach ($charData->spellbook as $spellName) {

			$spell = findSpell($spellName);

			// Ignore damaging spells if searching for heals.
			if ( $nonCombatOnly && !$spell->isHeal ) {
				continue;
			}

			if ( $spell->mpCost <= $mp ) {
				$canCast = true;
				break;
			}
		}

		return $canCast;
	}

	private function castSpell($spellName, $charData, $mapData, $outOfCombat) {

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
			if ( !$outOfCombat ) {
				global $combat;

				$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
				$monster 	= $room->occupant;

				list ($attackType, $damage) = $combat->monsterAttack($charData, $monster);

				$fightOutput .= (" It $attackType" . "s back for $damage!\n");

				echo $fightOutput;

				$charData->state = GameStates::Combat;
			}
			else {
				echo "$fightOutput\n";

				$charData->state = GameStates::Adventuring;
			}
		}

		$charData->mp -= $spell->mpCost;
	}

	public function generateInputFragments($charData, $outOfCombat = false) {

		$spellList = $charData->spellbook;

		$spellNum = 1;
		foreach( $spellList as $spellName ) {

			// Can only cast heal spells.
			if ( $outOfCombat ) {

				$spell = findSpell($spellName);

				if ( !$spell->isHeal ) {
					continue;
				}
			}

			$spellNumText = $this->getOverflowSpellNum($spellNum);

			$this->commands[] = new InputFragment(array($spellName, $spellNumText), function($charData, $mapData) use ($spellName, $outOfCombat) {
				
				$this->castSpell($spellName, $charData, $mapData, $outOfCombat);
			});

			++$spellNum;
		}

		$this->commands[] = new InputFragment(array("cancel"), function($charData, $mapData) use ($outOfCombat) {
		
			if ( !$outOfCombat ) {
				$charData->state = GameStates::Combat;
			}
			else {
				$charData->state = GameStates::Adventuring;
			}
		});
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
