<?php

include_once("statics.php");
include_once("class_definitions.php");

function getScoreFromFile($path) {

	if ( !file_exists($path) ) {
		return;
	}

	$handle 	= fopen($path, "r");
	$contents	= fread($handle, filesize($path));
	fclose($handle);

	$scoreData	= unserialize($contents);

	return array($scoreData->level, $scoreData->kills, $scoreData);
}

function getScores() {

	$home 		= getenv("HOME");
	$filePath 	= "$home/.blaventure/";
	$dir 		= new DirectoryIterator($filePath);

	$nickScores = [];

	foreach ($dir as $fileinfo) {
	    
	    if ( $fileinfo->isDot() ) {
	        continue;
	    }

	    $fileName 	= $fileinfo->getFilename();
	    $nickRegex	= "/\A([a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]*).[\w]+/i";

	    preg_match($nickRegex, $fileName, $matches);

	    // Ensure we got a nick match.
	    if ( count($matches) <= 1 ) {
	    	continue;
	    }

	    // We now have a nick.
	    $nick = $matches[1];

	    // Don't want to process nicks more than once.
	    if ( in_array($nick, array_keys($nickScores)) ) {
	    	continue;
	    }

		// Check best ever character.
		$scoreBoardPath 							= $filePath . $nick . ".scoreboard";
		list($bestLevel, $bestKills, $bestData) 	= getScoreFromFile($scoreBoardPath);

		// Check current character.
		$currentCharPath 							= $filePath . $nick . ".char";
		list($level, $kills, $data) 				= getScoreFromFile($currentCharPath);

		// TODO: Separate level/kills?
		if ( $level > $bestLevel ) {

			$bestLevel 	= $level;
			$bestKills 	= $kills;
			$bestData	= $data;
		}

		$nickScores[$nick] = array("nick" => $bestData->nick, "level" => $bestLevel, "kills" => $bestKills, "data" => $bestData);
	}

	return $nickScores;
}

function buildLeaderBoard($scores, $numLeaders = -1) {

	$leaders = [];
	foreach ( $scores as $nick => $nickScore ) {

		$leaders[] = $nickScore;
	}

	usort($leaders, function($a, $b) {

		return $b['level'] - $a['level'];
	});

	if ( $numLeaders > 0 ) {
		$leaders = array_slice($leaders, 0, $numLeaders);		
	}

	return $leaders;
}

// 1. Denyer, level 8 Barbarian 2. Silari, level 6 Wizard 3: wjoe 3.0, level 5 Fighter
function getLeadersAsString($leaders) {

	$output = "";

	for ( $i = 1; $i <= count($leaders); ++$i ) {

		$leader = $leaders[$i - 1];

		$nick	= $leader["nick"];
		$name	= $leader["data"]->name;
		$level 	= $leader["data"]->level;
		$class 	= $leader["data"]->class;

		$output .= "$i. $name, level $level $class ($nick)  ";
	}

	$output = rtrim($output) . "\n";

	return $output;
}

$numLeaders = -1;
if ( !empty($argv) && isset($argv[1]) ) {

	$num = $argv[1];

	if ( is_numeric($num) ) {

		$numLeaders = intval($num);
	}
}

$scores 	= getScores();
$leaders	= buildLeaderBoard($scores, $numLeaders);

$topString	= getLeadersAsString($leaders);

echo $topString;
