Feature: Generate cache

  Scenario: Manage wp-super-cache via cli
    Given a WP install

    When I run `wp plugin install wp-super-cache`
    Then STDOUT should contain:
      """
      Downloading install
      """
    And STDOUT should contain:
      """
      Plugin installed successfully.
      """
    And the wp-content/plugins/wp-super-cache directory should exist

    When I run `wp package install wojsmol/wp-super-cache-cli:tov2`
    Then STDOUT should contain:
      """
      Success: Package installed.
      """

     When I run `wp super-cache enable`
     Then STDOUT should contain:
      """
      Success: The WP Super Cache is enabled.
      """
