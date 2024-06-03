-- Fixup those where we cannot find any matching base tag at all (e.g. qa? tags will fit into this)

INSERT
  t_langtag (tag, [full], iso639_3, region, regionname, name, sldr, script, windows)
SELECT DISTINCT
  kl.bcp47,
  kl.bcp47,
  null,
  '001', --t.region,
  'World', --t.regionname,
  kl.description,
  0,
  kl.script_id,
  kl.bcp47
FROM
  t_keyboard_language kl LEFT JOIN
  t_langtag_tag tt ON kl.bcp47 = tt.tag
WHERE
  tt.tag IS NULL

