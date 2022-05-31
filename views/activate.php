<div class="autocomplete-box">
    <?php AutoComplete::view( 'title' ); ?>
    <?php AutoComplete::view( 'setup' );?>
</div>
<br/>
<div class="autocomplete-box">
    <?php AutoComplete::view( 'enter' );?>
</div>
<br/>
<div class="autocomplete-card">
    <div class="autocomplete-section-header">
        <div class="autocomplete-section-header__label">
            <span><?php esc_html_e( 'Check out the free demo!' , 'autocomplete'); ?></span>
        </div>
    </div>
    <div class="inside">
        <div class="autocomplete-example">
            <ul style="margin-top:0;">
                <li>Witness the power of AutoComplete by visiting <a href="<?php echo autocomplete_url('#demo'); ?>" target="_blank">the free demo.</a></li>
                <li>Test out the API as much as you like, return when you are ready to wield it.</li>
            </ul>
            <?php printf('<img src="%s" class="autocomplete-example-image">', plugins_url( '../_inc/img/demo_1.png', __FILE__ )); ?>
        </div>
    </div>
</div>

