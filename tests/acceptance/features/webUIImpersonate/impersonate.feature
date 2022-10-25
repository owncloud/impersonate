@webUI @insulated
# NOTE: Impersonation only works after the user (to be impersonated) has logged in or sent some API requests
Feature: Impersonate
  As an admin
  I want to impersonate a user
  So that I can see what they see


  Scenario Outline: administrator impersonates other users
    Given the administrator has created following users with settings:
      | username | password | group  | role   |
      | Alice    | 123456   | group1 | <role> |
    And "<impersonate-setting>" option in impersonate settings has been set to "<value>"
    And user "Alice" has created folder "test-folder"
    And the administrator has logged in using the webUI
    And the administrator has browsed to the users page
    When the administrator impersonates user "Alice" using the webUI
    Then the administrator should be redirected to the files page of user "Alice"
    And impersonate notification should be displayed on the webUI with the text "Logged in as Alice"
    When the impersonated user logs out of the webUI
    Then "admin" should be navigated back to their own account
    Examples:
      | impersonate-setting                  | value  | role        |
      | allow only an admin                  |        | user        |
      | allow all group admins               |        | group-admin |
      | only group admins of specific groups | group1 | group-admin |


  Scenario Outline: group admin impersonate other user of same group
    Given the administrator has created following users with settings:
      | username | password | group  | role        |
      | Alice    | 123456   | group1 | user        |
      | Brian    | 123456   | group1 | group-admin |
    And "<impersonate-setting>" option in impersonate settings has been set to "<value>"
    And user "Alice" has created folder "test-folder"
    And user "Brian" has logged in using the webUI
    And the user has browsed to the users page
    When the user impersonates user "Alice" using the webUI
    Then the user should be redirected to the files page of user "Alice"
    And impersonate notification should be displayed on the webUI with the text "Logged in as Alice"
    When the impersonated user logs out of the webUI
    Then "Brian" should be navigated back to their own account
    Examples:
      | impersonate-setting                  | value  |
      | allow all group admins               | true   |
      | only group admins of specific groups | group1 |


  Scenario: group admin tries to impersonate other user of same group when allow only an admin setting is set
    Given the administrator has created following users with settings:
      | username | password | group  | role        |
      | Alice    | 123456   | group1 | user        |
      | Bob      | 123456   | group1 | group-admin |
    And "allow only an admin" option in impersonate settings has been set to " "
    And user "Alice" has created folder "test-folder"
    And user "Bob" has logged in using the webUI
    And the user has browsed to the users page
    When the user impersonates user "Alice" using the webUI
    Then dialog should be displayed on the webUI
      | title | content             |
      | Error | Can not impersonate |
