@qbank @qbank_viewquestionnname @javascript
Feature: Filter questions by name
  As a teacher
  In order to organise my questions
  I want to filter the list of questions by name

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name    | intro              | course | idnumber |
      | qbank      | Qbank 1 | Question bank 1    | C1     | qbank1   |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | qbank1    | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name            | questiontext               |
      | Test questions   | truefalse | First question  | Answer the first question  |
      | Test questions   | numerical | Second question | Answer the second question |
      | Test questions   | essay     | Third question  | Answer the third question  |

  Scenario: Filter by a single word
    Given I am on the "Qbank 1" "core_question > question bank" page logged in as "admin"
    When I apply question bank filter "Question name" with value "First"
    Then I should see "First question"
    And I should not see "Second question"
    And I should not see "Third question"

  Scenario: Filter by any word
    Given I am on the "Qbank 1" "core_question > question bank" page logged in as "admin"
    When I apply question bank filter "Question name" with value "First, Third"
    Then I should see "First question"
    And I should not see "Second question"
    And I should see "Third question"

  Scenario: Filter by all words
    Given I am on the "Qbank 1" "core_question > question bank" page logged in as "admin"
    When I add question bank filter "Question name"
    And I set the field "Question name" to "question, d"
    And I set the field "Match" in the "Filter 3" "fieldset" to "All"
    And I press "Apply filters"
    Then I should not see "First question"
    And I should see "Second question"
    And I should see "Third question"

  Scenario: Exclude names by filter
    Given I am on the "Qbank 1" "core_question > question bank" page logged in as "admin"
    When I add question bank filter "Question name"
    And I set the field "Question name" to "First, Third"
    And I set the field "Match" in the "Filter 3" "fieldset" to "None"
    And I press "Apply filters"
    Then I should not see "First question"
    And I should see "Second question"
    And I should not see "Third question"
