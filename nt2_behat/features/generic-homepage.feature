
Feature: Display a generic homepage for this group of sites
  In order to check the homepage and elements exist
  As a developer
  I need to visit the homepage and look for elements

Scenario: Check for a header and footer
  Given I am on the homepage
  Then the response status code should be 200
  And I should see an "#header" element
  And I should see an "#footer" element

#develop-this: in either header or footer of page(s)
#Scenario: Find a Facebook link somewhere on homepage
#  Given I am on the homepage
#  Then I should see some kind of link to Facebook

#develop-this: in either header or footer of page(s)
#Scenario: Find a Twitter link somewhere on homepage
#  Given I am on the homepage
#  Then I should see some kind of link to Twitter

#case sensitive?
#Scenario: Find the word 'cottages' somewhere on the homepage
#  Given I am on the homepage
#  Then I should see "cottages"

#case sensitive?
#Scenario: Find the word 'book' somewhere on the homepage
#  Given I am on the homepage
#  Then I should see "book"

#unfinished
#Scenario: Find a 'Search' submit button somewhere on the homepage
#  Given I am on the homepage
#  Then I should see a submit button containing the word "Search"
