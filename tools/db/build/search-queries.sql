DROP FUNCTION IF EXISTS f_get_canonical_bcp47;
GO

CREATE FUNCTION f_get_canonical_bcp47 (
  @prmTag NVARCHAR(250)
) RETURNS NVARCHAR(250) AS
BEGIN
  -- Get canonical tag if it's in our mapping
  DECLARE @varTag NVARCHAR(250)
  SELECT TOP 1 @varTag = base_tag FROM t_langtag_tag t WHERE t.tag = @prmTag

  -- It wasn't a known mapping, so lookup iso639-3
  IF @varTag IS NULL
    SELECT TOP 1 @varTag = tag FROM t_langtag t WHERE t.iso639_3 = @prmTag

  IF @varTag IS NULL
    -- Try and canonicalize the language subtag, assuming they've passed ISO639-3 tag
    SELECT TOP 1 @varTag = tag FROM t_langtag t WHERE t.iso639_3 = @prmTag

  IF @varTag IS NULL
    -- Give up, return the original tag. Won't have much luck in most searches though.
    SET @varTag = @prmTag

  RETURN @varTag
END
GO
