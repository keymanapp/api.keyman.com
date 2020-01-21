DROP DATABASE IF EXISTS cjk;

CREATE DATABASE IF NOT EXISTS cjk CHARACTER SET utf8 COLLATE utf8_general_ci;

USE cjk;

CREATE TABLE IF NOT EXISTS kmw_japanese (
  kana varchar(64) not null,
  kanji varchar(64) not null,
  gloss varchar(255) null,
  pri int not null,
  seq int not null
);

CREATE TABLE IF NOT EXISTS kmw_chinese_pinyin (
  pinyin_key varchar(32) not null,
  id int not null,
  chinese_text varchar(32) not null,
  tip varchar(32) not null,
  frequency int null
);
