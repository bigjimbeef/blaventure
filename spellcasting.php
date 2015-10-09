<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("spell_list.php");
include_once("ability_list.php");
include_once("combat.php");

include_once("class_traits.php");

class Spellcasting {

	public $commands = [];

	private function reduceMPIfNeeded(&$spell, $charData) {

		global $traitMap;

		// Fighter trait.
		global $powerAttack;
		if ( $traitMap->ClassHasTrait($charData, TraitName::PwAtkLvlScale) ) {

			$trait 			= $traitMap->GetTrait($charData, TraitName::PwAtkLvlScale);
			$mpReduction 	= $trait->GetScaledValue($charData);

			if ( strcasecmp($spell->name, $powerAttack->name) == 0 ){

				$spell->mpCost = max($spell->mpCost - $mpReduction, 0);
			}
		}

		// Monk trait.
		global $quiveringPalm;
		if ( $traitMap->ClassHasTrait($charData, TraitName::PalmLvlScale) ) {

			$trait 			= $traitMap->GetTrait($charData, TraitName::PalmLvlScale);
			$mpReduction 	= $trait->GetScaledValue($charData);

			if ( strcasecmp($spell->name, $quiveringPalm->name) == 0 ){

				$spell->mpCost = max($spell->mpCost - $mpReduction, 0);
			}
		}

		// Rogue trait.
		global $backstab;
		if ( $traitMap->ClassHasTrait($charData, TraitName::StabLvlScale) ) {

			$trait 			= $traitMap->GetTrait($charData, TraitName::StabLvlScale);
			$mpReduction 	= $trait->GetScaledValue($charData);

			if ( strcasecmp($spell->name, $backstab->name) == 0 ){

				$spell->mpCost = max($spell->mpCost - $mpReduction, 0);
			}
		}

		return $spell;
	}

	public function findSpellOrAbility($spellName, $charData) {

		$spell = findSpell($spellName, $charData);
		if ( is_null($spell) ) {

			$spell = findAbility($spellName, $charData);
		}

		if ( !is_null($spell) ) {

			$this->reduceMPIfNeeded($spell, $charData);
		}

		return $spell;
	}

	public function canCastSpell($charData, $nonCombatOnly = false) {
		
		// Check we have enough mana to cast one of them.
		$canCast = false;
		$mp = $charData->mp;

		foreach ($charData->spellbook as $spellName) {

			$spell = $this->findSpellOrAbility($spellName, $charData);

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

	private function castSpell($spellName, $charData, $mapData, $dynData, $outOfCombat) {

		$spell = $this->findSpellOrAbility($spellName, $charData);

		if ( $charData->mp < $spell->mpCost ) {
			echo "You don't have enough MP to cast $spellName! ($charData->mp of $spell->mpCost needed)\n";
			return;
		}

		$spellDmg = $spell->Cast($charData);

		// Abilities.
		if ( $spell->isAbility ) {
			
			if ( $outOfCombat ) {
				StateManager::ChangeState($charData, GameStates::Adventuring);
			}
			else {
				StateManager::ChangeState($charData, GameStates::Combat);
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
			$connedName	= $monster->getConnedNameStr($charData->level);

			$spellText = "You cast $spellName on the $connedName for $spellDmg damage!";
			$spellMiss = "You try to cast $spellName on the $connedName, but it fizzles out!";
			$killedEnemy = $combat->playerAttack($charData, $mapData, $dynData, $room, $monster, $spellDmg, $spellText, $spellMiss);

			if ( !$killedEnemy ) {

				StateManager::ChangeState($charData, GameStates::Combat);
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

				list ($attackType, $damage) = $combat->monsterAttack($charData, $dynData, $monster);

				if ( $damage > 0 ) {
					$fightOutput .= (" It $attackType" . "s back for $damage!\n");					
				}
				else {
					$fightOutput .= " It swings at you, but misses!\n";
				}

				echo $fightOutput;

				StateManager::ChangeState($charData, GameStates::Combat);
			}
			else {
				echo "$fightOutput\n";

				StateManager::ChangeState($charData, GameStates::Adventuring);
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

				$spell = $this->findSpellOrAbility($spellName, $charData);

				if ( !$spell->isHeal ) {
					continue;
				}
			}

			$this->commands[] = new InputFragment($spellName, function($charData, $mapData, $dynData) use ($spellName, $outOfCombat) {
				
				$this->castSpell($spellName, $charData, $mapData, $dynData, $outOfCombat);
			});

			++$spellNum;
		}

		$this->commands[] = new InputFragment("cancel", function($charData, $mapData) use ($outOfCombat) {
		
			if ( !$outOfCombat ) {
				echo "You decide against casting a spell, and go back to the fight.\n";

				StateManager::ChangeState($charData, GameStates::Combat);
			}
			else {
				echo "You decide not to cast a spell, and go back to Adventuring.\n";

				StateManager::ChangeState($charData, GameStates::Adventuring);
			}
		});

		// Add unique identifiers to commands.
		$allocator = new UIDAllocator($this->commands);
		$allocator->Allocate();
	}
}

$spellcasting = new Spellcasting();

$spellcasting->commands[] = new InputFragment("book", function($charData, $mapData) {
	
	global $spellcasting;

	$spellList = $charData->spellbook;

	$output = "";

	foreach( $spellList as $spellName ) {

		$spell = $spellcasting->findSpellOrAbility($spellName, $charData);

		if ( is_null($spell) ) {
			continue;
		}

		$output .= "$spellName ($spell->mpCost MP), ";
	}

	$output = rtrim($output, ", ") . "\n";

	echo $output;
});
