<?php

class Autocomplete
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        self::$initiated = true;

        self::load_resources();

        add_action( "wp_ajax_text_generate", array( 'AutoComplete', 'ajax_text_generate' ) );
        add_action( "wp_ajax_fetch_account_details", array( 'AutoComplete', 'ajax_fetch_account_details' ) );

        AutoComplete_Metabox::make();
    }

    public static function load_resources() {
        global $pagenow;

        if (in_array($pagenow, ['post-new.php', 'post.php'])) {
            wp_register_style( 'autocomplete.css', plugin_dir_url( __FILE__ ) . '_inc/autocomplete.css', array(), AUTOCOMPLETE_VERSION );
            wp_enqueue_style( 'autocomplete.css');

            wp_register_script( 'autocomplete.js', plugin_dir_url( __FILE__ ) . '_inc/autocomplete.js', array('jquery'), AUTOCOMPLETE_VERSION );
            wp_enqueue_script( 'autocomplete.js' );
            wp_localize_script('autocomplete.js','ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

            wp_register_script( 'sweetalert.min.js', plugin_dir_url( __FILE__ ) . '_inc/sweetalert.min.js', array(), AUTOCOMPLETE_VERSION );
            wp_enqueue_script( 'sweetalert.min.js' );
        }
    }

    public static function predefined_api_key() {
        if ( defined( 'AUTOCOMPLETE_API_KEY' ) ) {
            return true;
        }

        return apply_filters( 'autocomplete_predefined_api_key', false );
    }

    public static function get_api_key()
    {
        return apply_filters('autocomplete_get_api_key', defined('AUTOCOMPLETE_API_KEY') ? constant('AUTOCOMPLETE_API_KEY') : get_option('autocomplete_api_key'));
    }

    /**
     * * Make a request to the Autocomplete API.
     * https://autocomplete.sh/documentation#api-access
     * @param $path
     * @param string $method
     * @param null $api_key
     * @param string $request_body
     * @return array
     */
    public static function api_call($path, $method='POST', $api_key=null, $request_body=[])
    {
        $key = $api_key ?? self::get_api_key();

        $http_args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $key,
            ),
            'timeout' => 100
        );

        $autocomplete_api_url = autocomplete_url($path, true);

        if(strtoupper($method) == 'POST') {
            $http_args['body'] = json_encode($request_body);
            $response = wp_remote_post($autocomplete_api_url, $http_args);
        } else {
            $http_args['body'] = http_build_query($request_body);
            $response = wp_remote_get($autocomplete_api_url, $http_args);
        }

        AutoComplete::log(compact('autocomplete_url', 'http_args', 'response'));

        if (is_wp_error($response)) {
            do_action('autocomplete_https_request_failure', $response);
            return array('', $response);
        }

        return array($response['headers'], $response['body'], true);
    }

    public static function get_response_body($response)
    {
        if ($response && isset($response[1]) && is_string($response[1])) {
            if (is_array($body = json_decode($response[1], true))) {
                return $body;
            }
        }

        return [];
    }

    public static function is_response_ok($response, &$body=null)
    {
        $body = self::get_response_body($response);

        return array_key_exists( 'status', $body) && $body['status'] == 200;
    }

    public static function api_fetch_account_details()
    {
        return self::api_call('account', 'GET');
    }

    public static function api_text_generate($request_data)
    {
        return self::api_call('engines/textgen/completion', 'POST', null, $request_data);
    }

    public static function ajax_text_generate() {
        $request_data = [
            'input' => (string)array_get($_POST, 'input'),
            'output_tokens' => (int)array_get($_POST, 'output_tokens'),
            'optimize_readability' => (bool)array_get($_POST, 'optimize_readability'),
            'temperature' => (float)array_get($_POST, 'temperature'),
        ];

        $validators = [
            'input' => function ($data) {
                return !empty($data) && is_string($data);
            },
            'output_tokens' => function ($data) {
                return !empty($data) && is_numeric($data) && $data >= 1 && $data <= 2048;
            },
            'optimize_readability' => function ($data) {
                return !empty($data) && is_bool($data);
            },
            'temerature' => function ($data) {
                return !empty($data) && is_float($data) && $data >= 0.1 && $data <= 1.0;
            }
        ];

        $errors = validate_data($request_data, $validators);

        if (!empty($errors)) {
            return array('', json_encode(['status' => 400, 'message' => implode(', ', $errors)]));
        }

        $response = self::api_text_generate($request_data);

        $body = self::get_response_body($response);

        echo json_encode($body);

        wp_die();
    }

    public static function ajax_fetch_account_details() {
        $response = self::api_fetch_account_details();

        $body = self::get_response_body($response);

        if (isset($body['balance'])) {
            $body['balance'] = number_format($body['balance']);
        }

        echo json_encode($body);

        wp_die();
    }

    public static function check_key_status($api_key)
    {
        $response = self::api_call('account', 'GET', $api_key);

        return self::is_response_ok($response);
    }

    public static function verify_key($key, $ip = null)
    {
        return self::check_key_status($key, $ip) ? 'valid' : 'invalid';
    }

    public static function is_test_mode()
    {
        return defined('AUTOCOMPLETE_TEST_MODE') && AUTOCOMPLETE_TEST_MODE;
    }

    public static function get_ip_address()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    private static function get_user_agent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    private static function get_referer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    // return a comma-separated list of role names for the given user
    public static function get_user_roles($user_id)
    {
        $roles = false;

        if (!class_exists('WP_User'))
            return false;

        if ($user_id > 0) {
            $comment_user = new WP_User($user_id);
            if (isset($comment_user->roles))
                $roles = join(',', $comment_user->roles);
        }

        if (is_multisite() && is_super_admin($user_id)) {
            if (empty($roles)) {
                $roles = 'super_admin';
            } else {
                $comment_user->roles[] = 'super_admin';
                $roles = join(',', $comment_user->roles);
            }
        }

        return $roles;
    }

    public static function _cmp_time($a, $b)
    {
        return $a['time'] > $b['time'] ? -1 : 1;
    }

    public static function _get_microtime()
    {
        $mtime = explode(' ', microtime());
        return $mtime[1] + $mtime[0];
    }

    private static function bail_on_activation($message, $deactivate = true)
    {
        ?>
      <!doctype html>
      <html>
      <head>
        <meta charset="<?php bloginfo('charset'); ?>"/>
        <style>
          * {
            text-align: center;
            margin: 0;
            padding: 0;
            font-family: "Lucida Grande", Verdana, Arial, "Bitstream Vera Sans", sans-serif;
          }

          p {
            margin-top: 1em;
            font-size: 18px;
          }
        </style>
      </head>
      <body>
      <p><?php echo esc_html($message); ?></p>
      </body>
      </html>
        <?php
        if ($deactivate) {
            $plugins = get_option('active_plugins');
            $autocomplete = plugin_basename(AUTOCOMPLETE_PLUGIN_DIR . 'autocomplete.php');
            $update = false;
            foreach ($plugins as $i => $plugin) {
                if ($plugin === $autocomplete) {
                    $plugins[$i] = false;
                    $update = true;
                }
            }

            if ($update) {
                update_option('active_plugins', array_filter($plugins));
            }
        }
        exit;
    }

    public static function view($name, array $args = array())
    {
        $args = apply_filters('autocomplete_view_arguments', $args, $name);

        foreach ($args AS $key => $val) {
            $$key = $val;
        }

        load_plugin_textdomain('autocomplete');

        $file = AUTOCOMPLETE_PLUGIN_DIR . 'views/' . $name . '.php';

        include($file);
    }

    /**
     * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
     * @static
     */
    public static function plugin_activation()
    {
        if (version_compare($GLOBALS['wp_version'], AUTOCOMPLETE_MINIMUM_WP_VERSION, '<')) {
            load_plugin_textdomain('autocomplete');

            $message = '<strong>' . sprintf(esc_html__('Autocomplete %s requires WordPress %s or higher.', 'autocomplete'), AUTOCOMPLETE_VERSION, AUTOCOMPLETE_MINIMUM_WP_VERSION) . '</strong> ' . sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version. </a>.', 'autocomplete'), 'https://codex.wordpress.org/Upgrading_WordPress');

            AutoComplete::bail_on_activation($message);
        } elseif (!empty($_SERVER['SCRIPT_NAME']) && false !== strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php')) {
            add_option('activated_autocomplete', true);
        }
    }

    /**
     * Removes all connection options
     * @static
     */
    public static function plugin_deactivation()
    {
        delete_option('autocomplete_api_key');
        delete_option('autocomplete_key_verified');
        delete_option('activated_autocomplete');
    }

    /**
     * Essentially a copy of WP's build_query but one that doesn't expect pre-urlencoded values.
     *
     * @param array $args An array of key => value pairs
     * @return string A string ready for use as a URL query string.
     */
    public static function build_query($args)
    {
        return _http_build_query($args, '', '&');
    }

    /**
     * Log debugging info to the error log.
     *
     * Enabled when WP_DEBUG_LOG is enabled (and WP_DEBUG, since according to
     * core, "WP_DEBUG_DISPLAY and WP_DEBUG_LOG perform no function unless
     * WP_DEBUG is true), but can be disabled via the akismet_debug_log filter.
     *
     * @param mixed $autocomplete_debug The data to log.
     */
    public static function log($autocomplete_debug)
    {
        if (apply_filters('autocomplete_debug_log', defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && defined('AUTOCOMPLETE_DEBUG') && AUTOCOMPLETE_DEBUG)) {
            error_log(print_r(compact('autocomplete_debug'), true));
        }
    }
}
