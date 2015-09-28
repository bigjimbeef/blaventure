<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("spell_list.php");
include_once("class_traits.php");

class Looting {

	public $commands = [];
}

$looting = new Looting();

function lootLevel($playerLevel) {

	$level = $playerLevel;

	$chanceInSix	= rand(1, 6);

	// 6:		Level+2
	if ( $chanceInSix >= 5 ) {

		$level += 2;
	}
	// 4,5:		Level+1
	else if ( $chanceInSix >= 3 ) {

		$level += 1;
	}
	// 1,2,3:	Level==

	return $level;
}

function giveGold($monster, &$charData) {

	// Gold is very random.
	$gold = $monster->level * rand(1, 10);

	$output = "On the corpse of the $monster->name, you find $gold GP! ";

	$charData->gold += $gold;

	return $output;
}

function giveWeapon($monster, &$charData) {

	//---------------------------------------
	// Barbarian trait: :D
	//
	global $traitMap;
	$isBarbarian = $traitMap->ClassHasTrait($charData, TraitName::DualWield);
	//---------------------------------------

	$textOutput 	= "";
	$monsterLevel	= $monster->level;

	$weaponName = NameGenerator::Weapon($monsterLevel);
	$weaponLvl	= lootLevel($monsterLevel);

	$currentWpnVal = $charData->weaponVal;

	// Only equip weapons that are better.
	if ( $weaponLvl > $currentWpnVal ) {

		$article 	= NameGenerator::GetArticle($weaponName);
		$textOutput = "You find $article $weaponName and equip it immediately";

		if ( $isBarbarian && ( $charData->weaponVal > $charData->weapon2Val ) ) {

			$textOutput .= ", moving your $charData->weapon to your off-hand";

			$charData->weapon2 = $charData->weapon;
			$charData->weapon2Val = $charData->weaponVal;
		}

		$textOutput .= "! ";

		$charData->weapon = $weaponName;
		$charData->weaponVal = $weaponLvl;
	}
	// Check the second weapon slot, for barbarians
	else if ( $isBarbarian && ( $weaponLvl > $charData->weapon2Val ) ) {

		$article 	= NameGenerator::GetArticle($weaponName);
		$textOutput = "You find $article $weaponName and equip it immediately in your off-hand! ";

		$charData->weapon2 		= $weaponName;
		$charData->weapon2Val 	= $weaponLvl;
	}
	else {

		$textOutput = giveGold($monster, $charData);
	}

	return $textOutput;
}

function giveLoot($monster, &$charData) {

	$textOutput		= "";

	$monsterLevel	= $monster->level;
	$chanceInSix	= rand(1, 6);

	// 5,6: Weapon
	if ( $chanceInSix >= 5 ) {

		$textOutput = giveWeapon($monster, $charData);
	}
	// 3,4: Armour
	else if ( $chanceInSix >= 3 ) {

		//---------------------------------------
		// Barbarian trait: :D
		//
		global $traitMap;
		$isBarbarian = $traitMap->ClassHasTrait($charData, TraitName::DualWield);
		//---------------------------------------

		if ( !$isBarbarian ) {
			
			$armourName = NameGenerator::Armour($monsterLevel);
			$armourLvl	= lootLevel($monsterLevel);

			$currentAmrVal = $charData->armourVal;

			// Only equip armour that is better.
			if ( $armourLvl > $currentAmrVal ) {

				$textOutput = "The monster was armoured! You steal the $armourName and equip it immediately! ";

				$charData->armour = $armourName;
				$charData->armourVal = $armourLvl;
			}
			else {

				$textOutput = giveGold($monster, $charData);
			}
		}
		// Barbarians can't wear armour, so they always get weapons.
		else {

			$textOutput = giveWeapon($monster, $charData);
		}
	}
	// 1,2: Spell
	else {

		// Check if we CAN award a spell.
		global $spellDrops;

		$currentSpells = $charData->spellbook;

		$possibleSpells = array();
		foreach ( $spellDrops as $dropSpell ) {

			if ( !in_array($dropSpell, $currentSpells) ) {

				$possibleSpells[] = $dropSpell;
			}
		}

		$canAwardSpell = !empty($possibleSpells);

		if ( $canAwardSpell ) {

			$spellIdx = rand(0, count($possibleSpells) - 1);
			$newSpell = $possibleSpells[$spellIdx];

			$textOutput = "You find a scroll of $newSpell on the body! Lucky you! ";

			$charData->spellbook[] = $newSpell;
		}
		else {

			$textOutput = giveGold($monster, $charData);
		}
	}

	return $textOutput;
}

function checkForLootDrop($monster, &$charData) {

	$isElite		= isset($monster->elite);

	$chanceInSix	= rand(1, 6);

	$output = "";

	// 5,6:	Loot
	if ( $chanceInSix > 4 ) {

		$output = giveLoot($monster, $charData);
	}
	// 3,4:	GP
	else if ( $chanceInSix > 3 ) {

		$output = giveGold($monster, $charData);
	}
	// 1,2: Sweet fuck all
	else {

		$output = "You find nothing of note on the $monster->name. ";
	}

	return $output;
}

// True on level up, false otherwise
function giveXP($monster, &$charData, &$lootText) {

	$isElite	= isset($monster->elite);

	// FLOOR.MATH(POWER(1.8, level))
	$xpAwarded	= floor(pow(1.8, $monster->level));

	// Double XP for elite monsters.
	if ( $isElite ) {
		$xpAwarded *= 2;
	}

	$xpForNextLevel	= pow(2, $charData->level);
	$currentXP		= $charData->xp;

	$currentXP += $xpAwarded;
	
	// Level up!
	if ( $currentXP >= $xpForNextLevel ) {

		$charData->level++;

		$carryOverXP 	= $xpForNextLevel - $currentXP;
		$charData->xp 	= $carryOverXP;

		$levelUpColoured = "\x0312L\x03\x033E\x03\x037V\x03\x035E\x03\x036L\x03 \x039U\x03\x0310P\x03\x038!\x03";

		$lootText .= "$levelUpColoured Choose to increase your (h)p or (m)p:\n";

		return true;
	}
	// Just get some XP, yo.
	else {

		$xpToLevel = $xpForNextLevel - $currentXP;
		$nextLevel = $charData->level + 1;
		$lootText .= "You gained $xpAwarded XP. $xpToLevel until level $nextLevel.\n";

		$charData->xp = $currentXP;
	}

	return false;
}

$looting->commands[] = new InputFragment("loot", function($charData, $mapData) {

	$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
	$monster 	= $room->occupant;
	
	$lootText 	= checkForLootDrop($monster, $charData);	
	$levelledUp	= giveXP($monster, $charData, $lootText);

	echo "$lootText\n";

	if ( $levelledUp ) {
		StateManager::ChangeState($charData, GameStates::LevelUp);
	}
	else {
		StateManager::ChangeState($charData, GameStates::Adventuring);
	}

	unset($room->occupant);
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($looting->commands);
$allocator->Allocate();
