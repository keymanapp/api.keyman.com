# search

* search.json

Documentation at https://help.keyman.com/developer/cloud/search

# search version history

## 2024-02-09 3.0
* Search results use the .keyboard_info 2.0 schema
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

## undated 2.0
* Changes not documented at this time

## 2019-12-13 1.0.2
* Added deprecated field for keyboard_info

## 2018-02-06 1.0.1
* Added SearchCountry definition.

## 2017-11-07 1.0 beta
* Initial version
