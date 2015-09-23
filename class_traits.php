<?php

// B - Rage ability, two weapons instead of armour
// C - Heals * 1.5, Pray replaces rest - rest at double speed
// F - Armour * 1.5, Slam ability - stun enemy for n levels
// M - Dodge attacks - 2.5% per level, QP cost down 5 per 5 levels
// R - Crit on 19-20, 1.5 x crit damage, crit range increases by 1 per 5 levels
// W - Spell dmg x 1.5, attack to restore mana (1 MP / damage)

abstract class TraitNames {

	// Barbarian
	const Ability_Rage	= "Rage";
	const DoubleWeapon 	= "DoubleWeapon";

	// Cleric
	const HealUp		= "HealUp";
	const Pray			= "Pray";

	// Fighter
	const ArmourUp		= "ArmourUp";
	const Ability_Slam	= "Slam";

	// Monk
	const Dodge			= "Dodge";
	const PalmLvlScale	= "PalmLvlScale";

	// Rogue
	const CritChanceUp	= "CritChanceUp";
	const CritDmgUp		= "CritDmgUp";
	const CritLvlScale	= "CritLvlScale";

	// Wizard
	const MagicUp		= "MagicUp";
	const AttackForMana	= "AttackForMana";
};

class Trait {

	public 	$name;
	private $scaleCallback;

	function __construct($name, $scaleCallback) {

		$this->name = $name;
		$this->scaleCallback = $scaleCallback;
	}
};

class TraitMap {

	public function ClassHasTrait($class, $traitName) {


	}

	public $map;
};

$traitMap = new TraitMap();
$traitMap->map = [];

//-----------------------------------------------
// Barbarian.
$barbTraits = array();

$barbTraits[] = new Trait(TraitNames::Rage, function(&$charData) {
	// Do nothing.
});
$barbTraits[] = new Trait(TraitNames::DoubleWeapon, function(&$charData) {
	// Do nothing.
});

$traitMap[Barbarian::Name] = $barbTraits;

//-----------------------------------------------
// Cleric.
$clericTraits = array();

$clericTraits[] = new Trait(TraitNames::HealUp, function(&$charData) {
	// Do nothing.
});
$clericTraits[] = new Trait(TraitNames::Pray, function(&$charData) {
	// Do nothing.
});

$traitMap[Cleric::Name] = $clericTraits;
