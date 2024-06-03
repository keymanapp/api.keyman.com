--
-- Deprecated keyboards and models should be flagged as such in the t_keyboard/t_model data
--

update t_keyboard
  set deprecated = 1
  where exists (select * from t_keyboard_related kr where kr.related_keyboard_id = t_keyboard.keyboard_id and kr.deprecates = 1);

update t_model
  set deprecated = 1
  where exists (select * from t_model_related mr where mr.related_model_id = t_model.model_id and mr.deprecates = 1);

--
-- Any keyboard that has been replaced by another one, or is not Unicode, is marked as obsolete
--

update t_keyboard
  set obsolete = 1
  where deprecated = 1 or is_unicode = 0
