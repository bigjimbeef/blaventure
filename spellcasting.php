<?php

include_once("statics.php");
include_once("class_definitions.php");

include_once("spell_list.php");

class Spellcasting {

	public $commands = [];

	private function castSpell($charData, $mapData) {

		
	}

	public function generateInputFragments($charData) {

		$spellList = $charData->spellbook;

		$spellNum = 0;
		foreach( $spellList as $spell ) {

			$this->commands[] = new InputFragment(array($spell, $spellNum), function($charData, $mapData) {
				
				$this->castSpell($charData, $mapData);
			});

			$spellNum++;
		}
	}
}

$spellcasting = new Spellcasting();
