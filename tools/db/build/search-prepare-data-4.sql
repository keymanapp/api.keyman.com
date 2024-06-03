--
-- Canonicalize bcp47 codes into langtags entries
--
-- Fixup those that are missing from t_langtags, first
--

-- Find those that are missing where there is a matching base tag but not a matching full tag

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

