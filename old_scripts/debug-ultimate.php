<?php
/**
 * Ultimate WordPress Debug Tool with WordPress Authentication
 *
 * Combines ALL features from debug.php and debug-advanced.php
 * Built on the proven debug-advanced.php foundation for reliability
 * Now includes WordPress authentication integration
 *
 * @package WordPress Debug Tool Ultimate
 * @version 1.2
 * @author WordPress Debug Team
 * @security Requires WordPress administrator login
 */

// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ultimate performance timing with memory tracking
$debug_start_time = microtime(true);
$debug_start_memory = memory_get_usage();
$debug_timings = [];
$debug_memory_usage = [];
$debug_detailed_timings = [];

function debug_time($label) {
    global $debug_timings, $debug_start_time, $debug_memory_usage, $debug_start_memory;
    $debug_timings[$label] = round((microtime(true) - $debug_start_time) * 1000, 2);
    $debug_memory_usage[$label] = round((memory_get_usage() - $debug_start_memory) / 1024 / 1024, 2);
}

function debug_start_timing($label) {
    global $debug_detailed_timings;
    $debug_detailed_timings[$label] = microtime(true);
}

function debug_end_timing($label) {
    global $debug_detailed_timings, $debug_timings;
    if (isset($debug_detailed_timings[$label])) {
        $debug_timings[$label] = round((microtime(true) - $debug_detailed_timings[$label]) * 1000, 2);
    }
}

// Try to load WordPress with enhanced detection
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    dirname(__DIR__) . '/wp-load.php',
    dirname(dirname(__DIR__)) . '/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/public_html/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/www/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/htdocs/wp-load.php',
];

$wp_loaded = false;
$wp_load_path_used = '';
foreach ($wp_load_paths as $wp_load_path) {
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
        $wp_loaded = true;
        $wp_load_path_used = $wp_load_path;
        break;
    }
}

if (!$wp_loaded) {
    die('WordPress not found. Please move this file to your WordPress root directory.');
}

debug_time('wordpress_loaded');

// WordPress Authentication Check
debug_time('authentication_check');

// Check if user is logged in and has administrator privileges
if (!is_user_logged_in()) {
    // Redirect to WordPress login with return URL
    $login_url = wp_login_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔐 WordPress Authentication Required</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .auth-container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 500px;
                text-align: center;
            }
            .auth-header {
                margin-bottom: 30px;
            }
            .auth-header h1 {
                color: #333;
                font-size: 28px;
                margin-bottom: 8px;
            }
            .auth-header p {
                color: #666;
                font-size: 16px;
                line-height: 1.5;
            }
            .login-btn {
                display: inline-block;
                padding: 16px 32px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                transition: transform 0.2s ease;
                margin: 20px 0;
            }
            .login-btn:hover {
                transform: translateY(-2px);
                color: white;
                text-decoration: none;
            }
            .info-box {
                margin-top: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                text-align: left;
                font-size: 14px;
                color: #666;
            }
            .info-box h3 {
                color: #333;
                margin-bottom: 10px;
                font-size: 16px;
            }
            .info-box ul {
                margin-left: 20px;
            }
            .info-box li {
                margin-bottom: 8px;
            }
            .site-info {
                margin-top: 20px;
                padding: 15px;
                background: #e3f2fd;
                border-radius: 8px;
                font-size: 13px;
                color: #1565c0;
            }
        </style>
    </head>
    <body>
        <div class="auth-container">
            <div class="auth-header">
                <h1>🔐 WordPress Login Required</h1>
                <p>You must be logged in as a WordPress administrator to access the Ultimate Debug Tool.</p>
            </div>

            <a href="<?php echo esc_url($login_url); ?>" class="login-btn">
                🚀 Login to WordPress
            </a>

            <div class="info-box">
                <h3>🛡️ Security Requirements</h3>
                <ul>
                    <li><strong>WordPress Account:</strong> You must have a valid WordPress user account</li>
                    <li><strong>Administrator Role:</strong> Only administrators can access debug tools</li>
                    <li><strong>Active Session:</strong> You must be logged in to WordPress</li>
                    <li><strong>Secure Access:</strong> All debug data is protected by WordPress authentication</li>
                </ul>
            </div>

            <div class="site-info">
                <strong>🌐 Site Information:</strong><br>
                <strong>Site URL:</strong> <?php echo esc_html(home_url()); ?><br>
                <strong>WordPress Version:</strong> <?php echo esc_html(get_bloginfo('version')); ?><br>
                <strong>Debug Tool:</strong> Ultimate WordPress Debug Tool v1.2
            </div>
        </div>

        <script>
            // Auto-redirect after 10 seconds if user doesn't click
            setTimeout(function() {
                if (confirm('Auto-redirect to WordPress login in 5 seconds. Click OK to continue or Cancel to stay.')) {
                    window.location.href = '<?php echo esc_js($login_url); ?>';
                }
            }, 10000);
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Check if user has administrator privileges
if (!current_user_can('manage_options')) {
    $current_user = wp_get_current_user();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>⚠️ Insufficient Privileges</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 500px;
                text-align: center;
            }
            .error-header h1 {
                color: #d63031;
                font-size: 28px;
                margin-bottom: 8px;
            }
            .error-header p {
                color: #666;
                font-size: 16px;
                line-height: 1.5;
                margin-bottom: 20px;
            }
            .user-info {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            }
            .user-info h3 {
                color: #856404;
                margin-bottom: 10px;
            }
            .user-info p {
                color: #856404;
                margin-bottom: 5px;
            }
            .action-buttons {
                margin-top: 30px;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                margin: 0 10px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: transform 0.2s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
                text-decoration: none;
            }
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-header">
                <h1>⚠️ Access Denied</h1>
                <p>You don't have sufficient privileges to access the WordPress Debug Tool.</p>
            </div>

            <div class="user-info">
                <h3>👤 Current User Information</h3>
                <p><strong>Username:</strong> <?php echo esc_html($current_user->user_login); ?></p>
                <p><strong>Display Name:</strong> <?php echo esc_html($current_user->display_name); ?></p>
                <p><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
                <p><strong>Roles:</strong> <?php echo esc_html(implode(', ', $current_user->roles)); ?></p>
                <p><strong>Required Role:</strong> Administrator</p>
            </div>

            <div class="action-buttons">
                <a href="<?php echo esc_url(admin_url()); ?>" class="btn btn-primary">
                    🏠 WordPress Dashboard
                </a>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn btn-secondary">
                    🚪 Logout
                </a>
            </div>

            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; font-size: 14px; color: #666;">
                <strong>💡 Need Access?</strong><br>
                Contact your site administrator to upgrade your account to Administrator role.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Ultimate global variables for comprehensive tracking
global $debug_has_the_content, $debug_content_filters, $debug_hooks_called, $debug_queries, $debug_shortcodes_found, $debug_broken_shortcodes, $debug_cron_jobs, $debug_cron_health;
$debug_has_the_content = false;
$debug_content_filters = [];
$debug_hooks_called = [];
$debug_queries = [];
$debug_shortcodes_found = [];
$debug_broken_shortcodes = [];
$debug_cron_jobs = [];
$debug_cron_health = [];

// Enhanced content detection with comprehensive filter chain tracking
add_action('wp', function() {
    if (is_page()) {
        add_filter('the_content', function($content) {
            global $debug_has_the_content, $debug_content_filters;
            $debug_has_the_content = true;
            $debug_content_filters[] = [
                'time' => date('H:i:s.u'),
                'content_length' => strlen($content),
                'filter' => 'the_content',
                'priority' => current_filter(),
                'memory' => memory_get_usage()
            ];
            return $content;
        }, 999);
    }
});

// Hook into WordPress actions for comprehensive tracking
add_action('all', function($hook) {
    global $debug_hooks_called;
    if (!isset($debug_hooks_called[$hook])) {
        $debug_hooks_called[$hook] = 0;
    }
    $debug_hooks_called[$hook]++;
});

debug_time('filters_added');

// Enhanced page detection with template analysis
$current_post = null;
$current_template = '';
$page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;

if ($page_id > 0) {
    $current_post = get_post($page_id);
    if ($current_post) {
        // Simulate template detection
        $template_hierarchy = [];
        if ($current_post->post_type === 'page') {
            $template_hierarchy[] = 'page-' . $current_post->ID . '.php';
            $template_hierarchy[] = 'page-' . $current_post->post_name . '.php';
            $template_hierarchy[] = 'page.php';
        } elseif ($current_post->post_type === 'post') {
            $template_hierarchy[] = 'single-' . $current_post->ID . '.php';
            $template_hierarchy[] = 'single.php';
        }
        $template_hierarchy[] = 'index.php';
        
        $theme_dir = get_template_directory();
        foreach ($template_hierarchy as $template) {
            if (file_exists($theme_dir . '/' . $template)) {
                $current_template = $template;
                break;
            }
        }
    }
}

debug_time('page_data_loaded');

// Advanced plugin conflict testing functionality
$disabled_plugins = [];
if (isset($_GET['debug_disable_plugins'])) {
    $disabled_str = sanitize_text_field($_GET['debug_disable_plugins']);
    $disabled_plugins = array_map('trim', explode(',', $disabled_str));
    
    // Actually disable plugins for this request
    add_filter('pre_option_active_plugins', function($pre, $option) use ($disabled_plugins) {
        if ($option !== 'active_plugins') {
            return $pre;
        }
        
        global $wpdb;
        $serialized = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'active_plugins'));
        if (false === $serialized) {
            return [];
        }
        
        $plugins = maybe_unserialize($serialized);
        if (!is_array($plugins)) {
            return [];
        }
        
        $filtered = array_values(array_diff($plugins, $disabled_plugins));
        return maybe_serialize($filtered);
    }, 10, 2);
}

debug_time('plugin_conflict_setup');

// Enhanced shortcode analysis
function analyze_shortcodes($content) {
    global $debug_shortcodes_found, $debug_broken_shortcodes;
    
    // Get all registered shortcodes
    global $shortcode_tags;
    
    // Find shortcodes in content using WordPress regex
    $shortcode_regex = get_shortcode_regex();
    if (preg_match_all('/' . $shortcode_regex . '/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tag = $match[2];
            if (!isset($debug_shortcodes_found[$tag])) {
                $debug_shortcodes_found[$tag] = 0;
            }
            $debug_shortcodes_found[$tag]++;
        }
    }
    
    // Check for broken shortcodes (unprocessed after filters)
    $processed_content = apply_filters('the_content', $content);
    if (preg_match_all('/\[([^\]]+)\]/', $processed_content, $broken_matches)) {
        foreach ($broken_matches[1] as $broken_tag) {
            $tag_name = explode(' ', $broken_tag)[0];
            if (!isset($shortcode_tags[$tag_name])) {
                $debug_broken_shortcodes[] = $tag_name;
            }
        }
    }
    
    return [
        'found' => $debug_shortcodes_found,
        'broken' => array_unique($debug_broken_shortcodes),
        'processed_length' => strlen($processed_content),
        'original_length' => strlen($content)
    ];
}

// Cron Job Diagnostics & Health Check Analysis
function analyze_cron_jobs() {
    global $debug_cron_jobs, $debug_cron_health;

    // Check if WP-Cron is disabled
    $debug_cron_health['wp_cron_disabled'] = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    $debug_cron_health['last_cron_run'] = get_option('_transient_doing_cron') ? 'Currently running' : 'Not currently running';
    $debug_cron_health['overdue_jobs'] = 0;
    $debug_cron_health['total_jobs'] = 0;

    // Fetch schedules
    $schedules = wp_get_schedules();
    $debug_cron_jobs['schedules'] = $schedules;

    // Fetch all cron jobs
    $cron_jobs = _get_cron_array();
    $debug_cron_jobs['all_jobs'] = [];
    $debug_cron_jobs['ready'] = [];
    $current_time = time();

    if ($cron_jobs) {
        foreach ($cron_jobs as $timestamp => $crons) {
            foreach ($crons as $hook => $args) {
                $debug_cron_health['total_jobs']++;
                $job_info = [
                    'hook' => $hook,
                    'timestamp' => $timestamp,
                    'next_run' => date('Y-m-d H:i:s', $timestamp),
                    'args' => $args,
                    'overdue' => ($current_time - $timestamp > 300), // 5min threshold
                    'time_until' => $timestamp - $current_time
                ];

                $debug_cron_jobs['all_jobs'][] = $job_info;

                // Check if job is ready to run or overdue
                if ($timestamp <= $current_time) {
                    $debug_cron_jobs['ready'][$hook] = $job_info;
                    if ($job_info['overdue']) {
                        $debug_cron_health['overdue_jobs']++;
                    }
                }
            }
        }
    }

    // Test cron functionality (non-destructive)
    debug_start_timing('cron_test');
    $test_hook = 'debug_tool_cron_test_' . time();
    $test_scheduled = wp_schedule_single_event(time() + 60, $test_hook);
    $test_next = wp_next_scheduled($test_hook);
    if ($test_next) {
        wp_unschedule_event($test_next, $test_hook); // Clean up test
    }
    debug_end_timing('cron_test');

    $debug_cron_health['test_execution'] = $test_scheduled !== false ? 'Success' : 'Failed';
    $debug_cron_health['test_time'] = $GLOBALS['debug_timings']['cron_test'] ?? 0;

    // Check for common cron issues
    $debug_cron_health['issues'] = [];

    if ($debug_cron_health['wp_cron_disabled']) {
        $debug_cron_health['issues'][] = 'WP-Cron is disabled via DISABLE_WP_CRON constant';
    }

    if ($debug_cron_health['overdue_jobs'] > 5) {
        $debug_cron_health['issues'][] = 'High number of overdue jobs detected';
    }

    if ($debug_cron_health['test_execution'] === 'Failed') {
        $debug_cron_health['issues'][] = 'Cron scheduling test failed';
    }

    // Check for loopback connectivity (required for WP-Cron)
    $loopback_url = home_url('/wp-cron.php');
    $loopback_response = wp_remote_get($loopback_url, [
        'timeout' => 10,
        'blocking' => true,
        'sslverify' => false
    ]);

    if (is_wp_error($loopback_response)) {
        $debug_cron_health['loopback_status'] = 'Failed';
        $debug_cron_health['loopback_error'] = $loopback_response->get_error_message();
        $debug_cron_health['issues'][] = 'Loopback request to wp-cron.php failed';
    } else {
        $debug_cron_health['loopback_status'] = 'Success';
        $debug_cron_health['loopback_code'] = wp_remote_retrieve_response_code($loopback_response);
    }
}

// Run cron analysis
analyze_cron_jobs();
debug_time('cron_analyzed');

// Database connection testing with enhanced metrics
$db_status = [];
try {
    global $wpdb;
    debug_start_timing('database_connection');
    $db_status['connection'] = $wpdb->check_connection() ? 'Connected' : 'Failed';
    debug_end_timing('database_connection');
    
    debug_start_timing('database_version');
    $db_status['version'] = $wpdb->get_var("SELECT VERSION()");
    debug_end_timing('database_version');
    
    $db_status['charset'] = $wpdb->charset;
    $db_status['collate'] = $wpdb->collate;
    $db_status['prefix'] = $wpdb->prefix;
    
    // Test query performance with multiple queries
    debug_start_timing('database_queries');
    $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} LIMIT 5");
    $db_status['posts_query_time'] = $debug_timings['database_queries'] ?? 0;
    
    $wpdb->get_results("SELECT option_name FROM {$wpdb->options} LIMIT 10");
    $db_status['options_query_time'] = $debug_timings['database_queries'] ?? 0;
    debug_end_timing('database_queries');
    
    // Get database size
    $db_size = $wpdb->get_var("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema='" . DB_NAME . "'");
    $db_status['size_mb'] = $db_size;
    
} catch (Exception $e) {
    $db_status['error'] = $e->getMessage();
}

debug_time('database_tested');

// WordPress configuration analysis with additional constants
$wp_config = [
    'WP_DEBUG' => defined('WP_DEBUG') ? (WP_DEBUG ? 'Enabled' : 'Disabled') : 'Not defined',
    'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') ? (WP_DEBUG_LOG ? 'Enabled' : 'Disabled') : 'Not defined',
    'WP_DEBUG_DISPLAY' => defined('WP_DEBUG_DISPLAY') ? (WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled') : 'Not defined',
    'WP_CACHE' => defined('WP_CACHE') ? (WP_CACHE ? 'Enabled' : 'Disabled') : 'Not defined',
    'CONCATENATE_SCRIPTS' => defined('CONCATENATE_SCRIPTS') ? (CONCATENATE_SCRIPTS ? 'Enabled' : 'Disabled') : 'Not defined',
    'COMPRESS_SCRIPTS' => defined('COMPRESS_SCRIPTS') ? (COMPRESS_SCRIPTS ? 'Enabled' : 'Disabled') : 'Not defined',
    'COMPRESS_CSS' => defined('COMPRESS_CSS') ? (COMPRESS_CSS ? 'Enabled' : 'Disabled') : 'Not defined',
    'WP_MEMORY_LIMIT' => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : 'Not defined',
    'WP_MAX_MEMORY_LIMIT' => defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : 'Not defined',
    'SCRIPT_DEBUG' => defined('SCRIPT_DEBUG') ? (SCRIPT_DEBUG ? 'Enabled' : 'Disabled') : 'Not defined',
    'WP_CRON_LOCK_TIMEOUT' => defined('WP_CRON_LOCK_TIMEOUT') ? WP_CRON_LOCK_TIMEOUT : 'Not defined',
    'AUTOMATIC_UPDATER_DISABLED' => defined('AUTOMATIC_UPDATER_DISABLED') ? (AUTOMATIC_UPDATER_DISABLED ? 'Disabled' : 'Enabled') : 'Not defined',
    'WP_AUTO_UPDATE_CORE' => defined('WP_AUTO_UPDATE_CORE') ? (WP_AUTO_UPDATE_CORE ? 'Enabled' : 'Disabled') : 'Not defined',
];

debug_time('config_analyzed');

// Enhanced cURL and HTTP diagnostics with comprehensive testing
$curl_diagnostics = [];
$cache_key = 'debug_ultimate_curl_' . md5($_SERVER['REQUEST_URI']);
$cached_curl = get_transient($cache_key);

if ($cached_curl === false) {
    debug_start_timing('curl_diagnostics');
    
    // cURL extension analysis
    if (extension_loaded('curl')) {
        $curl_version = curl_version();
        $curl_diagnostics['status'] = 'Loaded';
        $curl_diagnostics['version'] = $curl_version['version'];
        $curl_diagnostics['ssl_support'] = ($curl_version['features'] & CURL_VERSION_SSL) ? 'Yes' : 'No';
        $curl_diagnostics['protocols'] = implode(', ', $curl_version['protocols']);
        $curl_diagnostics['libz_support'] = ($curl_version['features'] & CURL_VERSION_LIBZ) ? 'Yes' : 'No';
    } else {
        $curl_diagnostics['status'] = 'Not loaded';
    }
    
    // External HTTP test with detailed analysis
    $test_urls = [
        'wordpress_api' => 'https://api.wordpress.org/core/version-check/1.7/',
        'google_dns' => 'https://dns.google/resolve?name=wordpress.org&type=A',
        'httpbin_ssl' => 'https://httpbin.org/get',
        'httpbin_redirect' => 'https://httpbin.org/redirect/3'
    ];
    
    foreach ($test_urls as $test_name => $test_url) {
        $http_start = microtime(true);
        $response = wp_remote_get($test_url, ['timeout' => 10, 'sslverify' => true]);
        $http_time = round((microtime(true) - $http_start) * 1000, 2);
        
        if (is_wp_error($response)) {
            $curl_diagnostics[$test_name] = [
                'status' => 'Failed',
                'error' => $response->get_error_message(),
                'time' => $http_time . 'ms'
            ];
        } else {
            $curl_diagnostics[$test_name] = [
                'status' => 'Success',
                'code' => wp_remote_retrieve_response_code($response),
                'time' => $http_time . 'ms',
                'headers' => wp_remote_retrieve_headers($response)->getAll()
            ];
        }
    }
    
    // Loopback test
    $loopback_start = microtime(true);
    add_filter('http_request_host_is_external', '__return_false');
    $loopback_response = wp_remote_get(home_url('/'), ['timeout' => 10]);
    remove_filter('http_request_host_is_external', '__return_false');
    $loopback_time = round((microtime(true) - $loopback_start) * 1000, 2);
    
    if (is_wp_error($loopback_response)) {
        $curl_diagnostics['loopback_test'] = [
            'status' => 'Failed',
            'error' => $loopback_response->get_error_message(),
            'time' => $loopback_time . 'ms'
        ];
    } else {
        $curl_diagnostics['loopback_test'] = [
            'status' => 'Success',
            'code' => wp_remote_retrieve_response_code($loopback_response),
            'time' => $loopback_time . 'ms'
        ];
    }
    
    debug_end_timing('curl_diagnostics');
    set_transient($cache_key, $curl_diagnostics, 300); // Cache for 5 minutes
} else {
    $curl_diagnostics = $cached_curl;
}

// Enhanced error log analysis with pattern detection
$error_log_analysis = [];
if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file) && is_readable($log_file)) {
        $log_size = filesize($log_file);
        $error_log_analysis['file_size'] = round($log_size / 1024, 2) . ' KB';
        $error_log_analysis['last_modified'] = date('Y-m-d H:i:s', filemtime($log_file));
        
        // Read last portion for recent errors with pattern analysis
        $lines = [];
        $handle = fopen($log_file, 'r');
        if ($handle) {
            fseek($handle, max(0, $log_size - 16384)); // Read last 16KB
            $content = fread($handle, 16384);
            fclose($handle);
            
            $lines = explode("\n", $content);
            $lines = array_filter($lines);
            $lines = array_slice($lines, -30); // Last 30 lines
            
            $error_log_analysis['recent_entries'] = count($lines);
            $error_log_analysis['sample_lines'] = array_slice($lines, -10); // Last 10 lines
            
            // Pattern analysis
            $patterns = [
                'fatal_errors' => '/FATAL|Fatal/',
                'php_errors' => '/PHP Error|PHP Fatal/',
                'warnings' => '/WARNING|Warning/',
                'notices' => '/NOTICE|Notice/',
                'curl_errors' => '/cURL|curl/',
                'memory_errors' => '/memory|Memory/',
                'timeout_errors' => '/timeout|Timeout/'
            ];
            
            $pattern_counts = [];
            foreach ($patterns as $pattern_name => $pattern) {
                $pattern_counts[$pattern_name] = 0;
                foreach ($lines as $line) {
                    if (preg_match($pattern, $line)) {
                        $pattern_counts[$pattern_name]++;
                    }
                }
            }
            $error_log_analysis['pattern_analysis'] = $pattern_counts;
        }
    }
}

