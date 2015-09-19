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

	$status = "$data->weapon ($data->weaponVal)    $data->armour ($data->armourVal)    $data->gold GP\n";

	echo $status;
});
