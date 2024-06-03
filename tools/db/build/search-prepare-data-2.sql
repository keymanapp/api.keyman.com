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

