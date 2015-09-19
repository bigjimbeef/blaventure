<?php

// DO NOT CALL THIS DIRECTLY. USE check.sh INSTEAD.

$char 		= file_get_contents("/home/minikeen/.blaventure/minikeen.char");
$charData	= unserialize($char);

$map 		= file_get_contents("/home/minikeen/.blaventure/minikeen.map");
$mapData	= unserialize($map);

if ( $argv[1] == "char" ) {
	print_r($charData);	
}
if ( $argv[1] == "map" ) {
	print_r($mapData);	
}
