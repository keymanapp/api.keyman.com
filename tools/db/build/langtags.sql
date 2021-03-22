DROP TABLE IF EXISTS t_langtag;
CREATE TABLE t_langtag (
  tag NVARCHAR(128) NOT NULL primary key,
  [full] NVARCHAR(128) NOT NULL,
  iso639_3 NVARCHAR(3),
  region NVARCHAR(3) NOT NULL,
  regionname NVARCHAR(128),
  name NVARCHAR(128) NOT NULL, -- this is not used for search, only for primary name result
  -- localname NVARCHAR(128), this field is deprecated; look at t_langtag_name
  sldr BIT,
  nophonvars BIT,
  script NVARCHAR(4),
  suppress BIT,
  windows NVARCHAR(128)
);

DROP TABLE IF EXISTS t_langtag_name;
CREATE TABLE t_langtag_name (
  _id INT NOT NULL IDENTITY(1,1),
  tag NVARCHAR(128) NOT NULL,
  name NVARCHAR(128) NOT NULL,
  name_kd NVARCHAR(128), -- NFKD, accents stripped for search (never displayed)
  nametype INT, -- 0=name, 1=localname, 2=latnname, 3=iana
  foreign key (tag) REFERENCES t_langtag (tag)
);

DROP TABLE IF EXISTS t_langtag_tag;
CREATE TABLE t_langtag_tag (
  base_tag NVARCHAR(128) NOT NULL,
  tag NVARCHAR(128) NOT NULL,
  tagtype INT, -- 0=base_tag, 1=alternate tag, 2=variant, 3=windows, 4=full, 5=custom(keyboard)
  foreign key (base_tag) REFERENCES t_langtag (tag)
);

DROP TABLE IF EXISTS t_langtag_region;
CREATE TABLE t_langtag_region (
  tag NVARCHAR(128) NOT NULL,
  region NVARCHAR(3) NOT NULL,
  foreign key (tag) REFERENCES t_langtag (tag)
);

DROP TABLE IF EXISTS t_keyboard_langtag
CREATE TABLE t_keyboard_langtag (
  keyboard_id NVARCHAR(256) NOT NULL,
  tag NVARCHAR(128) NOT NULL
);
