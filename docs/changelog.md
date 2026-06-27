---
layout: default
title: Changelog
nav_order: 9
---
# Changelog

## [3.8.2] - 2026-06-27
> This is the final planned maintenance release of the legacy 3.x line.

### Security
- Updated `guzzlehttp/psr7` to 2.12.3, fixing CRLF injection and host-confusion advisories (CVE-2026-55766, CVE-2026-49214, CVE-2026-48998).
- Updated `mtdowling/jmespath.php` to 2.9.1, fixing a code-injection advisory (CVE-2026-54133).
- Updated the JavaScript build toolchain dependencies `lodash` (4.18.1) and `qs` (6.15.3) to patched releases.

### Changed
- Updated PHP and JavaScript development dependencies.

## [3.8.1] - 2025-01-24
### Fixed
- Crash when uploading file (#625)

## [3.8.0] - 2025-01-18
### Added
- Support for PHP 8.4
- Add an `app_email` option to the config (#616)
- Exporting ShareX settings now work for the latest version of ShareX 16.1.0 (#617)
- Option to upload file with a custom name (#588)
- Upload from clipboard (#572)

### Changed
- Updated translations.
- Updated dependencies.

### Fixed
- Page count calculation (#586)

## [3.7.0] - 2024-01-14
### Added
- Added support for vanity urls.
- Added KDE integration for linux script.

### Changed
- Updated translations.
- File preview is now clickable to open the file.

### Fixed
- Fixes for LDAP authentication.

## [3.6.3] - 2023-05-27
### Fixed
- Fix LDAP for php >= 8.1

## [3.6.2] - 2023-05-24
### Changed
- Support for PHP 8.2

### Removed
- Azure blob storage driver

## [3.6.1] - 2022-11-27
### Changed
- Upgraded dependencies
- Updated translations

### Fixed
- Fixed error in export data (#499)
- Fixed issues with reverse proxies (#495)
- Fixed duplicated twitter tags (#496)

## [3.6.0] - 2022-06-20
### Changed
- Improved embedding on discord of large videos.
- Releases are now compressed for faster downloads
- Updated translations

### Fixed
- Fixed deprecation notices on php >= 8
- Fixed embed UA for Discord.
- Fixed error with post_max_size = 0

### Removed
- Support for php 7.2

## [3.5.1] - 2021-10-22
### Changed
- Fixed embed UA for Discord.
- Updated translations.

## [3.5.0] - 2021-09-05
### Added
- Support for theme-park.dev themes.
- Updated translations.

### Fixed
- Wrong css when reapplying the default theme.

### Removed
- Dropped theme cli command.


## [3.4.1] - 2021-08-11
### Added
- Toggle to disable embeds.

### Changed
- Raw url copying now contains also the file extension.

## [3.4.0] - 2021-08-01
### Added
- Added image support for OG for Discord only.

### Changed
- Updated translations.
- Dropped support for PHP 7.1

### Fixed
- Fixed possible XSS and CSRF attacks.

## [3.3.5] - 2021-04-25
### Fixed
- Removed OG integration for discord.

### Changed
- Updated translations.

## [3.3.4] - 2021-03-07
### Added
- Login failed logging.
- User identifier option for LDAP configurations.

### Fixed
- Fixed open graph meta tags for Discord.
- Fixed custom html tags are not displayed back in the admin setting.
- Fixed python plugin for newer version of Screencloud.
- Fixed accented chars in email subject.
- Fixed error on PHP 8.

## [3.3.3] - 2020-11-13
### Fixed
- Fixed issue with responsive menu on mobile.

## [3.3.2] - 2020-11-12
### Fixed
- Fixed switch not works for the first time for normal users.

## [3.3.1] - 2020-11-12
### Fixed
- Formatting error on the check for updates.
- Fixed default view for normal users.

## [3.3.0] - 2020-11-12
### Added
- Enabled PHP 8 support.
- Added Screencloud client support (https://screencloud.net).
- OpenGraph image tag (issue #269).
- Start adding unit tests.

### Changed
- The list mode is now available also for non-admin accounts (issue #226).

### Fixed
- Linux script strange response code in headless mode.

### Removed
- Dropped Telegram share button.

## [3.2.0] - 2020-09-05
### Added
- Added support to use Azure Blob Storage account as storage location.
- Support for other S3-compatible storage endpoint.
- Line number when showing text files.

### Fixed
- S3 driver file streaming not working properly.
- Fixed Slack image preview.

## [3.1.4] - 2020-04-13
### Changed
- Now the migrate command resync the system quota for each user.

### Fixed
- Fixed error with the migrate command.

## [3.1.3] - 2020-04-13
### Changed
- Added changelog page.
- Updated translations.

## [3.1.2] - 2020-04-12
### Changed
- Improved installer storage checks.

### Fixed
- Fixed upload table lost when updating very old instances.

## [3.1.1] - 2020-04-11
### Fixed
- Fixed error during a fresh installation with sqlite.

## [3.1] - 2020-04-10
### Added
- Added tagging system (add, delete, search of tagged files).
- Added basic media auto-tagging on upload.
- Added registration system.
- Added password recovery system.
- Added ability to export all media of an account.
- Added ability to choose between default and raw url on copy.
- Added hide by default option.
- Added user disk quota.
- Added reCAPTCHA login protection.
- Added bulk delete.
- Added account clean function.
- Added user disk quota system.
- Added notification option on account create.
- Added LDAP authentication.

### Changed
- The theme is now re-applied after every system update.
- Updated system settings page.
- Updated translations.
- Improved grid layout.

### Fixed
- Fixed bug html files raws are rendered in a browser.
- Fixes and improvements.

## [3.0.2] - 2019-12-04
### Changed
- Updated translations.

### Fixed
- Fixed error with migrate command.

## [3.0.1] - 2019-11-25
### Changed
- Small installer update.

### Fixed
- Fixed error with older mysql versions.
- Fixed config is compiled with the di container.

## [3.0] - 2019-11-23
### Added
- Added web upload.
- Added ability to add custom HTML in \<head\> tag.
- Added ability to show a preview of PDF files.
- Added remember me functionality.
- Added delete button on the preview page if the user is logged in.
- New project icon (by [@SerenaItalia](https://www.deviantart.com/serenaitalia)).
- The linux script can be used on headless systems.
- Raw URL now accept file extensions.
- Implemented SameSite XSS protection.

### Changed
- Upgraded from Slim3 to Slim 4.
- Replaced videojs player with Plyr.
- Improved installer.
- Improved thumbnail generation.
- Small fixes and improvements.

## [2.6.6] - 2019-10-23
### Added
- Ability to choose between releases and prereleases with the web updater.

### Changed
- Updated translations.

## [2.6.5] - 2019-09-17
### Changed
- Changed color to some buttons to address visibility with some themes.

### Fixed
- Fixed error after orphaned files removal #74.
- Fixed update password not correctly removed from log files (#74).

## [2.6.4] - 2019-09-15
### Added
- Filter on displayable images.

### Changed
- The generated random strings are now more human readable.

### Fixed
- Fixed during upload error on php compiled for 32 bit.
- Fixed icons on the installer page.

## [2.6.3] - 2019-09-14
### Fixed
- Fixed #67.
- Fixed bad preload statement.
- Fixed wrong redirect after install in subdirs.

## [2.6.2] - 2019-09-06
### Added
- Added method for cache busting when updating/change theme.
- Added russian translation from [Weblate](https://hosted.weblate.org/projects/xbackbone/xbackbone/).

### Changed
- Changed background default color.
- Use the Font Awesome web font for better performances.

## [2.6.1] - 2019-09-04
### Added
- Added alert if required extensions are not loaded.

### Changed
- Improved shell commands.
- Updated translations.

### Fixed
- Fixed bad redirects on the web installer (#62).
- Fixed login page with dark themes.

## [2.6] - 2019-08-20
### Added
- Added support to use AWS S3, Google Cloud Storage, Dropbox and FTP(s) accounts as storage location.
- Added german and norwegian translations from [Weblate](https://hosted.weblate.org/projects/xbackbone/xbackbone/).
- Added ability to force system language.

### Changed
- Improved lang detection.

### Fixed
- Fixed missing icon.

## [2.5.3] - 2019-05-12
### Changed
- Improved exception stacktrace logging.

### Fixed
- Fixed bad css loading on Firefox (#35).
- Fixed wrong style for publish/unpublish button.

## [2.5.2] - 2019-05-09
### Added
- Added preloading for some resources to improve performances.
- Added check for block execution on EOL and unsupported PHP versions.

### Changed
- Improved session handling.
- Other minor improvements.

### Fixed
- Fixed telegram share not working.
- Fix for big text file now are not rendered in the browser.

## [2.5.1] - 2019-04-10
### Changed
- Improved HTTP partial content implementation for large files.

### Fixed
- Fixed bad redirect if the theme folder is not writable. (#27)

## [2.5] - 2019-02-10
### Added
- Added partial content implementation (stream seeking on chromium based browsers).
- **[BETA]** Added self update feature.
- Added project favicon.

### Changed
- Updated project license to [AGPL v3.0](https://choosealicense.com/licenses/agpl-3.0/) (now releases ships with the new license).
- Improved video.js alignment with large videos.
- Optimized output zip release size.
- Templates cleanup and optimizations.
- Improved error handling.

## [2.4.1] - 2019-01-24
### Fixed
- Fixed error message when the file is too large. (#15)
- Fixed button alignment.

## [2.4] - 2019-01-22
### Added
- Added function to remove orphaned files.
- Multiple uploads sorting methods.
- Switch between tab and gallery mode using an admin account.
- Search in uploads.

### Changed
- Updated js dependencies.
- Internal refactoring and improvements

## [2.3.1] - 2018-12-09
### Added
- Added checks during the installation wizard.
- cURL and Wget can now directly download the file.

### Fixed
- Fixed english language.
- Fixed forced background with dark themes.

## [2.3] - 2018-11-30
### Added
- Added overlay on user gallery images.
- Added linux script to allow uploads from linux screenshot tools.
- Enable audio player with video.js.
- Font Awesome icon match the single file mime-type.

### Changed
- Improved image scaling in user gallery.
- Video and audio now starts with volume at 50%.
- Minor layout fixes.

### Fixed
- Fixed IT translation.

## [2.2] - 2018-11-20
### Added
- Added multi-language support.

### Fixed
- Improved routing.
- Minor improvements and bug fixes.
- Fixed HTTP/2 push is resetting the current session.

## [2.1] - 2018-11-20
### Added
- Added video.js support.
- Allow e-mail login.
- Support for ShareX deletion URL.

### Changed
- Improved theme style.
- Improved page redirecting.

### Fixed
- Fixed HTTP/2 push preload.

## [2.0] - 2018-11-13
### Added
- Added install wizard (using the CLI is no longer required).
- Added used space indicator per user.
- Allow discord bot to display the preview.
- Theme switcher on the web UI.
- MySQL support.

### Changed
- Migrated from Flight to Slim 3 framework.
- Improvements under the hood.

## [1.3] - 2018-10-14
### Added
- Added command to switch between bootswatch.com themes.
- Added popover to write the telegram message when sharing.
- Allow Facebook bots to display the preview.

### Changed
- Packaging improvements.
- Updated some dependencies.

## [1.2] - 2018-05-01
### Added
- Added auto config generator for ShareX.
- Show upload file size on the dashboard.

### Changed
- Previews are now scaled for better page load.

### Removed
- Removed HTTP2 push from the dashboard to improve loading time.

### Fixed
- Fixed insert for admin user (running `php bin\migrate --install`).

## [1.1] - 2018-04-28
### Added
- Added logging.
- Added share to Telegram.

### Changed
- Improved migrate system.
- Updated Bootstrap theme.

### Fixed
- Fixed back to top when click delete or publish/unpublish.
- Login redirect back to the requested page.

## [1.0] - 2018-04-28
### Added
- Initial version.
