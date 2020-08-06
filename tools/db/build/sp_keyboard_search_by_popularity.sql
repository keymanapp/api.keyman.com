-- #
-- # sp_keyboard_search_by_popularity
-- #

DROP PROCEDURE IF EXISTS sp_keyboard_search_by_popularity;
GO

CREATE PROCEDURE sp_keyboard_search_by_popularity
  @prmPlatform nvarchar(32),
  @prmPageNumber int,
  @prmPageSize int
AS
BEGIN
  SET NOCOUNT ON;

  declare @tt_keyboard tt_keyboard_search_keyboard

  declare @weight_langtag INT = 10
  declare @weight_country INT = 1
  declare @weight_script INT = 5
  declare @weight_keyboard INT = 30
  declare @weight_keyboard_id INT = 25
  declare @weight_keyboard_description INT = 5
  declare @weight_factor_exact_match INT = 3

  -- #
  -- # Search across keyboards
  -- #

  insert @tt_keyboard select * from f_keyboard_search_by_popularity(@prmPlatform, @weight_factor_exact_match, @weight_keyboard)

  -- #
  -- # Build final list of results; two result sets: summary data and current page result
  -- #

  SET NOCOUNT OFF;

  select * from f_keyboard_search_statistics(@prmPageSize, @prmPageNumber, 0, @tt_keyboard)
  select * from f_keyboard_search_results(@prmPageSize, @prmPageNumber, 0, @tt_keyboard)
END
GO