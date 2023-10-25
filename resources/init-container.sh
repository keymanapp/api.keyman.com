#!/usr/bin/env bash

# Note: currently on TIER_TEST, build.sh skips running this, because the 
# tests will rebuild the database with static test data anyway. If we add
# additional steps to this script, we may need to remove this optimization
# from build.sh.

echo "---- Sleep 15 Before Generating DB ----"
sleep 15;
php /var/www/html/tools/db/build/build_cli.php
