<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("class_traits.php");

class Resting {

	public $commands = [];
}

$resting = new Resting();
date_default_timezone_set('UTC');

function getCurrentStats($data, $alsoSet = false, $isPray = false) {

	$restedTimeInS 	= time() - $data->restStart;
	$restedTimeInM 	= floor($restedTimeInS / 60);

	if ( $isPray ) {
		$restedTimeInM *= 2;
	}

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

	global $traitMap;
	$isPray		= $traitMap->ClassHasTrait($charData, TraitName::Pray);

	$restEnd 	= $charData->restEnd;

	$date 		= new DateTime();
	$date->setTimestamp($restEnd);

	$status = "You will wake up at " . $date->format("H:i") . ". To wake up now enter 'wake'. (" . getCurrentStats($charData) . ")\n";		

	if ( $isPray ) {
		$status = "You will stop praying at " . $date->format("H:i") . ". To stop now, enter 'wake'. (" . getCurrentStats($charData, false, true) . ")\n";
	}

	echo $status;
});

$resting->commands[] = new InputFragment("wake", function($charData, $mapData) {

	global $traitMap;
	$isPray		= $traitMap->ClassHasTrait($charData, TraitName::Pray);

	$restString = "";

	if ( !$isPray ) {
		$restString = "You wake up, ready to start the new day. (" . getCurrentStats($charData, true, $isPray) . ")";
	}
	else {
		$restString = "You stand up, feeling thoroughly refreshed. (" . getCurrentStats($charData, true, $isPray) . ")";
	}

	echo $restString . "\n";

	$charData->restStart	= 0;
	$charData->restEnd		= 0;

	StateManager::ChangeState($charData, GameStates::Adventuring);
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($resting->commands);
$allocator->Allocate();
