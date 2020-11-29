DROP TABLE IF EXISTS kmw_japanese;
CREATE TABLE kmw_japanese (
  kana NVARCHAR(64) not null,
  kanji NVARCHAR(64) not null,
  gloss NVARCHAR(255) null,
  pri int not null,
  seq int not null
);

DROP TABLE IF EXISTS kmw_chinese_pinyin;
CREATE TABLE kmw_chinese_pinyin (
  pinyin_key NVARCHAR(32) not null,
  id int not null,
  chinese_text NVARCHAR(32) not null,
  tip NVARCHAR(32) not null,
  frequency int null
);
