/*
 sp_model_search
*/

DROP PROCEDURE IF EXISTS sp_model_search;
GO

CREATE PROCEDURE sp_model_search (
  @prmSearchRegex NVARCHAR(2048),
  @prmSearchPlain NVARCHAR(2048),
  @prmMatchType INT
) AS
BEGIN
  SELECT
    m.model_info

  FROM
    t_model m
  WHERE
    (
      @prmMatchType = 0 AND
      m.model_id = @prmSearchPlain
    ) OR (
      @prmMatchType = 1 AND (
        m.model_id LIKE @prmSearchRegex OR
        m.name LIKE @prmSearchRegex OR
        m.description LIKE @prmSearchRegex
      )
    ) OR (
      @prmMatchType = 2 AND (
        EXISTS (SELECT * FROM t_model_language ml WHERE ml.model_id = m.model_id AND ml.bcp47 = @prmSearchPlain)
      )
    )
  ORDER BY
    m.name;
END;
