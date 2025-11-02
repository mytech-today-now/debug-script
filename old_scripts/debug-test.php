<?php
/**
 * WordPress Debug Tool - Diagnostic Version
 * Use this to diagnose why debug.php is returning 500 error
 * 
 * Upload this file and access: https://mytech.today/debug-test.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<html><head><title>WordPress Debug Tool - Diagnostics</title></head><body>";
echo "<h1>🔍 WordPress Debug Tool - Diagnostic Test</h1>";
echo "<div style='font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa;'>";

echo "<h2>📋 Step 1: Basic PHP Information</h2>";
echo "<p>✅ PHP Version: " . PHP_VERSION . "</p>";
echo "<p>✅ Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p>✅ Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p>✅ Script Path: " . __FILE__ . "</p>";
echo "<p>✅ Current Directory: " . __DIR__ . "</p>";

echo "<h2>🔍 Step 2: WordPress Detection</h2>";

// Check if WordPress is already loaded
if (function_exists('wp')) {
    echo "<p>✅ WordPress already loaded!</p>";
} else {
    echo "<p>⚠️ WordPress not loaded, attempting to find installation...</p>";
    
    // Try to find WordPress installation
    $wp_load_paths = [
        __DIR__ . '/wp-load.php',                    // Same directory
        dirname(__DIR__) . '/wp-load.php',           // Parent directory  
        dirname(dirname(__DIR__)) . '/wp-load.php',  // Grandparent directory
        $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',  // Document root
    ];
    
    echo "<h3>🔍 Searching for wp-load.php:</h3>";
    echo "<ul>";
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $wp_load_path) {
        $exists = file_exists($wp_load_path);
        $readable = $exists ? is_readable($wp_load_path) : false;
        
        echo "<li>";
        echo "<strong>Path:</strong> " . htmlspecialchars($wp_load_path) . "<br>";
        echo "<strong>Exists:</strong> " . ($exists ? "✅ Yes" : "❌ No") . "<br>";
        echo "<strong>Readable:</strong> " . ($readable ? "✅ Yes" : "❌ No") . "<br>";
        
        if ($exists && $readable && !$wp_loaded) {
            echo "<strong>Status:</strong> 🔄 Attempting to load...<br>";
            
            try {
                // Capture any output during WordPress loading
                ob_start();
                require_once $wp_load_path;
                $load_output = ob_get_clean();
                
                if (function_exists('wp')) {
                    echo "<strong>Result:</strong> ✅ WordPress loaded successfully!<br>";
                    $wp_loaded = true;
                    
                    if (!empty($load_output)) {
                        echo "<strong>Load Output:</strong><pre style='background:#f0f0f0;padding:10px;'>" . htmlspecialchars($load_output) . "</pre>";
                    }
                } else {
                    echo "<strong>Result:</strong> ❌ File loaded but WordPress functions not available<br>";
                    if (!empty($load_output)) {
                        echo "<strong>Load Output:</strong><pre style='background:#f0f0f0;padding:10px;'>" . htmlspecialchars($load_output) . "</pre>";
                    }
                }
            } catch (Exception $e) {
                echo "<strong>Result:</strong> ❌ Error loading: " . htmlspecialchars($e->getMessage()) . "<br>";
            } catch (Error $e) {
                echo "<strong>Result:</strong> ❌ Fatal error: " . htmlspecialchars($e->getMessage()) . "<br>";
            }
        } else {
            echo "<strong>Status:</strong> ⏭️ Skipped<br>";
        }
        echo "</li><br>";
    }
    echo "</ul>";
}

echo "<h2>🔧 Step 3: WordPress Status</h2>";

if (function_exists('wp')) {
    echo "<p>✅ WordPress functions available</p>";
    
    // Test basic WordPress functions
    if (function_exists('home_url')) {
        try {
            $home_url = home_url();
            echo "<p>✅ Home URL: " . htmlspecialchars($home_url) . "</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error getting home URL: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    if (function_exists('wp_get_current_user')) {
        try {
            $current_user = wp_get_current_user();
            if ($current_user->ID) {
                echo "<p>✅ Current User: " . htmlspecialchars($current_user->display_name) . " (ID: " . $current_user->ID . ")</p>";
                echo "<p>✅ User Roles: " . implode(', ', $current_user->roles) . "</p>";
                
                if (function_exists('current_user_can')) {
                    $can_manage = current_user_can('manage_options');
                    echo "<p>✅ Can Manage Options: " . ($can_manage ? "Yes" : "No") . "</p>";
                }
            } else {
                echo "<p>⚠️ No user logged in</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error getting current user: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    if (defined('ABSPATH')) {
        echo "<p>✅ WordPress ABSPATH: " . htmlspecialchars(ABSPATH) . "</p>";
    }
    
    if (function_exists('wp_remote_get')) {
        echo "<p>✅ WordPress HTTP API available</p>";
    }
    
} else {
    echo "<p>❌ WordPress functions not available</p>";
    echo "<p>🔧 <strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Ensure debug-test.php is in the same directory as wp-config.php</li>";
    echo "<li>Check that wp-load.php exists in your WordPress installation</li>";
    echo "<li>Verify file permissions (644 for debug-test.php)</li>";
    echo "<li>Check your hosting provider's directory structure</li>";
    echo "</ul>";
}

echo "<h2>📁 Step 4: File System Information</h2>";

// Show directory contents
$current_dir_files = scandir(__DIR__);
echo "<h3>Files in current directory (" . __DIR__ . "):</h3>";
echo "<ul>";
foreach ($current_dir_files as $file) {
    if ($file !== '.' && $file !== '..') {
        $file_path = __DIR__ . '/' . $file;
        $is_dir = is_dir($file_path);
        $is_readable = is_readable($file_path);
        echo "<li>" . htmlspecialchars($file) . " " . ($is_dir ? "(directory)" : "(file)") . " - " . ($is_readable ? "readable" : "not readable") . "</li>";
    }
}
echo "</ul>";

// Check parent directory
$parent_dir = dirname(__DIR__);
if ($parent_dir !== __DIR__) {
    $parent_files = scandir($parent_dir);
    echo "<h3>Files in parent directory (" . $parent_dir . "):</h3>";
    echo "<ul>";
    foreach ($parent_files as $file) {
        if ($file !== '.' && $file !== '..' && (strpos($file, 'wp-') === 0 || $file === 'index.php' || $file === 'wp-config.php')) {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
    }
    echo "</ul>";
}

echo "<h2>🎯 Step 5: Recommendations</h2>";

if (!function_exists('wp')) {
    echo "<div style='background:#fff3cd;border:1px solid #ffeaa7;padding:15px;border-radius:5px;'>";
    echo "<h3>⚠️ WordPress Not Loaded</h3>";
    echo "<p><strong>To fix the 500 error:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Move files to WordPress root:</strong> Upload both debug-test.php and debug.php to the same directory as wp-config.php</li>";
    echo "<li><strong>Check file permissions:</strong> Ensure files have 644 permissions</li>";
    echo "<li><strong>Verify WordPress installation:</strong> Make sure wp-load.php exists and is readable</li>";
    echo "<li><strong>Contact hosting support:</strong> If issues persist, your hosting provider can help locate the correct WordPress directory</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background:#d4edda;border:1px solid #c3e6cb;padding:15px;border-radius:5px;'>";
    echo "<h3>✅ WordPress Loaded Successfully</h3>";
    echo "<p>WordPress is working correctly. The debug.php file should now work properly.</p>";
    echo "<p><a href='debug.php' style='color:#007bff;'>→ Try debug.php now</a></p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align:center;color:#6c757d;'>";
echo "<strong>WordPress Debug Tool Diagnostics</strong><br>";
echo "Generated: " . date('Y-m-d H:i:s') . "<br>";
echo "<a href='https://mytech.today/'>← Return to Site</a>";
echo "</p>";

echo "</div></body></html>";
?>
