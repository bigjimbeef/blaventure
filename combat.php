<?php 

include_once("statics.php");
include_once("class_definitions.php");

include_once("spellcasting.php");

class Combat {

	public $commands = [];

	public function monsterDamaged($monster, $room, $damage, $charData) {

		$survived = true;

		$monster->hp -= $damage;

		if ( $monster->hp <= 0 ) {

			$survived = false;
		}

		return $survived;
	}

	public function appendScoreboardInfo(&$charData) {

		$home 		= getenv("HOME");
		$filePath 	= "$home/.blaventure/$charData->nick.scoreboard";

		// Delete the current save game.
		$charFilePath 	= "$home/.blaventure/$charData->nick.char";
		$mapFilePath 	= "$home/.blaventure/$charData->nick.map";

		unlink($charFilePath);
		unlink($mapFilePath);

		$currentTop = null;

		if ( file_exists($filePath) ) {
			$handle		= fopen($filePath, "r");
			$contents	= fread($handle, filesize($filePath));

			$currentTop = unserialize($contents);
			fclose($handle);
		}

		if ( is_null($currentTop) || $charData->level > $currentTop->level ) {

			$handle		= fopen($filePath, "w");
			$winner		= serialize($charData);

			fwrite($handle, $winner);

			fclose($handle);

			return " On the plus side, you set a new personal best!\n";
		}

		// Must be a loser
		return " That wasn't your best.\n";
	}

	public function playerDamaged(&$charData, $damage) {

		$charData->hp -= $damage;

		if ( $charData->hp <= 0 ) {

			$deathMsg = "Oh no! $charData->name has died!";

			$deathMsg .= $this->appendScoreboardInfo($charData);

			echo $deathMsg;

			exit(11);
		}
	}

	// Note: check spreadsheet for reference to these arcane formulae!
	public function attackDamage($level, $attack) { 

		$minDamage = floor(1.5 * ($level + 1)) + $attack;
		$maxDamage = (2 * ($level + 1)) + $attack;

		$damage		= rand($minDamage, $maxDamage);
		$crit		= false;

		$chanceIn20	= rand(1, 20);
		if ( $chanceIn20 == 20 ) {
			$crit = true;

			$damage *= 2;
		}

		return array($damage, $crit);
	}

	public function monsterAttack(&$charData, $monster) {

		list($damage, $crit) = $this->attackDamage($monster->level, $monster->attack);
		
		$attackType = $crit ? "CRIT" : "hit";

		$mitigatedDamage = $damage - $charData->armourVal;
		$this->playerDamaged($charData, $mitigatedDamage);

		return array($attackType, $mitigatedDamage);
	}

	public function playerAttack(&$charData, &$room, &$monster, $spellDmg = null, $spellText = null) {

		$changedState = false;

		list($damage, $crit) = $this->attackDamage($charData->level, $charData->weaponVal);

		// Used when spellcasting.
		if ( !is_null($spellDmg) ) {
			$damage = $spellDmg;
		}

		$attackType = $crit ? "CRIT" : "hit";
		$fightOutput = "You $attackType the $monster->name for $damage!";

		// Used when spellcasting.
		if ( $spellText ) {
			$fightOutput = $spellText;
		}

		if ( $this->monsterDamaged($monster, $room, $damage, $charData) ) {

			// It survived. Attacks back.
			list ($attackType, $damage) = $this->monsterAttack($charData, $monster);

			$fightOutput .= (" It $attackType" . "s back for $damage! ($charData->hp/$charData->hpMax)\n");
		}
		else {

			$fightOutput .= " It dies! Check the body for loot!\n";

			// Move to the looting state.
			$charData->state = GameStates::Looting;

			$changedState = true;
		}

		$fightOutput .= "\n";

		echo $fightOutput;

		return $changedState;
	}
}

$combat = new Combat();

$combat->commands[] = new InputFragment("status", function($charData, $mapData) {

	$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
	$monster 	= $room->occupant;

	$status = "Level $monster->level $monster->name ($monster->hp/$monster->hpMax HP)    You ($charData->hp/$charData->hpMax HP  $charData->mp/$charData->mpMax MP)\n";

	echo $status;
});

$combat->commands[] = new InputFragment("attack", function($charData, $mapData) use($combat) {

	$room = $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);

	// Must have an occupant to be in combat.
	$monster = $room->occupant;
	
	$combat->playerAttack($charData, $room, $monster);
});

$combat->commands[] = new InputFragment("magic", function($charData, $mapData) {

	// Barbarian check time!
	if ( empty($charData->spellbook) ) {
		echo "You don't have any spells to cast!\n";
		return;
	}

	$spellList = $charData->spellbook;

	// Check we have enough mana to cast one of them.
	global $spellcasting;
	$canCast = $spellcasting->canCastSpell($charData);

	if ( !$canCast ) {
		echo "You don't have enough MP to cast any spells!\n";
		return;
	}

	$output = "Choose a spell: ";

	$spellcasting->generateInputFragments($charData);

	foreach ( $spellcasting->commands as $fragment ) {

		$output .= "$fragment->displayString, ";
	}

	$output = rtrim($output, ", ") . "\n";

	echo $output;

	$charData->state = GameStates::Spellcasting;
});

$combat->commands[] = new InputFragment("run", function($charData, $mapData) use($combat) {

	$chanceInSix = rand(1,6);
	// 5+ to escape
	if ( $chanceInSix < 5 ) {

		$room 		= $mapData->map->GetRoom($mapData->playerX, $mapData->playerY);
		$monster 	= $room->occupant;

		list ($attackType, $damage) = $combat->monsterAttack($charData, $monster);
		echo "You get caught and $attackType for $damage damage!\n";
	}
	else {

		echo "You scurry back to the last room!\n";

		$charData->state = GameStates::Adventuring;

		$mapData->playerX = $mapData->lastPlayerX;
		$mapData->playerY = $mapData->lastPlayerY;
	}
});

// Add unique identifiers to commands.
$allocator = new UIDAllocator($combat->commands);
$allocator->Allocate();
