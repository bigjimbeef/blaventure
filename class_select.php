<?php

include_once("statics.php");
include_once("class_definitions.php");

class ClassSelect {

	public 	$classes = [];
}

// This will be accessed via "global" in the main file. Because lol.
$classSelect = new ClassSelect();

$classSelect->classes[] = new InputFragment(array("1", "barbarian"), function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Barbarian::Name;
	$charData->hpMax = Barbarian::HP;
	$charData->mpMax = Barbarian::MP;
});
$classSelect->classes[] = new InputFragment(array("2", "fighter"), function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Fighter::Name;
	$charData->hpMax = Fighter::HP;
	$charData->mpMax = Fighter::MP;
});
$classSelect->classes[] = new InputFragment(array("3", "monk"), function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Monk::Name;
	$charData->hpMax = Monk::HP;
	$charData->mpMax = Monk::MP;
});
$classSelect->classes[] = new InputFragment(array("4", "ranger"), function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Ranger::Name;
	$charData->hpMax = Ranger::HP;
	$charData->mpMax = Ranger::MP;
});
$classSelect->classes[] = new InputFragment(array("5", "rogue"), function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Rogue::Name;
	$charData->hpMax = Rogue::HP;
	$charData->mpMax = Rogue::MP;
});
$classSelect->classes[] = new InputFragment(array("6", "wizard"), function($charData, $mapData) {

	if ( !property_exists($charData, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$charData->class = Wizard::Name;
	$charData->hpMax = Wizard::HP;
	$charData->mpMax = Wizard::MP;
});
