CLI interface for the WP Super Cache
--------------------------------------------------
This repository contains a [WP-CLI plugin](https://github.com/wp-cli/wp-cli)  for the [WP Super Cache Wordpress plugin](https://wordpress.org/plugins/wp-super-cache/).  After installing this plugin, a Wordpress administrator will have access to a `wp super-cache` command

    $ wp super-cache
    usage: wp super-cache disable 
       or: wp super-cache enable 
       or: wp super-cache flush [--post_id=<post-id>] [--permalink=<permalink>]
       or: wp super-cache preload [--status] [--cancel]
       or: wp super-cache status 
    
    See 'wp help super-cache <command>' for more information on a specific command.

Installing
--------------------------------------------------
For instructions on installing this, and other, WP-CLI community packages, read the [Community Packages](https://github.com/wp-cli/wp-cli/wiki/Community-Packages) section of the WP-CLI Wiki.