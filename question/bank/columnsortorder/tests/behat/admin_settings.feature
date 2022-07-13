@qbank @qbank_columnsortorder @javascript
Feature: Set default question bank column order, pinning and size
  In order to set sensible defaults for the question bank interface
  As an admin
  I want to hide, reorder, resize and pin columns

  Scenario: Admin can reorder question bank columns
    Given I log in as "admin"
    When I navigate to "Plugins > Question bank plugins > Column sort order" in site administration
    And I drag "Created by" "qbank_columnsortorder > column move handle" and I drop it in "T" "qbank_columnsortorder > column header"
    Then "Created by" "qbank_columnsortorder > column header" should appear before "T" "qbank_columnsortorder > column header"
    And I reload the page
    And "Created by" "qbank_columnsortorder > column header" should appear before "T" "qbank_columnsortorder > column header"

  Scenario: Custom fields are reorderable
    Given I log in as "admin"
    When I navigate to "Plugins > Question bank plugins > Question custom fields" in site administration
    And I press "Add a new category"
    And I click on "Add a new custom field" "link"
    And I follow "Checkbox"
    And I set the following fields to these values:
      | Name       | checkboxcustomcolumn |
      | Short name | chckcust             |
    And I press "Save changes"
    Then I should see "checkboxcustomcolumn"
    And I navigate to "Plugins > Question bank plugins > Column sort order" in site administration
    And I should see "checkboxcustomcolumn"
    And I change the window size to "large"
    Then "checkboxcustomcolumn" "text" should appear after "Created by" "text"
    And I drag "checkboxcustomcolumn" "qbank_columnsortorder > column move handle" and I drop it in "Created by" "qbank_columnsortorder > column header"
    And I reload the page
    Then "checkboxcustomcolumn" "qbank_columnsortorder > column header" should appear before "Created by" "qbank_columnsortorder > column header"
    And I click on "Manage question bank plugins" "link"
    And I click on "Disable" "link" in the "Question custom fields" "table_row"
    And I click on "Column sort order" "link"
    Then "Currently disabled question bank plugins:" "text" should appear before "chckcust" "text"
    And I click on "Manage question bank plugins" "link"
    And I click on "Enable" "link" in the "Question custom fields" "table_row"
    And I click on "Column sort order" "link"
    Then I should not see "Currently disabled question bank plugins:"
    And I should see "checkboxcustomcolumn"

  Scenario: Disabling a question bank plugin removes its columns
    Given I log in as "admin"
    When I navigate to "Plugins > Question bank plugins > Column sort order" in site administration
    And I should see "Created by"
    And I click on "Manage question bank plugins" "link"
    And I click on "Disable" "link" in the "View creator" "table_row"
    And I click on "Column sort order" "link"
    Then "Currently disabled question bank plugins:" "text" should appear before "Created by" "text"
    And I click on "Manage question bank plugins" "link"
    And I click on "Enable" "link" in the "View creator" "table_row"
    And I click on "Column sort order" "link"
    Then I should not see "Currently disabled question bank plugins:"
    And I should see "Created by"

  Scenario: Admin can hide a column in site administration page
    Given I log in as "admin"
    And I navigate to "Plugins > Question bank plugins > Column sort order" in site administration
    And I should see "Created by" in the "T" "table_row"
    And I press "Show/Hide column"
    And I set the field "Created by" to "0"
    Then I should not see "Created by" in the "Actions" "table_row"
    And I reload the page
    And I should not see "Created by" in the "Actions" "table_row"

  Scenario: Show/Hide list is keyboard accessible
    Given I log in as "admin"
    And I navigate to "Plugins > Question bank plugins > Column sort order" in site administration
    And I press "Show/Hide column"
    And the field "T" matches value "1"
    And I should see "T" in the "Actions" "table_row"
    When I press the down key
    And the focused element is "T" "checkbox"
    And I press the enter key
    Then the field "T" matches value "0"
    And I should not see "T" in the "Actions" "table_row"
    And I press the space key
    And the field "T" matches value "1"
    And I should see "T" in the "Actions" "table_row"
    And I press the up key
    And the focused element is "Modified by" "checkbox"

  Scenario: Admin can resize a column in site administration page using modal dialog
    Given I log in as "admin"
    And I navigate to "Plugins > Question bank plugins > Column sort order" in site administration
    And the "style" attribute of "Action" "qbank_columnsortorder > column header" should not contain "width: 480px"
    When I click on "Action" "qbank_columnsortorder > column resize handle"
    And I set the field "Column width (pixels)" to "480"
    And I press "Save changes"
    Then the "style" attribute of "Action" "qbank_columnsortorder > column header" should contain "width: 480px"
