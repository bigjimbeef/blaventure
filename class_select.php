<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("default_spells.php");

class ClassSelect {

	public 	$commands = [];
}

// This will be accessed via "global" in the main file. Because lol.
$classSelect = new ClassSelect();

$classSelect->commands[] = new InputFragment("barbarian", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Barbarian::Name;
	$charData->hpMax = Barbarian::HP;
	$charData->mpMax = Barbarian::MP;

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Barbarian::Name];
});
$classSelect->commands[] = new InputFragment("cleric", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Cleric::Name;
	$charData->hpMax = Cleric::HP;
	$charData->mpMax = Cleric::MP;

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Cleric::Name];
});
$classSelect->commands[] = new InputFragment("fighter", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Fighter::Name;
	$charData->hpMax = Fighter::HP;
	$charData->mpMax = Fighter::MP;

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Fighter::Name];
});
$classSelect->commands[] = new InputFragment("monk", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Monk::Name;
	$charData->hpMax = Monk::HP;
	$charData->mpMax = Monk::MP;

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Monk::Name];
});
$classSelect->commands[] = new InputFragment("rogue", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Rogue::Name;
	$charData->hpMax = Rogue::HP;
	$charData->mpMax = Rogue::MP;

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Rogue::Name];
});
$classSelect->commands[] = new InputFragment("wizard", function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Wizard::Name;
	$charData->hpMax = Wizard::HP;
	$charData->mpMax = Wizard::MP;

	global $defaultSpells;
	$charData->spellbook = $defaultSpells[Wizard::Name];
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($classSelect->commands);
$allocator->Allocate();
