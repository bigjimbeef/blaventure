#!/bin/sh

if [ $# -lt 1 ]; then
	echo "Needs input param."
	exit
fi

case $1 in
	"map")
		php check.php map 	> DEBUG/_map
		less DEBUG/_map
	;;
	"char")
		php check.php char 	> DEBUG/_char
		less DEBUG/_char
	;;
	"sb")
		php check.php scoreboard  > DEBUG/_sb
		less DEBUG/_sb
	;;
	*)
		echo "Enter either 'map' or 'char'"
	;;
esac
