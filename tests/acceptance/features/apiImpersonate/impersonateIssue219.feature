@api @issue-219
Feature: Impersonate Bug Demonstration for Issue #219

  As a owner,
  I want to impersonate a user,
  So that I can see what they see.

  Background:
    Given the administrator has created following users:
      | username | password | group  | role        |
      | user1    | user1    | group1 | user        |
      | user2    | user2    | group2 | user        |
    And user "user1" has created folder "abcd"
    And user "user2" has created folder "abcd"


  Scenario Outline: user in a group tries to impersonate other user of same group
    Given "<impersonate-setting>" option in impersonate settings has been set to "<value>"
    When "user1" sends a request to impersonate user "user2"
    Then the HTTP status code should be "200"
    # it should not be successful as user1 is in group1 and user2 is in group2
    Examples:
        | impersonate-setting                  | value  |
        | allow all group admins               |        |
