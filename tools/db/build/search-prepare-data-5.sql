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

