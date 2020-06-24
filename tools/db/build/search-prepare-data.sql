UPDATE t_iso639_3 SET Part2B=NULL WHERE Part2B='';
UPDATE t_iso639_3 SET Part2T=NULL WHERE Part2T='';
UPDATE t_iso639_3 SET Part1=NULL WHERE Part1='';
UPDATE t_iso639_3 SET _Comment=NULL WHERE _Comment='';
UPDATE t_iso639_3 SET CanonicalId=COALESCE(CAST(Part1 AS NVARCHAR),CAST(Id AS NVARCHAR))

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

--
-- Canonicalize bcp47 codes into langtags entries
--
-- Fixup those that are missing from t_langtags, first
--

-- Find those that are missing and add the names and tags as aliases of the base tag

INSERT
  t_langtag (tag, [full], iso639_3, region, regionname, name, sldr, script, windows)
SELECT DISTINCT
  kl.bcp47,
  kl.bcp47,
  null,
  t.region,
  t.regionname,
  kl.description,
  0,
  kl.script_id,
  kl.bcp47
FROM
  t_keyboard_language kl LEFT JOIN
  t_langtag_tag tt ON kl.bcp47 = tt.tag LEFT JOIN
  t_langtag_tag tt0 ON kl.language_id = tt0.tag LEFT JOIN
  t_langtag t ON tt0.base_tag = t.tag
WHERE
  tt.tag IS NULL AND
  tt0.tag IS NOT NULL

-- Insert the tags above for searching against

INSERT
  t_langtag_tag (base_tag, tag, tagtype)
SELECT DISTINCT
  kl.bcp47,
  kl.bcp47,
  5 -- custom (keyboard) tag type
FROM
  t_keyboard_language kl LEFT JOIN
  t_langtag_tag tt ON kl.bcp47 = tt.tag LEFT JOIN
  t_langtag_tag tt0 ON kl.language_id = tt0.tag
WHERE
  tt.tag IS NULL AND
  tt0.tag IS NOT NULL

-- Add new names that have been defined by keyboard authors

INSERT
  t_langtag_name (tag, name, name_kd, nametype)
SELECT DISTINCT
  t.base_tag,
  kl.description,
  kl.description, -- TODO: we can't do full normalisation here, but we'll live with it for now
  4 -- custom
FROM
  t_keyboard_language kl LEFT JOIN
  t_langtag_tag t ON kl.bcp47 = t.tag LEFT JOIN
  t_langtag_name n ON n.tag = t.base_tag AND n.name = kl.description
WHERE
  n._id IS NULL and t.tag is not null

-- Finally, match up all the keyboards with langtags!

INSERT
  t_keyboard_langtag
SELECT
  kl.keyboard_id, tt.base_tag
FROM
  t_keyboard_language kl INNER JOIN
  t_langtag_tag tt ON kl.bcp47 = tt.tag
