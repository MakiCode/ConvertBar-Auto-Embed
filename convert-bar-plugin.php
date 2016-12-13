<?php
/*
Plugin Name: ConvertBar Auto Embed
Plugin URI: http://convertbar.com/plugin
Description: This plugin will automatically add the correct embed code to your site!
Version: 1.0
Author: Trenton Maki
Author URI: perpendicularsoftware.com
License: CC0
License URI: https://creativecommons.org/publicdomain/zero/1.0/
Tested up to: 4.2
Requires at least: 3.0.0
*/

function cb_is_embed_code_set() {
	return ! ! get_option( "convertbar_embed_id", false );
}

function convertbar_activation_redirect( $plugin ) {
	if ( $plugin == plugin_basename( __FILE__ ) ) {
		exit( wp_redirect( admin_url( "admin.php?page=convertbar" ) ) );
	}
}

function check_embed_code( $embedCode ) {
	$url    = "https://app.convertbar.com/check-embed-code?embed-code=" . urlencode( $embedCode );
	$remote = wp_remote_get( $url );
	$result = json_decode( $remote["body"], true );

	return $result["valid"];

}

function cb_add_embed_script() {
	wp_enqueue_script(
		'convertbar-script',
		"https://app.convertbar.com/embed/" . get_option( "convertbar_embed_id", "" ) . "/convertbar.js",
		array(),
		'1.0.0',
		true
	);
}

function cb_admin_notice() {
	global $pagenow;
	if ( ! ( ( $pagenow == 'admin.php' || $pagenow == 'tools.php' ) && ( $_GET['page'] == 'convertbar' ) ) && ! cb_is_embed_code_set() ) {
		?>
        <div class="notice notice-error is-dismissible"><p><a
                        href="<?php admin_url( "admin.php?page=convertbar" ) ?>">Please
                    add the ConvertBar embed code for your website</a></p></div>
		<?php
	}
}

function cb_show_convertbar_page() {
	$success = null;
	if ( cb_is_embed_code_set() ) {
		$success = true;
	}
	if ( array_key_exists( "convertbar-code", $_POST ) && is_string( $_POST["convertbar-code"] ) ) {
		if ( check_embed_code( $_POST["convertbar-code"] ) ) {
			update_option( "convertbar_embed_id", $_POST["convertbar-code"] );
			$success = true;
		} else {
			$success = false;
		}
	}
	$embedCode = get_option( "convertbar_embed_id", "" );

	include( "embed-page.php" );
}

function cb_add_admin_page() {
	add_submenu_page(
		'tools.php',
		'ConvertBar',
		'ConvertBar',
		'manage_options',
		'convertbar',
		'cb_show_convertbar_page'
	);
}

function cb_load_admin_style() {
	global $pagenow;

	if ( ( ( $pagenow == 'admin.php' || $pagenow == 'tools.php' ) && array_key_exists( 'page',
			$_GET ) && $_GET['page'] == 'convertbar' )
	) {
		wp_enqueue_style( 'convertbar_font_awesome', plugin_dir_url( __FILE__ ) . '/css/font-awesome.css', false,
			'1.0.0' );
		wp_enqueue_style( 'convertbar_css', plugin_dir_url( __FILE__ ) . '/css/styles.css', false, '1.0.0' );
	}
}


add_action( 'admin_enqueue_scripts', 'cb_load_admin_style' );
add_action( 'admin_notices', 'cb_admin_notice' );
add_action( 'wp_enqueue_scripts', 'cb_add_embed_script' );
add_action( 'activated_plugin', 'convertbar_activation_redirect' );
add_action( 'admin_menu', 'cb_add_admin_page' );
