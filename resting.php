<?php

include_once("statics.php");
include_once("class_definitions.php");

class Resting {

	public $commands = [];
}

$resting = new Resting();
date_default_timezone_set('UTC');

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
$resting->commands[] = new InputFragment("check", function($charData, $mapData) {

	$restEnd 	= $charData->restEnd;

	$date 		= new DateTime();
	$date->setTimestamp($restEnd);

	$status 	= "You will wake up at " . $date->format("H:i") . ". To wake up now enter 'wake'.    (" . getCurrentStats($charData) . ")\n";

	echo $status;
});

$resting->commands[] = new InputFragment("wake", function($charData, $mapData) {

	echo "You wake up, ready to start the new day.    (" . getCurrentStats($charData, true) . ")\n";

	$charData->restStart	= 0;
	$charData->restEnd		= 0;

	$charData->state		= GameStates::Adventuring;
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($resting->commands);
$allocator->Allocate();
