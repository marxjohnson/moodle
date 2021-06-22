@qbank @qbank_managecategories @category_reorder @javascript
Feature: A teacher can reorder question categories
  In order to change question category order
  As a teacher
  I need to reorder them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | weeks  |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category 1 | 0        | CAT1     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "system role assigns" exist:
      | user     | role           | contextlevel |
      | teacher1 | editingteacher | System       |
    And the following "question categories" exist:
      | contextlevel | reference | name                   | idnumber     |
      | Course       | C1        | Course category 1      | questioncat1 |
      | Course       | C1        | Course category 2      | questioncat2 |
      | Course       | C1        | Course category 3      | questioncat3 |
      | Category     | CAT1      | Default for Category 1 |              |
      | System       | S1        | System category        |              |
    And I am on the "Course 1" "core_question > course question categories" page logged in as "teacher1"

  Scenario: Teacher cannot move or delete single category under context
    When I click on "Edit" "text" in the "Default for Category 1" "list_item"
    Then I should not see "Delete"

  Scenario: Teacher can see complete edit menu if multiples categories exist under context
    When I click on "Edit" "text" in the "Course category 1" "list_item"
    Then I should see "Edit settings"
    And I should see "Delete"
    And I should see "Export as Moodle XML"

  Scenario: Teacher can reorder categories
    Given "Course category 1" "text" should appear before "Default for Category 1" "text"
    Given "Course category 2" "text" should appear before "System category" "text"
    And I open the action menu in "Course category 1" "list_item"
    And I choose "Move" in the open action menu
    And I click on "After Default for Category 1" "link" in the "Move Course category 1" "dialogue"
    Then "Course category 1" "text" should appear after "Default for Category 1" "text"
    And I open the action menu in "Course category 2" "list_item"
    And I choose "Move" in the open action menu
    And I click on "After System category" "link" in the "Move Course category 2" "dialogue"
    Then "Course category 2" "text" should appear after "System category" "text"

  Scenario: Teacher can display and hide category descriptions
    When I click on "Show descriptions" "checkbox"
    Then I should see "The default category for questions shared in context 'Category 1'."
    And I click on "Show descriptions" "checkbox"
    And I should not see "The default category for questions shared in context 'Category 1'."

  Scenario: Teacher cannot create a duplicate idnumber within a context by moving a category
    Given "Course category 1" "text" should appear before "System category" "text"
    And I open the action menu in "Course category 1" "list_item"
    And I choose "Move" in the open action menu
    And I click on "After System category" "link" in the "Move Course category 1" "dialogue"
    Then "Course category 1" "text" should appear after "System category" "text"
    And I click on "Edit" "text" in the "Course category 2" "list_item"
    And I choose "Edit settings" in the open action menu
    And I set the field "ID number" to "questioncat1"
    And I click on "Edit category" "button" in the "Edit category" "dialogue"
    And I open the action menu in "Course category 2" "list_item"
    And I choose "Move" in the open action menu
    And I click on "After System category" "link" in the "Move Course category 2" "dialogue"
    And I should see "ID number already in use, please change it to move or update category"
