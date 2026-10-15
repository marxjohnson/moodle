@mod @mod_quiz
Feature: Allow settings to show Max marks and Marks, Max marks only, or hide the grade information completely.
  As a teacher
  In order to decide how grade review options are displayed on questions in a quiz review page
  I need to be able to set the grade review options for a quiz to to show Max and Marks, Max only, or hide the grade information.

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email                |
      | student1  | Student   | One      | student1@example.com |
      | teacher   | Teacher   | One      | teacher@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name    | intro              | course | idnumber |
      | qbank    | Qbank 1 | Question bank 1    | C1     | qbank1   |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | qbank1    | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext    |
      | Test questions   | truefalse | TF1  | First question  |
    And the following "activities" exist:
      | activity | name   | intro       | course | idnumber | maxmarksduring | marksduring | maxmarksimmediately | marksimmediately | preferredbehaviour |
      | quiz     | Quiz 1 | Quiz 1 test | C1     | quiz1    | 1              | 1           | 1                   | 1                | immediatefeedback  |
      | quiz     | Quiz 2 | Quiz 2 test | C1     | quiz2    | 0              | 0           | 1                   | 1                | immediatefeedback  |
    And the following "activities" exist:
      | activity | name   | intro       | course | idnumber | marksimmediately | marksopen | marksclosed | preferredbehaviour |
      | quiz     | Quiz 3 | Quiz 3 test | C1     | quiz3    | 1                | 0         | 0           | immediatefeedback  |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    | 2.00    |
    And quiz "Quiz 2" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    | 2.00    |
    And quiz "Quiz 3" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    | 2.00    |

  @javascript
  Scenario: Show max marks and marks during and immediately after the attempt.
    Given I am on the "Quiz 1" "quiz activity" page logged in as "student1"
    And I click on "Attempt quiz" "button"
    And I should see "Question 1" in the ".info" "css_element"
    And I should see "Not complete" in the ".info" "css_element"
    And I should see "Marked out of 2.00" in the ".info" "css_element"
    And I set the field "True" to "1"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    Then I should see "Finished" in the "Status" "table_row"
    And I should see "Question 1" in the ".info" "css_element"
    And I should see "Correct" in the ".info" "css_element"
    And I should see "Mark 2.00 out of 2.00" in the ".info" "css_element"

    And I am on the "Quiz 2" "quiz activity" page
    And I click on "Attempt quiz" "button"
    And I should see "Question 1" in the ".info" "css_element"
    And I should see "Not complete" in the ".info" "css_element"
    And I should not see "Marked out of 2.00" in the ".info" "css_element"
    And I set the field "True" to "1"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I should see "Finished" in the "Status" "table_row"
    And I should see "Question 1" in the ".info" "css_element"
    And I should see "Correct" in the ".info" "css_element"
    And I should see "Mark 2.00 out of 2.00" in the ".info" "css_element"

    # Check the page that lists all the quizzes in a course.
    And I am on the "C1" "quiz index" page
    And I should see "Quiz 1"
    And I should see "Quiz 2"

  @javascript
  Scenario: Review attempt before and after asynchronous grading
    Given the following config values are set as admin:
     | asyncgrading | 1 | quiz |
    And I am on the "Quiz 1" "quiz activity" page logged in as "student1"
    And I click on "Attempt quiz" "button"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "Finish attempt ..." "button" in the "region-main" "region"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I should see "Submitted" in the "Status" "table_row"
    And "Completed" "table_row" should exist
    And "Duration" "table_row" should exist
    And "Marks" "table_row" should not exist
    And I should see "Grading in progress" in the "Grade" "table_row"
    And I should see "This attempt has been submitted"
    And the field "True" matches value "1"
    And the field "False" matches value "0"
    And I should not see "Correct" in the ".info" "css_element"
    And I should not see "Mark 2.00 out of 2.00" in the ".info" "css_element"
    And I should not see "This is the right answer."
    When I run all adhoc tasks
    Then I should see "Finished" in the "Status" "table_row"
    And "Completed" "table_row" should exist
    And "Duration" "table_row" should exist
    And I should see "2.00/2.00" in the "Marks" "table_row"
    And I should see "100.00 out of 100.00" in the "Grade" "table_row"
    And I should not see "This attempt has been submitted"
    And the field "True" matches value "1"
    And the field "False" matches value "0"
    And I should see "Correct" in the ".info" "css_element"
    And I should see "Mark 2.00 out of 2.00" in the ".info" "css_element"
    And I should see "This is the right answer."

  Scenario: A quiz cannot be attempted if asynchronous grading is enabled, and marks can only be viewed immediately.
    Given I am on the "Quiz 1" "quiz activity" page logged in as "student1"
    And "Attempt quiz" "button" should exist
    And I am on the "Quiz 3" "quiz activity" page
    And "Attempt quiz" "button" should exist
    When the following config values are set as admin:
      | asyncgrading | 1 | quiz |
    And I am on the "Quiz 1" "quiz activity" page
    And "Attempt quiz" "button" should exist
    And I am on the "Quiz 3" "quiz activity" page
    Then "Attempt quiz" "button" should not exist
    And I should see "The current review options for this quiz are incompatible, so it cannot be attempted."
    And I am on the "Quiz 3" "quiz activity" page logged in as "teacher"
    And I should see "The current review options are incompatible with asynchronous grading, which is enabled."

  Scenario: Quiz settings cannot be saved with incompatible review options when asynchronous grading is enabled.
    Given I am on the "quiz3" "activity editing" page logged in as "teacher"
    And I press "Save and return to course"
    And "Course 1" "heading" should exist
    When the following config values are set as admin:
      | asyncgrading | 1 | quiz |
    And I am on the "quiz3" "activity editing" page logged in as "teacher"
    And I press "Save and return to course"
    And "Course 1" "heading" should not exist
    And I should see "The current review options are incompatible with asynchronous grading, which is enabled."
    And I set the field "marksopen" to "1"
    And I press "Save and return to course"
    And "Course 1" "heading" should exist

  @javascript
  Scenario: Question flags cannot be changed while locked.
    Given the following config values are set as admin:
      | asyncgrading | 1 | quiz |
    And I am on the "Quiz 1" "quiz activity" page logged in as "student1"
    And I click on "Attempt quiz" "button"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "Finish attempt ..." "button" in the "region-main" "region"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I should see "Grading in progress" in the "Grade" "table_row"
    And I should see "Flag question"
    And I click on "Flag question" "text"
    And I should not see "Flag question"
    And I should see "Remove flag"
    When I lock question flags for the latest attempt by "student1" in quiz "Quiz 1"
    And I click on "Remove flag" "text"
    Then I should see "This question's flag is currently locked."
    And I should not see "Flag question"
    And I should see "Remove flag"
