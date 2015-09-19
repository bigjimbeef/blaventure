<?php

class CharacterSaveData {
	public $name		= null;		// str
	public $class		= null;		// str
	public $level		= 0;		// int
	public $hp			= 0;		// int
	public $hpMax		= 0;		// int
	public $mp			= 0;		// int
	public $mpMax		= 0;		// int
	public $weapon		= null;		// str
	public $weaponVal	= 0;		// int
	public $armour		= null;		// str
	public $armourVal	= 0;		// int
	public $gold		= 0;		// int

	public $state		= GameStates::NameSelect;

	// Used for procedural generation.
	public $randomSeed 	= 0;		// int

	public $restStart	= 0;
	public $restEnd		= 0;
}

// Function Matches is called on each InputFragment, and the callback is called if it does match the input.
class InputFragment {

	public 			$tokens;
	public function Matches($input) {
		
		$matchFound = false;
		foreach ( $this->tokens as $token ) {

			if ( strcasecmp($input, $token) == 0 ) {
				$matchFound = true;
				break;
			}
		}

		return $matchFound;
	}

	public function FireCallback($data) {

		call_user_func($this->callback, $data);
	}

	// ctor
	function __construct($inTokens, $inCallback) {
		$this->tokens 	= $inTokens;
		$this->callback = $inCallback;
	}

	private 		$callback;
}
