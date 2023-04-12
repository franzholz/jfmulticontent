# TYPO3 extension jfmulticontent

I have recently adapted the extension jfmulticontent for the TYPO3 versions 11.5 - 12.3.

## Flexform Migration

For upgrades from a version < 2.10 the upgrade script must be executed once in the Install Tool.
It is only visible if it is required. All the flexform sheet names must have a leading 's_'. 
This transformation is done in the update script. The extension typo3db_legacy must be installed for the upgrade to work.
When upgrading from very old versions < 2 of this extension some more modifications are required, which have been only available from the Extension Manager UPDATE script of the previous TYPO3 versions. This is not redone by the current upgrade scripts.

## Third Party Extensions

The extension t3jquery seems not to exist any more and TYPO3 10 will provide jQuery for extensions. Any support for t3jquery shall therefore be dropped in a later version.

Now you can use the extension lib_jquery. In this case its jquery-x.min.js library will be used automatically.

The extension patchlayout is recommended to be installed in order to allow the internal column number -1.

## Contributions

Any contributions are welcome. Just create an issue or better write a patch and create a pull request.


## TSConfig Requirement

You must set TSConfig with one page id like this:
### example:
```
TCEFORM.tt_content.tx_jfmulticontent_contents.PAGE_TSCONFIG_ID = 17

```

The starting point page record for the plugin does not exist any more in TYPO3.
A ###PAGE_TSCONFIG_IDLIST### with multiple page ids is not supported at the moment.

### Content Plugin Wizard

If you want to allow a content element wizard for jfmulticontent, then you must use the 
backend page module, edit the page properties resources tab and add the Page TSconfig for jfmulticontent: Multiple Content Element Wizard

## Sponsors

The Booklet has been sponsored by made in nature WERBEAGENTUR (https://www.made-in-nature.de/leistungen/typo3) .

