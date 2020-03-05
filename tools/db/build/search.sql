DROP DATABASE IF EXISTS keyboards;

CREATE DATABASE IF NOT EXISTS keyboards DEFAULT CHARSET=utf8mb4;

USE keyboards;

CREATE TABLE IF NOT EXISTS t_language (
  language_id varchar(3) not null primary key
);

CREATE TABLE IF NOT EXISTS t_language_index (
  language_id varchar(3),
  name varchar(64),
  foreign key (language_id) references t_language (language_id)
);

CREATE TABLE IF NOT EXISTS t_region (
  region_id varchar(3) not null primary key,
  name varchar(64)
);

CREATE TABLE IF NOT EXISTS t_script (
  script_id varchar(4) not null primary key,
  name varchar(64)
);

/*====================================================================

 Keyboard data, unpacked from keyboard_info json for searching.
 Original .keyboard_info is stored in the keyboard_info column so
 we are not reconstructing the json from the data on each query. This
 also ensures we do not lose data if the keyboard_info format is
 extended in the future.

====================================================================*/

CREATE TABLE IF NOT EXISTS t_keyboard (
  keyboard_id varchar(256) not null primary key,
  name varchar(256),
  author_name varchar(256),
  author_email varchar(256),
  description text,
  license varchar(32),
  last_modified date,

  version varchar(64),
  min_keyman_version varchar(64),
  min_keyman_version_1 int,
  min_keyman_version_2 int,
  legacy_id int,

  package_filename varchar(256),
  package_filesize int,
  js_filename varchar(256),
  js_filesize int,
  documentation_filename varchar(256),
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

  keyboard_info json
);

/*
 A language entry for a given keyboard. If there is a
 matching language + region + script, then these will be populated,
 but the bcp47 column is the master identifier for the language entry.
 For example, the language_id may be qxx for an invented language.
*/
CREATE TABLE IF NOT EXISTS t_keyboard_language (
  keyboard_id varchar(256),
  bcp47 varchar(64),
  language_id varchar(3),
  region_id char(2),
  script_id char(4),
  description varchar(256), /* use when bcp47 is broader than lang+reg+scr? */

  foreign key (keyboard_id) references t_keyboard (keyboard_id)
  /*foreign key (language_id) references t_language (language_id),
  foreign key (region_id) references t_region (region_id),
  foreign key (script_id) references t_script (script_id)*/
);

CREATE TABLE IF NOT EXISTS t_keyboard_link (
  keyboard_id varchar(256),
  url varchar(1024),
  name varchar(256),

  foreign key (keyboard_id) references t_keyboard (keyboard_id)
);

