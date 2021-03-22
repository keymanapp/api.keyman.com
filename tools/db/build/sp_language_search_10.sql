/*
 sp_language_search_10
*/

DROP PROCEDURE IF EXISTS sp_language_search_10;
GO

CREATE PROCEDURE sp_language_search_10 (
 @prmSearchRegEx NVARCHAR(250),
 @prmSearchPlain NVARCHAR(250),
 @prmMatchType INT,
 @prmAll BIT
) AS
BEGIN
  SET NOCOUNT ON;

  DROP TABLE IF EXISTS #languages;

  CREATE TABLE #languages (
    id NVARCHAR(3),
    name NVARCHAR(128)
  );

  --
  -- Search on lang name or iso code or country (missing data atm)
  --
  IF @prmSearchPlain = '*' AND @prmMatchType = 1
  BEGIN
    --
    -- Results for codes only
    --
    INSERT INTO
      #languages
    SELECT DISTINCT
      kl.language_id,
      iso6393.Ref_Name
    FROM
      t_keyboard_language kl INNER JOIN
      t_iso639_3 iso6393 ON kl.language_id = iso6393.CanonicalID;
  END
  ELSE
  BEGIN
    IF @prmMatchType = 0
    BEGIN
      --
      -- Results for codes only
      --
      INSERT INTO
        #languages
      SELECT
        iso6393.CanonicalId,
        iso6393.Ref_Name
      FROM
        t_iso639_3 iso6393
      WHERE
        iso6393.Part1 = @prmSearchPlain OR
        iso6393.Id = @prmSearchPlain;
    END
    ELSE
    BEGIN
      IF @prmMatchType = 1
      BEGIN
        --
        -- Results for languages with matching names
        --
        INSERT INTO
          #languages
        SELECT DISTINCT
          iso.CanonicalId,
          CASE
            WHEN (eli.Name LIKE @prmSearchRegex OR iso.Part1 = @prmSearchPlain OR iso.Id=@prmSearchPlain) AND (eli.NameType = 'L') THEN
              eli.Name
            ELSE (
              SELECT TOP 1
                CASE
                  WHEN eli.Name = eli0.Name THEN eli.Name
                  ELSE CONCAT(eli0.Name, ' (', eli.Name, ')')
                END
              FROM
                t_ethnologue_language_index eli0
              WHERE
                eli0.LangID = eli.LangID AND
                eli0.NameType = 'L'
            )
          END
        FROM
          t_iso639_3 iso LEFT JOIN
          t_ethnologue_language_index eli ON eli.LangID = iso.Id
        WHERE
          eli.Name LIKE @prmSearchRegex OR
          iso.Part1 = @prmSearchPlain OR
          iso.Id = @prmSearchPlain;
      END
      ELSE -- prmMatchType = 2
      BEGIN
        --
        -- Results for languages for country id in prmSearchPlain
        --
        INSERT INTO
          #languages
        SELECT
          iso6393.CanonicalId,
          elc.Name
        FROM
          t_iso639_3 iso6393 INNER JOIN
          t_ethnologue_language_codes elc ON iso6393.Id = elc.LangID
        WHERE
          elc.CountryID = @prmSearchPlain
        ORDER BY
          2;
       END
    END
  END

  SET NOCOUNT OFF;

  SELECT id, name from
    (select id, name, row_number() over(partition by id order by name) as roworder from #languages) temp
  WHERE roworder=1
  ORDER BY name;

  SELECT
    k.language_id,
    k.keyboard_id
  FROM
    t_keyboard_language k
  WHERE
    k.language_id IN (SELECT id FROM #languages)
  ORDER BY
    k.keyboard_id;
END;
GO