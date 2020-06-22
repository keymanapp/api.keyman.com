-- #
-- # sp_keyboard_search_by_country_iso3166_code
-- #

DROP PROCEDURE IF EXISTS sp_keyboard_search_by_country_iso3166_code;
GO

CREATE PROCEDURE sp_keyboard_search_by_country_iso3166_code
  @prmSearchText nvarchar(250),
  @prmPlatform nvarchar(32),
  @prmPageNumber int,
  @prmPageSize int
AS
BEGIN
  SET NOCOUNT ON;

  declare @tt_langtag tt_keyboard_search_langtag
  declare @tt_keyboard tt_keyboard_search_keyboard

  declare @weight_region INT = 1
  declare @weight_factor_exact_match INT = 3

  -- #
  -- # Search across language names, region names and country names
  -- #

  insert @tt_langtag select * from f_keyboard_search_langtag_by_country_iso3166_code(@prmSearchText, @weight_region)

  -- #
  -- # Add all langtag, script and region matches to the keyboards temp table, with appropriate weights
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
