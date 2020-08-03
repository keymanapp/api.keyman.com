/*
 sp_keyboard_search
*/

-- These functions must be dropped first because they use the table types
DROP FUNCTION IF EXISTS f_keyboard_search_keyboards_from_langtags;
GO

DROP FUNCTION IF EXISTS f_keyboard_search_results;
GO

DROP FUNCTION IF EXISTS f_keyboard_search_statistics;
GO

DROP TYPE IF EXISTS tt_keyboard_search_langtag
GO

DROP TYPE IF EXISTS tt_keyboard_search_keyboard
GO

CREATE TYPE tt_keyboard_search_langtag AS TABLE (tag NVARCHAR(128), name NVARCHAR(128), weight int, match_name NVARCHAR(128), match_type NVARCHAR(32))
GO

CREATE TYPE tt_keyboard_search_keyboard AS TABLE (keyboard_id NVARCHAR(256), name NVARCHAR(256), weight int, match_name NVARCHAR(256), match_type NVARCHAR(32), match_tag NVARCHAR(128))
GO

-- #
-- # Get keyboard's matching tag for a base BCP 47 tag (it may not be normalized)
-- # We return this tag in the match_tag field for passing to the apps as the
-- # initial language to install for the keyboard.
-- #

DROP FUNCTION IF EXISTS f_keyboard_search_bcp47_for_keyboard;
GO

CREATE FUNCTION f_keyboard_search_bcp47_for_keyboard (
  @keyboard_id NVARCHAR(256),
  @base_tag NVARCHAR(128)
)
RETURNS NVARCHAR(128) AS
BEGIN
  DECLARE @ret NVARCHAR(128)
  select top 1
    @ret = lang.bcp47
  from
    t_keyboard_language lang inner join
    t_langtag_tag tag on lang.bcp47 = tag.tag
  where
    tag.base_tag = @base_tag and
    lang.keyboard_id = @keyboard_id
  RETURN @ret
END
GO

-- #
-- # Search across language names
-- #
DROP FUNCTION IF EXISTS f_keyboard_search_langtag_by_language;
GO

CREATE FUNCTION f_keyboard_search_langtag_by_language (
  @name NVARCHAR(128), @q NVARCHAR(131),
  @weight_factor_exact_match int, @weight_langtag int
) RETURNS
TABLE
AS
  return
  select
    tag as tag,
    name as name,
    case
      when name = @name or name_kd = @name then @weight_factor_exact_match -- exact match gets 3x weight factor
      else 3-log10(len(name)) -- otherwise give slightly greater weight to shorter matches
    end * @weight_langtag as weight,
    name as match_name,
    'language' as match_type
  from
    t_langtag_name
  where
    CONTAINS(name, @q) or
    CONTAINS(name_kd, @q)
GO

-- #
-- # Search across country ISO 3166 code
-- #
DROP FUNCTION IF EXISTS f_keyboard_search_langtag_by_country_iso3166_code;
GO

CREATE FUNCTION f_keyboard_search_langtag_by_country_iso3166_code (
  @q NVARCHAR(131),
  @weight_country int
) RETURNS
TABLE
AS
  return
  select
    t.tag as tag,
    t.name as name,
    @weight_country as weight,
    t.region as match_name,
    'country_iso3166_code' as match_type
  from
    t_langtag t
  where
    t.region = @q
GO

-- #
-- # Search across countries
-- #
DROP FUNCTION IF EXISTS f_keyboard_search_langtag_by_country;
GO

CREATE FUNCTION f_keyboard_search_langtag_by_country (
  @name NVARCHAR(128), @q NVARCHAR(131),
  @weight_factor_exact_match int, @weight_country int
) RETURNS
TABLE
AS
  return
  select
    t.tag as tag,
    t.name as name,
    @weight_country as weight,
    tr.name as match_name,
    'country' as match_type
  from
    (select region_id, name from t_region where contains(name, @q)) tr inner join
    t_langtag t on tr.region_id = t.region
GO

-- #
-- # Search across scripts
-- #
DROP FUNCTION IF EXISTS f_keyboard_search_langtag_by_script;
GO

CREATE FUNCTION f_keyboard_search_langtag_by_script (
  @name NVARCHAR(128), @q NVARCHAR(131),
  @weight_factor_exact_match int, @weight_script int
) RETURNS
TABLE
AS
  return
  select
    t.tag as tag,
    t.name as name,
    @weight_script as weight,
    ts.name as match_name,
    'script' as match_type
  from
    (select script_id, name from t_script where CONTAINS(name, @q)) ts inner join
    t_langtag t on ts.script_id = t.script
GO

DROP FUNCTION IF EXISTS f_keyboard_search_langtag_by_script_iso15924_code;
GO

