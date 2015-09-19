<?php

// Names are "higher level" the further down the file we go.
// i.e. line 1 monster is a Rat, line 30 monster a Tiny Dragon
class NameGenerator {

	public static function Monster($level) {

		$adjective 	= NameGenerator::GetSingleLine("data/monster_adjectives.txt");

		// Stay in the same vague area of difficulty as the level we're on.
		$levelAbove	= 10;
		$monster	= NameGenerator::GetSingleLine("data/monsters.txt", $level, $level + $levelAbove);

		$output = $adjective . " " . $monster;

		return $output;
	}

	public function Weapon($level) {

	}

	public function Armour($level) {

	}

	public function Attack($level) {

	}

	private static function GetLinesFromFile($filePath) {

		$handle = fopen($filePath, "r");
		$data	= fread($handle, filesize($filePath));
		fclose($handle);

		$lines	= explode(PHP_EOL, $data);

		return $lines;
	}

	private static function GetSingleLine($filePath, $overriddenMin = -1, $overriddenMax = -1) {

		$lines 	= NameGenerator::GetLinesFromFile($filePath);

		$min	= 0;
		$max	= count($lines) - 1;
		if ( $overriddenMin > 0 && $overriddenMax > 0 ) {
			// Use overridden values, but stay in bounds.
			$min = $overriddenMin;
			$max = min($overriddenMax, $max);
		}

		$line	= $lines[rand($min, $max)];
		$line	= rtrim($line);

		return $line;
	}
}
