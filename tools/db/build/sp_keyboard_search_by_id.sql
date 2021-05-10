/*
 sp_keyboard_search_by_id: return a single keyboard record, with result set for the sp_keyboard_search pattern

 TODO: can we merge with the f_keyboard_search patterns?
*/

DROP PROCEDURE IF EXISTS sp_keyboard_search_by_id
GO

CREATE PROCEDURE sp_keyboard_search_by_id (
  @prmSearchPlain NVARCHAR(250),
  @prmObsolete BIT
) AS
BEGIN
  SELECT
    k.name match_name,
    'keyboard_id' match_type,
    1 match_weight,
    COALESCE(kd.count, 0) download_count, -- missing count record = 0 downloads over last 30 days
    1 * (LOG(COALESCE(kd.count+1, 1))+1) final_weight,
    null match_tag,

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
    k.keyboard_info,
    kdt.count total_download_count

  FROM
    t_keyboard k left join
    v_keyboard_downloads_month kd on k.keyboard_id = kd.keyboard_id left join
    v_keyboard_downloads_total kdt on k.keyboard_id = kdt.keyboard_id
  WHERE
    k.keyboard_id LIKE @prmSearchPlain+'%' AND
    (k.obsolete = 0 or @prmObsolete = 1)
  ORDER BY
    k.obsolete ASC, -- deprecated keyboards always last
    5 DESC, -- order by final_weight descending
    k.name ASC -- fallback on identical weight
END
