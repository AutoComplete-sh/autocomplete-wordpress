<?php

//phpcs:disable VariableAnalysis
// There are "undefined" variables here because they're defined in the code that includes this file as a template.

?>
<?php if ( $type == 'plugin' ) : ?>
  <div class="updated" id="autocomplete_setup_prompt">
    <form name="autocomplete_activate" action="<?php echo esc_url( AutoComplete_Admin::get_page_url() ); ?>" method="POST">
      <div class="autocomplete_activate">
        <div class="aa_button_container">
          <div class="">
            <input type="submit" class="autocomplete-button" value="<?php esc_attr_e( 'Configure AutoComplete', 'autocomplete' ); ?>" />
          </div>
        </div>
        <div class="aa_description"><?php _e('<strong>Almost done</strong> - Configure AutoComplete and say goodbye to manually typing blog posts!', 'autocomplete');?></div>
      </div>
    </form>
  </div>
<?php elseif ( $type == 'notice' ) : ?>
  <div class="autocomplete-alert autocomplete-critical">
    <h3 class="autocomplete-key-status failed"><?php echo $notice_header; ?></h3>
    <p class="autocomplete-description">
        <?php echo $notice_text; ?>
    </p>
  </div>
<?php elseif ( $type == 'missing-functions' ) : ?>
  <div class="autocomplete-alert autocomplete-critical">
    <h3 class="autocomplete-key-status failed"><?php esc_html_e('Network functions are disabled.', 'autocomplete'); ?></h3>
    <p class="autocomplete-description"><?php printf( __('Your web host or server administrator has disabled PHP&#8217;s <code>gethostbynamel</code> function.  <strong>autocomplete cannot work correctly until this is fixed.</strong>  Please contact your web host or firewall administrator and give them <a href="%s" target="_blank">this information about autocomplete&#8217;s system requirements</a>.', 'autocomplete'), 'https://blog.autocomplete.com/autocomplete-hosting-faq/'); ?></p>
  </div>
<?php elseif ( $type == 'servers-be-down' ) : ?>
  <div class="autocomplete-alert autocomplete-critical">
    <h3 class="autocomplete-key-status failed"><?php esc_html_e("Your site can&#8217;t connect to the autocomplete servers.", 'autocomplete'); ?></h3>
    <p class="autocomplete-description"><?php printf( __('Your firewall may be blocking autocomplete from connecting to its API. Please contact your host and refer to <a href="%s" target="_blank">our guide about firewalls</a>.', 'autocomplete'), 'https://blog.autocomplete.com/autocomplete-hosting-faq/'); ?></p>
  </div>
<?php elseif ( $type == 'missing' ) : ?>
  <div class="autocomplete-alert autocomplete-critical">
    <h3 class="autocomplete-key-status failed"><?php esc_html_e( 'There is a problem with your API key.', 'autocomplete'); ?></h3>
    <p class="autocomplete-description"><?php printf( __('Please contact <a href="%s" target="_blank">Autocomplete.sh support</a> for assistance.', 'autocomplete'), 'https://autocomplete.com/contact/'); ?></p>
  </div>
<?php elseif ( $type == 'new-key-valid' ) : ?>
  <div class="autocomplete-alert autocomplete-active">
    <h3 class="autocomplete-key-status"><?php esc_html_e( 'AutoComplete is now ready. Scroll down to see how to get started. Happy blogging!', 'autocomplete' ); ?></h3>
  </div>
<?php elseif ( $type == 'new-key-invalid' ) : ?>
  <div class="autocomplete-alert autocomplete-critical">
    <h3 class="autocomplete-key-status"><?php esc_html_e( 'The key you entered is invalid. Please double-check it.' , 'autocomplete'); ?></h3>
  </div>
<?php elseif ( $type == 'existing-key-invalid' ) : ?>
  <div class="autocomplete-alert autocomplete-critical">
    <h3 class="autocomplete-key-status"><?php echo esc_html( __( 'Your Autocomplete.sh API key is no longer valid.', 'autocomplete' ) ); ?></h3>
    <p class="autocomplete-description">
        <?php

        echo wp_kses(
            sprintf(
            /* translators: The placeholder is a URL. */
                __( 'Please enter a new key or <a href="%s" target="_blank">Contact Autocomplete.sh support</a>.', 'autocomplete' ),
                constant('AUTOCOMPLETE_EMAIL_SUPPORT')
            ),
            array(
                'a' => array(
                    'href' => true,
                    'target' => true,
                ),
            )
        );

        ?>
    </p>
  </div>
<?php elseif ( $type == 'new-key-failed' ) : ?>
  <div class="autocomplete-alert autocomplete-critical">
    <h3 class="autocomplete-key-status"><?php esc_html_e( 'The API key you entered could not be verified.' , 'autocomplete'); ?></h3>
    <p class="autocomplete-description">
        <?php

        echo wp_kses(
            sprintf(
            /* translators: The placeholder is a URL. */
                __( 'The connection to autocomplete.com could not be established. Please refer to <a href="%s" target="_blank">our guide about firewalls</a> and check your server configuration.', 'autocomplete' ),
                'https://blog.autocomplete.com/autocomplete-hosting-faq/'
            ),
            array(
                'a' => array(
                    'href' => true,
                    'target' => true,
                ),
            )
        );

        ?>
    </p>
  </div>
<?php elseif ( $type == 'account-details-failed' ) : ?>
  <div class="autocomplete-alert autocomplete-critical">
    <h3 class="autocomplete-key-status"><?php esc_html_e( 'Unable to fetch account details. Please double-check your API Key and ensure the service is available.' , 'autocomplete'); ?></h3>
  </div>
<?php endif; ?>
