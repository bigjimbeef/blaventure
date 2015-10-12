<?php

include_once("statics.php");
include_once("class_definitions.php");

class Dynasty {

	public $commands = [];

	public $STAT_BASE_COSTS = [
		"precision"	=> 50,
		"endurance"	=> 10,
		"reflexes"	=> 20,
		"strength"	=> 20,
		"oddness"	=> 15,
		"nerve"		=> 20,
		"acuity"	=> 15,
	];

	public $STAT_MAX_LEVELS = [
		"precision"	=> 10,
		"endurance"	=> 25,
		"reflexes"	=> 15,
		"strength"	=> 15,
		"oddness"	=> 25,
		"nerve"		=> 15,
		"acuity"	=> 15,
	];

	public function generateInputFragments($charData, $dynData) {

		$precision = "precision";
		if ( isStatLevelAvailable($precision, $dynData) && canAffordStat($precision, $dynData) ) {

			$this->commands[] = new InputFragment($precision, function($charData, $mapData, $dynData) use($precision) {

				$AMOUNT = 10;	
				increaseStat($precision, $AMOUNT, $dynData);
			});
		}

		$endurance = "endurance";
		if ( isStatLevelAvailable($endurance, $dynData) && canAffordStat($endurance, $dynData) ) {

			$this->commands[] = new InputFragment($endurance, function($charData, $mapData, $dynData) use($endurance) {

				$AMOUNT = 5;
				increaseStat($endurance, $AMOUNT, $dynData);
			});
		}

		$reflexes = "reflexes";
		if ( isStatLevelAvailable($reflexes, $dynData) && canAffordStat($reflexes, $dynData) ) {

			$this->commands[] = new InputFragment($reflexes, function($charData, $mapData, $dynData) use($reflexes) {

				$AMOUNT = 2;
				increaseStat($reflexes, $AMOUNT, $dynData);
			});
		}

		$strength = "strength";
		if ( isStatLevelAvailable($strength, $dynData) && canAffordStat($strength, $dynData) ) {

			$this->commands[] = new InputFragment($strength, function($charData, $mapData, $dynData) use($strength) {

				$AMOUNT = 2;
				increaseStat($strength, $AMOUNT, $dynData);
			});
		}

		$oddness = "oddness";
		if ( isStatLevelAvailable($oddness, $dynData) && canAffordStat($oddness, $dynData) ) {

			$this->commands[] = new InputFragment($oddness, function($charData, $mapData, $dynData) use($oddness) {

				$AMOUNT = 5;
				increaseStat($oddness, $AMOUNT, $dynData);
			});
		}

		$nerve = "nerve";
		if ( isStatLevelAvailable($nerve, $dynData) && canAffordStat($nerve, $dynData) ) {

			$this->commands[] = new InputFragment($nerve, function($charData, $mapData, $dynData) use($nerve) {

				$AMOUNT = 2;
				increaseStat($nerve, $AMOUNT, $dynData);
			});
		}

		$acuity = "acuity";
		if ( isStatLevelAvailable($acuity, $dynData) && canAffordStat($acuity, $dynData) ) {

			$this->commands[] = new InputFragment($acuity, function($charData, $mapData, $dynData) use($acuity) {

				$AMOUNT = 2;
				increaseStat($acuity, $AMOUNT, $dynData);
			});
		}

		// Add unique identifiers to commands.
		$allocator = new UIDAllocator($this->commands);
		$allocator->Allocate();
	}
}

$dynasty = new Dynasty();

// PERSONA

// Precision	- hit
// Endurance	- hp
// Reflexes		- dodge
// Strength		- atk
// Oddness		- mp
// Nerve		- def
// Acuity		- crit

function isStatLevelAvailable($statName, $dynData) {

	global $dynasty;

	$currentLevel = $dynData->{$statName};

	return ($currentLevel < $dynasty->STAT_MAX_LEVELS[$statName]);
}

