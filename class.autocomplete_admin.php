<?php

class AutoComplete_Admin {
    const NONCE = 'autocomplete-update-key';

    private static $initiated = false;
    private static $notices   = array();

    public static function init() {
        if ( ! self::$initiated ) {
            self::init_hooks();
        }

        if ( isset( $_POST['action'] ) && $_POST['action'] == 'enter-key' ) {
            self::enter_api_key();
        }
    }

    public static function init_hooks() {
        self::$initiated = true;

        add_action( 'admin_init', array( 'AutoComplete_Admin', 'admin_init' ) );
        add_action( 'admin_menu', array( 'AutoComplete_Admin', 'admin_menu' ), 5 );
        add_action( 'admin_notices', array( 'AutoComplete_Admin', 'display_notice' ) );

        add_action( 'admin_enqueue_scripts', array( 'AutoComplete_Admin', 'load_resources' ) );
        add_filter( 'plugin_action_links', array( 'AutoComplete_Admin', 'plugin_action_links' ), 10, 2 );
        add_filter( 'plugin_action_links_'.plugin_basename( plugin_dir_path( __FILE__ ) . 'autocomplete.php'), array( 'AutoComplete_Admin', 'admin_plugin_settings_link' ) );
        add_filter( 'all_plugins', array( 'AutoComplete_Admin', 'modify_plugin_description' ) );
    }

    public static function admin_init() {

        $key_verified = false;
        if ($existing_key = constant('AUTOCOMPLETE_API_KEY')) {
            $key_verified = AutoComplete::check_key_status($existing_key);
        }

        if ( get_option( 'activated_autocomplete' ) ) {
            delete_option( 'activated_autocomplete' );

            if ( ! headers_sent() && $key_verified ) {
                wp_redirect( add_query_arg( array( 'page' => 'autocomplete-key-config', 'view' => 'start' ), class_exists( 'Jetpack' ) ? admin_url( 'admin.php' ) : admin_url( 'options-general.php' ) ) );
            }
        }

        load_plugin_textdomain( 'autocomplete' );
    }

    public static function admin_menu() {
        self::load_menu();
    }

