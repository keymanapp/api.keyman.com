#!/bin/bash

set -e 

function validate-file {
  local json=$1
  local dottedVer=$(basename `dirname "$1"` | cut -c 1).0
  ../tools/jsv.exe ../schemas/keymanweb-cloud-api/keymanweb-cloud-api-$dottedVer.json $json
}

function validate {
  local ver=$1
  local dottedVer=$(echo $ver | cut -c 1).0
  echo "TESTING $dottedVer"
  for json in data/$ver/*.json; do
    echo "  $json"
    validate-file $json
  done
}

if [ -f "$1" ]; then
  validate-file $1
  exit 0
fi

if [ ! -z "$1" ]; then
  validate $1
  exit 0
fi

for basever in data/*; do
  basever=`basename $basever`
  validate $basever
done