function getBaseCostFromLevel($dynData) {

	$BASE_COST			= 20;
	$COST_POWER			= 1.2;
	$currentLevel		= $dynData->level;

	$baseValue			= round(pow($COST_POWER, $currentLevel) * $BASE_COST, -1);

	return $baseValue;
}

function getStatIncreaseCost($statName, $dynData) {

	global $dynasty;

	$levelCost 		= getBaseCostFromLevel($dynData);
	$baseCost		= $dynasty->STAT_BASE_COSTS[$statName];

	$COST_POWER		= 2;
	$currentLevel	= $dynData->{$statName};
	$upgradeCost	= pow($COST_POWER, $currentLevel) * $baseCost;

	return ($levelCost + $upgradeCost);
}

function canAffordStat($statName, $dynData) {

	$increaseCost 	= getStatIncreaseCost($statName, $dynData);
	$currentGold	= $dynData->gold;

	return $currentGold >= $increaseCost;
}

function increaseStat($statName, $amount, &$dynData) {

	$increaseText = strcasecmp($statName, "reflexes") != 0 ? "increases" : "increase";
	echo "The $statName of your progeny $increaseText by $amount.\n";

	$upgradeCost = getStatIncreaseCost($statName, $dynData);
	$dynData->gold -= $upgradeCost;

	// Increase the stat level.
	$dynData->{$statName}++;

	// Increase overall Dynasty level.
	$dynData->level++;
}

// Returns string, or null if no stat increase available
function getSingleStatString($statName, $dynData) {
	
	if ( !isStatLevelAvailable($statName, $dynData) ) {
		return null;
	}

	$outString		= ucfirst($statName);

	// Add stat level info.
	global $dynasty;
	$maxLevel 		= $dynasty->STAT_MAX_LEVELS[$statName];
	$outString 		.= (" " . $dynData->{$statName} . "/$maxLevel");

	// Add stat cost.
	$upgradeCost	= getStatIncreaseCost($statName, $dynData);

	$outString		.= " (${upgradeCost}GP)";

	return $outString;
}

// Check the prices.
// 
// e.g. Precision 3/10 (400GP), Endurance 2/25 (500GP) etc.
//
$dynasty->commands[] = new InputFragment("check", function($charData, $mapData, $dynData) {

	$outString 			= "";

	$precisionString	= getSingleStatString("precision", $dynData);
	if ( !is_null($precisionString) ) {
		$outString .= $precisionString . ", ";
	}
	$enduranceString	= getSingleStatString("endurance", $dynData);
	if ( !is_null($enduranceString) ) {
		$outString .= $enduranceString . ", ";
	}
	$reflexesString		= getSingleStatString("reflexes", $dynData);
	if ( !is_null($reflexesString) ) {
		$outString .= $reflexesString . ", ";
	}
	$strengthString		= getSingleStatString("strength", $dynData);
	if ( !is_null($strengthString) ) {
		$outString .= $strengthString . ", ";
	}
	$oddnessString		= getSingleStatString("oddness", $dynData);
	if ( !is_null($oddnessString) ) {
		$outString .= $oddnessString . ", ";
	}
	$nerveString		= getSingleStatString("nerve", $dynData);
	if ( !is_null($nerveString) ) {
		$outString .= $nerveString . ", ";
	}
	$acuityString		= getSingleStatString("acuity", $dynData);
	if ( !is_null($acuityString) ) {
		$outString .= $acuityString . ", ";
	}

	$outString 	= rtrim($outString, ", ");

	$gold 		= $dynData->gold;
	$outString .= " [You have ${gold}GP]\n";

	echo $outString;
});

// Start the game already.
$dynasty->commands[] = new InputFragment("begin game", function($charData, $mapData, $dynData) {

	echo "Welcome! Please choose a name for your character:\n";

	StateManager::ChangeState($charData, GameStates::NameSelect);
});
