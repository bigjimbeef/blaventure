<?php

include_once("class_definitions.php");
include_once("combat.php");

include_once("class_traits.php");

$allAbilites = array();

function findAbility($abilityName, $charData) {

	global $allAbilites;

	$outAbility = null;

	foreach ( $allAbilites as $ability ) {

		if ( strcasecmp($ability->name, $abilityName) == 0 ) {

			$outAbility = $ability;

			break;
		}
	}

	return $outAbility;
}

function clearAllAbilityLocks(&$charData) {

	$charData->lockedAbilities = null;
}

$rage = new Ability("Rage", 0, false, function(&$charData) {

	if ( $charData->rageTurns > 0 ) {
		$turnPlural = $charData->rageTurns > 1 ? "s" : "";
		$remainPlural = $charData->rageTurns > 1 ? "" : "s";

		echo "You're angry enough already! ($charData->rageTurns turn$turnPlural remain$remainPlural)\n";
		return;
	}
	else if ( !is_null($charData->lockedAbilities) && in_array("Rage", $charData->lockedAbilities) ) {
		
		echo "You're too tired to get super angry again so soon!\n";
		return;
	}

	// Starts the barbarian berserking, for a number of rounds based on their level.
	$numRounds = 1 + ceil($charData->level / 5);

	$charData->rageTurns = $numRounds;

	// We can only berserk once per combat.
	if ( !isset($charData->lockedAbilities) ) {
		$charData->lockedAbilities = array();
	}
	$charData->lockedAbilities[] = "Rage";

	$plural = $numRounds > 1 ? "s" : "";
	echo "You get angry. Seems like you'll be angry for about $numRounds turn$plural. Grr.\n";
});
$allAbilites[] = $rage;
