<?php

class Reset_Partial_Entries {
    private static $add_script;
    private static $redirect;
    private static $initiated = false;
    
    public static function init() {
        if ( ! self::$initiated ) {
            self::init_hooks();
        }
    }
    
    public static function init_hooks() {
        self::$initiated = true;
        add_shortcode('partial_reset', array(__CLASS__, 'partial_reset_shortcode'));
        add_action('wp_print_footer_scripts', array(__CLASS__, 'add_reset_button_footer'));
        add_action('wp_ajax_disable_partial_entries', array(__CLASS__, 'disable_partial_entries'));
    }
    static function partial_reset_shortcode($atts) {
        self::$add_script = true;
        $a = shortcode_atts( array(
            'formid' => '',
            'redirect' => '',
        ), $atts );
        self::$redirect = $a['redirect'];
        
        $output = '<a class="button" onclick="ResetPartialEntries(' . $a['formid'] . ')">Reset</a>';
        
        return $output;
    }
    
    static function add_reset_button_footer(){
        if ( self::$add_script ) {
            $success = ( empty(self::$redirect) ? "alert(response)" : "window.location.replace('" . self::$redirect . "')");
            ?>
            <script type="text/javascript" >
                function ResetPartialEntries(form_id) {
                    var data = {
                        'action': 'disable_partial_entries',
                        'form_id': form_id
                    };
                    
                    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                
                    jQuery.post(ajaxurl, data, function(response) {
                        <?php echo $success; ?>;
                    });
                }
            </script> <?php
        }
    } 
    
    static function disable_partial_entries() {
        global $wpdb; // this is how you get access to the database 

        $form_id = intval( $_POST['form_id'] );
        Continue_Partial_Entries::clear_partial_entries( $form_id );

        echo 'Success';

        wp_die(); // this is required to terminate immediately and return a proper response
    }
}