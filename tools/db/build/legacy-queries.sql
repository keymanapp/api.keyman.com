DROP PROCEDURE IF EXISTS sp_legacy10_keyboards_for_language;
GO

CREATE PROCEDURE sp_legacy10_keyboards_for_language (
 @bcp47 NVARCHAR(256),
 @minKeymanVersion1 INT,
 @minKeymanVersion2 INT
) AS
BEGIN
  SELECT
    k.keyboard_id,
    k.version,
    k.platform_android,
    k.platform_ios,
    k.platform_windows,
    k.platform_web,
    k.last_modified,
    k.js_filename,
    k.js_filesize,
    k.is_rtl,
    k.name,
    k.legacy_id,
    k.keyboard_info
  FROM
    t_keyboard k
  WHERE
    -- (k.deprecated = 0 OR bcp47 IS NOT NULL) AND list deprecated keyboards last
    k.js_filename IS NOT NULL AND
    (
      k.min_keyman_version_1 < @minKeymanVersion1 OR
      (k.min_keyman_version_1 = @minKeymanVersion1 AND k.min_keyman_version_2 <= @minKeymanVersion2)
    ) AND
    EXISTS(SELECT * FROM t_keyboard_language kl WHERE kl.keyboard_id = k.keyboard_id AND kl.bcp47 = @bcp47)
  ORDER BY
    k.deprecated ASC,
    k.keyboard_id;
END;
GO

DROP PROCEDURE IF EXISTS sp_legacy10_keyboard_languages;
GO

CREATE PROCEDURE sp_legacy10_keyboard_languages (
 @keyboard_id NVARCHAR(256)
) AS
BEGIN
  SELECT
    kl.bcp47,
    elc.CountryID region_id, -- maps to t_region
    ec.Area legacy_region,   -- roughly, continent name, mapped in the php layer to a legacy numeric identifier
    COALESCE(elc.Name, li.Ref_Name, kl.bcp47) name  -- this name is inverted, e.g. "Arabic, Standard", not "Standard Arabic".
      -- Some names are missing from Ethnologue, e.g. Ancient and macrolanguages, for those we have no region and
      -- so we will end up with 'world'
  FROM
    t_keyboard_language kl LEFT JOIN
    -- This join is imperfect because we are ignoring the region part of the bcp47 tag, for now. We should join across that
    -- and then prioritise that region.
    t_iso639_3 li ON li.CanonicalId = kl.language_id LEFT JOIN -- Join across normalised language id
    t_ethnologue_language_codes elc ON li.Id = elc.LangID LEFT JOIN
    t_ethnologue_country_codes ec ON elc.CountryID = ec.CountryID
  WHERE
    kl.keyboard_id = @keyboard_id
  ORDER BY
    COALESCE(elc.Name, li.Ref_Name, kl.bcp47),
    kl.bcp47;
END;
GO

DROP PROCEDURE IF EXISTS sp_legacy10_all_keyboard_languages;
GO

CREATE PROCEDURE sp_legacy10_all_keyboard_languages (
 @keyboard_id NVARCHAR(256)
) AS
BEGIN
  SELECT
    kl.keyboard_id,
    kl.bcp47,
    elc.CountryID region_id, -- maps to t_region
    ec.Area legacy_region,   -- roughly, continent name, mapped in the php layer to a legacy numeric identifier
    COALESCE(elc.Name, li.Ref_Name, kl.bcp47) name  -- this name is inverted, e.g. "Arabic, Standard", not "Standard Arabic".
      -- Some names are missing from Ethnologue, e.g. Ancient and macrolanguages, for those we have no region and
      -- so we will end up with 'world'
  FROM
    t_keyboard_language kl INNER JOIN
    t_keyboard k ON kl.keyboard_id = k.keyboard_id LEFT JOIN
    -- This join is imperfect because we are ignoring the region part of the bcp47 tag, for now. We should join across that
    -- and then prioritise that region.
    t_iso639_3 li ON li.CanonicalId = kl.language_id LEFT JOIN -- Join across normalised language id
    t_ethnologue_language_codes elc ON li.Id = elc.LangID LEFT JOIN
    t_ethnologue_country_codes ec ON elc.CountryID = ec.CountryID
  WHERE
    (kl.keyboard_id = @keyboard_id OR @keyboard_id IS NULL) AND
    (k.deprecated = 0 OR @keyboard_id IS NOT NULL)
  ORDER BY
    k.deprecated ASC,
    kl.keyboard_id,
    COALESCE(elc.Name, li.Ref_Name, kl.bcp47),
    kl.bcp47;
