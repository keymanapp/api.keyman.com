# api.keyman.com

## Configuration

Currently, this site runs only on a Windows host with IIS and Microsoft SQL Server.

## Prerequisites

* Windows
* Chocolatey
* PHP 7.4
* MS SQL Server 2016 or later including FullText Search

* `configure.ps1` automatically installs chocolatey, PHP, Composer, SQL Server and PHP-PDO driver
  for SQL Server. This script is not particularly sophisticated, so for manual config, copy and
  paste elements from the script.

## Setup

Build the backend database from live data:

```
composer build
```

## Tests

Test suites run with mock data from the tests/data folder. If this data is refreshed, fixtures
will probably need to be updated accordingly as the data in them will have become stale.

To run tests:

```
composer test
```

To force a rebuild of the test database (e.g. if schema changes):

```
TEST_REBUILD=1 composer test
```
