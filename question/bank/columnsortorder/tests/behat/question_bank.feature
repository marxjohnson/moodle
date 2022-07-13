@qbank @qbank_columnsortorder @javascript
Feature: Set question bank column order, pinning and size
  In order customise my view of the question bank
  As a teacher
  I want to hide, reorder, resize and pin columns

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | weeks  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name           | intro              | course | idnumber |
      | quiz     | Test quiz Q001 | Quiz 1 description | C1     | quiz1    |
    And the following "question category" exist:
      | contextlevel    | reference | name              |
      | Activity module | quiz1     | Default for quiz1 |
    And the following "questions" exist:
      | questioncategory  | qtype | name                     | user     | questiontext                  | idnumber  |
      | Default for quiz1 | essay | Test question to be seen | teacher1 | Write about whatever you want | idnumber2 |

  Scenario: Teacher can see proper view
    Given I am on the "Test quiz Q001" "mod_quiz > question bank" page logged in as "teacher1"
    And I set the field "Select a category" to "Default for quiz1"
    And I should see "Test question to be seen"
    Then I should see "Teacher 1"

  Scenario: Defaults set in the admin screen are applied to the user's initial view
    Given I log in as "admin"
    When I navigate to "Plugins > Question bank plugins > Column sort order" in site administration
    And I drag "Created by" "qbank_columnsortorder > column move handle" and I drop it in "T" "qbank_columnsortorder > column header"
    And I am on the "Test quiz Q001" "mod_quiz > question bank" page logged in as "teacher1"
    And I set the field "Select a category" to "Default for quiz1"
    Then "Created by" "qbank_columnsortorder > column header" should appear before "T" "qbank_columnsortorder > column header"

  Scenario: User preference takes precedence over global defaults
    Given I am on the "Course 1" Course page logged in as admin
    And I am on "Course 1" course homepage with editing mode on
    Given I am on the "Test quiz Q001" "mod_quiz > question bank" page
    And I set the field "Select a category" to "Default for quiz1"
    And I change window size to "large"
    And "Status" "qbank_columnsortorder > column header" should appear before "Created by" "qbank_columnsortorder > column header"
    And I drag "Created by" "qbank_columnsortorder > column move handle" and I drop it in "Status" "qbank_columnsortorder > column header"
    And I reload the page
    And "Status" "qbank_columnsortorder > column header" should appear after "Created by" "qbank_columnsortorder > column header"
    And I navigate to "Plugins > Question bank plugins > Column sort order" in site administration
    And "Status" "qbank_columnsortorder > column header" should appear before "Created by" "qbank_columnsortorder > column header"

  Scenario: User can hide a column in the question bank
    Given I am logged in as "teacher1"
    And I am on "Course 1" Course homepage with editing mode on
    And I am on the "Test quiz Q001" "mod_quiz > question bank" page
    And I set the field "Select a category:" to "Default for quiz1"
    And I should see "Created by" in the "Actions" "table_row"
    When I press "Show/Hide column"
    And I set the field "Created by" to "0"
    Then I should not see "Created by" in the "Actions" "table_row"
    And I am on "Course 1" Course homepage with editing mode off
    And I am on the "Test quiz Q001" "mod_quiz > question bank" page
    And I set the field "Select a category:" to "Default for quiz1"
    And I should not see "Created by" in the "Actions" "table_row"

  Scenario: User can resize a column in the question bank using modal dialog
    Given I am logged in as "teacher1"
    And I am on "Course 1" Course homepage with editing mode on
    And I am on the "Test quiz Q001" "mod_quiz > question bank" page
    And I set the field "Select a category:" to "Default for quiz1"
    And the "style" attribute of "Action" "qbank_columnsortorder > column header" should not contain "width: 480px"
    When I click on "Action" "qbank_columnsortorder > column resize handle"
    And I set the field "Column width (pixels)" to "480"
    And I press "Save changes"
    Then the "style" attribute of "Action" "qbank_columnsortorder > column header" should contain "width: 480px"
