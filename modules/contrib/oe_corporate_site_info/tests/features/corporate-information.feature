@api
Feature: Corporate information
  In order to be able to set and assert corporate information in my Behat tests
  As a developer
  I want to make sure that the steps provided by this module work correctly.

  Scenario: Set and assert site owner.
    Given I set the site owner to "Directorate-General for Informatics"
    Then the site owner should be set to "Directorate-General for Informatics"

  Scenario: Set and assert the site default content owner.
    Given I set the site default content owner to "Directorate-General for Informatics"
    Then the site default content owner should be set to "Directorate-General for Informatics"
