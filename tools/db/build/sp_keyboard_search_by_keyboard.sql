DROP PROCEDURE IF EXISTS sp_keyboard_search_by_keyboard;
GO

CREATE PROCEDURE sp_keyboard_search_by_keyboard
  @prmSearchText nvarchar(250),
  @prmIDSearchText nvarchar(250), -- should be ascii (ideally, id only /[a-z][a-z0-9_]*/)
  @prmPlatform nvarchar(32),
  @prmPageNumber int,
  @prmPageSize int
AS
BEGIN
  SET NOCOUNT ON;

  declare @tt_keyboard tt_keyboard_search_keyboard

  declare @q NVARCHAR(131) = '"'+@prmSearchText+'*"'
  declare @likeid NVARCHAR(385) = CASE WHEN @prmIDSearchText='' THEN '' ELSE REPLACE(@prmIDSearchText, '_', '[_]')+'%' END

  declare @weight_keyboard INT = 30
  declare @weight_keyboard_id INT = 25
  declare @weight_keyboard_description INT = 5
  declare @weight_factor_exact_match INT = 3

  -- #
  -- # Search across keyboards
  -- #

  insert @tt_keyboard select * from f_keyboard_search(@prmSearchText, @q, @prmPlatform, @weight_factor_exact_match, @weight_keyboard)
  insert @tt_keyboard select * from f_keyboard_search_by_id(@prmSearchText, @likeid, @prmPlatform, @weight_factor_exact_match, @weight_keyboard_id)
  insert @tt_keyboard select * from f_keyboard_search_by_description(@prmSearchText, @q, @prmPlatform, @weight_factor_exact_match, @weight_keyboard_description)

  -- #
  -- # Build final list of results; two result sets: summary data and current page result
  -- #

  SET NOCOUNT OFF;

  select * from f_keyboard_search_statistics(@prmPageSize, @prmPageNumber, @tt_keyboard)
  select * from f_keyboard_search_results(@prmPageSize, @prmPageNumber, @tt_keyboard)
END
GO