/*
 sp_increment_download
 TODO: rename to sp_keyboard_downloads_increment
*/

DROP PROCEDURE IF EXISTS sp_increment_download;
GO

CREATE PROCEDURE sp_increment_download (
  @prmKeyboardID NVARCHAR(260)
) AS
BEGIN
  SET NOCOUNT ON;

  BEGIN TRANSACTION;

  DECLARE @date DATE, @count INT;

  SET @date = CONVERT(date, GETDATE());

  UPDATE kstats.t_keyboard_downloads
    WITH (UPDLOCK, SERIALIZABLE) -- ensure that this statement is atomic with following INSERT
    SET count = count + 1, @count = count + 1
    WHERE keyboard_id = @prmKeyboardID AND statdate = @date;

  IF @@ROWCOUNT = 0
  BEGIN
    INSERT kstats.t_keyboard_downloads (keyboard_id, statdate, count)
      SELECT @prmKeyboardID, @date, 1
    SET @count = 1
  END

  SET NOCOUNT OFF

  SELECT @prmKeyboardID keyboard_id, @count count

  COMMIT TRANSACTION;
END
