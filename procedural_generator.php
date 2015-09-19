<?php

class ProcGen {

	public function InitFromSeed($inSeed) {

		srand($inSeed);
	}

	public function GenerateRoomForMap(&$map, $xVal, $yVal, $playerLevel) {


	}

	public static function GetMapSize() {
		return ProcGen::$MAP_SIZE;
	}

	private $seed;
	private static $MAP_SIZE = 1001;
}
