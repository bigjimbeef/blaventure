<?php

include_once("class_definitions.php");

class ProcGen {

	public function InitFromSeed($inSeed) {

		srand($inSeed);
	}

	private function getMapDist($xVal, $yVal) {
		
		$midPoint		= floor(ProcGen::GetMapSize() / 2);
		$xDistFromStart = abs($xVal - $midPoint);
		$yDistFromStart = abs($yVal - $midPoint);

		$dist = max($xDistFromStart, $yDistFromStart);

		return $dist;
	}

	public function GenerateRoomForMap(&$map, $xVal, $yVal, $playerLevel, $noSpawning = false) {

		$room = new Room($xVal, $yVal);

		$chanceInHundred = rand(1, 100);

		// 5% chance to spawn an item shop
		if ( !$noSpawning && $chanceInHundred > 95 ) {

			// Item shop loot scales with distance from the center.
			$dist = $this->getMapDist($xVal, $yVal);

			$room->occupant = new Shop($playerLevel, $dist);
		}
		// 50% chance to spawn a monster.
		if ( false && !$noSpawning && $chanceInHundred > 50 ) {

			// Monster difficulty scales, in part, with distance from the start.
			$dist = $this->getMapDist($xVal, $yVal);

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
