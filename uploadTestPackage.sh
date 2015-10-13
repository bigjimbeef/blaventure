#!/bin/sh

rm blaventure-test.tar.gz
tar -zcvf blaventure-test.tar.gz .

scp -P 22222 blaventure-test.tar.gz smsd@bratch.co.uk:/home/smsd/blaventure-test