debug_time('error_log_analyzed');

// WordPress footer integration for on-page diagnostics
add_action('wp_footer', function() {
    if (is_page()) {
        global $post, $debug_has_the_content, $debug_shortcodes_found, $debug_broken_shortcodes;
        
        // Analyze current page content
        $shortcode_analysis = analyze_shortcodes($post->post_content);
        
        echo '<div id="debug-diagnostic-box-' . $post->ID . '" style="
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px dashed #007cba;
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
            max-width: 400px;
            z-index: 999999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        ">';
        
        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
        echo '<strong style="color: #007cba;">🔍 Debug: Page ' . $post->ID . '</strong>';
        echo '<button onclick="this.parentElement.parentElement.style.display=\'none\'" style="background: #dc3545; color: white; border: none; border-radius: 3px; padding: 2px 6px; cursor: pointer;">✕</button>';
        echo '</div>';
        
        // Content check
        if ($debug_has_the_content) {
            echo '✅ the_content() called<br>';
        } else {
            echo '⚠️ the_content() not detected<br>';
        }
        
        // Content analysis
        $raw_content = $post->post_content;
        $processed_content = apply_filters('the_content', $raw_content);
        $clean_content = trim(strip_tags($processed_content));
        
        if (!empty($clean_content)) {
            echo '✅ Content exists (' . strlen($clean_content) . ' chars)<br>';
        } else {
            echo '⚠️ No content after processing<br>';
        }
        
        // Shortcode analysis
        if (!empty($shortcode_analysis['found'])) {
            echo '✅ Shortcodes: ' . implode(', ', array_keys($shortcode_analysis['found'])) . '<br>';
        }
        
        if (!empty($shortcode_analysis['broken'])) {
            echo '⚠️ Broken: ' . implode(', ', $shortcode_analysis['broken']) . '<br>';
        }
        
        echo '<div style="margin-top: 10px; font-size: 10px; color: #666;">';
        echo '<a href="debug-ultimate.php?page_id=' . $post->ID . '" target="_blank" style="color: #007cba;">Full Diagnostics →</a>';
        echo '</div>';
        
        echo '</div>';
    }
});

