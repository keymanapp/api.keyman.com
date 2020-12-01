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

1. Install the dependencies:

```
composer install
```

2. Configure your local environment by copying tools/db/localenv.php.in to tools/db/localenv.php
   and completing the details therein.

3. Build the backend database from live data:

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

## Configuring a new Azure Database

1. Create an Azure SQL Server
2. Create an Azure SQL Database, e.g. called 'keymanapi'
3. Run the following script on the master database, replacing password as necessary:

```
-- logins for staging
CREATE LOGIN [k0] WITH PASSWORD=N'password'
GO

CREATE LOGIN [k1] WITH PASSWORD=N'password'
GO

-- logins for production
CREATE LOGIN [production_k0] WITH PASSWORD=N'password'
GO

CREATE LOGIN [production_k1] WITH PASSWORD=N'password'
GO
```

4. Run the following script on the keymanapi database:

```
-- Schemas, users and roles for staging
CREATE SCHEMA [k0]
GO

CREATE SCHEMA [k1]
GO

CREATE USER [k0] FOR LOGIN [k0] WITH DEFAULT_SCHEMA=[k0]
GO

CREATE USER [k1] FOR LOGIN [k1] WITH DEFAULT_SCHEMA=[k1]
GO

ALTER ROLE db_owner ADD MEMBER k0
GO

ALTER ROLE db_owner ADD MEMBER k1
GO

-- Schemas, users and roles for production
CREATE SCHEMA [production_k0]
GO

CREATE SCHEMA [production_k1]
GO

CREATE USER [production_k0] FOR LOGIN [production_k0] WITH DEFAULT_SCHEMA=[production_k0]
GO

CREATE USER [production_k1] FOR LOGIN [production_k1] WITH DEFAULT_SCHEMA=[production_k1]
GO

ALTER ROLE db_owner ADD MEMBER production_k0
GO

ALTER ROLE db_owner ADD MEMBER production_k1
GO
```