CREATE TABLE IF NOT EXISTS t_keyboard_related (
  keyboard_id varchar(256),
  related_keyboard_id varchar(256), /* we don't use a fk constraint because the related keyboard may not exist here */
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

CREATE TABLE IF NOT EXISTS t_model (
  model_id varchar(256) not null primary key,
  name varchar(256),
  author_name varchar(256),
  author_email varchar(256),
  description text,
  license varchar(32),
  last_modified date,

  version varchar(64),
  min_keyman_version varchar(64),
  min_keyman_version_1 int,
  min_keyman_version_2 int,

  package_filename varchar(256),
  package_filesize int,
  js_filename varchar(256),
  js_filesize int,

  is_rtl bit,

  includes_fonts bit,

  deprecated bit,

  model_info json
);

/*
 A language entry for a given model. If there is a
 matching language + region + script, then these will be populated,
 but the bcp47 column is the master identifier for the language entry.
 For example, the language_id may be qxx for an invented language.
*/
CREATE TABLE IF NOT EXISTS t_model_language (
  model_id varchar(256),
  bcp47 varchar(64),
  language_id varchar(3),
  region_id char(2),
  script_id char(4),
  description varchar(256), /* use when bcp47 is broader than lang+reg+scr? */

  foreign key (model_id) references t_model (model_id)
  /*foreign key (language_id) references t_language (language_id),
  foreign key (region_id) references t_region (region_id),
  foreign key (script_id) references t_script (script_id)*/
);

CREATE TABLE IF NOT EXISTS t_model_link (
  model_id varchar(256),
  url varchar(1024),
  name varchar(256),

  foreign key (model_id) references t_model (model_id)
);

CREATE TABLE IF NOT EXISTS t_model_related (
  model_id varchar(256),
  related_model_id varchar(256), /* we don't use a fk constraint because the related model may not exist here */
  deprecates bit,

  foreign key (model_id) references t_model (model_id)
);

/*====================================================================

Standards Data

====================================================================*/

CREATE TABLE IF NOT EXISTS t_iso639_3 (
  Id      char(3) NOT NULL,  /* The three-letter 639-3 identifier*/
  Part2B  char(3) NULL,      /* Equivalent 639-2 identifier of the bibliographic applications */
                            /* code set, if there is one*/
  Part2T  char(3) NULL,      /* Equivalent 639-2 identifier of the terminology applications code */
                            /* set, if there is one*/
  Part1   char(2) NULL,      /* Equivalent 639-1 identifier, if there is one    */
  _Scope   char(1) NOT NULL,  /* I(ndividual), M(acrolanguage), S(pecial)*/
  _Type    char(1) NOT NULL,  /* A(ncient), C(onstructed),  */
                            /* E(xtinct), H(istorical), L(iving), S(pecial)*/
  Ref_Name   varchar(150) NOT NULL,   /* Reference language name */
  _Comment    varchar(150) NULL,      /* Comment relating to one or more of the columns*/
  CanonicalId char(3) NULL   /* The canonical ID, being either Part1 or Id */
);

CREATE TABLE IF NOT EXISTS t_iso639_3_names (
  Id             char(3)     NOT NULL,  /* The three-letter 639-3 identifier*/
  Print_Name     varchar(75) NOT NULL,  /* One of the names associated with this identifier */
  Inverted_Name  varchar(75) NOT NULL  /* The inverted form of this Print_Name form   */
);

CREATE TABLE IF NOT EXISTS t_iso639_3_macrolanguages (
  M_Id      char(3) NOT NULL,   /* The identifier for a macrolanguage */
  I_Id      char(3) NOT NULL,   /* The identifier for an individual language */
                                /* that is a member of the macrolanguage */
  I_Status  char(1) NOT NULL    /* A (active) or R (retired) indicating the */
                                /* status of the individual code element */
);

/* Ethnologue data: Documentation at https://www.ethnologue.com/codes/ */

CREATE TABLE IF NOT EXISTS t_ethnologue_language_codes (
   LangID      char(3) NOT NULL,  /* Three-letter code */
   CountryID   char(2) NOT NULL,  /* Main country where used */
   LangStatus  char(1) NOT NULL,  /* L(iving), (e)X(tinct) */
   Name    varchar(75) NOT NULL   /* Primary name in that country */
);

/* As of writing, t_ethnologue_country_codes is a subset of t_region, i.e.
   there are no codes in t_ethnologue_country_codes that are not in t_region */
CREATE TABLE IF NOT EXISTS t_ethnologue_country_codes (
   CountryID  char(2) NOT NULL,  /* Two-letter code from ISO3166 */
   Name   varchar(75) NOT NULL,  /* Country name */
   Area   varchar(10) NOT NULL   /* World area */
);

CREATE TABLE IF NOT EXISTS t_ethnologue_language_index (
  LangID    char(3) NOT NULL,  /* The three-letter 639-3 identifier*/
  CountryID char(2) NOT NULL, /* Lookup from t_ethnologue_country_codes */
  NameType char(2) NOT NULL,  /* One of:  L(anguage), LA(lternate),D(ialect), DA(lternate), */
                              /*          LP,DP (a pejorative alternate) */
  Name  varchar(75) NOT NULL  /* The name */
);

CREATE INDEX ix_language_index_name ON t_language_index (Name, language_id);

CREATE INDEX ix_iso639_3_part1 ON t_iso639_3 (Part1, Id);
CREATE INDEX ix_iso639_3_id ON t_iso639_3 (Id);
CREATE INDEX ix_iso639_3_names_print_name ON t_iso639_3_names (Print_Name, Id);
CREATE INDEX ix_iso639_3_id_part1_ref_name ON t_iso639_3 (Id, Part1, Ref_Name);
CREATE INDEX ix_iso639_3_part1_id_ref_name ON t_iso639_3 (Part1, Id, Ref_Name);
CREATE INDEX ix_iso639_3_canonicalid_id_ref_name ON t_iso639_3 (CanonicalID, Id, Ref_Name);

CREATE INDEX ix_keyboard_language ON t_keyboard_language (keyboard_id, language_id);

CREATE INDEX ix_ethnologue_language_index_name ON t_ethnologue_language_index (Name, LangID);
CREATE INDEX ix_ethnologue_language_index_langid ON t_ethnologue_language_index (LangID, Name);
CREATE INDEX ix_elc_langid_countryid_name ON t_ethnologue_language_codes (LangID, CountryID, Name);
CREATE INDEX ix_ecc_countryid ON t_ethnologue_country_codes (CountryID);
