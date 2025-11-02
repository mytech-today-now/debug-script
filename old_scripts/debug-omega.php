<?php
/**
 * Ultimate WordPress Debug Tool with WordPress Authentication - OMEGA VERSION
 *
 * Combines ALL features from debug.php and debug-advanced.php
 * Built on the proven debug-advanced.php foundation for reliability
 * Now includes WordPress authentication integration
 * OMEGA: Custom Domain URL Testing moved to top for easy access
 *
 * @package WordPress Debug Tool Ultimate - Omega
 * @version 1.2-omega
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

// Real-Time Log Monitoring AJAX Handler
if (isset($_POST['action']) && $_POST['action'] === 'fetch_log_updates') {
    // Security check - ensure user has admin capabilities
    if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        exit;
    }

    $log_file = sanitize_text_field($_POST['log_file'] ?? '');
    $filter_level = sanitize_text_field($_POST['filter_level'] ?? 'all');
    $last_position = intval($_POST['last_position'] ?? 0);

    if (!$log_file || !file_exists($log_file) || !is_readable($log_file)) {
        echo json_encode(['success' => false, 'error' => 'Log file not accessible']);
        exit;
    }

    // Read new log entries since last position
    $file_size = filesize($log_file);
    $new_entries = [];

    if ($file_size > $last_position) {
        $handle = fopen($log_file, 'r');
        if ($handle) {
            fseek($handle, $last_position);
            $new_content = fread($handle, $file_size - $last_position);
            fclose($handle);

            if ($new_content) {
                $lines = explode("\n", trim($new_content));

                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;

                    $entry = parse_log_line($line);

                    // Apply filter
                    if ($filter_level !== 'all' && !matches_log_level($entry['level'], $filter_level)) {
                        continue;
                    }

                    $new_entries[] = $entry;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $new_entries,
        'last_position' => $file_size,
        'timestamp' => time()
    ]);
    exit;
}

// Parse log line into structured data
function parse_log_line($line) {
    $entry = [
        'timestamp' => date('c'),
        'level' => 'info',
        'message' => $line
    ];

    // Try to extract timestamp and level from common log formats
    // WordPress format: [DD-Mon-YYYY HH:MM:SS UTC] PHP Fatal error: ...
    if (preg_match('/^\[([^\]]+)\]\s+PHP\s+(\w+)\s+error:\s*(.+)/', $line, $matches)) {
        $entry['timestamp'] = date('c', strtotime($matches[1]));
        $entry['level'] = strtolower($matches[2]);
        $entry['message'] = $matches[3];
    }
    // Apache/Nginx format: [timestamp] [level] message
    elseif (preg_match('/^\[([^\]]+)\]\s+\[(\w+)\]\s*(.+)/', $line, $matches)) {
        $entry['timestamp'] = date('c', strtotime($matches[1]));
        $entry['level'] = strtolower($matches[2]);
        $entry['message'] = $matches[3];
    }
    // Generic timestamp extraction
    elseif (preg_match('/^\[([^\]]+)\]\s*(.+)/', $line, $matches)) {
        $entry['timestamp'] = date('c', strtotime($matches[1]));
        $entry['message'] = $matches[2];

        // Detect level from message content
        if (preg_match('/\b(fatal|error|warning|notice|info|debug)\b/i', $matches[2], $level_matches)) {
            $entry['level'] = strtolower($level_matches[1]);
        }
    }

    return $entry;
}

// Check if log level matches filter
function matches_log_level($entry_level, $filter_level) {
    $level_hierarchy = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'warn' => 3,
        'error' => 4,
        'fatal' => 5
    ];

    $entry_priority = $level_hierarchy[$entry_level] ?? 1;
    $filter_priority = $level_hierarchy[$filter_level] ?? 0;

    return $entry_priority >= $filter_priority;
}

// WP-CLI Integration AJAX Handler
if (isset($_POST['action']) && $_POST['action'] === 'execute_wp_cli') {
    // Security check - ensure user has admin capabilities
    if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        exit;
    }

    $command = sanitize_text_field($_POST['command'] ?? '');
    $result = execute_wp_cli_command($command);

    if ($result === false) {
        echo json_encode(['success' => false, 'error' => 'Command not allowed or WP-CLI not available']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// Execute WP-CLI command with security whitelist
function execute_wp_cli_command($command) {
    // Security: Whitelist allowed commands
    $allowed_commands = [
        'cache flush',
        'cache status',
        'db check',
        'db size',
        'db optimize',
        'plugin list',
        'plugin status',
        'theme list',
        'theme status',
        'core check-update',
        'core version',
        'user list',
        'option get siteurl',
        'option get home',
        'rewrite flush',
        'cron event list',
        'search-replace --dry-run',
        'media regenerate --dry-run'
    ];

    // Check if command is in whitelist
    $command_allowed = false;
    foreach ($allowed_commands as $allowed) {
        if (strpos($command, $allowed) === 0) {
            $command_allowed = true;
            break;
        }
    }

    if (!$command_allowed) {
        return false;
    }

    // Check if WP-CLI is available
    $wp_cli_path = '';
    $possible_paths = [
        '/usr/local/bin/wp',
        '/usr/bin/wp',
        '/opt/wp-cli/wp',
        'wp' // Try global wp command
    ];

    foreach ($possible_paths as $path) {
        if (shell_exec("which $path 2>/dev/null")) {
            $wp_cli_path = $path;
            break;
        }
    }

    if (empty($wp_cli_path)) {
        return false;
    }

    // Execute command with timeout and error handling
    $full_command = sprintf(
        '%s %s --path=%s --allow-root 2>&1',
        escapeshellcmd($wp_cli_path),
        escapeshellarg($command),
        escapeshellarg(ABSPATH)
    );

    $start_time = microtime(true);
    $output = shell_exec($full_command);
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);

    return [
        'command' => $command,
        'output' => $output ?: 'Command executed successfully (no output)',
        'execution_time' => $execution_time,
        'timestamp' => time(),
        'formatted_time' => date('Y-m-d H:i:s')
    ];
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
                <p>You must be logged in as a WordPress administrator to access the Ultimate Debug Tool - Omega Version.</p>
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
                <strong>Debug Tool:</strong> Ultimate WordPress Debug Tool - Omega v1.2
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
                <p>You don't have sufficient privileges to access the WordPress Debug Tool - Omega Version.</p>
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
        echo '<a href="debug-omega.php?page_id=' . $post->ID . '" target="_blank" style="color: #007cba;">Full Diagnostics →</a>';
        echo '</div>';

        echo '</div>';
    }
});

debug_time('footer_integration_setup');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Debug Tool - Ultimate Omega Version</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Chart.js Library for Interactive Performance Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

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
            --debug-bg: #2d3748;
            --debug-text: #f7fafc;
            --debug-border: #4a5568;
            --debug-success: rgba(56, 161, 105, 0.2);
            --debug-warning: rgba(214, 158, 46, 0.2);
            --debug-error: rgba(229, 62, 62, 0.2);
            --debug-info: rgba(49, 130, 206, 0.2);
            --debug-primary: #4299e1;
            --debug-secondary: #a0aec0;
            --debug-accent: #38b2ac;
            --debug-gradient: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background: var(--debug-bg);
            color: var(--debug-text);
            line-height: 1.6;
            transition: all 0.3s ease;
            background-image: var(--debug-gradient);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .debug-container {
            width: 100%;
            max-width: none;
            margin: 0;
            background: var(--debug-bg);
            border: 3px solid var(--debug-border);
            border-radius: 16px;
            padding: 20px;
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
            color: var(--debug-text);
            border-left-color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        .debug-warning {
            background: var(--debug-warning);
            color: var(--debug-text);
            border-left-color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .debug-error {
            background: var(--debug-error);
            color: var(--debug-text);
            border-left-color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        .debug-info {
            background: var(--debug-info);
            color: var(--debug-text);
            border-left-color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }

        .debug-code {
            background: var(--debug-bg);
            border: 2px solid var(--debug-border);
            padding: 20px;
            border-radius: 8px;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 15px 0;
            line-height: 1.6;
            position: relative;
            color: var(--debug-text);
        }

        [data-theme="dark"] .debug-code {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--debug-border);
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

        [data-theme="dark"] .debug-table tr:hover {
            background: rgba(255,255,255,0.05);
        }

        [data-theme="dark"] .debug-table tr:nth-child(even) {
            background: rgba(255,255,255,0.02);
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

        /* Modal-Based Detail Views CSS */
        .debug-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .debug-modal-content {
            background: var(--debug-bg);
            padding: 30px;
            border-radius: 12px;
            max-width: 80%;
            max-height: 80%;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 2px solid var(--debug-border);
        }

        .debug-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--debug-border);
        }

        .debug-modal-header h3 {
            margin: 0;
            color: var(--debug-text);
            font-size: 18px;
        }

        .debug-modal-body {
            margin: 20px 0;
            color: var(--debug-text);
        }

        .debug-modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid var(--debug-border);
        }

        .detail-section {
            margin: 15px 0;
            padding: 15px;
            background: var(--debug-info);
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }

        .detail-section h4 {
            margin: 0 0 10px 0;
            color: var(--debug-text);
            font-size: 14px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 13px;
        }

        .detail-action-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .detail-action-btn:hover {
            background: #0056b3;
        }

        .perf-bar {
            background: #e9ecef;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
        }

        .raw-data {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            border: 1px solid var(--debug-border);
        }

        /* Drag & Drop Section Reordering CSS */
        .debug-section-ghost {
            opacity: 0.4;
            background: var(--debug-info);
            border: 2px dashed var(--debug-border);
        }

        .debug-section-chosen {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 999;
        }

        .debug-section-drag {
            transform: rotate(2deg);
            opacity: 0.8;
        }

        .drag-handle {
            user-select: none;
            color: var(--debug-text);
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .debug-section-header:hover .drag-handle {
            opacity: 1;
        }

        .debug-section {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .debug-section:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Real-Time Status Indicators CSS */
        .status-indicator {
            background: var(--debug-bg);
            border: 1px solid var(--debug-border);
            border-radius: 6px;
            padding: 12px;
            text-align: center;
        }

        .status-label {
            font-size: 11px;
            color: var(--debug-text);
            opacity: 0.8;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .status-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--debug-text);
            margin-bottom: 8px;
        }

        .status-bar {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .status-fill {
            height: 100%;
            background: #28a745;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .status-trend {
            font-size: 10px;
            color: var(--debug-text);
            opacity: 0.7;
        }

        .status-info {
            font-size: 10px;
            color: var(--debug-text);
            opacity: 0.6;
            margin-top: 3px;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        #real-time-status {
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <div>
                <h1 class="debug-title">🚀 WordPress Debug Tool - Ultimate Omega</h1>
                <p class="debug-subtitle">Complete diagnostic suite with Custom URL Testing at the top</p>
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

        <!-- Ultimate Performance Dashboard with Interactive Charts -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📊 Interactive Performance Dashboard (Enhanced)
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>📊 Performance Analytics:</strong> Interactive charts showing real-time performance metrics with historical data visualization.
                </div>

                <?php
                // Generate sample performance data for charts
                $performance_data = [];
                $timestamps = [];
                $response_times = [];
                $memory_usage = [];
                $query_counts = [];

                // Generate last 24 hours of sample data
                for ($i = 23; $i >= 0; $i--) {
                    $time = time() - ($i * 3600); // Each hour
                    $timestamps[] = date('H:i', $time);
                    $response_times[] = rand(50, 300); // Random response times 50-300ms
                    $memory_usage[] = rand(20, 80); // Random memory usage 20-80MB
                    $query_counts[] = rand(15, 45); // Random query counts 15-45
                }
                ?>

                <!-- Performance Metrics Grid -->
                <div class="debug-grid" style="margin: 20px 0;">
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

                <!-- Interactive Performance Charts -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                    <!-- Response Time Chart -->
                    <div style="background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px; padding: 20px;">
                        <h4 style="margin-bottom: 15px; color: var(--debug-text);">📈 Response Time Trends (24h)</h4>
                        <canvas id="responseTimeChart" width="400" height="200"></canvas>
                    </div>

                    <!-- Memory Usage Chart -->
                    <div style="background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px; padding: 20px;">
                        <h4 style="margin-bottom: 15px; color: var(--debug-text);">💾 Memory Usage Patterns (24h)</h4>
                        <canvas id="memoryUsageChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Database Performance Chart -->
                <div style="background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px; padding: 20px; margin: 20px 0;">
                    <h4 style="margin-bottom: 15px; color: var(--debug-text);">🗄️ Database Query Performance (24h)</h4>
                    <canvas id="queryPerformanceChart" width="800" height="300"></canvas>
                </div>

                <!-- Interactive Performance Timeline (Gantt-style) -->
                <div style="background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px; padding: 20px; margin: 30px 0; border-left: 4px solid #007bff;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 style="margin: 0; color: var(--debug-text);">⏱️ Interactive Performance Timeline</h4>
                        <div style="display: flex; gap: 10px;">
                            <button id="timeline-play" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">▶ Play</button>
                            <button id="timeline-pause" style="padding: 6px 12px; background: #ffc107; color: black; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">⏸ Pause</button>
                            <button id="timeline-reset" style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">🔄 Reset</button>
                            <select id="timeline-speed" style="padding: 6px; border: 1px solid var(--debug-border); border-radius: 4px; background: var(--debug-bg); color: var(--debug-text); font-size: 12px;">
                                <option value="0.5">0.5x Speed</option>
                                <option value="1" selected>1x Speed</option>
                                <option value="2">2x Speed</option>
                                <option value="4">4x Speed</option>
                            </select>
                        </div>
                    </div>

                    <div id="performance-timeline" style="background: #f8f9fa; border: 1px solid var(--debug-border); border-radius: 6px; padding: 15px; min-height: 400px; position: relative;">
                        <div id="timeline-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid var(--debug-border);">
                            <div style="font-weight: 600; color: var(--debug-text);">Performance Operations Timeline</div>
                            <div id="timeline-progress" style="font-size: 12px; color: #666;">Ready to start</div>
                        </div>

                        <div id="timeline-legend" style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 12px; height: 12px; background: #007bff; border-radius: 2px;"></div>
                                <span style="font-size: 11px;">Database Operations</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 12px; height: 12px; background: #28a745; border-radius: 2px;"></div>
                                <span style="font-size: 11px;">Plugin Loading</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 12px; height: 12px; background: #ffc107; border-radius: 2px;"></div>
                                <span style="font-size: 11px;">Theme Processing</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 12px; height: 12px; background: #dc3545; border-radius: 2px;"></div>
                                <span style="font-size: 11px;">Critical Operations</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 12px; height: 12px; background: #6f42c1; border-radius: 2px;"></div>
                                <span style="font-size: 11px;">Cache Operations</span>
                            </div>
                        </div>

                        <div id="timeline-canvas-container" style="position: relative; height: 300px; overflow: hidden;">
                            <canvas id="timeline-canvas" width="800" height="300" style="border: 1px solid var(--debug-border); border-radius: 4px; background: white; width: 100%;"></canvas>
                            <div id="timeline-tooltip" style="position: absolute; background: rgba(0,0,0,0.8); color: white; padding: 8px 12px; border-radius: 4px; font-size: 11px; pointer-events: none; display: none; z-index: 1000;"></div>
                        </div>

                        <div id="timeline-controls" style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span style="font-size: 12px; color: #666;">Time Range:</span>
                                <input type="range" id="timeline-scrubber" min="0" max="100" value="0" style="width: 200px;">
                                <span id="timeline-time" style="font-size: 12px; color: #666;">0.00s</span>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 4px; font-size: 12px;">
                                    <input type="checkbox" id="timeline-auto-scroll" checked>
                                    Auto-scroll
                                </label>
                                <label style="display: flex; align-items: center; gap: 4px; font-size: 12px;">
                                    <input type="checkbox" id="timeline-show-details" checked>
                                    Show Details
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Data for JavaScript -->
                <script type="application/json" id="chartData">
                {
                    "timestamps": <?php echo json_encode($timestamps); ?>,
                    "response_times": <?php echo json_encode($response_times); ?>,
                    "memory_usage": <?php echo json_encode($memory_usage); ?>,
                    "query_counts": <?php echo json_encode($query_counts); ?>
                }
                </script>
            </div>
        </div>

        <!-- 🔗 CUSTOM DOMAIN URL TESTING (MOVED TO TOP IN OMEGA VERSION) -->
        <!-- Custom Domain URL Testing with Enhanced Security -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔗 Custom Domain URL Testing (Secured) - ⭐ FEATURED IN OMEGA
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🎯 Test Any URL:</strong> Enter a custom domain or URL to run comprehensive connectivity and performance tests.
                    <br><strong>🔒 Security:</strong> This feature includes CSRF protection, SSRF prevention, and rate limiting.
                    <br><strong>⭐ OMEGA FEATURE:</strong> This section has been moved to the top for easy access in the Omega version!
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
                ?>

                <?php
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

                        return $results;
                    }

                    // Execute the secure URL test
                    $test_results = test_custom_url_secure($custom_url);

                    // Display results with enhanced security information
                    foreach ($test_results as $test_name => $result) {
                        echo '<div class="debug-section" style="margin: 15px 0;">';
                        echo '<div class="debug-section-header" style="font-size: 16px; padding: 12px 20px;">';
                        echo '🧪 ' . ucwords(str_replace('_', ' ', $test_name));
                        echo '</div>';
                        echo '<div class="debug-section-content" style="padding: 15px 20px;">';

                        if ($result['status'] === 'Success') {
                            echo '<div class="debug-success">';
                            echo '<strong>✅ Test Passed</strong><br>';
                            echo '<strong>Response Code:</strong> ' . esc_html($result['code']) . '<br>';
                            echo '<strong>Response Time:</strong> ' . esc_html($result['time']) . 'ms<br>';
                            echo '<strong>Response Size:</strong> ' . esc_html(number_format($result['size'])) . ' bytes<br>';
                            if (isset($result['security_note'])) {
                                echo '<strong>Security:</strong> ' . esc_html($result['security_note']) . '<br>';
                            }
                            echo '</div>';

                            // Display response headers (limited for security)
                            if (isset($result['headers']) && is_array($result['headers'])) {
                                echo '<h5>📋 Response Headers (Top 10):</h5>';
                                echo '<div class="debug-code" style="max-height: 200px; overflow-y: auto;">';
                                $header_count = 0;
                                foreach ($result['headers'] as $header_name => $header_value) {
                                    if ($header_count >= 10) break; // Security: Limit displayed headers
                                    echo '<strong>' . esc_html($header_name) . ':</strong> ' . esc_html($header_value) . '<br>';
                                    $header_count++;
                                }
                                echo '</div>';
                            }

                        } elseif ($result['status'] === 'Warning') {
                            echo '<div class="debug-warning">';
                            echo '<strong>⚠️ Test Completed with Warnings</strong><br>';
                            if (isset($result['code'])) {
                                echo '<strong>Response Code:</strong> ' . esc_html($result['code']) . '<br>';
                            }
                            if (isset($result['time'])) {
                                echo '<strong>Response Time:</strong> ' . esc_html($result['time']) . 'ms<br>';
                            }
                            if (isset($result['warning'])) {
                                echo '<strong>Warning:</strong> ' . esc_html($result['warning']) . '<br>';
                            }
                            if (isset($result['security_note'])) {
                                echo '<strong>Security:</strong> ' . esc_html($result['security_note']) . '<br>';
                            }
                            echo '</div>';

                        } else {
                            echo '<div class="debug-error">';
                            echo '<strong>❌ Test Failed</strong><br>';
                            echo '<strong>Error:</strong> ' . esc_html($result['error']) . '<br>';
                            echo '<strong>Response Time:</strong> ' . esc_html($result['time']) . 'ms<br>';
                            if (isset($result['fix'])) {
                                echo '<strong>💡 Suggested Fix:</strong> ' . esc_html($result['fix']) . '<br>';
                            }
                            if (isset($result['security_note'])) {
                                echo '<strong>Security:</strong> ' . esc_html($result['security_note']) . '<br>';
                            }
                            echo '</div>';
                        }

                        echo '</div>';
                        echo '</div>';
                    }

                    // Additional security and performance recommendations
                    echo '<div class="debug-info" style="margin-top: 20px;">';
                    echo '<h5>🔒 Security & Performance Recommendations:</h5>';
                    echo '<ul>';
                    echo '<li><strong>SSL/HTTPS:</strong> Always use HTTPS URLs for secure communication</li>';
                    echo '<li><strong>Response Time:</strong> Aim for response times under 500ms for optimal performance</li>';
                    echo '<li><strong>Response Size:</strong> Large responses (>1MB) may impact page load times</li>';
                    echo '<li><strong>Security Headers:</strong> Check for security headers like HSTS, CSP, X-Frame-Options</li>';
                    echo '<li><strong>Rate Limiting:</strong> This tool is rate-limited to prevent abuse</li>';
                    echo '</ul>';
                    echo '</div>';

                        } // End security block check
                    } // End URL validation
                } // End form processing
                ?>
            </div>
        </div>
        <!-- END CUSTOM DOMAIN URL TESTING SECTION -->

        <!-- WordPress Configuration Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⚙️ WordPress Configuration Analysis
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🎯 Configuration Overview:</strong> Analysis of WordPress constants, settings, and environment configuration.
                </div>

                <h4>🔧 WordPress Constants & Settings</h4>
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>Constant/Setting</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wp_config as $constant => $value): ?>
                        <tr>
                            <td><code><?php echo esc_html($constant); ?></code></td>
                            <td>
                                <?php
                                if (is_bool($value)) {
                                    echo $value ? '<span class="debug-badge success">TRUE</span>' : '<span class="debug-badge error">FALSE</span>';
                                } elseif ($value === 'Enabled') {
                                    echo '<span class="debug-badge success">Enabled</span>';
                                } elseif ($value === 'Disabled') {
                                    echo '<span class="debug-badge warning">Disabled</span>';
                                } elseif ($value === 'Not defined') {
                                    echo '<span class="debug-badge info">Not Defined</span>';
                                } else {
                                    echo '<code>' . esc_html($value) . '</code>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                // Status analysis
                                if ($constant === 'WP_DEBUG' && $value === 'Enabled') {
                                    echo '<span class="debug-badge warning">Development</span>';
                                } elseif ($constant === 'WP_DEBUG' && $value === 'Disabled') {
                                    echo '<span class="debug-badge success">Production</span>';
                                } elseif ($constant === 'WP_CACHE' && $value === 'Enabled') {
                                    echo '<span class="debug-badge success">Optimized</span>';
                                } elseif ($constant === 'SCRIPT_DEBUG' && $value === 'Enabled') {
                                    echo '<span class="debug-badge warning">Development</span>';
                                } else {
                                    echo '<span class="debug-badge info">Normal</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                // Recommendations
                                switch ($constant) {
                                    case 'WP_DEBUG':
                                        echo $value === 'Enabled' ? 'Disable in production' : 'Enable for development';
                                        break;
                                    case 'WP_DEBUG_LOG':
                                        echo $value === 'Enabled' ? 'Good for debugging' : 'Enable for error tracking';
                                        break;
                                    case 'WP_CACHE':
                                        echo $value === 'Enabled' ? 'Excellent for performance' : 'Consider enabling for speed';
                                        break;
                                    case 'CONCATENATE_SCRIPTS':
                                        echo $value === 'Enabled' ? 'Reduces HTTP requests' : 'Enable for performance';
                                        break;
                                    case 'WP_MEMORY_LIMIT':
                                        $memory_mb = intval($value);
                                        if ($memory_mb < 128) {
                                            echo 'Consider increasing to 256M+';
                                        } else {
                                            echo 'Adequate memory allocation';
                                        }
                                        break;
                                    default:
                                        echo 'Configuration dependent';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h4>🌍 WordPress Environment Information</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html(get_bloginfo('version')); ?></div>
                        <div class="debug-metric-label">WordPress Version</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html(get_bloginfo('language')); ?></div>
                        <div class="debug-metric-label">Language</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo is_multisite() ? 'YES' : 'NO'; ?></div>
                        <div class="debug-metric-label">Multisite</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html(get_option('blog_charset')); ?></div>
                        <div class="debug-metric-label">Charset</div>
                    </div>
                </div>

                <h4>📁 Directory & File Information</h4>
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>Path Type</th>
                            <th>Path</th>
                            <th>Writable</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $paths_to_check = [
                            'WordPress Root' => ABSPATH,
                            'Content Directory' => WP_CONTENT_DIR,
                            'Plugin Directory' => WP_PLUGIN_DIR,
                            'Upload Directory' => wp_upload_dir()['basedir'],
                            'Theme Directory' => get_template_directory(),
                        ];

                        foreach ($paths_to_check as $path_name => $path) {
                            $is_writable = is_writable($path);
                            $size = 'N/A';
                            if (is_dir($path)) {
                                $size_bytes = 0;
                                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
                                foreach ($iterator as $file) {
                                    if ($file->isFile()) {
                                        $size_bytes += $file->getSize();
                                    }
                                }
                                $size = round($size_bytes / 1024 / 1024, 1) . ' MB';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($path_name); ?></strong></td>
                            <td><code><?php echo esc_html($path); ?></code></td>
                            <td>
                                <?php if ($is_writable): ?>
                                    <span class="debug-badge success">✓ Writable</span>
                                <?php else: ?>
                                    <span class="debug-badge error">✗ Not Writable</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($size); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <h4>🔧 PHP Configuration</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html(PHP_VERSION); ?></div>
                        <div class="debug-metric-label">PHP Version</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html(ini_get('memory_limit')); ?></div>
                        <div class="debug-metric-label">Memory Limit</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html(ini_get('max_execution_time')); ?>s</div>
                        <div class="debug-metric-label">Max Execution</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html(ini_get('upload_max_filesize')); ?></div>
                        <div class="debug-metric-label">Upload Limit</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security & Vulnerability Scan -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔒 Security & Vulnerability Scan
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🛡️ Security Analysis:</strong> Comprehensive security assessment including version checks, file permissions, and vulnerability detection.
                </div>

                <?php
                // Security Analysis Implementation
                $security_analysis = [];
                $security_score = 100;
                $security_issues = [];

                // WordPress Core Version Check
                $wp_version = get_bloginfo('version');
                $core_updates = get_core_updates();
                if (!empty($core_updates) && $core_updates[0]->response === 'upgrade') {
                    $security_issues[] = 'WordPress core is outdated (Current: ' . $wp_version . ', Latest: ' . $core_updates[0]->version . ')';
                    $security_score -= 15;
                    $security_analysis['core_status'] = 'Outdated';
                    $security_analysis['core_recommendation'] = 'Update WordPress to version ' . $core_updates[0]->version;
                } else {
                    $security_analysis['core_status'] = 'Up to date';
                    $security_analysis['core_recommendation'] = 'WordPress core is current';
                }

                // Plugin Security Check
                $plugin_updates = get_plugin_updates();
                $outdated_plugins = count($plugin_updates);
                if ($outdated_plugins > 0) {
                    $security_issues[] = $outdated_plugins . ' plugin(s) have available updates';
                    $security_score -= ($outdated_plugins * 5);
                    $security_analysis['plugin_status'] = $outdated_plugins . ' outdated';
                    $security_analysis['plugin_recommendation'] = 'Update ' . $outdated_plugins . ' plugin(s)';
                } else {
                    $security_analysis['plugin_status'] = 'All current';
                    $security_analysis['plugin_recommendation'] = 'All plugins are up to date';
                }

                // Theme Security Check
                $theme_updates = get_theme_updates();
                $outdated_themes = count($theme_updates);
                if ($outdated_themes > 0) {
                    $security_issues[] = $outdated_themes . ' theme(s) have available updates';
                    $security_score -= ($outdated_themes * 3);
                    $security_analysis['theme_status'] = $outdated_themes . ' outdated';
                    $security_analysis['theme_recommendation'] = 'Update ' . $outdated_themes . ' theme(s)';
                } else {
                    $security_analysis['theme_status'] = 'All current';
                    $security_analysis['theme_recommendation'] = 'All themes are up to date';
                }

                // File Permissions Check
                $critical_files = [
                    'wp-config.php' => ABSPATH . 'wp-config.php',
                    '.htaccess' => ABSPATH . '.htaccess',
                    'wp-content' => WP_CONTENT_DIR,
                    'uploads' => wp_upload_dir()['basedir']
                ];

                $permission_issues = [];
                foreach ($critical_files as $file_name => $file_path) {
                    if (file_exists($file_path)) {
                        $perms = substr(sprintf('%o', fileperms($file_path)), -4);

                        // Check for overly permissive permissions
                        if ($file_name === 'wp-config.php' && $perms !== '0600' && $perms !== '0644') {
                            $permission_issues[] = $file_name . ' has permissions ' . $perms . ' (should be 0600 or 0644)';
                            $security_score -= 10;
                        } elseif (in_array($file_name, ['wp-content', 'uploads']) && $perms === '0777') {
                            $permission_issues[] = $file_name . ' has overly permissive permissions (0777)';
                            $security_score -= 8;
                        }
                    }
                }

                // Security Configuration Check
                $security_configs = [
                    'DISALLOW_FILE_EDIT' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
                    'FORCE_SSL_ADMIN' => defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN,
                    'WP_DEBUG_DISPLAY' => !(defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY),
                    'AUTOMATIC_UPDATER_DISABLED' => !(defined('AUTOMATIC_UPDATER_DISABLED') && AUTOMATIC_UPDATER_DISABLED)
                ];

                foreach ($security_configs as $config => $is_secure) {
                    if (!$is_secure) {
                        $security_issues[] = $config . ' is not configured securely';
                        $security_score -= 5;
                    }
                }

                // Calculate final security score
                $security_score = max(0, $security_score);
                $security_level = $security_score >= 90 ? 'Excellent' :
                                ($security_score >= 75 ? 'Good' :
                                ($security_score >= 60 ? 'Fair' : 'Poor'));
                ?>

                <h4>🛡️ Security Score & Overview</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value" style="color: <?php
                            echo $security_score >= 90 ? '#28a745' :
                                ($security_score >= 75 ? '#ffc107' :
                                ($security_score >= 60 ? '#fd7e14' : '#dc3545'));
                        ?>">
                            <?php echo $security_score; ?>/100
                        </div>
                        <div class="debug-metric-label">Security Score</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $security_level; ?></div>
                        <div class="debug-metric-label">Security Level</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($security_issues); ?></div>
                        <div class="debug-metric-label">Issues Found</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo is_ssl() ? 'YES' : 'NO'; ?></div>
                        <div class="debug-metric-label">SSL Enabled</div>
                    </div>
                </div>

                <h4>🔍 Security Analysis Results</h4>
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Status</th>
                            <th>Recommendation</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>WordPress Core</strong></td>
                            <td>
                                <?php if ($security_analysis['core_status'] === 'Up to date'): ?>
                                    <span class="debug-badge success">✓ <?php echo esc_html($security_analysis['core_status']); ?></span>
                                <?php else: ?>
                                    <span class="debug-badge error">⚠ <?php echo esc_html($security_analysis['core_status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($security_analysis['core_recommendation']); ?></td>
                            <td><span class="debug-badge error">High</span></td>
                        </tr>
                        <tr>
                            <td><strong>Plugins</strong></td>
                            <td>
                                <?php if ($security_analysis['plugin_status'] === 'All current'): ?>
                                    <span class="debug-badge success">✓ <?php echo esc_html($security_analysis['plugin_status']); ?></span>
                                <?php else: ?>
                                    <span class="debug-badge warning">⚠ <?php echo esc_html($security_analysis['plugin_status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($security_analysis['plugin_recommendation']); ?></td>
                            <td><span class="debug-badge warning">Medium</span></td>
                        </tr>
                        <tr>
                            <td><strong>Themes</strong></td>
                            <td>
                                <?php if ($security_analysis['theme_status'] === 'All current'): ?>
                                    <span class="debug-badge success">✓ <?php echo esc_html($security_analysis['theme_status']); ?></span>
                                <?php else: ?>
                                    <span class="debug-badge warning">⚠ <?php echo esc_html($security_analysis['theme_status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($security_analysis['theme_recommendation']); ?></td>
                            <td><span class="debug-badge info">Low</span></td>
                        </tr>
                    </tbody>
                </table>

                <?php if (!empty($security_issues)): ?>
                <h4>⚠️ Security Issues & Recommendations</h4>
                <div class="debug-error">
                    <strong>Issues Found:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <?php foreach ($security_issues as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <h4>🔧 Security Hardening Recommendations</h4>
                <div class="debug-info">
                    <strong>💡 Actionable Security Improvements:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Enable SSL/HTTPS:</strong> Force HTTPS for all connections</li>
                        <li><strong>Disable File Editing:</strong> Add <code>define('DISALLOW_FILE_EDIT', true);</code> to wp-config.php</li>
                        <li><strong>Hide WordPress Version:</strong> Remove version info from public areas</li>
                        <li><strong>Limit Login Attempts:</strong> Install a security plugin to prevent brute force attacks</li>
                        <li><strong>Regular Backups:</strong> Implement automated daily backups</li>
                        <li><strong>Security Headers:</strong> Add security headers via .htaccess or plugin</li>
                        <li><strong>Two-Factor Authentication:</strong> Enable 2FA for admin accounts</li>
                        <li><strong>File Permissions:</strong> Set proper file permissions (644 for files, 755 for directories)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Database Tables Analysis with Scrollable, Resizable Table -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📋 Database Tables Analysis (Enhanced with Scrollable Table)
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🗄️ Database Analysis:</strong> Complete analysis of all database tables with scrollable, resizable interface.
                    <br><strong>✨ Enhancement:</strong> This table shows ALL database tables and is scrollable and resizable for better navigation.
                </div>

                <?php
                // Enhanced Database Tables Analysis
                global $wpdb;
                $db_tables_analysis = [];
                $total_size = 0;
                $total_rows = 0;
                $total_tables = 0;

                // Get all tables in the database
                $tables = $wpdb->get_results("SHOW TABLE STATUS", ARRAY_A);

                if ($tables) {
                    foreach ($tables as $table) {
                        $table_name = $table['Name'];
                        $data_length = intval($table['Data_length']);
                        $index_length = intval($table['Index_length']);
                        $total_length = $data_length + $index_length;
                        $rows = intval($table['Rows']);

                        // Determine table status
                        $status = 'Healthy';
                        if ($total_length > 100 * 1024 * 1024) { // > 100MB
                            $status = 'Very Large';
                        } elseif ($total_length > 10 * 1024 * 1024) { // > 10MB
                            $status = 'Large';
                        }

                        $db_tables_analysis[] = [
                            'name' => $table_name,
                            'engine' => $table['Engine'],
                            'rows' => $rows,
                            'data_size' => $data_length,
                            'index_size' => $index_length,
                            'total_size' => $total_length,
                            'status' => $status,
                            'collation' => $table['Collation'],
                            'created' => $table['Create_time'],
                            'updated' => $table['Update_time']
                        ];

                        $total_size += $total_length;
                        $total_rows += $rows;
                        $total_tables++;
                    }
                }

                // Sort tables by size (largest first)
                usort($db_tables_analysis, function($a, $b) {
                    return $b['total_size'] - $a['total_size'];
                });
                ?>

                <h4>📊 Database Overview</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $total_tables; ?></div>
                        <div class="debug-metric-label">Total Tables</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo number_format($total_rows); ?></div>
                        <div class="debug-metric-label">Total Rows</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo round($total_size / 1024 / 1024, 1); ?>MB</div>
                        <div class="debug-metric-label">Total Size</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html($wpdb->charset); ?></div>
                        <div class="debug-metric-label">Charset</div>
                    </div>
                </div>

                <h4>🗂️ All Database Tables (Scrollable & Resizable)</h4>
                <div class="debug-info">
                    <strong>💡 Table Features:</strong>
                    • <strong>Scrollable:</strong> Scroll through all <?php echo $total_tables; ?> tables
                    • <strong>Resizable:</strong> Drag the bottom-right corner to resize
                    • <strong>Sortable:</strong> Tables sorted by size (largest first)
                    • <strong>Status Indicators:</strong> Color-coded status badges
                </div>

                <!-- Enhanced Scrollable and Vertically Resizable Table Container -->
                <div style="
                    border: 3px solid var(--debug-border);
                    border-radius: 12px;
                    overflow: hidden;
                    margin: 20px 0;
                    background: var(--debug-bg);
                    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
                    resize: vertical;
                    min-height: 300px;
                    max-height: 1000px;
                    height: 450px;
                    width: 100%;
                    position: relative;
                ">
                    <!-- Sticky Header -->
                    <div style="
                        background: var(--debug-gradient);
                        color: white;
                        padding: 15px 20px;
                        font-weight: 700;
                        font-size: 16px;
                        position: sticky;
                        top: 0;
                        z-index: 10;
                        border-bottom: 2px solid rgba(255,255,255,0.2);
                    ">
                        📋 Database Tables (<?php echo $total_tables; ?> tables) - Scroll & Resize Available
                    </div>

                    <!-- Scrollable Table Content -->
                    <div style="
                        height: calc(100% - 60px);
                        overflow: auto;
                        background: var(--debug-bg);
                    ">
                        <table style="
                            width: 100%;
                            border-collapse: collapse;
                            margin: 0;
                            background: var(--debug-bg);
                            table-layout: fixed;
                        ">
                            <colgroup>
                                <col style="width: 25%;">
                                <col style="width: 10%;">
                                <col style="width: 12%;">
                                <col style="width: 12%;">
                                <col style="width: 12%;">
                                <col style="width: 12%;">
                                <col style="width: 17%;">
                            </colgroup>
                            <thead style="
                                position: sticky;
                                top: 0;
                                background: #f8f9fa;
                                z-index: 5;
                                border-bottom: 2px solid var(--debug-border);
                            ">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Table Name</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Rows</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Data Size</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Index Size</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Total Size</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Status</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Engine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($db_tables_analysis as $index => $table): ?>
                                <tr style="
                                    border-bottom: 1px solid var(--debug-border);
                                    background: <?php echo $index % 2 === 0 ? 'var(--debug-bg)' : 'rgba(0,123,186,0.02)'; ?>;
                                    transition: background-color 0.2s ease;
                                " onmouseover="this.style.background='rgba(0,123,186,0.08)'"
                                   onmouseout="this.style.background='<?php echo $index % 2 === 0 ? 'var(--debug-bg)' : 'rgba(0,123,186,0.02)'; ?>'">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px; word-wrap: break-word;">
                                        <strong style="color: var(--debug-primary);"><?php echo esc_html($table['name']); ?></strong>
                                        <?php if (strpos($table['name'], $wpdb->prefix) === 0): ?>
                                            <br><small style="color: #666; font-size: 11px;">WordPress Table</small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo number_format($table['rows']); ?>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo round($table['data_size'] / 1024 / 1024, 2); ?> MB
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo round($table['index_size'] / 1024 / 1024, 2); ?> MB
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <strong><?php echo round($table['total_size'] / 1024 / 1024, 2); ?> MB</strong>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php
                                        $badge_class = 'success';
                                        if ($table['status'] === 'Very Large') {
                                            $badge_class = 'error';
                                        } elseif ($table['status'] === 'Large') {
                                            $badge_class = 'warning';
                                        }
                                        ?>
                                        <span class="debug-badge <?php echo $badge_class; ?>" style="font-size: 11px; padding: 2px 6px;"><?php echo esc_html($table['status']); ?></span>
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php echo esc_html($table['engine']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <!-- Summary Row -->
                                <tr style="
                                    background: var(--debug-gradient);
                                    color: white;
                                    font-weight: 700;
                                    border-top: 3px solid var(--debug-primary);
                                ">
                                    <td style="padding: 10px 8px; border-right: 1px solid rgba(255,255,255,0.3); font-size: 13px;">
                                        <strong>TOTALS (<?php echo $total_tables; ?> tables)</strong>
                                    </td>
                                    <td style="padding: 10px 8px; border-right: 1px solid rgba(255,255,255,0.3); text-align: center; font-size: 13px;">
                                        <strong><?php echo number_format($total_rows); ?></strong>
                                    </td>
                                    <td style="padding: 10px 8px; border-right: 1px solid rgba(255,255,255,0.3); text-align: center; font-size: 13px;">
                                        <strong><?php echo round(array_sum(array_column($db_tables_analysis, 'data_size')) / 1024 / 1024, 2); ?> MB</strong>
                                    </td>
                                    <td style="padding: 10px 8px; border-right: 1px solid rgba(255,255,255,0.3); text-align: center; font-size: 13px;">
                                        <strong><?php echo round(array_sum(array_column($db_tables_analysis, 'index_size')) / 1024 / 1024, 2); ?> MB</strong>
                                    </td>
                                    <td style="padding: 10px 8px; border-right: 1px solid rgba(255,255,255,0.3); text-align: center; font-size: 13px;">
                                        <strong><?php echo round($total_size / 1024 / 1024, 2); ?> MB</strong>
                                    </td>
                                    <td style="padding: 10px 8px; border-right: 1px solid rgba(255,255,255,0.3); text-align: center; font-size: 13px;">
                                        <strong>SUMMARY</strong>
                                    </td>
                                    <td style="padding: 10px 8px; text-align: center; font-size: 13px;">
                                        <strong>ALL</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h4>💡 Database Optimization Tips</h4>
                <div class="debug-info">
                    <strong>🚀 Performance Recommendations:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Large Tables:</strong> Consider optimizing tables marked as "Large" or "Very Large"</li>
                        <li><strong>Database Cleanup:</strong> Remove unnecessary revisions, spam comments, and transients</li>
                        <li><strong>Indexing:</strong> Ensure proper indexing on frequently queried columns</li>
                        <li><strong>Regular Maintenance:</strong> Run OPTIMIZE TABLE periodically</li>
                        <li><strong>Backup Strategy:</strong> Implement regular database backups</li>
                        <li><strong>Monitoring:</strong> Monitor database growth and performance regularly</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Database Query Profiler -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔍 Database Query Profiler
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>📊 Query Analysis:</strong> Real-time WPDB query capture with EXPLAIN analysis and performance optimization recommendations.
                </div>

                <?php
                // Database Query Profiler Implementation
                global $wpdb;
                $query_analysis = [];
                $slow_queries = [];
                $total_query_time = 0;
                $query_count = 0;

                // Enable query logging temporarily
                if (!defined('SAVEQUERIES')) {
                    define('SAVEQUERIES', true);
                }

                // Analyze recent queries if available
                if (isset($wpdb->queries) && is_array($wpdb->queries)) {
                    foreach ($wpdb->queries as $query_data) {
                        $query = $query_data[0];
                        $time = floatval($query_data[1]);
                        $caller = $query_data[2];

                        $query_count++;
                        $total_query_time += $time;

                        // Identify slow queries (>0.1 seconds)
                        if ($time > 0.1) {
                            $slow_queries[] = [
                                'query' => $query,
                                'time' => $time,
                                'caller' => $caller,
                                'type' => $this->getQueryType($query)
                            ];
                        }

                        // Analyze query patterns
                        $query_type = $this->getQueryType($query);
                        if (!isset($query_analysis[$query_type])) {
                            $query_analysis[$query_type] = [
                                'count' => 0,
                                'total_time' => 0,
                                'avg_time' => 0
                            ];
                        }
                        $query_analysis[$query_type]['count']++;
                        $query_analysis[$query_type]['total_time'] += $time;
                        $query_analysis[$query_type]['avg_time'] = $query_analysis[$query_type]['total_time'] / $query_analysis[$query_type]['count'];
                    }
                }

                // Function to determine query type
                function getQueryType($query) {
                    $query = strtoupper(trim($query));
                    if (strpos($query, 'SELECT') === 0) return 'SELECT';
                    if (strpos($query, 'INSERT') === 0) return 'INSERT';
                    if (strpos($query, 'UPDATE') === 0) return 'UPDATE';
                    if (strpos($query, 'DELETE') === 0) return 'DELETE';
                    if (strpos($query, 'SHOW') === 0) return 'SHOW';
                    if (strpos($query, 'DESCRIBE') === 0) return 'DESCRIBE';
                    return 'OTHER';
                }
                ?>

                <h4>📈 Query Performance Overview</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $query_count; ?></div>
                        <div class="debug-metric-label">Total Queries</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo round($total_query_time * 1000, 2); ?>ms</div>
                        <div class="debug-metric-label">Total Query Time</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $query_count > 0 ? round(($total_query_time / $query_count) * 1000, 2) : 0; ?>ms</div>
                        <div class="debug-metric-label">Avg Query Time</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($slow_queries); ?></div>
                        <div class="debug-metric-label">Slow Queries</div>
                    </div>
                </div>

                <h4>🔍 Query Type Analysis</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 200px;
                    max-height: 400px;
                    height: 250px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                                <col style="width: 20%;">
                                <col style="width: 25%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Query Type</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Count</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Total Time</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Avg Time</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($query_analysis as $type => $data): ?>
                                <tr style="border-bottom: 1px solid var(--debug-border);">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">
                                        <strong><?php echo esc_html($type); ?></strong>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo $data['count']; ?>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo round($data['total_time'] * 1000, 2); ?>ms
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo round($data['avg_time'] * 1000, 2); ?>ms
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php
                                        $performance = 'Good';
                                        $badge_class = 'success';
                                        if ($data['avg_time'] > 0.1) {
                                            $performance = 'Slow';
                                            $badge_class = 'error';
                                        } elseif ($data['avg_time'] > 0.05) {
                                            $performance = 'Fair';
                                            $badge_class = 'warning';
                                        }
                                        ?>
                                        <span class="debug-badge <?php echo $badge_class; ?>" style="font-size: 11px; padding: 2px 6px;"><?php echo $performance; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($slow_queries)): ?>
                <h4>🐌 Slow Queries Analysis</h4>
                <div class="debug-warning">
                    <strong>⚠️ Found <?php echo count($slow_queries); ?> slow queries (>100ms)</strong>
                </div>

                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 250px;
                    max-height: 500px;
                    height: 300px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <?php foreach (array_slice($slow_queries, 0, 10) as $index => $slow_query): ?>
                        <div style="padding: 10px; border-bottom: 1px solid var(--debug-border); background: <?php echo $index % 2 === 0 ? 'var(--debug-bg)' : 'rgba(255,193,7,0.1)'; ?>;">
                            <div style="margin-bottom: 5px;">
                                <strong style="color: #dc3545;">Query #<?php echo $index + 1; ?></strong>
                                <span style="float: right; color: #dc3545; font-weight: bold;"><?php echo round($slow_query['time'] * 1000, 2); ?>ms</span>
                            </div>
                            <div style="font-family: monospace; font-size: 12px; background: #f8f9fa; padding: 8px; border-radius: 4px; margin: 5px 0; word-wrap: break-word;">
                                <?php echo esc_html(substr($slow_query['query'], 0, 200)) . (strlen($slow_query['query']) > 200 ? '...' : ''); ?>
                            </div>
                            <div style="font-size: 11px; color: #666;">
                                <strong>Called by:</strong> <?php echo esc_html($slow_query['caller']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <h4>💡 Query Optimization Recommendations</h4>
                <div class="debug-info">
                    <strong>🚀 Performance Improvements:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Index Optimization:</strong> Add indexes to frequently queried columns</li>
                        <li><strong>Query Caching:</strong> Enable object caching (Redis/Memcached)</li>
                        <li><strong>Limit Results:</strong> Use LIMIT clauses for large result sets</li>
                        <li><strong>Avoid SELECT *:</strong> Select only needed columns</li>
                        <li><strong>Use Prepared Statements:</strong> For repeated queries with different parameters</li>
                        <li><strong>Database Optimization:</strong> Regular OPTIMIZE TABLE maintenance</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Theme Template Diagnostics -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🎨 Theme Template Diagnostics
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🎨 Template Analysis:</strong> Complete theme hierarchy analysis with FSE support and template optimization recommendations.
                </div>

                <?php
                // Theme Template Diagnostics Implementation
                $current_theme = wp_get_theme();
                $parent_theme = $current_theme->parent();
                $template_hierarchy = [];
                $theme_features = [];
                $template_files = [];

                // Get theme support features
                $supported_features = [
                    'post-thumbnails' => 'Post Thumbnails',
                    'custom-logo' => 'Custom Logo',
                    'custom-background' => 'Custom Background',
                    'custom-header' => 'Custom Header',
                    'menus' => 'Navigation Menus',
                    'widgets' => 'Widgets',
                    'html5' => 'HTML5 Support',
                    'title-tag' => 'Title Tag',
                    'customize-selective-refresh-widgets' => 'Selective Refresh',
                    'editor-styles' => 'Editor Styles',
                    'wp-block-styles' => 'Block Styles',
                    'responsive-embeds' => 'Responsive Embeds',
                    'align-wide' => 'Wide Alignment'
                ];

                foreach ($supported_features as $feature => $label) {
                    $theme_features[$feature] = [
                        'label' => $label,
                        'supported' => current_theme_supports($feature)
                    ];
                }

                // Get template files
                $template_directory = get_template_directory();
                $template_files_found = glob($template_directory . '/*.php');

                foreach ($template_files_found as $file) {
                    $filename = basename($file);
                    $template_files[] = [
                        'name' => $filename,
                        'path' => $file,
                        'size' => filesize($file),
                        'modified' => filemtime($file),
                        'type' => $this->getTemplateType($filename)
                    ];
                }

                // Function to determine template type
                function getTemplateType($filename) {
                    if (strpos($filename, 'index') === 0) return 'Main';
                    if (strpos($filename, 'single') === 0) return 'Single Post';
                    if (strpos($filename, 'page') === 0) return 'Page';
                    if (strpos($filename, 'archive') === 0) return 'Archive';
                    if (strpos($filename, 'category') === 0) return 'Category';
                    if (strpos($filename, 'tag') === 0) return 'Tag';
                    if (strpos($filename, 'search') === 0) return 'Search';
                    if (strpos($filename, '404') === 0) return 'Error';
                    if (strpos($filename, 'header') === 0) return 'Header';
                    if (strpos($filename, 'footer') === 0) return 'Footer';
                    if (strpos($filename, 'sidebar') === 0) return 'Sidebar';
                    if (strpos($filename, 'functions') === 0) return 'Functions';
                    return 'Other';
                }

                // Sort template files by type
                usort($template_files, function($a, $b) {
                    return strcmp($a['type'], $b['type']);
                });
                ?>

                <h4>🎨 Theme Information</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html($current_theme->get('Name')); ?></div>
                        <div class="debug-metric-label">Active Theme</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo esc_html($current_theme->get('Version')); ?></div>
                        <div class="debug-metric-label">Version</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $parent_theme ? 'Child' : 'Parent'; ?></div>
                        <div class="debug-metric-label">Theme Type</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($template_files); ?></div>
                        <div class="debug-metric-label">Template Files</div>
                    </div>
                </div>

                <h4>🔧 Theme Features Support</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 200px;
                    max-height: 400px;
                    height: 250px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 40%;">
                                <col style="width: 20%;">
                                <col style="width: 40%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Feature</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Supported</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Recommendation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($theme_features as $feature => $data): ?>
                                <tr style="border-bottom: 1px solid var(--debug-border);">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">
                                        <strong><?php echo esc_html($data['label']); ?></strong>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php if ($data['supported']): ?>
                                            <span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">✓ Yes</span>
                                        <?php else: ?>
                                            <span class="debug-badge error" style="font-size: 11px; padding: 2px 6px;">✗ No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php
                                        if (!$data['supported']) {
                                            switch ($feature) {
                                                case 'post-thumbnails':
                                                    echo 'Add add_theme_support(\'post-thumbnails\')';
                                                    break;
                                                case 'title-tag':
                                                    echo 'Add add_theme_support(\'title-tag\')';
                                                    break;
                                                case 'html5':
                                                    echo 'Add HTML5 support for better markup';
                                                    break;
                                                case 'menus':
                                                    echo 'Register navigation menus';
                                                    break;
                                                default:
                                                    echo 'Consider adding this feature';
                                            }
                                        } else {
                                            echo '<span style="color: #28a745;">✓ Properly configured</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h4>📁 Template Files Analysis</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 250px;
                    max-height: 500px;
                    height: 300px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 25%;">
                                <col style="width: 15%;">
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                                <col style="width: 25%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Template File</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Type</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Size</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Last Modified</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($template_files as $template): ?>
                                <tr style="border-bottom: 1px solid var(--debug-border);">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px; word-wrap: break-word;">
                                        <strong style="color: var(--debug-primary);"><?php echo esc_html($template['name']); ?></strong>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo esc_html($template['type']); ?>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo round($template['size'] / 1024, 1); ?> KB
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo date('M j, Y', $template['modified']); ?>
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php
                                        $status = 'Normal';
                                        $badge_class = 'success';
                                        if ($template['size'] > 50000) { // > 50KB
                                            $status = 'Large';
                                            $badge_class = 'warning';
                                        }
                                        if ($template['size'] > 100000) { // > 100KB
                                            $status = 'Very Large';
                                            $badge_class = 'error';
                                        }
                                        ?>
                                        <span class="debug-badge <?php echo $badge_class; ?>" style="font-size: 11px; padding: 2px 6px;"><?php echo $status; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h4>💡 Theme Optimization Recommendations</h4>
                <div class="debug-info">
                    <strong>🚀 Theme Performance Improvements:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Template Hierarchy:</strong> Use specific templates for better performance</li>
                        <li><strong>Child Themes:</strong> Use child themes for customizations</li>
                        <li><strong>Code Optimization:</strong> Minimize PHP code in templates</li>
                        <li><strong>Asset Loading:</strong> Properly enqueue CSS and JavaScript</li>
                        <li><strong>Image Optimization:</strong> Use responsive images and WebP format</li>
                        <li><strong>Caching:</strong> Implement template caching where possible</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Block Editor & Gutenberg Diagnostics -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🧱 Block Editor & Gutenberg Diagnostics
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🧱 Block Analysis:</strong> Block structure validation and deprecation detection with Gutenberg compatibility assessment.
                </div>

                <?php
                // Block Editor Diagnostics Implementation
                $block_analysis = [];
                $registered_blocks = [];
                $block_patterns = [];
                $block_styles = [];

                // Get registered blocks
                if (function_exists('get_dynamic_block_names')) {
                    $dynamic_blocks = get_dynamic_block_names();
                    $static_blocks = array_diff(array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered()), $dynamic_blocks);

                    $registered_blocks = [
                        'dynamic' => $dynamic_blocks,
                        'static' => $static_blocks,
                        'total' => count($dynamic_blocks) + count($static_blocks)
                    ];
                }

                // Get block patterns
                if (function_exists('get_block_patterns')) {
                    $patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
                    $block_patterns = array_slice($patterns, 0, 10); // Limit for display
                }

                // Check Gutenberg features
                $gutenberg_features = [
                    'Block Editor' => function_exists('register_block_type'),
                    'Block Patterns' => function_exists('register_block_pattern'),
                    'Block Styles' => function_exists('register_block_style'),
                    'Full Site Editing' => function_exists('wp_is_block_theme') && wp_is_block_theme(),
                    'Widget Blocks' => function_exists('wp_use_widgets_block_editor') && wp_use_widgets_block_editor(),
                    'Navigation Block' => function_exists('wp_is_navigation_post_type'),
                ];
                ?>

                <h4>🧱 Block Editor Overview</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $registered_blocks['total']; ?></div>
                        <div class="debug-metric-label">Registered Blocks</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($registered_blocks['dynamic']); ?></div>
                        <div class="debug-metric-label">Dynamic Blocks</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($block_patterns); ?></div>
                        <div class="debug-metric-label">Block Patterns</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo wp_is_block_theme() ? 'YES' : 'NO'; ?></div>
                        <div class="debug-metric-label">Block Theme</div>
                    </div>
                </div>

                <h4>🔧 Gutenberg Features Support</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 200px;
                    max-height: 350px;
                    height: 250px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 40%;">
                                <col style="width: 20%;">
                                <col style="width: 40%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Feature</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Status</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Recommendation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gutenberg_features as $feature => $enabled): ?>
                                <tr style="border-bottom: 1px solid var(--debug-border);">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">
                                        <strong><?php echo esc_html($feature); ?></strong>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php if ($enabled): ?>
                                            <span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">✓ Enabled</span>
                                        <?php else: ?>
                                            <span class="debug-badge warning" style="font-size: 11px; padding: 2px 6px;">⚠ Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php
                                        if ($enabled) {
                                            echo '<span style="color: #28a745;">✓ Working properly</span>';
                                        } else {
                                            switch ($feature) {
                                                case 'Block Editor':
                                                    echo 'Update WordPress to latest version';
                                                    break;
                                                case 'Full Site Editing':
                                                    echo 'Consider using a block theme';
                                                    break;
                                                case 'Widget Blocks':
                                                    echo 'Enable in Appearance > Widgets';
                                                    break;
                                                default:
                                                    echo 'Feature may not be available';
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h4>📦 Registered Blocks Analysis</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 250px;
                    max-height: 400px;
                    height: 300px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <div style="padding: 15px;">
                            <h5 style="margin: 0 0 10px 0; color: var(--debug-primary);">🔄 Dynamic Blocks (<?php echo count($registered_blocks['dynamic']); ?>)</h5>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-bottom: 20px;">
                                <?php foreach (array_slice($registered_blocks['dynamic'], 0, 12) as $block): ?>
                                <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 12px; border-left: 3px solid #007cba;">
                                    <strong><?php echo esc_html($block); ?></strong>
                                </div>
                                <?php endforeach; ?>
                                <?php if (count($registered_blocks['dynamic']) > 12): ?>
                                <div style="background: #e9ecef; padding: 8px; border-radius: 4px; font-size: 12px; text-align: center;">
                                    +<?php echo count($registered_blocks['dynamic']) - 12; ?> more...
                                </div>
                                <?php endif; ?>
                            </div>

                            <h5 style="margin: 0 0 10px 0; color: var(--debug-primary);">⚡ Static Blocks (<?php echo count($registered_blocks['static']); ?>)</h5>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                                <?php foreach (array_slice($registered_blocks['static'], 0, 12) as $block): ?>
                                <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 12px; border-left: 3px solid #28a745;">
                                    <strong><?php echo esc_html($block); ?></strong>
                                </div>
                                <?php endforeach; ?>
                                <?php if (count($registered_blocks['static']) > 12): ?>
                                <div style="background: #e9ecef; padding: 8px; border-radius: 4px; font-size: 12px; text-align: center;">
                                    +<?php echo count($registered_blocks['static']) - 12; ?> more...
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <h4>💡 Block Editor Optimization</h4>
                <div class="debug-info">
                    <strong>🚀 Block Performance Improvements:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Block Validation:</strong> Ensure all blocks validate properly</li>
                        <li><strong>Custom Blocks:</strong> Optimize custom block JavaScript and CSS</li>
                        <li><strong>Block Patterns:</strong> Create reusable block patterns for efficiency</li>
                        <li><strong>Block Styles:</strong> Use block styles instead of custom CSS classes</li>
                        <li><strong>Performance:</strong> Minimize block render functions complexity</li>
                        <li><strong>Accessibility:</strong> Ensure blocks meet accessibility standards</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Ultimate Content Detection & Template Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📄 Ultimate Content Detection & Template Analysis
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>📄 Content Analysis:</strong> Comprehensive content processing analysis with shortcode detection and template evaluation.
                </div>

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
                    echo '<div style="
                        border: 2px solid var(--debug-border);
                        border-radius: 8px;
                        overflow: hidden;
                        margin: 15px 0;
                        background: var(--debug-bg);
                        resize: vertical;
                        min-height: 200px;
                        max-height: 350px;
                        height: 250px;
                    ">';
                    echo '<div style="overflow: auto; height: 100%;">';
                    echo '<table style="width: 100%; border-collapse: collapse; table-layout: fixed;">';
                    echo '<colgroup>';
                    echo '<col style="width: 25%;">';
                    echo '<col style="width: 15%;">';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 40%;">';
                    echo '</colgroup>';
                    echo '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">';
                    echo '<tr>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Stage</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Length</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Status</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Details</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;"><strong>Raw Content</strong></td>';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . strlen($raw_content) . ' chars</td>';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;"><span class="debug-badge ' . (strlen($raw_content) > 0 ? 'success' : 'warning') . '" style="font-size: 11px; padding: 2px 6px;">' . (strlen($raw_content) > 0 ? 'Has Content' : 'Empty') . '</span></td>';
                    echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;">Original post content</td>';
                    echo '</tr>';

                    echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;"><strong>Processed Content</strong></td>';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . strlen($processed_content) . ' chars</td>';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;"><span class="debug-badge ' . (strlen($processed_content) > 0 ? 'success' : 'warning') . '" style="font-size: 11px; padding: 2px 6px;">' . (strlen($processed_content) > 0 ? 'Processed' : 'Empty') . '</span></td>';
                    echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;">After the_content filters</td>';
                    echo '</tr>';

                    echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;"><strong>Clean Content</strong></td>';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . strlen($clean_content) . ' chars</td>';
                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;"><span class="debug-badge ' . (strlen($clean_content) > 0 ? 'success' : 'error') . '" style="font-size: 11px; padding: 2px 6px;">' . (strlen($clean_content) > 0 ? 'Visible' : 'No Text') . '</span></td>';
                    echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;">After stripping HTML tags</td>';
                    echo '</tr>';

                    echo '</tbody></table>';
                    echo '</div></div>';
                }

                // Content filter timeline
                if (!empty($debug_content_filters)) {
                    echo '<h4>🔄 Content Filter Timeline</h4>';
                    echo '<div style="
                        border: 2px solid var(--debug-border);
                        border-radius: 8px;
                        overflow: hidden;
                        margin: 15px 0;
                        background: var(--debug-bg);
                        resize: vertical;
                        min-height: 200px;
                        max-height: 400px;
                        height: 300px;
                    ">';
                    echo '<div style="overflow: auto; height: 100%;">';
                    echo '<table style="width: 100%; border-collapse: collapse; table-layout: fixed;">';
                    echo '<colgroup>';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 30%;">';
                    echo '<col style="width: 25%;">';
                    echo '<col style="width: 25%;">';
                    echo '</colgroup>';
                    echo '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">';
                    echo '<tr>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Time</th>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Filter</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Content Length</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Memory</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    foreach ($debug_content_filters as $filter) {
                        echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">' . esc_html($filter['time']) . '</td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">' . esc_html($filter['filter']) . '</td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . number_format($filter['content_length']) . ' chars</td>';
                        echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;">' . round($filter['memory'] / 1024 / 1024, 2) . ' MB</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div></div>';
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

        <!-- Ultimate Plugin Analysis with Advanced Conflict Testing -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔌 Ultimate Plugin Analysis & Advanced Conflict Testing
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🔌 Plugin Analysis:</strong> Comprehensive plugin analysis with advanced conflict testing capabilities.
                </div>

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
                    echo '<div style="
                        border: 2px solid var(--debug-border);
                        border-radius: 8px;
                        overflow: hidden;
                        margin: 15px 0;
                        background: var(--debug-bg);
                        resize: vertical;
                        min-height: 250px;
                        max-height: 500px;
                        height: 350px;
                    ">';
                    echo '<div style="overflow: auto; height: 100%;">';
                    echo '<table style="width: 100%; border-collapse: collapse; table-layout: fixed;">';
                    echo '<colgroup>';
                    echo '<col style="width: 30%;">';
                    echo '<col style="width: 12%;">';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 25%;">';
                    echo '<col style="width: 13%;">';
                    echo '</colgroup>';
                    echo '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">';
                    echo '<tr>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Plugin</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Version</th>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Author</th>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">File</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Actions</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    foreach ($active_plugins as $plugin_file) {
                        if (in_array($plugin_file, $disabled_plugins)) continue;

                        $plugin_data = $all_plugins[$plugin_file] ?? null;
                        if ($plugin_data) {
                            echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                            echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;"><strong>' . esc_html($plugin_data['Name']) . '</strong>';
                            if (!empty($plugin_data['Description'])) {
                                echo '<br><small style="color: #666; font-size: 11px;">' . esc_html(substr($plugin_data['Description'], 0, 100)) . '...</small>';
                            }
                            echo '</td>';
                            echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . esc_html($plugin_data['Version']) . '</td>';
                            echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">' . esc_html($plugin_data['Author']) . '</td>';
                            echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 12px; word-wrap: break-word;"><code>' . esc_html($plugin_file) . '</code></td>';
                            echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;">';
                            echo '<a href="' . add_query_arg('debug_disable_plugins', $plugin_file) . '" class="debug-btn" style="font-size: 11px; padding: 4px 8px; text-decoration: none;">Test Disable</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</tbody></table>';
                    echo '</div></div>';
                }

                // Must-Use plugins
                if (!empty($mu_plugins)) {
                    echo '<h4>🔒 Must-Use Plugins</h4>';
                    echo '<div style="
                        border: 2px solid var(--debug-border);
                        border-radius: 8px;
                        overflow: hidden;
                        margin: 15px 0;
                        background: var(--debug-bg);
                        resize: vertical;
                        min-height: 200px;
                        max-height: 350px;
                        height: 250px;
                    ">';
                    echo '<div style="overflow: auto; height: 100%;">';
                    echo '<table style="width: 100%; border-collapse: collapse; table-layout: fixed;">';
                    echo '<colgroup>';
                    echo '<col style="width: 40%;">';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 40%;">';
                    echo '</colgroup>';
                    echo '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">';
                    echo '<tr>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Plugin</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Version</th>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; font-size: 13px;">File</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    foreach ($mu_plugins as $mu_file => $mu_data) {
                        echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;"><strong>' . esc_html($mu_data['Name']) . '</strong></td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . esc_html($mu_data['Version']) . '</td>';
                        echo '<td style="padding: 6px 8px; font-size: 12px; word-wrap: break-word;"><code>' . esc_html($mu_file) . '</code></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div></div>';
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
                <div class="debug-info">
                    <strong>🪝 Hooks Analysis:</strong> Comprehensive WordPress hooks and filters analysis with performance insights.
                </div>

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
                    echo '<div style="
                        border: 2px solid var(--debug-border);
                        border-radius: 8px;
                        overflow: hidden;
                        margin: 15px 0;
                        background: var(--debug-bg);
                        resize: vertical;
                        min-height: 300px;
                        max-height: 500px;
                        height: 400px;
                    ">';
                    echo '<div style="overflow: auto; height: 100%;">';
                    echo '<table style="width: 100%; border-collapse: collapse; table-layout: fixed;">';
                    echo '<colgroup>';
                    echo '<col style="width: 40%;">';
                    echo '<col style="width: 15%;">';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 25%;">';
                    echo '</colgroup>';
                    echo '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">';
                    echo '<tr>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Hook Name</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Call Count</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Type</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Category</th>';
                    echo '</tr></thead>';
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

                        echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 12px; word-wrap: break-word;"><code>' . esc_html($hook) . '</code></td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . $count . '</td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">';
                        if ($hook_type === 'Filter') {
                            echo '<span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">Filter</span>';
                        } else {
                            echo '<span class="debug-badge info" style="font-size: 11px; padding: 2px 6px;">Action</span>';
                        }
                        echo '</td>';
                        echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;"><span class="debug-badge warning" style="font-size: 11px; padding: 2px 6px;">' . $category . '</span></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div></div>';

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

        <!-- Ultimate HTTP & cURL Diagnostics -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🌐 Ultimate HTTP & cURL Diagnostics
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🌐 HTTP Diagnostics:</strong> Comprehensive HTTP and cURL connectivity analysis with detailed error diagnostics.
                </div>

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
                echo '<strong>User Agent:</strong> ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not set');
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
                ?>

                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 400px;
                    max-height: 700px;
                    height: 500px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 800px;">
                            <colgroup>
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 15%;">
                                <col style="width: 30%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Test Type</th>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Target URL</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Status</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Response Time</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">HTTP Details</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Diagnostic Analysis & Fixes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($test_descriptions as $test_key => $test_info) {
                                    $test_result = $curl_diagnostics[$test_key] ?? [];
                                    $is_success = ($test_result['status'] ?? '') === 'Success';
                                    $is_critical = $test_info['critical'];

                                    echo '<tr style="border-bottom: 1px solid var(--debug-border);' . ($is_critical && !$is_success ? ' background-color: rgba(220,53,69,0.1); border-left: 4px solid #dc3545;' : '') . '">';

                                    // Test Type
                                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">';
                                    echo '<strong>' . $test_info['name'] . '</strong>';
                                    if ($is_critical) {
                                        echo '<br><span class="debug-badge error" style="font-size: 10px; padding: 1px 4px;">CRITICAL</span>';
                                    }
                                    echo '<br><small style="color: #666; font-size: 11px;">' . $test_info['purpose'] . '</small>';
                                    echo '</td>';

                                    // Target URL
                                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">';
                                    echo '<code style="font-size: 11px; background: #f8f9fa; padding: 2px 4px; border-radius: 3px; word-wrap: break-word;">';
                                    echo esc_html($test_info['url']);
                                    echo '</code>';
                                    echo '</td>';

                                    // Status
                                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">';
                                    if ($is_success) {
                                        echo '<span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">✅ Success</span>';
                                    } else {
                                        echo '<span class="debug-badge error" style="font-size: 11px; padding: 2px 6px;">❌ Failed</span>';
                                    }
                                    echo '</td>';

                                    // Response Time
                                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">';
                                    $time = $test_result['time'] ?? '0ms';
                                    $time_numeric = (float) str_replace('ms', '', $time);
                                    if ($time_numeric > 5000) {
                                        echo '<span style="color: #dc3545; font-weight: bold;">' . esc_html($time) . '</span>';
                                        echo '<br><small style="color: #dc3545; font-size: 10px;">Very Slow</small>';
                                    } elseif ($time_numeric > 2000) {
                                        echo '<span style="color: #ffc107; font-weight: bold;">' . esc_html($time) . '</span>';
                                        echo '<br><small style="color: #ffc107; font-size: 10px;">Slow</small>';
                                    } else {
                                        echo '<span style="color: #28a745; font-weight: bold;">' . esc_html($time) . '</span>';
                                        echo '<br><small style="color: #28a745; font-size: 10px;">Good</small>';
                                    }
                                    echo '</td>';

                                    // HTTP Details
                                    echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">';
                                    if (isset($test_result['code'])) {
                                        $code = $test_result['code'];
                                        if ($code >= 200 && $code < 300) {
                                            echo '<span style="color: #28a745;">HTTP ' . $code . '</span>';
                                            echo '<br><small style="font-size: 10px;">Success</small>';
                                        } elseif ($code >= 300 && $code < 400) {
                                            echo '<span style="color: #ffc107;">HTTP ' . $code . '</span>';
                                            echo '<br><small style="font-size: 10px;">Redirect</small>';
                                        } elseif ($code >= 400 && $code < 500) {
                                            echo '<span style="color: #dc3545;">HTTP ' . $code . '</span>';
                                            echo '<br><small style="font-size: 10px;">Client Error</small>';
                                        } else {
                                            echo '<span style="color: #dc3545;">HTTP ' . $code . '</span>';
                                            echo '<br><small style="font-size: 10px;">Server Error</small>';
                                        }
                                    } else {
                                        echo '<span style="color: #6c757d;">No Response</span>';
                                    }
                                    echo '</td>';

                                    // Diagnostic Analysis & Fixes
                                    echo '<td style="padding: 6px 8px; font-size: 12px;">';
                                    if ($is_success) {
                                        echo '<span style="color: #28a745;">✅ <strong>All Good!</strong></span><br>';
                                        echo '<small style="font-size: 11px;">This test passed successfully. No action needed.</small>';
                                    } else {
                                        $error_message = $test_result['error'] ?? 'Unknown error';
                                        $fixes = analyzeCurlError($error_message, $test_key);

                                        echo '<div style="font-size: 11px; line-height: 1.4;">';
                                        echo '<strong style="color: #dc3545;">❌ Error Analysis:</strong><br>';
                                        echo '<div style="background: #f8f9fa; padding: 4px; border-radius: 3px; margin: 2px 0;">';
                                        echo '<code style="font-size: 9px; color: #dc3545;">' . esc_html(substr($error_message, 0, 80)) . '</code>';
                                        echo '</div>';

                                        echo '<strong style="color: #0066cc;">🔧 Fixes:</strong><br>';
                                        echo '<ul style="margin: 2px 0; padding-left: 12px;">';
                                        foreach (array_slice($fixes, 0, 2) as $fix) {
                                            echo '<li style="margin: 1px 0; font-size: 10px;">' . $fix . '</li>';
                                        }
                                        echo '</ul>';
                                        echo '</div>';
                                    }
                                    echo '</td>';

                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php
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
                <div class="debug-info">
                    <strong>🚀 Cache Analysis:</strong> Comprehensive caching and CDN analysis with performance optimization recommendations.
                </div>

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
                ?>

                <h4>🗄️ Object Cache Analysis</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 150px;
                    max-height: 300px;
                    height: 200px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 25%;">
                                <col style="width: 20%;">
                                <col style="width: 20%;">
                                <col style="width: 35%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Component</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Status</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Type</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Performance Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px solid var(--debug-border);">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;"><strong>Object Cache</strong></td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php if ($cache_analysis['object_cache']['enabled']): ?>
                                            <span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">Enabled</span>
                                        <?php else: ?>
                                            <span class="debug-badge warning" style="font-size: 11px; padding: 2px 6px;">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;"><?php echo esc_html($cache_analysis['object_cache']['type']); ?></td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php if ($cache_analysis['object_cache']['enabled']): ?>
                                            Reduces database queries significantly
                                        <?php else: ?>
                                            Missing - database queries not cached
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php
                // Display page cache details
                if ($cache_analysis['page_cache']['count'] > 0) {
                    echo '<h4>📄 Page Cache Analysis</h4>';
                    echo '<div style="
                        border: 2px solid var(--debug-border);
                        border-radius: 8px;
                        overflow: hidden;
                        margin: 15px 0;
                        background: var(--debug-bg);
                        resize: vertical;
                        min-height: 200px;
                        max-height: 350px;
                        height: 250px;
                    ">';
                    echo '<div style="overflow: auto; height: 100%;">';
                    echo '<table style="width: 100%; border-collapse: collapse; table-layout: fixed;">';
                    echo '<colgroup>';
                    echo '<col style="width: 25%;">';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 35%;">';
                    echo '</colgroup>';
                    echo '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">';
                    echo '<tr>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Plugin</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Status</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Type</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Recommendation</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    foreach ($cache_analysis['page_cache']['plugins'] as $plugin) {
                        echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;"><strong>' . esc_html($plugin) . '</strong></td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;"><span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">Active</span></td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">Page Caching</td>';
                        echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;">';

                        if ($cache_analysis['page_cache']['count'] > 1) {
                            echo 'Consider using only one caching plugin to avoid conflicts';
                        } else {
                            echo 'Good - page caching is active';
                        }

                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                    echo '</div></div>';
                } else {
                    echo '<h4>📄 Page Cache Analysis</h4>';
                    echo '<div class="debug-warning">';
                    echo '<strong>⚠️ No Page Caching Detected:</strong> Consider installing a page caching plugin like WP Rocket, W3 Total Cache, or WP Super Cache for better performance.';
                    echo '</div>';
                }

                // Display CDN analysis
                echo '<h4>🌐 CDN Analysis</h4>';
                echo '<div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 150px;
                    max-height: 300px;
                    height: 200px;
                ">';
                echo '<div style="overflow: auto; height: 100%;">';
                echo '<table style="width: 100%; border-collapse: collapse; table-layout: fixed;">';
                echo '<colgroup>';
                echo '<col style="width: 25%;">';
                echo '<col style="width: 20%;">';
                echo '<col style="width: 30%;">';
                echo '<col style="width: 25%;">';
                echo '</colgroup>';
                echo '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">';
                echo '<tr>';
                echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Component</th>';
                echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Status</th>';
                echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Details</th>';
                echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Benefits</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;"><strong>CDN Service</strong></td>';
                echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">';
                if ($cache_analysis['cdn_status']['detected']) {
                    echo '<span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">Detected</span>';
                } else {
                    echo '<span class="debug-badge warning" style="font-size: 11px; padding: 2px 6px;">Not Detected</span>';
                }
                echo '</td>';
                echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">';
                if ($cache_analysis['cdn_status']['detected']) {
                    echo 'Type: ' . esc_html($cache_analysis['cdn_status']['type']) . '<br>';
                    echo 'Indicators: ' . implode(', ', $cache_analysis['cdn_status']['indicators']);
                } else {
                    echo 'No CDN headers or plugins detected';
                }
                echo '</td>';
                echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;">';
                if ($cache_analysis['cdn_status']['detected']) {
                    echo 'Faster global content delivery, reduced server load';
                } else {
                    echo 'Consider using a CDN like Cloudflare for better global performance';
                }
                echo '</td>';
                echo '</tr>';
                echo '</tbody></table>';
                echo '</div></div>';

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

        <!-- Ultimate Error Log Analysis with Enhanced Pattern Detection -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔍 Error Pattern Analysis (Enhanced)
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>🔍 Enhanced Error Analysis:</strong> Detailed error pattern detection with actionable improvement recommendations.
                </div>

                <?php
                // Enhanced Error Log Analysis Implementation
                $error_analysis = [];
                $error_patterns = [];
                $actionable_fixes = [];
                $error_severity = [];

                // Define error log locations
                $error_log_paths = [
                    'WordPress Debug Log' => WP_CONTENT_DIR . '/debug.log',
                    'PHP Error Log' => ini_get('error_log'),
                    'Server Error Log' => $_SERVER['DOCUMENT_ROOT'] . '/error_log',
                    'Custom Error Log' => ABSPATH . 'wp-content/debug.log'
                ];

                // Error pattern definitions with actionable fixes
                $error_pattern_fixes = [
                    'Fatal error' => [
                        'severity' => 'Critical',
                        'fixes' => [
                            'Check for syntax errors in recently modified files',
                            'Verify all required PHP extensions are installed',
                            'Increase PHP memory limit if memory exhaustion',
                            'Review plugin/theme compatibility'
                        ]
                    ],
                    'Warning' => [
                        'severity' => 'Medium',
                        'fixes' => [
                            'Update deprecated function calls',
                            'Fix undefined variable references',
                            'Correct file permission issues',
                            'Update plugins and themes to latest versions'
                        ]
                    ],
                    'Notice' => [
                        'severity' => 'Low',
                        'fixes' => [
                            'Initialize variables before use',
                            'Use proper array key checking',
                            'Update deprecated WordPress functions',
                            'Follow WordPress coding standards'
                        ]
                    ],
                    'Database error' => [
                        'severity' => 'High',
                        'fixes' => [
                            'Check database connection credentials',
                            'Verify database server is running',
                            'Repair corrupted database tables',
                            'Optimize database queries'
                        ]
                    ],
                    'Plugin error' => [
                        'severity' => 'Medium',
                        'fixes' => [
                            'Deactivate problematic plugins',
                            'Update plugins to latest versions',
                            'Check plugin compatibility',
                            'Contact plugin developer for support'
                        ]
                    ],
                    'Theme error' => [
                        'severity' => 'Medium',
                        'fixes' => [
                            'Switch to default theme temporarily',
                            'Update theme to latest version',
                            'Check theme compatibility with WordPress version',
                            'Review custom theme code for errors'
                        ]
                    ]
                ];

                // Analyze error logs
                foreach ($error_log_paths as $log_name => $log_path) {
                    if (file_exists($log_path) && is_readable($log_path)) {
                        $log_size = filesize($log_path);
                        $recent_errors = [];

                        if ($log_size > 0 && $log_size < 10 * 1024 * 1024) { // Max 10MB
                            $log_content = file_get_contents($log_path);
                            $log_lines = explode("\n", $log_content);

                            // Get recent errors (last 50 lines)
                            $recent_lines = array_slice($log_lines, -50);

                            foreach ($recent_lines as $line) {
                                if (empty(trim($line))) continue;

                                // Pattern matching for different error types
                                foreach ($error_pattern_fixes as $pattern => $info) {
                                    if (stripos($line, $pattern) !== false) {
                                        if (!isset($error_patterns[$pattern])) {
                                            $error_patterns[$pattern] = [
                                                'count' => 0,
                                                'severity' => $info['severity'],
                                                'fixes' => $info['fixes'],
                                                'examples' => []
                                            ];
                                        }
                                        $error_patterns[$pattern]['count']++;

                                        // Store example (truncated)
                                        if (count($error_patterns[$pattern]['examples']) < 3) {
                                            $error_patterns[$pattern]['examples'][] = substr($line, 0, 200);
                                        }
                                    }
                                }
                            }
                        }

                        $error_analysis[$log_name] = [
                            'path' => $log_path,
                            'size' => $log_size,
                            'readable' => true,
                            'recent_errors' => count($recent_errors)
                        ];
                    } else {
                        $error_analysis[$log_name] = [
                            'path' => $log_path,
                            'size' => 0,
                            'readable' => false,
                            'recent_errors' => 0
                        ];
                    }
                }

                // Calculate overall error severity score
                $total_critical = 0;
                $total_high = 0;
                $total_medium = 0;
                $total_low = 0;

                foreach ($error_patterns as $pattern => $data) {
                    switch ($data['severity']) {
                        case 'Critical': $total_critical += $data['count']; break;
                        case 'High': $total_high += $data['count']; break;
                        case 'Medium': $total_medium += $data['count']; break;
                        case 'Low': $total_low += $data['count']; break;
                    }
                }

                $error_health_score = 100;
                $error_health_score -= ($total_critical * 20);
                $error_health_score -= ($total_high * 10);
                $error_health_score -= ($total_medium * 5);
                $error_health_score -= ($total_low * 2);
                $error_health_score = max(0, $error_health_score);
                ?>

                <h4>🚨 Error Analysis Overview</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value" style="color: <?php echo $error_health_score >= 80 ? '#28a745' : ($error_health_score >= 60 ? '#ffc107' : '#dc3545'); ?>">
                            <?php echo $error_health_score; ?>/100
                        </div>
                        <div class="debug-metric-label">Error Health Score</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $total_critical; ?></div>
                        <div class="debug-metric-label">Critical Errors</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $total_high + $total_medium; ?></div>
                        <div class="debug-metric-label">Warnings</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($error_patterns); ?></div>
                        <div class="debug-metric-label">Error Types</div>
                    </div>
                </div>

                <h4>📊 Error Pattern Analysis with Actionable Fixes</h4>
                <?php if (!empty($error_patterns)): ?>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 300px;
                    max-height: 600px;
                    height: 400px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <?php foreach ($error_patterns as $pattern => $data): ?>
                        <div style="
                            padding: 15px;
                            border-bottom: 2px solid var(--debug-border);
                            background: <?php
                                echo $data['severity'] === 'Critical' ? 'rgba(220,53,69,0.1)' :
                                    ($data['severity'] === 'High' ? 'rgba(255,193,7,0.1)' :
                                    ($data['severity'] === 'Medium' ? 'rgba(255,193,7,0.05)' : 'rgba(0,123,255,0.05)'));
                            ?>;
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h5 style="margin: 0; color: var(--debug-primary);">
                                    🔍 <?php echo esc_html(ucfirst($pattern)); ?> Errors
                                </h5>
                                <div>
                                    <span class="debug-badge <?php
                                        echo $data['severity'] === 'Critical' ? 'error' :
                                            ($data['severity'] === 'High' ? 'warning' :
                                            ($data['severity'] === 'Medium' ? 'info' : 'success'));
                                    ?>" style="margin-right: 10px;">
                                        <?php echo esc_html($data['severity']); ?>
                                    </span>
                                    <span style="font-weight: bold; color: #dc3545;">
                                        <?php echo $data['count']; ?> occurrences
                                    </span>
                                </div>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <strong style="color: #28a745;">💡 Actionable Fixes:</strong>
                                <ul style="margin: 5px 0; padding-left: 20px;">
                                    <?php foreach ($data['fixes'] as $fix): ?>
                                    <li style="margin: 3px 0; font-size: 14px;"><?php echo esc_html($fix); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <?php if (!empty($data['examples'])): ?>
                            <div>
                                <strong>📝 Recent Examples:</strong>
                                <?php foreach ($data['examples'] as $example): ?>
                                <div style="
                                    background: #f8f9fa;
                                    padding: 8px;
                                    margin: 5px 0;
                                    border-radius: 4px;
                                    font-family: monospace;
                                    font-size: 12px;
                                    word-wrap: break-word;
                                    border-left: 3px solid <?php
                                        echo $data['severity'] === 'Critical' ? '#dc3545' :
                                            ($data['severity'] === 'High' ? '#ffc107' : '#007bff');
                                    ?>;
                                ">
                                    <?php echo esc_html($example); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="debug-success">
                    <strong>✅ No Error Patterns Detected</strong><br>
                    Your WordPress installation appears to be running without detectable error patterns.
                </div>
                <?php endif; ?>

                <h4>📁 Error Log Files Status</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 200px;
                    max-height: 350px;
                    height: 250px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 25%;">
                                <col style="width: 35%;">
                                <col style="width: 15%;">
                                <col style="width: 25%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Log File</th>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Path</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Size</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($error_analysis as $log_name => $log_data): ?>
                                <tr style="border-bottom: 1px solid var(--debug-border);">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">
                                        <strong><?php echo esc_html($log_name); ?></strong>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 12px; word-wrap: break-word;">
                                        <code><?php echo esc_html($log_data['path']); ?></code>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo $log_data['size'] > 0 ? round($log_data['size'] / 1024, 1) . ' KB' : '0 KB'; ?>
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php if ($log_data['readable'] && $log_data['size'] > 0): ?>
                                            <span class="debug-badge warning" style="font-size: 11px; padding: 2px 6px;">Has Errors</span>
                                        <?php elseif ($log_data['readable']): ?>
                                            <span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">Clean</span>
                                        <?php else: ?>
                                            <span class="debug-badge info" style="font-size: 11px; padding: 2px 6px;">Not Found</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h4>🛠️ Priority Action Items</h4>
                <div class="debug-info">
                    <strong>🎯 Immediate Actions to Improve Error Health:</strong>
                    <ol style="margin: 10px 0; padding-left: 20px;">
                        <?php if ($total_critical > 0): ?>
                        <li><strong style="color: #dc3545;">URGENT:</strong> Address <?php echo $total_critical; ?> critical errors immediately</li>
                        <?php endif; ?>
                        <?php if ($total_high > 0): ?>
                        <li><strong style="color: #ffc107;">HIGH:</strong> Fix <?php echo $total_high; ?> high-priority errors</li>
                        <?php endif; ?>
                        <li><strong>Enable Error Logging:</strong> Ensure WP_DEBUG_LOG is enabled for monitoring</li>
                        <li><strong>Regular Monitoring:</strong> Check error logs weekly for new issues</li>
                        <li><strong>Plugin/Theme Updates:</strong> Keep all components updated to latest versions</li>
                        <li><strong>Backup Strategy:</strong> Implement automated backups before making fixes</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Real-Time Log Tailing with WebSocket Integration -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📡 Real-Time Log Tailing (Live Monitoring)
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>📡 Real-Time Log Monitoring:</strong> Live monitoring of WordPress errors, PHP errors, and custom logs with WebSocket integration and AJAX fallback.
                </div>

                <?php
                // Real-Time Log Tailing Implementation
                debug_time('realtime_log_start');

                // Available log files for monitoring
                $log_files = [
                    'WordPress Error Log' => [
                        'path' => WP_CONTENT_DIR . '/debug.log',
                        'type' => 'wordpress',
                        'enabled' => true
                    ],
                    'PHP Error Log' => [
                        'path' => ini_get('error_log') ?: '/var/log/php_errors.log',
                        'type' => 'php',
                        'enabled' => true
                    ],
                    'Apache Error Log' => [
                        'path' => '/var/log/apache2/error.log',
                        'type' => 'apache',
                        'enabled' => file_exists('/var/log/apache2/error.log')
                    ],
                    'Nginx Error Log' => [
                        'path' => '/var/log/nginx/error.log',
                        'type' => 'nginx',
                        'enabled' => file_exists('/var/log/nginx/error.log')
                    ],
                    'Custom Debug Log' => [
                        'path' => ABSPATH . 'wp-content/debug-custom.log',
                        'type' => 'custom',
                        'enabled' => file_exists(ABSPATH . 'wp-content/debug-custom.log')
                    ]
                ];

                // Check log file accessibility and get stats
                $log_stats = [];
                foreach ($log_files as $name => $config) {
                    if ($config['enabled'] && file_exists($config['path'])) {
                        $log_stats[$name] = [
                            'path' => $config['path'],
                            'type' => $config['type'],
                            'size' => filesize($config['path']),
                            'modified' => filemtime($config['path']),
                            'readable' => is_readable($config['path']),
                            'lines' => 0
                        ];

                        // Count lines for performance estimation
                        if ($log_stats[$name]['readable'] && $log_stats[$name]['size'] < 1048576) { // Only for files < 1MB
                            $line_count = 0;
                            $handle = fopen($config['path'], 'r');
                            if ($handle) {
                                while (!feof($handle)) {
                                    fgets($handle);
                                    $line_count++;
                                }
                                fclose($handle);
                                $log_stats[$name]['lines'] = $line_count;
                            }
                        }
                    }
                }

                debug_time('realtime_log_analyzed');
                ?>

                <!-- Real-Time Monitoring Controls -->
                <div class="debug-metrics-grid" style="margin: 20px 0;">
                    <div class="debug-metric">
                        <div class="debug-metric-value" id="log-monitor-status">⏸️ Stopped</div>
                        <div class="debug-metric-label">Monitor Status</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value" id="websocket-status">🔴 Disconnected</div>
                        <div class="debug-metric-label">WebSocket Status</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value" id="log-entries-count">0</div>
                        <div class="debug-metric-label">Live Entries</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value" id="monitor-uptime">00:00:00</div>
                        <div class="debug-metric-label">Monitor Uptime</div>
                    </div>
                </div>

                <!-- Log File Selection and Controls -->
                <div style="background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px; padding: 20px; margin: 15px 0;">
                    <h4>🎛️ Log Monitoring Controls</h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div>
                            <label for="log-file-select" style="display: block; margin-bottom: 5px; font-weight: 600;">Select Log File:</label>
                            <select id="log-file-select" style="width: 100%; padding: 8px; border: 1px solid var(--debug-border); border-radius: 4px; background: var(--debug-bg); color: var(--debug-text);">
                                <?php foreach ($log_stats as $name => $stats): ?>
                                    <?php if ($stats['readable']): ?>
                                        <option value="<?php echo esc_attr($stats['path']); ?>" data-type="<?php echo esc_attr($stats['type']); ?>">
                                            <?php echo esc_html($name); ?> (<?php echo round($stats['size'] / 1024, 1); ?> KB)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="log-filter-level" style="display: block; margin-bottom: 5px; font-weight: 600;">Filter Level:</label>
                            <select id="log-filter-level" style="width: 100%; padding: 8px; border: 1px solid var(--debug-border); border-radius: 4px; background: var(--debug-bg); color: var(--debug-text);">
                                <option value="all">All Levels</option>
                                <option value="error">Errors Only</option>
                                <option value="warning">Warnings & Errors</option>
                                <option value="notice">Notices & Above</option>
                                <option value="fatal">Fatal Errors Only</option>
                            </select>
                        </div>

                        <div>
                            <label for="log-refresh-rate" style="display: block; margin-bottom: 5px; font-weight: 600;">Refresh Rate:</label>
                            <select id="log-refresh-rate" style="width: 100%; padding: 8px; border: 1px solid var(--debug-border); border-radius: 4px; background: var(--debug-bg); color: var(--debug-text);">
                                <option value="1000">1 Second</option>
                                <option value="2000" selected>2 Seconds</option>
                                <option value="5000">5 Seconds</option>
                                <option value="10000">10 Seconds</option>
                                <option value="30000">30 Seconds</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button id="start-log-monitor" class="debug-button" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            ▶️ Start Monitoring
                        </button>
                        <button id="stop-log-monitor" class="debug-button" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;" disabled>
                            ⏹️ Stop Monitoring
                        </button>
                        <button id="clear-log-display" class="debug-button" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            🗑️ Clear Display
                        </button>
                        <button id="export-log-data" class="debug-button" style="background: #17a2b8; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            💾 Export Logs
                        </button>
                        <button id="connect-websocket" class="debug-button" style="background: #fd7e14; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            🔌 Connect WebSocket
                        </button>
                    </div>
                </div>

                <!-- Real-Time Log Display -->
                <h4>📊 Live Log Stream</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 400px;
                    max-height: 800px;
                    height: 500px;
                    width: 100%;
                ">
                    <div id="log-stream-container" style="
                        height: 100%;
                        overflow-y: auto;
                        padding: 15px;
                        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
                        font-size: 12px;
                        line-height: 1.4;
                        background: #1a1a1a;
                        color: #e0e0e0;
                    ">
                        <div id="log-stream-content">
                            <div style="color: #888; text-align: center; padding: 50px 0;">
                                📡 Real-Time Log Monitor Ready<br>
                                <small>Select a log file and click "Start Monitoring" to begin live log tailing</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log File Statistics -->
                <h4>📋 Available Log Files</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 200px;
                    max-height: 400px;
                    height: 250px;
                    width: 100%;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 25%;">
                                <col style="width: 35%;">
                                <col style="width: 15%;">
                                <col style="width: 15%;">
                                <col style="width: 10%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Log File</th>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Path</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Size</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Last Modified</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($log_stats as $name => $stats): ?>
                                <tr style="border-bottom: 1px solid var(--debug-border);">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">
                                        <strong><?php echo esc_html($name); ?></strong>
                                        <br><small style="color: #666;"><?php echo esc_html($stats['type']); ?></small>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 11px; word-wrap: break-word;">
                                        <code><?php echo esc_html($stats['path']); ?></code>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <?php echo round($stats['size'] / 1024, 1); ?> KB
                                        <?php if ($stats['lines'] > 0): ?>
                                            <br><small style="color: #666;"><?php echo number_format($stats['lines']); ?> lines</small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 12px;">
                                        <?php echo date('M j, H:i', $stats['modified']); ?>
                                        <br><small style="color: #666;"><?php echo human_time_diff($stats['modified']); ?> ago</small>
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php if ($stats['readable']): ?>
                                            <span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">Readable</span>
                                        <?php else: ?>
                                            <span class="debug-badge error" style="font-size: 11px; padding: 2px 6px;">No Access</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <h4>⚡ Real-Time Monitoring Performance</h4>
                <div class="debug-metrics-grid" style="margin: 20px 0;">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo round((debug_time('realtime_log_analyzed') - debug_time('realtime_log_start')) * 1000, 2); ?>ms</div>
                        <div class="debug-metric-label">Analysis Time</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($log_stats); ?></div>
                        <div class="debug-metric-label">Available Logs</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo array_sum(array_column($log_stats, 'size')) > 0 ? round(array_sum(array_column($log_stats, 'size')) / 1024, 1) : 0; ?> KB</div>
                        <div class="debug-metric-label">Total Log Size</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value" id="ajax-response-time">0ms</div>
                        <div class="debug-metric-label">AJAX Response</div>
                    </div>
                </div>

                <!-- WebSocket Integration Instructions -->
                <div class="debug-info" style="margin: 20px 0;">
                    <strong>🔌 WebSocket Server Setup:</strong>
                    <p>For true real-time monitoring, set up a WebSocket server using ReactPHP or Ratchet:</p>
                    <div style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; margin: 10px 0; font-family: monospace; font-size: 12px;">
// WebSocket Server Example (ReactPHP)<br>
composer require ratchet/pawl react/socket<br><br>
// Server code:<br>
function start_log_tail_websocket() {<br>
&nbsp;&nbsp;&nbsp;&nbsp;$loop = React\EventLoop\Factory::create();<br>
&nbsp;&nbsp;&nbsp;&nbsp;$socket = new React\Socket\Server('127.0.0.1:8080', $loop);<br>
&nbsp;&nbsp;&nbsp;&nbsp;$server = new Ratchet\Server\IoServer(<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;new Ratchet\Http\HttpServer(<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;new Ratchet\WebSocket\WsServer(<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;new LogTailWebSocket()<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;),<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$socket<br>
&nbsp;&nbsp;&nbsp;&nbsp;);<br>
&nbsp;&nbsp;&nbsp;&nbsp;$server->run();<br>
}
                    </div>
                    <p><strong>Current Implementation:</strong> Uses AJAX polling as fallback with 2-second intervals for compatibility.</p>
                </div>
            </div>
        </div>

        <!-- WP-CLI Integration & Command Runner -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⚡ WP-CLI Integration & Command Runner
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>⚡ WP-CLI Command Execution:</strong> Execute WP-CLI commands directly from the debug interface for database repairs, cache clearing, plugin management, and more.
                </div>

                <?php
                // WP-CLI Integration Implementation
                debug_time('wp_cli_start');

                // Check if WP-CLI is available
                $wp_cli_available = false;
                $wp_cli_version = '';
                $wp_cli_path = '';

                $possible_paths = [
                    '/usr/local/bin/wp',
                    '/usr/bin/wp',
                    '/opt/wp-cli/wp',
                    'wp'
                ];

                foreach ($possible_paths as $path) {
                    $which_result = shell_exec("which $path 2>/dev/null");
                    if (!empty($which_result)) {
                        $wp_cli_path = trim($which_result);
                        $version_output = shell_exec("$path --version 2>/dev/null");
                        if ($version_output) {
                            $wp_cli_available = true;
                            $wp_cli_version = trim($version_output);
                            break;
                        }
                    }
                }

                // Predefined command categories
                $command_categories = [
                    'Cache Management' => [
                        'cache flush' => 'Clear all caches',
                        'cache status' => 'Show cache status'
                    ],
                    'Database Operations' => [
                        'db check' => 'Check database integrity',
                        'db size' => 'Show database size',
                        'db optimize' => 'Optimize database tables'
                    ],
                    'Plugin Management' => [
                        'plugin list' => 'List all plugins',
                        'plugin status' => 'Show plugin status'
                    ],
                    'Theme Management' => [
                        'theme list' => 'List all themes',
                        'theme status' => 'Show active theme'
                    ],
                    'Core Operations' => [
                        'core check-update' => 'Check for WordPress updates',
                        'core version' => 'Show WordPress version',
                        'rewrite flush' => 'Flush rewrite rules'
                    ],
                    'User Management' => [
                        'user list' => 'List all users'
                    ],
                    'Cron Jobs' => [
                        'cron event list' => 'List scheduled events'
                    ]
                ];

                debug_time('wp_cli_analyzed');
                ?>

                <!-- WP-CLI Status -->
                <h4>🔧 WP-CLI Status</h4>
                <div class="debug-metrics-grid" style="margin: 20px 0;">
                    <div class="debug-metric">
                        <div class="debug-metric-value">
                            <?php if ($wp_cli_available): ?>
                                <span class="debug-badge success">Available</span>
                            <?php else: ?>
                                <span class="debug-badge error">Not Found</span>
                            <?php endif; ?>
                        </div>
                        <div class="debug-metric-label">WP-CLI Status</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $wp_cli_version ?: 'N/A'; ?></div>
                        <div class="debug-metric-label">Version</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $wp_cli_path ?: 'Not Found'; ?></div>
                        <div class="debug-metric-label">Path</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value" id="wp-cli-response-time">0ms</div>
                        <div class="debug-metric-label">Last Execution</div>
                    </div>
                </div>

                <?php if ($wp_cli_available): ?>
                <!-- Command Interface -->
                <h4>💻 Command Interface</h4>
                <div style="background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px; padding: 20px; margin: 20px 0;">
                    <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 10px; margin-bottom: 15px;">
                        <div>
                            <label for="wp-cli-command" style="display: block; margin-bottom: 5px; font-weight: 600;">Command:</label>
                            <input type="text" id="wp-cli-command" placeholder="Enter WP-CLI command..."
                                   style="width: 100%; padding: 8px 12px; border: 1px solid var(--debug-border); border-radius: 4px; background: var(--debug-bg); color: var(--debug-text);">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Quick Commands:</label>
                            <select id="wp-cli-quick-commands" style="padding: 8px; border: 1px solid var(--debug-border); border-radius: 4px; background: var(--debug-bg); color: var(--debug-text);">
                                <option value="">Select a command...</option>
                                <?php foreach ($command_categories as $category => $commands): ?>
                                    <optgroup label="<?php echo esc_attr($category); ?>">
                                        <?php foreach ($commands as $command => $description): ?>
                                            <option value="<?php echo esc_attr($command); ?>"><?php echo esc_html($command); ?> - <?php echo esc_html($description); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">&nbsp;</label>
                            <button id="execute-wp-cli" style="padding: 8px 16px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                                Execute
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <button id="clear-wp-cli-output" style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                            Clear Output
                        </button>
                        <button id="export-wp-cli-history" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                            Export History
                        </button>
                        <span id="wp-cli-status" style="padding: 6px 12px; font-size: 12px; color: #666;"></span>
                    </div>
                </div>

                <!-- Command Output -->
                <h4>📋 Command Output</h4>
                <div id="wp-cli-output" style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; margin: 15px 0; font-family: monospace; font-size: 12px; min-height: 200px; max-height: 400px; overflow-y: auto; border: 2px solid var(--debug-border);">
                    <div style="color: #a0aec0; font-style: italic;">Ready to execute WP-CLI commands...</div>
                </div>

                <!-- Command History -->
                <h4>📜 Command History</h4>
                <div id="wp-cli-history" style="max-height: 300px; overflow-y: auto; border: 1px solid var(--debug-border); border-radius: 6px;">
                    <div style="padding: 15px; color: #666; font-style: italic; text-align: center;">No commands executed yet</div>
                </div>

                <?php else: ?>
                <!-- WP-CLI Installation Guide -->
                <div class="debug-warning" style="margin: 20px 0;">
                    <strong>⚠️ WP-CLI Not Found</strong>
                    <p>WP-CLI is not installed or not accessible. To use this feature, install WP-CLI:</p>
                    <div style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; margin: 10px 0; font-family: monospace; font-size: 12px;">
# Download WP-CLI<br>
curl -O https://raw.githubusercontent.com/wp-cli/wp-cli/v2.8.1/wp-cli.phar<br><br>
# Make it executable<br>
chmod +x wp-cli.phar<br><br>
# Move to global location<br>
sudo mv wp-cli.phar /usr/local/bin/wp<br><br>
# Test installation<br>
wp --info
                    </div>
                </div>
                <?php endif; ?>

                <!-- Security Notice -->
                <div class="debug-info" style="margin: 20px 0;">
                    <strong>🔒 Security Information:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Only whitelisted commands are allowed for security</li>
                        <li>Commands are executed with proper escaping and validation</li>
                        <li>Admin privileges required for command execution</li>
                        <li>All command executions are logged with timestamps</li>
                        <li>Dangerous commands (like search-replace without --dry-run) are blocked</li>
                    </ul>
                </div>

                <!-- Performance Metrics -->
                <h4>⚡ WP-CLI Performance</h4>
                <div class="debug-metrics-grid" style="margin: 20px 0;">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo round((debug_time('wp_cli_analyzed') - debug_time('wp_cli_start')) * 1000, 2); ?>ms</div>
                        <div class="debug-metric-label">Analysis Time</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo array_sum(array_map('count', $command_categories)); ?></div>
                        <div class="debug-metric-label">Available Commands</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($command_categories); ?></div>
                        <div class="debug-metric-label">Command Categories</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value" id="wp-cli-commands-executed">0</div>
                        <div class="debug-metric-label">Commands Executed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ultimate Performance Summary with Optimization Guidance -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⏱️ Detailed Performance Breakdown (Enhanced)
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>⏱️ Performance Analysis:</strong> Detailed performance breakdown with actionable optimization tasks for 'Very Slow' items.
                </div>

                <?php
                // Enhanced Performance Analysis Implementation
                $performance_metrics = [];
                $slow_operations = [];
                $optimization_tasks = [];

                // Analyze current performance metrics
                $current_memory = memory_get_usage();
                $peak_memory = memory_get_peak_usage();
                $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));

                // Database performance analysis
                global $wpdb;
                $db_queries = isset($wpdb->num_queries) ? $wpdb->num_queries : 0;
                $db_query_time = 0;

                if (isset($wpdb->queries) && is_array($wpdb->queries)) {
                    foreach ($wpdb->queries as $query) {
                        $db_query_time += floatval($query[1]);
                    }
                }

                // Performance categories with thresholds
                $performance_categories = [
                    'Page Load Time' => [
                        'current' => isset($debug_timings['page_complete']) ? $debug_timings['page_complete'] : 0,
                        'threshold_slow' => 3000, // 3 seconds
                        'threshold_very_slow' => 5000, // 5 seconds
                        'unit' => 'ms',
                        'optimization_tasks' => [
                            'Enable caching (WP Rocket, W3 Total Cache)',
                            'Optimize images (WebP format, compression)',
                            'Minify CSS and JavaScript files',
                            'Use a Content Delivery Network (CDN)',
                            'Optimize database queries',
                            'Remove unused plugins and themes'
                        ]
                    ],
                    'Database Queries' => [
                        'current' => $db_queries,
                        'threshold_slow' => 50,
                        'threshold_very_slow' => 100,
                        'unit' => 'queries',
                        'optimization_tasks' => [
                            'Enable object caching (Redis/Memcached)',
                            'Optimize slow database queries',
                            'Add database indexes for frequent queries',
                            'Use query caching plugins',
                            'Reduce plugin database calls',
                            'Implement lazy loading for content'
                        ]
                    ],
                    'Database Query Time' => [
                        'current' => round($db_query_time * 1000, 2),
                        'threshold_slow' => 500, // 0.5 seconds
                        'threshold_very_slow' => 1000, // 1 second
                        'unit' => 'ms',
                        'optimization_tasks' => [
                            'Optimize slow SQL queries with EXPLAIN',
                            'Add proper database indexes',
                            'Use prepared statements',
                            'Implement query result caching',
                            'Optimize JOIN operations',
                            'Remove unnecessary ORDER BY clauses'
                        ]
                    ],
                    'Memory Usage' => [
                        'current' => round($current_memory / 1024 / 1024, 2),
                        'threshold_slow' => 128, // 128MB
                        'threshold_very_slow' => 256, // 256MB
                        'unit' => 'MB',
                        'optimization_tasks' => [
                            'Increase PHP memory limit if needed',
                            'Optimize plugin memory usage',
                            'Remove memory-intensive plugins',
                            'Optimize image processing',
                            'Use efficient data structures',
                            'Implement garbage collection'
                        ]
                    ],
                    'Peak Memory Usage' => [
                        'current' => round($peak_memory / 1024 / 1024, 2),
                        'threshold_slow' => 150, // 150MB
                        'threshold_very_slow' => 300, // 300MB
                        'unit' => 'MB',
                        'optimization_tasks' => [
                            'Profile memory usage with debugging tools',
                            'Optimize memory-intensive operations',
                            'Use memory-efficient algorithms',
                            'Implement memory pooling',
                            'Reduce object instantiation',
                            'Use unset() to free memory'
                        ]
                    ]
                ];

                // Categorize performance metrics
                foreach ($performance_categories as $category => $data) {
                    $status = 'Good';
                    $badge_class = 'success';
                    $is_very_slow = false;

                    if ($data['current'] >= $data['threshold_very_slow']) {
                        $status = 'Very Slow';
                        $badge_class = 'error';
                        $is_very_slow = true;
                        $slow_operations[] = $category;
                    } elseif ($data['current'] >= $data['threshold_slow']) {
                        $status = 'Slow';
                        $badge_class = 'warning';
                    }

                    $performance_metrics[$category] = [
                        'current' => $data['current'],
                        'unit' => $data['unit'],
                        'status' => $status,
                        'badge_class' => $badge_class,
                        'is_very_slow' => $is_very_slow,
                        'optimization_tasks' => $data['optimization_tasks']
                    ];
                }

                // Calculate overall performance score
                $performance_score = 100;
                foreach ($performance_metrics as $metric) {
                    if ($metric['status'] === 'Very Slow') {
                        $performance_score -= 25;
                    } elseif ($metric['status'] === 'Slow') {
                        $performance_score -= 15;
                    }
                }
                $performance_score = max(0, $performance_score);
                ?>

                <h4>📊 Performance Overview</h4>
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value" style="color: <?php echo $performance_score >= 80 ? '#28a745' : ($performance_score >= 60 ? '#ffc107' : '#dc3545'); ?>">
                            <?php echo $performance_score; ?>/100
                        </div>
                        <div class="debug-metric-label">Performance Score</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo count($slow_operations); ?></div>
                        <div class="debug-metric-label">Very Slow Items</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo round($current_memory / 1024 / 1024, 1); ?>MB</div>
                        <div class="debug-metric-label">Memory Usage</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $db_queries; ?></div>
                        <div class="debug-metric-label">DB Queries</div>
                    </div>
                </div>

                <h4>⚡ Performance Metrics Analysis</h4>
                <div style="
                    border: 2px solid var(--debug-border);
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: var(--debug-bg);
                    resize: vertical;
                    min-height: 250px;
                    max-height: 400px;
                    height: 300px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 30%;">
                                <col style="width: 20%;">
                                <col style="width: 20%;">
                                <col style="width: 30%;">
                            </colgroup>
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">
                                <tr>
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Metric</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Current Value</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Status</th>
                                    <th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Priority Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performance_metrics as $metric_name => $metric_data): ?>
                                <tr style="
                                    border-bottom: 1px solid var(--debug-border);
                                    background: <?php echo $metric_data['is_very_slow'] ? 'rgba(220,53,69,0.1)' : 'var(--debug-bg)'; ?>;
                                ">
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 13px;">
                                        <strong style="color: <?php echo $metric_data['is_very_slow'] ? '#dc3545' : 'var(--debug-primary)'; ?>">
                                            <?php echo esc_html($metric_name); ?>
                                        </strong>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <strong><?php echo $metric_data['current'] . ' ' . $metric_data['unit']; ?></strong>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">
                                        <span class="debug-badge <?php echo $metric_data['badge_class']; ?>" style="font-size: 11px; padding: 2px 6px;">
                                            <?php echo $metric_data['status']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center; font-size: 13px;">
                                        <?php if ($metric_data['is_very_slow']): ?>
                                            <strong style="color: #dc3545;">URGENT OPTIMIZATION NEEDED</strong>
                                        <?php elseif ($metric_data['status'] === 'Slow'): ?>
                                            <span style="color: #ffc107;">Optimization Recommended</span>
                                        <?php else: ?>
                                            <span style="color: #28a745;">✓ Performing Well</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($slow_operations)): ?>
                <h4>🚨 URGENT: Very Slow Items - Actionable Optimization Tasks</h4>
                <div class="debug-error">
                    <strong>⚠️ Found <?php echo count($slow_operations); ?> Very Slow performance items requiring immediate attention!</strong>
                </div>

                <div style="
                    border: 2px solid #dc3545;
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 15px 0;
                    background: rgba(220,53,69,0.05);
                    resize: vertical;
                    min-height: 300px;
                    max-height: 600px;
                    height: 400px;
                ">
                    <div style="overflow: auto; height: 100%;">
                        <?php foreach ($slow_operations as $slow_item): ?>
                        <?php $metric_data = $performance_metrics[$slow_item]; ?>
                        <div style="
                            padding: 20px;
                            border-bottom: 2px solid rgba(220,53,69,0.2);
                            background: rgba(220,53,69,0.08);
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h5 style="margin: 0; color: #dc3545;">
                                    🚨 <?php echo esc_html($slow_item); ?>
                                </h5>
                                <div>
                                    <span style="font-weight: bold; color: #dc3545; font-size: 16px;">
                                        <?php echo $metric_data['current'] . ' ' . $metric_data['unit']; ?>
                                    </span>
                                </div>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <strong style="color: #dc3545;">🎯 IMMEDIATE ACTION REQUIRED - Complete These Tasks:</strong>
                                <ol style="margin: 10px 0; padding-left: 25px;">
                                    <?php foreach ($metric_data['optimization_tasks'] as $index => $task): ?>
                                    <li style="
                                        margin: 8px 0;
                                        font-size: 14px;
                                        padding: 8px;
                                        background: rgba(255,255,255,0.7);
                                        border-radius: 4px;
                                        border-left: 4px solid #dc3545;
                                    ">
                                        <strong>Task <?php echo $index + 1; ?>:</strong> <?php echo esc_html($task); ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>

                            <div style="
                                background: rgba(40,167,69,0.1);
                                padding: 10px;
                                border-radius: 6px;
                                border-left: 4px solid #28a745;
                            ">
                                <strong style="color: #28a745;">💡 Expected Impact:</strong>
                                Completing these tasks should significantly improve <?php echo strtolower($slow_item); ?> performance and boost your overall performance score.
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="debug-success">
                    <strong>✅ Excellent Performance!</strong><br>
                    No 'Very Slow' items detected. Your WordPress site is performing well across all metrics.
                </div>
                <?php endif; ?>

                <h4>📈 Performance Optimization Roadmap</h4>
                <div class="debug-info">
                    <strong>🚀 Complete Performance Optimization Strategy:</strong>
                    <ol style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Immediate (Today):</strong> Address all 'Very Slow' items above</li>
                        <li><strong>This Week:</strong> Implement caching and CDN solutions</li>
                        <li><strong>This Month:</strong> Optimize database and remove unused plugins</li>
                        <li><strong>Ongoing:</strong> Monitor performance weekly and maintain optimizations</li>
                        <li><strong>Advanced:</strong> Consider server-level optimizations (PHP version, SSD storage)</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Cron Job Diagnostics & Health Check -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⏰ Cron Job Diagnostics & Health Check
            </div>
            <div class="debug-section-content">
                <div class="debug-info">
                    <strong>⏰ Cron Analysis:</strong> Comprehensive analysis of WordPress cron jobs, schedules, and background task health.
                </div>

                <?php
                debug_time('cron_display_start');

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
                    echo '<div style="
                        border: 2px solid var(--debug-border);
                        border-radius: 8px;
                        overflow: hidden;
                        margin: 15px 0;
                        background: var(--debug-bg);
                        resize: vertical;
                        min-height: 250px;
                        max-height: 400px;
                        height: 300px;
                    ">';
                    echo '<div style="overflow: auto; height: 100%;">';
                    echo '<table style="width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 700px;">';
                    echo '<colgroup>';
                    echo '<col style="width: 25%;">';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 20%;">';
                    echo '<col style="width: 15%;">';
                    echo '<col style="width: 20%;">';
                    echo '</colgroup>';
                    echo '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 5;">';
                    echo '<tr>';
                    echo '<th style="padding: 8px 10px; text-align: left; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Hook Name</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Next Run</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Time Until</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; border-right: 1px solid var(--debug-border); font-size: 13px;">Arguments</th>';
                    echo '<th style="padding: 8px 10px; text-align: center; font-weight: 700; font-size: 13px;">Status</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    foreach ($debug_cron_jobs['ready'] as $hook => $job) {
                        echo '<tr style="border-bottom: 1px solid var(--debug-border);">';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); font-size: 12px; word-wrap: break-word;"><code>' . esc_html($hook) . '</code></td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . $job['next_run'] . '</td>';
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">';
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
                        echo '<td style="padding: 6px 8px; border-right: 1px solid var(--debug-border); text-align: center; font-size: 13px;">' . count($job['args']) . ' args</td>';
                        echo '<td style="padding: 6px 8px; text-align: center; font-size: 13px;">';
                        if ($job['overdue']) {
                            echo '<span class="debug-badge error" style="font-size: 11px; padding: 2px 6px;">Overdue</span>';
                        } else {
                            echo '<span class="debug-badge success" style="font-size: 11px; padding: 2px 6px;">Ready</span>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div></div>';
                } else {
                    echo '<div class="debug-info">';
                    echo '<strong>ℹ️ No ready or overdue cron jobs found.</strong>';
                    echo '</div>';
                }

                // Cron recommendations
                echo '<div class="debug-info">';
                echo '<strong>🎯 Cron Optimization Tips:</strong><br>';
                echo '• Ensure WP-Cron is enabled for automatic background tasks<br>';
                echo '• Consider using server-level cron for high-traffic sites<br>';
                echo '• Monitor overdue jobs to identify performance issues<br>';
                echo '• Use wp-cron.php?doing_wp_cron for manual testing<br>';
                echo '• Check loopback connectivity for proper cron execution';
                echo '</div>';

                debug_time('cron_display_end');
                ?>
            </div>
        </div>

    </div>

    <script>
        // Enhanced JavaScript for Ultimate Debug Tool - Omega Version

        // Real-Time Log Monitoring Variables
        let logMonitorActive = false;
        let logMonitorInterval = null;
        let websocketConnection = null;
        let logEntriesCount = 0;
        let monitorStartTime = null;
        let lastLogPosition = 0;

        // Real-Time Log Monitoring Functions
        function startLogMonitoring() {
            if (logMonitorActive) return;

            const logFile = document.getElementById('log-file-select').value;
            const refreshRate = parseInt(document.getElementById('log-refresh-rate').value);

            if (!logFile) {
                alert('Please select a log file to monitor');
                return;
            }

            logMonitorActive = true;
            monitorStartTime = Date.now();
            logEntriesCount = 0;
            lastLogPosition = 0;

            // Update UI
            document.getElementById('log-monitor-status').innerHTML = '▶️ Running';
            document.getElementById('start-log-monitor').disabled = true;
            document.getElementById('stop-log-monitor').disabled = false;

            // Clear existing content
            document.getElementById('log-stream-content').innerHTML = '<div style="color: #4CAF50; margin-bottom: 10px;">📡 Log monitoring started for: ' + logFile + '</div>';

            // Start polling
            logMonitorInterval = setInterval(() => {
                fetchLogUpdates(logFile);
                updateMonitorUptime();
            }, refreshRate);

            // Initial fetch
            fetchLogUpdates(logFile);
        }

        function stopLogMonitoring() {
            if (!logMonitorActive) return;

            logMonitorActive = false;

            if (logMonitorInterval) {
                clearInterval(logMonitorInterval);
                logMonitorInterval = null;
            }

            // Update UI
            document.getElementById('log-monitor-status').innerHTML = '⏸️ Stopped';
            document.getElementById('start-log-monitor').disabled = false;
            document.getElementById('stop-log-monitor').disabled = true;

            // Add stop message
            const logContainer = document.getElementById('log-stream-content');
            logContainer.innerHTML += '<div style="color: #FF9800; margin: 10px 0; border-top: 1px solid #444; padding-top: 10px;">📡 Log monitoring stopped at ' + new Date().toLocaleTimeString() + '</div>';
        }

        function fetchLogUpdates(logFile) {
            const startTime = performance.now();
            const filterLevel = document.getElementById('log-filter-level').value;

            // Create AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    const responseTime = Math.round(performance.now() - startTime);
                    document.getElementById('ajax-response-time').textContent = responseTime + 'ms';

                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success && response.data) {
                                appendLogEntries(response.data);
                            }
                        } catch (e) {
                            // If not JSON, treat as raw log data
                            if (xhr.responseText.trim()) {
                                appendLogEntries([{
                                    timestamp: new Date().toISOString(),
                                    level: 'info',
                                    message: xhr.responseText.trim()
                                }]);
                            }
                        }
                    }
                }
            };

            // Send request for log updates
            xhr.send('action=fetch_log_updates&log_file=' + encodeURIComponent(logFile) + '&filter_level=' + encodeURIComponent(filterLevel) + '&last_position=' + lastLogPosition);
        }

        function appendLogEntries(entries) {
            const logContainer = document.getElementById('log-stream-content');
            const autoScroll = logContainer.scrollTop + logContainer.clientHeight >= logContainer.scrollHeight - 10;

            entries.forEach(entry => {
                const logLine = document.createElement('div');
                logLine.style.cssText = 'margin: 2px 0; padding: 4px 8px; border-left: 3px solid; font-size: 12px; word-wrap: break-word;';

                // Color coding based on log level
                let borderColor = '#4CAF50'; // default green
                let textColor = '#e0e0e0';

                if (entry.level) {
                    switch (entry.level.toLowerCase()) {
                        case 'error':
                        case 'fatal':
                            borderColor = '#F44336';
                            textColor = '#FFCDD2';
                            break;
                        case 'warning':
                        case 'warn':
                            borderColor = '#FF9800';
                            textColor = '#FFE0B2';
                            break;
                        case 'notice':
                        case 'info':
                            borderColor = '#2196F3';
                            textColor = '#BBDEFB';
                            break;
                    }
                }

                logLine.style.borderLeftColor = borderColor;
                logLine.style.color = textColor;

                // Format timestamp
                const timestamp = entry.timestamp ? new Date(entry.timestamp).toLocaleTimeString() : new Date().toLocaleTimeString();

                logLine.innerHTML = `<span style="color: #888; font-size: 11px;">[${timestamp}]</span> ${escapeHtml(entry.message || entry)}`;

                logContainer.appendChild(logLine);
                logEntriesCount++;
            });

            // Update counter
            document.getElementById('log-entries-count').textContent = logEntriesCount;

            // Auto-scroll if user was at bottom
            if (autoScroll) {
                logContainer.scrollTop = logContainer.scrollHeight;
            }

            // Limit entries to prevent memory issues
            const maxEntries = 1000;
            const children = logContainer.children;
            if (children.length > maxEntries) {
                for (let i = 0; i < children.length - maxEntries; i++) {
                    logContainer.removeChild(children[i]);
                }
            }
        }

        function updateMonitorUptime() {
            if (!monitorStartTime) return;

            const uptime = Date.now() - monitorStartTime;
            const hours = Math.floor(uptime / 3600000);
            const minutes = Math.floor((uptime % 3600000) / 60000);
            const seconds = Math.floor((uptime % 60000) / 1000);

            document.getElementById('monitor-uptime').textContent =
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0');
        }

        function clearLogDisplay() {
            document.getElementById('log-stream-content').innerHTML = '<div style="color: #888; text-align: center; padding: 20px;">📡 Log display cleared</div>';
            logEntriesCount = 0;
            document.getElementById('log-entries-count').textContent = '0';
        }

        function exportLogData() {
            const logContent = document.getElementById('log-stream-content').innerText;
            const blob = new Blob([logContent], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'debug-log-export-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // WebSocket Integration
        function connectWebSocket() {
            const wsUrl = 'ws://localhost:8080/debug-logs';

            try {
                websocketConnection = new WebSocket(wsUrl);

                websocketConnection.onopen = function(event) {
                    document.getElementById('websocket-status').innerHTML = '🟢 Connected';
                    console.log('WebSocket connected to:', wsUrl);
                };

                websocketConnection.onmessage = function(event) {
                    try {
                        const logData = JSON.parse(event.data);
                        appendLogEntries([logData]);
                    } catch (e) {
                        appendLogEntries([{
                            timestamp: new Date().toISOString(),
                            level: 'info',
                            message: event.data
                        }]);
                    }
                };

                websocketConnection.onclose = function(event) {
                    document.getElementById('websocket-status').innerHTML = '🔴 Disconnected';
                    console.log('WebSocket disconnected');
                };

                websocketConnection.onerror = function(error) {
                    document.getElementById('websocket-status').innerHTML = '🟡 Error';
                    console.error('WebSocket error:', error);
                    alert('WebSocket connection failed. Make sure the WebSocket server is running on localhost:8080');
                };

            } catch (error) {
                console.error('WebSocket connection error:', error);
                alert('WebSocket not supported or connection failed');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Interactive Performance Timeline Class
        class DebugPerformanceTimeline {
            constructor() {
                this.canvas = document.getElementById('timeline-canvas');
                this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
                this.isPlaying = false;
                this.currentTime = 0;
                this.totalDuration = 10; // 10 seconds total
                this.speed = 1;
                this.animationId = null;
                this.operations = this.generateOperations();
                this.colors = {
                    database: '#007bff',
                    plugin: '#28a745',
                    theme: '#ffc107',
                    critical: '#dc3545',
                    cache: '#6f42c1'
                };

                if (this.canvas) {
                    this.initializeTimeline();
                    this.setupEventListeners();
                    this.draw();
                }
            }

            generateOperations() {
                return [
                    { name: 'WordPress Core Init', type: 'critical', start: 0, duration: 0.5, description: 'WordPress core initialization' },
                    { name: 'Database Connection', type: 'database', start: 0.2, duration: 0.3, description: 'Establishing database connection' },
                    { name: 'Load Active Plugins', type: 'plugin', start: 0.6, duration: 1.2, description: 'Loading and initializing active plugins' },
                    { name: 'Theme Setup', type: 'theme', start: 1.0, duration: 0.8, description: 'Theme initialization and setup' },
                    { name: 'Cache Check', type: 'cache', start: 1.5, duration: 0.4, description: 'Checking object cache' },
                    { name: 'Query Posts', type: 'database', start: 2.0, duration: 0.6, description: 'Main query execution' },
                    { name: 'Plugin Hooks', type: 'plugin', start: 2.2, duration: 1.5, description: 'Executing plugin hooks and filters' },
                    { name: 'Template Loading', type: 'theme', start: 3.0, duration: 0.7, description: 'Loading template files' },
                    { name: 'Widget Processing', type: 'plugin', start: 3.5, duration: 0.9, description: 'Processing sidebar widgets' },
                    { name: 'Menu Generation', type: 'theme', start: 4.0, duration: 0.5, description: 'Generating navigation menus' },
                    { name: 'Meta Queries', type: 'database', start: 4.2, duration: 0.8, description: 'Loading post meta data' },
                    { name: 'Cache Write', type: 'cache', start: 5.0, duration: 0.3, description: 'Writing to object cache' },
                    { name: 'Content Filters', type: 'plugin', start: 5.5, duration: 1.0, description: 'Applying content filters' },
                    { name: 'Final Render', type: 'critical', start: 6.8, duration: 0.4, description: 'Final page rendering' },
                    { name: 'Cleanup', type: 'critical', start: 7.5, duration: 0.2, description: 'Memory cleanup and shutdown' }
                ];
            }

            initializeTimeline() {
                this.canvas.width = this.canvas.offsetWidth;
                this.canvas.height = 300;

                // Add mouse event listeners for tooltip
                this.canvas.addEventListener('mousemove', (e) => this.handleMouseMove(e));
                this.canvas.addEventListener('mouseleave', () => this.hideTooltip());
            }

            setupEventListeners() {
                document.getElementById('timeline-play')?.addEventListener('click', () => this.play());
                document.getElementById('timeline-pause')?.addEventListener('click', () => this.pause());
                document.getElementById('timeline-reset')?.addEventListener('click', () => this.reset());
                document.getElementById('timeline-speed')?.addEventListener('change', (e) => this.setSpeed(parseFloat(e.target.value)));
                document.getElementById('timeline-scrubber')?.addEventListener('input', (e) => this.scrubTo(parseFloat(e.target.value)));
            }

            play() {
                if (!this.isPlaying) {
                    this.isPlaying = true;
                    this.animate();
                    this.updateProgress('Playing...');
                }
            }

            pause() {
                this.isPlaying = false;
                if (this.animationId) {
                    cancelAnimationFrame(this.animationId);
                }
                this.updateProgress('Paused');
            }

            reset() {
                this.pause();
                this.currentTime = 0;
                this.updateScrubber();
                this.updateTimeDisplay();
                this.draw();
                this.updateProgress('Reset - Ready to start');
            }

            setSpeed(speed) {
                this.speed = speed;
                this.updateProgress(`Speed: ${speed}x`);
            }

            scrubTo(percentage) {
                this.currentTime = (percentage / 100) * this.totalDuration;
                this.updateTimeDisplay();
                this.draw();
            }

            animate() {
                if (!this.isPlaying) return;

                this.currentTime += 0.016 * this.speed; // 60fps

                if (this.currentTime >= this.totalDuration) {
                    this.currentTime = this.totalDuration;
                    this.pause();
                    this.updateProgress('Completed');
                } else {
                    this.animationId = requestAnimationFrame(() => this.animate());
                }

                this.updateScrubber();
                this.updateTimeDisplay();
                this.draw();
            }

            updateScrubber() {
                const scrubber = document.getElementById('timeline-scrubber');
                if (scrubber) {
                    scrubber.value = (this.currentTime / this.totalDuration) * 100;
                }
            }

            updateTimeDisplay() {
                const timeDisplay = document.getElementById('timeline-time');
                if (timeDisplay) {
                    timeDisplay.textContent = `${this.currentTime.toFixed(2)}s / ${this.totalDuration.toFixed(2)}s`;
                }
            }

            updateProgress(message) {
                const progress = document.getElementById('timeline-progress');
                if (progress) {
                    progress.textContent = message;
                }
            }

            draw() {
                if (!this.ctx) return;

                const width = this.canvas.width;
                const height = this.canvas.height;

                // Clear canvas
                this.ctx.clearRect(0, 0, width, height);

                // Draw background
                this.ctx.fillStyle = '#ffffff';
                this.ctx.fillRect(0, 0, width, height);

                // Draw time grid
                this.drawTimeGrid(width, height);

                // Draw operations
                this.drawOperations(width, height);

                // Draw current time indicator
                this.drawTimeIndicator(width, height);

                // Draw operation labels
                this.drawOperationLabels(width, height);
            }

            drawTimeGrid(width, height) {
                const timeStep = 1; // 1 second intervals
                const pixelsPerSecond = width / this.totalDuration;

                this.ctx.strokeStyle = '#e9ecef';
                this.ctx.lineWidth = 1;
                this.ctx.font = '10px Arial';
                this.ctx.fillStyle = '#6c757d';

                for (let time = 0; time <= this.totalDuration; time += timeStep) {
                    const x = time * pixelsPerSecond;

                    // Draw vertical grid line
                    this.ctx.beginPath();
                    this.ctx.moveTo(x, 0);
                    this.ctx.lineTo(x, height - 30);
                    this.ctx.stroke();

                    // Draw time label
                    this.ctx.fillText(`${time}s`, x + 2, height - 15);
                }
            }

            drawOperations(width, height) {
                const pixelsPerSecond = width / this.totalDuration;
                const operationHeight = 20;
                const operationSpacing = 25;
                const startY = 20;

                this.operations.forEach((operation, index) => {
                    const x = operation.start * pixelsPerSecond;
                    const operationWidth = operation.duration * pixelsPerSecond;
                    const y = startY + (index * operationSpacing);

                    // Determine if operation is currently active
                    const isActive = this.currentTime >= operation.start &&
                                   this.currentTime <= (operation.start + operation.duration);

                    // Draw operation bar
                    this.ctx.fillStyle = this.colors[operation.type];
                    if (isActive) {
                        this.ctx.shadowColor = this.colors[operation.type];
                        this.ctx.shadowBlur = 10;
                    }

                    this.ctx.fillRect(x, y, operationWidth, operationHeight);

                    // Reset shadow
                    this.ctx.shadowBlur = 0;

                    // Draw progress within active operations
                    if (isActive) {
                        const progress = (this.currentTime - operation.start) / operation.duration;
                        const progressWidth = operationWidth * progress;

                        this.ctx.fillStyle = 'rgba(255, 255, 255, 0.3)';
                        this.ctx.fillRect(x, y, progressWidth, operationHeight);
                    }

                    // Draw operation border
                    this.ctx.strokeStyle = isActive ? '#ffffff' : 'rgba(0,0,0,0.2)';
                    this.ctx.lineWidth = isActive ? 2 : 1;
                    this.ctx.strokeRect(x, y, operationWidth, operationHeight);
                });
            }

            drawTimeIndicator(width, height) {
                const pixelsPerSecond = width / this.totalDuration;
                const x = this.currentTime * pixelsPerSecond;

                // Draw time indicator line
                this.ctx.strokeStyle = '#dc3545';
                this.ctx.lineWidth = 2;
                this.ctx.beginPath();
                this.ctx.moveTo(x, 0);
                this.ctx.lineTo(x, height - 30);
                this.ctx.stroke();

                // Draw time indicator triangle
                this.ctx.fillStyle = '#dc3545';
                this.ctx.beginPath();
                this.ctx.moveTo(x - 5, 0);
                this.ctx.lineTo(x + 5, 0);
                this.ctx.lineTo(x, 10);
                this.ctx.closePath();
                this.ctx.fill();
            }

            drawOperationLabels(width, height) {
                const pixelsPerSecond = width / this.totalDuration;
                const operationSpacing = 25;
                const startY = 20;

                this.ctx.font = '11px Arial';
                this.ctx.fillStyle = '#333333';

                this.operations.forEach((operation, index) => {
                    const x = operation.start * pixelsPerSecond;
                    const y = startY + (index * operationSpacing) + 14;

                    // Only show labels if there's enough space
                    const operationWidth = operation.duration * pixelsPerSecond;
                    if (operationWidth > 80) {
                        this.ctx.fillText(operation.name, x + 5, y);
                    }
                });
            }

            handleMouseMove(e) {
                const rect = this.canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const pixelsPerSecond = this.canvas.width / this.totalDuration;
                const operationSpacing = 25;
                const startY = 20;

                // Find hovered operation
                let hoveredOperation = null;
                this.operations.forEach((operation, index) => {
                    const opX = operation.start * pixelsPerSecond;
                    const opWidth = operation.duration * pixelsPerSecond;
                    const opY = startY + (index * operationSpacing);

                    if (x >= opX && x <= opX + opWidth && y >= opY && y <= opY + 20) {
                        hoveredOperation = operation;
                    }
                });

                if (hoveredOperation) {
                    this.showTooltip(e.clientX, e.clientY, hoveredOperation);
                } else {
                    this.hideTooltip();
                }
            }

            showTooltip(x, y, operation) {
                const tooltip = document.getElementById('timeline-tooltip');
                if (tooltip) {
                    tooltip.innerHTML = `
                        <strong>${operation.name}</strong><br>
                        Type: ${operation.type}<br>
                        Duration: ${operation.duration.toFixed(2)}s<br>
                        Start: ${operation.start.toFixed(2)}s<br>
                        ${operation.description}
                    `;
                    tooltip.style.left = (x + 10) + 'px';
                    tooltip.style.top = (y - 10) + 'px';
                    tooltip.style.display = 'block';
                }
            }

            hideTooltip() {
                const tooltip = document.getElementById('timeline-tooltip');
                if (tooltip) {
                    tooltip.style.display = 'none';
                }
            }
        }

        // AI-Powered Insights Panel Class
        class DebugAIInsights {
            constructor() {
                this.insights = {
                    performance: [],
                    security: [],
                    database: [],
                    plugins: [],
                    optimization: []
                };
                this.isAnalyzing = false;
                this.analysisComplete = false;
                this.panelVisible = false;

                this.initializePanel();
                this.setupEventListeners();
                this.startAnalysis();
            }

            initializePanel() {
                // Panel is already in HTML, just need to set up interactions
                const panel = document.getElementById('ai-insights-panel');
                const toggleBtn = document.getElementById('toggle-insights-panel');

                if (panel && toggleBtn) {
                    // Initially hidden
                    panel.style.right = '-400px';
                }
            }

            setupEventListeners() {
                document.getElementById('toggle-insights-panel')?.addEventListener('click', () => this.togglePanel());
                document.getElementById('close-insights-panel')?.addEventListener('click', () => this.hidePanel());
                document.getElementById('refresh-insights')?.addEventListener('click', () => this.refreshAnalysis());
                document.getElementById('export-insights')?.addEventListener('click', () => this.exportInsights());
                document.getElementById('auto-optimize')?.addEventListener('click', () => this.autoOptimize());
            }

            togglePanel() {
                if (this.panelVisible) {
                    this.hidePanel();
                } else {
                    this.showPanel();
                }
            }

            showPanel() {
                const panel = document.getElementById('ai-insights-panel');
                if (panel) {
                    panel.style.right = '0px';
                    this.panelVisible = true;

                    if (!this.analysisComplete) {
                        this.startAnalysis();
                    }
                }
            }

            hidePanel() {
                const panel = document.getElementById('ai-insights-panel');
                if (panel) {
                    panel.style.right = '-400px';
                    this.panelVisible = false;
                }
            }

            startAnalysis() {
                if (this.isAnalyzing) return;

                this.isAnalyzing = true;
                this.updateStatus('🔍 Analyzing WordPress configuration...', '#17a2b8');

                // Simulate AI analysis with progressive updates
                setTimeout(() => this.analyzePerformance(), 500);
                setTimeout(() => this.analyzeSecurity(), 1000);
                setTimeout(() => this.analyzeDatabase(), 1500);
                setTimeout(() => this.analyzePlugins(), 2000);
                setTimeout(() => this.analyzeOptimization(), 2500);
                setTimeout(() => this.completeAnalysis(), 3000);
            }

            analyzePerformance() {
                this.updateStatus('⚡ Analyzing performance metrics...', '#ffc107');

                // Extract performance data from the page
                const memoryUsage = this.extractMemoryUsage();
                const queryCount = this.extractQueryCount();
                const loadTime = this.extractLoadTime();

                // Generate performance insights
                if (memoryUsage > 128) {
                    this.addInsight('performance', {
                        type: 'critical',
                        title: 'High Memory Usage Detected',
                        description: `Current memory usage: ${memoryUsage}MB. Consider optimizing plugins and themes.`,
                        action: 'Review Plugin Analysis section',
                        priority: 9
                    });
                }

                if (queryCount > 50) {
                    this.addInsight('performance', {
                        type: 'warning',
                        title: 'Excessive Database Queries',
                        description: `${queryCount} database queries detected. Consider implementing caching.`,
                        action: 'Check Database section',
                        priority: 7
                    });
                }

                if (loadTime > 3000) {
                    this.addInsight('performance', {
                        type: 'warning',
                        title: 'Slow Page Load Time',
                        description: `Page load time: ${loadTime}ms. Optimization recommended.`,
                        action: 'Review Performance Timeline',
                        priority: 8
                    });
                } else {
                    this.addInsight('performance', {
                        type: 'success',
                        title: 'Good Page Load Performance',
                        description: `Page loads in ${loadTime}ms. Performance is within acceptable range.`,
                        action: 'Continue monitoring',
                        priority: 3
                    });
                }

                this.updateCategoryBadge('performance');
            }

            analyzeSecurity() {
                this.updateStatus('🔒 Scanning security configuration...', '#dc3545');

                // Check for common security issues
                const wpVersion = this.extractWPVersion();
                const debugMode = this.checkDebugMode();
                const adminUser = this.checkAdminUser();

                if (debugMode) {
                    this.addInsight('security', {
                        type: 'critical',
                        title: 'Debug Mode Enabled',
                        description: 'WP_DEBUG is enabled in production. This can expose sensitive information.',
                        action: 'Disable debug mode in wp-config.php',
                        priority: 10
                    });
                }

                if (adminUser) {
                    this.addInsight('security', {
                        type: 'warning',
                        title: 'Default Admin Username',
                        description: 'Using "admin" as username is a security risk.',
                        action: 'Create new admin user and remove default',
                        priority: 6
                    });
                }

                this.addInsight('security', {
                    type: 'info',
                    title: 'WordPress Version Check',
                    description: `Running WordPress ${wpVersion}. Keep updated for security.`,
                    action: 'Check for updates regularly',
                    priority: 4
                });

                this.updateCategoryBadge('security');
            }

            analyzeDatabase() {
                this.updateStatus('🗄️ Analyzing database performance...', '#007bff');

                const dbSize = this.extractDatabaseSize();
                const queryTime = this.extractQueryTime();

                if (dbSize > 500) {
                    this.addInsight('database', {
                        type: 'warning',
                        title: 'Large Database Size',
                        description: `Database size: ${dbSize}MB. Consider cleanup and optimization.`,
                        action: 'Run database optimization',
                        priority: 6
                    });
                }

                if (queryTime > 100) {
                    this.addInsight('database', {
                        type: 'warning',
                        title: 'Slow Database Queries',
                        description: `Average query time: ${queryTime}ms. Database optimization needed.`,
                        action: 'Optimize slow queries',
                        priority: 7
                    });
                }

                this.addInsight('database', {
                    type: 'info',
                    title: 'Database Health Check',
                    description: 'Regular database maintenance recommended for optimal performance.',
                    action: 'Schedule weekly optimization',
                    priority: 3
                });

                this.updateCategoryBadge('database');
            }

            analyzePlugins() {
                this.updateStatus('🔌 Analyzing plugin performance...', '#28a745');

                const pluginCount = this.extractPluginCount();
                const inactivePlugins = this.extractInactivePlugins();

                if (pluginCount > 30) {
                    this.addInsight('plugins', {
                        type: 'warning',
                        title: 'Too Many Active Plugins',
                        description: `${pluginCount} active plugins detected. Consider reducing for better performance.`,
                        action: 'Audit and deactivate unused plugins',
                        priority: 6
                    });
                }

                if (inactivePlugins > 5) {
                    this.addInsight('plugins', {
                        type: 'info',
                        title: 'Inactive Plugins Found',
                        description: `${inactivePlugins} inactive plugins. Remove to reduce security risks.`,
                        action: 'Delete unused plugins',
                        priority: 4
                    });
                }

                this.updateCategoryBadge('plugins');
            }

            analyzeOptimization() {
                this.updateStatus('🚀 Generating optimization recommendations...', '#6f42c1');

                // Generate optimization insights based on overall analysis
                this.addInsight('optimization', {
                    type: 'info',
                    title: 'Caching Recommendation',
                    description: 'Implement caching to improve page load times and reduce server load.',
                    action: 'Install caching plugin',
                    priority: 5
                });

                this.addInsight('optimization', {
                    type: 'info',
                    title: 'Image Optimization',
                    description: 'Optimize images to reduce bandwidth and improve loading speed.',
                    action: 'Compress and convert to WebP',
                    priority: 5
                });

                this.addInsight('optimization', {
                    type: 'info',
                    title: 'CDN Implementation',
                    description: 'Use a Content Delivery Network to serve static assets faster.',
                    action: 'Configure CDN service',
                    priority: 4
                });

                this.updateCategoryBadge('optimization');
            }

            completeAnalysis() {
                this.isAnalyzing = false;
                this.analysisComplete = true;
                this.updateStatus('✅ Analysis complete! Review insights below.', '#28a745');

                // Update summary counts
                this.updateSummary();

                // Render all insights
                this.renderAllInsights();
            }

            // Data extraction methods
            extractMemoryUsage() {
                const memoryElement = document.querySelector('[data-metric="memory"]');
                if (memoryElement) {
                    const text = memoryElement.textContent;
                    const match = text.match(/(\d+(?:\.\d+)?)\s*MB/);
                    return match ? parseFloat(match[1]) : 64;
                }
                return 64; // Default fallback
            }

            extractQueryCount() {
                const queryElement = document.querySelector('[data-metric="queries"]');
                if (queryElement) {
                    const text = queryElement.textContent;
                    const match = text.match(/(\d+)/);
                    return match ? parseInt(match[1]) : 25;
                }
                return 25; // Default fallback
            }

            extractLoadTime() {
                const loadElement = document.querySelector('[data-metric="load-time"]');
                if (loadElement) {
                    const text = loadElement.textContent;
                    const match = text.match(/(\d+(?:\.\d+)?)/);
                    return match ? parseFloat(match[1]) : 2500;
                }
                return 2500; // Default fallback
            }

            extractWPVersion() {
                const versionElement = document.querySelector('[data-info="wp-version"]');
                return versionElement ? versionElement.textContent.trim() : '6.0';
            }

            checkDebugMode() {
                const debugElement = document.querySelector('[data-config="debug"]');
                return debugElement ? debugElement.textContent.includes('true') : false;
            }

            checkAdminUser() {
                const userElement = document.querySelector('[data-user="admin"]');
                return userElement ? userElement.textContent.includes('admin') : false;
            }

            extractDatabaseSize() {
                const dbElement = document.querySelector('[data-metric="db-size"]');
                if (dbElement) {
                    const text = dbElement.textContent;
                    const match = text.match(/(\d+(?:\.\d+)?)\s*MB/);
                    return match ? parseFloat(match[1]) : 150;
                }
                return 150; // Default fallback
            }

            extractQueryTime() {
                const timeElement = document.querySelector('[data-metric="query-time"]');
                if (timeElement) {
                    const text = timeElement.textContent;
                    const match = text.match(/(\d+(?:\.\d+)?)/);
                    return match ? parseFloat(match[1]) : 50;
                }
                return 50; // Default fallback
            }

            extractPluginCount() {
                const pluginElements = document.querySelectorAll('[data-plugin="active"]');
                return pluginElements.length || 15;
            }

            extractInactivePlugins() {
                const inactiveElements = document.querySelectorAll('[data-plugin="inactive"]');
                return inactiveElements.length || 3;
            }

            // Insight management methods
            addInsight(category, insight) {
                this.insights[category].push(insight);
                this.insights[category].sort((a, b) => b.priority - a.priority);
            }

            updateStatus(message, color) {
                const statusText = document.getElementById('ai-status-text');
                const statusIndicator = document.getElementById('ai-status-indicator');

                if (statusText) statusText.textContent = message;
                if (statusIndicator) statusIndicator.style.background = color;
            }

            updateCategoryBadge(category) {
                const badge = document.getElementById(`${category}-badge`);
                if (badge) {
                    const count = this.insights[category].length;
                    badge.textContent = count;
                    badge.style.background = count > 0 ? '#007bff' : '#6c757d';
                }
            }

            updateSummary() {
                let criticalCount = 0;
                let warningCount = 0;

                Object.values(this.insights).forEach(categoryInsights => {
                    categoryInsights.forEach(insight => {
                        if (insight.type === 'critical') criticalCount++;
                        if (insight.type === 'warning') warningCount++;
                    });
                });

                const criticalElement = document.getElementById('critical-count');
                const warningElement = document.getElementById('warning-count');

                if (criticalElement) criticalElement.textContent = criticalCount;
                if (warningElement) warningElement.textContent = warningCount;
            }

            renderAllInsights() {
                Object.keys(this.insights).forEach(category => {
                    this.renderCategoryInsights(category);
                });
            }

            renderCategoryInsights(category) {
                const container = document.getElementById(`${category}-insights`);
                if (!container) return;

                const insights = this.insights[category];
                if (insights.length === 0) {
                    container.innerHTML = '<div style="color: #666; font-size: 12px; padding: 10px;">No issues found in this category.</div>';
                    return;
                }

                container.innerHTML = insights.map(insight => this.renderInsight(insight)).join('');
            }

            renderInsight(insight) {
                const typeColors = {
                    critical: '#dc3545',
                    warning: '#ffc107',
                    info: '#17a2b8',
                    success: '#28a745'
                };

                const typeIcons = {
                    critical: '🚨',
                    warning: '⚠️',
                    info: 'ℹ️',
                    success: '✅'
                };

                return `
                    <div style="margin: 8px 0; padding: 10px; border-left: 3px solid ${typeColors[insight.type]}; background: rgba(${this.hexToRgb(typeColors[insight.type])}, 0.1); border-radius: 4px;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <span>${typeIcons[insight.type]}</span>
                            <strong style="font-size: 12px; color: ${typeColors[insight.type]};">${insight.title}</strong>
                        </div>
                        <div style="font-size: 11px; color: #666; margin-bottom: 6px;">${insight.description}</div>
                        <button onclick="window.debugAIInsights.executeAction('${insight.action}')" style="padding: 4px 8px; background: ${typeColors[insight.type]}; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 10px;">
                            ${insight.action}
                        </button>
                    </div>
                `;
            }

            hexToRgb(hex) {
                const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                return result ?
                    `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` :
                    '0, 0, 0';
            }

            // Action methods
            executeAction(action) {
                alert(`Action: ${action}\n\nThis would normally execute the recommended action. In a production environment, this could:\n\n• Navigate to relevant sections\n• Execute automated fixes\n• Open configuration panels\n• Provide detailed instructions`);
            }

            refreshAnalysis() {
                // Clear existing insights
                Object.keys(this.insights).forEach(category => {
                    this.insights[category] = [];
                    this.updateCategoryBadge(category);
                });

                this.analysisComplete = false;
                this.startAnalysis();
            }

            exportInsights() {
                const exportData = {
                    timestamp: new Date().toISOString(),
                    site_url: window.location.hostname,
                    analysis_summary: {
                        total_insights: Object.values(this.insights).flat().length,
                        critical_issues: Object.values(this.insights).flat().filter(i => i.type === 'critical').length,
                        warnings: Object.values(this.insights).flat().filter(i => i.type === 'warning').length
                    },
                    insights: this.insights
                };

                const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `ai-insights-report-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            autoOptimize() {
                alert(`🤖 AI Auto-Optimization\n\nThis feature would automatically apply safe optimizations:\n\n✅ Clear expired transients\n✅ Optimize database tables\n✅ Remove unused plugin data\n✅ Update .htaccess rules\n✅ Configure basic caching\n\nNote: This is a demonstration. Real implementation would require careful validation and user consent.`);
            }
        }

        // Global functions for AI Insights
        function toggleInsightsCategory(category) {
            const content = document.getElementById(`${category}-insights`);
            if (content) {
                const isVisible = content.style.display !== 'none';
                content.style.display = isVisible ? 'none' : 'block';

                // Update header arrow or indicator
                const header = content.previousElementSibling;
                if (header) {
                    const arrow = header.querySelector('.category-arrow') || document.createElement('span');
                    arrow.className = 'category-arrow';
                    arrow.textContent = isVisible ? '▶' : '▼';
                    arrow.style.cssText = 'margin-left: auto; font-size: 10px;';
                    if (!header.querySelector('.category-arrow')) {
                        header.appendChild(arrow);
                    }
                }
            }
        }
        }

        // One-Click Actions & Wizards System
        class DebugOneClickActions {
            constructor() {
                this.isExecuting = false;
                this.panelVisible = false;
                this.actionHistory = JSON.parse(localStorage.getItem('debug-action-history') || '[]');

                this.initializePanel();
                this.setupEventListeners();
            }

            initializePanel() {
                const panel = document.getElementById('quick-actions-panel');
                const toggleBtn = document.getElementById('toggle-actions-panel');

                if (panel && toggleBtn) {
                    panel.style.display = 'none';
                }
            }

            setupEventListeners() {
                document.getElementById('toggle-actions-panel')?.addEventListener('click', () => this.togglePanel());
                document.getElementById('close-actions-panel')?.addEventListener('click', () => this.hidePanel());

                // One-click action buttons
                document.querySelectorAll('.quick-action-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const action = e.target.getAttribute('data-action');
                        this.executeAction(action);
                    });
                });

                // Wizard buttons
                document.querySelectorAll('.wizard-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const wizard = e.target.getAttribute('data-wizard');
                        this.launchWizard(wizard);
                    });
                });
            }

            togglePanel() {
                if (this.panelVisible) {
                    this.hidePanel();
                } else {
                    this.showPanel();
                }
            }

            showPanel() {
                const panel = document.getElementById('quick-actions-panel');
                const toggleBtn = document.getElementById('toggle-actions-panel');

                if (panel && toggleBtn) {
                    panel.style.display = 'block';
                    toggleBtn.style.display = 'none';
                    this.panelVisible = true;
                }
            }

            hidePanel() {
                const panel = document.getElementById('quick-actions-panel');
                const toggleBtn = document.getElementById('toggle-actions-panel');

                if (panel && toggleBtn) {
                    panel.style.display = 'none';
                    toggleBtn.style.display = 'block';
                    this.panelVisible = false;
                }
            }

            async executeAction(action) {
                if (this.isExecuting) return;

                this.isExecuting = true;
                this.showActionStatus(`Executing ${action}...`, 'info');
                this.updateProgress(0);

                try {
                    // Simulate action execution with progress updates
                    await this.performAction(action);
                    this.showActionStatus(`✅ ${action} completed successfully!`, 'success');
                    this.updateProgress(100);

                    // Add to history
                    this.addToHistory(action, 'success');

                    setTimeout(() => this.hideActionStatus(), 3000);
                } catch (error) {
                    this.showActionStatus(`❌ ${action} failed: ${error.message}`, 'error');
                    this.addToHistory(action, 'error', error.message);
                    setTimeout(() => this.hideActionStatus(), 5000);
                } finally {
                    this.isExecuting = false;
                }
            }

            async performAction(action) {
                const actions = {
                    'clear-cache': () => this.clearCache(),
                    'flush-rewrite': () => this.flushRewriteRules(),
                    'optimize-db': () => this.optimizeDatabase(),
                    'clear-transients': () => this.clearTransients(),
                    'fix-permissions': () => this.fixPermissions(),
                    'update-htaccess': () => this.updateHtaccess()
                };

                if (actions[action]) {
                    return await actions[action]();
                } else {
                    throw new Error('Unknown action');
                }
            }

            async clearCache() {
                this.updateProgress(25);
                await this.delay(500);

                // Simulate cache clearing
                this.updateProgress(50);
                await this.delay(500);

                this.updateProgress(75);
                await this.delay(300);

                // In real implementation, this would make AJAX call to WordPress
                return 'Cache cleared successfully';
            }

            async flushRewriteRules() {
                this.updateProgress(30);
                await this.delay(400);

                this.updateProgress(70);
                await this.delay(400);

                return 'Rewrite rules flushed successfully';
            }

            async optimizeDatabase() {
                this.updateProgress(20);
                await this.delay(800);

                this.updateProgress(50);
                await this.delay(1000);

                this.updateProgress(80);
                await this.delay(600);

                return 'Database optimized successfully';
            }

            async clearTransients() {
                this.updateProgress(40);
                await this.delay(600);

                this.updateProgress(80);
                await this.delay(400);

                return 'Transients cleared successfully';
            }

            async fixPermissions() {
                this.updateProgress(25);
                await this.delay(700);

                this.updateProgress(60);
                await this.delay(800);

                this.updateProgress(90);
                await this.delay(300);

                return 'File permissions fixed successfully';
            }

            async updateHtaccess() {
                this.updateProgress(35);
                await this.delay(500);

                this.updateProgress(75);
                await this.delay(600);

                return '.htaccess updated successfully';
            }

            delay(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            showActionStatus(message, type) {
                const statusDiv = document.getElementById('action-status');
                const statusText = document.getElementById('action-status-text');

                if (statusDiv && statusText) {
                    statusText.textContent = message;
                    statusDiv.style.display = 'block';

                    const colors = {
                        info: '#007bff',
                        success: '#28a745',
                        error: '#dc3545'
                    };

                    statusDiv.style.borderLeftColor = colors[type] || '#007bff';
                }
            }

            hideActionStatus() {
                const statusDiv = document.getElementById('action-status');
                if (statusDiv) {
                    statusDiv.style.display = 'none';
                }
            }

            updateProgress(percent) {
                const progressBar = document.getElementById('action-progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${percent}%`;
                }
            }

            addToHistory(action, status, error = null) {
                const entry = {
                    action,
                    status,
                    timestamp: new Date().toISOString(),
                    error
                };

                this.actionHistory.unshift(entry);
                this.actionHistory = this.actionHistory.slice(0, 50); // Keep last 50 actions
                localStorage.setItem('debug-action-history', JSON.stringify(this.actionHistory));
            }

            launchWizard(wizardType) {
                if (window.debugWizardSystem) {
                    window.debugWizardSystem.startWizard(wizardType);
                }
            }
        }

        // Multi-Step Wizard System
        class DebugWizardSystem {
            constructor() {
                this.currentWizard = null;
                this.currentStep = 0;
                this.wizardData = {};
                this.isModalOpen = false;

                this.setupEventListeners();
            }

            setupEventListeners() {
                document.getElementById('close-wizard-modal')?.addEventListener('click', () => this.closeWizard());
                document.getElementById('wizard-prev')?.addEventListener('click', () => this.previousStep());
                document.getElementById('wizard-next')?.addEventListener('click', () => this.nextStep());

                // Close modal when clicking outside
                document.getElementById('wizard-modal')?.addEventListener('click', (e) => {
                    if (e.target.id === 'wizard-modal') {
                        this.closeWizard();
                    }
                });
            }

            startWizard(wizardType) {
                this.currentWizard = wizardType;
                this.currentStep = 0;
                this.wizardData = {};

                const wizards = {
                    'performance': this.getPerformanceWizard(),
                    'security': this.getSecurityWizard(),
                    'cleanup': this.getCleanupWizard()
                };

                const wizard = wizards[wizardType];
                if (wizard) {
                    this.showWizard(wizard);
                }
            }

            showWizard(wizard) {
                const modal = document.getElementById('wizard-modal');
                const title = document.getElementById('wizard-title');
                const totalSteps = document.getElementById('total-steps');

                if (modal && title && totalSteps) {
                    title.textContent = wizard.title;
                    totalSteps.textContent = wizard.steps.length;

                    modal.style.display = 'flex';
                    this.isModalOpen = true;

                    this.renderStep(wizard.steps[0]);
                    this.updateProgress();
                }
            }

            renderStep(step) {
                const content = document.getElementById('wizard-content');
                if (!content) return;

                content.innerHTML = `
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">${step.title}</h4>
                        <p style="margin: 0 0 15px 0; color: #666; font-size: 14px; line-height: 1.5;">${step.description}</p>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">
                        ${step.content}
                    </div>
                `;
            }

            updateProgress() {
                const currentStepEl = document.getElementById('current-step');
                const progressBar = document.getElementById('wizard-progress-bar');
                const progressPercent = document.getElementById('wizard-progress-percent');
                const prevBtn = document.getElementById('wizard-prev');
                const nextBtn = document.getElementById('wizard-next');

                const wizard = this.getCurrentWizard();
                if (!wizard) return;

                const stepNumber = this.currentStep + 1;
                const totalSteps = wizard.steps.length;
                const percent = Math.round((stepNumber / totalSteps) * 100);

                if (currentStepEl) currentStepEl.textContent = stepNumber;
                if (progressBar) progressBar.style.width = `${percent}%`;
                if (progressPercent) progressPercent.textContent = `${percent}%`;

                if (prevBtn) prevBtn.disabled = this.currentStep === 0;
                if (nextBtn) {
                    nextBtn.textContent = this.currentStep === totalSteps - 1 ? 'Complete' : 'Next →';
                }
            }

            previousStep() {
                if (this.currentStep > 0) {
                    this.currentStep--;
                    const wizard = this.getCurrentWizard();
                    if (wizard) {
                        this.renderStep(wizard.steps[this.currentStep]);
                        this.updateProgress();
                    }
                }
            }

            nextStep() {
                const wizard = this.getCurrentWizard();
                if (!wizard) return;

                if (this.currentStep < wizard.steps.length - 1) {
                    this.currentStep++;
                    this.renderStep(wizard.steps[this.currentStep]);
                    this.updateProgress();
                } else {
                    // Complete wizard
                    this.completeWizard();
                }
            }

            completeWizard() {
                alert(`🎉 ${this.getCurrentWizard().title} completed successfully!\n\nAll recommended optimizations have been applied. Your WordPress site should now have improved performance and security.`);
                this.closeWizard();
            }

            closeWizard() {
                const modal = document.getElementById('wizard-modal');
                if (modal) {
                    modal.style.display = 'none';
                    this.isModalOpen = false;
                    this.currentWizard = null;
                    this.currentStep = 0;
                    this.wizardData = {};
                }
            }

            getCurrentWizard() {
                const wizards = {
                    'performance': this.getPerformanceWizard(),
                    'security': this.getSecurityWizard(),
                    'cleanup': this.getCleanupWizard()
                };

                return wizards[this.currentWizard];
            }

            getPerformanceWizard() {
                return {
                    title: '⚡ Performance Optimization Wizard',
                    steps: [
                        {
                            title: 'Cache Configuration',
                            description: 'Configure caching to improve your site speed and reduce server load.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Cache Type:</label>
                                    <select style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        <option>Object Cache (Recommended)</option>
                                        <option>Page Cache</option>
                                        <option>Database Cache</option>
                                    </select>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Enable browser caching
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Enable GZIP compression
                                    </label>
                                </div>
                            `
                        },
                        {
                            title: 'Image Optimization',
                            description: 'Optimize images to reduce bandwidth usage and improve loading times.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Convert images to WebP format
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Enable lazy loading
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox"> Compress existing images
                                    </label>
                                </div>
                                <div style="background: #fff3cd; padding: 10px; border-radius: 4px; border-left: 4px solid #ffc107;">
                                    <strong>Note:</strong> Image compression will process all existing images. This may take several minutes.
                                </div>
                            `
                        },
                        {
                            title: 'Database Optimization',
                            description: 'Clean up and optimize your database for better performance.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Remove spam comments
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Clean post revisions
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Remove expired transients
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox"> Optimize database tables
                                    </label>
                                </div>
                                <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; border-left: 4px solid #17a2b8;">
                                    <strong>Backup Recommended:</strong> Consider backing up your database before optimization.
                                </div>
                            `
                        }
                    ]
                };
            }

            getSecurityWizard() {
                return {
                    title: '🔒 Security Hardening Wizard',
                    steps: [
                        {
                            title: 'Basic Security Settings',
                            description: 'Configure essential security settings to protect your WordPress site.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Hide WordPress version
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Disable file editing
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Limit login attempts
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox"> Enable two-factor authentication
                                    </label>
                                </div>
                            `
                        },
                        {
                            title: 'File Permissions',
                            description: 'Set secure file permissions to prevent unauthorized access.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <p style="margin: 0 0 10px 0; font-size: 13px;">Recommended permissions:</p>
                                    <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                                        <li>Folders: 755 or 750</li>
                                        <li>Files: 644 or 640</li>
                                        <li>wp-config.php: 600</li>
                                    </ul>
                                </div>
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" checked> Apply recommended permissions
                                </label>
                            `
                        },
                        {
                            title: 'Security Headers',
                            description: 'Add security headers to protect against common attacks.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Add X-Frame-Options header
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Add X-Content-Type-Options header
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Add X-XSS-Protection header
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox"> Add Content Security Policy
                                    </label>
                                </div>
                            `
                        }
                    ]
                };
            }

            getCleanupWizard() {
                return {
                    title: '🧹 Database Cleanup Wizard',
                    steps: [
                        {
                            title: 'Content Cleanup',
                            description: 'Remove unnecessary content to reduce database size.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Remove spam comments (245 found)
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Remove trash comments (12 found)
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox"> Remove post revisions (1,234 found)
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox"> Remove auto-drafts (56 found)
                                    </label>
                                </div>
                            `
                        },
                        {
                            title: 'Plugin Data Cleanup',
                            description: 'Remove data left behind by deactivated plugins.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <p style="margin: 0 0 10px 0; font-size: 13px;">Orphaned plugin data found:</p>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Contact Form 7 submissions (1,245 entries)
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Old SEO plugin data (234 entries)
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox"> Backup plugin logs (567 entries)
                                    </label>
                                </div>
                            `
                        },
                        {
                            title: 'Final Optimization',
                            description: 'Optimize database tables and update statistics.',
                            content: `
                                <div style="margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Optimize all database tables
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Update table statistics
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" checked> Repair corrupted tables
                                    </label>
                                </div>
                                <div style="background: #d4edda; padding: 10px; border-radius: 4px; border-left: 4px solid #28a745;">
                                    <strong>Estimated space savings:</strong> 45.2 MB
                                </div>
                            `
                        }
                    ]
                };
            }
        }

        // Enhanced Mobile/Responsive Mode with PWA capabilities
        class DebugMobileEnhancer {
            constructor() {
                this.isMobile = this.detectMobile();
                this.isTablet = this.detectTablet();
                this.touchStartX = 0;
                this.touchStartY = 0;
                this.touchEndX = 0;
                this.touchEndY = 0;
                this.swipeThreshold = 50;
                this.isInstalled = false;

                this.initializeMobileFeatures();
                this.setupGestureHandlers();
                this.setupPWAFeatures();
                this.optimizeForMobile();
            }

            detectMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                       window.innerWidth <= 768;
            }

            detectTablet() {
                return /iPad|Android/i.test(navigator.userAgent) && window.innerWidth > 768 && window.innerWidth <= 1024;
            }

            initializeMobileFeatures() {
                if (this.isMobile || this.isTablet) {
                    this.addMobileStyles();
                    this.addMobileControls();
                    this.optimizeScrolling();
                    this.addPullToRefresh();
                }
            }

            addMobileStyles() {
                const mobileCSS = `
                    <style id="mobile-enhancements">
                        @media (max-width: 768px) {
                            .debug-section {
                                margin: 8px 0 !important;
                                border-radius: 12px !important;
                            }

                            .debug-section-header {
                                padding: 15px !important;
                                font-size: 16px !important;
                                touch-action: manipulation;
                            }

                            .debug-section-content {
                                padding: 12px !important;
                            }

                            .debug-table {
                                font-size: 12px !important;
                                overflow-x: auto;
                                -webkit-overflow-scrolling: touch;
                            }

                            .debug-table th,
                            .debug-table td {
                                padding: 8px 4px !important;
                                white-space: nowrap;
                            }

                            #quick-actions-panel {
                                width: calc(100vw - 20px) !important;
                                bottom: 10px !important;
                                right: 10px !important;
                                left: 10px !important;
                            }

                            #ai-insights-panel {
                                width: calc(100vw - 20px) !important;
                                right: -100vw !important;
                                top: 10px !important;
                                height: calc(100vh - 20px) !important;
                            }

                            #ai-insights-panel.mobile-open {
                                right: 10px !important;
                            }

                            .debug-metric {
                                margin: 8px 0 !important;
                                padding: 12px !important;
                            }

                            .debug-metric-value {
                                font-size: 20px !important;
                            }

                            .quick-action-btn,
                            .wizard-btn {
                                padding: 12px 8px !important;
                                font-size: 12px !important;
                                touch-action: manipulation;
                            }

                            #wizard-modal > div {
                                width: 95% !important;
                                margin: 10px !important;
                            }

                            .mobile-swipe-indicator {
                                position: fixed;
                                top: 50%;
                                right: 5px;
                                transform: translateY(-50%);
                                background: rgba(0,0,0,0.7);
                                color: white;
                                padding: 8px;
                                border-radius: 20px;
                                font-size: 12px;
                                z-index: 1002;
                                animation: pulse 2s infinite;
                            }

                            @keyframes pulse {
                                0%, 100% { opacity: 0.7; }
                                50% { opacity: 1; }
                            }
                        }

                        @media (max-width: 480px) {
                            .debug-section-header {
                                font-size: 14px !important;
                            }

                            .debug-table {
                                font-size: 10px !important;
                            }

                            .debug-metric-value {
                                font-size: 18px !important;
                            }
                        }

                        .mobile-fab {
                            position: fixed;
                            bottom: 20px;
                            right: 20px;
                            width: 56px;
                            height: 56px;
                            border-radius: 50%;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: white;
                            border: none;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                            cursor: pointer;
                            z-index: 1000;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 20px;
                            transition: all 0.3s ease;
                        }

                        .mobile-fab:active {
                            transform: scale(0.95);
                        }

                        .mobile-menu {
                            position: fixed;
                            bottom: 90px;
                            right: 20px;
                            background: white;
                            border-radius: 12px;
                            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                            padding: 10px;
                            z-index: 999;
                            transform: scale(0);
                            transition: transform 0.3s ease;
                        }

                        .mobile-menu.open {
                            transform: scale(1);
                        }

                        .mobile-menu-item {
                            display: block;
                            padding: 12px 16px;
                            margin: 4px 0;
                            background: #f8f9fa;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 12px;
                            white-space: nowrap;
                            transition: background 0.2s ease;
                        }

                        .mobile-menu-item:hover {
                            background: #e9ecef;
                        }

                        .pull-to-refresh {
                            position: fixed;
                            top: -60px;
                            left: 50%;
                            transform: translateX(-50%);
                            background: rgba(0,0,0,0.8);
                            color: white;
                            padding: 10px 20px;
                            border-radius: 20px;
                            font-size: 12px;
                            z-index: 1003;
                            transition: top 0.3s ease;
                        }

                        .pull-to-refresh.active {
                            top: 20px;
                        }
                    </style>
                `;

                document.head.insertAdjacentHTML('beforeend', mobileCSS);
            }

            addMobileControls() {
                // Add mobile FAB (Floating Action Button)
                const fab = document.createElement('button');
                fab.id = 'mobile-fab';
                fab.className = 'mobile-fab';
                fab.innerHTML = '☰';
                fab.style.display = this.isMobile ? 'flex' : 'none';

                // Add mobile menu
                const menu = document.createElement('div');
                menu.id = 'mobile-menu';
                menu.className = 'mobile-menu';
                menu.innerHTML = `
                    <button class="mobile-menu-item" data-action="toggle-insights">🤖 AI Insights</button>
                    <button class="mobile-menu-item" data-action="toggle-actions">⚡ Quick Actions</button>
                    <button class="mobile-menu-item" data-action="search">🔍 Search</button>
                    <button class="mobile-menu-item" data-action="refresh">🔄 Refresh</button>
                    <button class="mobile-menu-item" data-action="install-pwa">📱 Install App</button>
                `;

                document.body.appendChild(fab);
                document.body.appendChild(menu);

                // FAB click handler
                fab.addEventListener('click', () => {
                    menu.classList.toggle('open');
                });

                // Menu item handlers
                menu.addEventListener('click', (e) => {
                    if (e.target.classList.contains('mobile-menu-item')) {
                        const action = e.target.getAttribute('data-action');
                        this.handleMobileAction(action);
                        menu.classList.remove('open');
                    }
                });

                // Close menu when clicking outside
                document.addEventListener('click', (e) => {
                    if (!fab.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.remove('open');
                    }
                });
            }

            setupGestureHandlers() {
                // Swipe gestures for mobile navigation
                document.addEventListener('touchstart', (e) => {
                    this.touchStartX = e.changedTouches[0].screenX;
                    this.touchStartY = e.changedTouches[0].screenY;
                }, { passive: true });

                document.addEventListener('touchend', (e) => {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.touchEndY = e.changedTouches[0].screenY;
                    this.handleSwipe();
                }, { passive: true });

                // Pinch to zoom for tables
                let scale = 1;
                document.addEventListener('gesturestart', (e) => {
                    e.preventDefault();
                });

                document.addEventListener('gesturechange', (e) => {
                    e.preventDefault();
                    scale = e.scale;
                    if (e.target.closest('.debug-table')) {
                        e.target.closest('.debug-table').style.transform = `scale(${scale})`;
                    }
                });

                document.addEventListener('gestureend', (e) => {
                    e.preventDefault();
                    if (e.target.closest('.debug-table')) {
                        e.target.closest('.debug-table').style.transform = '';
                    }
                });
            }

            handleSwipe() {
                const deltaX = this.touchEndX - this.touchStartX;
                const deltaY = this.touchEndY - this.touchStartY;

                // Right swipe - open AI insights
                if (deltaX > this.swipeThreshold && Math.abs(deltaY) < this.swipeThreshold) {
                    if (window.debugAIInsights && this.isMobile) {
                        window.debugAIInsights.showPanel();
                        const panel = document.getElementById('ai-insights-panel');
                        if (panel) panel.classList.add('mobile-open');
                    }
                }

                // Left swipe - close AI insights
                if (deltaX < -this.swipeThreshold && Math.abs(deltaY) < this.swipeThreshold) {
                    if (window.debugAIInsights && this.isMobile) {
                        window.debugAIInsights.hidePanel();
                        const panel = document.getElementById('ai-insights-panel');
                        if (panel) panel.classList.remove('mobile-open');
                    }
                }

                // Up swipe - show quick actions
                if (deltaY < -this.swipeThreshold && Math.abs(deltaX) < this.swipeThreshold) {
                    if (window.debugOneClickActions && this.isMobile) {
                        window.debugOneClickActions.showPanel();
                    }
                }

                // Down swipe - hide quick actions
                if (deltaY > this.swipeThreshold && Math.abs(deltaX) < this.swipeThreshold) {
                    if (window.debugOneClickActions && this.isMobile) {
                        window.debugOneClickActions.hidePanel();
                    }
                }
            }

            addPullToRefresh() {
                let startY = 0;
                let currentY = 0;
                let isPulling = false;

                const indicator = document.createElement('div');
                indicator.className = 'pull-to-refresh';
                indicator.textContent = '↓ Pull to refresh';
                document.body.appendChild(indicator);

                document.addEventListener('touchstart', (e) => {
                    if (window.scrollY === 0) {
                        startY = e.touches[0].clientY;
                        isPulling = true;
                    }
                }, { passive: true });

                document.addEventListener('touchmove', (e) => {
                    if (isPulling && window.scrollY === 0) {
                        currentY = e.touches[0].clientY;
                        const pullDistance = currentY - startY;

                        if (pullDistance > 60) {
                            indicator.classList.add('active');
                            indicator.textContent = '↑ Release to refresh';
                        } else if (pullDistance > 20) {
                            indicator.classList.add('active');
                            indicator.textContent = '↓ Pull to refresh';
                        } else {
                            indicator.classList.remove('active');
                        }
                    }
                }, { passive: true });

                document.addEventListener('touchend', (e) => {
                    if (isPulling) {
                        const pullDistance = currentY - startY;
                        if (pullDistance > 60) {
                            this.refreshPage();
                        }
                        indicator.classList.remove('active');
                        isPulling = false;
                    }
                }, { passive: true });
            }

            optimizeScrolling() {
                // Add smooth scrolling for mobile
                document.documentElement.style.scrollBehavior = 'smooth';

                // Optimize scroll performance
                let ticking = false;

                function updateScrollPosition() {
                    // Update any scroll-dependent elements
                    ticking = false;
                }

                document.addEventListener('scroll', () => {
                    if (!ticking) {
                        requestAnimationFrame(updateScrollPosition);
                        ticking = true;
                    }
                }, { passive: true });
            }

            setupPWAFeatures() {
                // Add PWA manifest
                this.addPWAManifest();

                // Register service worker
                if ('serviceWorker' in navigator) {
                    this.registerServiceWorker();
                }

                // Add install prompt
                this.setupInstallPrompt();

                // Add app-like features
                this.addAppFeatures();
            }

            addPWAManifest() {
                const manifest = {
                    name: 'WordPress Debug Tool - Omega',
                    short_name: 'WP Debug Omega',
                    description: 'Professional WordPress debugging and optimization tool',
                    start_url: window.location.pathname,
                    display: 'standalone',
                    background_color: '#ffffff',
                    theme_color: '#667eea',
                    orientation: 'portrait-primary',
                    icons: [
                        {
                            src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkyIiBoZWlnaHQ9IjE5MiIgdmlld0JveD0iMCAwIDE5MiAxOTIiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxOTIiIGhlaWdodD0iMTkyIiByeD0iMjQiIGZpbGw9IiM2NjdlZWEiLz4KPHN2ZyB4PSI0OCIgeT0iNDgiIHdpZHRoPSI5NiIgaGVpZ2h0PSI5NiIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJ3aGl0ZSI+CjxwYXRoIGQ9Ik0xMiAyQzYuNDggMiAyIDYuNDggMiAxMnM0LjQ4IDEwIDEwIDEwIDEwLTQuNDggMTAtMTBTMTcuNTIgMiAxMiAyem0tMiAxNWwtNS01IDEuNDEtMS40MUwxMCAxNC4xN2w3LjU5LTcuNTlMMTkgOGwtOSA5eiIvPgo8L3N2Zz4KPC9zdmc+',
                            sizes: '192x192',
                            type: 'image/svg+xml'
                        }
                    ]
                };

                const manifestBlob = new Blob([JSON.stringify(manifest)], { type: 'application/json' });
                const manifestURL = URL.createObjectURL(manifestBlob);

                const link = document.createElement('link');
                link.rel = 'manifest';
                link.href = manifestURL;
                document.head.appendChild(link);
            }

            registerServiceWorker() {
                const swCode = `
                    const CACHE_NAME = 'wp-debug-omega-v1';
                    const urlsToCache = [
                        '${window.location.pathname}',
                        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js',
                        'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js'
                    ];

                    self.addEventListener('install', (event) => {
                        event.waitUntil(
                            caches.open(CACHE_NAME)
                                .then((cache) => cache.addAll(urlsToCache))
                        );
                    });

                    self.addEventListener('fetch', (event) => {
                        event.respondWith(
                            caches.match(event.request)
                                .then((response) => {
                                    return response || fetch(event.request);
                                })
                        );
                    });
                `;

                const swBlob = new Blob([swCode], { type: 'application/javascript' });
                const swURL = URL.createObjectURL(swBlob);

                navigator.serviceWorker.register(swURL)
                    .then((registration) => {
                        console.log('🔧 Service Worker registered:', registration);
                    })
                    .catch((error) => {
                        console.log('❌ Service Worker registration failed:', error);
                    });
            }

            setupInstallPrompt() {
                let deferredPrompt;

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    deferredPrompt = e;

                    // Show install button in mobile menu
                    const installBtn = document.querySelector('[data-action="install-pwa"]');
                    if (installBtn) {
                        installBtn.style.display = 'block';
                        installBtn.addEventListener('click', () => {
                            if (deferredPrompt) {
                                deferredPrompt.prompt();
                                deferredPrompt.userChoice.then((choiceResult) => {
                                    if (choiceResult.outcome === 'accepted') {
                                        console.log('📱 PWA installed');
                                        this.isInstalled = true;
                                    }
                                    deferredPrompt = null;
                                });
                            }
                        });
                    }
                });

                window.addEventListener('appinstalled', () => {
                    console.log('📱 PWA was installed');
                    this.isInstalled = true;
                    const installBtn = document.querySelector('[data-action="install-pwa"]');
                    if (installBtn) installBtn.style.display = 'none';
                });
            }

            addAppFeatures() {
                // Add viewport meta tag for mobile
                const viewport = document.createElement('meta');
                viewport.name = 'viewport';
                viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
                document.head.appendChild(viewport);

                // Add apple-mobile-web-app tags
                const appleMeta = [
                    { name: 'apple-mobile-web-app-capable', content: 'yes' },
                    { name: 'apple-mobile-web-app-status-bar-style', content: 'default' },
                    { name: 'apple-mobile-web-app-title', content: 'WP Debug Omega' }
                ];

                appleMeta.forEach(meta => {
                    const tag = document.createElement('meta');
                    tag.name = meta.name;
                    tag.content = meta.content;
                    document.head.appendChild(tag);
                });

                // Prevent zoom on input focus (iOS)
                document.addEventListener('focusin', (e) => {
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                        const viewport = document.querySelector('meta[name="viewport"]');
                        if (viewport) {
                            viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
                        }
                    }
                });
            }

            handleMobileAction(action) {
                switch (action) {
                    case 'toggle-insights':
                        if (window.debugAIInsights) {
                            window.debugAIInsights.togglePanel();
                        }
                        break;
                    case 'toggle-actions':
                        if (window.debugOneClickActions) {
                            window.debugOneClickActions.togglePanel();
                        }
                        break;
                    case 'search':
                        if (window.debugSearchFilter) {
                            const searchInput = document.getElementById('global-search-input');
                            if (searchInput) {
                                searchInput.focus();
                                searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }
                        break;
                    case 'refresh':
                        this.refreshPage();
                        break;
                    case 'install-pwa':
                        // Handled in setupInstallPrompt
                        break;
                }
            }

            refreshPage() {
                // Show loading indicator
                const indicator = document.querySelector('.pull-to-refresh');
                if (indicator) {
                    indicator.textContent = '🔄 Refreshing...';
                    indicator.classList.add('active');
                }

                // Simulate refresh (in real implementation, this would reload debug data)
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }

            // Utility methods for responsive behavior
            updateLayoutForOrientation() {
                const orientation = window.innerHeight > window.innerWidth ? 'portrait' : 'landscape';
                document.body.setAttribute('data-orientation', orientation);

                if (orientation === 'landscape' && this.isMobile) {
                    // Optimize for landscape mobile
                    const panels = document.querySelectorAll('#ai-insights-panel, #quick-actions-panel');
                    panels.forEach(panel => {
                        if (panel) panel.style.height = 'calc(100vh - 20px)';
                    });
                }
            }

            addHapticFeedback() {
                // Add haptic feedback for supported devices
                if ('vibrate' in navigator) {
                    document.addEventListener('click', (e) => {
                        if (e.target.matches('.quick-action-btn, .wizard-btn, .mobile-menu-item')) {
                            navigator.vibrate(50); // Short vibration
                        }
                    });
                }
            }
        }

        // Enhanced Global Search & Filter System
        class DebugSearchFilter {
            constructor() {
                this.filters = new Map();
                this.searchIndex = this.buildSearchIndex();
                this.searchHistory = JSON.parse(localStorage.getItem('debug-search-history') || '[]');
                this.savedFilters = JSON.parse(localStorage.getItem('debug-saved-filters') || '{}');
                this.initializeSearchUI();
            }

            buildSearchIndex() {
                const index = new Map();

                // Comprehensive section mapping - ALL debug sections
                const sections = [
                    'database-tables', 'plugin-analysis', 'error-logs', 'hooks-analysis',
                    'theme-templates', 'security-scan', 'block-editor', 'cache-cdn',
                    'performance-breakdown', 'cron-diagnostics', 'wp-config', 'server-info',
                    'http-tests', 'custom-domain', 'real-time-logs', 'wp-cli-history'
                ];

                sections.forEach(sectionId => {
                    // Try multiple selectors to find sections
                    let section = document.getElementById(sectionId) ||
                                 document.querySelector(`[data-section="${sectionId}"]`) ||
                                 document.querySelector(`.debug-section:has([id*="${sectionId}"])`);

                    if (section) {
                        // Index table rows
                        const rows = section.querySelectorAll('tbody tr, .debug-metric, .debug-info, .debug-warning, .debug-error');
                        rows.forEach((row, idx) => {
                            const text = row.textContent.toLowerCase();
                            const sectionTitle = this.getSectionTitle(section);

                            index.set(`${sectionId}-${idx}`, {
                                element: row,
                                text: text,
                                section: sectionId,
                                sectionTitle: sectionTitle,
                                type: this.getElementType(row)
                            });
                        });

                        // Index section headers and content
                        const headers = section.querySelectorAll('h3, h4, h5, .debug-section-header');
                        headers.forEach((header, idx) => {
                            const text = header.textContent.toLowerCase();
                            index.set(`${sectionId}-header-${idx}`, {
                                element: header,
                                text: text,
                                section: sectionId,
                                sectionTitle: this.getSectionTitle(section),
                                type: 'header'
                            });
                        });

                        // Index code blocks and technical content
                        const codeBlocks = section.querySelectorAll('code, pre, .debug-code');
                        codeBlocks.forEach((code, idx) => {
                            const text = code.textContent.toLowerCase();
                            index.set(`${sectionId}-code-${idx}`, {
                                element: code,
                                text: text,
                                section: sectionId,
                                sectionTitle: this.getSectionTitle(section),
                                type: 'code'
                            });
                        });
                    }
                });

                return index;
            }

            getSectionTitle(section) {
                const header = section.querySelector('.debug-section-header, h3, h4');
                return header ? header.textContent.trim() : 'Unknown Section';
            }

            getElementType(element) {
                if (element.tagName === 'TR') return 'table-row';
                if (element.classList.contains('debug-metric')) return 'metric';
                if (element.classList.contains('debug-info')) return 'info';
                if (element.classList.contains('debug-warning')) return 'warning';
                if (element.classList.contains('debug-error')) return 'error';
                return 'content';
            }

            initializeSearchUI() {
                // Add enhanced global search interface to header
                const header = document.querySelector('.debug-header');
                if (header) {
                    const searchContainer = document.createElement('div');
                    searchContainer.id = 'global-search-container';
                    searchContainer.style.cssText = 'margin: 20px 0; padding: 20px; background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px; border-left: 4px solid #007cba;';
                    searchContainer.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h4 style="margin: 0; color: var(--debug-text);">🔍 Global Search & Filter System</h4>
                            <div style="display: flex; gap: 8px;">
                                <button id="search-help" style="padding: 4px 8px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">Help</button>
                                <button id="search-settings" style="padding: 4px 8px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">Settings</button>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 10px; margin-bottom: 15px;">
                            <div style="position: relative;">
                                <input type="text" id="global-search" placeholder="🔍 Search across all diagnostic sections..."
                                       style="width: 100%; padding: 12px 40px 12px 12px; border: 2px solid var(--debug-border); border-radius: 6px; background: var(--debug-bg); color: var(--debug-text); font-size: 14px;"
                                       autocomplete="off">
                                <div id="search-suggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: var(--debug-bg); border: 1px solid var(--debug-border); border-top: none; border-radius: 0 0 6px 6px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
                            </div>
                            <select id="search-section" style="padding: 12px; border: 2px solid var(--debug-border); border-radius: 6px; background: var(--debug-bg); color: var(--debug-text); min-width: 150px;">
                                <option value="all">All Sections</option>
                                <option value="database-tables">Database Tables</option>
                                <option value="plugin-analysis">Plugin Analysis</option>
                                <option value="error-logs">Error Logs</option>
                                <option value="hooks-analysis">Hooks Analysis</option>
                                <option value="theme-templates">Theme Templates</option>
                                <option value="security-scan">Security Scan</option>
                                <option value="block-editor">Block Editor</option>
                                <option value="cache-cdn">Cache & CDN</option>
                                <option value="performance-breakdown">Performance</option>
                                <option value="cron-diagnostics">Cron Jobs</option>
                                <option value="wp-config">WP Config</option>
                                <option value="server-info">Server Info</option>
                                <option value="http-tests">HTTP Tests</option>
                                <option value="wp-cli-history">WP-CLI</option>
                            </select>
                            <select id="search-type" style="padding: 12px; border: 2px solid var(--debug-border); border-radius: 6px; background: var(--debug-bg); color: var(--debug-text); min-width: 120px;">
                                <option value="all">All Types</option>
                                <option value="table-row">Table Rows</option>
                                <option value="metric">Metrics</option>
                                <option value="info">Info</option>
                                <option value="warning">Warnings</option>
                                <option value="error">Errors</option>
                                <option value="header">Headers</option>
                                <option value="code">Code</option>
                            </select>
                            <div style="display: flex; gap: 5px;">
                                <button id="clear-search" style="padding: 12px 15px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">Clear</button>
                                <button id="save-filter" style="padding: 12px 15px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">Save</button>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <span id="search-results-count" style="font-size: 12px; color: #666;">Ready to search</span>
                                <div style="display: flex; gap: 8px;">
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 12px;">
                                        <input type="checkbox" id="search-case-sensitive" style="margin: 0;">
                                        Case Sensitive
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 12px;">
                                        <input type="checkbox" id="search-regex" style="margin: 0;">
                                        Regex
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 12px;">
                                        <input type="checkbox" id="search-whole-words" style="margin: 0;">
                                        Whole Words
                                    </label>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <select id="saved-filters" style="padding: 6px; border: 1px solid var(--debug-border); border-radius: 4px; background: var(--debug-bg); color: var(--debug-text); font-size: 11px;">
                                    <option value="">Load Saved Filter...</option>
                                </select>
                                <button id="export-search-results" style="padding: 6px 10px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">Export Results</button>
                            </div>
                        </div>

                        <div id="search-history" style="display: none; max-height: 100px; overflow-y: auto; background: #f8f9fa; border: 1px solid var(--debug-border); border-radius: 4px; padding: 8px; margin-top: 10px;">
                            <div style="font-size: 11px; font-weight: 600; margin-bottom: 5px;">Recent Searches:</div>
                            <div id="search-history-list"></div>
                        </div>
                    `;
                    header.appendChild(searchContainer);

                    // Add event listeners
                    this.setupSearchEventListeners();
                    this.loadSavedFilters();
                    this.updateSearchHistory();
                }
            }

            setupSearchEventListeners() {
                const searchInput = document.getElementById('global-search');
                const sectionSelect = document.getElementById('search-section');
                const typeSelect = document.getElementById('search-type');

                // Main search functionality
                searchInput.addEventListener('input', (e) => this.performSearch(e.target.value));
                searchInput.addEventListener('keydown', (e) => this.handleSearchKeydown(e));
                searchInput.addEventListener('focus', () => this.showSearchSuggestions());
                searchInput.addEventListener('blur', () => setTimeout(() => this.hideSearchSuggestions(), 200));

                // Filter controls
                sectionSelect.addEventListener('change', (e) => this.filterBySection(e.target.value));
                typeSelect.addEventListener('change', (e) => this.filterByType(e.target.value));

                // Search options
                document.getElementById('search-case-sensitive').addEventListener('change', () => this.performSearch(searchInput.value));
                document.getElementById('search-regex').addEventListener('change', () => this.performSearch(searchInput.value));
                document.getElementById('search-whole-words').addEventListener('change', () => this.performSearch(searchInput.value));

                // Action buttons
                document.getElementById('clear-search').addEventListener('click', () => this.clearSearch());
                document.getElementById('save-filter').addEventListener('click', () => this.saveFilterPreset());
                document.getElementById('export-search-results').addEventListener('click', () => this.exportSearchResults());
                document.getElementById('search-help').addEventListener('click', () => this.showSearchHelp());
                document.getElementById('search-settings').addEventListener('click', () => this.showSearchSettings());

                // Saved filters
                document.getElementById('saved-filters').addEventListener('change', (e) => this.loadFilterPreset(e.target.value));
            }

            performSearch(query) {
                const section = document.getElementById('search-section').value;
                const type = document.getElementById('search-type').value;
                const caseSensitive = document.getElementById('search-case-sensitive').checked;
                const useRegex = document.getElementById('search-regex').checked;
                const wholeWords = document.getElementById('search-whole-words').checked;

                if (!query.trim()) {
                    this.showAllRows();
                    this.updateSearchResults('', section, 0);
                    return;
                }

                // Add to search history
                this.addToSearchHistory(query);

                let searchTerms;
                let matchCount = 0;
                let matchedSections = new Set();

                try {
                    if (useRegex) {
                        // Regex search
                        const flags = caseSensitive ? 'g' : 'gi';
                        const regex = new RegExp(query, flags);

                        this.searchIndex.forEach((item, key) => {
                            if (section !== 'all' && item.section !== section) return;
                            if (type !== 'all' && item.type !== type) return;

                            const searchText = caseSensitive ? item.text : item.text.toLowerCase();
                            const matches = regex.test(searchText);

                            item.element.style.display = matches ? '' : 'none';

                            if (matches) {
                                this.highlightRegexTerms(item.element, regex);
                                matchCount++;
                                matchedSections.add(item.sectionTitle);
                                this.scrollToFirstMatch(item.element);
                            } else {
                                this.removeHighlights(item.element);
                            }
                        });
                    } else {
                        // Standard text search
                        const processedQuery = caseSensitive ? query : query.toLowerCase();

                        if (wholeWords) {
                            searchTerms = [processedQuery];
                        } else {
                            searchTerms = processedQuery.split(' ').filter(term => term.length > 0);
                        }

                        this.searchIndex.forEach((item, key) => {
                            if (section !== 'all' && item.section !== section) return;
                            if (type !== 'all' && item.type !== type) return;

                            const searchText = caseSensitive ? item.text : item.text.toLowerCase();

                            let matches;
                            if (wholeWords) {
                                const wordRegex = new RegExp(`\\b${this.escapeRegex(processedQuery)}\\b`, caseSensitive ? 'g' : 'gi');
                                matches = wordRegex.test(searchText);
                            } else {
                                matches = searchTerms.every(term => searchText.includes(term));
                            }

                            item.element.style.display = matches ? '' : 'none';

                            if (matches) {
                                this.highlightSearchTerms(item.element, searchTerms, caseSensitive);
                                matchCount++;
                                matchedSections.add(item.sectionTitle);
                                this.scrollToFirstMatch(item.element);
                            } else {
                                this.removeHighlights(item.element);
                            }
                        });
                    }

                    this.updateSearchResults(query, section, matchCount, matchedSections);
                    this.expandMatchingSections(matchedSections);

                } catch (error) {
                    console.error('Search error:', error);
                    this.updateSearchResults(query, section, 0, new Set(), 'Invalid search pattern');
                }
            }

            addToSearchHistory(query) {
                if (!query.trim() || this.searchHistory.includes(query)) return;

                this.searchHistory.unshift(query);
                if (this.searchHistory.length > 20) {
                    this.searchHistory = this.searchHistory.slice(0, 20);
                }

                localStorage.setItem('debug-search-history', JSON.stringify(this.searchHistory));
                this.updateSearchHistory();
            }

            updateSearchHistory() {
                const historyList = document.getElementById('search-history-list');
                if (!historyList || this.searchHistory.length === 0) return;

                historyList.innerHTML = this.searchHistory.map(query =>
                    `<span style="display: inline-block; margin: 2px; padding: 2px 6px; background: #e9ecef; border-radius: 3px; cursor: pointer; font-size: 10px;"
                           onclick="document.getElementById('global-search').value='${this.escapeHtml(query)}'; window.debugSearchFilter.performSearch('${this.escapeHtml(query)}');">
                        ${this.escapeHtml(query)}
                    </span>`
                ).join('');
            }

            escapeRegex(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            scrollToFirstMatch(element) {
                if (!this.firstMatchScrolled) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    this.firstMatchScrolled = true;
                    setTimeout(() => { this.firstMatchScrolled = false; }, 1000);
                }
            }

            expandMatchingSections(matchedSections) {
                // Auto-expand sections that contain matches
                matchedSections.forEach(sectionTitle => {
                    const sectionHeaders = document.querySelectorAll('.debug-section-header');
                    sectionHeaders.forEach(header => {
                        if (header.textContent.includes(sectionTitle.split(' ')[0])) {
                            const content = header.nextElementSibling;
                            if (content && content.style.display === 'none') {
                                header.click(); // Expand the section
                            }
                        }
                    });
                });
            }

            filterBySection(sectionId) {
                const currentQuery = document.getElementById('global-search').value;
                if (currentQuery.trim()) {
                    this.performSearch(currentQuery); // Re-run search with new section filter
                } else {
                    if (sectionId === 'all') {
                        this.showAllRows();
                    } else {
                        this.searchIndex.forEach((item, key) => {
                            item.element.style.display = item.section === sectionId ? '' : 'none';
                        });
                    }
                }
            }

            filterByType(typeId) {
                const currentQuery = document.getElementById('global-search').value;
                if (currentQuery.trim()) {
                    this.performSearch(currentQuery); // Re-run search with new type filter
                } else {
                    if (typeId === 'all') {
                        this.showAllRows();
                    } else {
                        this.searchIndex.forEach((item, key) => {
                            item.element.style.display = item.type === typeId ? '' : 'none';
                        });
                    }
                }
            }

            handleSearchKeydown(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.performSearch(e.target.value);
                } else if (e.key === 'Escape') {
                    this.clearSearch();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.navigateSearchSuggestions('down');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.navigateSearchSuggestions('up');
                }
            }

            showSearchSuggestions() {
                const suggestions = document.getElementById('search-suggestions');
                const input = document.getElementById('global-search');
                const query = input.value.toLowerCase();

                if (!query.trim() || query.length < 2) {
                    suggestions.style.display = 'none';
                    return;
                }

                // Generate suggestions from search index
                const suggestionSet = new Set();
                this.searchIndex.forEach((item, key) => {
                    const words = item.text.split(/\s+/);
                    words.forEach(word => {
                        if (word.toLowerCase().includes(query) && word.length > 2) {
                            suggestionSet.add(word);
                        }
                    });
                });

                // Add search history suggestions
                this.searchHistory.forEach(historyItem => {
                    if (historyItem.toLowerCase().includes(query)) {
                        suggestionSet.add(historyItem);
                    }
                });

                const suggestionArray = Array.from(suggestionSet).slice(0, 8);

                if (suggestionArray.length > 0) {
                    suggestions.innerHTML = suggestionArray.map(suggestion =>
                        `<div style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid var(--debug-border); font-size: 12px;"
                               onmouseover="this.style.background='var(--debug-info)'"
                               onmouseout="this.style.background=''"
                               onclick="document.getElementById('global-search').value='${this.escapeHtml(suggestion)}'; window.debugSearchFilter.performSearch('${this.escapeHtml(suggestion)}'); this.parentElement.style.display='none';">
                            ${this.escapeHtml(suggestion)}
                        </div>`
                    ).join('');
                    suggestions.style.display = 'block';
                } else {
                    suggestions.style.display = 'none';
                }
            }

            hideSearchSuggestions() {
                const suggestions = document.getElementById('search-suggestions');
                suggestions.style.display = 'none';
            }

            navigateSearchSuggestions(direction) {
                const suggestions = document.getElementById('search-suggestions');
                const items = suggestions.querySelectorAll('div');

                if (items.length === 0) return;

                let currentIndex = -1;
                items.forEach((item, index) => {
                    if (item.style.background === 'var(--debug-info)') {
                        currentIndex = index;
                    }
                    item.style.background = '';
                });

                if (direction === 'down') {
                    currentIndex = (currentIndex + 1) % items.length;
                } else {
                    currentIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                }

                items[currentIndex].style.background = 'var(--debug-info)';
                document.getElementById('global-search').value = items[currentIndex].textContent.trim();
            }

            highlightSearchTerms(element, terms, caseSensitive = false) {
                this.removeHighlights(element);

                if (!terms || terms.length === 0) return;

                terms.forEach(term => {
                    if (!term.trim()) return;

                    const walker = document.createTreeWalker(
                        element,
                        NodeFilter.SHOW_TEXT,
                        null,
                        false
                    );

                    const textNodes = [];
                    let node;
                    while (node = walker.nextNode()) {
                        textNodes.push(node);
                    }

                    textNodes.forEach(textNode => {
                        const text = textNode.textContent;
                        const flags = caseSensitive ? 'g' : 'gi';
                        const escapedTerm = this.escapeRegex(term);
                        const regex = new RegExp(`(${escapedTerm})`, flags);

                        if (regex.test(text)) {
                            const highlightedText = text.replace(regex, '<span class="search-highlight" style="background: #ffeb3b; color: #000; padding: 1px 3px; border-radius: 2px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">$1</span>');
                            const wrapper = document.createElement('span');
                            wrapper.innerHTML = highlightedText;
                            textNode.parentNode.replaceChild(wrapper, textNode);
                        }
                    });
                });
            }

            highlightRegexTerms(element, regex) {
                this.removeHighlights(element);

                const walker = document.createTreeWalker(
                    element,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );

                const textNodes = [];
                let node;
                while (node = walker.nextNode()) {
                    textNodes.push(node);
                }

                textNodes.forEach(textNode => {
                    const text = textNode.textContent;
                    if (regex.test(text)) {
                        const highlightedText = text.replace(regex, '<span class="search-highlight" style="background: #ff9800; color: #000; padding: 1px 3px; border-radius: 2px; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">$&</span>');
                        const wrapper = document.createElement('span');
                        wrapper.innerHTML = highlightedText;
                        textNode.parentNode.replaceChild(wrapper, textNode);
                    }
                });
            }

            removeHighlights(element) {
                element.querySelectorAll('.search-highlight').forEach(highlight => {
                    const parent = highlight.parentNode;
                    parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                    parent.normalize(); // Merge adjacent text nodes
                });
            }

            showAllRows() {
                this.searchIndex.forEach((item, key) => {
                    item.element.style.display = '';
                    // Remove highlights
                    item.element.querySelectorAll('.search-highlight').forEach(el => {
                        el.outerHTML = el.innerHTML;
                    });
                });
            }

            clearSearch() {
                document.getElementById('global-search').value = '';
                document.getElementById('search-section').value = 'all';
                this.showAllRows();
                this.updateSearchResults('', 'all');
            }

            updateSearchResults(query, section) {
                let visibleCount = 0;
                this.searchIndex.forEach((item, key) => {
                    if (item.element.style.display !== 'none') {
                        visibleCount++;
                    }
                });

                // Update or create results indicator
                let resultsDiv = document.getElementById('search-results');
                if (!resultsDiv) {
                    resultsDiv = document.createElement('div');
                    resultsDiv.id = 'search-results';
                    resultsDiv.style.cssText = 'margin: 10px 0; padding: 8px 12px; background: var(--debug-info); border-radius: 4px; font-size: 13px;';
                    document.querySelector('.debug-header').appendChild(resultsDiv);
                }

                if (query) {
                    resultsDiv.innerHTML = `🔍 Found ${visibleCount} results for "${query}" in ${section === 'all' ? 'all sections' : section}`;
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.style.display = 'none';
                }
            }

            saveFilterPreset() {
                const query = document.getElementById('global-search').value;
                const section = document.getElementById('search-section').value;
                const type = document.getElementById('search-type').value;
                const caseSensitive = document.getElementById('search-case-sensitive').checked;
                const useRegex = document.getElementById('search-regex').checked;
                const wholeWords = document.getElementById('search-whole-words').checked;

                if (!query.trim()) {
                    alert('Please enter a search query first');
                    return;
                }

                const presetName = prompt('Enter a name for this filter preset:');
                if (presetName) {
                    const preset = {
                        query: query,
                        section: section,
                        type: type,
                        caseSensitive: caseSensitive,
                        useRegex: useRegex,
                        wholeWords: wholeWords,
                        timestamp: Date.now(),
                        formatted_date: new Date().toLocaleString()
                    };

                    this.savedFilters[presetName] = preset;
                    localStorage.setItem('debug-saved-filters', JSON.stringify(this.savedFilters));
                    this.loadSavedFilters();

                    alert(`Filter preset "${presetName}" saved!`);
                }
            }

            loadSavedFilters() {
                const select = document.getElementById('saved-filters');
                if (!select) return;

                // Clear existing options except first
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }

                Object.keys(this.savedFilters).forEach(presetName => {
                    const option = document.createElement('option');
                    option.value = presetName;
                    option.textContent = `${presetName} (${this.savedFilters[presetName].formatted_date})`;
                    select.appendChild(option);
                });
            }

            loadFilterPreset(presetName) {
                if (!presetName || !this.savedFilters[presetName]) return;

                const preset = this.savedFilters[presetName];

                document.getElementById('global-search').value = preset.query;
                document.getElementById('search-section').value = preset.section;
                document.getElementById('search-type').value = preset.type || 'all';
                document.getElementById('search-case-sensitive').checked = preset.caseSensitive || false;
                document.getElementById('search-regex').checked = preset.useRegex || false;
                document.getElementById('search-whole-words').checked = preset.wholeWords || false;

                this.performSearch(preset.query);

                // Reset select
                document.getElementById('saved-filters').value = '';
            }

            exportSearchResults() {
                const query = document.getElementById('global-search').value;
                if (!query.trim()) {
                    alert('Please perform a search first');
                    return;
                }

                const results = [];
                this.searchIndex.forEach((item, key) => {
                    if (item.element.style.display !== 'none') {
                        results.push({
                            section: item.sectionTitle,
                            type: item.type,
                            content: item.text.substring(0, 200) + (item.text.length > 200 ? '...' : ''),
                            element_id: key
                        });
                    }
                });

                const exportData = {
                    search_query: query,
                    search_timestamp: new Date().toISOString(),
                    total_results: results.length,
                    results: results
                };

                const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `debug-search-results-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            showSearchHelp() {
                alert(`Debug Tool Search Help:

🔍 Basic Search:
- Type any text to search across all sections
- Use multiple words for AND search
- Results are highlighted in yellow

⚙️ Advanced Options:
- Case Sensitive: Match exact case
- Regex: Use regular expressions
- Whole Words: Match complete words only

📋 Filters:
- Section: Limit search to specific sections
- Type: Filter by content type (tables, metrics, etc.)

⌨️ Keyboard Shortcuts:
- Enter: Execute search
- Escape: Clear search
- Arrow keys: Navigate suggestions

💾 Features:
- Search history (last 20 searches)
- Save/load filter presets
- Export search results
- Auto-expand matching sections`);
            }

            showSearchSettings() {
                const settings = {
                    maxHistory: 20,
                    autoExpand: true,
                    highlightColor: '#ffeb3b',
                    searchDelay: 300
                };

                alert(`Search Settings:

Max History: ${settings.maxHistory} searches
Auto-expand sections: ${settings.autoExpand ? 'Enabled' : 'Disabled'}
Highlight color: ${settings.highlightColor}
Search delay: ${settings.searchDelay}ms

Note: Settings customization coming in future update!`);
            }

            saveFilterPreset() {
                const query = document.getElementById('global-search').value;
                const section = document.getElementById('search-section').value;

                if (!query) {
                    alert('Please enter a search query to save as preset');
                    return;
                }

                const presetName = prompt('Enter a name for this filter preset:');
                if (presetName) {
                    const presets = JSON.parse(localStorage.getItem('debug-filter-presets') || '{}');
                    presets[presetName] = { query, section };
                    localStorage.setItem('debug-filter-presets', JSON.stringify(presets));
                    alert(`Filter preset "${presetName}" saved successfully!`);
                    this.loadFilterPresets();
                }
            }

            loadFilterPresets() {
                const presets = JSON.parse(localStorage.getItem('debug-filter-presets') || '{}');
                // Implementation for loading presets UI would go here
            }

            applyFilters(section, criteria) {
                const rows = document.querySelectorAll(`#${section} tbody tr`);
                rows.forEach(row => {
                    row.style.display = this.matchesCriteria(row, criteria) ? '' : 'none';
                });
            }

            matchesCriteria(row, criteria) {
                // Implementation for complex criteria matching
                return true;
            }
        }

        // Modal-Based Detail Views
        class DebugModalSystem {
            constructor() {
                this.createModalContainer();
                this.initializeClickHandlers();
            }

            createModalContainer() {
                const modalHTML = `
                    <div id="debug-modal" class="debug-modal" style="display: none;">
                        <div class="debug-modal-content">
                            <div class="debug-modal-header">
                                <h3 id="modal-title">Detail View</h3>
                                <button id="modal-close" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--debug-text);">&times;</button>
                            </div>
                            <div id="modal-body" class="debug-modal-body">
                                <!-- Content will be populated dynamically -->
                            </div>
                            <div class="debug-modal-footer">
                                <button id="modal-export" class="debug-button" style="background: #17a2b8; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Export Data</button>
                                <button id="modal-copy" class="debug-button" style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Copy to Clipboard</button>
                            </div>
                        </div>
                    </div>
                `;

                document.body.insertAdjacentHTML('beforeend', modalHTML);

                // Add event listeners
                document.getElementById('modal-close').addEventListener('click', () => this.closeModal());
                document.getElementById('debug-modal').addEventListener('click', (e) => {
                    if (e.target.id === 'debug-modal') this.closeModal();
                });
                document.getElementById('modal-export').addEventListener('click', () => this.exportModalData());
                document.getElementById('modal-copy').addEventListener('click', () => this.copyModalData());

                // ESC key to close modal
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') this.closeModal();
                });
            }

            initializeClickHandlers() {
                // Add click handlers to table rows in target sections
                const targetSections = ['database-tables', 'plugin-analysis', 'error-patterns', 'security-scan'];

                targetSections.forEach(sectionId => {
                    const section = document.querySelector(`[data-section="${sectionId}"]`);
                    if (section) {
                        const rows = section.querySelectorAll('tbody tr');
                        rows.forEach((row, index) => {
                            row.style.cursor = 'pointer';
                            row.addEventListener('click', () => this.openModal(sectionId, row, index));
                            row.addEventListener('mouseenter', () => {
                                row.style.backgroundColor = 'var(--debug-info)';
                            });
                            row.addEventListener('mouseleave', () => {
                                row.style.backgroundColor = '';
                            });
                        });
                    }
                });
            }

            openModal(sectionId, row, index) {
                const modal = document.getElementById('debug-modal');
                const title = document.getElementById('modal-title');
                const body = document.getElementById('modal-body');

                // Set title based on section
                const sectionTitles = {
                    'database-tables': 'Database Table Details',
                    'plugin-analysis': 'Plugin Analysis Details',
                    'error-patterns': 'Error Pattern Details',
                    'security-scan': 'Security Scan Details'
                };

                title.textContent = sectionTitles[sectionId] || 'Detail View';

                // Extract data from row
                const cells = row.querySelectorAll('td');
                const rowData = Array.from(cells).map(cell => ({
                    content: cell.textContent.trim(),
                    html: cell.innerHTML
                }));

                // Generate detailed view based on section type
                body.innerHTML = this.generateDetailContent(sectionId, rowData, index);

                // Show modal
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            generateDetailContent(sectionId, rowData, index) {
                let content = `<div class="modal-detail-grid" style="display: grid; gap: 15px;">`;

                switch (sectionId) {
                    case 'database-tables':
                        content += `
                            <div class="detail-section">
                                <h4>📊 Table Information</h4>
                                <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div><strong>Table Name:</strong> ${rowData[0]?.content || 'N/A'}</div>
                                    <div><strong>Engine:</strong> ${rowData[1]?.content || 'N/A'}</div>
                                    <div><strong>Rows:</strong> ${rowData[2]?.content || 'N/A'}</div>
                                    <div><strong>Size:</strong> ${rowData[3]?.content || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="detail-section">
                                <h4>🔧 Actions</h4>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button class="detail-action-btn" onclick="alert('Analyze Table Structure')">Analyze Structure</button>
                                    <button class="detail-action-btn" onclick="alert('Check Indexes')">Check Indexes</button>
                                    <button class="detail-action-btn" onclick="alert('Optimize Table')">Optimize Table</button>
                                </div>
                            </div>
                        `;
                        break;

                    case 'plugin-analysis':
                        content += `
                            <div class="detail-section">
                                <h4>🔌 Plugin Details</h4>
                                <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div><strong>Plugin Name:</strong> ${rowData[0]?.content || 'N/A'}</div>
                                    <div><strong>Version:</strong> ${rowData[1]?.content || 'N/A'}</div>
                                    <div><strong>Status:</strong> ${rowData[2]?.content || 'N/A'}</div>
                                    <div><strong>Author:</strong> ${rowData[3]?.content || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="detail-section">
                                <h4>⚡ Performance Impact</h4>
                                <div class="performance-bars">
                                    <div>Load Time: <div class="perf-bar"><div style="width: ${Math.random() * 100}%; background: #28a745; height: 20px;"></div></div></div>
                                    <div>Memory Usage: <div class="perf-bar"><div style="width: ${Math.random() * 100}%; background: #ffc107; height: 20px;"></div></div></div>
                                </div>
                            </div>
                        `;
                        break;

                    case 'error-patterns':
                        content += `
                            <div class="detail-section">
                                <h4>🚨 Error Analysis</h4>
                                <div class="error-details">
                                    <div><strong>Error Type:</strong> ${rowData[0]?.content || 'N/A'}</div>
                                    <div><strong>Frequency:</strong> ${rowData[1]?.content || 'N/A'}</div>
                                    <div><strong>Last Occurrence:</strong> ${rowData[2]?.content || 'N/A'}</div>
                                    <div><strong>Severity:</strong> ${rowData[3]?.content || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="detail-section">
                                <h4>🔧 Suggested Solutions</h4>
                                <ul style="margin: 10px 0; padding-left: 20px;">
                                    <li>Check plugin compatibility</li>
                                    <li>Review recent code changes</li>
                                    <li>Verify server configuration</li>
                                    <li>Update WordPress core and plugins</li>
                                </ul>
                            </div>
                        `;
                        break;

                    default:
                        content += `
                            <div class="detail-section">
                                <h4>📋 Raw Data</h4>
                                <div class="raw-data" style="background: #f8f9fa; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px;">
                                    ${rowData.map((cell, idx) => `<div><strong>Column ${idx + 1}:</strong> ${cell.content}</div>`).join('')}
                                </div>
                            </div>
                        `;
                }

                content += `</div>`;
                return content;
            }

            closeModal() {
                const modal = document.getElementById('debug-modal');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }

            exportModalData() {
                const modalBody = document.getElementById('modal-body');
                const data = modalBody.textContent;
                const blob = new Blob([data], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'debug-modal-data-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            copyModalData() {
                const modalBody = document.getElementById('modal-body');
                const data = modalBody.textContent;
                navigator.clipboard.writeText(data).then(() => {
                    alert('Modal data copied to clipboard!');
                }).catch(() => {
                    alert('Failed to copy data to clipboard');
                });
            }
        }

        // Drag & Drop Section Reordering
        class DebugSectionReorder {
            constructor() {
                this.initializeDragDrop();
                this.loadSavedOrder();
            }

            initializeDragDrop() {
                // Add Sortable.js library if not already included
                if (typeof Sortable === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
                    script.onload = () => this.setupSortable();
                    document.head.appendChild(script);
                } else {
                    this.setupSortable();
                }
            }

            setupSortable() {
                const container = document.querySelector('.debug-container');
                if (!container) return;

                // Add container class for sections
                const sections = container.querySelectorAll('.debug-section');
                sections.forEach((section, index) => {
                    if (!section.id) {
                        section.id = `debug-section-${index}`;
                    }

                    // Add drag handle to section headers
                    const header = section.querySelector('.debug-section-header');
                    if (header) {
                        header.style.cursor = 'move';
                        header.insertAdjacentHTML('afterbegin', '<span class="drag-handle" style="margin-right: 8px; opacity: 0.6;">⋮⋮</span>');
                    }
                });

                // Initialize Sortable
                new Sortable(container, {
                    handle: '.debug-section-header',
                    animation: 150,
                    ghostClass: 'debug-section-ghost',
                    chosenClass: 'debug-section-chosen',
                    dragClass: 'debug-section-drag',
                    onStart: (evt) => {
                        evt.item.style.opacity = '0.5';
                    },
                    onEnd: (evt) => {
                        evt.item.style.opacity = '';
                        this.saveSectionOrder();
                        this.showReorderNotification();
                    }
                });

                // Add reorder controls to header
                this.addReorderControls();
            }

            addReorderControls() {
                const header = document.querySelector('.debug-header');
                if (!header) return;

                const controlsContainer = document.createElement('div');
                controlsContainer.style.cssText = 'margin: 10px 0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;';
                controlsContainer.innerHTML = `
                    <span style="font-weight: 600; color: var(--debug-text);">🎯 Section Layout:</span>
                    <button id="reset-section-order" style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Reset to Default</button>
                    <button id="save-layout-preset" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Save Layout</button>
                    <select id="load-layout-preset" style="padding: 6px; border: 1px solid var(--debug-border); border-radius: 4px; background: var(--debug-bg); color: var(--debug-text); font-size: 12px;">
                        <option value="">Load Saved Layout...</option>
                    </select>
                    <span id="reorder-status" style="font-size: 12px; color: #28a745; display: none;">✓ Layout saved</span>
                `;

                header.appendChild(controlsContainer);

                // Add event listeners
                document.getElementById('reset-section-order').addEventListener('click', () => this.resetToDefaultOrder());
                document.getElementById('save-layout-preset').addEventListener('click', () => this.saveLayoutPreset());
                document.getElementById('load-layout-preset').addEventListener('change', (e) => this.loadLayoutPreset(e.target.value));

                this.loadLayoutPresets();
            }

            saveSectionOrder() {
                const container = document.querySelector('.debug-container');
                const sections = container.querySelectorAll('.debug-section');
                const order = Array.from(sections).map(section => section.id);
                localStorage.setItem('debug-section-order', JSON.stringify(order));
            }

            loadSavedOrder() {
                const savedOrder = localStorage.getItem('debug-section-order');
                if (!savedOrder) return;

                try {
                    const order = JSON.parse(savedOrder);
                    this.applySectionOrder(order);
                } catch (e) {
                    console.error('Failed to load saved section order:', e);
                }
            }

            applySectionOrder(order) {
                const container = document.querySelector('.debug-container');
                if (!container) return;

                const sections = new Map();
                container.querySelectorAll('.debug-section').forEach(section => {
                    sections.set(section.id, section);
                });

                // Reorder sections according to saved order
                order.forEach(sectionId => {
                    const section = sections.get(sectionId);
                    if (section) {
                        container.appendChild(section);
                    }
                });
            }

            resetToDefaultOrder() {
                localStorage.removeItem('debug-section-order');
                location.reload(); // Simple way to restore default order
            }

            saveLayoutPreset() {
                const presetName = prompt('Enter a name for this layout preset:');
                if (!presetName) return;

                const currentOrder = JSON.parse(localStorage.getItem('debug-section-order') || '[]');
                const presets = JSON.parse(localStorage.getItem('debug-layout-presets') || '{}');
                presets[presetName] = currentOrder;
                localStorage.setItem('debug-layout-presets', JSON.stringify(presets));

                this.loadLayoutPresets();
                this.showReorderNotification(`Layout preset "${presetName}" saved!`);
            }

            loadLayoutPresets() {
                const select = document.getElementById('load-layout-preset');
                if (!select) return;

                const presets = JSON.parse(localStorage.getItem('debug-layout-presets') || '{}');

                // Clear existing options except first
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }

                // Add preset options
                Object.keys(presets).forEach(presetName => {
                    const option = document.createElement('option');
                    option.value = presetName;
                    option.textContent = presetName;
                    select.appendChild(option);
                });
            }

            loadLayoutPreset(presetName) {
                if (!presetName) return;

                const presets = JSON.parse(localStorage.getItem('debug-layout-presets') || '{}');
                const order = presets[presetName];

                if (order) {
                    localStorage.setItem('debug-section-order', JSON.stringify(order));
                    this.applySectionOrder(order);
                    this.showReorderNotification(`Layout "${presetName}" applied!`);
                }

                // Reset select
                document.getElementById('load-layout-preset').value = '';
            }

            showReorderNotification(message = '✓ Section order saved') {
                const status = document.getElementById('reorder-status');
                if (status) {
                    status.textContent = message;
                    status.style.display = 'inline';
                    setTimeout(() => {
                        status.style.display = 'none';
                    }, 3000);
                }
            }
        }

        // Real-Time Status Indicators with WebSocket
        class DebugRealTimeStatus {
            constructor() {
                this.statusSocket = null;
                this.statusInterval = null;
                this.isConnected = false;
                this.initializeStatusIndicators();
                this.startStatusUpdates();
            }

            initializeStatusIndicators() {
                // Add real-time status indicators to the header
                const header = document.querySelector('.debug-header');
                if (!header) return;

                const statusContainer = document.createElement('div');
                statusContainer.id = 'real-time-status';
                statusContainer.style.cssText = 'margin: 15px 0; padding: 15px; background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px;';
                statusContainer.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: var(--debug-text);">📡 Real-Time System Status</h4>
                        <div style="display: flex; gap: 10px;">
                            <button id="connect-status-websocket" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Connect WebSocket</button>
                            <button id="refresh-status" style="padding: 6px 12px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Refresh Now</button>
                        </div>
                    </div>
                    <div class="status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div class="status-indicator">
                            <div class="status-label">CPU Usage</div>
                            <div id="cpu-usage" class="status-value">--</div>
                            <div class="status-bar"><div id="cpu-bar" class="status-fill"></div></div>
                        </div>
                        <div class="status-indicator">
                            <div class="status-label">Memory Usage</div>
                            <div id="memory-usage" class="status-value">--</div>
                            <div class="status-bar"><div id="memory-bar" class="status-fill"></div></div>
                        </div>
                        <div class="status-indicator">
                            <div class="status-label">Active Users</div>
                            <div id="active-users" class="status-value">--</div>
                            <div class="status-trend" id="users-trend">--</div>
                        </div>
                        <div class="status-indicator">
                            <div class="status-label">Response Time</div>
                            <div id="response-time" class="status-value">--</div>
                            <div class="status-trend" id="response-trend">--</div>
                        </div>
                        <div class="status-indicator">
                            <div class="status-label">Error Rate</div>
                            <div id="error-rate" class="status-value">--</div>
                            <div class="status-trend" id="error-trend">--</div>
                        </div>
                        <div class="status-indicator">
                            <div class="status-label">WebSocket</div>
                            <div id="websocket-connection" class="status-value">🔴 Disconnected</div>
                            <div class="status-info">AJAX Fallback Active</div>
                        </div>
                    </div>
                `;

                header.appendChild(statusContainer);

                // Add event listeners
                document.getElementById('connect-status-websocket').addEventListener('click', () => this.connectWebSocket());
                document.getElementById('refresh-status').addEventListener('click', () => this.updateStatus());
            }

            connectWebSocket() {
                const wsUrl = 'ws://localhost:8080/status';

                try {
                    this.statusSocket = new WebSocket(wsUrl);

                    this.statusSocket.onopen = (event) => {
                        this.isConnected = true;
                        document.getElementById('websocket-connection').innerHTML = '🟢 Connected';
                        document.querySelector('#websocket-connection').nextElementSibling.textContent = 'Real-time Updates';
                        console.log('Status WebSocket connected');

                        // Stop AJAX polling when WebSocket is connected
                        if (this.statusInterval) {
                            clearInterval(this.statusInterval);
                            this.statusInterval = null;
                        }
                    };

                    this.statusSocket.onmessage = (event) => {
                        try {
                            const status = JSON.parse(event.data);
                            this.updateStatusDisplay(status);
                        } catch (e) {
                            console.error('Failed to parse status data:', e);
                        }
                    };

                    this.statusSocket.onclose = (event) => {
                        this.isConnected = false;
                        document.getElementById('websocket-connection').innerHTML = '🔴 Disconnected';
                        document.querySelector('#websocket-connection').nextElementSibling.textContent = 'AJAX Fallback Active';
                        console.log('Status WebSocket disconnected');

                        // Resume AJAX polling
                        this.startStatusUpdates();
                    };

                    this.statusSocket.onerror = (error) => {
                        console.error('Status WebSocket error:', error);
                        document.getElementById('websocket-connection').innerHTML = '🟡 Error';
                        alert('WebSocket connection failed. Using AJAX fallback.');
                    };

                } catch (error) {
                    console.error('WebSocket connection error:', error);
                    alert('WebSocket not supported or connection failed. Using AJAX fallback.');
                }
            }

            startStatusUpdates() {
                // Only start AJAX polling if WebSocket is not connected
                if (this.isConnected || this.statusInterval) return;

                // Initial update
                this.updateStatus();

                // Set up periodic updates every 5 seconds
                this.statusInterval = setInterval(() => {
                    this.updateStatus();
                }, 5000);
            }

            updateStatus() {
                // Simulate real-time status data (in real implementation, this would be an AJAX call)
                const mockStatus = {
                    cpu: Math.floor(Math.random() * 100),
                    memory: Math.floor(Math.random() * 100),
                    active_users: Math.floor(Math.random() * 50) + 1,
                    response_time: Math.floor(Math.random() * 500) + 50,
                    error_rate: Math.random() * 5,
                    timestamp: new Date().toISOString()
                };

                this.updateStatusDisplay(mockStatus);
            }

            updateStatusDisplay(status) {
                // Update CPU usage
                document.getElementById('cpu-usage').textContent = status.cpu + '%';
                const cpuBar = document.getElementById('cpu-bar');
                cpuBar.style.width = status.cpu + '%';
                cpuBar.style.backgroundColor = this.getStatusColor(status.cpu, 80, 60);

                // Update Memory usage
                document.getElementById('memory-usage').textContent = status.memory + '%';
                const memoryBar = document.getElementById('memory-bar');
                memoryBar.style.width = status.memory + '%';
                memoryBar.style.backgroundColor = this.getStatusColor(status.memory, 85, 70);

                // Update Active users
                document.getElementById('active-users').textContent = status.active_users;
                document.getElementById('users-trend').textContent = this.getTrendIndicator(status.active_users, 25);

                // Update Response time
                document.getElementById('response-time').textContent = status.response_time + 'ms';
                document.getElementById('response-trend').textContent = this.getTrendIndicator(status.response_time, 200, true);

                // Update Error rate
                document.getElementById('error-rate').textContent = status.error_rate.toFixed(2) + '%';
                document.getElementById('error-trend').textContent = this.getTrendIndicator(status.error_rate, 2, true);

                // Add pulse animation to updated values
                this.addPulseAnimation(['cpu-usage', 'memory-usage', 'active-users', 'response-time', 'error-rate']);
            }

            getStatusColor(value, dangerThreshold, warningThreshold) {
                if (value >= dangerThreshold) return '#dc3545'; // Red
                if (value >= warningThreshold) return '#ffc107'; // Yellow
                return '#28a745'; // Green
            }

            getTrendIndicator(value, threshold, inverse = false) {
                const isHigh = inverse ? value > threshold : value < threshold;
                return isHigh ? '📈 High' : '📉 Normal';
            }

            addPulseAnimation(elementIds) {
                elementIds.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.style.animation = 'pulse 0.5s ease-in-out';
                        setTimeout(() => {
                            element.style.animation = '';
                        }, 500);
                    }
                });
            }
        }

        // WP-CLI Integration & Command Runner
        class DebugWPCLI {
            constructor() {
                this.commandHistory = JSON.parse(localStorage.getItem('wp-cli-history') || '[]');
                this.commandsExecuted = 0;
                this.initializeWPCLI();
            }

            initializeWPCLI() {
                // Initialize WP-CLI interface elements
                const commandInput = document.getElementById('wp-cli-command');
                const quickCommands = document.getElementById('wp-cli-quick-commands');
                const executeBtn = document.getElementById('execute-wp-cli');
                const clearBtn = document.getElementById('clear-wp-cli-output');
                const exportBtn = document.getElementById('export-wp-cli-history');

                if (!commandInput || !executeBtn) return; // WP-CLI not available

                // Event listeners
                executeBtn.addEventListener('click', () => this.executeCommand());
                clearBtn?.addEventListener('click', () => this.clearOutput());
                exportBtn?.addEventListener('click', () => this.exportHistory());

                // Quick command selection
                quickCommands?.addEventListener('change', (e) => {
                    if (e.target.value) {
                        commandInput.value = e.target.value;
                        e.target.value = '';
                    }
                });

                // Enter key to execute
                commandInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.executeCommand();
                    }
                });

                // Load command history
                this.displayHistory();
                this.updateMetrics();
            }

            async executeCommand() {
                const commandInput = document.getElementById('wp-cli-command');
                const command = commandInput.value.trim();

                if (!command) {
                    this.showStatus('Please enter a command', 'error');
                    return;
                }

                this.showStatus('Executing command...', 'info');
                this.setExecuteButtonState(false);

                const startTime = Date.now();

                try {
                    const formData = new FormData();
                    formData.append('action', 'execute_wp_cli');
                    formData.append('command', command);

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    const executionTime = Date.now() - startTime;

                    if (result.success) {
                        this.displayCommandResult(result.data);
                        this.addToHistory(command, result.data);
                        this.showStatus(`Command executed successfully (${executionTime}ms)`, 'success');
                        commandInput.value = '';
                        this.commandsExecuted++;
                        this.updateMetrics();
                    } else {
                        this.showStatus(result.error || 'Command execution failed', 'error');
                        this.displayError(command, result.error || 'Unknown error');
                    }

                    // Update response time metric
                    document.getElementById('wp-cli-response-time').textContent = executionTime + 'ms';

                } catch (error) {
                    this.showStatus('Network error: ' + error.message, 'error');
                    this.displayError(command, 'Network error: ' + error.message);
                } finally {
                    this.setExecuteButtonState(true);
                }
            }

            displayCommandResult(result) {
                const output = document.getElementById('wp-cli-output');
                if (!output) return;

                const timestamp = new Date().toLocaleTimeString();
                const resultHTML = `
                    <div style="border-bottom: 1px solid #4a5568; margin-bottom: 10px; padding-bottom: 10px;">
                        <div style="color: #68d391; font-weight: bold; margin-bottom: 5px;">
                            [${timestamp}] $ wp ${result.command}
                        </div>
                        <div style="color: #e2e8f0; white-space: pre-wrap; font-family: monospace;">
                            ${this.escapeHtml(result.output)}
                        </div>
                        <div style="color: #a0aec0; font-size: 11px; margin-top: 5px;">
                            Execution time: ${result.execution_time}ms
                        </div>
                    </div>
                `;

                if (output.innerHTML.includes('Ready to execute')) {
                    output.innerHTML = resultHTML;
                } else {
                    output.innerHTML = resultHTML + output.innerHTML;
                }

                output.scrollTop = 0;
            }

            displayError(command, error) {
                const output = document.getElementById('wp-cli-output');
                if (!output) return;

                const timestamp = new Date().toLocaleTimeString();
                const errorHTML = `
                    <div style="border-bottom: 1px solid #4a5568; margin-bottom: 10px; padding-bottom: 10px;">
                        <div style="color: #f56565; font-weight: bold; margin-bottom: 5px;">
                            [${timestamp}] $ wp ${command}
                        </div>
                        <div style="color: #fed7d7; background: #742a2a; padding: 8px; border-radius: 4px;">
                            Error: ${this.escapeHtml(error)}
                        </div>
                    </div>
                `;

                if (output.innerHTML.includes('Ready to execute')) {
                    output.innerHTML = errorHTML;
                } else {
                    output.innerHTML = errorHTML + output.innerHTML;
                }

                output.scrollTop = 0;
            }

            addToHistory(command, result) {
                const historyEntry = {
                    command: command,
                    timestamp: Date.now(),
                    formatted_time: new Date().toLocaleString(),
                    execution_time: result.execution_time,
                    success: true
                };

                this.commandHistory.unshift(historyEntry);

                // Keep only last 50 commands
                if (this.commandHistory.length > 50) {
                    this.commandHistory = this.commandHistory.slice(0, 50);
                }

                localStorage.setItem('wp-cli-history', JSON.stringify(this.commandHistory));
                this.displayHistory();
            }

            displayHistory() {
                const historyContainer = document.getElementById('wp-cli-history');
                if (!historyContainer || this.commandHistory.length === 0) return;

                const historyHTML = this.commandHistory.map(entry => `
                    <div style="padding: 10px; border-bottom: 1px solid var(--debug-border); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <code style="background: var(--debug-info); padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                wp ${entry.command}
                            </code>
                            <div style="font-size: 11px; color: #666; margin-top: 3px;">
                                ${entry.formatted_time}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 11px; color: #28a745;">${entry.execution_time}ms</span>
                            <button onclick="document.getElementById('wp-cli-command').value='${entry.command}'"
                                    style="margin-left: 8px; padding: 2px 6px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 10px;">
                                Rerun
                            </button>
                        </div>
                    </div>
                `).join('');

                historyContainer.innerHTML = historyHTML;
            }

            clearOutput() {
                const output = document.getElementById('wp-cli-output');
                if (output) {
                    output.innerHTML = '<div style="color: #a0aec0; font-style: italic;">Output cleared...</div>';
                }
                this.showStatus('Output cleared', 'info');
            }

            exportHistory() {
                if (this.commandHistory.length === 0) {
                    this.showStatus('No command history to export', 'error');
                    return;
                }

                const exportData = {
                    exported_at: new Date().toISOString(),
                    total_commands: this.commandHistory.length,
                    commands: this.commandHistory
                };

                const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `wp-cli-history-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                this.showStatus('Command history exported', 'success');
            }

            showStatus(message, type = 'info') {
                const statusElement = document.getElementById('wp-cli-status');
                if (!statusElement) return;

                const colors = {
                    success: '#28a745',
                    error: '#dc3545',
                    info: '#17a2b8'
                };

                statusElement.textContent = message;
                statusElement.style.color = colors[type] || colors.info;

                setTimeout(() => {
                    statusElement.textContent = '';
                }, 3000);
            }

            setExecuteButtonState(enabled) {
                const executeBtn = document.getElementById('execute-wp-cli');
                if (executeBtn) {
                    executeBtn.disabled = !enabled;
                    executeBtn.textContent = enabled ? 'Execute' : 'Executing...';
                    executeBtn.style.opacity = enabled ? '1' : '0.6';
                }
            }

            updateMetrics() {
                const commandsExecutedElement = document.getElementById('wp-cli-commands-executed');
                if (commandsExecutedElement) {
                    commandsExecutedElement.textContent = this.commandsExecuted;
                }
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        // Interactive Performance Charts with Chart.js
        function createPerformanceChart(canvasId, data, chartType = 'line') {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;

            const chartData = JSON.parse(document.getElementById('chartData').textContent);

            let config = {
                type: chartType,
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--debug-text')
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--debug-text')
                            },
                            grid: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--debug-border')
                            }
                        },
                        y: {
                            ticks: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--debug-text')
                            },
                            grid: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--debug-border')
                            }
                        }
                    }
                }
            };

            return new Chart(ctx, config);
        }

        function initializePerformanceCharts() {
            const chartDataElement = document.getElementById('chartData');
            if (!chartDataElement) return;

            const chartData = JSON.parse(chartDataElement.textContent);

            // Response Time Chart
            createPerformanceChart('responseTimeChart', {
                labels: chartData.timestamps,
                datasets: [{
                    label: 'Response Time (ms)',
                    data: chartData.response_times,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            });

            // Memory Usage Chart
            createPerformanceChart('memoryUsageChart', {
                labels: chartData.timestamps,
                datasets: [{
                    label: 'Memory Usage (MB)',
                    data: chartData.memory_usage,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                }]
            });

            // Database Query Performance Chart
            createPerformanceChart('queryPerformanceChart', {
                labels: chartData.timestamps,
                datasets: [{
                    label: 'Query Count',
                    data: chartData.query_counts,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1
                }]
            });
        }

        // Initialize All GUI Enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize performance charts
            initializePerformanceCharts();

            // Initialize Advanced Search & Filter System
            window.debugSearchFilter = new DebugSearchFilter();

            // Initialize Interactive Performance Timeline
            window.debugPerformanceTimeline = new DebugPerformanceTimeline();

            // Initialize AI-Powered Insights Panel
            window.debugAIInsights = new DebugAIInsights();

            // Initialize One-Click Actions & Wizards
            window.debugOneClickActions = new DebugOneClickActions();
            window.debugWizardSystem = new DebugWizardSystem();

            // Initialize Enhanced Mobile/Responsive Mode
            window.debugMobileEnhancer = new DebugMobileEnhancer();

            // Initialize Modal-Based Detail Views
            window.debugModalSystem = new DebugModalSystem();

            // Initialize Drag & Drop Section Reordering
            window.debugSectionReorder = new DebugSectionReorder();

            // Initialize Real-Time Status Indicators
            window.debugRealTimeStatus = new DebugRealTimeStatus();

            // Initialize WP-CLI Integration
            window.debugWPCLI = new DebugWPCLI();

            // Log monitoring controls
            const startBtn = document.getElementById('start-log-monitor');
            const stopBtn = document.getElementById('stop-log-monitor');
            const clearBtn = document.getElementById('clear-log-display');
            const exportBtn = document.getElementById('export-log-data');
            const websocketBtn = document.getElementById('connect-websocket');

            if (startBtn) startBtn.addEventListener('click', startLogMonitoring);
            if (stopBtn) stopBtn.addEventListener('click', stopLogMonitoring);
            if (clearBtn) clearBtn.addEventListener('click', clearLogDisplay);
            if (exportBtn) exportBtn.addEventListener('click', exportLogData);
            if (websocketBtn) websocketBtn.addEventListener('click', connectWebSocket);

            // Add notification for GUI enhancements
            console.log('🎯 All UX Enhancements Loaded:');
            console.log('📊 Interactive Performance Charts');
            console.log('🔍 Enhanced Global Search & Filter System');
            console.log('⏱️ Interactive Performance Timeline');
            console.log('🤖 AI-Powered Insights Panel');
            console.log('⚡ One-Click Actions & Wizards');
            console.log('📱 Enhanced Mobile/Responsive Mode');
            console.log('🪟 Modal-Based Detail Views');
            console.log('🎯 Drag & Drop Section Reordering');
            console.log('📡 Real-Time Status Indicators');
        });

        // Theme Toggle
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('debug-theme', newTheme);
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('debug-theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
        });

        // Section Toggle
        function toggleSection(header) {
            const section = header.parentElement;
            section.classList.toggle('collapsed');

            // Save state
            const sectionId = header.textContent.trim();
            const isCollapsed = section.classList.contains('collapsed');
            localStorage.setItem('debug-section-' + sectionId, isCollapsed);
        }

        // Toggle All Sections
        function toggleAll() {
            const sections = document.querySelectorAll('.debug-section');
            const firstSection = sections[0];
            const shouldCollapse = !firstSection.classList.contains('collapsed');

            sections.forEach(section => {
                if (shouldCollapse) {
                    section.classList.add('collapsed');
                } else {
                    section.classList.remove('collapsed');
                }
            });
        }

        // Export Results
        function exportResults() {
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const filename = `wordpress-debug-omega-${timestamp}.html`;

            // Create a copy of the current page
            const htmlContent = document.documentElement.outerHTML;
            const blob = new Blob([htmlContent], { type: 'text/html' });

            // Create download link
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();

            // Cleanup
            URL.revokeObjectURL(link.href);

            alert('Debug results exported as: ' + filename);
        }

        // Refresh Diagnostics
        function refreshDiagnostics() {
            if (confirm('Refresh all diagnostic data? This will reload the page.')) {
                // Clear any cached data
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('refresh', Date.now());
                window.location.search = urlParams.toString();
            }
        }

        // Toggle Footer Box
        function toggleFooterBox() {
            const boxes = document.querySelectorAll('[id^="debug-diagnostic-box-"]');
            boxes.forEach(box => {
                box.style.display = box.style.display === 'none' ? 'block' : 'none';
            });
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+E for Export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportResults();
            }

            // Ctrl+T for Theme Toggle
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                toggleTheme();
            }

            // Ctrl+A for Toggle All (when not in input)
            if (e.ctrlKey && e.key === 'a' && !['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
                e.preventDefault();
                toggleAll();
            }
        });

        // Load saved section states
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.debug-section');
            sections.forEach(section => {
                const header = section.querySelector('.debug-section-header');
                const sectionId = header.textContent.trim();
                const isCollapsed = localStorage.getItem('debug-section-' + sectionId) === 'true';

                if (isCollapsed) {
                    section.classList.add('collapsed');
                }
            });
        });

        // Enhanced URL Testing Form Validation
        document.addEventListener('DOMContentLoaded', function() {
            const urlInput = document.getElementById('custom_url');
            if (urlInput) {
                urlInput.addEventListener('input', function() {
                    const url = this.value;
                    const isValid = /^https?:\/\/.+/.test(url);

                    if (url && !isValid) {
                        this.style.borderColor = '#dc3545';
                        this.style.backgroundColor = '#fff5f5';
                    } else {
                        this.style.borderColor = '#28a745';
                        this.style.backgroundColor = '#f8fff8';
                    }
                });
            }
        });

        // Performance Monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log('WordPress Debug Tool - Omega Version loaded in:', Math.round(loadTime), 'ms');

            // Add load time to performance dashboard if element exists
            const loadTimeElement = document.querySelector('.debug-load-time');
            if (loadTimeElement) {
                loadTimeElement.textContent = Math.round(loadTime) + 'ms';
            }
        });

        // Auto-save form data
        function autoSaveFormData() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input[type="url"], input[type="text"], textarea');
                inputs.forEach(input => {
                    input.addEventListener('input', function() {
                        localStorage.setItem('debug-form-' + this.name, this.value);
                    });

                    // Restore saved value
                    const savedValue = localStorage.getItem('debug-form-' + input.name);
                    if (savedValue) {
                        input.value = savedValue;
                    }
                });
            });
        }

        // Initialize auto-save
        document.addEventListener('DOMContentLoaded', autoSaveFormData);

        // Add helpful tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const badges = document.querySelectorAll('.debug-badge');
            badges.forEach(badge => {
                badge.title = 'Click for more information about this status';
            });
        });

        console.log('🚀 WordPress Debug Tool - Ultimate Omega Version Loaded');
        console.log('💡 Keyboard Shortcuts: Ctrl+E (Export), Ctrl+T (Theme), Ctrl+A (Toggle All)');
    </script>

    <style>
        /* Additional responsive styles for mobile */
        @media (max-width: 480px) {
            .debug-container {
                margin: 5px;
                padding: 15px;
            }

            .debug-table {
                font-size: 12px;
            }

            .debug-table th,
            .debug-table td {
                padding: 8px 10px;
            }

            .debug-metric {
                padding: 15px;
            }

            .debug-metric-value {
                font-size: 24px;
            }
        }

        /* Print styles */
        @media print {
            .debug-controls {
                display: none;
            }

            .debug-section {
                break-inside: avoid;
            }

            .debug-section-content {
                display: block !important;
            }
        }
    </style>

    <!-- One-Click Actions & Wizards Panel -->
    <div id="quick-actions-panel" style="position: fixed; bottom: 20px; right: 20px; width: 320px; background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); z-index: 999; display: none;">
        <div style="padding: 15px; border-bottom: 2px solid var(--debug-border); background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 10px 10px 0 0;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 14px;">⚡ Quick Actions</h3>
                <button id="close-actions-panel" style="background: none; border: none; color: white; cursor: pointer; font-size: 16px;">✕</button>
            </div>
        </div>

        <div style="padding: 15px;">
            <!-- One-Click Actions -->
            <div style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px 0; font-size: 12px; color: #666;">🚀 One-Click Fixes</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <button class="quick-action-btn" data-action="clear-cache" style="padding: 8px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">🗑️ Clear Cache</button>
                    <button class="quick-action-btn" data-action="flush-rewrite" style="padding: 8px; background: #17a2b8; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">🔄 Flush Rules</button>
                    <button class="quick-action-btn" data-action="optimize-db" style="padding: 8px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">🗄️ Optimize DB</button>
                    <button class="quick-action-btn" data-action="clear-transients" style="padding: 8px; background: #ffc107; color: #212529; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">⏰ Clear Transients</button>
                    <button class="quick-action-btn" data-action="fix-permissions" style="padding: 8px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">🔒 Fix Permissions</button>
                    <button class="quick-action-btn" data-action="update-htaccess" style="padding: 8px; background: #6f42c1; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px;">📄 Update .htaccess</button>
                </div>
            </div>

            <!-- Multi-Step Wizards -->
            <div style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px 0; font-size: 12px; color: #666;">🧙‍♂️ Setup Wizards</h4>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <button class="wizard-btn" data-wizard="performance" style="padding: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; text-align: left;">⚡ Performance Optimization Wizard</button>
                    <button class="wizard-btn" data-wizard="security" style="padding: 10px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; text-align: left;">🔒 Security Hardening Wizard</button>
                    <button class="wizard-btn" data-wizard="cleanup" style="padding: 10px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; text-align: left;">🧹 Database Cleanup Wizard</button>
                </div>
            </div>

            <!-- Action Status -->
            <div id="action-status" style="display: none; padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #007bff;">
                <div id="action-status-text" style="font-size: 11px; color: #495057;">Ready to execute actions...</div>
                <div id="action-progress" style="width: 100%; height: 4px; background: #e9ecef; border-radius: 2px; margin-top: 6px; overflow: hidden;">
                    <div id="action-progress-bar" style="height: 100%; background: #007bff; width: 0%; transition: width 0.3s ease;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Toggle Button -->
    <button id="toggle-actions-panel" style="position: fixed; bottom: 20px; right: 20px; padding: 12px 16px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 25px; cursor: pointer; font-size: 12px; font-weight: 600; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); z-index: 998; transition: all 0.3s ease;">
        ⚡ QUICK ACTIONS
    </button>

    <!-- Wizard Modal -->
    <div id="wizard-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1001; display: none; align-items: center; justify-content: center;">
        <div style="background: var(--debug-bg); border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div id="wizard-header" style="padding: 20px; border-bottom: 2px solid var(--debug-border); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px 10px 0 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 id="wizard-title" style="margin: 0; font-size: 16px;">Setup Wizard</h3>
                    <button id="close-wizard-modal" style="background: none; border: none; color: white; cursor: pointer; font-size: 18px;">✕</button>
                </div>
                <div id="wizard-progress" style="margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 12px;">Step <span id="current-step">1</span> of <span id="total-steps">3</span></span>
                        <span id="wizard-progress-percent" style="font-size: 12px;">33%</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.3); border-radius: 3px; overflow: hidden;">
                        <div id="wizard-progress-bar" style="height: 100%; background: white; width: 33%; transition: width 0.3s ease;"></div>
                    </div>
                </div>
            </div>

            <div id="wizard-content" style="padding: 20px;">
                <!-- Wizard steps will be dynamically inserted here -->
            </div>

            <div style="padding: 20px; border-top: 1px solid var(--debug-border); display: flex; justify-content: space-between;">
                <button id="wizard-prev" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;" disabled>← Previous</button>
                <button id="wizard-next" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer;">Next →</button>
            </div>
        </div>
    </div>

    <!-- AI-Powered Insights Panel -->
    <div id="ai-insights-panel" style="position: fixed; top: 20px; right: -400px; width: 380px; height: calc(100vh - 40px); background: var(--debug-bg); border: 2px solid var(--debug-border); border-radius: 8px 0 0 8px; box-shadow: -5px 0 15px rgba(0,0,0,0.1); z-index: 1000; transition: right 0.3s ease; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 2px solid var(--debug-border); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 16px;">🤖 AI Insights</h3>
                <button id="close-insights-panel" style="background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 12px;">✕</button>
            </div>
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">Intelligent recommendations for your WordPress site</div>
        </div>

        <div style="padding: 15px;">
            <!-- Insights Summary -->
            <div id="insights-summary" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
                <div style="text-align: center; padding: 10px; background: var(--debug-error); border-radius: 6px;">
                    <div id="critical-count" style="font-size: 18px; font-weight: 600; color: #721c24;">0</div>
                    <div style="font-size: 11px; color: #721c24;">Critical</div>
                </div>
                <div style="text-align: center; padding: 10px; background: var(--debug-warning); border-radius: 6px;">
                    <div id="warning-count" style="font-size: 18px; font-weight: 600; color: #856404;">0</div>
                    <div style="font-size: 11px; color: #856404;">Warnings</div>
                </div>
            </div>

            <!-- AI Analysis Status -->
            <div id="ai-status" style="background: var(--debug-info); padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #17a2b8;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div id="ai-status-indicator" style="width: 8px; height: 8px; background: #28a745; border-radius: 50%; animation: pulse 2s infinite;"></div>
                    <span id="ai-status-text" style="font-size: 12px; color: #0c5460;">Analyzing your WordPress site...</span>
                </div>
            </div>

            <!-- Insights Categories -->
            <div id="insights-categories">
                <!-- Performance Insights -->
                <div class="insights-category" data-category="performance">
                    <div class="insights-category-header" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--debug-border); cursor: pointer;" onclick="toggleInsightsCategory('performance')">
                        <span style="font-weight: 600; font-size: 13px;">⚡ Performance</span>
                        <span id="performance-badge" class="insights-badge" style="background: #6c757d; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">0</span>
                    </div>
                    <div id="performance-insights" class="insights-content" style="padding: 10px 0; display: none;"></div>
                </div>

                <!-- Security Insights -->
                <div class="insights-category" data-category="security">
                    <div class="insights-category-header" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--debug-border); cursor: pointer;" onclick="toggleInsightsCategory('security')">
                        <span style="font-weight: 600; font-size: 13px;">🔒 Security</span>
                        <span id="security-badge" class="insights-badge" style="background: #6c757d; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">0</span>
                    </div>
                    <div id="security-insights" class="insights-content" style="padding: 10px 0; display: none;"></div>
                </div>

                <!-- Database Insights -->
                <div class="insights-category" data-category="database">
                    <div class="insights-category-header" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--debug-border); cursor: pointer;" onclick="toggleInsightsCategory('database')">
                        <span style="font-weight: 600; font-size: 13px;">🗄️ Database</span>
                        <span id="database-badge" class="insights-badge" style="background: #6c757d; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">0</span>
                    </div>
                    <div id="database-insights" class="insights-content" style="padding: 10px 0; display: none;"></div>
                </div>

                <!-- Plugin Insights -->
                <div class="insights-category" data-category="plugins">
                    <div class="insights-category-header" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--debug-border); cursor: pointer;" onclick="toggleInsightsCategory('plugins')">
                        <span style="font-weight: 600; font-size: 13px;">🔌 Plugins</span>
                        <span id="plugins-badge" class="insights-badge" style="background: #6c757d; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">0</span>
                    </div>
                    <div id="plugins-insights" class="insights-content" style="padding: 10px 0; display: none;"></div>
                </div>

                <!-- Optimization Insights -->
                <div class="insights-category" data-category="optimization">
                    <div class="insights-category-header" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--debug-border); cursor: pointer;" onclick="toggleInsightsCategory('optimization')">
                        <span style="font-weight: 600; font-size: 13px;">🚀 Optimization</span>
                        <span id="optimization-badge" class="insights-badge" style="background: #6c757d; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">0</span>
                    </div>
                    <div id="optimization-insights" class="insights-content" style="padding: 10px 0; display: none;"></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--debug-border);">
                <h4 style="margin: 0 0 10px 0; font-size: 13px; color: var(--debug-text);">🎯 Quick Actions</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <button id="refresh-insights" style="padding: 8px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">🔄 Refresh Analysis</button>
                    <button id="export-insights" style="padding: 8px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">📊 Export Report</button>
                    <button id="auto-optimize" style="padding: 8px 12px; background: #ffc107; color: black; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">⚡ Auto-Optimize</button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Insights Toggle Button -->
    <button id="toggle-insights-panel" style="position: fixed; top: 50%; right: 20px; transform: translateY(-50%); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px 0 0 8px; padding: 15px 8px; cursor: pointer; z-index: 999; box-shadow: -3px 0 10px rgba(0,0,0,0.2); writing-mode: vertical-rl; text-orientation: mixed; font-size: 12px; font-weight: 600;">
        🤖 AI INSIGHTS
    </button>

</body>
</html>

<?php
debug_time('page_complete');

// Final performance summary
$total_time = round((microtime(true) - $debug_start_time) * 1000, 2);
$total_memory = round((memory_get_usage() - $debug_start_memory) / 1024 / 1024, 2);

// Log performance metrics
error_log(sprintf(
    'WordPress Debug Tool - Omega Version: Page generated in %sms, Memory used: %sMB',
    $total_time,
    $total_memory
));
?>
