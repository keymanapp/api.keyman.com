/*
 sp_keyboard_search_10
*/

DROP PROCEDURE IF EXISTS sp_keyboard_search_10;
GO

CREATE PROCEDURE sp_keyboard_search_10 (
  @prmSearchRegex NVARCHAR(250),
  @prmSearchPlain NVARCHAR(250),
  @prmMatchType INT
) AS
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
    k.platform_linux,
    k.deprecated,
    k.keyboard_info

  FROM
    t_keyboard k
  WHERE
    (
      @prmMatchType = 0 AND
      k.keyboard_id = @prmSearchPlain
    ) OR (
      @prmMatchType = 1 AND (
        k.keyboard_id LIKE @prmSearchRegex OR
        k.name LIKE @prmSearchRegex OR
        k.description LIKE @prmSearchRegex
      )
    ) OR (
      @prmMatchType = 2 AND (
        EXISTS (SELECT * FROM t_keyboard_language kl WHERE kl.keyboard_id = k.keyboard_id AND kl.language_id = @prmSearchPlain)
      )
    ) OR (
      @prmMatchType = 3 AND (
        CAST(k.legacy_id AS NVARCHAR) = @prmSearchPlain
      )
    )
  ORDER BY
    k.deprecated ASC,
    k.is_unicode DESC,
    k.name;
