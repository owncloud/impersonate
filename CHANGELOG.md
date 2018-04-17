# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.2.0]

### Changed
- Group admins can only impersonate members of the same group [#99](https://github.com/owncloud/impersonate/pull/99)


## [0.1.2] - 2017-12-07

### Changed
- Moved impersonate Settings into User Authentication Section in Admin Panel [#54](https://github.com/owncloud/impersonate/pull/54)
- View default app when impersonating a user [#88](https://github.com/owncloud/impersonate/pull/88)
- Use precompiled handlebars for frontend templates [#42](https://github.com/owncloud/impersonate/pull/42)
- Use core hooks for logout of impersonated users [#68](https://github.com/owncloud/impersonate/pull/68)

### Fixed
- Impersonate not working with 10.0.4 [#80](https://github.com/owncloud/impersonate/pull/80)


## [0.1.1] - 2017-11-13

### Changed

- Replace Phony target all with dist in Makefile - [#65](https://github.com/owncloud/impersonate/issues/65)
- Change screenshot, fix indentation in app info - [#61](https://github.com/owncloud/impersonate/issues/61)

### Fixed

- Prevent further level impersonation - [#63](https://github.com/owncloud/impersonate/issues/63)
- Clear session when impersonation does not happen - [#62](https://github.com/owncloud/impersonate/issues/62)
- Restrict impersonate to admin user from subadmin user - [#49](https://github.com/owncloud/impersonate/issues/49)
- Minor code cleanup - [#46](https://github.com/owncloud/impersonate/issues/46)

## [0.1.0] - 2017-05-31

### Added

- Add screenshot file - [#40](https://github.com/owncloud/impersonate/issues/40)
- Changing the order of signing the app - [#39](https://github.com/owncloud/impersonate/issues/39)
- Make logged in message permanent in users page - [#34](https://github.com/owncloud/impersonate/issues/34)
- Make loglevel messages from warning to info - [#31](https://github.com/owncloud/impersonate/issues/31)
- Makefile for impersonate app. - [#28](https://github.com/owncloud/impersonate/issues/28)
- User who hasn't logged in yet cannot be impersonated - [#27](https://github.com/owncloud/impersonate/issues/27)
- Upgrading the version from 0.0.5 to 0.1.0 - [#26](https://github.com/owncloud/impersonate/issues/26)
- Improve impersonate app with changes - [#16](https://github.com/owncloud/impersonate/issues/16)

### Changed

- Rename subadmins to Group admins - [#33](https://github.com/owncloud/impersonate/issues/33)
- Move location of keys to $(HOME)/.owncloud/certificates - [#30](https://github.com/owncloud/impersonate/issues/30)
- Adjust travis PHP versions - [#19](https://github.com/owncloud/impersonate/issues/19)

### Fixed

- Fix the user page issue - [#35](https://github.com/owncloud/impersonate/issues/35)
- This commit fixes the issues below: - [#24](https://github.com/owncloud/impersonate/issues/24)


## Old Log

### Added

owncloud-impersonate (0.0.3)
* use eventdispatcher for js injection to users page, requires oc9

owncloud-impersonate (0.0.2)
* add action to userlist instead of input field in admin settings

owncloud-impersonate (0.0.1)
* First release

[0.2.0]: https://github.com/owncloud/impersonate/compare/v0.1.2...v0.2.0
[0.1.2]: https://github.com/owncloud/impersonate/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/owncloud/impersonate/compare/v0.1.0...v0.1.1