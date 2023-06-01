<?php
/**
 * Plugin Name: Local Theme Install
 * Description: This plugin allows you to install a theme from a local folder.
 * Version: 1.0
 * Author: Aamir Mukhtar
 * Author URI: https://kickstarters.co
 */

add_action( 'admin_menu', function() {
    add_menu_page( 'Local Theme Install', 'Local Theme Install', 'manage_options', 'local-theme-install', function() {
        ?>
        <h2>Local Theme Install</h2>
        <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
            <?php wp_nonce_field( 'local_theme_install' ); ?>
            <input type="text" name="theme_folder" placeholder="Theme Folder">
            <input type="submit" value="Install Theme">
        </form>
        <?php
    } );
} );

add_action( 'wp_ajax_local_theme_install', function() {
    // Check for nonce
    if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'local_theme_install' ) ) {
        wp_send_json_error();
        return;
    }

    // Get theme folder
    $theme_folder = $_REQUEST['theme_folder'];

    // Check if theme folder exists
    if ( ! file_exists( $theme_folder ) ) {
        wp_send_json_error();
        return;
    }

    // Copy theme folder to wp-content/themes
    $destination = WP_CONTENT_DIR . '/themes';
    wp_mkdir_p( $destination );
    copy_folder( $theme_folder, $destination );

    // Activate theme
    $theme = get_theme_by_path( $destination );
    $active_theme = get_option( 'active_theme' );
    update_option( 'active_theme', $theme );

    // Redirect to dashboard
    wp_redirect( admin_url( '/' ) );
    exit;
} );

function copy_folder( $source, $destination ) {
    if ( is_dir( $source ) ) {
        if ( ! is_dir( $destination ) ) {
            mkdir( $destination, 0755, true );
        }

        $objects = scandir( $source );
        foreach ( $objects as $object ) {
            if ( $object != '.' && $object != '..' ) {
                copy_folder( "$source/$object", "$destination/$object" );
            }
        }
    } else {
        copy( $source, $destination );
    }
}