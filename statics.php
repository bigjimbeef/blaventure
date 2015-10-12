<?php

include_once("personas.php");

abstract class SaveFileType {
	const Character	= "char";
	const Map		= "map";
	const Dynasty	= "dynasty";
}

// game state
abstract class GameStates {
	const NameSelect			= 0;
	const ClassSelect			= 1;
	const FirstPlay				= 2;
	const Adventuring			= 3;
	const Resting				= 4;
	const Combat				= 5;
	const Spellcasting			= 6;
	const Looting				= 7;
	const LevelUp				= 8;
	const Dead					= 9;
	const UsingItem				= 10;
	const Shopping				= 11;

	const DynastySplash			= 12;
	const DynastyInit			= 13;
	const Dynasty				= 14;
}

// items
abstract class ItemUse {
	const Either		= 0;
	const CombatOnly	= 1;
	const NonCombatOnly = 2;
}

// classes
abstract class Barbarian {
	const Name		= "Barbarian";
	const HP		= 100;
	const MP		= 0;
}
abstract class Cleric {
	const Name		= "Cleric";
	const HP		= 30;
	const MP		= 70;
}
abstract class Fighter {
	const Name		= "Fighter";
	const HP		= 90;
	const MP		= 10;
}
abstract class Monk {
	const Name		= "Monk";
	const HP		= 50;
	const MP		= 50;
}
abstract class Rogue {
	const Name		= "Rogue";
	const HP		= 70;
	const MP		= 30;
}
abstract class Wizard {
	const Name		= "Wizard";
	const HP		= 10;
	const MP		= 90;
}

abstract class PersonaMultiplier {

	const Precision	= 5;
	const Endurance	= 10;
	const Reflexes	= 2;
	const Strength	= 1;
	const Oddness	= 10;
	const Nerve		= 1;
	const Acuity	= 2;
}

abstract class StatPatcher {

	static function FixUpPERSONA(&$charData) {

		global $personaList;

		$charClass 	= $charData->class;
		$persona	= $personaList->getPersona($charClass);

		// Base stats.
		$charData->precision 	= $persona->precision;
		$charData->reflexes 	= $persona->reflexes;
		$charData->strength 	= $persona->strength;
		$charData->nerve 		= $persona->nerve;
		$charData->acuity 		= $persona->acuity;

		// Previously-altered stats (E, O)
		$charData->endurance 	= $charData->hpMax / 10;
		$charData->oddness 		= $charData->mpMax / 10;
	}
}
