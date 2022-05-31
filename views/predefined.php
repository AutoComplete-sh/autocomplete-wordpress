<div class="autocomplete-box">
  <h2><?php esc_html_e( 'Manual Configuration', 'autocomplete' ); ?></h2>
  <p>
      <?php
      /* translators: %s is the wp-config.php file */
      echo sprintf( esc_html__( 'An autocomplete API key has been defined in the %s file for this site.', 'autocomplete' ), '<code>wp-config.php</code>' );
      ?>
  </p>
</div>