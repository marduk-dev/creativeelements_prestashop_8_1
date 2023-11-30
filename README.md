# CreativeElements in PrestaShop 8.1

All credits to WebshopWorks.com, this override is just to address CreativeElements issues in the 8.1 PrestaShop release.

## How to make it work?

Please copy `creativeelements.php` to your `_PS_PATH_/override/modules/creativeelements` directory and it should work.

## What are differences?

There are just two changes:
1. Method `hookOverrideLayoutTemplate` is being called differently than in previous PrestaShop versions, the page is not yet created when it is called for the first time - and CreativeElements simply fails.
2. PrestaShop replaced `instance` variable with `instance()` method and this is called deep in `hookOverrideLayoutTemplate` (thus we need to overload entire method).
