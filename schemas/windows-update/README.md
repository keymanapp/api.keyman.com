# windows-update

* windows-update.json is the schema for /windows/17.0/update endpoint

## 2024-02-09 17.0
* Keyboard results use the .keyboard_info 2.0 schema
  `keyboard_info/2.0/keyboard_info.schema.json#/definitions/KeyboardInfo`
  * Removed:
    - `.documentationFilename`
    - `.documentationFileSize`
    - `.legacyId`
    - `.links`
    - `.related[].note`
    - `.languages[].example`
  * Added:
    - `.languages[].examples[]`
  * Modified:
    - `.languages[].font`, `.languages[].oskFont`: `.source` is `[string]`

## 2020-11-17 14.0.1
* Relaxed `file` to `optional-file` to allow for responses without an update available.

## 2020-07-23 14.0
* Initial version 14.0 (alpha)

