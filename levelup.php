<?php

include_once("statics.php");
include_once("class_definitions.php");

class LevelUp {

	public $commands = [];
}

$levelUp = new LevelUp();

$PER_LEVEL_INCREASE = 10;

$levelUp->commands[] = new InputFragment(array("HP", "1"), function($charData, $mapData) {

	$charData->hp 		+= 10;
	$charData->hpMax 	+= 10;

	echo "HP increases by 10! ($charData->hp/$charData->hpMax)\n";

	$charData->state = GameStates::Adventuring;
});

$levelUp->commands[] = new InputFragment(array("MP", "2"), function($charData, $mapData) {

	$charData->mp 		+= 10;
	$charData->mpMax 	+= 10;

	echo "MP increases by 10! ($charData->mp/$charData->mpMax)\n";

	$charData->state = GameStates::Adventuring;
});
