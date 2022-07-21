# TYPO3 extension jfmulticontent

I have adapted the former version of the jfmulticontent extension for the TYPO3 versions 9.5 - 11.5.

## Flexform Migration

The upgrade script must be executed once in the Install Tool if visible. All the flexform sheet names must have a leading 's_'. This transformation is done in the update script. The extension typo3db_legacy must be installed fo it to work. When upgrading from older versions of this extension some more modifications are required, which have been only available from the Extension Manager UPDATE script of the previous versions.

## Third Party Extensions

The extension t3jquery seems not to exist any more and TYPO3 10 will provide jQuery for extensions. Any support for t3jquery shall therefore be dropped in a later version.

Now you can use the extension lib_jquery. In this case its jquery-x.min.js library will be used automatically.

The extension patchlayout is recommended to be installed in order to allow a column number -1.

## Contributions

Any contributions are welcome. Just create an issue or even write a pull request.


## TSConfig Requirement

You must set TSConfig like this:
### example:
```
TCEFORM.tt_content.tx_jfmulticontent_contents.PAGE_TSCONFIG_ID = 17

```

The starting point page record for the plugin does not exist any more in TYPO3.

## Sponsors

The Booklet has been sponsored by made in nature WERBEAGENTUR (https://www.made-in-nature.de/leistungen/typo3) .

