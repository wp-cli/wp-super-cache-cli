Feature: Generate cache

  Scenario: Manage wp-super-cache via CLI
    Given a WP install

    When I run `wp plugin install wp-super-cache`
    Then STDOUT should contain:
      """
      Plugin installed successfully.
      """
    And the wp-content/plugins/wp-super-cache directory should exist

    When I try `wp super-cache enable`
    Then STDERR should contain:
      """
      Error: WP Super Cache needs to be enabled to use its WP-CLI commands.
      """

    When I run `wp plugin activate wp-super-cache`
    And I run `wp super-cache enable`
    Then STDOUT should contain:
      """
      Success: The WP Super Cache is enabled.
      """
