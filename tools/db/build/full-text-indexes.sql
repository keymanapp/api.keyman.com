--
-- Cleanup existing indexes
--

IF exists (select * from sys.fulltext_indexes i where i.object_id = object_id('t_langtag_name') and schema_id(object_schema_name(i.object_id)) = schema_id())
  DROP FULLTEXT INDEX ON t_langtag_name;

IF exists (select * from sys.fulltext_indexes i where i.object_id = object_id('t_keyboard') and schema_id(object_schema_name(i.object_id)) = schema_id())
  DROP FULLTEXT INDEX ON t_keyboard;

DROP INDEX IF EXISTS ix_keyboard_id ON t_keyboard;
DROP INDEX IF EXISTS ix_langtag_name_tag ON t_langtag_name;
DROP INDEX IF EXISTS ix_region_id ON t_region;
DROP INDEX IF EXISTS ix_script_id ON t_script;

--
-- Catalog: we use a separate catalog for each schema, although it's not technically necessary,
-- but this helps to keep the scripts completely isolated
--

DECLARE @stmt NVARCHAR(MAX)

if exists (select * from sys.fulltext_catalogs c where c.name = schema_name() + '_c_keyboard')
begin
  SET @stmt = REPLACE('DROP FULLTEXT CATALOG c_keyboard', 'c_keyboard', schema_name()+'_c_keyboard')
  EXEC(@stmt);
end

SET @stmt = REPLACE('CREATE FULLTEXT CATALOG c_keyboard', 'c_keyboard', schema_name()+'_c_keyboard')
EXEC(@stmt);

SET @stmt=REPLACE('
  --
  -- Keyboard name and description fulltext search
  --

  CREATE UNIQUE INDEX ix_keyboard_id ON t_keyboard (keyboard_id);

  CREATE FULLTEXT INDEX ON t_keyboard (
    description,
    name
  ) KEY INDEX ix_keyboard_id ON c_keyboard;

  --
  -- Keyboard Search (Languages)
  --

  CREATE UNIQUE INDEX ix_langtag_name_tag ON t_langtag_name (_id);

  CREATE FULLTEXT INDEX ON t_langtag_name (
    name,
    name_kd
  ) KEY INDEX ix_langtag_name_tag ON c_keyboard;

  --
  -- Keyboard Search (Region)
  --

  CREATE UNIQUE INDEX ix_region_id ON t_region (region_id);

  CREATE FULLTEXT INDEX ON t_region (
    name
  ) KEY INDEX ix_region_id ON c_keyboard;

  --
  -- Keyboard Search (Script)
  --

  CREATE UNIQUE INDEX ix_script_id ON t_script (script_id);

  CREATE FULLTEXT INDEX ON t_script (
    name
  ) KEY INDEX ix_script_id ON c_keyboard;
', 'c_keyboard', schema_name()+'_c_keyboard')

EXEC(@stmt)