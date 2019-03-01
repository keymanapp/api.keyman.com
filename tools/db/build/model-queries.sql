USE keyboards;

/*
 sp_model_search
*/

DROP PROCEDURE IF EXISTS sp_model_search;

CREATE PROCEDURE sp_model_search (
IN prmSearchRegex VARCHAR(2048),
 IN prmSearchPlain VARCHAR(2048),
 IN prmMatchType INT
)
READS SQL DATA
BEGIN
  SELECT
    m.model_info
    
  FROM
    t_model m
  WHERE
    (
      prmMatchType = 0 AND
      m.model_id = prmSearchPlain
    ) OR (
      prmMatchType = 1 AND (
        m.model_id REGEXP prmSearchRegex OR
        m.name REGEXP prmSearchRegex OR
        m.description REGEXP prmSearchRegex
      )
    ) OR (
      prmMatchType = 2 AND (
        EXISTS (SELECT * FROM t_model_language ml WHERE ml.model_id = m.model_id AND ml.language_id = prmSearchPlain)
      )
    )
  ORDER BY
    m.name;
END;
