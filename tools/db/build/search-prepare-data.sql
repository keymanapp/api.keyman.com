UPDATE t_iso639_3 SET Part2B=NULL WHERE Part2B='';
UPDATE t_iso639_3 SET Part2T=NULL WHERE Part2T='';
UPDATE t_iso639_3 SET Part1=NULL WHERE Part1='';
UPDATE t_iso639_3 SET _Comment=NULL WHERE _Comment='';
UPDATE t_iso639_3 SET CanonicalId=COALESCE(Part1,Id);

--
-- We need to do some sanitisation of the t_language_index and t_iso639_3_names
-- to remove names marked as pejorative in the Ethnologue index.
--

delete
  t_iso639_3_names
where exists (select * from t_ethnologue_language_index el where el.LangID = t_iso639_3_names.Id and (el.nametype='LP' or el.nametype='DP'))

delete
  t_language_index
where exists (select * from t_ethnologue_language_index el where el.LangID = t_language_index.language_id and (el.nametype='LP' or el.nametype='DP'))

delete from t_ethnologue_language_index where nametype='LP' or nametype='DP';

--
-- Deprecated keyboards and models should be flagged as such in the t_keyboard/t_model data
--

update t_keyboard
  set deprecated = 1
  where exists (select * from t_keyboard_related kr where kr.related_keyboard_id = t_keyboard.keyboard_id);

update t_model
  set deprecated = 1
  where exists (select * from t_model_related mr where mr.related_model_id = t_model.model_id);
