DROP VIEW IF EXISTS v_keyboard_downloads_total;
GO

CREATE VIEW v_keyboard_downloads_total AS
  SELECT
    kd.keyboard_id,
	  SUM(kd.count) count
  FROM
    kstats.t_keyboard_downloads kd
  GROUP BY
    kd.keyboard_id
