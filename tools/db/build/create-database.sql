-- We'll go ahead and create databases if they are not present, before choosing the database to populate based on the t_active setting

USE master;
GO

IF DB_ID('$keyboards') IS NULL
BEGIN
  EXEC('CREATE DATABASE $keyboards')
END
GO

USE $keyboards
EXEC('CREATE SCHEMA k0')
EXEC('CREATE SCHEMA k1')
EXEC('CREATE USER k0 FOR LOGIN k0 WITH DEFAULT_SCHEMA=k0')
EXEC('CREATE USER k1 FOR LOGIN k1 WITH DEFAULT_SCHEMA=k1')
EXEC('GRANT ALL TO k0')
EXEC('GRANT ALL TO k1')
EXEC('ALTER ROLE db_owner ADD MEMBER k0')
EXEC('ALTER ROLE db_owner ADD MEMBER k1')
