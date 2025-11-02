<?php
/**
 * WordPress Debug Tool - Advanced Version
 * Comprehensive diagnostics between medium and full versions
 * 
 * Upload and access: https://mytech.today/debug-advanced.php
 */

// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Advanced performance timing with memory tracking
$debug_start_time = microtime(true);
$debug_start_memory = memory_get_usage();
$debug_timings = [];
$debug_memory_usage = [];

function debug_time($label) {
    global $debug_timings, $debug_start_time, $debug_memory_usage, $debug_start_memory;
    $debug_timings[$label] = round((microtime(true) - $debug_start_time) * 1000, 2);
    $debug_memory_usage[$label] = round((memory_get_usage() - $debug_start_memory) / 1024 / 1024, 2);
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

// Enhanced global variables for comprehensive tracking
global $debug_has_the_content, $debug_content_filters, $debug_hooks_called, $debug_queries;
$debug_has_the_content = false;
$debug_content_filters = [];
$debug_hooks_called = [];
$debug_queries = [];

// Advanced content detection with filter chain tracking
add_filter('the_content', function($content) {
    global $debug_has_the_content, $debug_content_filters;
    $debug_has_the_content = true;
    $debug_content_filters[] = [
        'time' => date('H:i:s.u'),
        'content_length' => strlen($content),
        'filter' => 'the_content',
        'priority' => current_filter()
    ];
    return $content;
}, 1);

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

// Plugin conflict testing functionality
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

// Database connection testing
$db_status = [];
try {
    global $wpdb;
    $db_status['connection'] = $wpdb->check_connection() ? 'Connected' : 'Failed';
    $db_status['version'] = $wpdb->get_var("SELECT VERSION()");
    $db_status['charset'] = $wpdb->charset;
    $db_status['collate'] = $wpdb->collate;
    $db_status['prefix'] = $wpdb->prefix;
    
    // Test query performance
    $query_start = microtime(true);
    $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} LIMIT 5");
    $db_status['query_time'] = round((microtime(true) - $query_start) * 1000, 2);
} catch (Exception $e) {
    $db_status['error'] = $e->getMessage();
}

debug_time('database_tested');

// WordPress configuration analysis
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
];

debug_time('config_analyzed');

// Enhanced cURL and HTTP diagnostics with caching
$curl_diagnostics = [];
$cache_key = 'debug_advanced_curl_' . md5($_SERVER['REQUEST_URI']);
$cached_curl = get_transient($cache_key);

if ($cached_curl === false) {
    // cURL extension analysis
    if (extension_loaded('curl')) {
        $curl_version = curl_version();
        $curl_diagnostics['status'] = 'Loaded';
        $curl_diagnostics['version'] = $curl_version['version'];
        $curl_diagnostics['ssl_support'] = ($curl_version['features'] & CURL_VERSION_SSL) ? 'Yes' : 'No';
        $curl_diagnostics['protocols'] = implode(', ', $curl_version['protocols']);
    } else {
        $curl_diagnostics['status'] = 'Not loaded';
    }
    
    // External HTTP test with detailed analysis
    $test_url = 'https://api.wordpress.org/core/version-check/1.7/';
    $http_start = microtime(true);
    $response = wp_remote_get($test_url, ['timeout' => 10, 'sslverify' => true]);
    $http_time = round((microtime(true) - $http_start) * 1000, 2);
    
    if (is_wp_error($response)) {
        $curl_diagnostics['external_test'] = [
            'status' => 'Failed',
            'error' => $response->get_error_message(),
            'time' => $http_time . 'ms'
        ];
    } else {
        $curl_diagnostics['external_test'] = [
            'status' => 'Success',
            'code' => wp_remote_retrieve_response_code($response),
            'time' => $http_time . 'ms',
            'headers' => wp_remote_retrieve_headers($response)->getAll()
        ];
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
    
    // SSL certificate test
    $ssl_start = microtime(true);
    $ssl_response = wp_remote_get('https://httpbin.org/get', ['timeout' => 10, 'sslverify' => false]);
    $ssl_time = round((microtime(true) - $ssl_start) * 1000, 2);
    
    if (is_wp_error($ssl_response)) {
        $curl_diagnostics['ssl_test'] = [
            'status' => 'Failed',
            'error' => $ssl_response->get_error_message(),
            'time' => $ssl_time . 'ms'
        ];
    } else {
        $curl_diagnostics['ssl_test'] = [
            'status' => 'Success',
            'code' => wp_remote_retrieve_response_code($ssl_response),
            'time' => $ssl_time . 'ms'
        ];
    }
    
    set_transient($cache_key, $curl_diagnostics, 300); // Cache for 5 minutes
} else {
    $curl_diagnostics = $cached_curl;
}

debug_time('curl_diagnostics');

// Error log analysis
$error_log_analysis = [];
if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file) && is_readable($log_file)) {
        $log_size = filesize($log_file);
        $error_log_analysis['file_size'] = round($log_size / 1024, 2) . ' KB';
        $error_log_analysis['last_modified'] = date('Y-m-d H:i:s', filemtime($log_file));
        
        // Read last 50 lines for recent errors
        $lines = [];
        $handle = fopen($log_file, 'r');
        if ($handle) {
            fseek($handle, max(0, $log_size - 8192)); // Read last 8KB
            $content = fread($handle, 8192);
            fclose($handle);
            
            $lines = explode("\n", $content);
            $lines = array_filter($lines);
            $lines = array_slice($lines, -20); // Last 20 lines
            
            $error_log_analysis['recent_entries'] = count($lines);
            $error_log_analysis['sample_lines'] = array_slice($lines, -5); // Last 5 lines
        }
    }
}