CREATE FUNCTION f_keyboard_search_langtag_by_script_iso15924_code (
  @name NVARCHAR(128),
  @weight_script int
) RETURNS
TABLE
AS
  return
  select
    t.tag as tag,
    t.name as name,
    @weight_script as weight,
    t.script as match_name,
    'script_iso15924_code' as match_type
  from
    t_langtag t
  where t.script LIKE @name
GO

-- #
-- # Search across keyboards
-- #
DROP FUNCTION IF EXISTS f_keyboard_search;
GO

CREATE FUNCTION f_keyboard_search (
  @name NVARCHAR(128), @q NVARCHAR(131), @prmPlatform nvarchar(32),
  @weight_factor_exact_match int, @weight_keyboard int
) RETURNS
TABLE
AS
  return
  select
    k.keyboard_id as keyboard_id,
    k.name as name,
    CASE
      WHEN k.name = @name THEN @weight_factor_exact_match -- 3x factor for exact match
      ELSE 1
    END * @weight_keyboard as weight,
    k.name as match_name,
    'keyboard' as match_type,
    null as match_tag
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
GO

-- #
-- # Return list of keyboards sorted by popularity
-- #
DROP FUNCTION IF EXISTS f_keyboard_search_by_popularity;
GO

CREATE FUNCTION f_keyboard_search_by_popularity (
  @prmPlatform nvarchar(32),
  @weight_factor_exact_match int, @weight_keyboard int
) RETURNS
TABLE
AS
  return
  select
    k.keyboard_id as keyboard_id,
    k.name as name,
    @weight_keyboard as weight,
    k.name as match_name,
    'keyboard' as match_type,
    null as match_tag
  from
    t_keyboard k
  where
    k.is_unicode = 1 and
    k.deprecated = 0 and
    ((@prmPlatform is null) or
    (@prmPlatform = 'android' and k.platform_android > 0) or
    (@prmPlatform = 'ios'     and k.platform_ios > 0) or
    (@prmPlatform = 'linux'   and k.platform_linux > 0) or
    (@prmPlatform = 'macos'   and k.platform_macos > 0) or
    (@prmPlatform = 'web'     and k.platform_web > 0) or
    (@prmPlatform = 'windows' and k.platform_windows > 0))
GO

-- #
-- # Search across keyboard identifier
-- #
DROP FUNCTION IF EXISTS f_keyboard_search_by_id;
GO

CREATE FUNCTION f_keyboard_search_by_id (
  @name NVARCHAR(128), @likeid NVARCHAR(385), @prmPlatform nvarchar(32),
  @weight_factor_exact_match int, @weight_keyboard_id int
) RETURNS
TABLE
AS
  return
  select
    k.keyboard_id as keyboard_id,
    k.name as name,
    CASE
      WHEN k.keyboard_id = @name THEN @weight_factor_exact_match -- 3x factor for exact match
      ELSE 1
    END * @weight_keyboard_id as weight,
    k.keyboard_id as match_name,
    'keyboard_id' as match_type,
    null as match_tag
  from
    t_keyboard k
  where
    k.keyboard_id LIKE @likeid and (
    (@prmPlatform is null) or
    (@prmPlatform = 'android' and k.platform_android > 0) or
    (@prmPlatform = 'ios'     and k.platform_ios > 0) or
    (@prmPlatform = 'linux'   and k.platform_linux > 0) or
    (@prmPlatform = 'macos'   and k.platform_macos > 0) or
    (@prmPlatform = 'web'     and k.platform_web > 0) or
    (@prmPlatform = 'windows' and k.platform_windows > 0))
GO

-- #
-- # Search across keyboard description
-- #
DROP FUNCTION IF EXISTS f_keyboard_search_by_description;
GO

CREATE FUNCTION f_keyboard_search_by_description (
  @name NVARCHAR(128), @q NVARCHAR(131), @prmPlatform nvarchar(32),
  @weight_factor_exact_match int, @weight_keyboard_description int
) RETURNS
TABLE
AS
  return
  select
    k.keyboard_id as keyboard_id,
    k.name as name,
    @weight_keyboard_description as weight,
    NULL as match_name, -- if the match_text is null, we know that we should highlight the term in the description in the results
    'description' as match_type,
    null as match_tag
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
GO

-- #
-- # Build keyboard search results from langtags
-- #

CREATE FUNCTION f_keyboard_search_keyboards_from_langtags(
  @prmPlatform NVARCHAR(32), @tt_langtags tt_keyboard_search_langtag READONLY)
RETURNS TABLE
AS
  return
  select
    k.keyboard_id,
    k.name,
    tlt.weight,
    tlt.match_name,
    tlt.match_type,
    $schema.f_keyboard_search_bcp47_for_keyboard(k.keyboard_id, tlt.tag) as match_tag
  from
    t_keyboard k inner join
    t_keyboard_langtag lt on k.keyboard_id = lt.keyboard_id inner join
    @tt_langtags tlt on lt.tag = tlt.tag
  where
    (@prmPlatform is null) or
    (@prmPlatform = 'android' and k.platform_android > 0) or
    (@prmPlatform = 'ios'     and k.platform_ios > 0) or
    (@prmPlatform = 'linux'   and k.platform_linux > 0) or
    (@prmPlatform = 'macos'   and k.platform_macos > 0) or
    (@prmPlatform = 'web'     and k.platform_web > 0) or
    (@prmPlatform = 'windows' and k.platform_windows > 0)
