CREATE INDEX ix_language_index_name ON t_language_index (Name, language_id);

CREATE INDEX ix_iso639_3_part1 ON t_iso639_3 (Part1, Id);
CREATE INDEX ix_iso639_3_id ON t_iso639_3 (Id);
CREATE INDEX ix_iso639_3_names_print_name ON t_iso639_3_names (Print_Name, Id);
CREATE INDEX ix_iso639_3_id_part1_ref_name ON t_iso639_3 (Id, Part1, Ref_Name);
CREATE INDEX ix_iso639_3_part1_id_ref_name ON t_iso639_3 (Part1, Id, Ref_Name);
CREATE INDEX ix_iso639_3_canonicalid_id_ref_name ON t_iso639_3 (CanonicalID, Id, Ref_Name);

CREATE INDEX ix_keyboard_language ON t_keyboard_language (keyboard_id, language_id);

CREATE INDEX ix_ethnologue_language_index_name ON t_ethnologue_language_index (Name, LangID);
CREATE INDEX ix_ethnologue_language_index_langid ON t_ethnologue_language_index (LangID, Name);
CREATE INDEX ix_elc_langid_countryid_name ON t_ethnologue_language_codes (LangID, CountryID, Name);
CREATE INDEX ix_ecc_countryid ON t_ethnologue_country_codes (CountryID);

CREATE INDEX ix_langtag_tag ON t_langtag_tag (tag) INCLUDE (base_tag);
CREATE INDEX ix_langtag_name ON t_langtag_name (name) INCLUDE (tag);

CREATE INDEX ix_langtag_basetag ON t_langtag_tag (base_tag)
CREATE INDEX ix_keyboard_language_id_bcp47 ON t_keyboard_language (keyboard_id, bcp47)
CREATE INDEX ix_langtag_name_id_tag ON t_langtag_name (_id, tag, name, name_kd)

/*
Missing Index Details from SQLQuery2.sql - (local).keyboards_1 (BARROW\mcdurdin (57))
The Query Processor estimates that implementing the following index could improve the query cost by 89.7528%.
*/

CREATE NONCLUSTERED INDEX ix_langtag_region
ON [t_langtag] ([region])
INCLUDE ([name])

/*
Missing Index Details from SQLQuery2.sql - (local).keyboards_1 (BARROW\mcdurdin (57))
The Query Processor estimates that implementing the following index could improve the query cost by 87.7063%.
*/

CREATE NONCLUSTERED INDEX ix_langtag_script
ON [t_langtag] ([script])
INCLUDE ([name])

/*
Missing Index Details from SQLQuery2.sql - (local).keyboards_1 (BARROW\mcdurdin (57))
The Query Processor estimates that implementing the following index could improve the query cost by 80.0976%.
*/

CREATE NONCLUSTERED INDEX ix_keyboard_langtag_tag
ON [t_keyboard_langtag] ([tag])
INCLUDE ([keyboard_id])

