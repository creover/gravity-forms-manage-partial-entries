<?php

/**
 * Plugin Name: Gravity Forms Manage Partial Entries
 * Plugin URI: http://www.ryanprejean.com/
 * Description: Allows continuation of partial entries
 * Version: 1.0.0
 * Author: Ryan Prejean
 * Author URI: http://www.ryanprejean.com/
 * License: GPL2
 */
 
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Version of the plugin
define( 'GF_Manage_PE_Version', '1.0.0' );
//Minimum Wordpress Version
define( 'GF_Manage_PE__MINIMUM_WP_VERSION', '4.5' );
//Minimum Wordpress Version
define( 'GF_Manage_PE__MINIMUM_GF_VERSION', '1.9' );
//Minimum Wordpress Version
define( 'GF_Manage_PE__MINIMUM_PE_VERSION', '1.0' );
//Plugin path
define( 'GF_Manage_PE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, 'gf_manage_pe_activation' );


require_once( GF_Manage_PE__PLUGIN_DIR . 'includes/class-continue-partial-entries.php' );
require_once( GF_Manage_PE__PLUGIN_DIR . 'includes/class-reset-partial-entries.php' );

add_action( 'init', array( 'Continue_Partial_Entries', 'init' ) );
add_action( 'init', array( 'Reset_Partial_Entries', 'init' ) );

function gf_manage_pe_activation() {
    if ( version_compare( $GLOBALS['wp_version'], GF_Manage_PE__MINIMUM_WP_VERSION, '<' ) ) {
			
        $message = '<strong>'.sprintf('Gravity Forms Manage Partial Entries %s requires WordPress %s or higher.', GF_Manage_PE_Version, GF_Manage_PE__MINIMUM_WP_VERSION ).'</strong> '.sprintf('Please <a href="%1$s">upgrade WordPress</a> to a current version.', 'https://codex.wordpress.org/Upgrading_WordPress');

        bail_on_activation( $message );
    }
    
    // make sure we're running the required minimum version of Gravity Forms
    if( ! property_exists( 'GFForms', 'version' ) || ! version_compare( GFForms::$version, GF_Manage_PE__MINIMUM_GF_VERSION, '>=' ) ) {
        $message = '<strong>'.sprintf('Gravity Forms Manage Partial Entries %s requires Gravity Forms %s or higher.', GF_Manage_PE_Version, GF_Manage_PE__MINIMUM_GF_VERSION ).'</strong> '.sprintf('Please <a href="%1$s">upgrade Gravity Forms</a> to a current version.', 'https://www.gravityhelp.com/');

        bail_on_activation( $message );
    }
    
    // make sure we're running the required minimum version of Gravity Forms Partial Entries
    if( ! class_exists( 'GF_Partial_Entries' ) || ! version_compare( GF_PARTIAL_ENTRIES_VERSION, GF_Manage_PE__MINIMUM_PE_VERSION, '>=' ) ) {
        $message = '<strong>'.sprintf('Gravity Forms Manage Partial Entries %s requires Gravity Forms Partial Entries %s or higher.', GF_Manage_PE_Version, GF_Manage_PE__MINIMUM_PE_VERSION ).'</strong> '.sprintf('Please <a href="%1$s">upgrade Gravity Forms Partial Entries</a> to a current version.', 'https://www.gravityhelp.com/');

        bail_on_activation( $message );
    }
}

function bail_on_activation( $message, $deactivate = true ) {
?>
<!doctype html>
<html>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<style>
* {
	text-align: center;
	margin: 0;
	padding: 0;
	font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
}
p {
	margin-top: 1em;
	font-size: 18px;
}
</style>
<body>
<p><?php echo esc_html( $message ); ?></p>
</body>
</html>
<?php
    if ( $deactivate ) {
        $plugins = get_option( 'active_plugins' );
        $gfmanage = plugin_basename( GF_Manage_PE__PLUGIN_DIR . 'gravity-forms-manage-partial-entries.php' );
        $update  = false;
        foreach ( $plugins as $i => $plugin ) {
            if ( $plugin === $gfmanage ) {
                $plugins[$i] = false;
                $update = true;
            }
        }

        if ( $update ) {
            update_option( 'active_plugins', array_filter( $plugins ) );
        }
    }
    exit;
}