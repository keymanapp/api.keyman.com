UPDATE t_iso639_3 SET Part2B=NULL WHERE Part2B='';
UPDATE t_iso639_3 SET Part2T=NULL WHERE Part2T='';
UPDATE t_iso639_3 SET Part1=NULL WHERE Part1='';
UPDATE t_iso639_3 SET _Comment=NULL WHERE _Comment='';
UPDATE t_iso639_3 SET CanonicalId=COALESCE(Part1,Id);

--
-- We need to do some sanitisation of the t_language_index and t_iso639_3_names
-- to remove names marked as pejorative in the Ethnologue index.
--

create table t_pejorative_index (
  id char(3) not null,
  name varchar(75) not null
);

insert t_pejorative_index 
  select el.LangID, el.Name
  from t_ethnologue_language_index el where el.nametype='LP' or el.nametype='DP';
  
-- This ridiculous syntax is needed because MySQL is horrendously slow on joined deletes
-- this is probably solvable but it's not worth the extra research effort...

update
  t_iso639_3_names inner join
  t_pejorative_index el on el.id = t_iso639_3_names.Id and el.Name = t_iso639_3_names.Print_Name
 set
  print_name='XXXXXX';
 
delete from t_iso639_3_names where print_name='XXXXXX';

update
  t_language_index inner join
  t_pejorative_index el on el.id = t_language_index.language_id and el.Name = t_language_index.Name
 set
  t_language_index.name='XXXXXX';
 
delete from t_language_index where name='XXXXXX';
  
--
-- Now we will eliminate all pejorative names from the Ethnologue 
-- name index so we can avoid accidentally using them.
--

delete from t_ethnologue_language_index where nametype='LP' or nametype='DP';

drop table t_pejorative_index;

--
-- Deprecated keyboards and models should be flagged as such in the t_keyboard/t_model data
--

update t_keyboard 
  set deprecated = 1
  where exists (select * from t_keyboard_related kr where kr.related_keyboard_id = t_keyboard.keyboard_id);

update t_model
  set deprecated = 1
  where exists (select * from t_model_related mr where mr.related_model_id = t_model.model_id);
