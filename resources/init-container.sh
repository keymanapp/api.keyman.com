#!/usr/bin/env bash

openssl version
uname -a

echo "---- Sleep 15 for SQL Server to start before generating DB ----"
sleep 15;

# If we know we are immediately going to run tests, there's no need to build
# the database and then rebuild it again as a test database!
if [[ ! -f /var/www/html/tier.txt ]] || [[ $(</var/www/html/tier.txt) != TIER_TEST ]]; then
  php /var/www/html/tools/db/build/build_cli.php -f
else
  echo "tier.txt contains TIER_TEST -- not generating database!"
  echo "(For normal use, delete tier.txt and restart)"
fi
