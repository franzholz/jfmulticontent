TYPO3 extension jfmulticontent
==============================

I have recently adapted the extension jfmulticontent for the TYPO3
versions 11.5 - 13.4.

Flexform Migration
------------------

For upgrades from a version < 2.10 the upgrade script must be executed
once in the Install Tool. It is only visible if this task is still required.
All the flexform sheet names must have a leading ‘s\_’. This transformation is
done in the update script. The extension typo3db_legacy must be
installed for the upgrade to work. When upgrading from a very old version
< 2 of this extension some more modifications are required, which have
been only available from the Extension Manager UPDATE script of older TYPO3 versions. This is not available in the current upgrade scripts.

Third Party Extensions
----------------------

Any support for the TYPO3 extension t3jquery has
been dropped.

You can use the extension lib_jquery. In this case its
jquery-x.min.js library will be used automatically.

The extension patchlayout is recommended to be installed in order to
allow the internal column number -1.

Contributions
-------------

Any contributions are welcome. Just create an issue or better write a
patch and create a pull request.

TSConfig Requirement
--------------------

You must set TSConfig with one page id like this:

example:
^^^^^^^^

   TCEFORM.tt_content.tx_jfmulticontent_contents.PAGE_TSCONFIG_ID = 17

The starting point page record for the plugin does not exist any more in
TYPO3. A ``###PAGE_TSCONFIG_IDLIST###`` run with multiple page ids is not
supported at the moment.

Content Plugin Wizard
~~~~~~~~~~~~~~~~~~~~~

If you want to allow a content element wizard for jfmulticontent, then
you must use the backend page module, edit the page properties resources
tab and add the Page TSconfig for jfmulticontent: Multiple Content
Element Wizard

Sponsors
--------

The Booklet has been sponsored by `made in nature WERBEAGENTUR <https://www.made-in-nature.de/leistungen/typo3>`_ .
