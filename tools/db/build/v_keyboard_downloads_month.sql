DROP VIEW IF EXISTS v_keyboard_downloads_month;
GO

CREATE VIEW v_keyboard_downloads_month AS
  SELECT
    kd.keyboard_id,
	  SUM(kd.count) count
  FROM
    kstats.t_keyboard_downloads kd
  WHERE
    DATEDIFF(day, kd.statdate, GETDATE()) < 31
  GROUP BY
    kd.keyboard_id
