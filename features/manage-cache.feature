Feature: Generate cache

  Scenario: Manage wp-super-cache via CLI
    Given a WP install

    When I try `wp super-cache status`
    Then STDERR should contain:
      """
      Error: WP Super Cache needs to be installed to use its WP-CLI commands.
      """

    When I run `wp plugin install wp-super-cache`
    Then STDOUT should contain:
      """
      Plugin installed successfully.
      """
    And the wp-content/plugins/wp-super-cache directory should exist

    When I try `wp super-cache enable`
    Then STDERR should contain:
      """
      Error: WP Super Cache needs to be activated to use its WP-CLI commands.
      """

    When I run `wp plugin activate wp-super-cache`
    And I run `wp super-cache enable`
    Then STDOUT should contain:
      """
      Success: The WP Super Cache is enabled.
      """

    When I run `wp super-cache flush`
    Then STDOUT should contain:
      """
      Success: Cache cleared.
      """

    When I run `wp post create --post_title='Test post' --post_status=publish --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp super-cache flush --post_id={POST_ID}`
    Then STDOUT should contain:
      """
      Success: Post cache cleared.
      """

    When I try `wp super-cache flush --post_id=invalid`
    Then STDERR should contain:
      """
      Error: This is not a valid post id.
      """
