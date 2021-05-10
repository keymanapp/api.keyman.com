-- We'll go ahead and create databases if they are not present, before choosing the database to populate based on the t_active setting

USE master;
GO

IF DB_ID('keyboards_database') IS NULL /* keyboards_database will be replaced with predefined database name at build */
BEGIN
  EXEC('CREATE DATABASE keyboards_database') /* keyboards_database will be replaced with predefined database name at build */
END
GO

USE keyboards_database  /* keyboards_database will be replaced with predefined database name at build */
EXEC('CREATE SCHEMA k0')
EXEC('CREATE SCHEMA k1')
EXEC('CREATE USER k0 FOR LOGIN k0 WITH DEFAULT_SCHEMA=k0')
EXEC('CREATE USER k1 FOR LOGIN k1 WITH DEFAULT_SCHEMA=k1')
EXEC('ALTER ROLE db_owner ADD MEMBER k0')
EXEC('ALTER ROLE db_owner ADD MEMBER k1')
EXEC('CREATE SCHEMA kstats')
