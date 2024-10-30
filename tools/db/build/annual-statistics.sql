/*
 * Keyman is copyright (C) SIL Global. MIT License.
 *
 * Basic annual statistics for SIL reports -- SQL Query
 */

/*
 Some rough notes:

  * We have, in our cloud database <AllKeyboards> keyboards
  * Of these, <CurrentKeyboards> are listed as "not obsolete". Obsolete
    keyboards are keyboards which have been renamed and for which there is a new
    version, or keyboards which are non-Unicode.
  * We updated <ModifiedKeyboards> keyboards from startDate-endDate, according
    to each keyboard's last update date. This can be anything from a metadata
    change to significant keyboard rewrite.
  * We list <LanguageCount> languages today, for <LanguageKeyboardPairs>
    language:keyboard pairs. Many keyboards support more than one language.
  * We list <LexicalModelCount> lexical models.
  * <RawKeyboardDownloadCount> lists the total number of downloads of keyboards
    through keyman.com over the last year. This does not match the number of
    users or keyboards in use, but gives a rough volume.
*/

DROP PROCEDURE IF EXISTS sp_annual_statistics;
GO

CREATE PROCEDURE sp_annual_statistics (
  @prmStartDate DATE,
  @prmEndDate DATE
) AS

SELECT
  (select count(*) from k0.t_keyboard) AS AllKeyboards,
  (select count(*) from k0.t_keyboard where obsolete = 0) AS CurrentKeyboards,
  (select count(*) from k0.t_keyboard where last_modified >= @prmStartDate and last_modified < @prmEndDate) AS ModifiedKeyboards,
  (select count(distinct tag) from k0.t_keyboard_langtag) AS LanguageCount,
  (select count(*) from k0.t_keyboard_langtag) AS LanguageKeyboardPairs,
  (select count(*) from k0.t_model) AS LexicalModelCount,
  (select sum(count) from kstats.t_keyboard_downloads WHERE statdate >= @prmStartDate AND statdate < @prmEndDate) RawKeyboardDownloadCount
