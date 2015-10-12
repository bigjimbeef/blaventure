<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("default_spells.php");

class ClassSelect {

	public 	$commands = [];
}

// This will be accessed via "global" in the main file. Because lol.
$classSelect = new ClassSelect();

function getHPByClass($className) {

	global $personaList;

	$persona = $personaList->getPersona($className);
	$hp = $persona->endurance * PersonaMultiplier::Endurance;

	return $hp;
}
function getMPByClass($className) {

	global $personaList;

	$persona = $personaList->getPersona($className);
	$mp = $persona->oddness * PersonaMultiplier::Oddness;

	return $mp;
}

$classSelect->commands[] = new InputFragment("barbarian", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Barbarian::Name;
	$charData->hpMax = getHPByClass(Barbarian::Name);
	$charData->mpMax = getMPByClass(Barbarian::Name);

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Barbarian::Name];
});
$classSelect->commands[] = new InputFragment("cleric", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Cleric::Name;
	$charData->hpMax = getHPByClass(Cleric::Name);
	$charData->mpMax = getMPByClass(Cleric::Name);

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Cleric::Name];
});
$classSelect->commands[] = new InputFragment("fighter", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Fighter::Name;
	$charData->hpMax = getHPByClass(Fighter::Name);
	$charData->mpMax = getMPByClass(Fighter::Name);

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Fighter::Name];
});
$classSelect->commands[] = new InputFragment("monk", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Monk::Name;
	$charData->hpMax = getHPByClass(Monk::Name);
	$charData->mpMax = getMPByClass(Monk::Name);

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Monk::Name];
});
$classSelect->commands[] = new InputFragment("rogue", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Rogue::Name;
	$charData->hpMax = getHPByClass(Rogue::Name);
	$charData->mpMax = getMPByClass(Rogue::Name);

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Rogue::Name];
});
$classSelect->commands[] = new InputFragment("wizard", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Wizard::Name;
	$charData->hpMax = getHPByClass(Wizard::Name);
	$charData->mpMax = getMPByClass(Wizard::Name);

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Wizard::Name];
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($classSelect->commands);
$allocator->Allocate();
