-- Finally, match up all the keyboards with langtags!

INSERT
  t_keyboard_langtag
SELECT
  kl.keyboard_id, tt.base_tag
FROM
  t_keyboard_language kl INNER JOIN
  t_langtag_tag tt ON kl.bcp47 = tt.tag
