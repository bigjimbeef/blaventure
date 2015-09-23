<?php

include_once("class_definitions.php");

class ProcGen {

	public function InitFromSeed($inSeed) {

		srand($inSeed);
	}

	public function GenerateRoomForMap(&$map, $xVal, $yVal, $playerLevel, $noSpawning = false) {

		$room = new Room($xVal, $yVal);

		$chanceInHundred = rand(1, 100);

		// 50% chance to spawn a monster.
		if ( !$noSpawning && $chanceInHundred > 50 ) {

			// Monster difficulty scales, in part, with distance from the start.
			$midPoint		= floor($MAP_SIZE / 2);
			$xDistFromStart = abs($xVal - $midPoint);
			$yDistFromStart = abs($yVal - $midPoint);

			$dist = max($xDistFromStart, $yDistFromStart);

			$room->occupant = new Monster($playerLevel, $dist);
		}

		$map->grid[$xVal][$yVal] = $room;

		return $room;
	}

	public static function GetMapSize() {
		return ProcGen::$MAP_SIZE;
	}

	private $seed;
	private static $MAP_SIZE = 101;
}

$procGen = new ProcGen();
