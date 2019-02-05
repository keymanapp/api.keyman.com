USE keyboards;

/*
 sp_keyboard_search
*/

DROP PROCEDURE IF EXISTS sp_keyboard_search;

CREATE PROCEDURE sp_keyboard_search (
IN prmSearchRegex VARCHAR(250),
 IN prmSearchPlain VARCHAR(250),
 IN prmMatchType INT
)
READS SQL DATA
BEGIN
  SELECT
    k.keyboard_id,
    k.name,
    k.author_name,
    k.author_email,
    k.description,
    k.license,
    k.last_modified,
    k.version,
    k.min_keyman_version,
    k.legacy_id,
    k.package_filename,
    k.js_filename,
    k.documentation_filename,
    k.is_ansi,
    k.is_unicode,
    k.includes_welcome,
    k.includes_documentation,
    k.includes_fonts,
    k.includes_visual_keyboard,
    k.platform_windows,
    k.platform_macos,
    k.platform_ios,
    k.platform_android,
    k.platform_web,
    k.keyboard_info
    
  FROM
    t_keyboard k
  WHERE
    (
      prmMatchType = 0 AND
      k.keyboard_id = prmSearchPlain
    ) OR (
      prmMatchType = 1 AND (
        k.keyboard_id REGEXP prmSearchRegex OR
        k.name REGEXP prmSearchRegex OR
        k.description REGEXP prmSearchRegex
      )
    ) OR (
      prmMatchType = 2 AND (
        EXISTS (SELECT * FROM t_keyboard_language kl WHERE kl.keyboard_id = k.keyboard_id AND kl.language_id = prmSearchPlain)
      )
    ) OR (
      prmMatchType = 3 AND (
        k.legacy_id = prmSearchPlain
      )
    )
  ORDER BY
    k.is_unicode DESC,
    k.name;
END;

/*
 sp_language_search
*/

DROP PROCEDURE IF EXISTS sp_language_search;

CREATE PROCEDURE sp_language_search (
 IN prmSearchRegEx VARCHAR(250),
 IN prmSearchPlain VARCHAR(250),
 IN prmMatchType INT,
 IN prmAll BIT
)
READS SQL DATA
BEGIN
  DROP TABLE IF EXISTS languages;
  
  CREATE TEMPORARY TABLE languages (
    id VARCHAR(3),
    name VARCHAR(75)
  ) ENGINE=MEMORY;

  --
  -- Search on lang name or iso code or country (missing data atm)
  --
  IF prmSearchPlain = '*' AND prmMatchType = 1 THEN
    --
    -- Results for codes only
    --
    INSERT INTO
      languages
    SELECT DISTINCT
      kl.language_id,
      iso6393.Ref_Name
    FROM
      t_keyboard_language kl INNER JOIN
      t_iso639_3 iso6393 ON kl.language_id = iso6393.CanonicalID;
 
  ELSE 
    IF prmMatchType = 0 THEN
      --
      -- Results for codes only
      --
      INSERT INTO
        languages
      SELECT 
        iso6393.CanonicalId,
        iso6393.Ref_Name
      FROM
        t_iso639_3 iso6393
      WHERE
        iso6393.Part1 = prmSearchPlain OR
        iso6393.Id = prmSearchPlain;
        
    ELSE
      IF prmMatchType = 1 THEN
        --
        -- Results for languages with matching names
        --
        INSERT INTO
          languages
        SELECT
          iso.CanonicalId,
          CASE 
            WHEN (eli.Name REGEXP prmSearchRegex OR iso.Part1 = prmSearchPlain OR iso.Id=prmSearchPlain) AND (eli.NameType = 'L') THEN
              eli.Name
            ELSE (
              SELECT 
                CASE
                  WHEN eli.Name = eli0.Name THEN eli.Name 
                  ELSE CONCAT(eli0.Name, ' (', eli.Name, ')')
                END
              FROM
                t_ethnologue_language_index eli0
              WHERE 
                eli0.LangID = eli.LangID AND 
                eli0.NameType = 'L'
              LIMIT 1
            )
          END
        FROM
          t_iso639_3 iso LEFT JOIN
          t_ethnologue_language_index eli ON eli.LangID = iso.Id
        WHERE
          eli.Name REGEXP prmSearchRegex OR
          iso.Part1 = prmSearchPlain OR
          iso.Id = prmSearchPlain
        GROUP BY
          iso.CanonicalId;
          
      ELSE -- prmMatchType = 2
        --
        -- Results for languages for country id in prmSearchPlain
        --
        INSERT INTO 
          languages
        SELECT 
          iso6393.CanonicalId,
          elc.Name
        FROM
          t_iso639_3 iso6393 INNER JOIN
          t_ethnologue_language_codes elc ON iso6393.Id = elc.LangID
        WHERE
          elc.CountryID = prmSearchPlain
        ORDER BY
          2;
       END IF;
    END IF;
  END IF;

  SELECT * from languages ORDER BY name;
  
  SELECT
    k.language_id,
    k.keyboard_id
  FROM 
    t_keyboard_language k
  WHERE
    k.language_id IN (SELECT id FROM languages)
  ORDER BY
    k.keyboard_id;
