/*
 sp_app_downloads_increment
*/

DROP PROCEDURE IF EXISTS sp_app_downloads_increment;
GO

CREATE PROCEDURE sp_app_downloads_increment (
  @prmProduct NVARCHAR(64),
  @prmVersion NVARCHAR(64),
  @prmTier NVARCHAR(16)
) AS
BEGIN
  SET NOCOUNT ON;

  BEGIN TRANSACTION;

  DECLARE @date DATE, @count INT;

  SET @date = CONVERT(date, GETDATE());

  UPDATE kstats.t_app_downloads
    WITH (UPDLOCK, SERIALIZABLE) -- ensure that this statement is atomic with following INSERT
    SET count = count + 1, @count = count + 1
    WHERE product = @prmProduct AND version = @prmVersion AND tier = @prmTier AND statdate = @date;

  IF @@ROWCOUNT = 0
  BEGIN
    INSERT kstats.t_app_downloads (product, version, tier, statdate, count)
      SELECT @prmProduct, @prmVersion, @prmTier, @date, 1
    SET @count = 1
  END

  SET NOCOUNT OFF

  SELECT @prmProduct product, @prmVersion version, @prmTier tier, @count count

  COMMIT TRANSACTION;
END
