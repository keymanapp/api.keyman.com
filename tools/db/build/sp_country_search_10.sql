/*
 sp_country_search_10
*/

DROP PROCEDURE IF EXISTS sp_country_search_10;
GO

CREATE PROCEDURE sp_country_search_10 (
 @prmSearchRegEx NVARCHAR(250),
 @prmSearchPlain NVARCHAR(250),
 @prmMatchType INT
) AS
BEGIN
  SET NOCOUNT ON;

  DROP TABLE IF EXISTS #countries;
  DROP TABLE IF EXISTS #languages;

  CREATE TABLE #countries (
    id NCHAR(2),
    name NVARCHAR(75),
    area NVARCHAR(10)
  );

  CREATE TABLE #languages (
    id NVARCHAR(3),
    country_id NCHAR(2),
    name NVARCHAR(75)
  );

  IF @prmMatchType = 0
  BEGIN
    -- Search for code only
    INSERT INTO
      #countries
    SELECT
      ecc.CountryID id,
      ecc.Name name,
      ecc.Area area
    FROM
      t_ethnologue_country_codes ecc
    WHERE
      ecc.CountryID = @prmSearchPlain;
  END
  ELSE
  BEGIN
    IF @prmMatchType = 2
    BEGIN
      -- Search for region only
      INSERT INTO
        #countries
      SELECT
        ecc.CountryID id,
        ecc.Name name,
        ecc.Area area
      FROM
        t_ethnologue_country_codes ecc
      WHERE
        ecc.Area LIKE @prmSearchRegEx;
    END
    ELSE -- prmMatchType = 1
    BEGIN
      -- Search for name or code
      INSERT INTO
        #countries
      SELECT
        ecc.CountryID,
        ecc.Name,
        ecc.Area
      FROM
        t_ethnologue_country_codes ecc
      WHERE
        ecc.CountryID = @prmSearchPlain OR
        ecc.Name LIKE @prmSearchRegex;
    END
  END

  -- Languages
  INSERT INTO
    #languages
  SELECT
    iso6393.CanonicalId,
    elc.CountryID,
    elc.Name
  FROM
    t_iso639_3 iso6393 INNER JOIN
    t_ethnologue_language_codes elc ON iso6393.Id = elc.LangID
  WHERE
    elc.CountryID IN (SELECT id FROM #countries)
  ORDER BY
    2;

  SET NOCOUNT OFF;

  SELECT * FROM #countries;
  SELECT * FROM #languages;

  -- Keyboards matching the languages found
  SELECT
    k.language_id,
    k.keyboard_id
  FROM
    t_keyboard_language k
  WHERE
    k.language_id IN (SELECT id FROM #languages)
  ORDER BY
    k.keyboard_id;

  DROP TABLE IF EXISTS #countries;
  DROP TABLE IF EXISTS #languages;
END;