END;

/*
 sp_country_search
*/

DROP PROCEDURE IF EXISTS sp_country_search;

CREATE PROCEDURE sp_country_search (
 IN prmSearchRegEx VARCHAR(250),
 IN prmSearchPlain VARCHAR(250),
 IN prmMatchType INT
)
READS SQL DATA
BEGIN
  DROP TABLE IF EXISTS countries;
  DROP TABLE IF EXISTS languages;
  
  CREATE TEMPORARY TABLE countries (
    id CHAR(2),
    name VARCHAR(75),
    area VARCHAR(10)
  ) ENGINE=MEMORY;

  CREATE TEMPORARY TABLE languages (
    id VARCHAR(3),
    country_id CHAR(2),
    name VARCHAR(75)
  ) ENGINE=MEMORY;
  
  IF prmMatchType = 0 THEN
    -- Search for code only
    INSERT INTO 
      countries
    SELECT
      ecc.CountryID id,
      ecc.Name name,
      ecc.Area area
    FROM
      t_ethnologue_country_codes ecc
    WHERE
      ecc.CountryID = prmSearchPlain;
  ELSE
    IF prmMatchType = 2 THEN
      -- Search for region only
      INSERT INTO 
        countries
      SELECT
        ecc.CountryID id,
        ecc.Name name,
        ecc.Area area
      FROM
        t_ethnologue_country_codes ecc
      WHERE
        ecc.Area REGEXP prmSearchRegion;
    ELSE -- prmMatchType = 1
      -- Search for name or code
      INSERT INTO 
        countries
      SELECT
        ecc.CountryID,
        ecc.Name,
        ecc.Area
      FROM
        t_ethnologue_country_codes ecc
      WHERE
        ecc.CountryID = prmSearchPlain OR
        ecc.Name REGEXP prmSearchRegex;
    END IF;
  END IF;
  
  -- Languages
  INSERT INTO 
    languages
  SELECT 
    iso6393.CanonicalId,
    elc.CountryID,
    elc.Name
  FROM
    t_iso639_3 iso6393 INNER JOIN
    t_ethnologue_language_codes elc ON iso6393.Id = elc.LangID
  WHERE
    elc.CountryID IN (SELECT id FROM countries)
  ORDER BY
    2;

  SELECT * FROM countries;
  SELECT * FROM languages;
    
  -- Keyboards matching the languages found
  SELECT
    k.language_id,
    k.keyboard_id
  FROM 
    t_keyboard_language k
  WHERE
    k.language_id IN (SELECT id FROM languages)
  ORDER BY
    k.keyboard_id;

  DROP TABLE IF EXISTS countries;
  DROP TABLE IF EXISTS languages;
END;
