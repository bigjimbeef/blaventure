<?php

// DO NOT CALL THIS DIRECTLY. USE check.sh INSTEAD.

$char 		= file_get_contents("/home/minikeen/.blaventure/minikeen.char");
$charData	= unserialize($char);

$map 		= file_get_contents("/home/minikeen/.blaventure/minikeen.map");
$mapData	= unserialize($map);

$sb 		= file_get_contents("/home/minikeen/.blaventure/minikeen.scoreboard");
$sbData		= unserialize($sb);

$dyn 		= file_get_contents("/home/minikeen/.blaventure/minikeen.dynasty");
$dynData	= unserialize($dyn);

if ( $argv[1] == "char" ) {
	print_r($charData);
}
if ( $argv[1] == "map" ) {
	print_r($mapData);
}
if ( $argv[1] == "scoreboard" ) {
	print_r($sbData);
}
if ( $argv[1] == "dyn" ) {
	print_r($dynData);
}
