/*
 sp_keyboard_search
*/

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
  declare @q NVARCHAR(131) = '"'+@name+'*"'

  declare @likeid NVARCHAR(385) = CASE WHEN @prmIDSearchText='' THEN '' ELSE REPLACE(@prmIDSearchText, '_', '[_]')+'%' END

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
  declare @matchtype_keyboard_id INT = 5

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

  -- #
  -- # Search across keyboard id (LIKE match only)
  -- #

  insert
    #tt_keyboard
  select
    k.keyboard_id,
    k.name,
    CASE
      WHEN k.keyboard_id = @name THEN @weight_factor_exact_match -- 3x factor for exact match
      ELSE 1
    END * @weight_keyboard,
    k.keyboard_id,
    @matchtype_keyboard_id
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
  -- # Build final list of results; two result sets: summary data and current page result
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
