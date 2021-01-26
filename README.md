# Impersonate
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_impersonate&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_impersonate)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_impersonate&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_impersonate)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_impersonate&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_impersonate)

The Impersonate application allows administrators, and group admins, to log in as another user within an ownCloud instance. It provides a helpdesk-like experience and can be useful to help users with configuration issues, to get a better understanding of what they see when they use their ownCloud account, or to perform actions in legacy accounts.

Once Impersonate is installed, a new column will be available in the user management panel. Click on the icon next to the user that you want to impersonate and you will be logged in as that user. Your current session will be temporarily suspended, and you will see a notification at the top of the page reminding you that you’re impersonating another user. Once you’re finished, log out, and you will return to your previous user session.

As a security measure, the application lets ownCloud administrators restrict the ability to impersonate users in specific groups. When enabled and configured, only a group’s administrator can impersonate members of their group. Administrators can find configuration options in the "User Authentication" section of the "Admin settings" panel.

## Installation
For development, execute `make build-dep; make js-templates`
To create distribution tar file, execute `make dist`

# Known limitations
- If you impersonate a user that has never logged in, the filesystem cannot be initialized (that requires a proper login). As a result you will only see an error page, no matter what app you try to use. You have to kill the cookie to log out. Maybe add a logout link to error pages?
