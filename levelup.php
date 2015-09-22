<?php

include_once("statics.php");
include_once("class_definitions.php");

class LevelUp {

	public $commands = [];
}

$levelUp = new LevelUp();

$PER_LEVEL_INCREASE = 10;

$levelUp->commands[] = new InputFragment("hp", function($charData, $mapData) {

	$charData->hp 		+= 10;
	$charData->hpMax 	+= 10;

	echo "HP increases by 10 to $charData->hp/$charData->hpMax. You go back to Adventuring.\n";

	$charData->state = GameStates::Adventuring;
});

$levelUp->commands[] = new InputFragment("mp", function($charData, $mapData) {

	$charData->mp 		+= 10;
	$charData->mpMax 	+= 10;

	echo "MP increases by 10! to $charData->mp/$charData->mpMax. You go back to Adventuring.\n";

	$charData->state = GameStates::Adventuring;
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($levelUp->commands);
$allocator->Allocate();