END;
GO

DROP PROCEDURE IF EXISTS sp_legacy10_languages_for_keyboards_for_language;
GO

CREATE PROCEDURE sp_legacy10_languages_for_keyboards_for_language (
 @bcp47 NVARCHAR(256)
) AS
BEGIN
  SELECT
    kl.keyboard_id,
    kl.bcp47,
    elc.CountryID region_id, -- maps to t_region
    ec.Area legacy_region,   -- roughly, continent name, mapped in the php layer to a legacy numeric identifier
    COALESCE(elc.Name, li.Ref_Name, kl.bcp47) name  -- this name is inverted, e.g. "Arabic, Standard", not "Standard Arabic".
      -- Some names are missing from Ethnologue, e.g. Ancient and macrolanguages, for those we have no region and
      -- so we will end up with 'world'
  FROM
    t_keyboard_language kl LEFT JOIN
    -- This join is imperfect because we are ignoring the region part of the bcp47 tag, for now. We should join across that
    -- and then prioritise that region.
    t_iso639_3 li ON li.CanonicalId = kl.language_id LEFT JOIN -- Join across normalised language id
    t_ethnologue_language_codes elc ON li.Id = elc.LangID LEFT JOIN
    t_ethnologue_country_codes ec ON elc.CountryID = ec.CountryID
  WHERE
    kl.keyboard_id IN (
      SELECT
        k.keyboard_id
      FROM
        t_keyboard k
      WHERE
        -- k.deprecated = 0 AND ... don't exclude deprecated keyboards here
        k.js_filename IS NOT NULL AND
        EXISTS(SELECT * FROM t_keyboard_language kl WHERE kl.keyboard_id = k.keyboard_id AND kl.bcp47 = @bcp47)
    )
  ORDER BY
    kl.keyboard_id,
    COALESCE(elc.Name, li.Ref_Name, kl.bcp47),
    kl.bcp47;
END;
GO


DROP PROCEDURE IF EXISTS sp_legacy10_keyboard;
GO

CREATE PROCEDURE sp_legacy10_keyboard (
 @keyboard_id NVARCHAR(256),
 @minKeymanVersion1 INT,
 @minKeymanVersion2 INT
) AS
BEGIN
  SELECT
    k.keyboard_id,
    k.version,
    k.platform_android,
    k.platform_ios,
    k.platform_windows,
    k.platform_web,
    k.last_modified,
    k.js_filename,
    k.js_filesize,
    k.is_rtl,
    k.name,
    k.legacy_id,
    k.keyboard_info
  FROM
    t_keyboard k
  WHERE
    (k.deprecated = 0 OR keyboard_id IS NOT NULL) AND
    k.js_filename IS NOT NULL AND
    (
      k.min_keyman_version_1 < @minKeymanVersion1 OR
      (k.min_keyman_version_1 = @minKeymanVersion1 AND k.min_keyman_version_2 <= @minKeymanVersion2)
    ) AND
    (k.keyboard_id = @keyboard_id OR @keyboard_id IS NULL)
  ORDER BY
    k.deprecated ASC,
    k.keyboard_id;
END;
GO

DROP PROCEDURE IF EXISTS sp_legacy10_language;
GO

CREATE PROCEDURE sp_legacy10_language (
 @bcp47 NVARCHAR(256),
 @minKeymanVersion1 INT,
 @minKeymanVersion2 INT
) AS
BEGIN
  SELECT
    k.keyboard_id,
    k.version,
    k.platform_android,
    k.platform_ios,
    k.platform_windows,
    k.platform_web,
    k.last_modified,
    k.js_filename,
    k.js_filesize,
    k.is_rtl,
    k.name,
    k.legacy_id,
    k.keyboard_info,
    kl.bcp47,
    elc.CountryID region_id, -- see notes from sp_legacy10_keyboard_languages
    ec.Area legacy_region,   -- see notes from sp_legacy10_keyboard_languages
    COALESCE(
      elc.Name, li.Ref_Name, kl.bcp47) +
    COALESCE(
      ' (' + s.name + ', ' + r.name + ')',
      ' (' + r.name + ')',
      ' (' + s.name + ')',
      '')
    language_name            -- see notes from sp_legacy10_keyboard_languages
  FROM
    t_keyboard k INNER JOIN
    t_keyboard_language kl ON k.keyboard_id = kl.keyboard_id LEFT JOIN
    -- This join is imperfect because we are ignoring the region part of the bcp47 tag, for now. We should join across that
    -- and then prioritise that region.
    t_iso639_3 li ON li.CanonicalId = kl.language_id  LEFT JOIN -- Join across normalised language id
    t_ethnologue_language_codes elc ON li.Id = elc.LangID LEFT JOIN
    t_ethnologue_country_codes ec ON elc.CountryID = ec.CountryID LEFT JOIN
    t_region r ON kl.region_id = r.region_id LEFT JOIN
    t_script s ON kl.script_id = s.script_id
  WHERE
    (k.deprecated = 0 OR @bcp47 IS NOT NULL) AND
    (kl.bcp47 = @bcp47 OR @bcp47 IS NULL) AND
    (
      k.min_keyman_version_1 < @minKeymanVersion1 OR
      (k.min_keyman_version_1 = @minKeymanVersion1 AND k.min_keyman_version_2 <= @minKeymanVersion2)
    ) AND
    (k.js_filename IS NOT NULL)
  ORDER BY
    k.deprecated ASC,
    COALESCE(elc.Name, li.Ref_Name, kl.bcp47),
    kl.bcp47,
    k.keyboard_id;
