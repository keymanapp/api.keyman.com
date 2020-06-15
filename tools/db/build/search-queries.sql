/*
 sp_keyboard_search
*/

DROP PROCEDURE IF EXISTS sp_keyboard_search;
GO

CREATE PROCEDURE sp_keyboard_search
  @prmSearchText nvarchar(250),
  @prmPlatform nvarchar(32),
  @prmPageNumber int,
  @prmPageSize int
AS
BEGIN
  SET NOCOUNT ON;

  drop table if exists #tt_langtag
  drop table if exists #tt_region
  drop table if exists #tt_script
  drop table if exists #tt_keyboard

  create table #tt_langtag (tag NVARCHAR(128), name NVARCHAR(128), weight int, match_name NVARCHAR(128), match_type int)
  create table #tt_region (region_id NVARCHAR(3), name NVARCHAR(64))
  create table #tt_script (script_id NVARCHAR(4), name NVARCHAR(64))
  create table #tt_keyboard (keyboard_id NVARCHAR(256), name NVARCHAR(256), weight int, match_name NVARCHAR(256), match_type int)

  declare @PageSize INT = @prmPageSize
  declare @PageNumber INT = @prmPageNumber

  declare @name NVARCHAR(128) = @prmSearchText
  declare @q NVARCHAR(128) = '"'+@name+'*"'

  declare @weight_langtag INT = 10
  declare @weight_region INT = 1
  declare @weight_script INT = 5
  declare @weight_keyboard INT = 30
  declare @weight_keyboard_description INT = 5
  declare @weight_factor_exact_match INT = 3

  declare @matchtype_keyboard INT = 0
  declare @matchtype_keyboard_description INT = 1
  declare @matchtype_langtag INT = 2
  declare @matchtype_script INT = 3
  declare @matchtype_region INT = 4

  -- #
  -- # Search across language names
  -- #

  insert
    #tt_langtag
  select
    tag,
    name,
    case
      when name = @name or name_kd = @name then @weight_factor_exact_match -- exact match gets 3x weight factor
      else 1 -- otherwise same weight
    end * @weight_langtag,
    name,
    @matchtype_langtag
  from
    t_langtag_name
  where
    CONTAINS(name, @q) or
    CONTAINS(name_kd, @q)

  -- #
  -- # Search across regions
  -- #

  -- TODO: merge #tt_region in a subquery once we have stabilised the algorithm

  insert #tt_region
    select region_id, name from t_region where contains(name, @q)

  -- Add region matches to language tag list

  insert
    #tt_langtag
  select
    t.tag, t.name, @weight_region, tr.name, @matchtype_region
  from
    #tt_region tr inner join
    t_langtag t on tr.region_id = t.region

  -- #
  -- # Search across scripts
  -- #

  -- TODO: merge #tt_script in a subquery once we have stabilised the algorithm

  insert
    #tt_script
  select
    script_id,
    name
  from
    t_script
  where
    CONTAINS(name, @q)

  -- Add script matches to language tag list

  insert
    #tt_langtag
  select
    t.tag,
    t.name,
    @weight_script,
    ts.name,
    @matchtype_script
  from
    #tt_script ts inner join
    t_langtag t on ts.script_id = t.script

  -- DEBUG: select * from #tt_langtag order by weight desc

  -- #
  -- # Search across keyboards
  -- #

  insert
    #tt_keyboard
  select
    k.keyboard_id,
    k.name,
    CASE
      WHEN k.name = @name THEN @weight_factor_exact_match -- 3x factor for exact match
      ELSE 1
    END * @weight_keyboard,
    k.name,
    @matchtype_keyboard
  from
    t_keyboard k
  where
    CONTAINS(k.name, @q) and (
    (@prmPlatform is null) or
    (@prmPlatform = 'android' and k.platform_android > 0) or
    (@prmPlatform = 'ios'     and k.platform_ios > 0) or
    (@prmPlatform = 'linux'   and k.platform_linux > 0) or
    (@prmPlatform = 'macos'   and k.platform_macos > 0) or
    (@prmPlatform = 'web'     and k.platform_web > 0) or
    (@prmPlatform = 'windows' and k.platform_windows > 0))

  -- Add keyboards where the term appears in the description, lower weight

  insert
    #tt_keyboard
  select
    k.keyboard_id,
    k.name,
    @weight_keyboard_description,
    NULL, -- if the match_text is null, we know that we should highlight the term in the description in the results
    @matchtype_keyboard_description
  from
    t_keyboard k
  where
    CONTAINS(k.description, @q) and (
    (@prmPlatform is null) or
    (@prmPlatform = 'android' and k.platform_android > 0) or
    (@prmPlatform = 'ios'     and k.platform_ios > 0) or
    (@prmPlatform = 'linux'   and k.platform_linux > 0) or
    (@prmPlatform = 'macos'   and k.platform_macos > 0) or
    (@prmPlatform = 'web'     and k.platform_web > 0) or
    (@prmPlatform = 'windows' and k.platform_windows > 0))

  -- #
  -- # Add all langtag, script and region matches to the keyboards temp table, with appropriate weights
  -- #

  insert
    #tt_keyboard
  select
    k.keyboard_id,
    k.name,
    tlt.weight,
    tlt.match_name,
    tlt.match_type
  from
    t_keyboard k inner join
    t_keyboard_langtag lt on k.keyboard_id = lt.keyboard_id inner join
    #tt_langtag tlt on lt.tag = tlt.tag
  where
    (@prmPlatform is null) or
    (@prmPlatform = 'android' and k.platform_android > 0) or
    (@prmPlatform = 'ios'     and k.platform_ios > 0) or
    (@prmPlatform = 'linux'   and k.platform_linux > 0) or
    (@prmPlatform = 'macos'   and k.platform_macos > 0) or
    (@prmPlatform = 'web'     and k.platform_web > 0) or
    (@prmPlatform = 'windows' and k.platform_windows > 0)

  -- #
  -- # Build final list of langtags
  -- #

  -- DEBUG: select * from #tt_keyboard order by weight desc

  SET NOCOUNT OFF;

  select
    count(distinct keyboard_id) total_count
  from
    #tt_keyboard;

  select
    match_name,
    match_type,
    (select sum(weight) from #tt_keyboard k2 where keyboard_id = temp.keyboard_id) match_weight,

    COALESCE(kd.count, 0) download_count, -- missing count record = 0 downloads over last 30 days

    /*
      Final weight: sum the weights of all matches, then multiply by log(download_count+1)+1 to
      get what looks like a reasonable weight balance. If downloads increase exponentially, we may
      need to adjust the log factor in future.
    */

    (select sum(weight) from #tt_keyboard k2 where keyboard_id = temp.keyboard_id) *
    (LOG(COALESCE(kd.count+1, 1))+1) final_weight,

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
    k.platform_linux,
    k.deprecated,
    k.keyboard_info
  from
    (
      select
        keyboard_id,
        match_name,
        match_type,
        row_number() over(partition by keyboard_id order by weight desc) as roworder
      from #tt_keyboard
    ) temp inner join
    t_keyboard k on temp.keyboard_id = k.keyboard_id left join
    t_keyboard_downloads kd on temp.keyboard_id = kd.keyboard_id
  where
    temp.roworder = 1
  order by
    k.deprecated ASC, -- deprecated keyboards always last
    5 DESC, -- order by final_weight descending
    k.name ASC -- fallback on identical weight
  offset
    @PageSize * (@PageNumber - 1) rows
  fetch next
    @PageSize rows only
END
GO

/*
 sp_keyboard_search_alt: other search types that don't fit the generic algorithm above
*/

DROP PROCEDURE IF EXISTS sp_keyboard_search_alt
GO

CREATE PROCEDURE sp_keyboard_search_alt (
  @prmSearchPlain NVARCHAR(250),
  @prmMatchType INT,
  @prmPageNumber int,
  @prmPageSize int
) AS
BEGIN
  SELECT
    k.name match_name,
    0 match_type,
    1 match_weight,
    COALESCE(kd.count, 0) download_count, -- missing count record = 0 downloads over last 30 days
    1 final_weight,

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
    k.platform_linux,
    k.deprecated,
    k.keyboard_info

  FROM
    t_keyboard k left join
    t_keyboard_downloads kd on k.keyboard_id = kd.keyboard_id
  WHERE
    (
      @prmMatchType = 0/*mtKeyboardId*/ AND
      k.keyboard_id = @prmSearchPlain
    ) OR (
      @prmMatchType = 2/*mtLanguageId*/ AND (
        EXISTS (SELECT * FROM t_keyboard_language kl WHERE kl.keyboard_id = k.keyboard_id AND kl.language_id = @prmSearchPlain)
      )
    ) OR (
      @prmMatchType = 3/*mtLegacyKeyboardId*/ AND (
        CAST(k.legacy_id AS NVARCHAR) = @prmSearchPlain
      )
    )
  ORDER BY
    k.deprecated ASC,
    k.is_unicode DESC,
    k.name
  offset
    @prmPageSize * (@prmPageNumber - 1) rows
  fetch next
    @prmPageSize rows only
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