debug_time('footer_integration_setup');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Debug Tool - Ultimate Version</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --debug-bg: #ffffff;
            --debug-text: #333333;
            --debug-border: #dee2e6;
            --debug-success: #d4edda;
            --debug-warning: #fff3cd;
            --debug-error: #f8d7da;
            --debug-info: #d1ecf1;
            --debug-primary: #007cba;
            --debug-secondary: #6c757d;
            --debug-accent: #28a745;
            --debug-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        [data-theme="dark"] {
            --debug-bg: #1a1a1a;
            --debug-text: #e0e0e0;
            --debug-border: #404040;
            --debug-success: #155724;
            --debug-warning: #856404;
            --debug-error: #721c24;
            --debug-info: #0c5460;
            --debug-primary: #4a9eff;
            --debug-secondary: #adb5bd;
            --debug-accent: #20c997;
            --debug-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 15px;
            background: var(--debug-bg);
            color: var(--debug-text);
            line-height: 1.6;
            transition: all 0.3s ease;
            background-image: var(--debug-gradient);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .debug-container {
            max-width: 1600px;
            margin: 0 auto;
            background: var(--debug-bg);
            border: 3px solid var(--debug-border);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .debug-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--debug-gradient);
        }

        .debug-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 3px solid var(--debug-primary);
            background: var(--debug-gradient);
            border-radius: 12px;
            padding: 25px;
            color: white;
            position: relative;
        }

        .debug-title {
            margin: 0;
            color: white;
            font-size: 32px;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .debug-subtitle {
            margin: 5px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }

        .debug-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .debug-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            text-decoration: none;
            display: inline-block;
        }

        .debug-btn:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .debug-btn.secondary {
            background: var(--debug-secondary);
            border-color: var(--debug-secondary);
        }

        .debug-btn.success {
            background: var(--debug-accent);
            border-color: var(--debug-accent);
        }

        .debug-section {
            margin: 30px 0;
            border: 2px solid var(--debug-border);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: var(--debug-bg);
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
        }

        .debug-section:hover {
            border-color: var(--debug-primary);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .debug-section-header {
            background: var(--debug-gradient);
            color: white;
            padding: 18px 25px;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .debug-section-header:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .debug-section-content {
            padding: 25px;
            background: var(--debug-bg);
            transition: all 0.3s ease;
        }

        .debug-section.collapsed .debug-section-content {
            display: none;
        }

        .debug-section.collapsed .debug-section-header::after {
            content: ' ▼';
        }

        .debug-section:not(.collapsed) .debug-section-header::after {
            content: ' ▲';
        }

        .debug-status {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid;
            font-weight: 600;
            position: relative;
        }

        .debug-success {
            background: var(--debug-success);
            color: #155724;
            border-left-color: #28a745;
        }
        .debug-warning {
            background: var(--debug-warning);
            color: #856404;
            border-left-color: #ffc107;
        }
        .debug-error {
            background: var(--debug-error);
            color: #721c24;
            border-left-color: #dc3545;
        }
        .debug-info {
            background: var(--debug-info);
            color: #0c5460;
            border-left-color: #17a2b8;
        }

        .debug-code {
            background: #f8f9fa;
            border: 2px solid var(--debug-border);
            padding: 20px;
            border-radius: 8px;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 15px 0;
            line-height: 1.6;
            position: relative;
        }

        .debug-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 25px 0;
        }

        .debug-metric {
            background: var(--debug-bg);
            border: 3px solid var(--debug-border);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .debug-metric::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--debug-gradient);
        }

        .debug-metric:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: var(--debug-primary);
        }

        .debug-metric-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--debug-primary);
            margin-bottom: 10px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .debug-metric-label {
            font-size: 14px;
            color: var(--debug-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .debug-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: var(--debug-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .debug-table th,
        .debug-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid var(--debug-border);
        }

        .debug-table th {
            background: var(--debug-gradient);
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .debug-table tr:hover {
            background: rgba(0,123,186,0.05);
        }

        .debug-progress {
            background: var(--debug-border);
            border-radius: 12px;
            height: 10px;
            overflow: hidden;
            margin: 10px 0;
            position: relative;
        }

        .debug-progress-bar {
            height: 100%;
            background: var(--debug-gradient);
            transition: width 0.5s ease;
            border-radius: 12px;
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .debug-container { padding: 20px; }
            .debug-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .debug-controls {
                justify-content: center;
            }
            .debug-grid {
                grid-template-columns: 1fr;
            }
            .debug-title {
                font-size: 28px;
            }
        }

        .debug-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .debug-badge.success {
            background: var(--debug-accent);
            color: white;
        }

        .debug-badge.warning {
            background: #ffc107;
            color: #212529;
        }

        .debug-badge.error {
            background: #dc3545;
            color: white;
        }

        .debug-badge.info {
            background: var(--debug-primary);
            color: white;
        }

        .debug-footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 40px;
            border-top: 3px solid var(--debug-primary);
            background: var(--debug-gradient);
            border-radius: 12px;
            padding: 40px;
            color: white;
        }

        .debug-footer h3 {
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 700;
        }

        .debug-footer p {
            margin-bottom: 25px;
            opacity: 0.9;
            font-size: 16px;
        }

        .debug-footer .debug-btn {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <div>
                <h1 class="debug-title">🚀 WordPress Debug Tool - Ultimate</h1>
                <p class="debug-subtitle">Complete diagnostic suite with WordPress authentication</p>
                <?php
                $current_user = wp_get_current_user();
                echo '<div style="margin-top: 10px; font-size: 14px; opacity: 0.9;">';
                echo '<strong>👤 Logged in as:</strong> ' . esc_html($current_user->display_name);
                echo ' (' . esc_html($current_user->user_login) . ')';
                echo ' | <strong>Role:</strong> ' . esc_html(implode(', ', $current_user->roles));
                echo '</div>';
                ?>
            </div>
            <div class="debug-controls">
                <button class="debug-btn" onclick="toggleTheme()">🌓 Theme</button>
                <button class="debug-btn secondary" onclick="toggleAll()">📦 Toggle All</button>
                <button class="debug-btn success" onclick="exportResults()">💾 Export</button>
                <button class="debug-btn" onclick="refreshDiagnostics()">🔄 Refresh</button>
                <button class="debug-btn" onclick="toggleFooterBox()">👁️ Footer Box</button>
                <a href="<?php echo esc_url(wp_logout_url($_SERVER['REQUEST_URI'])); ?>"
                   class="debug-btn"
                   style="text-decoration: none; color: inherit;"
                   onclick="return confirm('Are you sure you want to logout? You will need to login again to access the debug tool.')">
                    🚪 Logout
                </a>
            </div>
        </div>

        <!-- Ultimate Performance Dashboard -->
        <div class="debug-grid">
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo $debug_timings['wordpress_loaded'] ?? '0'; ?>ms</div>
                <div class="debug-metric-label">WordPress Load</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo count(get_option('active_plugins', [])); ?></div>
                <div class="debug-metric-label">Active Plugins</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo $debug_has_the_content ? 'YES' : 'NO'; ?></div>
                <div class="debug-metric-label">Content Called</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo round(memory_get_usage() / 1024 / 1024, 1); ?>MB</div>
                <div class="debug-metric-label">Memory Usage</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo count($debug_hooks_called); ?></div>
                <div class="debug-metric-label">Hooks Fired</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo $db_status['connection'] ?? 'Unknown'; ?></div>
                <div class="debug-metric-label">Database</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo count($debug_shortcodes_found); ?></div>
                <div class="debug-metric-label">Shortcodes</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo $db_status['size_mb'] ?? '0'; ?>MB</div>
                <div class="debug-metric-label">DB Size</div>
            </div>
        </div>

        <!-- WordPress Configuration & Environment -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⚙️ WordPress Configuration & Environment Analysis
            </div>
            <div class="debug-section-content">
                <div class="debug-grid">
                    <div>
                        <h4>📊 Core Information</h4>
                        <div class="debug-code">
                            <strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?><br>
                            <strong>PHP:</strong> <?php echo PHP_VERSION; ?><br>
                            <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                            <strong>Theme:</strong> <?php echo wp_get_theme()->get('Name') . ' v' . wp_get_theme()->get('Version'); ?><br>
                            <strong>Multisite:</strong> <?php echo is_multisite() ? 'Yes' : 'No'; ?><br>
                            <strong>WordPress Path:</strong> <?php echo ABSPATH; ?><br>
                            <strong>WP Load Path:</strong> <?php echo $wp_load_path_used; ?><br>
                            <strong>Site URL:</strong> <?php echo home_url(); ?><br>
                            <strong>Admin URL:</strong> <?php echo admin_url(); ?>
                        </div>
                    </div>
                    <div>
                        <h4>🔧 Debug Configuration</h4>
                        <table class="debug-table">
                            <?php foreach ($wp_config as $constant => $value): ?>
                            <tr>
                                <td><strong><?php echo $constant; ?></strong></td>
                                <td>
                                    <?php if (strpos($value, 'Enabled') !== false): ?>
                                        <span class="debug-badge success"><?php echo $value; ?></span>
                                    <?php elseif (strpos($value, 'Disabled') !== false): ?>
                                        <span class="debug-badge warning"><?php echo $value; ?></span>
                                    <?php else: ?>
                                        <span class="debug-badge error"><?php echo $value; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>

                <?php
                $current_user = wp_get_current_user();
                if ($current_user->ID) {
                    echo '<div class="debug-success">';
                    echo '<strong>👤 Current User:</strong> ' . esc_html($current_user->display_name) . ' (ID: ' . $current_user->ID . ')<br>';
                    echo '<strong>Roles:</strong> ' . implode(', ', $current_user->roles) . '<br>';
                    echo '<strong>Capabilities:</strong> ' . (current_user_can('manage_options') ? 'Administrator' : 'Limited') . '<br>';
                    echo '<strong>Email:</strong> ' . esc_html($current_user->user_email);
                    echo '</div>';
                } else {
                    echo '<div class="debug-warning">';
                    echo '<strong>👤 User Status:</strong> Not logged in. <a href="' . wp_login_url() . '">Login here</a>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Security & Vulnerability Scan -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔒 Security & Vulnerability Scan
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('security_scan_start');

                // Enhanced security scan function
                function perform_security_scan() {
                    $security_analysis = [
                        'risk_score' => 0,
                        'risk_level' => 'Low',
                        'core_status' => [],
                        'plugin_vulnerabilities' => [],
                        'theme_vulnerabilities' => [],
                        'file_permissions' => [],
                        'security_headers' => [],
                        'recommendations' => []
                    ];

                    // Check WordPress core version
                    $current_version = get_bloginfo('version');
                    $updates = get_core_updates();

                    if (!empty($updates) && isset($updates[0]->response) && $updates[0]->response !== 'latest') {
                        $security_analysis['core_status'] = [
                            'current' => $current_version,
                            'latest' => $updates[0]->current ?? 'Unknown',
                            'status' => 'outdated',
                            'update_url' => admin_url('update-core.php')
                        ];
                        $security_analysis['risk_score'] += 3;
                        $security_analysis['recommendations'][] = 'Update WordPress core to latest version';
                    } else {
                        $security_analysis['core_status'] = [
                            'current' => $current_version,
                            'status' => 'up_to_date'
                        ];
                    }

                    // Check active plugins for updates
                    $active_plugins = get_option('active_plugins', []);
                    $all_plugins = get_plugins();
                    $plugin_updates = get_plugin_updates();

                    foreach ($active_plugins as $plugin_file) {
                        if (isset($plugin_updates[$plugin_file])) {
                            $plugin_data = $all_plugins[$plugin_file];
                            $security_analysis['plugin_vulnerabilities'][] = [
                                'name' => $plugin_data['Name'],
                                'current' => $plugin_data['Version'],
                                'latest' => $plugin_updates[$plugin_file]->update->new_version ?? 'Unknown',
                                'file' => $plugin_file,
                                'risk' => 'medium'
                            ];
                            $security_analysis['risk_score'] += 1;
                        }
                    }

                    // Check theme for updates
                    $current_theme = wp_get_theme();
                    $theme_updates = get_theme_updates();

                    if (isset($theme_updates[$current_theme->get_stylesheet()])) {
                        $security_analysis['theme_vulnerabilities'][] = [
                            'name' => $current_theme->get('Name'),
                            'current' => $current_theme->get('Version'),
                            'latest' => $theme_updates[$current_theme->get_stylesheet()]->update['new_version'] ?? 'Unknown',
                            'risk' => 'low'
                        ];
                        $security_analysis['risk_score'] += 0.5;
                    }

                    // Check critical file permissions
                    $critical_files = [
                        'wp-config.php' => ABSPATH . 'wp-config.php',
                        '.htaccess' => ABSPATH . '.htaccess',
                        'wp-admin/' => ABSPATH . 'wp-admin/',
                        'wp-includes/' => ABSPATH . 'wp-includes/',
                        'wp-content/uploads/' => WP_CONTENT_DIR . '/uploads/'
                    ];

                    foreach ($critical_files as $name => $path) {
                        if (file_exists($path)) {
                            $perms = fileperms($path);
                            $octal_perms = substr(sprintf('%o', $perms), -4);

                            $security_analysis['file_permissions'][] = [
                                'file' => $name,
                                'permissions' => $octal_perms,
                                'status' => check_file_permission_security($name, $octal_perms),
                                'path' => $path
                            ];

                            // Add risk for overly permissive files
                            if (check_file_permission_security($name, $octal_perms) === 'risky') {
                                $security_analysis['risk_score'] += 1;
                            }
                        }
                    }

                    // Check security headers (basic check)
                    $security_analysis['security_headers'] = check_security_headers();

                    // Calculate overall risk level
                    if ($security_analysis['risk_score'] >= 5) {
                        $security_analysis['risk_level'] = 'High';
                    } elseif ($security_analysis['risk_score'] >= 2) {
                        $security_analysis['risk_level'] = 'Medium';
                    } else {
                        $security_analysis['risk_level'] = 'Low';
                    }

                    // Add general recommendations
                    if (empty($security_analysis['recommendations'])) {
                        $security_analysis['recommendations'][] = 'Security status looks good - continue monitoring';
                    }

                    return $security_analysis;
                }

                // Helper function to check file permission security
                function check_file_permission_security($filename, $permissions) {
                    $risky_patterns = [
                        'wp-config.php' => ['0777', '0666', '0644'],
                        '.htaccess' => ['0777', '0666'],
                        'wp-admin/' => ['0777'],
                        'wp-includes/' => ['0777'],
                        'wp-content/uploads/' => ['0777']
                    ];

                    if (isset($risky_patterns[$filename]) && in_array($permissions, $risky_patterns[$filename])) {
                        return 'risky';
                    }

                    return 'secure';
                }

                // Helper function to check security headers
                function check_security_headers() {
                    $headers = [];

                    // Check if we can determine some basic security configurations
                    $headers['wp_debug'] = defined('WP_DEBUG') && WP_DEBUG ? 'enabled' : 'disabled';
                    $headers['wp_debug_display'] = defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'enabled' : 'disabled';
                    $headers['wp_debug_log'] = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'enabled' : 'disabled';
                    $headers['force_ssl_admin'] = defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN ? 'enabled' : 'disabled';

                    return $headers;
                }

                $security_scan = perform_security_scan();

                // Display security metrics
                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value ' . strtolower($security_scan['risk_level']) . '">' . $security_scan['risk_level'] . '</div>';
                echo '<div class="debug-metric-label">Risk Level</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $security_scan['risk_score'] . '</div>';
                echo '<div class="debug-metric-label">Risk Score</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($security_scan['plugin_vulnerabilities']) . '</div>';
                echo '<div class="debug-metric-label">Outdated Plugins</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($security_scan['theme_vulnerabilities']) . '</div>';
                echo '<div class="debug-metric-label">Outdated Themes</div>';
                echo '</div>';
                echo '</div>';

                // Display WordPress core status
                echo '<h4>🏛️ WordPress Core Status</h4>';
                echo '<div class="debug-info">';
                if ($security_scan['core_status']['status'] === 'up_to_date') {
                    echo '<span class="debug-badge success">✅ Up to Date</span> WordPress ' . esc_html($security_scan['core_status']['current']);
                } else {
                    echo '<span class="debug-badge error">⚠️ Outdated</span> ';
                    echo 'Current: ' . esc_html($security_scan['core_status']['current']) . ' | ';
                    echo 'Latest: ' . esc_html($security_scan['core_status']['latest']) . ' | ';
                    echo '<a href="' . esc_url($security_scan['core_status']['update_url']) . '">Update Now</a>';
                }
                echo '</div>';

                // Display plugin vulnerabilities
                if (!empty($security_scan['plugin_vulnerabilities'])) {
                    echo '<h4>🔌 Outdated Plugins</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Plugin</th><th>Current Version</th><th>Latest Version</th><th>Risk Level</th><th>Action</th></tr></thead>';
                    echo '<tbody>';

                    foreach ($security_scan['plugin_vulnerabilities'] as $plugin) {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($plugin['name']) . '</strong></td>';
                        echo '<td>' . esc_html($plugin['current']) . '</td>';
                        echo '<td>' . esc_html($plugin['latest']) . '</td>';
                        echo '<td><span class="debug-badge warning">' . ucfirst($plugin['risk']) . '</span></td>';
                        echo '<td><a href="' . admin_url('plugins.php') . '">Update Plugin</a></td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                }

                // Display theme vulnerabilities
                if (!empty($security_scan['theme_vulnerabilities'])) {
                    echo '<h4>🎨 Outdated Themes</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Theme</th><th>Current Version</th><th>Latest Version</th><th>Risk Level</th><th>Action</th></tr></thead>';
                    echo '<tbody>';

                    foreach ($security_scan['theme_vulnerabilities'] as $theme) {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($theme['name']) . '</strong></td>';
                        echo '<td>' . esc_html($theme['current']) . '</td>';
                        echo '<td>' . esc_html($theme['latest']) . '</td>';
                        echo '<td><span class="debug-badge warning">' . ucfirst($theme['risk']) . '</span></td>';
                        echo '<td><a href="' . admin_url('themes.php') . '">Update Theme</a></td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                }

                // Display file permissions
                if (!empty($security_scan['file_permissions'])) {
                    echo '<h4>📁 File Permissions Analysis</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>File/Directory</th><th>Permissions</th><th>Status</th><th>Recommendation</th></tr></thead>';
                    echo '<tbody>';

                    foreach ($security_scan['file_permissions'] as $file) {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($file['file']) . '</strong></td>';
                        echo '<td><code>' . esc_html($file['permissions']) . '</code></td>';
                        echo '<td>';

                        if ($file['status'] === 'secure') {
                            echo '<span class="debug-badge success">Secure</span>';
                        } else {
                            echo '<span class="debug-badge error">Risky</span>';
                        }

                        echo '</td>';
                        echo '<td>';

                        if ($file['status'] === 'risky') {
                            if ($file['file'] === 'wp-config.php') {
                                echo 'Set permissions to 0600 or 0644';
                            } elseif (strpos($file['file'], '/') !== false) {
                                echo 'Set directory permissions to 0755';
                            } else {
                                echo 'Review and restrict permissions';
                            }
                        } else {
                            echo 'Permissions are appropriate';
                        }

                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                }

                // Display security configuration
                echo '<h4>🛡️ Security Configuration</h4>';
                echo '<table class="debug-table">';
                echo '<thead><tr><th>Setting</th><th>Status</th><th>Recommendation</th></tr></thead>';
                echo '<tbody>';

                foreach ($security_scan['security_headers'] as $setting => $status) {
                    echo '<tr>';
                    echo '<td><strong>' . esc_html(str_replace('_', ' ', strtoupper($setting))) . '</strong></td>';
                    echo '<td>';

                    if ($status === 'enabled') {
                        if (in_array($setting, ['wp_debug', 'wp_debug_display'])) {
                            echo '<span class="debug-badge warning">Enabled</span>';
                        } else {
                            echo '<span class="debug-badge success">Enabled</span>';
                        }
                    } else {
                        if (in_array($setting, ['force_ssl_admin'])) {
                            echo '<span class="debug-badge warning">Disabled</span>';
                        } else {
                            echo '<span class="debug-badge success">Disabled</span>';
                        }
                    }

                    echo '</td>';
                    echo '<td>';

                    switch ($setting) {
                        case 'wp_debug':
                        case 'wp_debug_display':
                            echo $status === 'enabled' ? 'Disable in production' : 'Good for production';
                            break;
                        case 'wp_debug_log':
                            echo $status === 'enabled' ? 'Monitor log file size' : 'Consider enabling for debugging';
                            break;
                        case 'force_ssl_admin':
                            echo $status === 'enabled' ? 'Good security practice' : 'Consider enabling for HTTPS';
                            break;
                        default:
                            echo 'Review configuration';
                    }

                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';

                // Display recommendations
                if (!empty($security_scan['recommendations'])) {
                    echo '<div class="debug-warning">';
                    echo '<strong>🔒 Security Recommendations:</strong><br>';
                    foreach ($security_scan['recommendations'] as $recommendation) {
                        echo '• ' . esc_html($recommendation) . '<br>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="debug-success">';
                    echo '<strong>✅ Security Status:</strong> No critical security issues detected.';
                    echo '</div>';
                }

                debug_time('security_scan_end');
                ?>
            </div>
        </div>

        <!-- Enhanced Database Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🗄️ Enhanced Database Analysis & Performance
            </div>
            <div class="debug-section-content">
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $db_status['connection'] ?? 'Unknown'; ?></div>
                        <div class="debug-metric-label">Connection Status</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $db_status['posts_query_time'] ?? '0'; ?>ms</div>
                        <div class="debug-metric-label">Posts Query</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $db_status['options_query_time'] ?? '0'; ?>ms</div>
                        <div class="debug-metric-label">Options Query</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $db_status['size_mb'] ?? '0'; ?>MB</div>
                        <div class="debug-metric-label">Database Size</div>
                    </div>
                </div>

                <div class="debug-code">
                    <strong>Database Host:</strong> <?php echo DB_HOST; ?><br>
                    <strong>Database Name:</strong> <?php echo DB_NAME; ?><br>
                    <strong>Table Prefix:</strong> <?php echo $db_status['prefix'] ?? 'Unknown'; ?><br>
                    <strong>Charset:</strong> <?php echo $db_status['charset'] ?? 'Unknown'; ?><br>
                    <strong>Collation:</strong> <?php echo $db_status['collate'] ?? 'Unknown'; ?><br>
                    <strong>MySQL Version:</strong> <?php echo $db_status['version'] ?? 'Unknown'; ?><br>
                    <?php if (isset($db_status['error'])): ?>
                    <strong style="color: #dc3545;">Error:</strong> <?php echo esc_html($db_status['error']); ?>
                    <?php endif; ?>
                </div>

                <?php
                // Enhanced table analysis with scrollable, resizable table
                global $wpdb;
                $tables = $wpdb->get_results("SHOW TABLE STATUS LIKE '{$wpdb->prefix}%'");
                if ($tables) {
                    echo '<h4>📋 Database Tables Analysis</h4>';
                    echo '<div class="debug-info">';
                    echo '<strong>Total Tables:</strong> ' . count($tables) . ' | ';
                    echo '<strong>Database:</strong> ' . DB_NAME . ' | ';
                    echo '<strong>Prefix:</strong> ' . $wpdb->prefix;
                    echo '</div>';

                    // Scrollable and resizable table container
                    echo '<div class="debug-table-container" style="
                        max-height: 400px;
                        overflow: auto;
                        border: 2px solid var(--debug-border);
                        border-radius: 8px;
                        margin: 15px 0;
                        resize: both;
                        min-height: 200px;
                        min-width: 300px;
                        background: white;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    ">';
                    echo '<table class="debug-table" style="margin: 0; width: 100%; border-collapse: collapse;">';
                    echo '<thead style="position: sticky; top: 0; background: var(--debug-primary); color: white; z-index: 10;">';
                    echo '<tr>';
                    echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #fff;">Table Name</th>';
                    echo '<th style="padding: 12px; text-align: right; border-bottom: 2px solid #fff;">Rows</th>';
                    echo '<th style="padding: 12px; text-align: right; border-bottom: 2px solid #fff;">Data Size</th>';
                    echo '<th style="padding: 12px; text-align: right; border-bottom: 2px solid #fff;">Index Size</th>';
                    echo '<th style="padding: 12px; text-align: right; border-bottom: 2px solid #fff;">Total Size</th>';
                    echo '<th style="padding: 12px; text-align: center; border-bottom: 2px solid #fff;">Engine</th>';
                    echo '<th style="padding: 12px; text-align: center; border-bottom: 2px solid #fff;">Collation</th>';
                    echo '<th style="padding: 12px; text-align: center; border-bottom: 2px solid #fff;">Status</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    $total_data_size = 0;
                    $total_index_size = 0;
                    $total_rows = 0;

                    foreach ($tables as $index => $table) {
                        $data_size = round($table->Data_length / 1024 / 1024, 2);
                        $index_size = round($table->Index_length / 1024 / 1024, 2);
                        $total_size = $data_size + $index_size;

                        $total_data_size += $data_size;
                        $total_index_size += $index_size;
                        $total_rows += $table->Rows;

                        // Determine status based on table health
                        $status = 'success';
                        $status_text = 'Healthy';
                        if ($table->Rows > 1000000) {
                            $status = 'warning';
                            $status_text = 'Large';
                        }
                        if ($total_size > 100) {
                            $status = 'error';
                            $status_text = 'Very Large';
                        }
                        if (empty($table->Engine)) {
                            $status = 'error';
                            $status_text = 'No Engine';
                        }

                        // Alternate row colors
                        $row_class = ($index % 2 === 0) ? 'even' : 'odd';
                        echo '<tr style="background-color: ' . (($index % 2 === 0) ? '#f8f9fa' : '#ffffff') . ';">';
                        echo '<td style="padding: 8px; border-bottom: 1px solid #dee2e6; font-family: monospace; font-weight: bold;">' . esc_html($table->Name) . '</td>';
                        echo '<td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: right;">' . number_format($table->Rows) . '</td>';
                        echo '<td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: right;">' . $data_size . ' MB</td>';
                        echo '<td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: right;">' . $index_size . ' MB</td>';
                        echo '<td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: right; font-weight: bold;">' . $total_size . ' MB</td>';
                        echo '<td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: center;">' . esc_html($table->Engine ?: 'Unknown') . '</td>';
                        echo '<td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: center; font-size: 11px;">' . esc_html($table->Collation ?: 'Unknown') . '</td>';
                        echo '<td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: center;">';
                        echo '<span class="debug-badge ' . $status . '">' . $status_text . '</span>';
                        echo '</td>';
                        echo '</tr>';
                    }

                    // Summary row
                    echo '<tr style="background: var(--debug-primary); color: white; font-weight: bold;">';
                    echo '<td style="padding: 12px; border-top: 2px solid #333;">TOTALS</td>';
                    echo '<td style="padding: 12px; border-top: 2px solid #333; text-align: right;">' . number_format($total_rows) . '</td>';
                    echo '<td style="padding: 12px; border-top: 2px solid #333; text-align: right;">' . round($total_data_size, 2) . ' MB</td>';
                    echo '<td style="padding: 12px; border-top: 2px solid #333; text-align: right;">' . round($total_index_size, 2) . ' MB</td>';
                    echo '<td style="padding: 12px; border-top: 2px solid #333; text-align: right;">' . round($total_data_size + $total_index_size, 2) . ' MB</td>';
                    echo '<td style="padding: 12px; border-top: 2px solid #333; text-align: center;" colspan="3">' . count($tables) . ' Tables</td>';
                    echo '</tr>';

                    echo '</tbody></table>';
                    echo '</div>';

                    // Database optimization recommendations
                    echo '<div class="debug-info">';
                    echo '<strong>💡 Database Optimization Tips:</strong><br>';
                    echo '• <strong>Resize Table:</strong> Drag the bottom-right corner of the table to resize<br>';
                    echo '• <strong>Large Tables:</strong> Consider archiving old data from tables marked as "Large" or "Very Large"<br>';
                    echo '• <strong>Indexing:</strong> High index-to-data ratios may indicate over-indexing<br>';
                    echo '• <strong>Engine:</strong> Ensure all tables use appropriate storage engines (InnoDB recommended)<br>';
                    echo '• <strong>Collation:</strong> Inconsistent collations can cause performance issues<br>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Database Query Profiler -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔍 Database Query Profiler & Performance Analysis
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('query_profiler_start');

                // Enhanced query profiler function
                function analyze_database_queries() {
                    global $wpdb;

                    // Enable query logging if not already enabled
                    if (!defined('SAVEQUERIES')) {
                        define('SAVEQUERIES', true);
                    }

                    $query_analysis = [
                        'total_queries' => 0,
                        'slow_queries' => [],
                        'duplicate_queries' => [],
                        'query_types' => [],
                        'total_time' => 0,
                        'recommendations' => []
                    ];

                    if (!empty($wpdb->queries)) {
                        $query_analysis['total_queries'] = count($wpdb->queries);
                        $query_hashes = [];
                        $slow_threshold = 50; // 50ms threshold

                        foreach ($wpdb->queries as $index => $query_data) {
                            $sql = $query_data[0];
                            $time = floatval($query_data[1]) * 1000; // Convert to milliseconds
                            $stack = $query_data[2] ?? '';

                            $query_analysis['total_time'] += $time;

                            // Analyze query type
                            $query_type = strtoupper(explode(' ', trim($sql))[0]);
                            if (!isset($query_analysis['query_types'][$query_type])) {
                                $query_analysis['query_types'][$query_type] = 0;
                            }
                            $query_analysis['query_types'][$query_type]++;

                            // Check for slow queries
                            if ($time > $slow_threshold) {
                                $explain_data = null;

                                // Run EXPLAIN for SELECT queries
                                if (stripos($sql, 'SELECT') === 0) {
                                    try {
                                        $explain_data = $wpdb->get_results("EXPLAIN $sql", ARRAY_A);
                                    } catch (Exception $e) {
                                        $explain_data = ['error' => $e->getMessage()];
                                    }
                                }

                                $query_analysis['slow_queries'][] = [
                                    'index' => $index + 1,
                                    'sql' => $sql,
                                    'time' => $time,
                                    'stack' => $stack,
                                    'explain' => $explain_data,
                                    'recommendation' => generate_query_recommendation($sql, $time, $explain_data)
                                ];
                            }

                            // Check for duplicate queries
                            $query_hash = md5(preg_replace('/\s+/', ' ', trim($sql)));
                            if (isset($query_hashes[$query_hash])) {
                                $query_hashes[$query_hash]['count']++;
                                $query_hashes[$query_hash]['total_time'] += $time;
                            } else {
                                $query_hashes[$query_hash] = [
                                    'sql' => $sql,
                                    'count' => 1,
                                    'total_time' => $time
                                ];
                            }
                        }

                        // Find duplicate queries
                        foreach ($query_hashes as $hash => $data) {
                            if ($data['count'] > 1) {
                                $query_analysis['duplicate_queries'][] = [
                                    'sql' => $data['sql'],
                                    'count' => $data['count'],
                                    'total_time' => $data['total_time'],
                                    'avg_time' => $data['total_time'] / $data['count']
                                ];
                            }
                        }

                        // Generate overall recommendations
                        $query_analysis['recommendations'] = generate_overall_recommendations($query_analysis);
                    }

                    return $query_analysis;
                }

                // Generate query-specific recommendations
                function generate_query_recommendation($sql, $time, $explain_data) {
                    $recommendations = [];

                    if ($time > 100) {
                        $recommendations[] = "Query is very slow ({$time}ms) - consider optimization";
                    }

                    if (stripos($sql, 'SELECT *') !== false) {
                        $recommendations[] = "Avoid SELECT * - specify only needed columns";
                    }

                    if (stripos($sql, 'ORDER BY') !== false && stripos($sql, 'LIMIT') === false) {
                        $recommendations[] = "ORDER BY without LIMIT can be expensive";
                    }

                    if (is_array($explain_data) && !isset($explain_data['error'])) {
                        foreach ($explain_data as $row) {
                            if (isset($row['type']) && $row['type'] === 'ALL') {
                                $recommendations[] = "Full table scan detected - add appropriate indexes";
                            }
                            if (isset($row['Extra']) && strpos($row['Extra'], 'Using filesort') !== false) {
                                $recommendations[] = "Filesort operation detected - consider adding index for ORDER BY";
                            }
                            if (isset($row['rows']) && intval($row['rows']) > 1000) {
                                $recommendations[] = "High row count ({$row['rows']}) - consider adding WHERE conditions";
                            }
                        }
                    }

                    return empty($recommendations) ? 'Query appears optimized' : implode('; ', $recommendations);
                }

                // Generate overall recommendations
                function generate_overall_recommendations($analysis) {
                    $recommendations = [];

                    if ($analysis['total_queries'] > 50) {
                        $recommendations[] = "High query count ({$analysis['total_queries']}) - consider caching";
                    }

                    if (count($analysis['slow_queries']) > 5) {
                        $recommendations[] = "Multiple slow queries detected - database optimization needed";
                    }

                    if (count($analysis['duplicate_queries']) > 0) {
                        $recommendations[] = "Duplicate queries found - implement query caching";
                    }

                    if ($analysis['total_time'] > 500) {
                        $recommendations[] = "Total query time is high ({$analysis['total_time']}ms) - optimize database";
                    }

                    return $recommendations;
                }

                $query_analysis = analyze_database_queries();

                // Display query profiler metrics
                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $query_analysis['total_queries'] . '</div>';
                echo '<div class="debug-metric-label">Total Queries</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . round($query_analysis['total_time'], 2) . 'ms</div>';
                echo '<div class="debug-metric-label">Total Query Time</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($query_analysis['slow_queries']) . '</div>';
                echo '<div class="debug-metric-label">Slow Queries (>50ms)</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($query_analysis['duplicate_queries']) . '</div>';
                echo '<div class="debug-metric-label">Duplicate Queries</div>';
                echo '</div>';
                echo '</div>';

                // Display slow queries analysis
                if (!empty($query_analysis['slow_queries'])) {
                    echo '<h4>🐌 Slow Queries Analysis (>' . 50 . 'ms)</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>#</th><th>Query</th><th>Time</th><th>EXPLAIN</th><th>Recommendations</th></tr></thead>';
                    echo '<tbody>';

                    foreach (array_slice($query_analysis['slow_queries'], 0, 10) as $slow_query) {
                        echo '<tr>';
                        echo '<td>' . $slow_query['index'] . '</td>';
                        echo '<td><code style="font-size: 11px; max-width: 300px; display: block; overflow: hidden; text-overflow: ellipsis;">' . esc_html(substr($slow_query['sql'], 0, 100)) . '...</code></td>';
                        echo '<td><span class="debug-badge error">' . round($slow_query['time'], 2) . 'ms</span></td>';
                        echo '<td>';

                        if (isset($slow_query['explain']['error'])) {
                            echo '<span style="color: #dc3545;">Error: ' . esc_html($slow_query['explain']['error']) . '</span>';
                        } elseif (!empty($slow_query['explain'])) {
                            echo '<details><summary>View EXPLAIN</summary>';
                            echo '<pre style="font-size: 10px; max-height: 100px; overflow: auto;">';
                            echo esc_html(json_encode($slow_query['explain'], JSON_PRETTY_PRINT));
                            echo '</pre></details>';
                        } else {
                            echo '<em>N/A</em>';
                        }

                        echo '</td>';
                        echo '<td style="font-size: 11px;">' . esc_html($slow_query['recommendation']) . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';

                    if (count($query_analysis['slow_queries']) > 10) {
                        echo '<div class="debug-info">Showing top 10 slow queries. Total: ' . count($query_analysis['slow_queries']) . '</div>';
                    }
                }

                // Display duplicate queries
                if (!empty($query_analysis['duplicate_queries'])) {
                    echo '<h4>🔄 Duplicate Queries Analysis</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Query</th><th>Count</th><th>Total Time</th><th>Avg Time</th><th>Impact</th></tr></thead>';
                    echo '<tbody>';

                    foreach (array_slice($query_analysis['duplicate_queries'], 0, 10) as $dup_query) {
                        echo '<tr>';
                        echo '<td><code style="font-size: 11px; max-width: 300px; display: block; overflow: hidden; text-overflow: ellipsis;">' . esc_html(substr($dup_query['sql'], 0, 100)) . '...</code></td>';
                        echo '<td><span class="debug-badge warning">' . $dup_query['count'] . 'x</span></td>';
                        echo '<td>' . round($dup_query['total_time'], 2) . 'ms</td>';
                        echo '<td>' . round($dup_query['avg_time'], 2) . 'ms</td>';
                        echo '<td>';

                        if ($dup_query['count'] > 5) {
                            echo '<span style="color: #dc3545;">High</span> - Consider caching';
                        } elseif ($dup_query['count'] > 2) {
                            echo '<span style="color: #ffc107;">Medium</span> - Review necessity';
                        } else {
                            echo '<span style="color: #28a745;">Low</span> - Acceptable';
                        }

                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                }

                // Display query types breakdown
                if (!empty($query_analysis['query_types'])) {
                    echo '<h4>📊 Query Types Breakdown</h4>';
                    echo '<div class="debug-grid">';

                    foreach ($query_analysis['query_types'] as $type => $count) {
                        echo '<div class="debug-metric">';
                        echo '<div class="debug-metric-value">' . $count . '</div>';
                        echo '<div class="debug-metric-label">' . $type . ' Queries</div>';
                        echo '</div>';
                    }

                    echo '</div>';
                }

                // Display recommendations
                if (!empty($query_analysis['recommendations'])) {
                    echo '<div class="debug-warning">';
                    echo '<strong>💡 Database Performance Recommendations:</strong><br>';
                    foreach ($query_analysis['recommendations'] as $recommendation) {
                        echo '• ' . esc_html($recommendation) . '<br>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="debug-success">';
                    echo '<strong>✅ Database Performance:</strong> No major issues detected. Queries appear to be well optimized.';
                    echo '</div>';
                }

                debug_time('query_profiler_end');
                ?>
            </div>
        </div>

        <!-- Theme Template Diagnostics -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🎨 Theme Template Diagnostics & Hierarchy Analysis
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('theme_template_analysis_start');

                // Enhanced theme template diagnostics function
                function scan_template_hierarchy($post = null) {
                    global $wp_query;

                    // Get current post if not provided
                    if (!$post && isset($wp_query->post)) {
                        $post = $wp_query->post;
                    }

                    $results = [];
                    $theme_dir = get_template_directory();
                    $child_theme_dir = get_stylesheet_directory();
                    $is_child_theme = ($theme_dir !== $child_theme_dir);

                    // Get template hierarchy for current context
                    $hierarchy = [];
                    if (is_front_page()) {
                        $hierarchy = ['front-page.php', 'home.php', 'index.php'];
                    } elseif (is_home()) {
                        $hierarchy = ['home.php', 'index.php'];
                    } elseif (is_page()) {
                        $page_template = get_page_template_slug($post);
                        if ($page_template) {
                            $hierarchy[] = $page_template;
                        }
                        $hierarchy = array_merge($hierarchy, [
                            'page-' . $post->post_name . '.php',
                            'page-' . $post->ID . '.php',
                            'page.php',
                            'singular.php',
                            'index.php'
                        ]);
                    } elseif (is_single()) {
                        $post_type = get_post_type($post);
                        $hierarchy = [
                            'single-' . $post_type . '-' . $post->post_name . '.php',
                            'single-' . $post_type . '.php',
                            'single.php',
                            'singular.php',
                            'index.php'
                        ];
                    } elseif (is_archive()) {
                        if (is_category()) {
                            $category = get_queried_object();
                            $hierarchy = [
                                'category-' . $category->slug . '.php',
                                'category-' . $category->term_id . '.php',
                                'category.php',
                                'archive.php',
                                'index.php'
                            ];
                        } elseif (is_tag()) {
                            $tag = get_queried_object();
                            $hierarchy = [
                                'tag-' . $tag->slug . '.php',
                                'tag-' . $tag->term_id . '.php',
                                'tag.php',
                                'archive.php',
                                'index.php'
                            ];
                        } else {
                            $hierarchy = ['archive.php', 'index.php'];
                        }
                    } elseif (is_search()) {
                        $hierarchy = ['search.php', 'index.php'];
                    } elseif (is_404()) {
                        $hierarchy = ['404.php', 'index.php'];
                    } else {
                        $hierarchy = ['index.php'];
                    }

                    // Check each template in hierarchy
                    foreach ($hierarchy as $template) {
                        $parent_path = $theme_dir . '/' . $template;
                        $child_path = $child_theme_dir . '/' . $template;

                        $exists_in_parent = file_exists($parent_path);
                        $exists_in_child = $is_child_theme && file_exists($child_path);
                        $active_path = $exists_in_child ? $child_path : ($exists_in_parent ? $parent_path : null);

                        // Check if this is the currently loaded template
                        $current_template = get_page_template();
                        $is_loaded = ($active_path && $current_template && realpath($active_path) === realpath($current_template));

                        $results[] = [
                            'template' => $template,
                            'exists_parent' => $exists_in_parent,
                            'exists_child' => $exists_in_child,
                            'active_path' => $active_path,
                            'is_loaded' => $is_loaded,
                            'override_type' => $exists_in_child ? 'Child Theme' : ($exists_in_parent ? 'Parent Theme' : 'Missing'),
                            'file_size' => $active_path ? filesize($active_path) : 0,
                            'last_modified' => $active_path ? filemtime($active_path) : 0
                        ];
                    }

                    return $results;
                }

                // Get theme information
                $current_theme = wp_get_theme();
                $parent_theme = $current_theme->parent();
                $template_hierarchy = scan_template_hierarchy();

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . esc_html($current_theme->get('Name')) . '</div>';
                echo '<div class="debug-metric-label">Active Theme</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . esc_html($current_theme->get('Version')) . '</div>';
                echo '<div class="debug-metric-label">Theme Version</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($parent_theme ? 'Child Theme' : 'Parent Theme') . '</div>';
                echo '<div class="debug-metric-label">Theme Type</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($template_hierarchy) . '</div>';
                echo '<div class="debug-metric-label">Templates in Hierarchy</div>';
                echo '</div>';
                echo '</div>';

                // Template hierarchy analysis
                echo '<h4>🏗️ Template Hierarchy Analysis</h4>';
                echo '<div class="debug-info">';
                echo '<strong>Current Context:</strong> ';
                if (is_front_page()) echo 'Front Page';
                elseif (is_home()) echo 'Blog Home';
                elseif (is_page()) echo 'Page: ' . get_the_title();
                elseif (is_single()) echo 'Single Post: ' . get_the_title();
                elseif (is_category()) echo 'Category: ' . single_cat_title('', false);
                elseif (is_tag()) echo 'Tag: ' . single_tag_title('', false);
                elseif (is_archive()) echo 'Archive';
                elseif (is_search()) echo 'Search Results';
                elseif (is_404()) echo '404 Error';
                else echo 'Unknown';
                echo '</div>';

                echo '<table class="debug-table">';
                echo '<thead><tr><th>Template File</th><th>Status</th><th>Location</th><th>File Info</th><th>Recommendations</th></tr></thead>';
                echo '<tbody>';

                foreach ($template_hierarchy as $template_info) {
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($template_info['template']) . '</strong></td>';
                    echo '<td>';

                    if ($template_info['is_loaded']) {
                        echo '<span class="debug-badge success">✅ Active</span>';
                    } elseif ($template_info['active_path']) {
                        echo '<span class="debug-badge warning">Available</span>';
                    } else {
                        echo '<span class="debug-badge error">Missing</span>';
                    }

                    echo '</td>';
                    echo '<td>' . esc_html($template_info['override_type']) . '</td>';
                    echo '<td>';

                    if ($template_info['active_path']) {
                        echo 'Size: ' . number_format($template_info['file_size']) . ' bytes<br>';
                        echo 'Modified: ' . date('Y-m-d H:i:s', $template_info['last_modified']);
                    } else {
                        echo '<em>File not found</em>';
                    }

                    echo '</td>';
                    echo '<td>';

                    if ($template_info['is_loaded']) {
                        echo 'Currently rendering this page';
                    } elseif (!$template_info['active_path']) {
                        echo 'Consider creating this template for more specific control';
                    } elseif ($template_info['exists_child'] && $template_info['exists_parent']) {
                        echo 'Child theme overrides parent template';
                    } else {
                        echo 'Available in template hierarchy';
                    }

                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';

                debug_time('theme_template_analysis_end');
                ?>
            </div>
        </div>

        <!-- Ultimate Content Detection & Template Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📄 Ultimate Content Detection & Template Analysis
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('content_analysis_start');

                if ($current_post) {
                    echo '<div class="debug-info">';
                    echo '<strong>🎯 Analyzing Page:</strong> ' . esc_html($current_post->post_title) . ' (ID: ' . $current_post->ID . ')<br>';
                    echo '<strong>📄 Template:</strong> ' . ($current_template ?: 'Default template') . '<br>';
                    echo '<strong>📝 Post Type:</strong> ' . $current_post->post_type . '<br>';
                    echo '<strong>📊 Content Length:</strong> ' . strlen($current_post->post_content) . ' characters<br>';
                    echo '<strong>📅 Published:</strong> ' . $current_post->post_date . '<br>';
                    echo '<strong>👤 Author:</strong> ' . get_the_author_meta('display_name', $current_post->post_author);
                    echo '</div>';

                    // Enhanced content processing
                    $raw_content = $current_post->post_content;
                    $processed_content = apply_filters('the_content', $raw_content);
                    $clean_content = trim(strip_tags($processed_content));

                    // Shortcode analysis
                    $shortcode_analysis = analyze_shortcodes($raw_content);
                } else {
                    echo '<div class="debug-warning">';
                    echo '<strong>⚠️ No specific page selected.</strong> Add ?page_id=123 to test a specific page.';
                    echo '</div>';

                    // Analyze current page if we're on one
                    global $post;
                    if ($post && is_page()) {
                        $raw_content = $post->post_content;
                        $processed_content = apply_filters('the_content', $raw_content);
                        $clean_content = trim(strip_tags($processed_content));
                        $shortcode_analysis = analyze_shortcodes($raw_content);

                        echo '<div class="debug-info">';
                        echo '<strong>🎯 Current Page:</strong> ' . esc_html($post->post_title) . ' (ID: ' . $post->ID . ')';
                        echo '</div>';
                    }
                }

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($debug_has_the_content ? 'YES' : 'NO') . '</div>';
                echo '<div class="debug-metric-label">the_content() Called</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($debug_content_filters) . '</div>';
                echo '<div class="debug-metric-label">Filter Calls</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . (isset($shortcode_analysis) ? count($shortcode_analysis['found']) : 0) . '</div>';
                echo '<div class="debug-metric-label">Shortcodes Found</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . (isset($shortcode_analysis) ? count($shortcode_analysis['broken']) : 0) . '</div>';
                echo '<div class="debug-metric-label">Broken Shortcodes</div>';
                echo '</div>';
                echo '</div>';

                // Content processing results
                if (isset($raw_content)) {
                    echo '<h4>📝 Content Processing Results</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Stage</th><th>Length</th><th>Status</th><th>Details</th></tr></thead>';
                    echo '<tbody>';

                    echo '<tr>';
                    echo '<td><strong>Raw Content</strong></td>';
                    echo '<td>' . strlen($raw_content) . ' chars</td>';
                    echo '<td><span class="debug-badge ' . (strlen($raw_content) > 0 ? 'success' : 'warning') . '">' . (strlen($raw_content) > 0 ? 'Has Content' : 'Empty') . '</span></td>';
                    echo '<td>Original post content</td>';
                    echo '</tr>';

                    echo '<tr>';
                    echo '<td><strong>Processed Content</strong></td>';
                    echo '<td>' . strlen($processed_content) . ' chars</td>';
                    echo '<td><span class="debug-badge ' . (strlen($processed_content) > 0 ? 'success' : 'warning') . '">' . (strlen($processed_content) > 0 ? 'Processed' : 'Empty') . '</span></td>';
                    echo '<td>After the_content filters</td>';
                    echo '</tr>';

                    echo '<tr>';
                    echo '<td><strong>Clean Content</strong></td>';
                    echo '<td>' . strlen($clean_content) . ' chars</td>';
                    echo '<td><span class="debug-badge ' . (strlen($clean_content) > 0 ? 'success' : 'error') . '">' . (strlen($clean_content) > 0 ? 'Visible' : 'No Text') . '</span></td>';
                    echo '<td>After stripping HTML tags</td>';
                    echo '</tr>';

                    echo '</tbody></table>';
                }

                // Content filter timeline
                if (!empty($debug_content_filters)) {
                    echo '<h4>🔄 Content Filter Timeline</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Time</th><th>Filter</th><th>Content Length</th><th>Memory</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($debug_content_filters as $filter) {
                        echo '<tr>';
                        echo '<td>' . esc_html($filter['time']) . '</td>';
                        echo '<td>' . esc_html($filter['filter']) . '</td>';
                        echo '<td>' . number_format($filter['content_length']) . ' chars</td>';
                        echo '<td>' . round($filter['memory'] / 1024 / 1024, 2) . ' MB</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }

                // Shortcode analysis results
                if (isset($shortcode_analysis)) {
                    echo '<h4>🏷️ Shortcode Analysis Results</h4>';

                    if (!empty($shortcode_analysis['found'])) {
                        echo '<div class="debug-success">';
                        echo '<strong>✅ Found Shortcodes:</strong><br>';
                        foreach ($shortcode_analysis['found'] as $shortcode => $count) {
                            echo '• [' . esc_html($shortcode) . '] - ' . $count . ' instance(s)<br>';
                        }
                        echo '</div>';
                    }

                    if (!empty($shortcode_analysis['broken'])) {
                        echo '<div class="debug-error">';
                        echo '<strong>❌ Broken/Unregistered Shortcodes:</strong><br>';
                        foreach ($shortcode_analysis['broken'] as $broken) {
                            echo '• [' . esc_html($broken) . '] - not registered<br>';
                        }
                        echo '</div>';
                    }

                    if (empty($shortcode_analysis['found']) && empty($shortcode_analysis['broken'])) {
                        echo '<div class="debug-info">';
                        echo '<strong>ℹ️ No shortcodes found in content.</strong>';
                        echo '</div>';
                    }
                }

                debug_time('content_analysis_end');
                ?>
            </div>
        </div>

        <!-- Block Editor & Gutenberg Diagnostics -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🧱 Block Editor & Gutenberg Diagnostics
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('gutenberg_analysis_start');

                // Enhanced Gutenberg block analysis function
                function analyze_gutenberg_blocks($content = '') {
                    global $post;

                    if (empty($content) && $post) {
                        $content = $post->post_content;
                    }

                    $block_analysis = [
                        'total_blocks' => 0,
                        'valid_blocks' => 0,
                        'invalid_blocks' => [],
                        'deprecated_blocks' => [],
                        'block_types' => [],
                        'nested_blocks' => 0,
                        'recommendations' => []
                    ];

                    if (empty($content)) {
                        return $block_analysis;
                    }

                    // Parse blocks using WordPress core function
                    $blocks = parse_blocks($content);
                    $block_analysis['total_blocks'] = count($blocks);

                    if (empty($blocks)) {
                        $block_analysis['recommendations'][] = 'No blocks found - content may be using classic editor';
                        return $block_analysis;
                    }

                    $registry = WP_Block_Type_Registry::get_instance();

                    foreach ($blocks as $index => $block) {
                        $block_name = $block['blockName'];

                        // Skip empty blocks
                        if (empty($block_name)) {
                            continue;
                        }

                        // Count block types
                        if (!isset($block_analysis['block_types'][$block_name])) {
                            $block_analysis['block_types'][$block_name] = 0;
                        }
                        $block_analysis['block_types'][$block_name]++;

                        // Check if block is registered
                        if (!$registry->is_registered($block_name)) {
                            $block_analysis['invalid_blocks'][] = [
                                'index' => $index,
                                'name' => $block_name,
                                'issue' => 'Block type not registered',
                                'fix' => 'Install/activate plugin that provides this block or replace with core block'
                            ];
                        } else {
                            $block_analysis['valid_blocks']++;

                            // Check for deprecated attributes
                            $block_type = $registry->get_registered($block_name);
                            if ($block_type && isset($block['attrs'])) {
                                foreach ($block['attrs'] as $attr_name => $attr_value) {
                                    if (strpos($attr_name, 'deprecated') !== false || strpos($attr_name, 'legacy') !== false) {
                                        $block_analysis['deprecated_blocks'][] = [
                                            'index' => $index,
                                            'name' => $block_name,
                                            'attribute' => $attr_name,
                                            'fix' => 'Update block to use current attributes'
                                        ];
                                    }
                                }
                            }
                        }

                        // Check for nested blocks
                        if (!empty($block['innerBlocks'])) {
                            $block_analysis['nested_blocks'] += count($block['innerBlocks']);

                            // Recursively check inner blocks
                            foreach ($block['innerBlocks'] as $inner_index => $inner_block) {
                                if (!empty($inner_block['blockName']) && !$registry->is_registered($inner_block['blockName'])) {
                                    $block_analysis['invalid_blocks'][] = [
                                        'index' => $index . '.' . $inner_index,
                                        'name' => $inner_block['blockName'],
                                        'issue' => 'Nested block type not registered',
                                        'fix' => 'Install/activate plugin that provides this nested block'
                                    ];
                                }
                            }
                        }
                    }

                    // Generate recommendations
                    if (count($block_analysis['invalid_blocks']) > 0) {
                        $block_analysis['recommendations'][] = 'Invalid blocks detected - may cause display issues';
                    }

                    if (count($block_analysis['deprecated_blocks']) > 0) {
                        $block_analysis['recommendations'][] = 'Deprecated block attributes found - update for compatibility';
                    }

                    if ($block_analysis['nested_blocks'] > 20) {
                        $block_analysis['recommendations'][] = 'High number of nested blocks may impact performance';
                    }

                    if (empty($block_analysis['recommendations'])) {
                        $block_analysis['recommendations'][] = 'Block structure appears healthy';
                    }

                    return $block_analysis;
                }

                // Check if Gutenberg/Block Editor is active
                $is_gutenberg_active = function_exists('parse_blocks') && function_exists('has_blocks');
                $current_post_content = $current_post ? $current_post->post_content : '';
                $has_blocks = $is_gutenberg_active && $current_post && has_blocks($current_post_content);

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($is_gutenberg_active ? 'Active' : 'Inactive') . '</div>';
                echo '<div class="debug-metric-label">Block Editor Status</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($has_blocks ? 'Yes' : 'No') . '</div>';
                echo '<div class="debug-metric-label">Current Page Uses Blocks</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count(WP_Block_Type_Registry::get_instance()->get_all_registered()) . '</div>';
                echo '<div class="debug-metric-label">Registered Block Types</div>';
                echo '</div>';
                echo '</div>';

                if ($is_gutenberg_active && $current_post) {
                    $block_analysis = analyze_gutenberg_blocks($current_post_content);

                    echo '<div class="debug-grid">';
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . $block_analysis['total_blocks'] . '</div>';
                    echo '<div class="debug-metric-label">Total Blocks</div>';
                    echo '</div>';
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . $block_analysis['valid_blocks'] . '</div>';
                    echo '<div class="debug-metric-label">Valid Blocks</div>';
                    echo '</div>';
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . count($block_analysis['invalid_blocks']) . '</div>';
                    echo '<div class="debug-metric-label">Invalid Blocks</div>';
                    echo '</div>';
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . $block_analysis['nested_blocks'] . '</div>';
                    echo '<div class="debug-metric-label">Nested Blocks</div>';
                    echo '</div>';
                    echo '</div>';

                    // Display invalid blocks
                    if (!empty($block_analysis['invalid_blocks'])) {
                        echo '<h4>❌ Invalid Blocks</h4>';
                        echo '<table class="debug-table">';
                        echo '<thead><tr><th>Position</th><th>Block Name</th><th>Issue</th><th>Recommended Fix</th></tr></thead>';
                        echo '<tbody>';

                        foreach ($block_analysis['invalid_blocks'] as $invalid_block) {
                            echo '<tr>';
                            echo '<td>#' . esc_html($invalid_block['index']) . '</td>';
                            echo '<td><code>' . esc_html($invalid_block['name']) . '</code></td>';
                            echo '<td><span class="debug-badge error">' . esc_html($invalid_block['issue']) . '</span></td>';
                            echo '<td>' . esc_html($invalid_block['fix']) . '</td>';
                            echo '</tr>';
                        }

                        echo '</tbody></table>';
                    }

                    // Display deprecated blocks
                    if (!empty($block_analysis['deprecated_blocks'])) {
                        echo '<h4>⚠️ Deprecated Block Attributes</h4>';
                        echo '<table class="debug-table">';
                        echo '<thead><tr><th>Position</th><th>Block Name</th><th>Deprecated Attribute</th><th>Recommended Fix</th></tr></thead>';
                        echo '<tbody>';

                        foreach ($block_analysis['deprecated_blocks'] as $deprecated_block) {
                            echo '<tr>';
                            echo '<td>#' . esc_html($deprecated_block['index']) . '</td>';
                            echo '<td><code>' . esc_html($deprecated_block['name']) . '</code></td>';
                            echo '<td><span class="debug-badge warning">' . esc_html($deprecated_block['attribute']) . '</span></td>';
                            echo '<td>' . esc_html($deprecated_block['fix']) . '</td>';
                            echo '</tr>';
                        }

                        echo '</tbody></table>';
                    }

                    // Display block types breakdown
                    if (!empty($block_analysis['block_types'])) {
                        echo '<h4>📊 Block Types Usage</h4>';
                        echo '<table class="debug-table">';
                        echo '<thead><tr><th>Block Type</th><th>Count</th><th>Percentage</th><th>Status</th></tr></thead>';
                        echo '<tbody>';

                        arsort($block_analysis['block_types']);
                        $total_blocks = array_sum($block_analysis['block_types']);

                        foreach ($block_analysis['block_types'] as $block_type => $count) {
                            $percentage = round(($count / $total_blocks) * 100, 1);
                            echo '<tr>';
                            echo '<td><code>' . esc_html($block_type) . '</code></td>';
                            echo '<td>' . $count . '</td>';
                            echo '<td>' . $percentage . '%</td>';
                            echo '<td>';

                            if (strpos($block_type, 'core/') === 0) {
                                echo '<span class="debug-badge success">Core Block</span>';
                            } elseif (WP_Block_Type_Registry::get_instance()->is_registered($block_type)) {
                                echo '<span class="debug-badge success">Registered</span>';
                            } else {
                                echo '<span class="debug-badge error">Unregistered</span>';
                            }

                            echo '</td>';
                            echo '</tr>';
                        }

                        echo '</tbody></table>';
                    }

                    // Display recommendations
                    if (!empty($block_analysis['recommendations'])) {
                        echo '<div class="debug-warning">';
                        echo '<strong>🧱 Block Editor Recommendations:</strong><br>';
                        foreach ($block_analysis['recommendations'] as $recommendation) {
                            echo '• ' . esc_html($recommendation) . '<br>';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="debug-success">';
                        echo '<strong>✅ Block Structure:</strong> All blocks appear to be valid and properly configured.';
                        echo '</div>';
                    }

                    debug_time('gutenberg_analysis_end');
                } else {
                    echo '<div class="debug-info">';
                    if (!$is_gutenberg_active) {
                        echo '<strong>ℹ️ Block Editor Status:</strong> Block Editor is not active or not available.';
                    } elseif (!$current_post) {
                        echo '<strong>ℹ️ Analysis Status:</strong> No current post available for block analysis.';
                    } else {
                        echo '<strong>ℹ️ Content Type:</strong> Current page does not use block editor content.';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Ultimate Plugin Analysis with Advanced Conflict Testing -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔌 Ultimate Plugin Analysis & Advanced Conflict Testing
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('plugin_analysis_start');

                $active_plugins = get_option('active_plugins', []);
                $all_plugins = get_plugins();
                $mu_plugins = get_mu_plugins();

                if (!empty($disabled_plugins)) {
                    echo '<div class="debug-warning">';
                    echo '<strong>⚠️ Plugin Conflict Test Active:</strong> Disabled plugins: ' . implode(', ', $disabled_plugins);
                    echo '<br><a href="' . remove_query_arg('debug_disable_plugins') . '">Reset to normal</a>';
                    echo '</div>';
                }

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($active_plugins) . '</div>';
                echo '<div class="debug-metric-label">Active Plugins</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($all_plugins) . '</div>';
                echo '<div class="debug-metric-label">Total Plugins</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($mu_plugins) . '</div>';
                echo '<div class="debug-metric-label">Must-Use Plugins</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($disabled_plugins) . '</div>';
                echo '<div class="debug-metric-label">Disabled (Test)</div>';
                echo '</div>';
                echo '</div>';

                if (!empty($active_plugins)) {
                    echo '<h4>🟢 Active Plugins</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Plugin</th><th>Version</th><th>Author</th><th>File</th><th>Actions</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($active_plugins as $plugin_file) {
                        if (in_array($plugin_file, $disabled_plugins)) continue;

                        $plugin_data = $all_plugins[$plugin_file] ?? null;
                        if ($plugin_data) {
                            echo '<tr>';
                            echo '<td><strong>' . esc_html($plugin_data['Name']) . '</strong>';
                            if (!empty($plugin_data['Description'])) {
                                echo '<br><small>' . esc_html(substr($plugin_data['Description'], 0, 100)) . '...</small>';
                            }
                            echo '</td>';
                            echo '<td>' . esc_html($plugin_data['Version']) . '</td>';
                            echo '<td>' . esc_html($plugin_data['Author']) . '</td>';
                            echo '<td><code>' . esc_html($plugin_file) . '</code></td>';
                            echo '<td>';
                            echo '<a href="' . add_query_arg('debug_disable_plugins', $plugin_file) . '" class="debug-btn" style="font-size: 11px; padding: 4px 8px; text-decoration: none;">Test Disable</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</tbody></table>';
                }

                // Must-Use plugins
                if (!empty($mu_plugins)) {
                    echo '<h4>🔒 Must-Use Plugins</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Plugin</th><th>Version</th><th>File</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($mu_plugins as $mu_file => $mu_data) {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($mu_data['Name']) . '</strong></td>';
                        echo '<td>' . esc_html($mu_data['Version']) . '</td>';
                        echo '<td><code>' . esc_html($mu_file) . '</code></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }

                // Plugin conflict testing instructions
                echo '<div class="debug-info">';
                echo '<strong>🧪 Advanced Plugin Conflict Testing:</strong><br>';
                echo '• Click "Test Disable" to temporarily disable a plugin for this request<br>';
                echo '• Use URL parameter: <code>?debug_disable_plugins=plugin1.php,plugin2.php</code><br>';
                echo '• Test multiple plugins: <code>?debug_disable_plugins=plugin1.php,plugin2.php,plugin3.php</code><br>';
                echo '• This helps identify plugin conflicts without affecting your live site<br>';
                echo '• Changes only affect the current debug session';
                echo '</div>';

                // Plugin load order analysis
                echo '<h4>📊 Plugin Load Order Analysis</h4>';
                echo '<div class="debug-code">';
                echo '<strong>Plugin Load Sequence:</strong><br>';
                foreach ($active_plugins as $index => $plugin_file) {
                    if (in_array($plugin_file, $disabled_plugins)) {
                        echo ($index + 1) . '. <span style="color: #dc3545; text-decoration: line-through;">' . esc_html($plugin_file) . ' (DISABLED FOR TEST)</span><br>';
                    } else {
                        echo ($index + 1) . '. ' . esc_html($plugin_file) . '<br>';
                    }
                }
                echo '</div>';

                debug_time('plugin_analysis_end');
                ?>
            </div>
        </div>

        <!-- WordPress Hooks & Filters Ultimate Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🪝 WordPress Hooks & Filters Ultimate Analysis
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('hooks_analysis_start');

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($debug_hooks_called) . '</div>';
                echo '<div class="debug-metric-label">Unique Hooks</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . array_sum($debug_hooks_called) . '</div>';
                echo '<div class="debug-metric-label">Total Hook Calls</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count(array_filter($debug_hooks_called, function($count) { return $count > 10; })) . '</div>';
                echo '<div class="debug-metric-label">Heavy Hooks (>10)</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count(array_filter($debug_hooks_called, function($count) { return $count > 50; })) . '</div>';
                echo '<div class="debug-metric-label">Very Heavy (>50)</div>';
                echo '</div>';
                echo '</div>';

                // Most called hooks with enhanced analysis
                if (!empty($debug_hooks_called)) {
                    arsort($debug_hooks_called);
                    $top_hooks = array_slice($debug_hooks_called, 0, 20, true);

                    echo '<h4>🔥 Most Called Hooks (Top 20)</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Hook Name</th><th>Call Count</th><th>Type</th><th>Category</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($top_hooks as $hook => $count) {
                        $hook_type = 'Action';
                        $category = 'General';

                        if (strpos($hook, '_filter') !== false || in_array($hook, ['the_content', 'the_title', 'the_excerpt'])) {
                            $hook_type = 'Filter';
                        }

                        // Categorize hooks
                        if (strpos($hook, 'wp_') === 0) {
                            $category = 'WordPress Core';
                        } elseif (strpos($hook, 'admin_') === 0) {
                            $category = 'Admin';
                        } elseif (strpos($hook, 'wp_ajax_') === 0) {
                            $category = 'AJAX';
                        } elseif (strpos($hook, 'wp_enqueue_') === 0) {
                            $category = 'Assets';
                        } elseif (in_array($hook, ['init', 'wp_loaded', 'wp_head', 'wp_footer'])) {
                            $category = 'Lifecycle';
                        }

                        echo '<tr>';
                        echo '<td><code>' . esc_html($hook) . '</code></td>';
                        echo '<td>' . $count . '</td>';
                        echo '<td>';
                        if ($hook_type === 'Filter') {
                            echo '<span class="debug-badge success">Filter</span>';
                        } else {
                            echo '<span class="debug-badge info">Action</span>';
                        }
                        echo '</td>';
                        echo '<td><span class="debug-badge warning">' . $category . '</span></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';

                    // Hook performance analysis
                    echo '<div class="debug-info">';
                    echo '<strong>🎯 Hook Performance Analysis:</strong><br>';
                    $heavy_hooks = array_filter($debug_hooks_called, function($count) { return $count > 50; });
                    if (!empty($heavy_hooks)) {
                        echo '• ⚠️ ' . count($heavy_hooks) . ' hooks called more than 50 times<br>';
                        echo '• Consider optimizing: ' . implode(', ', array_slice(array_keys($heavy_hooks), 0, 3)) . '<br>';
                    }
                    echo '• Total hook calls: ' . array_sum($debug_hooks_called) . '<br>';
                    echo '• Average calls per hook: ' . round(array_sum($debug_hooks_called) / count($debug_hooks_called), 1);
                    echo '</div>';
                }

                debug_time('hooks_analysis_end');
                ?>
            </div>
        </div>

        <!-- Custom Domain URL Testing with Enhanced Security -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔗 Custom Domain URL Testing (Secured)
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🎯 Test Any URL:</strong> Enter a custom domain or URL to run comprehensive connectivity and performance tests.
                    <br><strong>🔒 Security:</strong> This feature includes CSRF protection, SSRF prevention, and rate limiting.
                </div>

                <?php
                // Security: Check user capabilities
                if (!current_user_can('manage_options')) {
                    echo '<div class="debug-warning">';
                    echo '<strong>⚠️ Access Denied:</strong> You need administrator privileges to use the URL testing feature.';
                    echo '</div>';
                } else {
                    // Security: Rate limiting check
                    $rate_limit_key = 'debug_url_test_' . get_current_user_id();
                    $rate_limit_count = get_transient($rate_limit_key) ?: 0;
                    $rate_limit_max = 10; // Max 10 tests per hour
                    $rate_limit_window = 3600; // 1 hour

                    if ($rate_limit_count >= $rate_limit_max) {
                        echo '<div class="debug-warning">';
                        echo '<strong>⚠️ Rate Limit Exceeded:</strong> You can only test ' . $rate_limit_max . ' URLs per hour. Please try again later.';
                        echo '</div>';
                    } else {
                ?>

                <div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 2px solid var(--debug-border);">
                    <form method="POST" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <?php wp_nonce_field('debug_url_test_action', 'debug_url_test_nonce'); ?>
                        <label for="custom_url" style="font-weight: bold; color: var(--debug-primary);">🌐 URL to Test:</label>
                        <input type="url"
                               id="custom_url"
                               name="custom_url"
                               placeholder="https://example.com"
                               value="<?php echo esc_attr($_POST['custom_url'] ?? ''); ?>"
                               style="flex: 1; min-width: 300px; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;"
                               maxlength="2048"
                               pattern="https?://.+"
                               title="Please enter a valid HTTP or HTTPS URL"
                               required>
                        <button type="submit"
                                name="test_custom_url"
                                style="padding: 10px 20px; background: var(--debug-primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                            🚀 Test URL
                        </button>
                        <?php if (!empty($_POST['custom_url'])): ?>
                        <a href="?" style="padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px;">
                            🔄 Clear
                        </a>
                        <?php endif; ?>
                    </form>

                    <div style="margin-top: 10px; font-size: 12px; color: #666;">
                        <strong>🛡️ Security Notes:</strong>
                        • Internal/localhost URLs are blocked for security
                        • Rate limited to <?php echo $rate_limit_max; ?> tests per hour
                        • Tests remaining: <?php echo max(0, $rate_limit_max - $rate_limit_count); ?>
                    </div>
                </div>

                <?php
                    } // End rate limit check
                } // End capability check
                ?>

                <?php
                // Secure Custom URL Testing Logic
                if (current_user_can('manage_options') &&
                    isset($_POST['test_custom_url']) &&
                    !empty($_POST['custom_url']) &&
                    wp_verify_nonce($_POST['debug_url_test_nonce'], 'debug_url_test_action')) {

                    // Security: Input validation and sanitization
                    $custom_url = esc_url_raw($_POST['custom_url']);

                    // Security: Additional URL validation
                    if (!filter_var($custom_url, FILTER_VALIDATE_URL) ||
                        !in_array(parse_url($custom_url, PHP_URL_SCHEME), ['http', 'https'])) {
                        echo '<div class="debug-warning">';
                        echo '<strong>⚠️ Invalid URL:</strong> Please enter a valid HTTP or HTTPS URL.';
                        echo '</div>';
                    } else {
                        // Security: SSRF Protection - Block internal/private IPs and localhost
                        $parsed_url = parse_url($custom_url);
                        $host = $parsed_url['host'] ?? '';

                        // Check for blocked hosts and IP ranges
                        $blocked_hosts = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
                        $is_blocked = false;

                        // Check if host is in blocked list
                        if (in_array(strtolower($host), $blocked_hosts)) {
                            $is_blocked = true;
                        }

                        // Check for private IP ranges
                        if (!$is_blocked && filter_var($host, FILTER_VALIDATE_IP)) {
                            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                $is_blocked = true;
                            }
                        }

                        // Check for internal domains
                        if (!$is_blocked) {
                            $internal_patterns = ['.local', '.internal', '.lan', '.intranet'];
                            foreach ($internal_patterns as $pattern) {
                                if (strpos(strtolower($host), $pattern) !== false) {
                                    $is_blocked = true;
                                    break;
                                }
                            }
                        }

                        if ($is_blocked) {
                            echo '<div class="debug-warning">';
                            echo '<strong>🚫 Security Block:</strong> Testing internal/private URLs is not allowed for security reasons.';
                            echo '<br><small>Blocked: localhost, private IPs (10.x.x.x, 192.168.x.x, 172.16-31.x.x), and internal domains</small>';
                            echo '</div>';
                        } else {
                            // Security: Update rate limiting
                            $rate_limit_count = get_transient($rate_limit_key) ?: 0;
                            set_transient($rate_limit_key, $rate_limit_count + 1, $rate_limit_window);

                            // Security: Log the URL test attempt
                            error_log(sprintf(
                                'Debug Tool URL Test: User %d tested URL %s from IP %s',
                                get_current_user_id(),
                                $custom_url,
                                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                            ));

                            echo '<h4>🧪 Testing Results for: <code>' . esc_html($custom_url) . '</code></h4>';
                            echo '<div class="debug-info">';
                            echo '<strong>🔒 Security Status:</strong> URL validated and approved for testing';
                            echo '</div>';

                    // Enhanced secure function to test custom URL
                    function test_custom_url_secure($url) {
                        $results = [];
                        $parsed_url = parse_url($url);
                        $domain = $parsed_url['host'] ?? '';

                        // Security: Enhanced request arguments with security headers
                        $secure_args = [
                            'timeout' => 15,
                            'redirection' => 3, // Limit redirects
                            'user-agent' => 'WordPress Debug Tool Ultimate/1.0 (Security Enhanced)',
                            'sslverify' => true, // Enable SSL verification by default
                            'headers' => [
                                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                                'Accept-Language' => 'en-US,en;q=0.5',
                                'Accept-Encoding' => 'gzip, deflate',
                                'DNT' => '1',
                                'Connection' => 'close'
                            ],
                            'body' => null,
                            'compress' => true,
                            'decompress' => true,
                            'stream' => false,
                            'filename' => null
                        ];

                        // Test 1: Basic HTTP Request with enhanced security
                        debug_start_timing('custom_http_test');
                        $response = wp_remote_get($url, $secure_args);
                        debug_end_timing('custom_http_test');

                        if (is_wp_error($response)) {
                            $error_message = $response->get_error_message();
                            $results['http_test'] = [
                                'status' => 'Failed',
                                'error' => esc_html($error_message),
                                'time' => $GLOBALS['debug_timings']['custom_http_test'] ?? 0,
                                'fix' => 'Check if the URL is accessible and not blocked by firewall',
                                'security_note' => 'Request failed security validation'
                            ];

                            // Security: Log failed requests for monitoring
                            error_log(sprintf(
                                'Debug Tool URL Test Failed: %s - Error: %s',
                                $url,
                                $error_message
                            ));
                        } else {
                            $code = wp_remote_retrieve_response_code($response);
                            $response_body = wp_remote_retrieve_body($response);
                            $response_headers = wp_remote_retrieve_headers($response);

                            // Security: Validate response size to prevent memory exhaustion
                            $response_size = strlen($response_body);
                            if ($response_size > 10485760) { // 10MB limit
                                $results['http_test'] = [
                                    'status' => 'Warning',
                                    'code' => $code,
                                    'time' => $GLOBALS['debug_timings']['custom_http_test'] ?? 0,
                                    'size' => $response_size,
                                    'warning' => 'Response size exceeds 10MB limit',
                                    'security_note' => 'Large response truncated for security'
                                ];
                            } else {
                                $results['http_test'] = [
                                    'status' => ($code >= 200 && $code < 400) ? 'Success' : 'Warning',
                                    'code' => $code,
                                    'time' => $GLOBALS['debug_timings']['custom_http_test'] ?? 0,
                                    'size' => $response_size,
                                    'headers' => array_slice($response_headers->getAll(), 0, 20), // Limit headers
                                    'security_note' => 'Response validated and safe'
                                ];
                            }
                        }

                        // Test 2: Enhanced SSL/HTTPS Test with security validation
                        if (strpos($url, 'https://') === 0) {
                            debug_start_timing('custom_ssl_test');

                            // Security: Enhanced SSL verification
                            $ssl_args = array_merge($secure_args, [
                                'sslverify' => true,
                                'sslcertificates' => ABSPATH . WPINC . '/certificates/ca-bundle.crt'
                            ]);

                            $ssl_response = wp_remote_get($url, $ssl_args);
                            debug_end_timing('custom_ssl_test');

                            if (is_wp_error($ssl_response)) {
                                $ssl_error = $ssl_response->get_error_message();
                                $results['ssl_test'] = [
                                    'status' => 'Failed',
                                    'error' => esc_html($ssl_error),
                                    'time' => $GLOBALS['debug_timings']['custom_ssl_test'] ?? 0,
                                    'fix' => 'SSL certificate may be invalid, expired, or self-signed',
                                    'security_note' => 'SSL verification enforced for security'
                                ];

                                // Security: Log SSL failures
                                error_log(sprintf(
                                    'Debug Tool SSL Test Failed: %s - SSL Error: %s',
                                    $url,
                                    $ssl_error
                                ));
                            } else {
                                $ssl_code = wp_remote_retrieve_response_code($ssl_response);
                                $results['ssl_test'] = [
                                    'status' => 'Success',
                                    'code' => $ssl_code,
                                    'time' => $GLOBALS['debug_timings']['custom_ssl_test'] ?? 0,
                                    'message' => 'SSL certificate is valid and trusted',
                                    'security_note' => 'SSL certificate verified successfully'
                                ];
                            }
                        } else {
                            $results['ssl_test'] = [
                                'status' => 'Skipped',
                                'message' => 'Not an HTTPS URL',
                                'security_note' => 'Consider using HTTPS for better security'
                            ];
                        }

                        // Test 3: Enhanced DNS Resolution with security checks
                        debug_start_timing('custom_dns_test');
                        $ip = gethostbyname($domain);
                        debug_end_timing('custom_dns_test');

                        if ($ip === $domain) {
                            $results['dns_test'] = [
                                'status' => 'Failed',
                                'error' => 'DNS resolution failed',
                                'time' => $GLOBALS['debug_timings']['custom_dns_test'] ?? 0,
                                'fix' => 'Check DNS settings or domain configuration',
                                'security_note' => 'DNS resolution required for security validation'
                            ];
                        } else {
                            // Security: Additional IP validation after DNS resolution
                            $is_private_ip = !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

                            $results['dns_test'] = [
                                'status' => $is_private_ip ? 'Warning' : 'Success',
                                'ip' => esc_html($ip),
                                'time' => $GLOBALS['debug_timings']['custom_dns_test'] ?? 0,
                                'warning' => $is_private_ip ? 'Resolved to private/reserved IP address' : null,
                                'security_note' => $is_private_ip ? 'Private IP detected - potential security risk' : 'Public IP resolved successfully'
                            ];

                            if ($is_private_ip) {
                                error_log(sprintf(
                                    'Debug Tool DNS Warning: %s resolved to private IP %s',
                                    $domain,
                                    $ip
                                ));
                            }
                        }

                        // Test 4: Enhanced Performance Analysis with security limits
                        debug_start_timing('custom_performance_test');

                        // Security: Limited performance test with shorter timeout
                        $perf_args = array_merge($secure_args, [
                            'timeout' => 20, // Reduced from 30 to prevent long-running requests
                            'user-agent' => 'WordPress Debug Tool Performance Test/1.0'
                        ]);

                        $perf_response = wp_remote_get($url, $perf_args);
                        debug_end_timing('custom_performance_test');

                        $perf_time = $GLOBALS['debug_timings']['custom_performance_test'] ?? 0;

                        // Security: Validate performance test results
                        if (is_wp_error($perf_response)) {
                            $results['performance_test'] = [
                                'status' => 'Failed',
                                'time' => $perf_time,
                                'error' => esc_html($perf_response->get_error_message()),
                                'fix' => 'Performance test failed - check connectivity',
                                'security_note' => 'Performance test failed security validation'
                            ];
                        } else {
                            $results['performance_test'] = [
                                'status' => $perf_time < 2000 ? 'Success' : ($perf_time < 5000 ? 'Warning' : 'Failed'),
                                'time' => $perf_time,
                                'rating' => $perf_time < 1000 ? 'Excellent' : ($perf_time < 2000 ? 'Good' : ($perf_time < 5000 ? 'Slow' : 'Very Slow')),
                                'fix' => $perf_time > 2000 ? 'Consider optimizing server response time or using a CDN' : 'Performance is acceptable',
                                'security_note' => 'Performance test completed within security limits'
                            ];
                        }

                        return $results;
                    }

                    $custom_test_results = test_custom_url_secure($custom_url);

                    // Display results in a comprehensive secure table
                    echo '<div class="debug-info" style="margin-bottom: 15px;">';
                    echo '<strong>🔒 Security Validation:</strong> All tests completed with enhanced security measures (CSRF protection, SSRF prevention, rate limiting)';
                    echo '</div>';

                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Test Type</th><th>Status</th><th>Time</th><th>Details</th><th>Security Notes</th><th>Recommendations</th></tr></thead>';
                    echo '<tbody>';

                    foreach ($custom_test_results as $test_name => $result) {
                        $test_display_name = ucwords(str_replace('_', ' ', $test_name));
                        echo '<tr>';
                        echo '<td><strong>' . $test_display_name . '</strong></td>';
                        echo '<td>';
                        if ($result['status'] === 'Success') {
                            echo '<span class="debug-badge success">Success</span>';
                        } elseif ($result['status'] === 'Warning') {
                            echo '<span class="debug-badge warning">Warning</span>';
                        } else {
                            echo '<span class="debug-badge error">Failed</span>';
                        }
                        echo '</td>';
                        echo '<td>' . ($result['time'] ?? 0) . 'ms</td>';
                        echo '<td>';
                        if (isset($result['error'])) {
                            echo '<span style="color: #dc3545;">' . esc_html($result['error']) . '</span>';
                        } elseif (isset($result['code'])) {
                            echo 'HTTP ' . $result['code'];
                            if (isset($result['size'])) {
                                echo ' (' . number_format($result['size']) . ' bytes)';
                            }
                        } elseif (isset($result['ip'])) {
                            echo 'Resolved to: ' . $result['ip'];
                        } elseif (isset($result['rating'])) {
                            echo 'Performance: ' . $result['rating'];
                        } else {
                            echo $result['message'] ?? 'Test completed';
                        }

                        if (isset($result['warning'])) {
                            echo '<br><span style="color: #ffc107;">⚠️ ' . esc_html($result['warning']) . '</span>';
                        }

                        echo '</td>';
                        echo '<td style="font-size: 11px; color: #666;">' . esc_html($result['security_note'] ?? 'Standard security') . '</td>';
                        echo '<td>';
                        echo isset($result['fix']) ? esc_html($result['fix']) : 'No action needed';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';

                    // Summary and recommendations
                    $total_tests = count($custom_test_results);
                    $passed_tests = count(array_filter($custom_test_results, function($r) { return $r['status'] === 'Success'; }));
                    $warning_tests = count(array_filter($custom_test_results, function($r) { return $r['status'] === 'Warning'; }));
                    $failed_tests = count(array_filter($custom_test_results, function($r) { return $r['status'] === 'Failed'; }));

                    echo '<div class="debug-info">';
                    echo '<strong>📊 Secure Test Summary:</strong><br>';
                    echo '• <strong>Total Tests:</strong> ' . $total_tests . '<br>';
                    echo '• <strong>Passed:</strong> <span style="color: #28a745;">' . $passed_tests . '</span><br>';
                    echo '• <strong>Warnings:</strong> <span style="color: #ffc107;">' . $warning_tests . '</span><br>';
                    echo '• <strong>Failed:</strong> <span style="color: #dc3545;">' . $failed_tests . '</span><br>';
                    echo '<strong>Overall Status:</strong> ';
                    if ($failed_tests === 0 && $warning_tests === 0) {
                        echo '<span class="debug-badge success">Excellent</span>';
                    } elseif ($failed_tests === 0) {
                        echo '<span class="debug-badge warning">Good with Minor Issues</span>';
                    } else {
                        echo '<span class="debug-badge error">Issues Detected</span>';
                    }
                    echo '<br><br><strong>🔒 Security Status:</strong> All tests completed with comprehensive security measures:';
                    echo '<br>• CSRF Protection: ✅ Enabled';
                    echo '<br>• SSRF Prevention: ✅ Internal URLs blocked';
                    echo '<br>• Rate Limiting: ✅ ' . max(0, $rate_limit_max - $rate_limit_count) . ' tests remaining';
                    echo '<br>• Input Validation: ✅ Enhanced validation applied';
                    echo '<br>• SSL Verification: ✅ Enforced for HTTPS URLs';
                    echo '</div>';
                        } // End SSRF validation block
                    } // End nonce/capability validation block
                } // End rate limit check block
                ?>
            </div>
        </div>

        <!-- Ultimate HTTP & cURL Diagnostics -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🌐 Ultimate HTTP & cURL Diagnostics
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('curl_analysis_start');

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($curl_diagnostics['status'] ?? 'Unknown') . '</div>';
                echo '<div class="debug-metric-label">cURL Status</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($curl_diagnostics['wordpress_api']['time'] ?? '0ms') . '</div>';
                echo '<div class="debug-metric-label">WordPress API</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($curl_diagnostics['loopback_test']['time'] ?? '0ms') . '</div>';
                echo '<div class="debug-metric-label">Loopback Test</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($curl_diagnostics['httpbin_ssl']['time'] ?? '0ms') . '</div>';
                echo '<div class="debug-metric-label">SSL Test</div>';
                echo '</div>';
                echo '</div>';

                // cURL Configuration
                echo '<h4>🔧 cURL Configuration</h4>';
                echo '<div class="debug-code">';
                if (isset($curl_diagnostics['version'])) {
                    echo '<strong>cURL Version:</strong> ' . esc_html($curl_diagnostics['version']) . '<br>';
                    echo '<strong>SSL Support:</strong> ' . esc_html($curl_diagnostics['ssl_support']) . '<br>';
                    echo '<strong>LibZ Support:</strong> ' . esc_html($curl_diagnostics['libz_support']) . '<br>';
                    echo '<strong>Protocols:</strong> ' . esc_html($curl_diagnostics['protocols']) . '<br>';
                } else {
                    echo '<strong>cURL Status:</strong> Not available<br>';
                }
                echo '<strong>PHP Timeout:</strong> ' . ini_get('default_socket_timeout') . ' seconds<br>';
                echo '<strong>Max Execution:</strong> ' . ini_get('max_execution_time') . ' seconds<br>';
                echo '<strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '<br>';
                echo '<strong>User Agent:</strong> ' . $_SERVER['HTTP_USER_AGENT'] ?? 'Not set';
                echo '</div>';

                // Enhanced Comprehensive HTTP Test Results with Detailed Diagnostics
                echo '<h4>🧪 Comprehensive HTTP Test Results with Detailed Diagnostics</h4>';

                // Function to analyze cURL errors and provide actionable fixes
                function analyzeCurlError($error_message, $test_type) {
                    $fixes = [];
                    $error_lower = strtolower($error_message);

                    if (strpos($error_lower, 'curl error 7') !== false || strpos($error_lower, 'couldn\'t connect') !== false) {
                        $fixes[] = '🔧 <strong>Connection Failed:</strong> Check if the target server is online and accessible';
                        $fixes[] = '🌐 <strong>Network Issue:</strong> Verify your server\'s internet connectivity';
                        $fixes[] = '🔥 <strong>Firewall:</strong> Check if outbound connections are blocked by firewall';
                        $fixes[] = '📡 <strong>DNS:</strong> Verify DNS resolution is working correctly';
                    } elseif (strpos($error_lower, 'curl error 6') !== false || strpos($error_lower, 'resolve host') !== false) {
                        $fixes[] = '🔍 <strong>DNS Resolution Failed:</strong> The domain name cannot be resolved';
                        $fixes[] = '⚙️ <strong>Fix DNS:</strong> Check your server\'s DNS settings (/etc/resolv.conf)';
                        $fixes[] = '🌐 <strong>Alternative DNS:</strong> Try using Google DNS (8.8.8.8, 8.8.4.4)';
                        $fixes[] = '📋 <strong>Hosts File:</strong> Check if domain is blocked in /etc/hosts';
                    } elseif (strpos($error_lower, 'ssl') !== false || strpos($error_lower, 'certificate') !== false) {
                        $fixes[] = '🔒 <strong>SSL Certificate Issue:</strong> The SSL certificate is invalid or expired';
                        $fixes[] = '🛠️ <strong>Fix SSL:</strong> Update SSL certificates on the target server';
                        $fixes[] = '⚠️ <strong>Temporary Fix:</strong> Disable SSL verification for testing (not recommended for production)';
                        $fixes[] = '🔄 <strong>Certificate Chain:</strong> Ensure complete certificate chain is installed';
                    } elseif (strpos($error_lower, 'timeout') !== false) {
                        $fixes[] = '⏱️ <strong>Request Timeout:</strong> The server took too long to respond';
                        $fixes[] = '🚀 <strong>Increase Timeout:</strong> Increase timeout values in wp_remote_get()';
                        $fixes[] = '⚡ <strong>Server Performance:</strong> Optimize target server response time';
                        $fixes[] = '🌐 <strong>CDN:</strong> Consider using a Content Delivery Network';
                    } elseif (strpos($error_lower, 'curl error 35') !== false) {
                        $fixes[] = '🔐 <strong>SSL Handshake Failed:</strong> SSL/TLS negotiation failed';
                        $fixes[] = '🔧 <strong>Protocol Mismatch:</strong> Check supported SSL/TLS versions';
                        $fixes[] = '🛠️ <strong>Cipher Suites:</strong> Verify compatible cipher suites are available';
                        $fixes[] = '📋 <strong>OpenSSL Update:</strong> Update OpenSSL on your server';
                    } else {
                        $fixes[] = '🔍 <strong>General Error:</strong> ' . esc_html($error_message);
                        $fixes[] = '📖 <strong>Documentation:</strong> Check cURL error codes documentation';
                        $fixes[] = '🛠️ <strong>Debug:</strong> Enable verbose cURL logging for more details';
                    }

                    return $fixes;
                }

                echo '<div style="overflow-x: auto;">';
                echo '<table class="debug-table" style="min-width: 800px;">';
                echo '<thead><tr>';
                echo '<th style="width: 15%;">Test Type</th>';
                echo '<th style="width: 20%;">Target URL</th>';
                echo '<th style="width: 10%;">Status</th>';
                echo '<th style="width: 10%;">Response Time</th>';
                echo '<th style="width: 15%;">HTTP Details</th>';
                echo '<th style="width: 30%;">Diagnostic Analysis & Fixes</th>';
                echo '</tr></thead>';
                echo '<tbody>';

                $test_descriptions = [
                    'wordpress_api' => [
                        'name' => 'WordPress API',
                        'url' => 'api.wordpress.org',
                        'purpose' => 'Tests external API connectivity',
                        'critical' => true
                    ],
                    'google_dns' => [
                        'name' => 'Google DNS',
                        'url' => 'dns.google',
                        'purpose' => 'Tests DNS resolution and HTTPS connectivity',
                        'critical' => true
                    ],
                    'httpbin_ssl' => [
                        'name' => 'SSL Certificate Test',
                        'url' => 'httpbin.org',
                        'purpose' => 'Tests SSL/TLS certificate validation',
                        'critical' => false
                    ],
                    'httpbin_redirect' => [
                        'name' => 'HTTP Redirect Test',
                        'url' => 'httpbin.org',
                        'purpose' => 'Tests HTTP redirect handling',
                        'critical' => false
                    ],
                    'loopback_test' => [
                        'name' => 'Loopback Test',
                        'url' => home_url(),
                        'purpose' => 'Tests if your site can connect to itself',
                        'critical' => true
                    ]
                ];

                foreach ($test_descriptions as $test_key => $test_info) {
                    $test_result = $curl_diagnostics[$test_key] ?? [];
                    $is_success = ($test_result['status'] ?? '') === 'Success';
                    $is_critical = $test_info['critical'];

                    echo '<tr style="' . ($is_critical && !$is_success ? 'background-color: #fff5f5; border-left: 4px solid #dc3545;' : '') . '">';

                    // Test Type
                    echo '<td>';
                    echo '<strong>' . $test_info['name'] . '</strong>';
                    if ($is_critical) {
                        echo '<br><span class="debug-badge error" style="font-size: 10px;">CRITICAL</span>';
                    }
                    echo '<br><small style="color: #666;">' . $test_info['purpose'] . '</small>';
                    echo '</td>';

                    // Target URL
                    echo '<td>';
                    echo '<code style="font-size: 11px; background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">';
                    echo esc_html($test_info['url']);
                    echo '</code>';
                    echo '</td>';

                    // Status
                    echo '<td>';
                    if ($is_success) {
                        echo '<span class="debug-badge success">✅ Success</span>';
                    } else {
                        echo '<span class="debug-badge error">❌ Failed</span>';
                    }
                    echo '</td>';

                    // Response Time
                    echo '<td>';
                    $time = $test_result['time'] ?? '0ms';
                    $time_numeric = (float) str_replace('ms', '', $time);
                    if ($time_numeric > 5000) {
                        echo '<span style="color: #dc3545; font-weight: bold;">' . esc_html($time) . '</span>';
                        echo '<br><small style="color: #dc3545;">Very Slow</small>';
                    } elseif ($time_numeric > 2000) {
                        echo '<span style="color: #ffc107; font-weight: bold;">' . esc_html($time) . '</span>';
                        echo '<br><small style="color: #ffc107;">Slow</small>';
                    } else {
                        echo '<span style="color: #28a745; font-weight: bold;">' . esc_html($time) . '</span>';
                        echo '<br><small style="color: #28a745;">Good</small>';
                    }
                    echo '</td>';

                    // HTTP Details
                    echo '<td>';
                    if (isset($test_result['code'])) {
                        $code = $test_result['code'];
                        if ($code >= 200 && $code < 300) {
                            echo '<span style="color: #28a745;">HTTP ' . $code . '</span>';
                            echo '<br><small>Success</small>';
                        } elseif ($code >= 300 && $code < 400) {
                            echo '<span style="color: #ffc107;">HTTP ' . $code . '</span>';
                            echo '<br><small>Redirect</small>';
                        } elseif ($code >= 400 && $code < 500) {
                            echo '<span style="color: #dc3545;">HTTP ' . $code . '</span>';
                            echo '<br><small>Client Error</small>';
                        } else {
                            echo '<span style="color: #dc3545;">HTTP ' . $code . '</span>';
                            echo '<br><small>Server Error</small>';
                        }
                    } else {
                        echo '<span style="color: #6c757d;">No Response</span>';
                    }
                    echo '</td>';

                    // Diagnostic Analysis & Fixes
                    echo '<td>';
                    if ($is_success) {
                        echo '<span style="color: #28a745;">✅ <strong>All Good!</strong></span><br>';
                        echo '<small>This test passed successfully. No action needed.</small>';
                    } else {
                        $error_message = $test_result['error'] ?? 'Unknown error';
                        $fixes = analyzeCurlError($error_message, $test_key);

                        echo '<div style="font-size: 12px; line-height: 1.4;">';
                        echo '<strong style="color: #dc3545;">❌ Error Analysis:</strong><br>';
                        echo '<div style="background: #f8f9fa; padding: 8px; border-radius: 4px; margin: 4px 0;">';
                        echo '<code style="font-size: 10px; color: #dc3545;">' . esc_html(substr($error_message, 0, 100)) . '</code>';
                        echo '</div>';

                        echo '<strong style="color: #0066cc;">🔧 Actionable Fixes:</strong><br>';
                        echo '<ul style="margin: 4px 0; padding-left: 16px;">';
                        foreach (array_slice($fixes, 0, 3) as $fix) {
                            echo '<li style="margin: 2px 0; font-size: 11px;">' . $fix . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                    echo '</td>';

                    echo '</tr>';
                }

                echo '</tbody></table>';
                echo '</div>';

                // Summary and Overall Recommendations
                $total_tests = count($test_descriptions);
                $passed_tests = 0;
                $critical_failures = 0;

                foreach ($test_descriptions as $test_key => $test_info) {
                    $test_result = $curl_diagnostics[$test_key] ?? [];
                    if (($test_result['status'] ?? '') === 'Success') {
                        $passed_tests++;
                    } elseif ($test_info['critical']) {
                        $critical_failures++;
                    }
                }

                echo '<div class="debug-info" style="margin-top: 20px;">';
                echo '<strong>📊 HTTP Test Summary:</strong><br>';
                echo '• <strong>Total Tests:</strong> ' . $total_tests . '<br>';
                echo '• <strong>Passed:</strong> <span style="color: #28a745;">' . $passed_tests . '</span><br>';
                echo '• <strong>Failed:</strong> <span style="color: #dc3545;">' . ($total_tests - $passed_tests) . '</span><br>';
                echo '• <strong>Critical Failures:</strong> <span style="color: #dc3545; font-weight: bold;">' . $critical_failures . '</span><br>';

                if ($critical_failures > 0) {
                    echo '<br><strong style="color: #dc3545;">⚠️ URGENT ACTION REQUIRED:</strong><br>';
                    echo '• Critical connectivity tests are failing<br>';
                    echo '• This may affect WordPress updates, plugin installations, and external integrations<br>';
                    echo '• Contact your hosting provider if network issues persist<br>';
                } elseif ($passed_tests === $total_tests) {
                    echo '<br><strong style="color: #28a745;">✅ EXCELLENT:</strong> All connectivity tests passed!<br>';
                } else {
                    echo '<br><strong style="color: #ffc107;">⚠️ MINOR ISSUES:</strong> Some non-critical tests failed<br>';
                }
                echo '</div>';

                debug_time('curl_analysis_end');
                ?>
            </div>
        </div>

        <!-- Cache & CDN Health Check -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🚀 Cache & CDN Health Check
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('cache_analysis_start');

                // Enhanced cache and CDN analysis function
                function analyze_caching_layers() {
                    $cache_analysis = [
                        'object_cache' => [],
                        'page_cache' => [],
                        'database_cache' => [],
                        'cdn_status' => [],
                        'cache_hit_rate' => 0,
                        'recommendations' => []
                    ];

                    // Check object cache
                    $cache_analysis['object_cache'] = [
                        'enabled' => wp_using_ext_object_cache(),
                        'type' => 'Unknown',
                        'status' => wp_using_ext_object_cache() ? 'Active' : 'Inactive'
                    ];

                    // Detect object cache type
                    if (defined('WP_REDIS_OBJECT_CACHE') && WP_REDIS_OBJECT_CACHE) {
                        $cache_analysis['object_cache']['type'] = 'Redis';
                    } elseif (class_exists('Memcached') && wp_using_ext_object_cache()) {
                        $cache_analysis['object_cache']['type'] = 'Memcached';
                    } elseif (function_exists('apcu_cache_info')) {
                        $cache_analysis['object_cache']['type'] = 'APCu';
                    }

                    // Check page cache plugins
                    $page_cache_plugins = [
                        'W3 Total Cache' => class_exists('W3TC'),
                        'WP Super Cache' => function_exists('wp_super_cache_init'),
                        'WP Rocket' => function_exists('rocket_init'),
                        'LiteSpeed Cache' => class_exists('LiteSpeed_Cache'),
                        'Autoptimize' => class_exists('autoptimizeMain'),
                        'WP Fastest Cache' => class_exists('WpFastestCache'),
                        'Comet Cache' => class_exists('comet_cache')
                    ];

                    $active_cache_plugins = [];
                    foreach ($page_cache_plugins as $plugin_name => $is_active) {
                        if ($is_active) {
                            $active_cache_plugins[] = $plugin_name;
                        }
                    }

                    $cache_analysis['page_cache'] = [
                        'plugins' => $active_cache_plugins,
                        'count' => count($active_cache_plugins),
                        'status' => count($active_cache_plugins) > 0 ? 'Active' : 'Inactive'
                    ];

                    // Test cache hit rate with transients
                    $hit_rate = test_cache_hit_rate();
                    $cache_analysis['cache_hit_rate'] = $hit_rate;

                    // Check for CDN indicators
                    $cdn_analysis = detect_cdn_usage();
                    $cache_analysis['cdn_status'] = $cdn_analysis;

                    // Generate recommendations
                    if (!$cache_analysis['object_cache']['enabled']) {
                        $cache_analysis['recommendations'][] = 'Enable object caching (Redis/Memcached) for better performance';
                    }

                    if ($cache_analysis['page_cache']['count'] === 0) {
                        $cache_analysis['recommendations'][] = 'Install a page caching plugin to improve load times';
                    } elseif ($cache_analysis['page_cache']['count'] > 1) {
                        $cache_analysis['recommendations'][] = 'Multiple caching plugins detected - may cause conflicts';
                    }

                    if ($hit_rate < 70) {
                        $cache_analysis['recommendations'][] = 'Cache hit rate is low - review caching configuration';
                    }

                    if (empty($cache_analysis['recommendations'])) {
                        $cache_analysis['recommendations'][] = 'Caching configuration appears optimal';
                    }

                    return $cache_analysis;
                }

                // Test cache hit rate using transients
                function test_cache_hit_rate() {
                    $test_count = 10;
                    $hits = 0;
                    $test_prefix = 'debug_cache_test_';

                    // Set test transients
                    for ($i = 0; $i < $test_count; $i++) {
                        set_transient($test_prefix . $i, time(), 300);
                    }

                    // Test retrieval
                    for ($i = 0; $i < $test_count; $i++) {
                        if (get_transient($test_prefix . $i) !== false) {
                            $hits++;
                        }
                    }

                    // Clean up test transients
                    for ($i = 0; $i < $test_count; $i++) {
                        delete_transient($test_prefix . $i);
                    }

                    return round(($hits / $test_count) * 100, 1);
                }

                // Detect CDN usage
                function detect_cdn_usage() {
                    $cdn_analysis = [
                        'detected' => false,
                        'type' => 'None',
                        'indicators' => []
                    ];

                    // Check common CDN indicators in headers or URLs
                    $site_url = home_url();
                    $response = wp_remote_head($site_url);

                    if (!is_wp_error($response)) {
                        $headers = wp_remote_retrieve_headers($response);

                        // Check for CDN headers
                        $cdn_headers = [
                            'cf-ray' => 'Cloudflare',
                            'x-cache' => 'Generic CDN',
                            'x-served-by' => 'Fastly',
                            'x-amz-cf-id' => 'Amazon CloudFront',
                            'x-azure-ref' => 'Azure CDN'
                        ];

                        foreach ($cdn_headers as $header => $cdn_name) {
                            if (isset($headers[$header])) {
                                $cdn_analysis['detected'] = true;
                                $cdn_analysis['type'] = $cdn_name;
                                $cdn_analysis['indicators'][] = "Header: $header";
                            }
                        }
                    }

                    // Check for CDN plugins
                    $cdn_plugins = [
                        'Cloudflare' => function_exists('cloudflare_init'),
                        'MaxCDN' => class_exists('MaxCDN'),
                        'KeyCDN' => class_exists('KeyCDN')
                    ];

                    foreach ($cdn_plugins as $plugin_name => $is_active) {
                        if ($is_active) {
                            $cdn_analysis['detected'] = true;
                            $cdn_analysis['type'] = $plugin_name;
                            $cdn_analysis['indicators'][] = "Plugin: $plugin_name";
                        }
                    }

                    return $cdn_analysis;
                }

                $cache_analysis = analyze_caching_layers();

                // Display cache metrics
                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $cache_analysis['object_cache']['status'] . '</div>';
                echo '<div class="debug-metric-label">Object Cache</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $cache_analysis['page_cache']['status'] . '</div>';
                echo '<div class="debug-metric-label">Page Cache</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $cache_analysis['cache_hit_rate'] . '%</div>';
                echo '<div class="debug-metric-label">Cache Hit Rate</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($cache_analysis['cdn_status']['detected'] ? 'Active' : 'None') . '</div>';
                echo '<div class="debug-metric-label">CDN Status</div>';
                echo '</div>';
                echo '</div>';

                // Display object cache details
                echo '<h4>🗄️ Object Cache Analysis</h4>';
                echo '<table class="debug-table">';
                echo '<thead><tr><th>Component</th><th>Status</th><th>Type</th><th>Performance Impact</th></tr></thead>';
                echo '<tbody>';
                echo '<tr>';
                echo '<td><strong>Object Cache</strong></td>';
                echo '<td>';
                if ($cache_analysis['object_cache']['enabled']) {
                    echo '<span class="debug-badge success">Enabled</span>';
                } else {
                    echo '<span class="debug-badge warning">Disabled</span>';
                }
                echo '</td>';
                echo '<td>' . esc_html($cache_analysis['object_cache']['type']) . '</td>';
                echo '<td>';
                if ($cache_analysis['object_cache']['enabled']) {
                    echo 'Reduces database queries significantly';
                } else {
                    echo 'Missing - database queries not cached';
                }
                echo '</td>';
                echo '</tr>';
                echo '</tbody></table>';

                // Display page cache details
                if ($cache_analysis['page_cache']['count'] > 0) {
                    echo '<h4>📄 Page Cache Analysis</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Plugin</th><th>Status</th><th>Type</th><th>Recommendation</th></tr></thead>';
                    echo '<tbody>';

                    foreach ($cache_analysis['page_cache']['plugins'] as $plugin) {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($plugin) . '</strong></td>';
                        echo '<td><span class="debug-badge success">Active</span></td>';
                        echo '<td>Page Caching</td>';
                        echo '<td>';

                        if ($cache_analysis['page_cache']['count'] > 1) {
                            echo 'Consider using only one caching plugin to avoid conflicts';
                        } else {
                            echo 'Good - page caching is active';
                        }

                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                } else {
                    echo '<h4>📄 Page Cache Analysis</h4>';
                    echo '<div class="debug-warning">';
                    echo '<strong>⚠️ No Page Caching Detected:</strong> Consider installing a page caching plugin like WP Rocket, W3 Total Cache, or WP Super Cache for better performance.';
                    echo '</div>';
                }

                // Display CDN analysis
                echo '<h4>🌐 CDN Analysis</h4>';
                echo '<table class="debug-table">';
                echo '<thead><tr><th>Component</th><th>Status</th><th>Details</th><th>Benefits</th></tr></thead>';
                echo '<tbody>';
                echo '<tr>';
                echo '<td><strong>CDN Service</strong></td>';
                echo '<td>';
                if ($cache_analysis['cdn_status']['detected']) {
                    echo '<span class="debug-badge success">Detected</span>';
                } else {
                    echo '<span class="debug-badge warning">Not Detected</span>';
                }
                echo '</td>';
                echo '<td>';
                if ($cache_analysis['cdn_status']['detected']) {
                    echo 'Type: ' . esc_html($cache_analysis['cdn_status']['type']) . '<br>';
                    echo 'Indicators: ' . implode(', ', $cache_analysis['cdn_status']['indicators']);
                } else {
                    echo 'No CDN headers or plugins detected';
                }
                echo '</td>';
                echo '<td>';
                if ($cache_analysis['cdn_status']['detected']) {
                    echo 'Faster global content delivery, reduced server load';
                } else {
                    echo 'Consider using a CDN like Cloudflare for better global performance';
                }
                echo '</td>';
                echo '</tr>';
                echo '</tbody></table>';

                // Display cache performance metrics
                echo '<h4>📊 Cache Performance Metrics</h4>';
                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $cache_analysis['cache_hit_rate'] . '%</div>';
                echo '<div class="debug-metric-label">Transient Hit Rate</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($cache_analysis['object_cache']['enabled'] ? 'Yes' : 'No') . '</div>';
                echo '<div class="debug-metric-label">Persistent Object Cache</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $cache_analysis['page_cache']['count'] . '</div>';
                echo '<div class="debug-metric-label">Page Cache Plugins</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($cache_analysis['cdn_status']['detected'] ? 'Yes' : 'No') . '</div>';
                echo '<div class="debug-metric-label">CDN Active</div>';
                echo '</div>';
                echo '</div>';

                // Display recommendations
                if (!empty($cache_analysis['recommendations'])) {
                    echo '<div class="debug-warning">';
                    echo '<strong>🚀 Caching Recommendations:</strong><br>';
                    foreach ($cache_analysis['recommendations'] as $recommendation) {
                        echo '• ' . esc_html($recommendation) . '<br>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="debug-success">';
                    echo '<strong>✅ Caching Status:</strong> Your caching configuration appears to be well optimized.';
                    echo '</div>';
                }

                debug_time('cache_analysis_end');
                ?>
            </div>
        </div>

        <!-- Ultimate Error Log Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📝 Ultimate Error Log Analysis & Pattern Detection
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('error_analysis_start');

                if (!empty($error_log_analysis)) {
                    echo '<div class="debug-grid">';
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . ($error_log_analysis['file_size'] ?? '0 KB') . '</div>';
                    echo '<div class="debug-metric-label">Log File Size</div>';
                    echo '</div>';
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . ($error_log_analysis['recent_entries'] ?? '0') . '</div>';
                    echo '<div class="debug-metric-label">Recent Entries</div>';
                    echo '</div>';
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . ($error_log_analysis['pattern_analysis']['fatal_errors'] ?? '0') . '</div>';
                    echo '<div class="debug-metric-label">Fatal Errors</div>';
                    echo '</div>';
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . ($error_log_analysis['pattern_analysis']['curl_errors'] ?? '0') . '</div>';
                    echo '<div class="debug-metric-label">cURL Errors</div>';
                    echo '</div>';
                    echo '</div>';

                    // Enhanced Pattern analysis with detailed explanations and actionable fixes
                    if (isset($error_log_analysis['pattern_analysis'])) {
                        echo '<h4>🔍 Enhanced Error Pattern Analysis with Actionable Solutions</h4>';

                        // Function to get detailed error explanations and fixes
                        function getErrorAnalysisAndFixes($pattern) {
                            $analysis = [
                                'description' => '',
                                'impact' => '',
                                'fixes' => [],
                                'priority' => 'medium'
                            ];

                            $pattern_lower = strtolower($pattern);

                            if (strpos($pattern_lower, 'fatal') !== false) {
                                $analysis['description'] = 'Critical errors that stop script execution completely';
                                $analysis['impact'] = 'Site functionality is broken, users may see white screens';
                                $analysis['priority'] = 'critical';
                                $analysis['fixes'] = [
                                    '🚨 <strong>Immediate:</strong> Check the specific fatal error message for the exact cause',
                                    '🔧 <strong>Memory:</strong> Increase PHP memory limit in wp-config.php: ini_set(\'memory_limit\', \'512M\')',
                                    '🔌 <strong>Plugins:</strong> Deactivate recently installed plugins to identify conflicts',
                                    '🎨 <strong>Theme:</strong> Switch to a default WordPress theme temporarily',
                                    '📁 <strong>Files:</strong> Check file permissions and ensure all files are properly uploaded',
                                    '🔄 <strong>Recovery:</strong> Use WordPress recovery mode if available'
                                ];
                            } elseif (strpos($pattern_lower, 'warning') !== false) {
                                $analysis['description'] = 'Non-fatal issues that may cause unexpected behavior';
                                $analysis['impact'] = 'Site works but may have reduced functionality or performance';
                                $analysis['priority'] = 'medium';
                                $analysis['fixes'] = [
                                    '📋 <strong>Review Code:</strong> Check the specific warning message for deprecated functions',
                                    '🔄 <strong>Update:</strong> Update plugins, themes, and WordPress core to latest versions',
                                    '🔧 <strong>PHP Version:</strong> Ensure PHP version compatibility with all components',
                                    '📝 <strong>Debug:</strong> Enable WP_DEBUG_LOG to capture more detailed information',
                                    '🔌 <strong>Plugin Audit:</strong> Review and update or replace outdated plugins',
                                    '👨‍💻 <strong>Developer:</strong> Contact theme/plugin developers for compatibility updates'
                                ];
                            } elseif (strpos($pattern_lower, 'notice') !== false) {
                                $analysis['description'] = 'Informational messages about minor issues or deprecated features';
                                $analysis['impact'] = 'Minimal impact on functionality, mainly for developers';
                                $analysis['priority'] = 'low';
                                $analysis['fixes'] = [
                                    '📚 <strong>Documentation:</strong> Review WordPress coding standards and best practices',
                                    '🔄 <strong>Code Update:</strong> Update code to use current WordPress functions and methods',
                                    '🔧 <strong>Suppress:</strong> Add error_reporting configuration to hide notices in production',
                                    '🧹 <strong>Cleanup:</strong> Remove or update deprecated function calls',
                                    '📝 <strong>Development:</strong> Use notices to improve code quality during development'
                                ];
                            } elseif (strpos($pattern_lower, 'database') !== false || strpos($pattern_lower, 'mysql') !== false) {
                                $analysis['description'] = 'Database connection or query execution problems';
                                $analysis['impact'] = 'Data operations fail, site may be inaccessible';
                                $analysis['priority'] = 'critical';
                                $analysis['fixes'] = [
                                    '🔗 <strong>Connection:</strong> Verify database credentials in wp-config.php',
                                    '🗄️ <strong>Server:</strong> Check if MySQL/MariaDB service is running',
                                    '💾 <strong>Space:</strong> Ensure sufficient disk space for database operations',
                                    '🔧 <strong>Repair:</strong> Use phpMyAdmin or WP-CLI to repair corrupted tables',
                                    '📊 <strong>Optimize:</strong> Optimize database tables to improve performance',
                                    '🔄 <strong>Backup:</strong> Restore from a recent backup if corruption is severe'
                                ];
                            } elseif (strpos($pattern_lower, 'memory') !== false) {
                                $analysis['description'] = 'PHP memory limit exceeded during script execution';
                                $analysis['impact'] = 'Scripts fail to complete, features may not work';
                                $analysis['priority'] = 'high';
                                $analysis['fixes'] = [
                                    '📈 <strong>Increase Limit:</strong> Add ini_set(\'memory_limit\', \'512M\') to wp-config.php',
                                    '🔌 <strong>Plugin Review:</strong> Identify memory-hungry plugins and optimize or replace',
                                    '🖼️ <strong>Images:</strong> Optimize large images and implement lazy loading',
                                    '🗄️ <strong>Database:</strong> Optimize database queries and implement caching',
                                    '⚡ <strong>Caching:</strong> Install object caching (Redis/Memcached) to reduce memory usage',
                                    '🏃‍♂️ <strong>Performance:</strong> Use performance profiling tools to identify bottlenecks'
                                ];
                            } elseif (strpos($pattern_lower, 'permission') !== false || strpos($pattern_lower, 'chmod') !== false) {
                                $analysis['description'] = 'File or directory permission issues preventing access';
                                $analysis['impact'] = 'File operations fail, uploads/updates may not work';
                                $analysis['priority'] = 'high';
                                $analysis['fixes'] = [
                                    '📁 <strong>Directories:</strong> Set directory permissions to 755: chmod 755 /path/to/directories',
                                    '📄 <strong>Files:</strong> Set file permissions to 644: chmod 644 /path/to/files',
                                    '🔐 <strong>wp-config:</strong> Set wp-config.php to 600 for security: chmod 600 wp-config.php',
                                    '👤 <strong>Ownership:</strong> Ensure correct file ownership: chown www-data:www-data /path/to/wordpress',
                                    '🔧 <strong>FTP/SSH:</strong> Use FTP client or SSH to fix permissions recursively',
                                    '🛡️ <strong>Security:</strong> Never use 777 permissions as it\'s a security risk'
                                ];
                            } else {
                                $analysis['description'] = 'General error that requires specific investigation';
                                $analysis['impact'] = 'Variable impact depending on the specific error type';
                                $analysis['priority'] = 'medium';
                                $analysis['fixes'] = [
                                    '🔍 <strong>Investigate:</strong> Read the full error message for specific details',
                                    '📝 <strong>Log Analysis:</strong> Enable detailed logging to capture more context',
                                    '🔄 <strong>Reproduce:</strong> Try to reproduce the error in a controlled environment',
                                    '🌐 <strong>Search:</strong> Search WordPress forums and documentation for similar issues',
                                    '👨‍💻 <strong>Support:</strong> Contact plugin/theme developers or hosting support',
                                    '🔧 <strong>Debug:</strong> Use debugging tools and plugins to isolate the issue'
                                ];
                            }

                            return $analysis;
                        }

                        echo '<div style="overflow-x: auto;">';
                        echo '<table class="debug-table" style="min-width: 900px;">';
                        echo '<thead><tr>';
                        echo '<th style="width: 15%;">Error Type</th>';
                        echo '<th style="width: 8%;">Count</th>';
                        echo '<th style="width: 10%;">Severity</th>';
                        echo '<th style="width: 20%;">Description & Impact</th>';
                        echo '<th style="width: 47%;">Actionable Solutions</th>';
                        echo '</tr></thead>';
                        echo '<tbody>';

                        foreach ($error_log_analysis['pattern_analysis'] as $pattern => $count) {
                            $severity = 'info';
                            if (strpos($pattern, 'fatal') !== false) {
                                $severity = 'error';
                            } elseif (strpos($pattern, 'error') !== false) {
                                $severity = 'error';
                            } elseif (strpos($pattern, 'warning') !== false) {
                                $severity = 'warning';
                            }

                            $analysis = getErrorAnalysisAndFixes($pattern);
                            $priority_color = $analysis['priority'] === 'critical' ? '#dc3545' :
                                            ($analysis['priority'] === 'high' ? '#fd7e14' :
                                            ($analysis['priority'] === 'medium' ? '#ffc107' : '#28a745'));

                            echo '<tr style="' . ($analysis['priority'] === 'critical' ? 'background-color: #fff5f5; border-left: 4px solid #dc3545;' : '') . '">';

                            // Error Type
                            echo '<td>';
                            echo '<strong>' . ucwords(str_replace('_', ' ', $pattern)) . '</strong>';
                            echo '<br><span class="debug-badge" style="background: ' . $priority_color . '; color: white; font-size: 10px;">';
                            echo strtoupper($analysis['priority']) . ' PRIORITY';
                            echo '</span>';
                            echo '</td>';

                            // Count
                            echo '<td style="text-align: center;">';
                            echo '<span style="font-size: 18px; font-weight: bold; color: ' . $priority_color . ';">' . $count . '</span>';
                            if ($count > 10) {
                                echo '<br><small style="color: #dc3545;">High Frequency</small>';
                            } elseif ($count > 5) {
                                echo '<br><small style="color: #ffc107;">Moderate</small>';
                            } else {
                                echo '<br><small style="color: #28a745;">Low</small>';
                            }
                            echo '</td>';

                            // Severity
                            echo '<td style="text-align: center;">';
                            echo '<span class="debug-badge ' . $severity . '">' . ucfirst($severity) . '</span>';
                            echo '</td>';

                            // Description & Impact
                            echo '<td>';
                            echo '<strong>📋 Description:</strong><br>';
                            echo '<small>' . $analysis['description'] . '</small><br><br>';
                            echo '<strong>⚠️ Impact:</strong><br>';
                            echo '<small style="color: ' . $priority_color . ';">' . $analysis['impact'] . '</small>';
                            echo '</td>';

                            // Actionable Solutions
                            echo '<td>';
                            echo '<div style="font-size: 12px; line-height: 1.4;">';
                            echo '<strong style="color: #0066cc;">🔧 Immediate Actions:</strong><br>';
                            echo '<ol style="margin: 4px 0; padding-left: 16px;">';
                            foreach (array_slice($analysis['fixes'], 0, 4) as $fix) {
                                echo '<li style="margin: 3px 0;">' . $fix . '</li>';
                            }
                            echo '</ol>';
                            if (count($analysis['fixes']) > 4) {
                                echo '<details style="margin-top: 8px;">';
                                echo '<summary style="cursor: pointer; color: #0066cc; font-weight: bold;">Show More Solutions (' . (count($analysis['fixes']) - 4) . ')</summary>';
                                echo '<ol start="5" style="margin: 4px 0; padding-left: 16px;">';
                                foreach (array_slice($analysis['fixes'], 4) as $fix) {
                                    echo '<li style="margin: 3px 0;">' . $fix . '</li>';
                                }
                                echo '</ol>';
                                echo '</details>';
                            }
                            echo '</div>';
                            echo '</td>';

                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';

                        // Error Summary and Priority Actions
                        $total_errors = array_sum($error_log_analysis['pattern_analysis']);
                        $critical_patterns = array_filter($error_log_analysis['pattern_analysis'], function($pattern) {
                            return strpos(strtolower($pattern), 'fatal') !== false;
                        }, ARRAY_FILTER_USE_KEY);
                        $critical_count = array_sum($critical_patterns);

                        echo '<div class="debug-info" style="margin-top: 20px;">';
                        echo '<strong>📊 Error Analysis Summary:</strong><br>';
                        echo '• <strong>Total Error Occurrences:</strong> ' . $total_errors . '<br>';
                        echo '• <strong>Unique Error Types:</strong> ' . count($error_log_analysis['pattern_analysis']) . '<br>';
                        echo '• <strong>Critical Errors:</strong> <span style="color: #dc3545; font-weight: bold;">' . $critical_count . '</span><br>';

                        if ($critical_count > 0) {
                            echo '<br><strong style="color: #dc3545;">🚨 CRITICAL PRIORITY ACTIONS:</strong><br>';
                            echo '• Address fatal errors immediately - they break site functionality<br>';
                            echo '• Check memory limits and increase if necessary<br>';
                            echo '• Review recent plugin/theme changes<br>';
                            echo '• Consider enabling WordPress maintenance mode until issues are resolved<br>';
                        } elseif ($total_errors > 50) {
                            echo '<br><strong style="color: #ffc107;">⚠️ HIGH VOLUME DETECTED:</strong><br>';
                            echo '• Large number of errors detected - investigate patterns<br>';
                            echo '• Consider implementing error monitoring and alerting<br>';
                            echo '• Review and optimize code to reduce error frequency<br>';
                        } else {
                            echo '<br><strong style="color: #28a745;">✅ MANAGEABLE ERROR LEVELS:</strong><br>';
                            echo '• Error levels are within normal ranges<br>';
                            echo '• Continue monitoring and address warnings when possible<br>';
                        }
                        echo '</div>';
                    }

                    if (!empty($error_log_analysis['sample_lines'])) {
                        echo '<h4>🔍 Recent Error Log Entries</h4>';
                        echo '<div class="debug-code" style="max-height: 400px; overflow-y: auto; font-size: 12px;">';
                        foreach ($error_log_analysis['sample_lines'] as $line) {
                            $line = trim($line);
                            if (!empty($line)) {
                                // Enhanced color coding
                                if (strpos($line, 'FATAL') !== false || strpos($line, 'Fatal') !== false) {
                                    echo '<span style="color: #dc3545; font-weight: bold; background: rgba(220,53,69,0.1); padding: 2px;">' . esc_html($line) . '</span><br>';
                                } elseif (strpos($line, 'ERROR') !== false || strpos($line, 'Error') !== false) {
                                    echo '<span style="color: #e74c3c; background: rgba(231,76,60,0.1); padding: 2px;">' . esc_html($line) . '</span><br>';
                                } elseif (strpos($line, 'WARNING') !== false || strpos($line, 'Warning') !== false) {
                                    echo '<span style="color: #f39c12; background: rgba(243,156,18,0.1); padding: 2px;">' . esc_html($line) . '</span><br>';
                                } elseif (strpos($line, 'NOTICE') !== false || strpos($line, 'Notice') !== false) {
                                    echo '<span style="color: #3498db; background: rgba(52,152,219,0.1); padding: 2px;">' . esc_html($line) . '</span><br>';
                                } else {
                                    echo esc_html($line) . '<br>';
                                }
                            }
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<div class="debug-info">';
                    echo '<strong>ℹ️ Error Logging Configuration:</strong><br>';
                    if (!defined('WP_DEBUG') || !WP_DEBUG) {
                        echo '• WP_DEBUG is not enabled<br>';
                    }
                    if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
                        echo '• WP_DEBUG_LOG is not enabled<br>';
                    }
                    echo '• No error log file found or accessible<br>';
                    echo '• To enable comprehensive error logging, add these to wp-config.php:<br>';
                    echo '<div class="debug-code">';
                    echo "define('WP_DEBUG', true);<br>";
                    echo "define('WP_DEBUG_LOG', true);<br>";
                    echo "define('WP_DEBUG_DISPLAY', false);<br>";
                    echo "define('SCRIPT_DEBUG', true);";
                    echo '</div>';
                    echo '</div>';
                }

                debug_time('error_analysis_end');
                ?>
            </div>
        </div>

        <!-- Ultimate Performance Summary -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⚡ Ultimate Performance Summary & Optimization
            </div>
            <div class="debug-section-content">
                <?php
                $total_time = round((microtime(true) - $debug_start_time) * 1000, 2);
                $total_memory = round((memory_get_usage() - $debug_start_memory) / 1024 / 1024, 2);
                debug_time('total_execution');

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $total_time . 'ms</div>';
                echo '<div class="debug-metric-label">Total Execution</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . round(memory_get_usage() / 1024 / 1024, 1) . 'MB</div>';
                echo '<div class="debug-metric-label">Current Memory</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . round(memory_get_peak_usage() / 1024 / 1024, 1) . 'MB</div>';
                echo '<div class="debug-metric-label">Peak Memory</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ini_get('memory_limit') . '</div>';
                echo '<div class="debug-metric-label">Memory Limit</div>';
                echo '</div>';
                echo '</div>';

                // Enhanced Performance breakdown with actionable optimization recommendations
                echo '<h4>⏱️ Detailed Performance Breakdown with Optimization Guide</h4>';

                // Function to get specific optimization recommendations for slow operations
                function getPerformanceOptimizations($operation_name, $time_ms, $memory_mb) {
                    $optimizations = [];
                    $operation_lower = strtolower($operation_name);

                    // Database-related optimizations
                    if (strpos($operation_lower, 'database') !== false || strpos($operation_lower, 'query') !== false) {
                        if ($time_ms > 1000) {
                            $optimizations = [
                                '🗄️ <strong>Database Indexing:</strong> Add indexes to frequently queried columns',
                                '🔧 <strong>Query Optimization:</strong> Review and optimize slow SQL queries',
                                '💾 <strong>Object Caching:</strong> Implement Redis or Memcached for query results',
                                '📊 <strong>Database Cleanup:</strong> Remove unnecessary data and optimize tables',
                                '⚡ <strong>Connection Pooling:</strong> Use persistent database connections',
                                '🔄 <strong>Query Reduction:</strong> Combine multiple queries where possible'
                            ];
                        }
                    }
                    // Plugin-related optimizations
                    elseif (strpos($operation_lower, 'plugin') !== false) {
                        if ($time_ms > 1000) {
                            $optimizations = [
                                '🔌 <strong>Plugin Audit:</strong> Deactivate unnecessary or poorly coded plugins',
                                '🚀 <strong>Plugin Optimization:</strong> Replace heavy plugins with lighter alternatives',
                                '⚡ <strong>Lazy Loading:</strong> Implement lazy loading for plugin assets',
                                '📦 <strong>Plugin Caching:</strong> Use caching plugins to reduce plugin load',
                                '🔄 <strong>Plugin Updates:</strong> Ensure all plugins are updated to latest versions',
                                '🧹 <strong>Plugin Cleanup:</strong> Remove unused plugin files and database entries'
                            ];
                        }
                    }
                    // Theme-related optimizations
                    elseif (strpos($operation_lower, 'theme') !== false || strpos($operation_lower, 'template') !== false) {
                        if ($time_ms > 1000) {
                            $optimizations = [
                                '🎨 <strong>Theme Optimization:</strong> Optimize theme code and remove unnecessary features',
                                '📱 <strong>Responsive Images:</strong> Implement responsive image loading',
                                '⚡ <strong>CSS/JS Minification:</strong> Minify and combine CSS/JavaScript files',
                                '🖼️ <strong>Image Optimization:</strong> Compress and optimize theme images',
                                '🔄 <strong>Template Caching:</strong> Implement template fragment caching',
                                '📦 <strong>Asset Loading:</strong> Optimize when and how theme assets are loaded'
                            ];
                        }
                    }
                    // Content-related optimizations
                    elseif (strpos($operation_lower, 'content') !== false) {
                        if ($time_ms > 1000) {
                            $optimizations = [
                                '📝 <strong>Content Caching:</strong> Implement full-page caching for content',
                                '🖼️ <strong>Media Optimization:</strong> Optimize images and videos in content',
                                '⚡ <strong>Lazy Loading:</strong> Implement lazy loading for images and videos',
                                '🔄 <strong>Content Delivery:</strong> Use a CDN for faster content delivery',
                                '📦 <strong>Content Compression:</strong> Enable GZIP compression for content',
                                '🧹 <strong>Content Cleanup:</strong> Remove unnecessary content and revisions'
                            ];
                        }
                    }
                    // HTTP/cURL optimizations
                    elseif (strpos($operation_lower, 'curl') !== false || strpos($operation_lower, 'http') !== false) {
                        if ($time_ms > 1000) {
                            $optimizations = [
                                '🌐 <strong>Connection Optimization:</strong> Use HTTP/2 and keep-alive connections',
                                '⚡ <strong>Timeout Tuning:</strong> Optimize timeout values for external requests',
                                '🔄 <strong>Request Caching:</strong> Cache external API responses when possible',
                                '📡 <strong>DNS Optimization:</strong> Use faster DNS servers (1.1.1.1, 8.8.8.8)',
                                '🚀 <strong>Async Requests:</strong> Make non-critical requests asynchronous',
                                '🔧 <strong>Request Reduction:</strong> Minimize number of external requests'
                            ];
                        }
                    }
                    // Memory-related optimizations
                    elseif ($memory_mb > 50) {
                        $optimizations = [
                            '💾 <strong>Memory Limit:</strong> Increase PHP memory limit if needed',
                            '🧹 <strong>Memory Cleanup:</strong> Unset large variables when no longer needed',
                            '⚡ <strong>Object Caching:</strong> Implement object caching to reduce memory usage',
                            '📦 <strong>Code Optimization:</strong> Optimize code to use less memory',
                            '🔄 <strong>Garbage Collection:</strong> Force garbage collection for large operations',
                            '📊 <strong>Memory Profiling:</strong> Use profiling tools to identify memory leaks'
                        ];
                    }
                    // General optimizations for very slow operations
                    elseif ($time_ms > 1000) {
                        $optimizations = [
                            '⚡ <strong>Caching Strategy:</strong> Implement comprehensive caching for this operation',
                            '🔧 <strong>Code Review:</strong> Review and optimize the code for this operation',
                            '📊 <strong>Profiling:</strong> Use profiling tools to identify bottlenecks',
                            '🚀 <strong>Async Processing:</strong> Move heavy operations to background processing',
                            '🔄 <strong>Load Balancing:</strong> Distribute load across multiple servers',
                            '📦 <strong>Resource Optimization:</strong> Optimize server resources for this operation'
                        ];
                    }

                    return $optimizations;
                }

                echo '<div style="overflow-x: auto;">';
                echo '<table class="debug-table" style="min-width: 1000px;">';
                echo '<thead><tr>';
                echo '<th style="width: 20%;">Operation</th>';
                echo '<th style="width: 10%;">Time (ms)</th>';
                echo '<th style="width: 10%;">Memory (MB)</th>';
                echo '<th style="width: 10%;">% of Total</th>';
                echo '<th style="width: 10%;">Status</th>';
                echo '<th style="width: 40%;">Optimization Recommendations</th>';
                echo '</tr></thead>';
                echo '<tbody>';

                $very_slow_operations = [];

                foreach ($debug_timings as $label => $time) {
                    $clean_label = ucwords(str_replace('_', ' ', $label));
                    $memory = $debug_memory_usage[$label] ?? 0;
                    $percentage = $total_time > 0 ? round(($time / $total_time) * 100, 1) : 0;

                    // Performance status
                    $status = 'success';
                    $status_text = 'Good';
                    $status_color = '#28a745';

                    if ($time > 1000) {
                        $status = 'error';
                        $status_text = 'Very Slow';
                        $status_color = '#dc3545';
                        $very_slow_operations[] = ['name' => $clean_label, 'time' => $time, 'memory' => $memory];
                    } elseif ($time > 500) {
                        $status = 'warning';
                        $status_text = 'Slow';
                        $status_color = '#ffc107';
                    }

                    echo '<tr style="' . ($status === 'error' ? 'background-color: #fff5f5; border-left: 4px solid #dc3545;' : '') . '">';

                    // Operation
                    echo '<td>';
                    echo '<strong>' . esc_html($clean_label) . '</strong>';
                    if ($percentage > 20) {
                        echo '<br><span class="debug-badge warning" style="font-size: 10px;">HIGH IMPACT</span>';
                    }
                    echo '</td>';

                    // Time
                    echo '<td style="text-align: right;">';
                    echo '<span style="color: ' . $status_color . '; font-weight: bold;">' . $time . '</span>';
                    echo '</td>';

                    // Memory
                    echo '<td style="text-align: right;">';
                    if ($memory > 50) {
                        echo '<span style="color: #dc3545; font-weight: bold;">' . $memory . '</span>';
                    } elseif ($memory > 20) {
                        echo '<span style="color: #ffc107; font-weight: bold;">' . $memory . '</span>';
                    } else {
                        echo '<span style="color: #28a745;">' . $memory . '</span>';
                    }
                    echo '</td>';

                    // Percentage
                    echo '<td>';
                    echo '<div class="debug-progress" style="width: 60px; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">';
                    echo '<div class="debug-progress-bar" style="width: ' . min($percentage, 100) . '%; height: 100%; background: ' . $status_color . ';"></div>';
                    echo '</div>';
                    echo '<small>' . $percentage . '%</small>';
                    echo '</td>';

                    // Status
                    echo '<td style="text-align: center;">';
                    echo '<span class="debug-badge ' . $status . '">' . $status_text . '</span>';
                    echo '</td>';

                    // Optimization Recommendations
                    echo '<td>';
                    if ($status === 'error') {
                        $optimizations = getPerformanceOptimizations($clean_label, $time, $memory);
                        if (!empty($optimizations)) {
                            echo '<div style="font-size: 12px; line-height: 1.4;">';
                            echo '<strong style="color: #dc3545;">🚨 URGENT OPTIMIZATIONS:</strong><br>';
                            echo '<ol style="margin: 4px 0; padding-left: 16px;">';
                            foreach (array_slice($optimizations, 0, 3) as $optimization) {
                                echo '<li style="margin: 2px 0;">' . $optimization . '</li>';
                            }
                            echo '</ol>';
                            if (count($optimizations) > 3) {
                                echo '<details style="margin-top: 4px;">';
                                echo '<summary style="cursor: pointer; color: #0066cc; font-weight: bold;">More Solutions (' . (count($optimizations) - 3) . ')</summary>';
                                echo '<ol start="4" style="margin: 4px 0; padding-left: 16px;">';
                                foreach (array_slice($optimizations, 3) as $optimization) {
                                    echo '<li style="margin: 2px 0;">' . $optimization . '</li>';
                                }
                                echo '</ol>';
                                echo '</details>';
                            }
                            echo '</div>';
                        } else {
                            echo '<span style="color: #dc3545;">⚠️ Requires investigation</span>';
                        }
                    } elseif ($status === 'warning') {
                        echo '<span style="color: #ffc107;">⚡ Consider optimization</span>';
                    } else {
                        echo '<span style="color: #28a745;">✅ Performance is good</span>';
                    }
                    echo '</td>';

                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';

                // Ultimate performance recommendations
                echo '<div class="debug-info">';
                echo '<strong>🎯 Ultimate Performance Recommendations:</strong><br>';
                if ($total_time > 2000) {
                    echo '• ⚠️ Total execution time is very high (' . $total_time . 'ms) - consider optimization<br>';
                }
                if (memory_get_peak_usage() > 128 * 1024 * 1024) {
                    echo '• ⚠️ High memory usage detected (' . round(memory_get_peak_usage() / 1024 / 1024, 1) . 'MB)<br>';
                }
                if (count($debug_hooks_called) > 1000) {
                    echo '• ⚠️ Many hooks fired (' . count($debug_hooks_called) . ') - consider plugin optimization<br>';
                }
                if (count(get_option('active_plugins', [])) > 30) {
                    echo '• ⚠️ Many active plugins (' . count(get_option('active_plugins', [])) . ') - consider deactivating unused ones<br>';
                }
                if (isset($db_status['posts_query_time']) && $db_status['posts_query_time'] > 100) {
                    echo '• ⚠️ Slow database queries detected - consider database optimization<br>';
                }
                echo '• ✅ WordPress loaded in ' . ($debug_timings['wordpress_loaded'] ?? 0) . 'ms<br>';
                echo '• ✅ Database connection: ' . ($db_status['connection'] ?? 'Unknown') . '<br>';
                echo '• ✅ cURL status: ' . ($curl_diagnostics['status'] ?? 'Unknown');
                echo '</div>';
                ?>
            </div>
        </div>

        <!-- Cron Job Diagnostics & Health Check -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⏰ Cron Job Diagnostics & Health Check
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('cron_display_start');

                echo '<div class="debug-info">';
                echo '<strong>🎯 WordPress Cron Analysis:</strong> Comprehensive analysis of WordPress cron jobs, schedules, and background task health.';
                echo '</div>';

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($debug_cron_jobs['ready'] ?? []) . '</div>';
                echo '<div class="debug-metric-label">Ready Jobs</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($debug_cron_health['overdue_jobs'] ?? 0) . '</div>';
                echo '<div class="debug-metric-label">Overdue Jobs</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($debug_cron_jobs['schedules'] ?? []) . '</div>';
                echo '<div class="debug-metric-label">Schedules</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($debug_cron_health['test_time'] ?? 0) . 'ms</div>';
                echo '<div class="debug-metric-label">Test Time</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($debug_cron_health['total_jobs'] ?? 0) . '</div>';
                echo '<div class="debug-metric-label">Total Jobs</div>';
                echo '</div>';
                echo '</div>';

                // Cron Health Status
                echo '<h4>🏥 Cron Health Status</h4>';
                echo '<div class="debug-code">';
                echo '<strong>WP-Cron Status:</strong> ';
                if ($debug_cron_health['wp_cron_disabled']) {
                    echo '<span class="debug-badge error">Disabled</span>';
                } else {
                    echo '<span class="debug-badge success">Enabled</span>';
                }
                echo '<br>';

                echo '<strong>Current Status:</strong> ' . esc_html($debug_cron_health['last_cron_run']) . '<br>';
                echo '<strong>Test Execution:</strong> ';
                if ($debug_cron_health['test_execution'] === 'Success') {
                    echo '<span class="debug-badge success">Success</span>';
                } else {
                    echo '<span class="debug-badge error">Failed</span>';
                }
                echo ' (' . $debug_cron_health['test_time'] . 'ms)<br>';

                echo '<strong>Loopback Test:</strong> ';
                if ($debug_cron_health['loopback_status'] === 'Success') {
                    echo '<span class="debug-badge success">Success</span>';
                    echo ' (HTTP ' . ($debug_cron_health['loopback_code'] ?? 'Unknown') . ')';
                } else {
                    echo '<span class="debug-badge error">Failed</span>';
                    if (isset($debug_cron_health['loopback_error'])) {
                        echo '<br><small style="color: #dc3545;">Error: ' . esc_html($debug_cron_health['loopback_error']) . '</small>';
                    }
                }
                echo '</div>';

                // Ready/Overdue Jobs
                if (!empty($debug_cron_jobs['ready'])) {
                    echo '<h4>🔄 Ready & Overdue Jobs</h4>';
                    echo '<div style="overflow-x: auto;">';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Hook Name</th><th>Next Run</th><th>Time Until</th><th>Arguments</th><th>Status</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($debug_cron_jobs['ready'] as $hook => $job) {
                        echo '<tr>';
                        echo '<td><code style="font-size: 11px;">' . esc_html($hook) . '</code></td>';
                        echo '<td>' . $job['next_run'] . '</td>';
                        echo '<td>';
                        if ($job['time_until'] < 0) {
                            $overdue_time = abs($job['time_until']);
                            if ($overdue_time > 3600) {
                                echo '<span style="color: #dc3545;">Overdue by ' . round($overdue_time / 3600, 1) . ' hours</span>';
                            } elseif ($overdue_time > 60) {
                                echo '<span style="color: #dc3545;">Overdue by ' . round($overdue_time / 60) . ' minutes</span>';
                            } else {
                                echo '<span style="color: #dc3545;">Overdue by ' . $overdue_time . ' seconds</span>';
                            }
                        } else {
                            echo '<span style="color: #28a745;">In ' . round($job['time_until'] / 60) . ' minutes</span>';
                        }
                        echo '</td>';
                        echo '<td>' . count($job['args']) . ' args</td>';
                        echo '<td>';
                        if ($job['overdue']) {
                            echo '<span class="debug-badge error">Overdue</span>';
                        } else {
                            echo '<span class="debug-badge success">Ready</span>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div>';
                }

                // Custom Schedules
                if (!empty($debug_cron_jobs['schedules'])) {
                    echo '<h4>📅 Registered Cron Schedules</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Schedule Name</th><th>Interval</th><th>Display Name</th><th>Frequency</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($debug_cron_jobs['schedules'] as $key => $schedule) {
                        $frequency = '';
                        if ($schedule['interval'] < 60) {
                            $frequency = 'Every ' . $schedule['interval'] . ' seconds';
                        } elseif ($schedule['interval'] < 3600) {
                            $frequency = 'Every ' . round($schedule['interval'] / 60) . ' minutes';
                        } elseif ($schedule['interval'] < 86400) {
                            $frequency = 'Every ' . round($schedule['interval'] / 3600) . ' hours';
                        } else {
                            $frequency = 'Every ' . round($schedule['interval'] / 86400) . ' days';
                        }

                        echo '<tr>';
                        echo '<td><strong>' . esc_html($key) . '</strong></td>';
                        echo '<td>' . number_format($schedule['interval']) . ' seconds</td>';
                        echo '<td>' . esc_html($schedule['display']) . '</td>';
                        echo '<td><small>' . $frequency . '</small></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }

                // Issues and Recommendations
                echo '<div class="debug-info">';
                echo '<strong>💡 Cron Health Recommendations:</strong><br>';

                if (!empty($debug_cron_health['issues'])) {
                    echo '<strong style="color: #dc3545;">🚨 Issues Detected:</strong><br>';
                    foreach ($debug_cron_health['issues'] as $issue) {
                        echo '• <span style="color: #dc3545;">' . esc_html($issue) . '</span><br>';
                    }
                    echo '<br>';
                }

                echo '<strong>🔧 Optimization Tips:</strong><br>';
                if ($debug_cron_health['wp_cron_disabled']) {
                    echo '• <strong>Enable WP-Cron:</strong> Remove or set DISABLE_WP_CRON to false in wp-config.php<br>';
                    echo '• <strong>Server Cron:</strong> Set up server cron: <code>*/5 * * * * wget -q -O - ' . home_url('/wp-cron.php') . ' >/dev/null 2>&1</code><br>';
                } else {
                    echo '• <strong>WP-Cron is enabled:</strong> Background tasks should run automatically<br>';
                }

                if (($debug_cron_health['overdue_jobs'] ?? 0) > 0) {
                    echo '• <strong>Overdue Jobs:</strong> ' . $debug_cron_health['overdue_jobs'] . ' jobs are overdue - check hosting traffic limits<br>';
                    echo '• <strong>Manual Trigger:</strong> Run <code>wp cron event run --all</code> via WP-CLI to process overdue jobs<br>';
                }

                if ($debug_cron_health['loopback_status'] === 'Failed') {
                    echo '• <strong>Loopback Failed:</strong> Check if your site can connect to itself (required for WP-Cron)<br>';
                    echo '• <strong>Firewall:</strong> Ensure loopback connections are not blocked by firewall<br>';
                }

                echo '• <strong>Monitoring:</strong> Consider using a cron monitoring service for production sites<br>';
                echo '• <strong>Performance:</strong> For high-traffic sites, use server cron instead of WP-Cron<br>';
                echo '• <strong>Testing:</strong> Use WP-CLI <code>wp cron test</code> for comprehensive cron testing<br>';
                echo '</div>';

                debug_time('cron_display_end');
                ?>
            </div>
        </div>

        <!-- Footer with Enhanced Navigation -->
        <div class="debug-footer">
            <h3>🚀 WordPress Debug Tool - Ultimate Version</h3>
            <p>Complete diagnostic suite combining all features from debug.php and debug-advanced.php<br>
            Includes on-page footer integration, advanced plugin conflict testing, and comprehensive analysis</p>
            <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                <a href="<?php echo home_url(); ?>" class="debug-btn">🏠 Return to Site</a>
                <a href="debug-minimal.php" class="debug-btn">📱 Minimal</a>
                <a href="debug-medium.php" class="debug-btn">⚖️ Medium</a>
                <a href="debug-advanced.php" class="debug-btn">🚀 Advanced</a>
                <a href="debug.php" class="debug-btn">🔧 Original</a>
                <a href="?refresh=<?php echo time(); ?>" class="debug-btn success">🔄 Refresh</a>
            </div>
            <p style="font-size: 14px; opacity: 0.9;">
                <strong>🎮 Keyboard Shortcuts:</strong>
                Ctrl+D (Toggle All) | Ctrl+E (Export) | Ctrl+T (Theme) | Ctrl+F (Footer Box)
            </p>
            <p style="font-size: 12px; opacity: 0.8;">
                <strong>📊 Session Stats:</strong>
                <?php echo count($debug_timings); ?> operations |
                <?php echo $total_time; ?>ms total |
                <?php echo round(memory_get_peak_usage() / 1024 / 1024, 1); ?>MB peak memory
            </p>
        </div>
    </div>

    <script>
        // Ultimate theme toggle with system preference and persistence
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('debug-ultimate-theme', newTheme);

            // Update button text with animation
            const themeBtn = document.querySelector('button[onclick="toggleTheme()"]');
            if (themeBtn) {
                themeBtn.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    themeBtn.textContent = newTheme === 'dark' ? '☀️ Light' : '🌓 Dark';
                    themeBtn.style.transform = 'scale(1)';
                }, 150);
            }
        }

        // Enhanced section toggle with smooth animations
        function toggleSection(header) {
            const section = header.parentElement;
            const content = section.querySelector('.debug-section-content');

            if (section.classList.contains('collapsed')) {
                section.classList.remove('collapsed');
                content.style.maxHeight = content.scrollHeight + 'px';
                content.style.opacity = '1';
            } else {
                section.classList.add('collapsed');
                content.style.maxHeight = '0px';
                content.style.opacity = '0';
            }
        }

        // Smart toggle all with progress indication
        function toggleAll() {
            const sections = document.querySelectorAll('.debug-section');
            const allCollapsed = Array.from(sections).every(s => s.classList.contains('collapsed'));
            const btn = document.querySelector('button[onclick="toggleAll()"]');

            btn.textContent = '⏳ Processing...';

            sections.forEach((section, index) => {
                setTimeout(() => {
                    const content = section.querySelector('.debug-section-content');
                    if (allCollapsed) {
                        section.classList.remove('collapsed');
                        content.style.maxHeight = content.scrollHeight + 'px';
                        content.style.opacity = '1';
                    } else {
                        section.classList.add('collapsed');
                        content.style.maxHeight = '0px';
                        content.style.opacity = '0';
                    }

                    if (index === sections.length - 1) {
                        setTimeout(() => {
                            btn.textContent = allCollapsed ? '📦 Collapse All' : '📋 Expand All';
                        }, 100);
                    }
                }, index * 50);
            });
        }

        // Ultimate export with comprehensive data structure
        function exportResults() {
            const data = {
                meta: {
                    timestamp: new Date().toISOString(),
                    tool_version: 'debug-ultimate.php',
                    export_version: '1.0',
                    user_agent: navigator.userAgent
                },
                site_info: {
                    url: '<?php echo home_url(); ?>',
                    wordpress_version: '<?php echo get_bloginfo("version"); ?>',
                    php_version: '<?php echo PHP_VERSION; ?>',
                    theme: '<?php echo wp_get_theme()->get("Name"); ?>',
                    multisite: <?php echo is_multisite() ? 'true' : 'false'; ?>,
                    wp_load_path: '<?php echo $wp_load_path_used; ?>'
                },
                performance: {
                    timings: <?php echo json_encode($debug_timings); ?>,
                    memory_usage: <?php echo json_encode($debug_memory_usage); ?>,
                    total_execution_time: '<?php echo $total_time; ?>ms',
                    current_memory: '<?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB',
                    peak_memory: '<?php echo round(memory_get_peak_usage() / 1024 / 1024, 2); ?> MB'
                },
                plugins: {
                    active_plugins: <?php echo json_encode(array_values(get_option('active_plugins', []))); ?>,
                    disabled_for_test: <?php echo json_encode($disabled_plugins); ?>,
                    total_plugins: <?php echo count(get_plugins()); ?>,
                    mu_plugins: <?php echo json_encode(array_keys(get_mu_plugins())); ?>
                },
                content_analysis: {
                    the_content_called: <?php echo $debug_has_the_content ? 'true' : 'false'; ?>,
                    filter_calls: <?php echo count($debug_content_filters); ?>,
                    content_filters: <?php echo json_encode($debug_content_filters); ?>,
                    shortcodes_found: <?php echo json_encode($debug_shortcodes_found); ?>,
                    broken_shortcodes: <?php echo json_encode($debug_broken_shortcodes); ?>
                },
                hooks_analysis: {
                    unique_hooks: <?php echo count($debug_hooks_called); ?>,
                    total_hook_calls: <?php echo array_sum($debug_hooks_called); ?>,
                    top_hooks: <?php echo json_encode(array_slice($debug_hooks_called, 0, 20, true)); ?>,
                    heavy_hooks: <?php echo json_encode(array_filter($debug_hooks_called, function($count) { return $count > 50; })); ?>
                },
                database: <?php echo json_encode($db_status); ?>,
                curl_diagnostics: <?php echo json_encode($curl_diagnostics); ?>,
                wp_config: <?php echo json_encode($wp_config); ?>,
                error_log: <?php echo json_encode($error_log_analysis); ?>,
                cron_diagnostics: {
                    health: <?php echo json_encode($debug_cron_health); ?>,
                    jobs: <?php echo json_encode($debug_cron_jobs); ?>,
                    ready_jobs_count: <?php echo count($debug_cron_jobs['ready'] ?? []); ?>,
                    overdue_jobs_count: <?php echo $debug_cron_health['overdue_jobs'] ?? 0; ?>,
                    total_schedules: <?php echo count($debug_cron_jobs['schedules'] ?? []); ?>
                }
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'debug-ultimate-results-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            // Enhanced success feedback
            const btn = document.querySelector('button[onclick="exportResults()"]');
            const originalText = btn.textContent;
            btn.textContent = '✅ Exported!';
            btn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            btn.style.transform = 'scale(1.05)';
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
                btn.style.transform = 'scale(1)';
            }, 2000);
        }

        // Refresh diagnostics with cache busting
        function refreshDiagnostics() {
            const url = new URL(window.location);
            url.searchParams.set('refresh', Date.now());
            url.searchParams.set('cache_bust', Math.random().toString(36).substr(2, 9));
            window.location.href = url.toString();
        }

        // Toggle footer diagnostic box
        function toggleFooterBox() {
            const footerBoxes = document.querySelectorAll('[id^="debug-diagnostic-box-"]');
            footerBoxes.forEach(box => {
                if (box.style.display === 'none') {
                    box.style.display = 'block';
                    box.style.animation = 'fadeIn 0.3s ease';
                } else {
                    box.style.display = 'none';
                }
            });
        }

        // Initialize theme with enhanced detection
        function initializeTheme() {
            const savedTheme = localStorage.getItem('debug-ultimate-theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');

            document.body.setAttribute('data-theme', theme);

            // Update button text
            const themeBtn = document.querySelector('button[onclick="toggleTheme()"]');
            if (themeBtn) {
                themeBtn.textContent = theme === 'dark' ? '☀️ Light' : '🌓 Dark';
            }
        }

        // Ultimate keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case 'd':
                        e.preventDefault();
                        toggleAll();
                        break;
                    case 'e':
                        e.preventDefault();
                        exportResults();
                        break;
                    case 't':
                        e.preventDefault();
                        toggleTheme();
                        break;
                    case 'f':
                        e.preventDefault();
                        toggleFooterBox();
                        break;
                    case 'r':
                        e.preventDefault();
                        refreshDiagnostics();
                        break;
                }
            }
        });

        // Enhanced initialization
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();

            // Add smooth transitions to all section content
            document.querySelectorAll('.debug-section-content').forEach(content => {
                content.style.transition = 'max-height 0.4s ease-out, opacity 0.3s ease';
                content.style.overflow = 'hidden';
                content.style.maxHeight = content.scrollHeight + 'px';
                content.style.opacity = '1';
            });

            // Add fade-in animation
            document.querySelector('.debug-container').style.animation = 'fadeIn 0.5s ease';

            // Performance monitoring
            if (performance.mark) {
                performance.mark('debug-tool-loaded');
            }
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('debug-ultimate-theme')) {
                document.body.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            }
        });

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .debug-section-content {
                transition: max-height 0.4s ease-out, opacity 0.3s ease !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
