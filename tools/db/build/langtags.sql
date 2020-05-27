DROP TABLE IF EXISTS t_langtag;
CREATE TABLE t_langtag (
  tag NVARCHAR(128) NOT NULL primary key,
  [full] NVARCHAR(128) NOT NULL,
  iso639_3 NVARCHAR(3),
  region NVARCHAR(3) NOT NULL,
  regionname NVARCHAR(128),
  name NVARCHAR(128) NOT NULL,
  -- localname NVARCHAR(128), this field is deprecated; look at t_langtag_name
  sldr BIT,
  nophonvars BIT,
  script NVARCHAR(4),
  suppress BIT,
  windows NVARCHAR(128)
);

DROP TABLE IF EXISTS t_langtag_name;
CREATE TABLE t_langtag_name (
  tag NVARCHAR(128) NOT NULL,
  name NVARCHAR(128) NOT NULL,
  name_kd NVARCHAR(128), -- NFKD, accents stripped for search (never displayed)
  nametype INT, -- 0=name, 1=localname, 2=latnname, 3=iana
  foreign key (tag) REFERENCES t_langtag (tag)
);

DROP TABLE IF EXISTS t_langtag_tag;
CREATE TABLE t_langtag_tag (
  tag NVARCHAR(128) NOT NULL,
  alttag NVARCHAR(128) NOT NULL,
  alttagtype INT, -- 0=tag, 1=variant
  foreign key (tag) REFERENCES t_langtag (tag)
);

DROP TABLE IF EXISTS t_langtag_region;
CREATE TABLE t_langtag_region (
  tag NVARCHAR(128) NOT NULL,
  region NVARCHAR(3) NOT NULL,
  foreign key (tag) REFERENCES t_langtag (tag)
);

