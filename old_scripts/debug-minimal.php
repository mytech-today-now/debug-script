<?php
/**
 * Minimal WordPress Debug Tool - No Security Restrictions
 * Use this version if you're getting blank pages
 * 
 * Upload and access: https://mytech.today/debug-minimal.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to load WordPress
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    dirname(__DIR__) . '/wp-load.php',
    dirname(dirname(__DIR__)) . '/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $wp_load_path) {
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('WordPress not found. Please move this file to your WordPress root directory.');
}

// Simple debug output - no security checks
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Debug Tool - Minimal Version</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 1000px; margin: 0 auto; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .code { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 WordPress Debug Tool - Minimal Version</h1>
        
        <div class="success">
            <strong>✅ WordPress Loaded Successfully!</strong><br>
            This minimal version has no security restrictions and should work for basic debugging.
        </div>

        <h2>📊 Basic WordPress Information</h2>
        <div class="code">
            <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?><br>
            <strong>Site URL:</strong> <?php echo home_url(); ?><br>
            <strong>Admin URL:</strong> <?php echo admin_url(); ?><br>
            <strong>Theme:</strong> <?php echo wp_get_theme()->get('Name'); ?><br>
            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
        </div>

        <h2>👤 Current User</h2>
        <?php
        $current_user = wp_get_current_user();
        if ($current_user->ID) {
            echo '<div class="info">';
            echo '<strong>Logged in as:</strong> ' . esc_html($current_user->display_name) . ' (ID: ' . $current_user->ID . ')<br>';
            echo '<strong>Email:</strong> ' . esc_html($current_user->user_email) . '<br>';
            echo '<strong>Roles:</strong> ' . implode(', ', $current_user->roles) . '<br>';
            echo '<strong>Can manage options:</strong> ' . (current_user_can('manage_options') ? 'Yes' : 'No') . '<br>';
            echo '</div>';
        } else {
            echo '<div class="info">Not logged in. <a href="' . wp_login_url() . '">Login here</a></div>';
        }
        ?>

        <h2>🔌 Active Plugins</h2>
        <div class="code">
            <?php
            $active_plugins = get_option('active_plugins');
            if (!empty($active_plugins)) {
                foreach ($active_plugins as $plugin) {
                    echo esc_html($plugin) . '<br>';
                }
            } else {
                echo 'No active plugins';
            }
            ?>
        </div>

        <h2>🌐 HTTP Test</h2>
        <?php
        $test_url = 'https://api.wordpress.org/core/version-check/1.7/';
        $response = wp_remote_get($test_url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 5px;">';
            echo '<strong>❌ HTTP Request Failed:</strong> ' . esc_html($response->get_error_message());
            echo '</div>';
        } else {
            $code = wp_remote_retrieve_response_code($response);
            echo '<div class="success">';
            echo '<strong>✅ HTTP Request Successful:</strong> Response code ' . $code;
            echo '</div>';
        }
        ?>

        <h2>📄 Current Page Info</h2>
        <div class="code">
            <?php
            global $post;
            if ($post) {
                echo '<strong>Post ID:</strong> ' . $post->ID . '<br>';
                echo '<strong>Post Type:</strong> ' . $post->post_type . '<br>';
                echo '<strong>Post Status:</strong> ' . $post->post_status . '<br>';
                echo '<strong>Post Title:</strong> ' . esc_html($post->post_title) . '<br>';
            } else {
                echo 'No post data available (this is normal for the debug tool page)';
            }
            ?>
        </div>

        <div class="info">
            <h3>🎯 Next Steps</h3>
            <p>If this minimal version works, the issue with debug.php was likely:</p>
            <ul>
                <li><strong>Security restrictions:</strong> The full debug tool requires administrator login</li>
                <li><strong>wp_die() function:</strong> Can sometimes display as blank page</li>
                <li><strong>Theme conflicts:</strong> Some themes interfere with wp_die() output</li>
            </ul>
            
            <p><strong>To use the full debug tool:</strong></p>
            <ol>
                <li>Make sure you're logged in as an administrator</li>
                <li>Try the updated debug.php file (fixed wp_die issue)</li>
                <li>Or use this minimal version for basic debugging</li>
            </ol>
        </div>

        <hr>
        <p style="text-align: center; color: #6c757d;">
            <strong>WordPress Debug Tool - Minimal Version</strong><br>
            <a href="<?php echo home_url(); ?>">← Return to Site</a> | 
            <a href="debug.php">Try Full Debug Tool</a>
        </p>
    </div>
</body>
</html>