END;
GO

DROP PROCEDURE IF EXISTS sp_legacy10_language_0;
GO

CREATE PROCEDURE sp_legacy10_language_0 (
 @bcp47 NVARCHAR(256),
 @minKeymanVersion1 INT,
 @minKeymanVersion2 INT
) AS
BEGIN
  SELECT
    kl.keyboard_id,
    kl.bcp47,
    elc.CountryID region_id, -- see notes from sp_legacy10_keyboard_languages
    ec.Area legacy_region,   -- see notes from sp_legacy10_keyboard_languages
    COALESCE(
      elc.Name, li.Ref_Name, kl.bcp47) +
    COALESCE(
      ' (' + s.name + ', ' + r.name + ')',
      ' (' + r.name + ')',
      ' (' + s.name + ')',
      '')
    language_name            -- see notes from sp_legacy10_keyboard_languages
  FROM
    t_keyboard k INNER JOIN
    t_keyboard_language kl ON k.keyboard_id = kl.keyboard_id LEFT JOIN
    -- This join is imperfect because we are ignoring the region part of the bcp47 tag, for now. We should join across that
    -- and then prioritise that region.
    t_iso639_3 li ON li.CanonicalId = kl.language_id  LEFT JOIN -- Join across normalised language id
    t_ethnologue_language_codes elc ON li.Id = elc.LangID LEFT JOIN
    t_ethnologue_country_codes ec ON elc.CountryID = ec.CountryID LEFT JOIN
    t_region r ON kl.region_id = r.region_id LEFT JOIN
    t_script s ON kl.script_id = s.script_id
  WHERE
    (k.deprecated = 0 OR @bcp47 IS NOT NULL) AND
    (kl.bcp47 = @bcp47 OR @bcp47 IS NULL) AND
    (
      k.min_keyman_version_1 < @minKeymanVersion1 OR
      (k.min_keyman_version_1 = @minKeymanVersion1 AND k.min_keyman_version_2 <= @minKeymanVersion2)
    ) AND
    (k.js_filename IS NOT NULL)
  ORDER BY
    k.deprecated ASC,
    COALESCE(elc.Name, li.Ref_Name, kl.bcp47),
    kl.bcp47,
    k.keyboard_id;
END;
GO

DROP PROCEDURE IF EXISTS sp_legacy10_language_0_keyboards;
GO

CREATE PROCEDURE sp_legacy10_language_0_keyboards (
 @bcp47 NVARCHAR(256),
 @minKeymanVersion1 INT,
 @minKeymanVersion2 INT
) AS
BEGIN
  SELECT
    k.keyboard_id,
    k.version,
    k.platform_android,
    k.platform_ios,
    k.platform_windows,
    k.platform_web,
    k.last_modified,
    k.js_filename,
    k.js_filesize,
    k.is_rtl,
    k.name,
    k.legacy_id,
    k.keyboard_info
  FROM
    t_keyboard k
  WHERE
    (k.deprecated = 0 OR @bcp47 IS NOT NULL) AND
    k.keyboard_id IN (
      SELECT
        kl.keyboard_id
      FROM
        t_keyboard_language kl
      WHERE
        (kl.bcp47 = @bcp47 OR @bcp47 IS NULL)
    ) AND
    (
      k.min_keyman_version_1 < @minKeymanVersion1 OR
      (k.min_keyman_version_1 = @minKeymanVersion1 AND k.min_keyman_version_2 <= @minKeymanVersion2)
    ) AND
    (k.js_filename IS NOT NULL)
  ORDER BY
    k.deprecated ASC,
    k.keyboard_id;
END;