debug_time('error_log_analyzed');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Debug Tool - Advanced Version</title>
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
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 15px;
            background: var(--debug-bg);
            color: var(--debug-text);
            line-height: 1.6;
            transition: all 0.3s ease;
        }
        
        .debug-container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--debug-bg);
            border: 2px solid var(--debug-border);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .debug-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--debug-primary);
            background: linear-gradient(135deg, var(--debug-primary)10, transparent);
            border-radius: 8px;
            padding: 20px;
        }
        
        .debug-title {
            margin: 0;
            color: var(--debug-primary);
            font-size: 28px;
            font-weight: 700;
        }
        
        .debug-controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .debug-btn {
            background: var(--debug-primary);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .debug-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .debug-btn.secondary {
            background: var(--debug-secondary);
        }
        
        .debug-btn.success {
            background: var(--debug-accent);
        }
        
        .debug-section {
            margin: 25px 0;
            border: 2px solid var(--debug-border);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .debug-section:hover {
            border-color: var(--debug-primary);
        }
        
        .debug-section-header {
            background: linear-gradient(135deg, var(--debug-info), var(--debug-primary));
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .debug-section-header:hover {
            background: linear-gradient(135deg, var(--debug-primary), var(--debug-info));
        }
        
        .debug-section-content {
            padding: 20px;
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
            padding: 12px 16px;
            border-radius: 6px;
            margin: 12px 0;
            border-left: 4px solid;
            font-weight: 500;
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
            border: 1px solid var(--debug-border);
            padding: 15px;
            border-radius: 6px;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 12px 0;
            line-height: 1.5;
        }
        
        .debug-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .debug-metric {
            background: var(--debug-bg);
            border: 2px solid var(--debug-border);
            padding: 20px;
            border-radius: 8px;
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
            height: 4px;
            background: linear-gradient(90deg, var(--debug-primary), var(--debug-accent));
        }
        
        .debug-metric:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        
        .debug-metric-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--debug-primary);
            margin-bottom: 8px;
        }
        
        .debug-metric-label {
            font-size: 14px;
            color: var(--debug-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .debug-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: var(--debug-bg);
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .debug-table th,
        .debug-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--debug-border);
        }
        
        .debug-table th {
            background: var(--debug-info);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .debug-table tr:hover {
            background: rgba(0,123,186,0.05);
        }
        
        .debug-progress {
            background: var(--debug-border);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin: 8px 0;
        }
        
        .debug-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--debug-primary), var(--debug-accent));
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .debug-container { padding: 15px; }
            .debug-header { 
                flex-direction: column; 
                gap: 15px; 
                text-align: center;
            }
            .debug-controls { 
                justify-content: center;
            }
            .debug-grid {
                grid-template-columns: 1fr;
            }
            .debug-title {
                font-size: 24px;
            }
        }
        
        .debug-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
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
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <h1 class="debug-title">🚀 WordPress Debug Tool - Advanced</h1>
            <div class="debug-controls">
                <button class="debug-btn" onclick="toggleTheme()">🌓 Theme</button>
                <button class="debug-btn secondary" onclick="toggleAll()">📦 Toggle All</button>
                <button class="debug-btn success" onclick="exportResults()">💾 Export</button>
                <button class="debug-btn" onclick="refreshDiagnostics()">🔄 Refresh</button>
            </div>
        </div>

        <!-- Advanced Performance Dashboard -->
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
        </div>

        <!-- WordPress Configuration Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⚙️ WordPress Configuration & Environment
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
                            <strong>WP Load Path:</strong> <?php echo $wp_load_path_used; ?>
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
                    echo '<strong>Capabilities:</strong> ' . (current_user_can('manage_options') ? 'Administrator' : 'Limited');
                    echo '</div>';
                } else {
                    echo '<div class="debug-warning">';
                    echo '<strong>👤 User Status:</strong> Not logged in. <a href="' . wp_login_url() . '">Login here</a>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Database Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🗄️ Database Analysis & Performance
            </div>
            <div class="debug-section-content">
                <div class="debug-grid">
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $db_status['connection'] ?? 'Unknown'; ?></div>
                        <div class="debug-metric-label">Connection Status</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $db_status['query_time'] ?? '0'; ?>ms</div>
                        <div class="debug-metric-label">Query Time</div>
                    </div>
                    <div class="debug-metric">
                        <div class="debug-metric-value"><?php echo $db_status['version'] ?? 'Unknown'; ?></div>
                        <div class="debug-metric-label">MySQL Version</div>
                    </div>
                </div>

                <div class="debug-code">
                    <strong>Database Host:</strong> <?php echo DB_HOST; ?><br>
                    <strong>Database Name:</strong> <?php echo DB_NAME; ?><br>
                    <strong>Table Prefix:</strong> <?php echo $db_status['prefix'] ?? 'Unknown'; ?><br>
                    <strong>Charset:</strong> <?php echo $db_status['charset'] ?? 'Unknown'; ?><br>
                    <strong>Collation:</strong> <?php echo $db_status['collate'] ?? 'Unknown'; ?><br>
                    <?php if (isset($db_status['error'])): ?>
                    <strong style="color: #dc3545;">Error:</strong> <?php echo esc_html($db_status['error']); ?>
                    <?php endif; ?>
                </div>

                <?php
                // Table analysis
                global $wpdb;
                $tables = $wpdb->get_results("SHOW TABLE STATUS LIKE '{$wpdb->prefix}%'");
                if ($tables) {
                    echo '<h4>📋 Database Tables</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Table</th><th>Rows</th><th>Size</th><th>Engine</th></tr></thead>';
                    echo '<tbody>';
                    foreach (array_slice($tables, 0, 10) as $table) {
                        $size = round(($table->Data_length + $table->Index_length) / 1024 / 1024, 2);
                        echo '<tr>';
                        echo '<td>' . esc_html($table->Name) . '</td>';
                        echo '<td>' . number_format($table->Rows) . '</td>';
                        echo '<td>' . $size . ' MB</td>';
                        echo '<td>' . esc_html($table->Engine) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    if (count($tables) > 10) {
                        echo '<p><em>Showing first 10 tables. Total: ' . count($tables) . ' tables.</em></p>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Enhanced Content Detection -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📄 Advanced Content Detection & Template Analysis
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('content_analysis_start');

                if ($current_post) {
                    echo '<div class="debug-info">';
                    echo '<strong>🎯 Analyzing Page:</strong> ' . esc_html($current_post->post_title) . ' (ID: ' . $current_post->ID . ')<br>';
                    echo '<strong>📄 Template:</strong> ' . ($current_template ?: 'Default template') . '<br>';
                    echo '<strong>📝 Post Type:</strong> ' . $current_post->post_type . '<br>';
                    echo '<strong>📊 Content Length:</strong> ' . strlen($current_post->post_content) . ' characters';
                    echo '</div>';

                    // Process content to trigger filters
                    $content = apply_filters('the_content', $current_post->post_content);

                    // Shortcode analysis
                    global $shortcode_tags;
                    $found_shortcodes = [];
                    if (!empty($current_post->post_content)) {
                        foreach (array_keys($shortcode_tags) as $shortcode) {
                            if (strpos($current_post->post_content, '[' . $shortcode) !== false) {
                                $found_shortcodes[] = $shortcode;
                            }
                        }
                    }
                } else {
                    echo '<div class="debug-warning">';
                    echo '<strong>⚠️ No specific page selected.</strong> Add ?page_id=123 to test a specific page.';
                    echo '</div>';
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
                echo '<div class="debug-metric-value">' . count($found_shortcodes ?? []) . '</div>';
                echo '<div class="debug-metric-label">Shortcodes Found</div>';
                echo '</div>';
                echo '</div>';

                if (!empty($debug_content_filters)) {
                    echo '<h4>🔄 Content Filter Timeline</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Time</th><th>Filter</th><th>Content Length</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($debug_content_filters as $filter) {
                        echo '<tr>';
                        echo '<td>' . esc_html($filter['time']) . '</td>';
                        echo '<td>' . esc_html($filter['filter']) . '</td>';
                        echo '<td>' . number_format($filter['content_length']) . ' chars</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }

                if (!empty($found_shortcodes)) {
                    echo '<div class="debug-success">';
                    echo '<strong>✅ Shortcodes in content:</strong> [' . implode('], [', $found_shortcodes) . ']';
                    echo '</div>';
                }

                debug_time('content_analysis_end');
                ?>
            </div>
        </div>

        <!-- Advanced Plugin Analysis with Conflict Testing -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔌 Advanced Plugin Analysis & Conflict Testing
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('plugin_analysis_start');

                $active_plugins = get_option('active_plugins', []);
                $all_plugins = get_plugins();

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
                echo '<div class="debug-metric-value">' . count($disabled_plugins) . '</div>';
                echo '<div class="debug-metric-label">Disabled (Test)</div>';
                echo '</div>';
                echo '</div>';

                if (!empty($active_plugins)) {
                    echo '<h4>🟢 Active Plugins</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Plugin</th><th>Version</th><th>File</th><th>Actions</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($active_plugins as $plugin_file) {
                        if (in_array($plugin_file, $disabled_plugins)) continue;

                        $plugin_data = $all_plugins[$plugin_file] ?? null;
                        if ($plugin_data) {
                            echo '<tr>';
                            echo '<td><strong>' . esc_html($plugin_data['Name']) . '</strong></td>';
                            echo '<td>' . esc_html($plugin_data['Version']) . '</td>';
                            echo '<td><code>' . esc_html($plugin_file) . '</code></td>';
                            echo '<td>';
                            echo '<a href="' . add_query_arg('debug_disable_plugins', $plugin_file) . '" class="debug-btn" style="font-size: 11px; padding: 4px 8px;">Test Disable</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</tbody></table>';

                    // Plugin conflict testing instructions
                    echo '<div class="debug-info">';
                    echo '<strong>🧪 Plugin Conflict Testing:</strong><br>';
                    echo '• Click "Test Disable" to temporarily disable a plugin for this request<br>';
                    echo '• Use URL parameter: <code>?debug_disable_plugins=plugin1.php,plugin2.php</code><br>';
                    echo '• This helps identify plugin conflicts without affecting your site';
                    echo '</div>';
                }

                // Plugin load order analysis
                echo '<h4>📊 Plugin Load Analysis</h4>';
                echo '<div class="debug-code">';
                echo '<strong>Plugin Load Order:</strong><br>';
                foreach ($active_plugins as $index => $plugin_file) {
                    if (in_array($plugin_file, $disabled_plugins)) {
                        echo ($index + 1) . '. <span style="color: #dc3545; text-decoration: line-through;">' . esc_html($plugin_file) . ' (DISABLED)</span><br>';
                    } else {
                        echo ($index + 1) . '. ' . esc_html($plugin_file) . '<br>';
                    }
                }
                echo '</div>';

                debug_time('plugin_analysis_end');
                ?>
            </div>
        </div>

        <!-- WordPress Hooks & Filters Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🪝 WordPress Hooks & Filters Analysis
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
                echo '</div>';

                // Most called hooks
                if (!empty($debug_hooks_called)) {
                    arsort($debug_hooks_called);
                    $top_hooks = array_slice($debug_hooks_called, 0, 15, true);

                    echo '<h4>🔥 Most Called Hooks</h4>';
                    echo '<table class="debug-table">';
                    echo '<thead><tr><th>Hook Name</th><th>Call Count</th><th>Type</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($top_hooks as $hook => $count) {
                        $hook_type = 'Action';
                        if (strpos($hook, '_filter') !== false || in_array($hook, ['the_content', 'the_title', 'the_excerpt'])) {
                            $hook_type = 'Filter';
                        }

                        echo '<tr>';
                        echo '<td><code>' . esc_html($hook) . '</code></td>';
                        echo '<td>' . $count . '</td>';
                        echo '<td>';
                        if ($hook_type === 'Filter') {
                            echo '<span class="debug-badge success">Filter</span>';
                        } else {
                            echo '<span class="debug-badge warning">Action</span>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }

                debug_time('hooks_analysis_end');
                ?>
            </div>
        </div>

        <!-- Enhanced HTTP & cURL Diagnostics -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🌐 Enhanced HTTP & cURL Diagnostics
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
                echo '<div class="debug-metric-value">' . ($curl_diagnostics['external_test']['time'] ?? '0ms') . '</div>';
                echo '<div class="debug-metric-label">External Test</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($curl_diagnostics['loopback_test']['time'] ?? '0ms') . '</div>';
                echo '<div class="debug-metric-label">Loopback Test</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . ($curl_diagnostics['ssl_test']['time'] ?? '0ms') . '</div>';
                echo '<div class="debug-metric-label">SSL Test</div>';
                echo '</div>';
                echo '</div>';

                // cURL Configuration
                echo '<h4>🔧 cURL Configuration</h4>';
                echo '<div class="debug-code">';
                if (isset($curl_diagnostics['version'])) {
                    echo '<strong>cURL Version:</strong> ' . esc_html($curl_diagnostics['version']) . '<br>';
                    echo '<strong>SSL Support:</strong> ' . esc_html($curl_diagnostics['ssl_support']) . '<br>';
                    echo '<strong>Protocols:</strong> ' . esc_html($curl_diagnostics['protocols']) . '<br>';
                } else {
                    echo '<strong>cURL Status:</strong> Not available<br>';
                }
                echo '<strong>PHP Timeout:</strong> ' . ini_get('default_socket_timeout') . ' seconds<br>';
                echo '<strong>Max Execution:</strong> ' . ini_get('max_execution_time') . ' seconds<br>';
                echo '<strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '<br>';
                echo '</div>';

                // HTTP Test Results
                echo '<h4>🧪 HTTP Test Results</h4>';
                echo '<table class="debug-table">';
                echo '<thead><tr><th>Test</th><th>Status</th><th>Time</th><th>Details</th></tr></thead>';
                echo '<tbody>';

                // External test
                $external = $curl_diagnostics['external_test'] ?? [];
                echo '<tr>';
                echo '<td><strong>External HTTP</strong><br><small>api.wordpress.org</small></td>';
                echo '<td>';
                if (($external['status'] ?? '') === 'Success') {
                    echo '<span class="debug-badge success">Success</span>';
                } else {
                    echo '<span class="debug-badge error">Failed</span>';
                }
                echo '</td>';
                echo '<td>' . esc_html($external['time'] ?? '0ms') . '</td>';
                echo '<td>';
                if (isset($external['code'])) {
                    echo 'HTTP ' . $external['code'];
                } elseif (isset($external['error'])) {
                    echo esc_html($external['error']);
                }
                echo '</td>';
                echo '</tr>';

                // Loopback test
                $loopback = $curl_diagnostics['loopback_test'] ?? [];
                echo '<tr>';
                echo '<td><strong>Loopback</strong><br><small>' . home_url() . '</small></td>';
                echo '<td>';
                if (($loopback['status'] ?? '') === 'Success') {
                    echo '<span class="debug-badge success">Success</span>';
                } else {
                    echo '<span class="debug-badge error">Failed</span>';
                }
                echo '</td>';
                echo '<td>' . esc_html($loopback['time'] ?? '0ms') . '</td>';
                echo '<td>';
                if (isset($loopback['code'])) {
                    echo 'HTTP ' . $loopback['code'];
                } elseif (isset($loopback['error'])) {
                    echo esc_html($loopback['error']);
                }
                echo '</td>';
                echo '</tr>';

                // SSL test
                $ssl = $curl_diagnostics['ssl_test'] ?? [];
                echo '<tr>';
                echo '<td><strong>SSL Test</strong><br><small>httpbin.org</small></td>';
                echo '<td>';
                if (($ssl['status'] ?? '') === 'Success') {
                    echo '<span class="debug-badge success">Success</span>';
                } else {
                    echo '<span class="debug-badge error">Failed</span>';
                }
                echo '</td>';
                echo '<td>' . esc_html($ssl['time'] ?? '0ms') . '</td>';
                echo '<td>';
                if (isset($ssl['code'])) {
                    echo 'HTTP ' . $ssl['code'];
                } elseif (isset($ssl['error'])) {
                    echo esc_html($ssl['error']);
                }
                echo '</td>';
                echo '</tr>';

                echo '</tbody></table>';

                // HTTP Headers from external test
                if (isset($curl_diagnostics['external_test']['headers']) && is_array($curl_diagnostics['external_test']['headers'])) {
                    echo '<h4>📋 Response Headers (External Test)</h4>';
                    echo '<div class="debug-code" style="max-height: 200px; overflow-y: auto;">';
                    foreach ($curl_diagnostics['external_test']['headers'] as $header => $value) {
                        if (is_array($value)) {
                            $value = implode(', ', $value);
                        }
                        echo '<strong>' . esc_html($header) . ':</strong> ' . esc_html($value) . '<br>';
                    }
                    echo '</div>';
                }

                debug_time('curl_analysis_end');
                ?>
            </div>
        </div>

        <!-- Error Log Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📝 Error Log Analysis & Recent Issues
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
                    echo '<div class="debug-metric-value">' . ($error_log_analysis['last_modified'] ?? 'Unknown') . '</div>';
                    echo '<div class="debug-metric-label">Last Modified</div>';
                    echo '</div>';
                    echo '</div>';

                    if (!empty($error_log_analysis['sample_lines'])) {
                        echo '<h4>🔍 Recent Error Log Entries</h4>';
                        echo '<div class="debug-code" style="max-height: 300px; overflow-y: auto; font-size: 12px;">';
                        foreach ($error_log_analysis['sample_lines'] as $line) {
                            $line = trim($line);
                            if (!empty($line)) {
                                // Color code different types of log entries
                                if (strpos($line, 'FATAL') !== false || strpos($line, 'Fatal') !== false) {
                                    echo '<span style="color: #dc3545; font-weight: bold;">' . esc_html($line) . '</span><br>';
                                } elseif (strpos($line, 'ERROR') !== false || strpos($line, 'Error') !== false) {
                                    echo '<span style="color: #e74c3c;">' . esc_html($line) . '</span><br>';
                                } elseif (strpos($line, 'WARNING') !== false || strpos($line, 'Warning') !== false) {
                                    echo '<span style="color: #f39c12;">' . esc_html($line) . '</span><br>';
                                } else {
                                    echo esc_html($line) . '<br>';
                                }
                            }
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<div class="debug-info">';
                    echo '<strong>ℹ️ Error Logging Status:</strong><br>';
                    if (!defined('WP_DEBUG') || !WP_DEBUG) {
                        echo '• WP_DEBUG is not enabled<br>';
                    }
                    if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
                        echo '• WP_DEBUG_LOG is not enabled<br>';
                    }
                    echo '• No error log file found or accessible<br>';
                    echo '• To enable error logging, add these to wp-config.php:<br>';
                    echo '<code>define(\'WP_DEBUG\', true);<br>define(\'WP_DEBUG_LOG\', true);</code>';
                    echo '</div>';
                }

                debug_time('error_analysis_end');
                ?>
            </div>
        </div>

        <!-- Advanced Performance Summary -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⚡ Advanced Performance Summary & Memory Analysis
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

                // Performance breakdown
                echo '<h4>⏱️ Performance Breakdown</h4>';
                echo '<table class="debug-table">';
                echo '<thead><tr><th>Operation</th><th>Time (ms)</th><th>Memory (MB)</th><th>Percentage</th></tr></thead>';
                echo '<tbody>';
                foreach ($debug_timings as $label => $time) {
                    $clean_label = ucwords(str_replace('_', ' ', $label));
                    $memory = $debug_memory_usage[$label] ?? 0;
                    $percentage = $total_time > 0 ? round(($time / $total_time) * 100, 1) : 0;

                    echo '<tr>';
                    echo '<td>' . esc_html($clean_label) . '</td>';
                    echo '<td>' . $time . '</td>';
                    echo '<td>' . $memory . '</td>';
                    echo '<td>';
                    echo '<div class="debug-progress">';
                    echo '<div class="debug-progress-bar" style="width: ' . min($percentage, 100) . '%"></div>';
                    echo '</div>';
                    echo $percentage . '%';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';

                // Performance recommendations
                echo '<div class="debug-info">';
                echo '<strong>🎯 Performance Recommendations:</strong><br>';
                if ($total_time > 1000) {
                    echo '• ⚠️ Total execution time is high (' . $total_time . 'ms)<br>';
                }
                if (memory_get_peak_usage() > 64 * 1024 * 1024) {
                    echo '• ⚠️ High memory usage detected<br>';
                }
                if (count($debug_hooks_called) > 500) {
                    echo '• ⚠️ Many hooks fired - consider plugin optimization<br>';
                }
                if (count(get_option('active_plugins', [])) > 20) {
                    echo '• ⚠️ Many active plugins - consider deactivating unused ones<br>';
                }
                echo '• ✅ WordPress loaded in ' . ($debug_timings['wordpress_loaded'] ?? 0) . 'ms<br>';
                echo '• ✅ Database connection: ' . ($db_status['connection'] ?? 'Unknown');
                echo '</div>';
                ?>
            </div>
        </div>

        <!-- Footer with Navigation -->
        <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 3px solid var(--debug-primary); background: linear-gradient(135deg, var(--debug-info)10, transparent); border-radius: 8px; padding: 30px;">
            <h3 style="color: var(--debug-primary); margin-bottom: 20px;">🚀 WordPress Debug Tool - Advanced Version</h3>
            <p style="margin-bottom: 20px; color: var(--debug-secondary);">
                Comprehensive WordPress diagnostics with advanced features, plugin conflict testing, and performance analysis
            </p>
            <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <a href="<?php echo home_url(); ?>" class="debug-btn">🏠 Return to Site</a>
                <a href="debug-minimal.php" class="debug-btn secondary">📱 Minimal Version</a>
                <a href="debug-medium.php" class="debug-btn secondary">⚖️ Medium Version</a>
                <a href="debug.php" class="debug-btn secondary">🔧 Full Version</a>
                <a href="?refresh=1" class="debug-btn success">🔄 Refresh Diagnostics</a>
            </div>
            <p style="margin-top: 20px; font-size: 12px; color: var(--debug-secondary);">
                <strong>Keyboard Shortcuts:</strong> Ctrl+D (Toggle All) | Ctrl+E (Export) | Ctrl+T (Theme Toggle)
            </p>
        </div>
    </div>

    <script>
        // Enhanced theme toggle with system preference detection
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('debug-advanced-theme', newTheme);

            // Update button text
            const themeBtn = document.querySelector('button[onclick="toggleTheme()"]');
            if (themeBtn) {
                themeBtn.textContent = newTheme === 'dark' ? '☀️ Light' : '🌓 Dark';
            }
        }

        // Enhanced section toggle with animation
        function toggleSection(header) {
            const section = header.parentElement;
            const content = section.querySelector('.debug-section-content');

            if (section.classList.contains('collapsed')) {
                section.classList.remove('collapsed');
                content.style.maxHeight = content.scrollHeight + 'px';
            } else {
                section.classList.add('collapsed');
                content.style.maxHeight = '0px';
            }
        }

        // Toggle all sections with smart behavior
        function toggleAll() {
            const sections = document.querySelectorAll('.debug-section');
            const allCollapsed = Array.from(sections).every(s => s.classList.contains('collapsed'));

            sections.forEach(section => {
                const content = section.querySelector('.debug-section-content');
                if (allCollapsed) {
                    section.classList.remove('collapsed');
                    content.style.maxHeight = content.scrollHeight + 'px';
                } else {
                    section.classList.add('collapsed');
                    content.style.maxHeight = '0px';
                }
            });
        }

        // Enhanced export with comprehensive data
        function exportResults() {
            const data = {
                timestamp: new Date().toISOString(),
                tool_version: 'debug-advanced.php',
                site_info: {
                    url: '<?php echo home_url(); ?>',
                    wordpress_version: '<?php echo get_bloginfo("version"); ?>',
                    php_version: '<?php echo PHP_VERSION; ?>',
                    theme: '<?php echo wp_get_theme()->get("Name"); ?>',
                    multisite: <?php echo is_multisite() ? 'true' : 'false'; ?>
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
                    total_plugins: <?php echo count(get_plugins()); ?>
                },
                content_analysis: {
                    the_content_called: <?php echo $debug_has_the_content ? 'true' : 'false'; ?>,
                    filter_calls: <?php echo count($debug_content_filters); ?>,
                    content_filters: <?php echo json_encode($debug_content_filters); ?>
                },
                hooks_analysis: {
                    unique_hooks: <?php echo count($debug_hooks_called); ?>,
                    total_hook_calls: <?php echo array_sum($debug_hooks_called); ?>,
                    top_hooks: <?php echo json_encode(array_slice($debug_hooks_called, 0, 10, true)); ?>
                },
                database: <?php echo json_encode($db_status); ?>,
                curl_diagnostics: <?php echo json_encode($curl_diagnostics); ?>,
                wp_config: <?php echo json_encode($wp_config); ?>,
                error_log: <?php echo json_encode($error_log_analysis); ?>
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'debug-advanced-results-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            // Show success message
            const btn = document.querySelector('button[onclick="exportResults()"]');
            const originalText = btn.textContent;
            btn.textContent = '✅ Exported!';
            btn.style.background = '#28a745';
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
            }, 2000);
        }

        // Refresh diagnostics
        function refreshDiagnostics() {
            const url = new URL(window.location);
            url.searchParams.set('refresh', Date.now());
            window.location.href = url.toString();
        }

        // Load saved theme with system preference fallback
        function initializeTheme() {
            const savedTheme = localStorage.getItem('debug-advanced-theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');

            document.body.setAttribute('data-theme', theme);

            // Update button text
            const themeBtn = document.querySelector('button[onclick="toggleTheme()"]');
            if (themeBtn) {
                themeBtn.textContent = theme === 'dark' ? '☀️ Light' : '🌓 Dark';
            }
        }

        // Enhanced keyboard shortcuts
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
                }
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();

            // Add smooth transitions to section content
            document.querySelectorAll('.debug-section-content').forEach(content => {
                content.style.transition = 'max-height 0.3s ease-out';
                content.style.overflow = 'hidden';
                content.style.maxHeight = content.scrollHeight + 'px';
            });
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('debug-advanced-theme')) {
                document.body.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            }
        });
    </script>
</body>
</html>
