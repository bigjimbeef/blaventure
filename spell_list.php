<?php

include_once("class_definitions.php");
include_once("combat.php");

function getSpellDamage($level, $minBase, $maxBase, $levelDecrease = 0) {

	$targetLvl	= $levelDecrease > 0 ? max($level - $levelDecrease, 1) : $level;

	$minDmg		= $targetLvl * $minBase;
	$maxDmg 	= $targetLvl * $maxBase;

	$damage 	= rand($minDmg, $maxDmg);

	return $damage;
}

$allSpells = array();

function findSpell($spellName) {

	global $allSpells;

	$outSpell = null;

	foreach ( $allSpells as $spell ) {

		if ( strcasecmp($spell->name, $spellName) == 0 ) {

			$outSpell = $spell;
			break;
		}
	}

	return $outSpell;
}

//-----------------------------------------------
// Default spells.
//-----------------------------------------------

$powerAttack = new Spell("Power Attack", 10, false, function($charData) {

	global $combat;

	// Either does damage * 1.5, or misses 1 in 3.
	list($damage, $crit) = $combat->attackDamage($charData->level, $charData->weaponVal);

	$damage = ceil($damage * 1.5);
	$oneInThree = rand(1, 3);

	if ( $oneInThree == 3 ) {
		$damage = 0;
	}

	return $damage;
});
$allSpells[] = $powerAttack;

$quiveringPalm = new Spell("Quivering Palm", 50, false, function($charData) {

	// Always does 9999 damage. 1 in 4 chance to hit.
	$oneInFour = rand(1, 4);
	
	$damage = 0;
	if ( $oneInFour == 4 ) {
		$damage = getSpellDamage($charData->level, 9999, 9999);
	}

	return $damage;
});
$allSpells[] = $quiveringPalm;

$backstab = new Spell("Backstab", 10, false, function($charData) {

	global $combat;

	// Does weapon damage with a 1 in 3 chance for double damage.
	$oneInThree = rand(1, 3);
	
	list($damage, $crit) = $combat->attackDamage($charData->level, $charData->weaponVal);
	if ( $oneInThree == 3 ) {
		$damage *= 2;
	}

	return $damage;
});
$allSpells[] = $backstab;

$fireblast = new Spell("Fireblast", 5, false, function($charData) {

	// 60-90 at level 30
	// 12-18 dmg/MP
	return getSpellDamage($charData->level, 2, 3);
});
$allSpells[] = $fireblast;

$fireball = new Spell("Fireball", 30, false, function($charData) {

	// 150-300 at level 30
	// 5-10 dmg/MP
	return getSpellDamage($charData->level, 5, 10);
});
$allSpells[] = $fireball;

//-----------------------------------------------------------------------------
// DROPPABLE SPELLS

$spellDrops = array();

//-----------------------------------------------
// Damaging.
//-----------------------------------------------

$rayOfFrost = new Spell("Ray of Frost", 10, false, function($charData) {

	// 60-120 at level 30
	// 6-12 dmg/MP
	return getSpellDamage($charData->level, 2, 4);
});
$allSpells[] = $rayOfFrost;
$spellDrops[] = $rayOfFrost->name;

$lightningBolt = new Spell("Lightning Bolt", 50, false, function($charData) {

	// 30-450 at level 30
	// 0.6-9 dmg/MP
	return getSpellDamage($charData->level, 1, 15);
});
$allSpells[] = $lightningBolt;
$spellDrops[] = $lightningBolt->name;

$deathBlow = new Spell("Deathblow", 20, false, function($charData) {

	// 300-450 at level 30
	// 15-22.5 dmg/MP (if it hits)
	$oneInTwo = rand(1, 2);
	
	$damage = 0;
	if ( $oneInTwo == 2 ) {
		$damage = getSpellDamage($charData->level, 10, 15);
	}

	return $damage;
});
$allSpells[] = $deathBlow;
$spellDrops[] = $deathBlow->name;

$fireBomb = new Spell("Fire Bomb", 60, false, function($charData) {

	// 220-308 at level 30.
	// 3.7-5.1 dmg/MP
	return getSpellDamage($charData->level, 10, 14, 8);
});
$allSpells[] = $fireBomb;
$spellDrops[] = $fireBomb->name;

$lightningStorm = new Spell("Lightning Storm", 80, false, function($charData) {

	// 60-660 at level 30
	// 0.75-8.25 dmg/MP
	return getSpellDamage($charData->level, 2, 22);
});
$allSpells[] = $lightningStorm;
$spellDrops[] = $lightningStorm->name;

$disintegrate = new Spell("Disintegrate", 100, false, function($charData) {

	// 360-720 at level 30.
	// 3.6-7.2 dmg/MP
	return getSpellDamage($charData->level, 12, 24);
});
$allSpells[] = $disintegrate;
$spellDrops[] = $disintegrate->name;

$thorsLightning = new Spell("Thor's Lightning", 150, false, function($charData) {

	// 120-1260 at level 30.
	// 0.8-8.4 dmg/MP
	return getSpellDamage($charData->level, 4, 42);
});
$allSpells[] = $thorsLightning;
$spellDrops[] = $thorsLightning->name;

//-----------------------------------------------
// Healing.
//-----------------------------------------------

$lesserHeal = new Spell("Lesser Heal", 30, true, function($charData) {

	// 90-150 at level 30
	// 3-5 heal/MP
	return getSpellDamage($charData->level, 3, 5);
});
$allSpells[] = $lesserHeal;
$spellDrops[] = $lesserHeal->name;

$heal = new Spell("Heal", 50, true, function($charData) {

	// 120-240 at level 30
	// 2.4-4.8 heal/MP
	return getSpellDamage($charData->level, 4, 8);
});
$allSpells[] = $heal;
$spellDrops[] = $heal->name;

$greaterHeal = new Spell("Greater Heal", 75, true, function($charData) {

	// 150-300 at level 30
	// 2-4 heal/MP
	return getSpellDamage($charData->level, 5, 10);
});
$allSpells[] = $greaterHeal;
$spellDrops[] = $greaterHeal->name;

$fullHeal = new Spell("Full Heal", 120, true, function($charData) {

	// Always heals 9999.
	return getSpellDamage($charData->level, 9999, 9999);
});
$allSpells[] = $fullHeal;
$spellDrops[] = $fullHeal->name;
