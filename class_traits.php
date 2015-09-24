<?php

// B - Rage ability, two weapons instead of armour
// C - Heals add weapon val, Pray replaces rest - rest at double speed
// F - Armour +level, Slam ability - stun enemy for n levels
// M - Dodge attacks - 2.5% per level, QP cost down 5 per 5 levels
// R - Crit on 19-20, crit range increases by 1 per 5 levels, Backstab cost down 2 per 5 levels
// W - Spell dmg adds weapon val, attack to restore mana (1 MP / damage)

abstract class TraitName {

	// Barbarian
	const Ability_Rage	= "Rage";				// done
	const DualWield 	= "DualWield";			// done

	// Cleric
	const HealUp		= "HealUp";				// done
	const Pray			= "Pray";				// done

	// Fighter
	const ArmourUp		= "ArmourUp";			// done
	const Ability_Slam	= "Slam";				// 

	// Monk
	const Dodge			= "Dodge";				// done
	const PalmLvlScale	= "PalmLvlScale";		// 

	// Rogue
	const CritChanceUp	= "CritChanceUp";		// done
	const CritLvlScale	= "CritLvlScale";		// done
	const StabLvlScale	= "StabLvlScale";		// 

	// Wizard
	const MagicUp		= "MagicUp";			// done
	const AttackForMana	= "AttackForMana";		// done
}

class ClassTrait {

	public 	$name;
	private $scaleCallback;

	public function GetScaledValue(&$charData) {

		return call_user_func($this->scaleCallback, $charData);
	}

	function __construct($name, $scaleCallback) {

		$this->name = $name;
		$this->scaleCallback = $scaleCallback;
	}
}

class TraitMap {

	public function GetTrait($charData, $traitName) {

		$outTrait	= null;

		$class 		= $charData->class;
		$mapEntry 	= $this->map[$class];

		foreach ( $mapEntry as $trait ) {

			if ( strcasecmp($trait->name, $traitName) == 0 ) {

				$outTrait = $trait;
				break;
			}
		}

		return $outTrait;
	}

	public function ClassHasTrait($charData, $traitName) {

		$trait = $this->GetTrait($charData, $traitName);

		return !is_null($trait);
	}

	public $map;
};

$traitMap = new TraitMap();
$traitMap->map = [];

//-----------------------------------------------
// Barbarian.
$barbTraits = array();

$barbTraits[] = new ClassTrait(TraitName::Ability_Rage, function(&$charData) {
	// Do nothing.
});
$barbTraits[] = new ClassTrait(TraitName::DualWield, function(&$charData) {
	// Do nothing.
});

$traitMap->map[Barbarian::Name] = $barbTraits;

//-----------------------------------------------
// Cleric.
$clericTraits = array();

$clericTraits[] = new ClassTrait(TraitName::HealUp, function(&$charData) {
	// Do nothing.
});
$clericTraits[] = new ClassTrait(TraitName::Pray, function(&$charData) {
	// Do nothing.
});

$traitMap->map[Cleric::Name] = $clericTraits;

//-----------------------------------------------
// Fighter.
$fighterTraits = array();

$fighterTraits[] = new ClassTrait(TraitName::ArmourUp, function(&$charData) {
	// Do nothing.
});
$fighterTraits[] = new ClassTrait(TraitName::Ability_Slam, function(&$charData) {
	// Do nothing.
});

$traitMap->map[Fighter::Name] = $fighterTraits;

//-----------------------------------------------
// Monk.
$monkTraits = array();

$monkTraits[] = new ClassTrait(TraitName::Dodge, function(&$charData) {
	
	// 2.5% per level
	$perLevel 		= 2.5;
	$dodgeAmount 	= $charData->level * $perLevel;

	return $dodgeAmount;
});
$monkTraits[] = new ClassTrait(TraitName::PalmLvlScale, function(&$charData) {
	
	// -5MP per 5 levels
	$perLevel		= -5;
	$levelsBy5		= floor($charData->level / 5);
	$mpReduction	= $levelsBy5 * $perLevel;

	return $mpReduction;
});

$traitMap->map[Monk::Name] = $monkTraits;

//-----------------------------------------------
// Rogue.
$rogueTraits = array();

$rogueTraits[] = new ClassTrait(TraitName::CritChanceUp, function(&$charData) {
	// Do nothing.
});
$rogueTraits[] = new ClassTrait(TraitName::CritLvlScale, function(&$charData) {
	
	// Crit threat + 1 / 5 levels
	$perLevel		= 1;
	$levelsBy5		= floor($charData->level / 5);
	// Starts at 19-20
	$critThreat		= 1 + ( $levelsBy5 * $perLevel );

	return $critThreat;
});

$traitMap->map[Rogue::Name] = $rogueTraits;

//-----------------------------------------------
// Wizard.
$wizardTraits = array();

$wizardTraits[] = new ClassTrait(TraitName::MagicUp, function(&$charData) {
	// Do nothing.
});
$wizardTraits[] = new ClassTrait(TraitName::AttackForMana, function(&$charData) {
	// Do nothing.
});

$traitMap->map[Wizard::Name] = $wizardTraits;
