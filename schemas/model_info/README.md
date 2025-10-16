# model_info

* **model_info.schema.json**

Documentation at https://help.keyman.com/developer/cloud/model_info

* Primary version:
  * https://github.com/keymanapp/api.keyman.com/tree/master/schemas/model_info
* Synchronized copies at:
  * https://github.com/keymanapp/keyman/tree/master/common/schemas/model_info

# .model_info version history

## 2023-08-11 2.0 stable
* Removed:
    `.links`
    `.related[].note`
  - Source .model_info files are no longer needed, so source vs distribution
    model_info distinction is removed

## 2020-09-21 1.0.1
* Relaxed the URL definitions in the schema so extension is no longer tested

## 2019-01-31 1.0 beta
* Initial version, seeded from .keyboard_info specification
