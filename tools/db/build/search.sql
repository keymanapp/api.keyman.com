-- This drops all FK constraints across the database before attempting to drop tables

DECLARE @name SYSNAME
DECLARE @object_name SYSNAME

DECLARE table_cursor CURSOR FOR
SELECT
    c.name,
    object_name(c.parent_object_id)
FROM
    sys.foreign_keys c
WHERE
    object_name(c.parent_object_id) LIKE 't[_]%' AND
    c.schema_id = schema_id()

OPEN table_cursor
FETCH NEXT FROM table_cursor INTO @name, @object_name

DECLARE @cmd varchar(200)
WHILE @@FETCH_STATUS = 0
BEGIN
    SET @cmd = 'alter table '+QUOTENAME(@object_name)+' DROP CONSTRAINT '+QUOTENAME(@name)
    PRINT @cmd
    EXEC( @cmd )
    FETCH NEXT FROM table_cursor INTO @name, @object_name
END

CLOSE table_cursor
DEALLOCATE table_cursor

-- Recreate tables

DROP TABLE IF EXISTS t_language_index;
DROP TABLE IF EXISTS t_language;
DROP TABLE IF EXISTS t_region;
DROP TABLE IF EXISTS t_script;

CREATE TABLE t_language (
  language_id nvarchar(3) not null primary key
);

CREATE TABLE t_language_index (
  language_id nvarchar(3),
  name nvarchar(64),
  foreign key (language_id) references t_language (language_id)
);

CREATE TABLE t_region (
  region_id nvarchar(3) not null primary key,
  name nvarchar(64)
);

CREATE TABLE t_script (
  script_id nvarchar(4) not null primary key,
  name nvarchar(64)
);

/*====================================================================

 Keyboard data, unpacked from keyboard_info json for searching.
 Original .keyboard_info is stored in the keyboard_info column so
 we are not reconstructing the json from the data on each query. This
 also ensures we do not lose data if the keyboard_info format is
 extended in the future.

====================================================================*/

DROP TABLE IF EXISTS t_keyboard;

CREATE TABLE t_keyboard (
  keyboard_id nvarchar(256) not null primary key,
  name nvarchar(256),
  author_name nvarchar(256),
  author_email nvarchar(256),
  description text,
  license nvarchar(32),
  last_modified date,

  version nvarchar(64),
  min_keyman_version nvarchar(64),
  min_keyman_version_1 int,
  min_keyman_version_2 int,
  legacy_id int,

  package_filename nvarchar(256),
  package_filesize int,
  js_filename nvarchar(256),
  js_filesize int,
  documentation_filename nvarchar(256),
  documentation_filesize int,

  is_rtl bit,
  is_unicode bit,
  is_ansi bit,

  includes_welcome bit,
  includes_documentation bit,
  includes_fonts bit,
  includes_visual_keyboard bit,

  platform_windows tinyint,
  platform_macos tinyint,
  platform_ios tinyint,
  platform_android tinyint,
  platform_web tinyint,
  platform_linux tinyint,

  deprecated bit,
  obsolete bit,

  keyboard_info nvarchar(max)
);

/*
 A language entry for a given keyboard. If there is a
 matching language + region + script, then these will be populated,
 but the bcp47 column is the master identifier for the language entry.
 For example, the language_id may be qxx for an invented language.
*/
DROP TABLE IF EXISTS t_keyboard_language;
CREATE TABLE t_keyboard_language (
  keyboard_id nvarchar(256),
  bcp47 nvarchar(64),
  language_id nvarchar(3),
  region_id nvarchar(3),
  script_id nchar(4),
  description nvarchar(256), /* use when bcp47 is broader than lang+reg+scr? */

  foreign key (keyboard_id) references t_keyboard (keyboard_id)
  /*foreign key (language_id) references t_language (language_id),
  foreign key (region_id) references t_region (region_id),
  foreign key (script_id) references t_script (script_id)*/
);

DROP TABLE IF EXISTS t_keyboard_link;
CREATE TABLE t_keyboard_link (
  keyboard_id nvarchar(256),
  url nvarchar(1024),
  name nvarchar(256),

  foreign key (keyboard_id) references t_keyboard (keyboard_id)
);

DROP TABLE IF EXISTS t_keyboard_related;
CREATE TABLE t_keyboard_related (
  keyboard_id nvarchar(256),
  related_keyboard_id nvarchar(256), /* we don't use a fk constraint because the related keyboard may not exist here */
  deprecates bit,

  foreign key (keyboard_id) references t_keyboard (keyboard_id)
);

/*====================================================================

 Model data, unpacked from model_info json for searching.
 Original .model_info is stored in the model_info column so
 we are not reconstructing the json from the data on each query. This
 also ensures we do not lose data if the model_info format is
 extended in the future.

====================================================================*/

DROP TABLE IF EXISTS t_model;
CREATE TABLE t_model (
  model_id nvarchar(256) not null primary key,
  name nvarchar(256),
  author_name nvarchar(256),
  author_email nvarchar(256),
  description text,
  license nvarchar(32),
  last_modified date,

  version nvarchar(64),
  min_keyman_version nvarchar(64),
  min_keyman_version_1 int,
  min_keyman_version_2 int,

  package_filename nvarchar(256),
  package_filesize int,
  js_filename nvarchar(256),
  js_filesize int,

  is_rtl bit,

  includes_fonts bit,

  deprecated bit,

  model_info nvarchar(max)
);

