<?php

abstract class SaveFileType {
	const Character	= "char";
	const Map		= "map";
}

// game state
abstract class GameStates {
	const NameSelect	= 0;
	const ClassSelect	= 1;
	const FirstPlay		= 2;
	const Adventuring	= 3;
	const Resting		= 4;
	const Combat		= 5;
}

// classes
abstract class Wizard {
	const Name		= "Wizard";
	const HP		= 10;
	const MP		= 90;
}
abstract class Rogue {
	const Name		= "Rogue";
	const HP		= 70;
	const MP		= 30;
}
abstract class Fighter {
	const Name		= "Fighter";
	const HP		= 90;
	const MP		= 10;
}
abstract class Barbarian {
	const Name		= "Barbarian";
	const HP		= 100;
	const MP		= 0;
}
abstract class Ranger {
	const Name		= "Ranger";
	const HP		= 50;
	const MP		= 50;
}
abstract class Monk {
	const Name		= "Monk";
	const HP		= 30;
	const MP		= 70;
}
