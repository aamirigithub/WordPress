<?php
/**
 * Plugin Name: Local Theme Install
 * Description: This plugin allows you to install a theme from a local folder or from a GitHub repository.
 * Version: 1.2
 * Author: Bard
 * Author URI: https://bard.ai
 */

add_action( 'admin_menu', function() {
    add_menu_page( 'Local Theme Install', 'Local Theme Install', 'manage_options', 'local-theme-install', function() {
        ?>
        <h2>Local Theme Install</h2>
        <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
            <?php wp_nonce_field( 'local_theme_install' ); ?>
            <input type="radio" name="theme_source" value="local" checked> Local Folder
            <input type="radio" name="theme_source" value="github"> GitHub
            <br>
            <input type="text" name="theme_folder" placeholder="Theme Folder" disabled>
            <input type="text" name="theme_url" placeholder="Theme URL">
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

    // Get theme source
    $theme_source = $_REQUEST['theme_source'];

    // Get theme folder or URL
    if ( $theme_source === 'local' ) {
        $theme_folder = $_REQUEST['theme_folder'];
    } else {
        $theme_url = $_REQUEST['theme_url'];
    }

    // Check if theme folder exists
    if ( $theme_source === 'local' && ! file_exists( $theme_folder ) ) {
        wp_send_json_error();
        return;
    }

    // Check if theme URL exists
    if ( $theme_source === 'github' && ! filter_var( $theme_url, FILTER_VALIDATE_URL ) ) {
        wp_send_json_error();
        return;
    }

    // Get theme repository
    if ( $theme_source === 'github' ) {
        $theme_repo = parse_url( $theme_url, PHP_URL_HOST );
    }

    // Get theme name
    $theme_name = basename( $theme_folder );
    if ( $theme_source === 'github' ) {
        $theme_name = str_replace( '-', '_', $theme_repo );
    }

    // Create theme directory
    $destination = WP_CONTENT_DIR . '/themes';
    wp_mkdir_p( $destination );

    // Download theme from URL
    if ( $theme_source === 'github' ) {
        $command = "wget -O $destination/$theme_name.zip $theme_url";
        exec( $command );
    }

    // Unzip theme
    if ( $theme_source === 'github' ) {
        $command = "unzip $destination/$theme_name.zip -d $destination";
        exec( $command );
    }

    // Remove theme zip file
    if ( $theme_source === 'github' ) {
        unlink( $destination . '/' . $theme_name . '.zip' );
    }

    // Activate theme
    $theme = get_theme_by_path( $destination . '/' . $theme_name );
    $active_theme = get_option( 'active_theme' );
    update_option( 'active_theme', $theme );
});