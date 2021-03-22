-- #
-- # sp_keyboard_search_alphabetically
-- #

DROP PROCEDURE IF EXISTS sp_keyboard_search_alphabetically;
GO

CREATE PROCEDURE sp_keyboard_search_alphabetically
  @prmPlatform nvarchar(32),
  @prmPageNumber int,
  @prmPageSize int
AS
BEGIN
  SET NOCOUNT ON;

  declare @tt_keyboard tt_keyboard_search_keyboard

  declare @weight_keyboard INT = 1

  -- #
  -- # Search across keyboards
  -- #

  insert @tt_keyboard
    select
      *
    from
      f_keyboard_search_alphabetically(@prmPlatform, @weight_keyboard)

  -- #
  -- # Build final list of results; two result sets: summary data and current page result
  -- #

  SET NOCOUNT OFF;

  select * from f_keyboard_search_statistics(@prmPageSize, @prmPageNumber, 0, @tt_keyboard)
  select * from f_keyboard_search_results_ignore_downloads(@prmPageSize, @prmPageNumber, 0, @tt_keyboard)
END
GO