GO

-- #
-- # Get keyboard search result statistics
-- #

CREATE FUNCTION f_keyboard_search_statistics(@PageSize INT, @PageNumber INT, @tt_keyboard tt_keyboard_search_keyboard READONLY)
RETURNS TABLE
AS
  return
  select
    count(distinct keyboard_id) total_count,
    @PageSize page_size,
    @PageNumber page_number
  from
    @tt_keyboard;
GO

-- #
-- # Get keyboard search result
-- #

CREATE FUNCTION f_keyboard_search_results(@PageSize INT, @PageNumber INT, @tt_keyboard tt_keyboard_search_keyboard READONLY)
RETURNS TABLE
AS
  return
  select
    match_name,
    match_type,
    (select sum(weight) from @tt_keyboard k2 where keyboard_id = temp.keyboard_id) match_weight,

    COALESCE(kd.count, 0) download_count, -- missing count record = 0 downloads over last 30 days

    /*
      Final weight: sum the weights of all matches, then multiply by log(download_count+1)+1 to
      get what looks like a reasonable weight balance. If downloads increase exponentially, we may
      need to adjust the log factor in future.
    */

    (select sum(weight) from @tt_keyboard k2 where keyboard_id = temp.keyboard_id) *
    (LOG(COALESCE(kd.count+1, 1))+1) final_weight,
    match_tag,

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
        match_tag,
        row_number() over(
          partition by keyboard_id
          order by
            weight desc, -- primary order
            match_name,  -- helps sort shorter matches earlier
            match_type   -- allows consistent results for equal weight+name
          ) as roworder
      from @tt_keyboard
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
GO

-- #
-- # sp_keyboard_search
-- #

DROP PROCEDURE IF EXISTS sp_keyboard_search;
GO

CREATE PROCEDURE sp_keyboard_search
  @prmSearchText nvarchar(250),
  @prmIDSearchText nvarchar(250), -- should be ascii (ideally, id only /[a-z][a-z0-9_]*/)
  @prmPlatform nvarchar(32),
  @prmPageNumber int,
  @prmPageSize int
AS
BEGIN
  SET NOCOUNT ON;

  declare @tt_langtag tt_keyboard_search_langtag
  declare @tt_keyboard tt_keyboard_search_keyboard

  declare @q NVARCHAR(131) = '"'+@prmSearchText+'*"'
  declare @likeid NVARCHAR(385) = CASE WHEN @prmIDSearchText='' THEN '' ELSE REPLACE(@prmIDSearchText, '_', '[_]')+'%' END

  declare @weight_langtag INT = 10
  declare @weight_country INT = 1
  declare @weight_script INT = 5
  declare @weight_keyboard INT = 30
  declare @weight_keyboard_id INT = 25
  declare @weight_keyboard_description INT = 5
  declare @weight_factor_exact_match INT = 3

  -- #
  -- # Search across language names, country names and script names
  -- #

  insert @tt_langtag select * from f_keyboard_search_langtag_by_language(@prmSearchText, @q, @weight_factor_exact_match, @weight_langtag)
  insert @tt_langtag select * from f_keyboard_search_langtag_by_country(@prmSearchText, @q, @weight_factor_exact_match, @weight_country)
  insert @tt_langtag select * from f_keyboard_search_langtag_by_script(@prmSearchText, @q, @weight_factor_exact_match, @weight_script)

  -- #
  -- # Search across keyboards
  -- #

  insert @tt_keyboard select * from f_keyboard_search(@prmSearchText, @q, @prmPlatform, @weight_factor_exact_match, @weight_keyboard)
  insert @tt_keyboard select * from f_keyboard_search_by_id(@prmSearchText, @likeid, @prmPlatform, @weight_factor_exact_match, @weight_keyboard_id)
  insert @tt_keyboard select * from f_keyboard_search_by_description(@prmSearchText, @q, @prmPlatform, @weight_factor_exact_match, @weight_keyboard_description)

  -- #
  -- # Add all langtag, country and script matches to the keyboards temp table, with appropriate weights
  -- #

  insert @tt_keyboard select * from f_keyboard_search_keyboards_from_langtags(@prmPlatform, @tt_langtag)

  -- #
  -- # Build final list of results; two result sets: summary data and current page result
  -- #

  SET NOCOUNT OFF;

  select * from f_keyboard_search_statistics(@prmPageSize, @prmPageNumber, @tt_keyboard)
  select * from f_keyboard_search_results(@prmPageSize, @prmPageNumber, @tt_keyboard)
END
GO

