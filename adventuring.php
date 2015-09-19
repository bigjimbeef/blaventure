<?php

include_once("statics.php");
include_once("class_definitions.php");

class Adventuring {

	public $commands = [];
}

$adventuring = new Adventuring();

// Get the character status
// e.g. Level 3 Barbarian    HP 3/10    MP 2/5
$adventuring->commands[] = new InputFragment(array("status"), function($data) {

	$status = "Level $data->level $data->class    HP $data->hp/$data->hpMax    MP $data->mp/$data->mpMax\n";

	echo $status;
});

// Get the character inventory
// e.g. Level 3 Barbarian    HP 3/10    MP 2/5
$adventuring->commands[] = new InputFragment(array("inventory", "items"), function($data) {

	$inventory = "$data->weapon ($data->weaponVal)    $data->armour ($data->armourVal)    $data->gold GP\n";

	echo $inventory;
});

// Begin resting. 
// 
// Resting is tied into real time. It takes 1 real minute to regen one HP and MP.
$adventuring->commands[] = new InputFragment(array("rest", "sleep"), function($data) {

	$hpDeficit		= $data->hpMax - $data->hp;
	$mpDeficit		= $data->mpMax - $data->mp;

	// Can't rest at max HP and MP.
	if ( $hpDeficit == 0 && $mpDeficit == 0 ) {

		echo "You're not really tired. Better find something else to do.\n";
	}
	else {

		$restDuration 		= $hpDeficit > $mpDeficit ? $hpDeficit : $mpDeficit;

		echo "You curl up in a ball and go to sleep. It will take $restDuration minutes to fully restore.\n";
		$data->state 		= GameStates::Resting;

		$data->restStart 	= time();

		$toMinutes			= 60;
		$data->restEnd		= time() + ( $restDuration * $toMinutes );
	}
});

