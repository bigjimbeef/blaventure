<?php

include_once("spell_list.php");

$defaultSpells = array(

	"Barbarian" => array(),

	"Cleric" => array($lesserHeal->name),

	"Fighter" => array($powerAttack->name),

	"Monk" => array($quiveringPalm->name),

	"Rogue" => array($backstab->name),

	"Wizard" => array($fireblast->name, $fireball->name)
);
