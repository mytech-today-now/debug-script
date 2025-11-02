<?php
/**
 * Simple WordPress Debug Test
 * Use this to diagnose blank page issues
 * 
 * Upload and access: https://mytech.today/debug-simple.php
 */

// Force error reporting and output
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Immediate output to test if PHP is working
echo "STEP 1: PHP is working<br>";
flush();

// Test basic PHP functionality
echo "STEP 2: PHP Version: " . PHP_VERSION . "<br>";
echo "STEP 3: Current file: " . __FILE__ . "<br>";
echo "STEP 4: Current directory: " . __DIR__ . "<br>";
flush();

// Check if WordPress functions exist (without loading WordPress yet)
if (function_exists('wp')) {
    echo "STEP 5: WordPress already loaded<br>";
} else {
    echo "STEP 5: WordPress not loaded, will attempt to load<br>";
}
flush();

// Try to find WordPress
echo "STEP 6: Looking for WordPress...<br>";
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    dirname(__DIR__) . '/wp-load.php',
    dirname(dirname(__DIR__)) . '/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',
];

$wp_found = false;
foreach ($wp_load_paths as $path) {
    echo "STEP 6a: Checking path: " . htmlspecialchars($path) . "<br>";
    if (file_exists($path)) {
        echo "STEP 6b: Found wp-load.php at: " . htmlspecialchars($path) . "<br>";
        $wp_found = true;
        $wp_load_path = $path;
        break;
    }
}
flush();

if (!$wp_found) {
    echo "STEP 7: WordPress not found in any expected location<br>";
    echo "STEP 8: This is likely why you're getting blank pages<br>";
    echo "<h2>Solution:</h2>";
    echo "<p>Move this file to the same directory as wp-config.php</p>";
    echo "<p>Your WordPress installation should be in one of these locations:</p>";
    echo "<ul>";
    foreach ($wp_load_paths as $path) {
        echo "<li>" . htmlspecialchars($path) . "</li>";
    }
    echo "</ul>";
    exit;
}

// Try to load WordPress
echo "STEP 7: Attempting to load WordPress from: " . htmlspecialchars($wp_load_path) . "<br>";
flush();

try {
    require_once $wp_load_path;
    echo "STEP 8: WordPress file included successfully<br>";
    flush();
} catch (Exception $e) {
    echo "STEP 8: Error loading WordPress: " . htmlspecialchars($e->getMessage()) . "<br>";
    exit;
}

// Check if WordPress functions are now available
if (function_exists('wp')) {
    echo "STEP 9: WordPress functions are available<br>";
} else {
    echo "STEP 9: WordPress loaded but functions not available<br>";
    exit;
}
flush();

// Test WordPress functions
echo "STEP 10: Testing WordPress functions...<br>";

if (function_exists('home_url')) {
    try {
        $home_url = home_url();
        echo "STEP 10a: Home URL: " . htmlspecialchars($home_url) . "<br>";
    } catch (Exception $e) {
        echo "STEP 10a: Error getting home URL: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}
flush();

// Check current user
if (function_exists('wp_get_current_user')) {
    try {
        $current_user = wp_get_current_user();
        if ($current_user->ID) {
            echo "STEP 11: User logged in: " . htmlspecialchars($current_user->display_name) . " (ID: " . $current_user->ID . ")<br>";
            
            if (function_exists('current_user_can')) {
                $can_manage = current_user_can('manage_options');
                echo "STEP 12: Can manage options: " . ($can_manage ? "YES" : "NO") . "<br>";
                
                if (!$can_manage) {
                    echo "STEP 13: User does not have admin privileges<br>";
                    echo "<h2>Solution:</h2>";
                    echo "<p>You need to be logged in as an administrator to use the debug tool.</p>";
                    echo "<p><a href='" . wp_login_url() . "'>Login as Administrator</a></p>";
                    exit;
                }
            }
        } else {
            echo "STEP 11: No user logged in<br>";
            echo "STEP 12: This might be why you're getting blank pages<br>";
            echo "<h2>Solution:</h2>";
            echo "<p>You need to be logged in as an administrator to use the debug tool.</p>";
            echo "<p><a href='" . wp_login_url() . "'>Login as Administrator</a></p>";
            exit;
        }
    } catch (Exception $e) {
        echo "STEP 11: Error checking user: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}
flush();

echo "STEP 13: All checks passed - WordPress is working correctly<br>";
echo "<h2>✅ Success!</h2>";
echo "<p>WordPress is loaded and you have the correct permissions.</p>";
echo "<p>The debug.php file should work now. The blank page issue was likely due to:</p>";
echo "<ul>";
echo "<li>WordPress not being found in the expected location, OR</li>";
echo "<li>Not being logged in as an administrator, OR</li>";
echo "<li>Security checks failing silently</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Try debug.php again:</strong> <a href='debug.php'>Click here to test debug.php</a></li>";
echo "<li><strong>If still blank:</strong> The issue is in the debug.php file itself</li>";
echo "<li><strong>If working:</strong> The issue was with file location or permissions</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Debug Simple Test Complete</strong><br>";
echo "Generated: " . date('Y-m-d H:i:s') . "<br>";
echo "<a href='" . home_url() . "'>← Return to Site</a></p>";
?>
