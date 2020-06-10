IF exists (select * from sys.fulltext_indexes i where i.object_id = object_id('t_keyboard'))
  DROP FULLTEXT INDEX ON t_keyboard;

if exists (select * from sys.fulltext_catalogs where name = 'c_keyboard')
  DROP FULLTEXT CATALOG c_keyboard;

CREATE FULLTEXT CATALOG c_keyboard;

DROP INDEX IF EXISTS ix_keyboard_id ON t_keyboard;
CREATE UNIQUE INDEX ix_keyboard_id ON t_keyboard (keyboard_id);

CREATE FULLTEXT INDEX ON t_keyboard (
  description,
  name
) KEY INDEX ix_keyboard_id ON c_keyboard;
