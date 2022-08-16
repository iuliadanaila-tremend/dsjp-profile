@api
Feature: SKOS feature
  In order to work with vocabularies
  As a site administrator
  I should be able to see SKOS entities

  Background:
    Given I am logged in as a user with the "administer site configuration, administer skos concept scheme entities, administer skos concept entities" permissions

  @skos
  Scenario: SKOS Concept Scheme listing
    Given I go to "the SKOS concept scheme administration page"
    Then I should see "Fruit" in the "http://example.com/fruit" row
    And I should see "Vegetables" in the "http://example.com/vegetables" row
    Then I click "Fruit"
    And I should see the link "Citrus fruit"
    And I should see the link "Exotic fruit"

  @skos
  Scenario: SKOS Concept listing
    Given I go to "the SKOS concept administration page"
    Then I should see "Apple" in the "http://example.com/fruit/apple" row
    And I should see "Potato" in the "http://example.com/vegetables/potato" row
    Then I click "Citrus fruit"
    And I should see "Citrus fruit ALT"
    And I should see "Citrus fruit HIDDEN"
    And I should see "lemons, oranges, limes, mandarines, grapefruit, satsumas"
    And I should see the link "Fruit"
