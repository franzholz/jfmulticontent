# TYPO3 extension jfmulticontent

I have developed the existing jfmulticontent extension for the TYPO3 versions 8.7 - 9.5. TYPO3 10 needs more testing.

## Flexform Migration

The upgrade script must be executed in the Install Tool or in the Extension Manager once. All the flexform sheet names must have a leading 's_'. This transformation is done in the update script. The extension typo3db_legacy must be installed fo it to work. Older versions of this extension require some more modifications, which are also available from the Extension Manager update script.

## Third Party Extennsions

The extension t3jquery seems not to exist any more and TYPO3 10 will provide jQuery for extensions. Any support for t3jquery shall therefore be dropped in a later version.

Now you can use the extension lib_jquery. In this case its jquery-x.min.js library will be used automatically.

TYPO3 8 and later require the extension patchlayout to be installed.

## Contributions

Any contributions are welcome. Just create an issue or write a pull request.


## TSConfig Requirement

In TYPO3 > 7.4 you must set TSConfig like this:
### example:
```
TCEFORM.tt_content.tx_jfmulticontent_contents.PAGE_TSCONFIG_ID = 17

```

The starting point page record in the plugin is not existing any more.

## Sponsors

The Booklet has been sponsored by made in nature WERBEAGENTUR (https://www.made-in-nature.de/typo3-agentur) .