/*
 A language entry for a given model. If there is a
 matching language + region + script, then these will be populated,
 but the bcp47 column is the master identifier for the language entry.
 For example, the language_id may be qxx for an invented language.
*/
DROP TABLE IF EXISTS t_model_language;
CREATE TABLE t_model_language (
  model_id nvarchar(256),
  bcp47 nvarchar(64),
  language_id nvarchar(3),
  region_id nvarchar(3),
  script_id nchar(4),
  description nvarchar(256), /* use when bcp47 is broader than lang+reg+scr? */

  foreign key (model_id) references t_model (model_id)
  /*foreign key (language_id) references t_language (language_id),
  foreign key (region_id) references t_region (region_id),
  foreign key (script_id) references t_script (script_id)*/
);

DROP TABLE IF EXISTS t_model_link;
CREATE TABLE t_model_link (
  model_id nvarchar(256),
  url nvarchar(1024),
  name nvarchar(256),

  foreign key (model_id) references t_model (model_id)
);

DROP TABLE IF EXISTS t_model_related;
CREATE TABLE t_model_related (
  model_id nvarchar(256),
  related_model_id nvarchar(256), /* we don't use a fk constraint because the related model may not exist here */
  deprecates bit,

  foreign key (model_id) references t_model (model_id)
);

/*====================================================================

Standards Data

====================================================================*/

DROP TABLE IF EXISTS t_iso639_3;
CREATE TABLE t_iso639_3 (
  Id      nchar(3) NOT NULL,  /* The three-letter 639-3 identifier*/
  Part2B  nchar(3) NULL,      /* Equivalent 639-2 identifier of the bibliographic applications */
                            /* code set, if there is one*/
  Part2T  nchar(3) NULL,      /* Equivalent 639-2 identifier of the terminology applications code */
                            /* set, if there is one*/
  Part1   nchar(2) NULL,      /* Equivalent 639-1 identifier, if there is one    */
  _Scope   nchar(1) NOT NULL,  /* I(ndividual), M(acrolanguage), S(pecial)*/
  _Type    nchar(1) NOT NULL,  /* A(ncient), C(onstructed),  */
                            /* E(xtinct), H(istorical), L(iving), S(pecial)*/
  Ref_Name   nvarchar(150) NOT NULL,   /* Reference language name */
  _Comment    nvarchar(150) NULL,      /* Comment relating to one or more of the columns*/
  CanonicalId nvarchar(3) NULL   /* The canonical ID, being either Part1 or Id */
);

DROP TABLE IF EXISTS t_iso639_3_names;
CREATE TABLE t_iso639_3_names (
  Id             nchar(3)     NOT NULL,  /* The three-letter 639-3 identifier*/
  Print_Name     nvarchar(75) NOT NULL,  /* One of the names associated with this identifier */
  Inverted_Name  nvarchar(75) NOT NULL  /* The inverted form of this Print_Name form   */
);

DROP TABLE IF EXISTS t_iso639_3_macrolanguages;
CREATE TABLE t_iso639_3_macrolanguages (
  M_Id      nchar(3) NOT NULL,   /* The identifier for a macrolanguage */
  I_Id      nchar(3) NOT NULL,   /* The identifier for an individual language */
                                /* that is a member of the macrolanguage */
  I_Status  nchar(1) NOT NULL    /* A (active) or R (retired) indicating the */
                                /* status of the individual code element */
);

/* Ethnologue data: Documentation at https://www.ethnologue.com/codes/ */

DROP TABLE IF EXISTS t_ethnologue_language_codes;
CREATE TABLE t_ethnologue_language_codes (
   LangID      nchar(3) NOT NULL,  /* Three-letter code */
   CountryID   nchar(2) NOT NULL,  /* Main country where used */
   LangStatus  nchar(1) NOT NULL,  /* L(iving), (e)X(tinct) */
   Name    nvarchar(75) NOT NULL   /* Primary name in that country */
);

/* As of writing, t_ethnologue_country_codes is a subset of t_region, i.e.
   there are no codes in t_ethnologue_country_codes that are not in t_region */
DROP TABLE IF EXISTS t_ethnologue_country_codes;
CREATE TABLE t_ethnologue_country_codes (
   CountryID  nchar(2) NOT NULL,  /* Two-letter code from ISO3166 */
   Name   nvarchar(75) NOT NULL,  /* Country name */
   Area   nvarchar(10) NOT NULL   /* World area */
);

DROP TABLE IF EXISTS t_ethnologue_language_index;
CREATE TABLE t_ethnologue_language_index (
  LangID    nchar(3) NOT NULL,  /* The three-letter 639-3 identifier*/
  CountryID nchar(2) NOT NULL, /* Lookup from t_ethnologue_country_codes */
  NameType nchar(2) NOT NULL,  /* One of:  L(anguage), LA(lternate),D(ialect), DA(lternate), */
                              /*          LP,DP (a pejorative alternate) */
  Name  nvarchar(75) NOT NULL  /* The name */
);

DROP TABLE IF EXISTS t_dbdatasources;
CREATE TABLE t_dbdatasources (
  uri NVARCHAR(260) NULL,
  filename NVARCHAR(260) NULL,
  date INT NULL
);

-- drops obsolete t_keyboard_downloads from current schema,
-- which is different to the kstats schema version below
DROP TABLE IF EXISTS t_keyboard_downloads;
GO

--add a new schema for kstats here so we can use it in search.sql
IF SCHEMA_ID('kstats') IS NULL
BEGIN
	EXEC sp_executesql N'CREATE SCHEMA kstats'
END
GO

IF OBJECT_ID('kstats.t_keyboard_downloads', 'U') IS NULL
BEGIN
  CREATE TABLE kstats.t_keyboard_downloads (
    keyboard_id NVARCHAR(260) NOT NULL,
    statdate DATE,
    count INT NOT NULL
  )

  CREATE INDEX ix_keyboard_downloads ON kstats.t_keyboard_downloads (
    keyboard_id, statdate
  ) INCLUDE (count)
END
