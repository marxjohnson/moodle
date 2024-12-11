@qbank @qbank_viewquestionnname @javascript
Feature: Filter questions by idnumber
  As a teacher
  In order to organise my questions
  I want to filter the list of questions by idnumber

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
      | questioncategory | qtype     | name | questiontext               | idnumber        |
      | Test questions   | truefalse | a    | Answer the first question  | First question  |
      | Test questions   | numerical | b    | Answer the second question | Second question |
      | Test questions   | essay     | c    | Answer the third question  | Third Question  |
    And I am on the "Qbank 1" "core_question > question bank" page logged in as "admin"
    And I should see "First question"
    And I should see "Second question"
    And I should see "Third Question"

  Scenario: Filter by a single word
    When I apply question bank filter "Question ID number" with value "First"
    Then I should see "First question"
    And I should not see "Second question"
    And I should not see "Third Question"

  Scenario: Filter by any word
    When I apply question bank filter "Question ID number" with value "First, Third"
    Then I should see "First question"
    And I should not see "Second question"
    And I should see "Third Question"

  Scenario: Filter by all words
    When I add question bank filter "Question ID number"
    And I set the field "Question ID number" to "question, d"
    And I set the field "Match" in the "Filter 3" "fieldset" to "All"
    And I press "Apply filters"
    Then I should not see "First question"
    And I should see "Second question"
    # Filter should be case-insensitive.
    And I should see "Third Question"

  Scenario: Exclude idnumbers by filter
    When I add question bank filter "Question ID number"
    And I set the field "Question ID number" to "First, Third"
    And I set the field "Match" in the "Filter 3" "fieldset" to "None"
    And I press "Apply filters"
    Then I should not see "First question"
    And I should see "Second question"
    And I should not see "Third Question"
