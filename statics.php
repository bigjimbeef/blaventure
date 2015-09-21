<?php

abstract class SaveFileType {
	const Character	= "char";
	const Map		= "map";
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
	const NonCombatSpellcasting	= 9;
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
