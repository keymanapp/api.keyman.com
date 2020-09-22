/*
 sp_keyboard_search_by_language_bcp47_tag: find all keyboards for a given bcp47 base_tag

 TODO: can we merge with the f_keyboard_search patterns?
*/

DROP PROCEDURE IF EXISTS sp_keyboard_search_by_language_bcp47_tag
GO


CREATE PROCEDURE sp_keyboard_search_by_language_bcp47_tag (
  @prmTag NVARCHAR(250),
  @prmPlatform NVARCHAR(32),
  @prmObsolete BIT,
  @prmPageNumber INT,
  @prmPageSize INT
) AS
BEGIN
  DECLARE @varTag NVARCHAR(250)
  SET @varTag = $schema.f_get_canonical_bcp47(@prmTag)

  -- Get total rows for search
  SELECT
    count(*) total_count,
    @varTag 'base_tag'
  FROM
    t_keyboard k
  WHERE
    EXISTS (SELECT * FROM t_keyboard_langtag kl WHERE kl.keyboard_id = k.keyboard_id AND kl.tag = @varTag) AND
    (k.obsolete = 0 or @prmObsolete = 1) AND
    ((@prmPlatform is null) or
    (@prmPlatform = 'android' and k.platform_android > 0) or
    (@prmPlatform = 'ios'     and k.platform_ios > 0) or
    (@prmPlatform = 'linux'   and k.platform_linux > 0) or
    (@prmPlatform = 'macos'   and k.platform_macos > 0) or
    (@prmPlatform = 'web'     and k.platform_web > 0) or
    (@prmPlatform = 'windows' and k.platform_windows > 0))

  -- Result matches
  SELECT
    $schema.f_keyboard_search_bcp47_for_keyboard(k.keyboard_id, @varTag) match_name,
    'language_bcp47_tag' match_type,
    1 match_weight,
    COALESCE(kd.count, 0) download_count, -- missing count record = 0 downloads over last 30 days
    1 * (LOG(COALESCE(kd.count+1, 1))+1) final_weight,
    $schema.f_keyboard_search_bcp47_for_keyboard(k.keyboard_id, @varTag) match_tag,
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
    k.obsolete,
    k.keyboard_info

  FROM
    t_keyboard k left join
    t_keyboard_downloads kd on k.keyboard_id = kd.keyboard_id
  WHERE
    EXISTS (SELECT * FROM t_keyboard_langtag kl WHERE kl.keyboard_id = k.keyboard_id AND kl.tag = @varTag) AND
    (k.obsolete = 0 or @prmObsolete = 1) AND
    ((@prmPlatform is null) or
    (@prmPlatform = 'android' and k.platform_android > 0) or
    (@prmPlatform = 'ios'     and k.platform_ios > 0) or
    (@prmPlatform = 'linux'   and k.platform_linux > 0) or
    (@prmPlatform = 'macos'   and k.platform_macos > 0) or
    (@prmPlatform = 'web'     and k.platform_web > 0) or
    (@prmPlatform = 'windows' and k.platform_windows > 0))
  ORDER BY
    k.obsolete ASC,
    5 DESC, --final_weight
    k.name
  offset
    @prmPageSize * (@prmPageNumber - 1) rows
  fetch next
    @prmPageSize rows only
END
