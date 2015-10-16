<?php

include_once("file_io.php");

// Names are "higher level" the further down the file we go.
// i.e. line 1 monster is a Rat, line 30 monster a Tiny Dragon
class NameGenerator {

	private static function GetLevelBasedName($level, $adjectivesPath, $filePath, $levelAbove) {

		$adjective 	= NameGenerator::GetSingleLine($adjectivesPath, $level, $level + $levelAbove);

		// Stay in the same vague area of difficulty as the level we're on.
		$object	= NameGenerator::GetSingleLine($filePath, $level, $level + $levelAbove);

		$output = $adjective . " " . $object;

		return $output;
	}

	public static function Monster($level) {

		return NameGenerator::GetLevelBasedName($level, "data/monster_adjectives.txt", "data/monsters.txt", 10);
	}

	public function Weapon($level) {

		return NameGenerator::GetLevelBasedName($level, "data/weapon_adjectives.txt", "data/weapons.txt", 3);
	}

	public function Armour($level) {

		return NameGenerator::GetLevelBasedName($level, "data/armour_adjectives.txt", "data/armours.txt", 3);
	}

	public function Attack($level) {

	}

	public static function GetArticle($word) {
		$vowels 	= array("a", "e", "i", "o", "u");

		$firstChar 	= $word[0];
		$firstChar	= strtolower($firstChar);

		$article	= in_array($firstChar, $vowels) ? "an" : "a";

		return $article;
	}

	private static function GetLinesFromFile($filePath) {

		$data	= FileIO::ReadFile($filePath);

		$lines	= explode(PHP_EOL, $data);

		return $lines;
	}

	private static function GetSingleLine($filePath, $overriddenMin = -1, $overriddenMax = -1) {

		$lines 	= NameGenerator::GetLinesFromFile($filePath);

		$min	= 0;
		$max	= count($lines) - 1;
		if ( $overriddenMin > 0 && $overriddenMax > 0 ) {
			// Use overridden values, but stay in bounds.
			$min = min($max, $overriddenMin);
			$max = min($overriddenMax, $max);
		}

		$idx	= rand($min, $max);
		$line	= $lines[$idx];
		$line	= rtrim($line);

		return $line;
	}
}
