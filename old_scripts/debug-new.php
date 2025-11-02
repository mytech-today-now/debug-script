<?php
/**
 * WordPress Debug Tool - Production Optimized Version
 * 
 * FAST & PRODUCTION-SAFE with AJAX progressive loading
 * 
 * @version 3.0.0-optimized
 * @author WordPress Debug Team
 * @description Lightning-fast WordPress debugging tool optimized for production servers
 * 
 * PERFORMANCE FEATURES:
 * - AJAX progressive loading (sections load on-demand)
 * - Production-safe resource limits
 * - Intelligent caching with transients
 * - Chunked processing for large operations
 * - Modern JavaScript with fetch API
 * - Emergency disable mechanism
 * - Sub-second initial page load
 */

// ============================================================================
// PRODUCTION SAFETY & PERFORMANCE SETTINGS
// ============================================================================

// Emergency disable mechanism
if (defined('DISABLE_DEBUG_NEW') && DISABLE_DEBUG_NEW) {
    wp_die('Debug New Tool has been disabled. Remove DISABLE_DEBUG_NEW constant to re-enable.');
}

// Production-safe resource limits
ini_set('memory_limit', '256M');
set_time_limit(30); // Conservative 30-second limit
ignore_user_abort(true);

// Performance monitoring
$debug_start_time = microtime(true);
$debug_start_memory = memory_get_usage(true);

// ============================================================================
// AUTHENTICATION & SECURITY
// ============================================================================

// WordPress integration check
if (!function_exists('wp_get_current_user')) {
    // Load WordPress if not already loaded
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../wp-load.php', 
        '../wp-load.php',
        'wp-load.php'
    ];
    
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// Security check - Admin only
if (!current_user_can('manage_options')) {
    http_response_code(403);
    wp_die('Access denied. Administrator privileges required.');
}

// ============================================================================
// AJAX HANDLERS
// ============================================================================

// Handle AJAX requests for progressive loading
if (isset($_POST['action']) && $_POST['action'] === 'debug_load_section') {
    header('Content-Type: application/json');

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'debug_new_nonce')) {
        wp_send_json_error('Security check failed');
    }

    $section = sanitize_text_field($_POST['section']);
    $force_refresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] === 'true';

    // Check cache first (unless force refresh)
    if (!$force_refresh) {
        $cached_data = get_transient("debug_new_cache_{$section}");
        if ($cached_data !== false) {
            wp_send_json_success([
                'html' => $cached_data,
                'cached' => true,
                'cache_time' => get_option("debug_new_cache_time_{$section}", 0)
            ]);
        }
    }

    // Generate section data
    $start_time = microtime(true);
    $html = generate_section_html($section);
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);

    // Cache the result for 5 minutes
    set_transient("debug_new_cache_{$section}", $html, 300);
    update_option("debug_new_cache_time_{$section}", time());

    wp_send_json_success([
        'html' => $html,
        'cached' => false,
        'execution_time' => $execution_time,
        'memory_used' => round((memory_get_usage(true) - $debug_start_memory) / 1024 / 1024, 2)
    ]);
}

// Handle cache clearing
if (isset($_POST['action']) && $_POST['action'] === 'debug_clear_cache') {
    header('Content-Type: application/json');

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'debug_new_nonce')) {
        wp_send_json_error('Security check failed');
    }

    // Clear all debug cache transients
    $sections = ['system_info', 'database_info', 'plugin_analysis', 'theme_analysis', 'security_scan', 'performance_metrics', 'error_analysis', 'cache_analysis'];
    $cleared = 0;

    foreach ($sections as $section) {
        if (delete_transient("debug_new_cache_{$section}")) {
            $cleared++;
        }
        delete_option("debug_new_cache_time_{$section}");
    }

    wp_send_json_success([
        'message' => "Cleared {$cleared} cached sections",
        'cleared_count' => $cleared
    ]);
}

// ============================================================================
// SECTION GENERATORS
// ============================================================================

