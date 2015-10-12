#!/bin/sh

rm ~/.blaventure/*.char
rm ~/.blaventure/*.map

while getopts "ds" opt; do
  case $opt in
    d)
      rm ~/.blaventure/*.dynasty      
      ;;
    s)
      rm ~/.blaventure/*.scoreboard
      ;;
  esac
done