    public static function admin_head() {
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }
    }

    public static function admin_plugin_settings_link( $links ) {
        $settings_link = '<a href="'.esc_url( self::get_page_url() ).'">'.__('Settings', 'autocomplete').'</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public static function load_menu() {
        $hook = add_options_page( __('autocomplete', 'autocomplete'), __('autocomplete', 'autocomplete'), 'manage_options', 'autocomplete-key-config', array( 'AutoComplete_Admin', 'display_page' ) );
        if ( $hook ) {
            add_action( "load-$hook", array( 'AutoComplete_Admin', 'admin_help' ) );
        }
    }

    public static function load_resources() {
        global $hook_suffix;

        if ( in_array( $hook_suffix, apply_filters( 'autocomplete_admin_page_hook_suffixes', array(
            'index.php', # dashboard
            'post.php',
            'settings_page_autocomplete-key-config',
            'plugins.php',
        ) ) ) ) {
            wp_register_style( 'autocomplete.css', plugin_dir_url( __FILE__ ) . '_inc/autocomplete.css', array(), AUTOCOMPLETE_VERSION );
            wp_enqueue_style( 'autocomplete.css');

            wp_register_script( 'autocomplete.js', plugin_dir_url( __FILE__ ) . '_inc/autocomplete.js', array('jquery'), AUTOCOMPLETE_VERSION );
            wp_enqueue_script( 'autocomplete.js' );
        }
    }

    /**
     * Add help to the autocomplete page
     *
     * @return false if not the autocomplete page
     */
    public static function admin_help() {
        $current_screen = get_current_screen();

        // Screen Content
        if ( current_user_can( 'manage_options' ) ) {
            if ( !AutoComplete::get_api_key() || ( isset( $_GET['view'] ) && $_GET['view'] == 'start' ) ) {
                //setup page
                $current_screen->add_help_tab(
                    array(
                        'id'		=> 'overview',
                        'title'		=> __( 'Overview' , 'autocomplete'),
                        'content'	=>
                            '<p><strong>' . esc_html__( 'Autocomplete Setup' , 'autocomplete') . '</strong></p>' .
                            '<p>' . esc_html__( constant("AUTOCOMPLETE_DESCRIPTION"), 'autocomplete') . '</p>' .
                            '<p>' . esc_html__( 'On this page, you are able to set up the AutoComplete plugin.' , 'autocomplete') . '</p>',
                    )
                );

                $current_screen->add_help_tab(
                    array(
                        'id'		=> 'setup-api-key',
                        'title'		=> __( 'New to AutoComplete' , 'autocomplete'),
                        'content'	=>
                            '<p><strong>' . esc_html__( 'AutoComplete Setup' , 'autocomplete') . '</strong></p>' .
                            '<p>' . esc_html__( 'You need to enter an Autocomplete API key to make use of this plugin on your site.' , 'autocomplete') . '</p>' .
                            '<p>' . sprintf( __( '%s to get an API Key.' , 'autocomplete'), '<a href="' . autocomplete_url('signup') . '" target="_blank">Sign up for an account</a>' ) . '</p>',
                    )
                );

                $current_screen->add_help_tab(
                    array(
                        'id'		=> 'setup-manual',
                        'title'		=> __( 'Enter an API Key' , 'autocomplete'),
                        'content'	=>
                            '<p><strong>' . esc_html__( 'AutoComplete Setup' , 'autocomplete') . '</strong></p>' .
                            '<p>' . esc_html__( 'If you already have an API key' , 'autocomplete') . '</p>' .
                            '<ol>' .
                            '<li>' . esc_html__( 'Copy and paste the API key into the text field.' , 'autocomplete') . '</li>' .
                            '<li>' . esc_html__( 'Click the \'Use this key\' button.' , 'autocomplete') . '</li>' .
                            '</ol>',
                    )
                );
            }

            else {
                //configuration page
                $current_screen->add_help_tab(
                    array(
                        'id'		=> 'overview',
                        'title'		=> __( 'Overview' , 'autocomplete'),
                        'content'	=>
                            '<p><strong>' . esc_html__( 'AutoComplete Configuration' , 'autocomplete') . '</strong></p>' .
                            '<p>' . esc_html__( constant('AUTOCOMPLETE_DESCRIPTION') , 'autocomplete') . '</p>' .
                            '<p>' . esc_html__( 'On this page, you are able to update your Autocomplete API Key.' , 'autocomplete') . '</p>',
                    )
                );

                $current_screen->add_help_tab(
                    array(
                        'id'		=> 'settings',
                        'title'		=> __( 'Settings' , 'autocomplete'),
                        'content'	=>
                            '<p><strong>' . esc_html__( 'AutoComplete Configuration' , 'autocomplete') . '</strong></p>' .
                            ( AutoComplete::predefined_api_key() ? '' : '<p><strong>' . esc_html__( 'AutoComplete API Key' , 'autocomplete') . '</strong> - ' . esc_html__( 'Enter/Delete an API Key.' , 'autocomplete') . '</p>' )
                    )
                );
            }
        }

        $current_screen->set_help_sidebar(
            '<p><strong>' . esc_html__( 'For more information:' , 'autocomplete') . '</strong></p>' .
            '<p><a href="' . autocomplete_url('documentation') . '" target="_blank">'     . esc_html__( 'Documentation' , 'autocomplete') . '</a></p>' .
            '<p><a href="mailto:' . constant('AUTOCOMPLETE_EMAIL_SUPPORT') .'" target="_blank">' . esc_html__( 'Support' , 'autocomplete') . '</a></p>'
        );
    }

    public static function enter_api_key() {
        if ( ! current_user_can( 'manage_options' ) ) {
            die( __( "Ah ah ah, you didn't say the magic word...", 'autocomplete' ) );
        }

        if ( !wp_verify_nonce( $_POST['_wpnonce'], self::NONCE ) )
            return false;

        if ( AutoComplete::predefined_api_key() ) {
            return false; //shouldn't have option to save key if already defined
        }

        $new_key = preg_replace( '/[^a-f0-9]/i', '', $_POST['key'] );
        $old_key = AutoComplete::get_api_key();

        if ( empty( $new_key ) ) {
            if ( !empty( $old_key ) ) {
                delete_option( 'autocomplete_api_key' );
                self::$notices[] = 'new-key-empty';
            }
        }
        elseif ( $new_key != $old_key ) {
            self::save_key( $new_key );
        }

        return true;
    }

    public static function save_key( $api_key ) {
        $key_status = AutoComplete::verify_key( $api_key );

        if ( $key_status == 'valid' ) {
            update_option( 'autocomplete_api_key', $api_key );
            update_option('autocomplete_key_verified', true);
            self::$notices['status'] = 'new-key-valid';
        }
        elseif ( in_array( $key_status, array( 'invalid', 'failed' ) ) ) {
            self::$notices['status'] = 'new-key-'.$key_status;
        }
    }

    public static function plugin_action_links( $links, $file ) {
        if ( $file == plugin_basename( plugin_dir_url( __FILE__ ) . '/autocomplete.php' ) ) {
            $links[] = '<a href="' . esc_url( self::get_page_url() ) . '">'.esc_html__( 'Settings' , 'autocomplete').'</a>';
        }

        return $links;
    }

    // Check connectivity between the WordPress blog and autocomplete's servers.
    // Returns an associative array of server IP addresses, where the key is the IP address, and value is true (available) or false (unable to connect).
    public static function check_server_ip_connectivity() {

        $servers = $ips = array();

        // Some web hosts may disable this function
        if ( function_exists('gethostbynamel') ) {

            $ips = gethostbynamel( constant("AUTOCOMPLETE_URL_API"));
            if ( $ips && is_array($ips) && count($ips) ) {
                $api_key = AutoComplete::get_api_key();

                foreach ( $ips as $ip ) {
                    $response = AutoComplete::verify_key( $api_key, $ip );
                    // even if the key is invalid, at least we know we have connectivity
                    if ( $response == 'valid' || $response == 'invalid' )
                        $servers[$ip] = 'connected';
                    else
                        $servers[$ip] = $response ? $response : 'unable to connect';
                }
            }
        }

        return $servers;
    }

    // Simpler connectivity check
    public static function check_server_connectivity($cache_timeout = 86400) {

        $debug = array();
        $debug[ 'PHP_VERSION' ]              = PHP_VERSION;
        $debug[ 'WORDPRESS_VERSION' ]        = $GLOBALS['wp_version'];
        $debug[ 'AUTOCOMPLETE_VERSION' ]     = AUTOCOMPLETE_VERSION;
        $debug[ 'AUTOCOMPLETE_PLUGIN_DIR' ]  = AUTOCOMPLETE_PLUGIN_DIR;
        $debug[ 'SITE_URL' ]                 = site_url();
        $debug[ 'HOME_URL' ]                 = home_url();

        $servers = get_option('autocomplete_available_servers');
        if ( (time() - get_option('autocomplete_connectivity_time') < $cache_timeout) && $servers !== false ) {
            $servers = self::check_server_ip_connectivity();
            update_option('autocomplete_available_servers', $servers);
            update_option('autocomplete_connectivity_time', time());
        }

        $response = wp_remote_get( constant('AUTOCOMPLETE_URL_API') );

        $debug[ 'gethostbynamel' ]  = function_exists('gethostbynamel') ? 'exists' : 'not here';
        $debug[ 'Servers' ]         = $servers;
        $debug[ 'Test Connection' ] = $response;

        AutoComplete::log( $debug );

        if ( $response && 'connected' == wp_remote_retrieve_body( $response ) )
            return true;

        return false;
    }

    public static function get_page_url( $page = 'config' ) {

        $args = array( 'page' => 'autocomplete-key-config' );

        if ( $page == 'delete_key' ) {
            $args = array('page' => 'autocomplete-key-config', 'view' => 'start', 'action' => 'delete-key', '_wpnonce' => wp_create_nonce(self::NONCE));
        }

        return add_query_arg( $args, class_exists( 'Jetpack' ) ? admin_url( 'admin.php' ) : admin_url( 'options-general.php' ) );
    }

    public static function display_alert() {
        AutoComplete::view( 'notice', array(
            'type' => 'alert',
            'code' => (int) get_option( 'autocomplete_alert_code' ),
            'msg'  => get_option( 'autocomplete_alert_msg' )
        ) );
    }

    public static function display_api_key_warning() {
        AutoComplete::view( 'notice', array( 'type' => 'plugin' ) );
    }

    public static function display_page() {
        if ( !AutoComplete::get_api_key() || ( isset( $_GET['view'] ) && $_GET['view'] == 'start' ) ) {
            self::display_start_page();
        } else {
            self::display_configuration_page();
        }
    }

    public static function display_start_page() {
        if ( isset( $_GET['action'] ) ) {
            if ( $_GET['action'] == 'delete-key' ) {
                if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], self::NONCE ) )
                    delete_option( 'autocomplete_api_key' );
                    update_option('autocomplete_key_verified', false);
            }
        }

        if ( $api_key = AutoComplete::get_api_key() && ( empty( self::$notices['status'] ) || 'existing-key-invalid' != self::$notices['status'] ) ) {
            self::display_configuration_page();
            return;
        }

        AutoComplete::view( 'start', array('notices' => self::$notices));
    }

    public static function display_configuration_page() {
        $api_key = AutoComplete::get_api_key();
        $details = [];

        if (!empty($api_key)) {
            $response = AutoComplete::api_fetch_account_details();
            if (!AutoComplete::is_response_ok($response, $details)) {
                self::$notices['alert'] = 'account-details-failed';
                 update_option('autocomplete_key_verified', false);
            } else {
                 if (!get_option('autocomplete_key_verified')) {
                     self::$notices['status'] = 'new-key-valid';
                 }
                 update_option('autocomplete_key_verified', true);
            }
        }

        AutoComplete::view( 'config', ['api_key' => $api_key, 'notices' => self::$notices, 'details' => $details]);
    }

   public static function display_notice() {
		global $hook_suffix;

		if ( in_array( $hook_suffix, array( 'jetpack_page_autocomplete-key-config', 'settings_page_autocomeplete-key-config' ) ) ) {
			return;
		}

		if ( ( 'plugins.php' === $hook_suffix ) && (! get_option('autocomplete_key_verified') ) ) {
			self::display_api_key_warning();
		}
	}

    /**
     * When autocomplete is active, remove the "Activate autocomplete" step from the plugin description.
     */
    public static function modify_plugin_description( $all_plugins ) {
        if ( isset( $all_plugins['autocomplete/autocomplete.php'] ) ) {
            if ( AutoComplete::get_api_key() ) {
                $all_plugins['autocomplete/autocomplete.php']['Description'] = __( 'Welcome to AutoComplete!', 'autocomplete' );
            }
            else {
                $all_plugins['autocomplete/autocomplete.php']['Description'] = __( 'Welcome to AutoComplete! To get started, just go to <a href="admin.php?page=autocomplete-key-config">your AutoComplete Settings page</a> to set up your API Key.', 'autocomplete' );
            }
        }

        return $all_plugins;
    }
}
