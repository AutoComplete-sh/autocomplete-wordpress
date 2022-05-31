<div id="autocomplete-plugin-container">
    <?php if ( ! empty( $notices ) ) { ?>
        <?php foreach ( $notices as $notice ) { ?>
            <?php AutoComplete::view( 'notice', ['type' => $notice ] ); ?>
        <?php } ?>
    <?php } ?>
  <div class="autocomplete-masthead">
    <div class="autocomplete-masthead__inside-container">
      <div class="autocomplete-masthead__logo-container">
        <p><a id="autocomplete-logo-text" href="<?php echo autocomplete_url(); ?>" target="_blank">./AutoComplete.sh</a></p>
      </div>
    </div>
  </div>
  <div class="autocomplete-lower">
    <div class="autocomplete-boxes">
        <?php
        AutoComplete::view( 'activate' );
        ?>
    </div>
  </div>
</div>