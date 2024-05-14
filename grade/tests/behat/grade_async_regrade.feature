@core @core_grades @javascript
Feature: Asynchronous regrade on a large course

  Background:
    Given the following "courses" exist:
      | shortname | fullname      | idnumber |
      | C1        | Test course 1 | C1       |
    And the following "users" exist:
      | username  |
      | teacher1  |
    And the following "course enrolments" exist:
      | user      | course | role           |
      | teacher1  | C1     | editingteacher |
    And "100" "users" exist with the following data:
      | username  | student[count]             |
      | firstname | Student                    |
      | lastname  | [count]                    |
      | email     | student[count]@example.com |
    And "100" "course enrolments" exist with the following data:
      | user   | student[count] |
      | course | C1             |
      | role   | student        |
    And the following "activity" exists:
      | activity                            | assign                  |
      | course                              | C1                      |
      | idnumber                            | a1                      |
      | name                                | Test assignment 1       |
      | grade                               | 100                     |
      | intro                               | Submit your online text |

  Scenario: Edit a course module without any grades saved
    And I am on the "Test assignment 1" "assign activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I set the field "Maximum grade" to "50"
    And I press "Save and return to course"
    And I am on the "Test course 1" "grades > Grader report > View" page
    Then I should not see "Grade recalculations are being performed in the background."

  Scenario: Edit a course module with 100 grades saved
    And "100" "grade grades" exist with the following data:
      | gradeitem | Test assignment 1 |
      | user      | student[count]    |
      | grade     | 80.00             |
    And I am on the "Test assignment 1" "assign activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I set the field "Rescale existing grades" to "Yes"
    And I set the field "Maximum grade" to "50"
    When I press "Save and return to course"
    And I am on the "Test course 1" "grades > Grader report > View" page
    And I should see "Grade recalculations are being performed in the background."
    And I should see "Task pending"
    And I should see "0.0%"
    And "40.00" "text" should not exist in the "student2@example.com" "table_row"
    When I run all adhoc tasks
    Then I should not see "Task pending"
    And I should see "100%"
    And I reload the page
    And I should not see "Grade recalculations are being performed in the background."
    And "40.00" "text" should exist in the "student2@example.com" "table_row"
