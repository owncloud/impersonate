@api
Feature: Impersonate

  As an admin
  I want to impersonate a user
  So that I can see what they see

  Background:
    Given the administrator has created following users with settings:
      | username | password | group  | role        |
      | Alice    | Alice    | group1 | user        |
      | Bob      | Bob      | group1 | group-admin |
      | John     | John     | group2 | user        |
      | Harry    | Harry    | group2 | group-admin |
    And user "Alice" has created folder "abcd"
    And user "Bob" has created folder "abcd"
    And user "John" has created folder "abcd"
    And user "Harry" has created folder "abcd"


  Scenario Outline: Admin is able to impersonate in each case
    Given "<impersonate-setting>" option in impersonate settings has been set to "<value>"
    When "admin" sends a request to impersonate user "Alice"
    Then the HTTP status code should be "200"
    When "admin" sends a request to impersonate user "Bob"
    Then the HTTP status code should be "200"
    When "admin" sends a request to impersonate user "John"
    Then the HTTP status code should be "200"
    When "admin" sends a request to impersonate user "Harry"
    Then the HTTP status code should be "200"
    Examples:
        | impersonate-setting                  | value  |
        | allow only an admin                  |        |
        | allow all group admins               |        |
        | only group admins of specific groups | group1 |
        | only group admins of specific groups | group2 |


  Scenario:  Allow all group admins to impersonate users within the groups they are admins of
    Given "allow all group admins" option in impersonate settings has been set to "true"
    When "Bob" sends a request to impersonate user "Alice"
    Then the HTTP status code should be "200"
    When "Harry" sends a request to impersonate user "John"
    Then the HTTP status code should be "200"


  Scenario:  Allow group admins of group1 only to impersonate users within group1
    Given "only group admins of specific groups" option in impersonate settings has been set to "group1"
    When "Bob" sends a request to impersonate user "Alice"
    Then the HTTP status code should be "200"
    When "Harry" sends a request to impersonate user "John"
    Then the HTTP status code should be "404"

  Scenario Outline: User in a group tries to impersonate other user of same group
    Given "<impersonate-setting>" option in impersonate settings has been set to "<value>"
    When "Alice" sends a request to impersonate user "John"
    Then the HTTP status code should be "<status-code>"
    Examples:
      | impersonate-setting                  | value  | status-code |
      | allow only an admin                  |        | 404         |
      | allow all group admins               |        | 404         |
      | only group admins of specific groups | group1 | 404         |

  Scenario Outline: User in a group tries to impersonate group admins of the same group
    Given "<impersonate-setting>" option in impersonate settings has been set to "<value>"
    When "Alice" sends a request to impersonate user "Bob"
    Then the HTTP status code should be "<status-code>"
    Examples:
      | impersonate-setting                  | value  | status-code |
      | allow only an admin                  |        | 404         |
      | allow all group admins               |        | 404         |
      | only group admins of specific groups | group1 | 404         |
