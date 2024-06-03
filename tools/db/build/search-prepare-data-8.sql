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
