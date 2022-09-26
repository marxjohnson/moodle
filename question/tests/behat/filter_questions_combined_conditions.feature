@core @core_question
Feature: The questions in the question bank can be filtered by combine various conditions
  In order to find the questions I need
  As a teacher
  I want to filter the questions by various conditions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | weeks |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name            |
      | Course       | C1        | Test questions 1|
      | Course       | C1        | Test questions 2|
    And the following "questions" exist:
      | questioncategory | qtype     | name            | user     | questiontext    |
      | Test questions 1 | essay     | question 1 name | teacher1 | Question 1 text |
      | Test questions 1 | essay     | question 2 name | teacher1 | Question 2 text |
      | Test questions 2 | essay     | question 3 name | teacher1 | Question 3 text |
      | Test questions 2 | essay     | question 4 name | teacher1 | Question 4 text |
    And the following "core_question > Tags" exist:
      | question        | tag |
      | question 1 name | foo |
      | question 3 name | foo |
    And I am on the "Course 1" "core_question > course question bank" page logged in as "teacher1"

  @javascript
  Scenario: The questions can be filtered by matching all conditions
    And I set the field "Type or select..." in the "Filter 1" "fieldset" to "Test questions 1"
    When I click on "Add condition" "button"
    And I set the field "Match" to "All"
    And I set the field "type" in the "Filter 3" "fieldset" to "Tag"
    And I set the field "Type or select..." in the "Filter 3" "fieldset" to "foo"
    And I click on "Apply filters" "button"
    Then I should see "question 1 name" in the "categoryquestions" "table"
    And I should not see "question 2 name" in the "categoryquestions" "table"
    And I should not see "question 3 name" in the "categoryquestions" "table"
    And I should not see "question 4 name" in the "categoryquestions" "table"

  @javascript
  Scenario: The questions can be filtered by matching any conditions
    And I set the field "Type or select..." in the "Filter 1" "fieldset" to "Test questions 1"
    When I click on "Add condition" "button"
    And I set the field "Match" to "Any"
    And I set the field "type" in the "Filter 3" "fieldset" to "Tag"
    And I set the field "Type or select..." in the "Filter 3" "fieldset" to "foo"
    And I click on "Apply filters" "button"
    Then I should see "question 1 name" in the "categoryquestions" "table"
    And I should see "question 2 name" in the "categoryquestions" "table"
    And I should see "question 3 name" in the "categoryquestions" "table"
    And I should not see "question 4 name" in the "categoryquestions" "table"

  @javascript
  Scenario: The questions can be filtered by matching none of conditions
    And I set the field "Type or select..." in the "Filter 1" "fieldset" to "Test questions 1"
    When I click on "Add condition" "button"
    And I set the field "Match" to "None"
    And I set the field "type" in the "Filter 3" "fieldset" to "Tag"
    And I set the field "Type or select..." in the "Filter 3" "fieldset" to "foo"
    And I click on "Apply filters" "button"
    Then I should not see "question 1 name" in the "categoryquestions" "table"
    And I should not see "question 2 name" in the "categoryquestions" "table"
    And I should not see "question 3 name" in the "categoryquestions" "table"
    And I should see "question 4 name" in the "categoryquestions" "table"