function generate_section_html($section) {
    switch ($section) {
        case 'system_info':
            return generate_system_info();
        case 'database_info':
            return generate_database_info();
        case 'plugin_analysis':
            return generate_plugin_analysis();
        case 'theme_analysis':
            return generate_theme_analysis();
        case 'security_scan':
            return generate_security_scan();
        case 'performance_metrics':
            return generate_performance_metrics();
        case 'error_analysis':
            return generate_error_analysis();
        case 'cache_analysis':
            return generate_cache_analysis();
        default:
            return '<div class="error">Unknown section: ' . esc_html($section) . '</div>';
    }
}

function generate_system_info() {
    global $wp_version;
    
    $info = [
        'WordPress Version' => $wp_version,
        'PHP Version' => PHP_VERSION,
        'MySQL Version' => $GLOBALS['wpdb']->db_version(),
        'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time') . 's',
        'Upload Max Size' => ini_get('upload_max_filesize'),
        'Post Max Size' => ini_get('post_max_size'),
        'Active Theme' => wp_get_theme()->get('Name'),
        'Active Plugins' => count(get_option('active_plugins', [])),
        'Multisite' => is_multisite() ? 'Yes' : 'No',
        'Debug Mode' => WP_DEBUG ? 'Enabled' : 'Disabled'
    ];
    
    $html = '<div class="info-grid">';
    foreach ($info as $label => $value) {
        $html .= '<div class="info-item">';
        $html .= '<strong>' . esc_html($label) . ':</strong> ';
        $html .= '<span>' . esc_html($value) . '</span>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

function generate_database_info() {
    global $wpdb;
    
    try {
        // Get basic database info with limits
        $tables = $wpdb->get_results("SHOW TABLES LIMIT 50", ARRAY_N);
        $table_count = count($tables);
        
        // Get database size (optimized query)
        $db_size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 
            FROM information_schema.tables 
            WHERE table_schema = '{$wpdb->dbname}'
        ");
        
        $html = '<div class="db-overview">';
        $html .= '<div class="metric"><strong>Database:</strong> ' . esc_html($wpdb->dbname) . '</div>';
        $html .= '<div class="metric"><strong>Tables:</strong> ' . $table_count . '</div>';
        $html .= '<div class="metric"><strong>Size:</strong> ' . ($db_size ?: 'Unknown') . ' MB</div>';
        $html .= '<div class="metric"><strong>Charset:</strong> ' . esc_html($wpdb->charset) . '</div>';
        $html .= '</div>';
        
        // Show first 20 tables
        $html .= '<div class="table-list">';
        $html .= '<h4>Database Tables (First 20)</h4>';
        $html .= '<div class="table-grid">';
        foreach (array_slice($tables, 0, 20) as $table) {
            $table_name = $table[0];
            $html .= '<div class="table-item">' . esc_html($table_name) . '</div>';
        }
        $html .= '</div>';
        if ($table_count > 20) {
            $html .= '<div class="note">... and ' . ($table_count - 20) . ' more tables</div>';
        }
        $html .= '</div>';
        
        return $html;
        
    } catch (Exception $e) {
        return '<div class="error">Database analysis failed: ' . esc_html($e->getMessage()) . '</div>';
    }
}

function generate_plugin_analysis() {
    $active_plugins = get_option('active_plugins', []);
    $mu_plugins = get_mu_plugins();
    
    $html = '<div class="plugin-overview">';
    $html .= '<div class="metric"><strong>Active Plugins:</strong> ' . count($active_plugins) . '</div>';
    $html .= '<div class="metric"><strong>Must-Use Plugins:</strong> ' . count($mu_plugins) . '</div>';
    $html .= '</div>';
    
    // Show active plugins (limited to first 15)
    $html .= '<div class="plugin-list">';
    $html .= '<h4>Active Plugins (First 15)</h4>';
    foreach (array_slice($active_plugins, 0, 15) as $plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $html .= '<div class="plugin-item">';
        $html .= '<strong>' . esc_html($plugin_data['Name']) . '</strong> ';
        $html .= '<span class="version">v' . esc_html($plugin_data['Version']) . '</span>';
        $html .= '</div>';
    }
    if (count($active_plugins) > 15) {
        $html .= '<div class="note">... and ' . (count($active_plugins) - 15) . ' more plugins</div>';
    }
    $html .= '</div>';
    
    return $html;
}

function generate_theme_analysis() {
    $theme = wp_get_theme();
    $parent_theme = $theme->parent();
    
    $html = '<div class="theme-info">';
    $html .= '<div class="metric"><strong>Active Theme:</strong> ' . esc_html($theme->get('Name')) . '</div>';
    $html .= '<div class="metric"><strong>Version:</strong> ' . esc_html($theme->get('Version')) . '</div>';
    $html .= '<div class="metric"><strong>Author:</strong> ' . esc_html($theme->get('Author')) . '</div>';
    
    if ($parent_theme) {
        $html .= '<div class="metric"><strong>Parent Theme:</strong> ' . esc_html($parent_theme->get('Name')) . '</div>';
        $html .= '<div class="metric"><strong>Child Theme:</strong> Yes</div>';
    } else {
        $html .= '<div class="metric"><strong>Child Theme:</strong> No</div>';
    }
    
    // Theme features
    $features = [
        'post-thumbnails' => 'Post Thumbnails',
        'custom-background' => 'Custom Background',
        'custom-header' => 'Custom Header',
        'menus' => 'Navigation Menus',
        'widgets' => 'Widgets',
        'custom-logo' => 'Custom Logo'
    ];
    
    $html .= '<h4>Theme Features</h4>';
    foreach ($features as $feature => $label) {
        $supported = current_theme_supports($feature) ? '✅' : '❌';
        $html .= '<div class="feature-item">' . $supported . ' ' . esc_html($label) . '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

function generate_security_scan() {
    $issues = [];
    $score = 100;
    
    // Check debug mode
    if (WP_DEBUG) {
        $issues[] = 'WP_DEBUG is enabled in production';
        $score -= 15;
    }
    
    // Check file permissions
    if (is_writable(ABSPATH . 'wp-config.php')) {
        $issues[] = 'wp-config.php is writable';
        $score -= 20;
    }
    
    // Check WordPress version
    if (version_compare($GLOBALS['wp_version'], '6.0', '<')) {
        $issues[] = 'WordPress version is outdated';
        $score -= 25;
    }
    
    // Check admin user
    $admin_users = get_users(['role' => 'administrator']);
    foreach ($admin_users as $user) {
        if ($user->user_login === 'admin') {
            $issues[] = 'Default "admin" username detected';
            $score -= 10;
            break;
        }
    }
    
    $html = '<div class="security-overview">';
    $html .= '<div class="security-score">Security Score: <strong>' . max(0, $score) . '/100</strong></div>';
    
    if (!empty($issues)) {
        $html .= '<div class="security-issues">';
        $html .= '<h4>Security Issues Found</h4>';
        foreach ($issues as $issue) {
            $html .= '<div class="issue">⚠️ ' . esc_html($issue) . '</div>';
        }
        $html .= '</div>';
    } else {
        $html .= '<div class="security-good">✅ No major security issues detected</div>';
    }
    
    $html .= '</div>';
    return $html;
}

function generate_performance_metrics() {
    global $debug_start_time, $debug_start_memory;

    $current_memory = memory_get_usage(true);
    $peak_memory = memory_get_peak_usage(true);
    $execution_time = microtime(true) - $debug_start_time;

    // Get additional performance data
    $opcache_enabled = function_exists('opcache_get_status') && opcache_get_status() !== false;
    $query_count = get_num_queries();
    $upload_max = ini_get('upload_max_filesize');
    $post_max = ini_get('post_max_size');

    $html = '<div class="performance-metrics">';
    $html .= '<div class="metric"><strong>Page Load Time:</strong> ' . round($execution_time * 1000, 2) . 'ms</div>';
    $html .= '<div class="metric"><strong>Memory Usage:</strong> ' . round($current_memory / 1024 / 1024, 2) . 'MB</div>';
    $html .= '<div class="metric"><strong>Peak Memory:</strong> ' . round($peak_memory / 1024 / 1024, 2) . 'MB</div>';
    $html .= '<div class="metric"><strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '</div>';
    $html .= '<div class="metric"><strong>Database Queries:</strong> ' . $query_count . '</div>';
    $html .= '<div class="metric"><strong>OPcache:</strong> ' . ($opcache_enabled ? '✅ Enabled' : '❌ Disabled') . '</div>';
    $html .= '<div class="metric"><strong>Upload Limit:</strong> ' . $upload_max . '</div>';
    $html .= '<div class="metric"><strong>Post Size Limit:</strong> ' . $post_max . '</div>';

    // Performance score calculation
    $score = 100;
    if ($execution_time > 1) $score -= 20;
    if ($current_memory > 128 * 1024 * 1024) $score -= 15;
    if ($query_count > 50) $score -= 15;
    if (!$opcache_enabled) $score -= 10;

    $score = max(0, $score);
    $score_color = $score >= 80 ? '#28a745' : ($score >= 60 ? '#ffc107' : '#dc3545');

    $html .= '<div class="metric" style="border-left-color: ' . $score_color . ';"><strong>Performance Score:</strong> ' . $score . '/100</div>';

    // Performance recommendations
    $html .= '<div class="recommendations" style="margin-top: 20px;">';
    $html .= '<h4>🚀 Performance Recommendations</h4>';

    if ($current_memory > 128 * 1024 * 1024) {
        $html .= '<div class="rec">🔧 High memory usage detected - consider optimizing plugins</div>';
    }

    if ($execution_time > 1) {
        $html .= '<div class="rec">⚡ Slow page load - enable caching and optimize database queries</div>';
    }

    if ($query_count > 50) {
        $html .= '<div class="rec">🗄️ High database query count - review plugin efficiency</div>';
    }

    if (!$opcache_enabled) {
        $html .= '<div class="rec">⚡ Enable OPcache for significant PHP performance improvement</div>';
    }

    $html .= '<div class="rec">💡 Use a CDN for static assets</div>';
    $html .= '<div class="rec">🗜️ Enable GZIP compression</div>';
    $html .= '<div class="rec">🖼️ Optimize and compress images</div>';
    $html .= '<div class="rec">🔄 Implement browser caching headers</div>';
    $html .= '</div>';

    $html .= '</div>';
    return $html;
}

function generate_error_analysis() {
    $error_log = ini_get('error_log');
    $html = '<div class="error-analysis">';
    
    if ($error_log && file_exists($error_log) && is_readable($error_log)) {
        $file_size = filesize($error_log);
        $html .= '<div class="metric"><strong>Error Log:</strong> ' . esc_html($error_log) . '</div>';
        $html .= '<div class="metric"><strong>Log Size:</strong> ' . round($file_size / 1024, 2) . 'KB</div>';
        
        // Read last 10 lines safely
        if ($file_size > 0 && $file_size < 1024 * 1024) { // Only if less than 1MB
            $lines = file($error_log);
            $recent_lines = array_slice($lines, -10);
            
            $html .= '<div class="recent-errors">';
            $html .= '<h4>Recent Errors (Last 10 lines)</h4>';
            $html .= '<div class="error-log">';
            foreach ($recent_lines as $line) {
                $html .= '<div class="log-line">' . esc_html(trim($line)) . '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="note">Error log is too large to display safely</div>';
        }
    } else {
        $html .= '<div class="note">No error log found or not accessible</div>';
    }
    
    $html .= '</div>';
    return $html;
}

function generate_cache_analysis() {
    $html = '<div class="cache-analysis">';
    
    // Object cache check
    $object_cache = wp_using_ext_object_cache() ? 'External' : 'Default';
    $html .= '<div class="metric"><strong>Object Cache:</strong> ' . $object_cache . '</div>';
    
    // Check for common caching plugins
    $cache_plugins = [
        'W3 Total Cache' => class_exists('W3TC'),
        'WP Super Cache' => function_exists('wp_super_cache_init'),
        'WP Rocket' => function_exists('rocket_init'),
        'LiteSpeed Cache' => class_exists('LiteSpeed_Cache'),
        'Autoptimize' => class_exists('autoptimizeMain')
    ];
    
    $active_cache_plugins = array_filter($cache_plugins);
    
    $html .= '<div class="metric"><strong>Cache Plugins:</strong> ' . count($active_cache_plugins) . ' detected</div>';
    
    if (!empty($active_cache_plugins)) {
        $html .= '<div class="cache-plugins">';
        $html .= '<h4>Active Cache Plugins</h4>';
        foreach ($active_cache_plugins as $plugin => $active) {
            $html .= '<div class="plugin">✅ ' . esc_html($plugin) . '</div>';
        }
        $html .= '</div>';
    } else {
        $html .= '<div class="recommendation">💡 Consider installing a caching plugin for better performance</div>';
    }
    
    $html .= '</div>';
    return $html;
}

// ============================================================================
// MAIN HTML OUTPUT
// ============================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Debug Tool - Production Optimized</title>
    <style>
        /* Critical CSS - Inline for fast loading */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f1f1; color: #333; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; text-align: center; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.1em; opacity: 0.9; }
        .performance-bar { background: #e9ecef; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .section { background: white; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .section-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center; }
        .section-title { font-size: 1.3em; font-weight: 600; color: #495057; }
        .load-btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .load-btn:hover { background: #0056b3; transform: translateY(-1px); }
        .load-btn:disabled { background: #6c757d; cursor: not-allowed; transform: none; }
        .section-content { padding: 20px; min-height: 100px; }
        .loading { text-align: center; padding: 40px; color: #6c757d; }
        .loading::after { content: ''; display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite; margin-left: 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; }
        .info-grid, .db-overview, .plugin-overview, .theme-info, .security-overview, .performance-metrics, .cache-analysis { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .info-item, .metric { background: #f8f9fa; padding: 12px; border-radius: 5px; border-left: 3px solid #007bff; }
        .cache-time { font-size: 0.9em; color: #6c757d; margin-top: 10px; }
        .emergency-disable { background: #dc3545; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .emergency-disable code { background: rgba(255,255,255,0.2); padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ WordPress Debug Tool</h1>
            <p>Production Optimized • AJAX Progressive Loading • Lightning Fast</p>
        </div>
        
        <div class="emergency-disable">
            <strong>🚨 Emergency Disable:</strong> Add <code>define('DISABLE_DEBUG_NEW', true);</code> to wp-config.php if needed
        </div>
        
        <div class="performance-bar">
            <span><strong>Initial Load:</strong> <?php echo round((microtime(true) - $debug_start_time) * 1000, 2); ?>ms</span>
            <span><strong>Memory:</strong> <?php echo round(memory_get_usage(true) / 1024 / 1024, 2); ?>MB</span>
            <span><strong>Cache:</strong> Transient (5min TTL)</span>
        </div>

        <!-- System Information Section -->
        <div class="section" id="system-info-section">
            <div class="section-header">
                <div class="section-title">🖥️ System Information</div>
                <button class="load-btn" onclick="loadSection('system_info', 'system-info-section')">Load System Info</button>
            </div>
            <div class="section-content">
                <div class="loading">Click "Load System Info" to fetch WordPress and server information...</div>
            </div>
        </div>

        <!-- Database Analysis Section -->
        <div class="section" id="database-info-section">
            <div class="section-header">
                <div class="section-title">🗄️ Database Analysis</div>
                <button class="load-btn" onclick="loadSection('database_info', 'database-info-section')">Load Database Info</button>
            </div>
            <div class="section-content">
                <div class="loading">Click "Load Database Info" to analyze database structure and performance...</div>
            </div>
        </div>

        <!-- Plugin Analysis Section -->
        <div class="section" id="plugin-analysis-section">
            <div class="section-header">
                <div class="section-title">🔌 Plugin Analysis</div>
                <button class="load-btn" onclick="loadSection('plugin_analysis', 'plugin-analysis-section')">Load Plugin Analysis</button>
            </div>
            <div class="section-content">
                <div class="loading">Click "Load Plugin Analysis" to examine active plugins and performance impact...</div>
            </div>
        </div>

        <!-- Theme Analysis Section -->
        <div class="section" id="theme-analysis-section">
            <div class="section-header">
                <div class="section-title">🎨 Theme Analysis</div>
                <button class="load-btn" onclick="loadSection('theme_analysis', 'theme-analysis-section')">Load Theme Analysis</button>
            </div>
            <div class="section-content">
                <div class="loading">Click "Load Theme Analysis" to examine theme features and compatibility...</div>
            </div>
        </div>

        <!-- Security Scan Section -->
        <div class="section" id="security-scan-section">
            <div class="section-header">
                <div class="section-title">🛡️ Security Scan</div>
                <button class="load-btn" onclick="loadSection('security_scan', 'security-scan-section')">Load Security Scan</button>
            </div>
            <div class="section-content">
                <div class="loading">Click "Load Security Scan" to perform security analysis and recommendations...</div>
            </div>
        </div>

        <!-- Performance Metrics Section -->
        <div class="section" id="performance-metrics-section">
            <div class="section-header">
                <div class="section-title">⚡ Performance Metrics</div>
                <button class="load-btn" onclick="loadSection('performance_metrics', 'performance-metrics-section')">Load Performance Metrics</button>
            </div>
            <div class="section-content">
                <div class="loading">Click "Load Performance Metrics" to analyze site performance and optimization opportunities...</div>
            </div>
        </div>

        <!-- Error Analysis Section -->
        <div class="section" id="error-analysis-section">
            <div class="section-header">
                <div class="section-title">🚨 Error Analysis</div>
                <button class="load-btn" onclick="loadSection('error_analysis', 'error-analysis-section')">Load Error Analysis</button>
            </div>
            <div class="section-content">
                <div class="loading">Click "Load Error Analysis" to examine error logs and debugging information...</div>
            </div>
        </div>

        <!-- Cache Analysis Section -->
        <div class="section" id="cache-analysis-section">
            <div class="section-header">
                <div class="section-title">🗄️ Cache Analysis</div>
                <button class="load-btn" onclick="loadSection('cache_analysis', 'cache-analysis-section')">Load Cache Analysis</button>
            </div>
            <div class="section-content">
                <div class="loading">Click "Load Cache Analysis" to examine caching configuration and performance...</div>
            </div>
        </div>

        <!-- Quick Actions Panel -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">⚡ Quick Actions</div>
                <button class="load-btn" onclick="loadAllSections()">Load All Sections</button>
            </div>
            <div class="section-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <button class="load-btn" onclick="clearAllCache()">🗑️ Clear Cache</button>
                    <button class="load-btn" onclick="refreshAllSections()">🔄 Refresh All</button>
                    <button class="load-btn" onclick="exportResults()">📥 Export Results</button>
                    <button class="load-btn" onclick="toggleTheme()">🌙 Toggle Theme</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modern JavaScript for AJAX progressive loading
        const DEBUG_NONCE = '<?php echo wp_create_nonce('debug_new_nonce'); ?>';
        const AJAX_URL = '<?php echo admin_url('admin-ajax.php'); ?>';
        let loadingStates = new Map();

        // Load individual section
        async function loadSection(sectionName, sectionId, forceRefresh = false) {
            const button = document.querySelector(`#${sectionId} .load-btn`);
            const content = document.querySelector(`#${sectionId} .section-content`);

            if (loadingStates.get(sectionName)) return; // Prevent double loading

            try {
                // Update UI
                loadingStates.set(sectionName, true);
                button.disabled = true;
                button.textContent = 'Loading...';
                content.innerHTML = '<div class="loading">Loading section data...</div>';

                // Prepare form data
                const formData = new FormData();
                formData.append('action', 'debug_load_section');
                formData.append('section', sectionName);
                formData.append('nonce', DEBUG_NONCE);
                if (forceRefresh) formData.append('force_refresh', 'true');

                // Make AJAX request
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    content.innerHTML = result.data.html;

                    // Add cache info
                    if (result.data.cached) {
                        const cacheTime = new Date(result.data.cache_time * 1000).toLocaleTimeString();
                        content.innerHTML += `<div class="cache-time">📄 Cached data from ${cacheTime}</div>`;
                    } else {
                        content.innerHTML += `<div class="cache-time">⚡ Generated in ${result.data.execution_time}ms (${result.data.memory_used}MB)</div>`;
                    }

                    button.textContent = '🔄 Refresh';
                    button.onclick = () => loadSection(sectionName, sectionId, true);
                } else {
                    content.innerHTML = `<div class="error">Error loading section: ${result.data || 'Unknown error'}</div>`;
                    button.textContent = '🔄 Retry';
                }

            } catch (error) {
                content.innerHTML = `<div class="error">Network error: ${error.message}</div>`;
                button.textContent = '🔄 Retry';
            } finally {
                loadingStates.set(sectionName, false);
                button.disabled = false;
            }
        }

        // Load all sections sequentially
        async function loadAllSections() {
            const sections = [
                ['system_info', 'system-info-section'],
                ['database_info', 'database-info-section'],
                ['plugin_analysis', 'plugin-analysis-section'],
                ['theme_analysis', 'theme-analysis-section'],
                ['security_scan', 'security-scan-section'],
                ['performance_metrics', 'performance-metrics-section'],
                ['error_analysis', 'error-analysis-section'],
                ['cache_analysis', 'cache-analysis-section']
            ];

            for (const [sectionName, sectionId] of sections) {
                await loadSection(sectionName, sectionId);
                // Small delay to prevent overwhelming the server
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        }

        // Refresh all loaded sections
        async function refreshAllSections() {
            const sections = [
                ['system_info', 'system-info-section'],
                ['database_info', 'database-info-section'],
                ['plugin_analysis', 'plugin-analysis-section'],
                ['theme_analysis', 'theme-analysis-section'],
                ['security_scan', 'security-scan-section'],
                ['performance_metrics', 'performance-metrics-section'],
                ['error_analysis', 'error-analysis-section'],
                ['cache_analysis', 'cache-analysis-section']
            ];

            for (const [sectionName, sectionId] of sections) {
                const content = document.querySelector(`#${sectionId} .section-content`);
                if (content && !content.innerHTML.includes('Click "Load')) {
                    await loadSection(sectionName, sectionId, true);
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
            }
        }

        // Clear all cache
        async function clearAllCache() {
            if (!confirm('Clear all cached debug data? This will force fresh data on next load.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'debug_clear_cache');
                formData.append('nonce', DEBUG_NONCE);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ ${result.data.message}`);
                    // Reset all section content to initial state
                    document.querySelectorAll('.section-content').forEach(content => {
                        if (!content.innerHTML.includes('Click "Load')) {
                            content.innerHTML = '<div class="loading">Click the load button to fetch fresh data...</div>';
                        }
                    });
                    // Reset all buttons
                    document.querySelectorAll('.section .load-btn').forEach(btn => {
                        if (btn.textContent.includes('Refresh')) {
                            btn.textContent = btn.textContent.replace('🔄 Refresh', 'Load');
                            btn.onclick = function() {
                                const sectionId = btn.closest('.section').id;
                                const sectionName = sectionId.replace('-section', '').replace('-', '_');
                                loadSection(sectionName, sectionId);
                            };
                        }
                    });
                } else {
                    alert(`❌ Error clearing cache: ${result.data || 'Unknown error'}`);
                }
            } catch (error) {
                alert(`❌ Network error: ${error.message}`);
            }
        }

        // Export results
        function exportResults() {
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const filename = `wordpress-debug-optimized-${timestamp}.html`;

            const content = document.documentElement.outerHTML;
            const blob = new Blob([content], { type: 'text/html' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Theme toggle
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('debug-theme', newTheme);
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('debug-theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
        });

        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log('🚀 WordPress Debug Tool (Optimized) loaded in:', Math.round(loadTime), 'ms');

            // Update performance bar
            const perfBar = document.querySelector('.performance-bar');
            if (perfBar) {
                perfBar.innerHTML += `<span><strong>Total Load:</strong> ${Math.round(loadTime)}ms</span>`;
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                loadAllSections();
            }
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshAllSections();
            }
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportResults();
            }
        });
    </script>

    <!-- Dark theme styles -->
    <style>
        [data-theme="dark"] {
            background: #1a1a1a;
            color: #e0e0e0;
        }
        [data-theme="dark"] .section {
            background: #2d2d2d;
            color: #e0e0e0;
        }
        [data-theme="dark"] .section-header {
            background: #3a3a3a;
            border-color: #4a4a4a;
        }
        [data-theme="dark"] .info-item,
        [data-theme="dark"] .metric {
            background: #3a3a3a;
            color: #e0e0e0;
        }
        [data-theme="dark"] .performance-bar {
            background: #3a3a3a;
            color: #e0e0e0;
        }
    </style>
</body>
</html>
<?php
// End of file - Total execution time tracking
$total_time = round((microtime(true) - $debug_start_time) * 1000, 2);
$total_memory = round((memory_get_usage(true) - $debug_start_memory) / 1024 / 1024, 2);

// Log performance for monitoring
error_log("Debug Tool Performance: {$total_time}ms, {$total_memory}MB memory");
?>
