<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("personas.php");

class LevelUp {

	public $commands = [];
}

$levelUp = new LevelUp();

function getStatEquivalent($statName) {

	$outString = "";

	switch ( $statName ) {
		case "precision": {
			$outString = "hit chance";
		}
		break;
		case "endurance": {
			$outString = "HP";
		}
		break;
		case "reflexes": {
			$outString = "dodge chance";
		}
		break;
		case "strength": {
			$outString = "attack damage";
		}
		break;
		case "oddness": {
			$outString = "MP";
		}
		break;
		case "nerve": {
			$outString = "defense";
		}
		break;
		case "acuity": {
			$outString = "crit chance";
		}
		break;
		default:
		break;
	}

	return $outString;
}

function consumeStatIncrease($charData, $statName) {

	$charData->{$statName}++;

	$statEquivalent = getStatEquivalent($statName);

	echo "Your $statName increases, improving your $statEquivalent. You go back to Adventuring.\n";

	StateManager::ChangeState($charData, GameStates::Adventuring);
}

$levelUp->commands[] = new InputFragment("precision", function($charData, $mapData) {

	consumeStatIncrease($charData, "precision");
});
$levelUp->commands[] = new InputFragment("endurance", function($charData, $mapData) {

	// Increase HP.
	$charData->hp 		+= PersonaMultiplier::Endurance;
	$charData->hpMax 	+= PersonaMultiplier::Endurance;

	consumeStatIncrease($charData, "endurance");
});
$levelUp->commands[] = new InputFragment("reflexes", function($charData, $mapData) {

	consumeStatIncrease($charData, "reflexes");
});
$levelUp->commands[] = new InputFragment("strength", function($charData, $mapData) {

	consumeStatIncrease($charData, "strength");
});
$levelUp->commands[] = new InputFragment("oddness", function($charData, $mapData) {

	// Increase MP.
	$charData->mp 		+= PersonaMultiplier::Oddness;
	$charData->mpMax 	+= PersonaMultiplier::Oddness;

	consumeStatIncrease($charData, "oddness");
});
$levelUp->commands[] = new InputFragment("nerve", function($charData, $mapData) {

	consumeStatIncrease($charData, "nerve");
});
$levelUp->commands[] = new InputFragment("acuity", function($charData, $mapData) {

	consumeStatIncrease($charData, "acuity");
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($levelUp->commands);
$allocator->Allocate();
