<?php 

include_once("statics.php");
include_once("class_definitions.php");

include_once("spellcasting.php");
include_once("class_traits.php");

include_once("ability_list.php");

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

	public function appendScoreboardInfo(&$charData) {

		$home 		= getenv("HOME");
		$filePath 	= "$home/.blaventure/$charData->nick.scoreboard";

		// Delete the current save game.
		$charFilePath 	= "$home/.blaventure/$charData->nick.char";
		$mapFilePath 	= "$home/.blaventure/$charData->nick.map";

		unlink($charFilePath);
		unlink($mapFilePath);

		$currentTop = null;

		if ( file_exists($filePath) ) {
			$handle		= fopen($filePath, "r");
			$contents	= fread($handle, filesize($filePath));

			$currentTop = unserialize($contents);
			fclose($handle);
		}

		if ( is_null($currentTop) || $charData->level > $currentTop->level ) {

			$handle		= fopen($filePath, "w");
			$winner		= serialize($charData);

			fwrite($handle, $winner);

			fclose($handle);

			return " On the plus side, you set a new personal best!\n";
		}

		// Must be a loser
		return " That wasn't your best.\n";
	}

	public function playerDamaged(&$charData, $damage) {

		$charData->hp -= $damage;

		if ( $charData->hp <= 0 ) {

			$deathMsg = "Oh no! $charData->name has died!";

			$deathMsg .= $this->appendScoreboardInfo($charData);

			echo $deathMsg;

			exit(11);
		}
	}

	// Note: check spreadsheet for reference to these arcane formulae!
	public function attackDamage($level, $attack, $critThreatOverride = -1) { 

		$minDamage = floor(1.5 * ($level + 1)) + $attack;
		$maxDamage = (2 * ($level + 1)) + $attack;

		$damage		= rand($minDamage, $maxDamage);
		$crit		= false;

		$chanceIn20	= rand(1, 20);

		$critNumber = 20;
		if ( $critThreatOverride > 0 ) {
			$critNumber = $critThreatOverride;
		}

		if ( $chanceIn20 >= $critNumber ) {
			$crit = true;

			$critDmgMultiplier = 2;
			$damage *= $critDmgMultiplier;
		}

		return array($damage, $crit);
	}

	public function monsterAttack(&$charData, $monster, &$fightOutput) {

		list($damage, $crit) = $this->attackDamage($monster->level, $monster->attack);		
		$attackType = $crit ? "CRIT" : "hit";

		//---------------------------------------
		// Monk trait: dodge, duck, dip, dive, dodge
		//
		global $traitMap;
		if ( $traitMap->ClassHasTrait($charData, TraitName::Dodge) ) {

			$trait 		= $traitMap->GetTrait($charData, TraitName::Dodge);
			$dodgePerc 	= $trait->GetScaledValue($charData);

			$oneInHundred = rand(1, 100);

			if ( $oneInHundred <= $dodgePerc ) {

				$fightOutput .= " You dodge its return strike!\nn";
				return;
			}
		}
		//---------------------------------------

		// Barbarians don't use armour.
		$armourVal = $charData->armourVal;
		if ( $traitMap->ClassHasTrait($charData, TraitName::DualWield) ) {

			$armourVal = 0;
		}

		// Also they're angry sometimes.
		if ( $charData->rageTurns > 0 ) {
			$damage *= 2;
		}

		$mitigatedDamage = $damage - $armourVal;

		//---------------------------------------
		// Fighter trait: yawn, fighters
		//
		if ( $traitMap->ClassHasTrait($charData, TraitName::ArmourUp) ) {

			$mitigatedDamage -= $charData->level;
			$mitigatedDamage = max(0, $mitigatedDamage);
		}
		//---------------------------------------

		$this->playerDamaged($charData, $mitigatedDamage);

		$fightOutput .= (" It $attackType" . "s back for $mitigatedDamage! ($charData->hp/$charData->hpMax)\n");

		return array($attackType, $damage);
	}

	public function playerAttack(&$charData, &$room, &$monster, $spellDmg = null, $spellText = null) {

		$changedState = false;
		$critThreatOverride = null;

		$isAngryBarbarian = $charData->rageTurns > 0;

		//---------------------------------------
		// Rogue trait.
		global $traitMap;
		if ( $traitMap->ClassHasTrait($charData, TraitName::CritChanceUp) ) {

			$trait = $traitMap->GetTrait($charData, TraitName::CritLvlScale);
			
			$critThreatOverride = 20 - $trait->GetScaledValue($charData);
		}
		//---------------------------------------

		//---------------------------------------
		// Barbarian trait.
		$weaponDamage = $charData->weaponVal;
		if ( $traitMap->ClassHasTrait($charData, TraitName::DualWield) ) {

			$weaponDamage += $charData->weapon2Val;
		}
		//---------------------------------------

		list($damage, $crit) = $this->attackDamage($charData->level, $weaponDamage, $critThreatOverride);

		// Angry barbarian? (don't double spell damage)
		if ( $isAngryBarbarian && is_null($spellDmg) ){
			$damage *= 2;
		}

		// Used when spellcasting.
		if ( !is_null($spellDmg) ) {
			$damage = $spellDmg;
		}

		$attackType = $crit ? "CRIT" : "hit";
		$fightOutput = "You $attackType the $monster->name for $damage!";

		//---------------------------------------
		// Wiz trait.
		global $traitMap;
		if ( $traitMap->ClassHasTrait($charData, TraitName::AttackForMana) ) {

			$currentMP = $charData->mp;
			
			$charData->mp += floor( $damage / 3 );
			$charData->mp = min($charData->mp, $charData->mpMax);

			$restoredMP = $charData->mp - $currentMP;
			
			if ( $restoredMP > 0 ) {
				$fightOutput .= " It restores $restoredMP MP!";				
			}
		}
		//---------------------------------------

		// Used when spellcasting.
		if ( $spellText ) {
			$fightOutput = $spellText;
		}

		if ( $this->monsterDamaged($monster, $room, $damage, $charData) ) {

			// It survived. Attacks back.
			$this->monsterAttack($charData, $monster, $fightOutput);

			// Barbarians get a little less angry
			if ( $isAngryBarbarian ) {

				$charData->rageTurns--;
			}
		}
		else {

			$fightOutput .= " It dies! Check the body for loot!\n";

			$charData->kills++;

			clearAllAbilityLocks($charData);

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

$combat->commands[] = new InputFragment("check", function($charData, $mapData) {

	$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
	$monster 	= $room->occupant;

	$status = "Level $monster->level $monster->name ($monster->hp/$monster->hpMax HP)    You ($charData->hp/$charData->hpMax HP  $charData->mp/$charData->mpMax MP)\n";

	echo $status;
});

$combat->commands[] = new InputFragment("attack", function($charData, $mapData) use($combat) {

	$room = $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);

	// Must have an occupant to be in combat.
	$monster = $room->occupant;
	
	$combat->playerAttack($charData, $room, $monster);
});

$combat->commands[] = new InputFragment("magic", function($charData, $mapData) {

	// Barbarian check time!
	if ( empty($charData->spellbook) ) {
		echo "You don't have any spells to cast!\n";
		return;
	}

	$spellList = $charData->spellbook;

	// Check we have enough mana to cast one of them.
	global $spellcasting;
	$canCast = $spellcasting->canCastSpell($charData);

	if ( !$canCast ) {
		echo "You don't have enough MP to cast any spells!\n";
		return;
	}

	$output = "Choose a spell: ";

	$spellcasting->generateInputFragments($charData);

	foreach ( $spellcasting->commands as $fragment ) {

		$output .= "$fragment->displayString, ";
	}

	$output = rtrim($output, ", ") . "\n";

	echo $output;

	$charData->state = GameStates::Spellcasting;
});

$combat->commands[] = new InputFragment("run", function($charData, $mapData) use($combat) {

	$chanceInSix = rand(1,6);
	// 5+ to escape
	if ( $chanceInSix < 5 ) {

		$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
		$monster 	= $room->occupant;

		list ($attackType, $damage) = $combat->monsterAttack($charData, $monster, $output);
		echo "You get caught and $attackType for $damage damage!\n";
	}
	else {

		echo "You scurry back to the last room!\n";

		$charData->state = GameStates::Adventuring;

		$mapData->playerX = $mapData->lastPlayerX;
		$mapData->playerY = $mapData->lastPlayerY;
	}
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($combat->commands);
$allocator->Allocate();
