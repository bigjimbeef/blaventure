<?php

include_once("statics.php");
include_once("class_definitions.php");

class Resting {

	public $commands = [];
}

$resting = new Resting();

function getCurrentStats($data, $alsoSet = false) {

	$restedTimeInS 	= time() - $data->restStart;
	$restedTimeInM 	= floor($restedTimeInS / 60);

	$restedHP		= $data->hp + $restedTimeInM;
	$restedHP		= min($restedHP, $data->hpMax);

	$restedMP		= $data->mp + $restedTimeInM;
	$restedMP		= min($restedMP, $data->mpMax);

	$status			= "$restedHP/$data->hpMax HP, $restedMP/$data->mpMax MP";

	if ( $alsoSet ) {
		$data->hp = $restedHP;
		$data->mp = $restedMP;
	}

	return $status;
}

// Check the status of the rest
$resting->commands[] = new InputFragment(array(""), function($data) {

	$restEnd 	= $data->restEnd;
	$status 	= "You will wake up at " . date("H:i", $restEnd) . ". To wake up now enter 'wake'.    (" . getCurrentStats($data) . ")\n";

	echo $status;
});

$resting->commands[] = new InputFragment(array("wake"), function($data) {

	echo "You wake up, ready to start the new day.    (" . getCurrentStats($data, true) . ")\n";

	$data->restStart	= 0;
	$data->restEnd		= 0;

	$data->state		= GameStates::Adventuring;
});