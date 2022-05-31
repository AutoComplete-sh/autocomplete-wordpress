<?php
/**
 * @package autocomplete
 */
/*
Plugin Name:  AutoComplete.sh
Plugin URI:   https://www.autocomplete.sh
Description:  Make your blog posts intelligent with AutoComplete's NLP text generation API.
Version:      1.0
Author:       AutoComplete.sh
Author URI:   https://github.com/AutoComplete-sh
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  autocomplete
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2022 AutoComplete.sh.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'This plugin cannot be called directly';
    exit;
}

define( 'AUTOCOMPLETE_VERSION', '1.0.0-'.time() );
define( 'AUTOCOMPLETE_MINIMUM_WP_VERSION', '5.0' );
define( 'AUTOCOMPLETE_DESCRIPTION', 'Make your blog posts intelligent with AutoComplete\'s NLP text generation API');
define( 'AUTOCOMPLETE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AUTOCOMPLETE_URL', 'https://autocomplete.sh');
define( 'AUTOCOMPLETE_ATTRIBUTION', '?r=ac-wp-plugin&cs=ac-wp-plugin&crs=1653005781');
define( 'AUTOCOMPLETE_URL_API', 'https://api.autocomplete.sh/v1');
define( 'AUTOCOMPLETE_EMAIL_SUPPORT', 'help@autocomplete.sh');

require_once(AUTOCOMPLETE_PLUGIN_DIR . 'helpers.php');

register_activation_hook( __FILE__, array( 'AutoComplete', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'AutoComplete', 'plugin_deactivation' ) );

require_once( AUTOCOMPLETE_PLUGIN_DIR . 'class.autocomplete.php' );
require_once( AUTOCOMPLETE_PLUGIN_DIR . 'class.autocomplete_metabox.php' );

add_action( 'init', array( 'AutoComplete', 'init' ) );

if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
    require_once( AUTOCOMPLETE_PLUGIN_DIR . 'class.autocomplete_admin.php' );
    add_action( 'init', array( 'AutoComplete_Admin', 'init' ) );
}
