# Release Guide

This guide covers all the steps required to release a new version for all packages. The order of packages must be followed but packages that do not have a new version to be released can be skipped (keeping the rest in order).

## 1. htmlburger/wpemerge

1. Edit config.php and update WPEMERGE_VERSION.
2. Create a new release: https://github.com/htmlburger/wpemerge/releases/new

## 2. htmlburger/wpemerge-cli

1. Update and commit `composer.json` with the latest version of this package (otherwise packagist.org will not update).
2. Create a new release: https://github.com/htmlburger/wpemerge-cli/releases/new

## 3. htmlburger/wpemerge-blade

1. Update and commit `composer.json` with the latest `htmlburger/wpemerge` version requirement.
2. Create a new release: https://github.com/htmlburger/wpemerge-blade/releases/new

## 4. htmlburger/wpemerge-twig

1. Update and commit `composer.json` with the latest `htmlburger/wpemerge` version requirement.
2. Create a new release: https://github.com/htmlburger/wpemerge-twig/releases/new

## 5. htmlburger/wpemerge-app-core

1. Update and commit `composer.json` with the latest `htmlburger/wpemerge` version requirement.
2. Create a new release: https://github.com/htmlburger/wpemerge-app-core/releases/new

## 6. htmlburger/wpemerge-theme

1. Run `yarn i18n`.
2. Update and commit `composer.json` with the latest version requirements for:
    - `htmlburger/wpemerge`
    - `htmlburger/wpemerge-app-core`
    - `htmlburger/wpemerge-cli`
3. Update and commit `composer.json` with the latest version of this package (otherwise packagist.org will not update).
4. Update call to `myapp_should_load_wpemerge()` with the latest minimum version required.
5. Create a new release: https://github.com/htmlburger/wpemerge-theme/releases/new

## 7. htmlburger/wpemerge-plugin

1. Run `yarn i18n`.
2. Update and commit `composer.json` with the latest version requirements for:
    - `htmlburger/wpemerge`
    - `htmlburger/wpemerge-app-core`
    - `htmlburger/wpemerge-cli`
3. Update and commit `composer.json` with the latest version of this package (otherwise packagist.org will not update).
4. Update call to `myapp_should_load_wpemerge()` with the latest minimum version required.
5. Create a new release: https://github.com/htmlburger/wpemerge-plugin/releases/new
