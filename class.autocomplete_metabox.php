<?php

class AutoComplete_Metabox {
    const CONFIG = array (
        'title' => 'AutoComplete',
        'prefix' => 'autocomplete_',
        'domain' => 'autocomplete',
        'class_name' => 'AutoComplete_Metabox',
        'post-type' =>
            array (
                0 => 'post',
            ),
        'context' => 'side',
        'priority' => 'default',
        'number_fields' => array (
            0 =>
                array (
                    'type' => 'number',
                    'class' => 'autocomplete-input number',
                    'label' => 'Tokens',
                    'step' => '1',
                    'default' => '512',
                    'min' => '1',
                    'max' => '2048',
                    'id' => 'autocomplete-tokens',
                ),
            1 =>
                array (
                    'type' => 'number',
                    'class' => 'autocomplete-input number',
                    'label' => 'Temperature',
                    'step' => '0.1',
                    'default' => '0.7',
                    'min' => '0.1',
                    'max' => '1.0',
                    'id' => 'autocomplete-temperature',
                ),
        ),
        'fields' => array(
            0 =>
                array (
                    'type' => 'checkbox',
                    'class' => 'autocomplete-input checkbox',
                    'label' => 'Optimize Readability',
                    'checked' => true,
                    'id' => 'autocomplete-readability',
                ),
            1 =>
                array (
                    'type' => 'submit',
                    'class' => 'autocomplete-input submit',

                    'default' => 'Submit',
                    'id' => 'autocomplete-submit',
                )
        )
    );

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_post' ] );
    }

    public function add_meta_boxes() {
        foreach ( self::CONFIG['post-type'] as $screen ) {
            add_meta_box(
                sanitize_title( self::CONFIG['title'] ),
                self::CONFIG['title'],
                [ $this, 'add_meta_box_callback' ],
                $screen,
                self::CONFIG['context'],
                self::CONFIG['priority']
            );
        }
    }

    public function save_post( $post_id ) {
        foreach ( self::CONFIG['fields'] as $field ) {
            switch ( $field['type'] ) {
                default:
                    if ( isset( $_POST[ $field['id'] ] ) ) {
                        $sanitized = sanitize_text_field( $_POST[ $field['id'] ] );
                        update_post_meta( $post_id, $field['id'], $sanitized );
                    }
            }
        }
    }

    public function add_meta_box_callback() { //form tag not allowed here..
        ?><div class="autocomplete-account-details autocomplete-field-group" style="flex-direction: column;">
            <div class="autocomplete-label">Username:</label> <span id="autocomplete-username">null</div>
            <div class="autocomplete-label">Balance:</label> <span id="autocomplete-balance">0</div>
        </div><hr class="autocomplete-hr"><?php

        $this->number_fields();

        $this->fields_div();

        ?><div class="autocomplete-hidden" id="autocomplete-job-output-container">
            <div id="autocomplete-job-cost">Cost: <span></span></div>
            <div id="autocomplete-job-input">Input: <span></span></div>
            <div id="autocomplete-job-output">Output: <span></span></div>
        </div><?php

        printf('</div>');
    }

    private function number_fields() {
        ?><div class="autocomplete-field-group"><?php
        foreach ( self::CONFIG['number_fields'] as $field ) {
            ?><div class="components-base-control">
            <div class="components-base-control__field autocomplete-field"><?php
                $this->label( $field );
                $this->field( $field );
                ?></div>
            </div><?php
        }
        ?></div><?php
    }

    private function fields_div() {
        foreach ( self::CONFIG['fields'] as $field ) {
            ?><div class="components-base-control">
            <div class="components-base-control__field autocomplete-field"><?php
                $this->label( $field );
                $this->field( $field );
                ?></div>
            </div><?php
        }
    }

    private function label( $field ) {
        switch ( $field['type'] ) {
            default:
                printf(
                    '<label class="components-base-control__label autocomplete-label" for="%s">%s</label>',
                    $field['id'], $field['label']
                );
        }
    }

    private function field( $field ) {
        switch ( $field['type'] ) {
            case 'number':
                $this->input_minmax( $field );
                break;
            case 'checkbox':
                $this->input_checkbox( $field );
                break;
            default:
                $this->input( $field );
        }
    }

    private function input( $field ) {
        printf(
            '<input class="components-text-control__input %s" id="%s" name="%s" %s type="%s" value="%s">',
            isset( $field['class'] ) ? $field['class'] : '',
            $field['id'], $field['id'],
            isset( $field['pattern'] ) ? "pattern='{$field['pattern']}'" : '',
            $field['type'],
            $this->value( $field )
        );
    }

    private function input_minmax( $field ) {
        printf(
            '<input class="components-text-control__input %s" id="%s" %s %s name="%s" %s type="%s" value="%s">',
            isset( $field['class'] ) ? $field['class'] : '',
            $field['id'],
            isset( $field['max'] ) ? "max='{$field['max']}'" : '',
            isset( $field['min'] ) ? "min='{$field['min']}'" : '',
            $field['id'],
            isset( $field['step'] ) ? "step='{$field['step']}'" : '',
            $field['type'],
            $this->value( $field )
        );
    }

    private function input_checkbox( $field ) {
         printf(
            '<input class="components-text-control__input %s" id="%s" name="%s" %s type="%s" value="%s" %s>',
            isset( $field['class'] ) ? $field['class'] : '',
            $field['id'], $field['id'],
            isset( $field['pattern'] ) ? "pattern='{$field['pattern']}'" : '',
            $field['type'],
            $this->value( $field ),
            isset( $field['checked'] ) && $field['checked'] ? 'checked' : ''
        );
    }

    private function value( $field ) {
        global $post;
        if ( metadata_exists( 'post', $post->ID, $field['id'] ) ) {
            $value = get_post_meta( $post->ID, $field['id'], true );
        } else if ( isset( $field['default'] ) ) {
            $value = $field['default'];
        } else {
            return '';
        }
        return str_replace( '\u0027', "'", $value );
    }

    public static function make() {
        return new AutoComplete_Metabox();
    }
}

