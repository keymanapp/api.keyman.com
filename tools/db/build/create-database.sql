-- We'll go ahead and create databases if they are not present, before choosing the database to populate based on the t_active setting

IF DB_ID('keyboards') IS NULL
BEGIN
  CREATE DATABASE keyboards;
END

IF DB_ID('keyboards_1') IS NULL
BEGIN
  CREATE DATABASE keyboards_1;
END
