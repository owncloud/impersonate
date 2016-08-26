# Impersonate

Allow administrators to become a different user by adding an impersonate action
to the user list.

Place this app in **owncloud/apps/**


# TODO
- UI: Place the impersonate action next to trashbin?
- UI: Only show action icon on hover?

# Known limitations
- If you impersonate a user that has never logged in, the filesystem cannot be initialized (that requires a proper login). As a result you will only see an error page, no matter what app you try to use. You have to kill the cookie to log out. Maybe add a logout link to error pages?