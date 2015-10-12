<?php

class Persona {
	public $name		= 0;

	public $precision	= 0;
	public $endurance	= 0;
	public $reflexes	= 0;
	public $strength	= 0;
	public $oddness		= 0;
	public $nerve		= 0;
	public $acuity		= 0;

	function __construct($p, $e, $r, $s, $o, $n, $a) {
		$this->precision 	= $p;
		$this->endurance 	= $e;
		$this->reflexes 	= $r;
		$this->strength 	= $s;
		$this->oddness 		= $o;
		$this->nerve 		= $n;
		$this->acuity 		= $a;
	}
}

class PersonaList {

	private $personas = [];

	public function getPersona($name) {

		$caselessName = strtolower($name);

		return $this->personas[$caselessName];
	}

	function __construct() {

		$this->personas["barbarian"] = new Persona(0, 6, 3, 8, 0, 3, 0);
		$this->personas["cleric"] = new Persona(0, 4, 0, 3, 10, 3, 0);
		$this->personas["fighter"] = new Persona(0, 10, 0, 4, 1, 5, 0);
		$this->personas["monk"] = new Persona(2, 5, 6, 2, 5, 0, 0);
		$this->personas["rogue"] = new Persona(1, 7, 5, 3, 1, 0, 3);
		$this->personas["wizard"] = new Persona(0, 2, 0, 1, 15, 2, 0);
	}
}

$personaList = new PersonaList();
