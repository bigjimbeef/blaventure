<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("spell_list.php");
include_once("ability_list.php");
include_once("combat.php");

include_once("class_traits.php");

class Spellcasting {

	public $commands = [];

	public function findSpellOrAbility($spellName) {

		$spell = findSpell($spellName);
		if ( is_null($spell) ) {

			$spell = findAbility($spellName);
		}

		return $spell;
	}

	public function canCastSpell($charData, $nonCombatOnly = false) {
		
		// Check we have enough mana to cast one of them.
		$canCast = false;
		$mp = $charData->mp;

		foreach ($charData->spellbook as $spellName) {

			$spell = $this->findSpellOrAbility($spellName);

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

		$spell = $this->findSpellOrAbility($spellName);

		if ( $charData->mp < $spell->mpCost ) {
			echo "You don't have enough MP to cast $spellName! ($charData->mp of $spell->mpCost needed)\n";
			return;
		}

		$spellDmg = $spell->Cast($charData);

		// Abilities.
		if ( $spell->isAbility ) {
			
			if ( $outOfCombat ) {
				$charData->state = GameStates::Adventuring;
			}
			else {
				$charData->state = GameStates::Combat;
			}
		}
		// Damage spells.
		else if ( !$spell->isHeal ) {

			//---------------------------------------
			// Wizard trait: increase damage of spell by weapon attack value
			//
			global $traitMap;
			if ( $traitMap->ClassHasTrait($charData, TraitName::MagicUp) ) {

				$spellDmg += floor($charData->weaponVal / 2);
			}
			//---------------------------------------

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

			//---------------------------------------
			// Cleric trait: increase healing of spell by weapon attack value
			//
			global $traitMap;
			if ( $traitMap->ClassHasTrait($charData, TraitName::HealUp) ) {

				$spellDmg += $charData->weaponVal;
			}
			//---------------------------------------

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

				$spell = $this->findSpellOrAbility($spellName);

				if ( !$spell->isHeal ) {
					continue;
				}
			}

			$this->commands[] = new InputFragment($spellName, function($charData, $mapData) use ($spellName, $outOfCombat) {
				
				$this->castSpell($spellName, $charData, $mapData, $outOfCombat);
			});

			++$spellNum;
		}

		$this->commands[] = new InputFragment("cancel", function($charData, $mapData) use ($outOfCombat) {
		
			if ( !$outOfCombat ) {
				echo "You decide against casting a spell, and go back to the fight.\n";

				$charData->state = GameStates::Combat;
			}
			else {
				echo "You decide not to cast a spell, and go back to Adventuring.\n";

				$charData->state = GameStates::Adventuring;
			}
		});

		// Add unique identifiers to commands.
		$allocator = new UIDAllocator($this->commands);
		$allocator->Allocate();
	}
}

$spellcasting = new Spellcasting();

$spellcasting->commands[] = new InputFragment("book", function($charData, $mapData) {
	
	$spellList = $charData->spellbook;

	$output = "";

	foreach( $spellList as $spellName ) {

		$spell = $this->findSpellOrAbility($spellName);

		if ( is_null($spell) ) {
			continue;
		}

		$output .= "$spellName ($spell->mpCost/$charData->mp MP)  ";
	}

	$output = rtrim($output) . "\n";

	echo $output;
});
