# Setting up database for Azure

We need two new logins, `k0` and `k1`, for swapping schemas.

```sql
-- First, on master:

CREATE LOGIN k0 WITH PASSWORD='...'
CREATE LOGIN k1 WITH PASSWORD='...'

CREATE USER k0 FOR LOGIN k0
CREATE USER k1 FOR LOGIN k1

ALTER ROLE dbmanager ADD MEMBER k0
ALTER ROLE dbmanager ADD MEMBER k1

-- Then, on the target database:

CREATE SCHEMA k0
CREATE SCHEMA k1

CREATE USER k0 FOR LOGIN k0 WITH DEFAULT_SCHEMA=k0
CREATE USER k1 FOR LOGIN k1 WITH DEFAULT_SCHEMA=k1

ALTER ROLE db_owner ADD MEMBER k0
ALTER ROLE db_owner ADD MEMBER k1
```

