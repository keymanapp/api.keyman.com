-- #
-- # sp_keyboard_search_debug
-- #

DROP PROCEDURE IF EXISTS sp_keyboard_search_debug;
GO

CREATE PROCEDURE sp_keyboard_search_debug
  @prmSearchText nvarchar(250),
  @prmIDSearchText nvarchar(250), -- should be ascii (ideally, id only /[a-z][a-z0-9_]*/)
  @prmPlatform nvarchar(32),
  @prmObsolete bit,
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

  -- select * from f_keyboard_search_statistics(@prmPageSize, @prmPageNumber, @prmObsolete, @tt_keyboard)
  -- select * from f_keyboard_search_results(@prmPageSize, @prmPageNumber, @prmObsolete, @tt_keyboard)
  select * from @tt_keyboard
  select * from @tt_langtag
END
GO

