#!/usr/bin/env bash

echo "---- Sleep 15 Before Generating DB ----"
sleep 15;
php /var/www/html/tools/db/build/build_cli.php
