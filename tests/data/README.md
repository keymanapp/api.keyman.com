# Data files for tests

Because this data takes a while to build, and is static, we build the database once when first running any test class, based on the state of the t_dbdatasources table (we test the langtags.json record). If this matches the test data, we don't rebuild. We also always run on the first database ($mssqldb0).

This means that the local database will be using test data post-test (we don't rebuild from live data), so don't forget to run tools/db/build/build_cli.php to rebuild from live data if you need it.

These data files were downloaded at approximately 2020-05-25 3:40pm AEST.
Note: langtags.json - version 1.1.1 downloaded from https://raw.githubusercontent.com/silnrsi/langtags/master/pub/langtags.json (1.1.1 is still in staging)
