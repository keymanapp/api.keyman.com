--
-- Cleanup existing indexes
--

IF exists (select * from sys.fulltext_indexes i where i.object_id = object_id('t_langtag_name'))
  DROP FULLTEXT INDEX ON t_langtag_name;

IF exists (select * from sys.fulltext_indexes i where i.object_id = object_id('t_keyboard'))
  DROP FULLTEXT INDEX ON t_keyboard;

DROP INDEX IF EXISTS ix_keyboard_id ON t_keyboard;
DROP INDEX IF EXISTS ix_langtag_name_tag ON t_langtag_name;
DROP INDEX IF EXISTS ix_region_id ON t_region;
DROP INDEX IF EXISTS ix_script_id ON t_script;

--
-- Catalog
--

if exists (select * from sys.fulltext_catalogs where name = 'c_keyboard')
  DROP FULLTEXT CATALOG c_keyboard;

CREATE FULLTEXT CATALOG c_keyboard;

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
