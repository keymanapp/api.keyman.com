# keyboard_info

* **keyboard_info.source.json**
* **keyboard_info.distribution.json**

Documentation at https://help.keyman.com/developer/cloud/keyboard_info

New versions should be deployed to
- **keymanapp/keyman/windows/src/global/inst/data/keyboard_info**
- **keymanapp/keyboards/tools**
- **keymanapp/keyboards-starter/tools**

# .keyboard_info version history

## 2018-11-26 1.0.5 stable
* Add deprecated field - true if the keyboard is deprecated (generated at deployment time).

## 2018-11-26 1.0.4 stable
* Add helpLink field - a link to a keyboard's help page on help.keyman.com if it exists.

## 2018-02-12 1.0.3 stable
* Renamed minKeymanDesktopVersion to minKeymanVersion to clarify that this version information applies to all platforms.

## 2018-02-10 1.0.2 stable
* Add dictionary to platform support choices. Fixed default for platform to 'none'.

## 2018-01-31 1.0.1 stable
* Add file sizes, isRTL, sourcePath fields so we can supply these to the legacy KeymanWeb Cloud API endpoints.
* Remove references to .kmx being a valid package format.

## 2017-09-14 1.0 stable
* Initial version

------------------------------------------------------------

# search

* search.json

Documentation at https://help.keyman.com/developer/cloud/search

# search version history

## 2019-12-13 1.0.2
* Added deprecated field for keyboard_info

## 2018-02-06 1.0.1
* Added SearchCountry definition.

## 2017-11-07 1.0 beta
* Initial version

------------------------------------------------------------

# keyboard_json

* keyboard_json.json

Note: this format is deprecated as of Keyman 10.0.

Documentation at https://help.keyman.com/developer/9.0/guides/distribute/mobile-apps

# keyboard_json version history

## 2017-11-23 1.0 beta
* Initial version

------------------------------------------------------------

# package

* package.json

Documentation at https://help.keyman.com/developer/10.0/reference/file-types/metadata

# package.json version history

## 2019-01-31 1.1.0
* Add lexicalModels properties (note: `version` is optional and currently unused)

## 2018-02-13 1.0.2
* Add rtl property for keyboard layouts

## 2018-01-22 1.0.1
* Remove id field as it is derived from the filename anyway

## 2017-11-30 1.0 beta
* Initial version

------------------------------------------------------------

# version

* version.json

Documentation at https://help.keyman.com/developer/cloud/version/2.0

## version.json version history

## 2021-09-30 2.0.2
* Add 'all' semantics

## 2019-10-23 2.0.1 alpha
* Add linux platform

## 2018-03-07 2.0 beta
* Initial version

------------------------------------------------------------

# keymanweb-cloud-api

* keymanweb-cloud-api-1.0.json
* keymanweb-cloud-api-2.0.json
* keymanweb-cloud-api-3.0.json
* keymanweb-cloud-api-4.0.json

Formal specification of legacy KeymanWeb cloud API endpoints at https://r.keymanweb.com/api/

Documentation at https://help.keyman.com/developer/cloud/

# keyman-web-cloud-api version history

## 2018-01-31
* Created schema files for existing json api endpoints

------------------------------------------------------------

# model_info

* model_info.source.json
* model_info.distribution.json

Documentation at https://help.keyman.com/developer/cloud/model_info

## 2019-01-31 1.0 beta
* Initial version, seeded from .keyboard_info specification

## 2020-09-21 1.0.1
* Relaxed the URL definitions in the schema so extension is no longer tested

------------------------------------------------------------

# visualkeyboard

* visualkeyboard.dtd

XML Document Type Defintion for the .kvks file format. Previously, this was
at http://tavultesoft.com/keymandev/visualkeyboard.dtd.

## 2019-08-20
* Moved to api.keyman.com (redirect on tavultesoft.com)

------------------------------------------------------------

# package-version

* package-version.json

Documentation at https://help.keyman.com/developer/cloud/package-version

## 2020-04-30 1.0
* Initial version 1.0 (alpha)

## 2020-09-21 1.0.1

* Fixed bugs with missing .kmp files
* Added deprecation links
* Updated .kmp URLs to use keyman.com/go/package format

------------------------------------------------------------

# windows-update

* windows-update.json

## 2020-07-23 1.0
* Initial version 1.0 (alpha)

## 2020-11-17 1.0.1
* Relaxed `file` to `optional-file` to allow for responses without an update available.

------------------------------------------------------------

# developer-update

* developer-update.json

## 2020-11-17 1.0
* Initial version 1.0 (alpha)

------------------------------------------------------------

# kps

* kps.xsd

## 2021-07-19 7.0
* Initial version 7.0
