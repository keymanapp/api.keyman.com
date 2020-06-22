DROP FUNCTION IF EXISTS f_get_canonical_bcp47;
GO

CREATE FUNCTION f_get_canonical_bcp47 (
  @prmTag NVARCHAR(250)
) RETURNS NVARCHAR(250) AS
BEGIN
  -- Get canonical tag if it's in our mapping
  DECLARE @varTag NVARCHAR(250)
  SELECT TOP 1 @varTag = base_tag FROM t_langtag_tag t WHERE t.tag = @prmTag

  -- It wasn't a known mapping, so lookup iso639-3
  IF @varTag IS NULL
    SELECT TOP 1 @varTag = tag FROM t_langtag t WHERE t.iso639_3 = @prmTag

  IF @varTag IS NULL
    -- Try and canonicalize the language subtag, assuming they've passed ISO639-3 tag
    SELECT TOP 1 @varTag = tag FROM t_langtag t WHERE t.iso639_3 = @prmTag

  IF @varTag IS NULL
    -- Give up, return the original tag. Won't have much luck in most searches though.
    SET @varTag = @prmTag

  RETURN @varTag
END
GO

/*
 sp_language_search
*/

DROP PROCEDURE IF EXISTS sp_language_search;
GO

CREATE PROCEDURE sp_language_search (
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
    name NVARCHAR(75)
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
        SELECT
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
          iso.Id = @prmSearchPlain
        --GROUP BY
        --  iso.CanonicalId;
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

  SELECT * from #languages ORDER BY name;

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

/*
 sp_country_search
*/

DROP PROCEDURE IF EXISTS sp_country_search;
GO

CREATE PROCEDURE sp_country_search (
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
