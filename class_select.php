<?php

include_once("statics.php");
include_once("class_definitions.php");

class ClassSelect {

	public 	$classes = [];
}

// This will be accessed via "global" in the main file. Because lol.
$classSelect = new ClassSelect();

$classSelect->classes[] = new InputFragment(array("1", "barbarian"), function($data) {

	if ( !property_exists($data, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$data->class = Barbarian::Name;
	$data->hpMax = Barbarian::HP;
	$data->mpMax = Barbarian::MP;
});
$classSelect->classes[] = new InputFragment(array("2", "fighter"), function($data) {

	if ( !property_exists($data, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$data->class = Fighter::Name;
	$data->hpMax = Fighter::HP;
	$data->mpMax = Fighter::MP;
});
$classSelect->classes[] = new InputFragment(array("3", "monk"), function($data) {

	if ( !property_exists($data, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$data->class = Monk::Name;
	$data->hpMax = Monk::HP;
	$data->mpMax = Monk::MP;
});
$classSelect->classes[] = new InputFragment(array("4", "ranger"), function($data) {

	if ( !property_exists($data, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$data->class = Ranger::Name;
	$data->hpMax = Ranger::HP;
	$data->mpMax = Ranger::MP;
});
$classSelect->classes[] = new InputFragment(array("5", "rogue"), function($data) {

	if ( !property_exists($data, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$data->class = Rogue::Name;
	$data->hpMax = Rogue::HP;
	$data->mpMax = Rogue::MP;
});
$classSelect->classes[] = new InputFragment(array("6", "wizard"), function($data) {

	if ( !property_exists($data, "class") ) {
		echo "ERROR: Cannot set class!\n";
		exit(7);
	}

	$data->class = Wizard::Name;
	$data->hpMax = Wizard::HP;
	$data->mpMax = Wizard::MP;
});
