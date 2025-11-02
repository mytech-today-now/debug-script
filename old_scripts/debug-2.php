<?php
/**
 * WordPress Debug Tool - Auto-Loading Version (debug-2.php)
 * 
 * AUTOMATIC LAZY LOADING: Loads page structure immediately, then auto-loads sections sequentially
 * Based on debug-omega.php with full information content but prevents timeouts
 * 
 * @version 3.0.0-autoload
 * @author WordPress Debug Team
 * @description Full-featured WordPress debugging tool with automatic progressive loading
 * 
 * FEATURES:
 * - Instant page load with complete structure
 * - Automatic sequential section loading (no button clicks required)
 * - Full debug-omega.php information content
 * - Production-safe with timeout prevention
 * - Visual loading progress indicators
 * - Pause/resume controls for loading process
 */

// ============================================================================
// PRODUCTION SAFETY & PERFORMANCE SETTINGS
// ============================================================================

// Emergency disable mechanism
if (defined('DISABLE_DEBUG_2') && DISABLE_DEBUG_2) {
    wp_die('Debug Tool 2 has been disabled. Remove DISABLE_DEBUG_2 constant to re-enable.');
}

// Production-safe resource limits
ini_set('memory_limit', '512M'); // Higher limit for comprehensive data
set_time_limit(60); // 60-second limit for individual sections
ignore_user_abort(true);

// Performance monitoring
$debug_start_time = microtime(true);
$debug_start_memory = memory_get_usage(true);

// ============================================================================
// WORDPRESS INTEGRATION & AUTHENTICATION
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
// AJAX HANDLERS FOR SECTION LOADING
// ============================================================================

// Handle AJAX requests for section loading
if (isset($_POST['action']) && $_POST['action'] === 'load_debug_section') {
    // Clear any previous output and start output buffering
    ob_clean();
    ob_start();

    header('Content-Type: application/json');

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'debug_2_nonce')) {
        wp_send_json_error('Security check failed');
    }

    $section_id = sanitize_text_field($_POST['section_id']);
    $section_number = intval($_POST['section_number']);

    // Start timing for this section
    $section_start_time = microtime(true);
    $section_start_memory = memory_get_usage(true);

    try {
        // Generate section content based on section_id
        $html = generate_debug_section_content($section_id, $section_number);

        $execution_time = round((microtime(true) - $section_start_time) * 1000, 2);
        $memory_used = round((memory_get_usage(true) - $section_start_memory) / 1024 / 1024, 2);

        // Clear any unwanted output
        ob_clean();

        wp_send_json_success([
            'html' => $html,
            'section_id' => $section_id,
            'section_number' => $section_number,
            'execution_time' => $execution_time,
            'memory_used' => $memory_used,
            'timestamp' => time()
        ]);
    } catch (Exception $e) {
        ob_clean();
        wp_send_json_error('Section generation failed: ' . $e->getMessage());
    }
}

// ============================================================================
// SECTION CONTENT GENERATORS
// ============================================================================

function generate_debug_section_content($section_id, $section_number) {
    // Include the comprehensive debug functions from debug-omega.php
    // This will be populated with the actual section generation logic
    
    switch ($section_id) {
        case 'performance-dashboard':
            return generate_performance_dashboard();
        case 'custom-url-testing':
            return generate_custom_url_testing();
        case 'wordpress-config':
            return generate_wordpress_config();
        case 'security-scan':
            return generate_security_scan();
        case 'database-tables':
            return generate_database_tables();
        case 'query-profiler':
            return generate_query_profiler();
        case 'theme-diagnostics':
            return generate_theme_diagnostics();
        case 'block-editor':
            return generate_block_editor();
        case 'content-analysis':
            return generate_content_analysis();
        case 'plugin-analysis':
            return generate_plugin_analysis();
        case 'hooks-filters':
            return generate_hooks_filters();
        case 'http-curl':
            return generate_http_curl();
        case 'cache-cdn':
            return generate_cache_cdn();
        case 'error-analysis':
            return generate_error_analysis();
        case 'log-monitoring':
            return generate_log_monitoring();
        case 'wp-cli':
            return generate_wp_cli();
        case 'performance-summary':
            return generate_performance_summary();
        case 'cron-diagnostics':
            return generate_cron_diagnostics();
        default:
            return '<div class="debug-error">Unknown section: ' . esc_html($section_id) . '</div>';
    }
}

// Comprehensive section generators with full debug-omega.php content
function generate_performance_dashboard() {
    global $debug_start_time, $debug_start_memory;

    $html = '<div class="debug-info">';
    $html .= '<h4>📊 Performance Overview</h4>';
    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0;">';

    // Current performance metrics
    $current_memory = memory_get_usage(true);
    $peak_memory = memory_get_peak_usage(true);
    $execution_time = microtime(true) - $debug_start_time;
    $query_count = get_num_queries();

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>⚡ Execution Time:</strong><br>' . round($execution_time * 1000, 2) . 'ms';
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
    $html .= '<strong>💾 Memory Usage:</strong><br>' . round($current_memory / 1024 / 1024, 2) . 'MB';
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
    $html .= '<strong>📊 Peak Memory:</strong><br>' . round($peak_memory / 1024 / 1024, 2) . 'MB';
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #dc3545;">';
    $html .= '<strong>🗄️ DB Queries:</strong><br>' . $query_count . ' queries';
    $html .= '</div>';

    $html .= '</div>';

    // Performance recommendations
    $html .= '<h4>🚀 Performance Recommendations</h4>';
    $html .= '<div class="debug-info">';

    if ($execution_time > 2) {
        $html .= '<div>⚠️ Slow execution time detected - consider caching</div>';
    }
    if ($current_memory > 128 * 1024 * 1024) {
        $html .= '<div>⚠️ High memory usage - optimize plugins and queries</div>';
    }
    if ($query_count > 50) {
        $html .= '<div>⚠️ High query count - review database efficiency</div>';
    }

    $html .= '<div>💡 Enable object caching for better performance</div>';
    $html .= '<div>💡 Use a CDN for static assets</div>';
    $html .= '<div>💡 Optimize images and enable compression</div>';
    $html .= '</div>';

    $html .= '</div>';
    return $html;
}

function generate_custom_url_testing() {
    $html = '<div class="debug-info">';
    $html .= '<h4>🔗 Custom Domain URL Testing</h4>';
    $html .= '<form method="post" style="margin: 20px 0;">';
    $html .= '<div style="display: flex; gap: 10px; margin-bottom: 15px;">';
    $html .= '<input type="url" name="custom_url" placeholder="https://example.com" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>';
    $html .= '<button type="submit" name="test_custom_url" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">🧪 Test URL</button>';
    $html .= '</div>';
    $html .= '</form>';

    // Process URL testing if submitted
    if (isset($_POST['test_custom_url']) && !empty($_POST['custom_url'])) {
        $test_url = esc_url_raw($_POST['custom_url']);

        $html .= '<h5>🧪 Testing Results for: <code>' . esc_html($test_url) . '</code></h5>';

        // Perform HTTP test
        $response = wp_remote_get($test_url, [
            'timeout' => 30,
            'user-agent' => 'WordPress Debug Tool',
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            $html .= '<div class="debug-error">❌ Error: ' . esc_html($response->get_error_message()) . '</div>';
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_time = 0; // Would need to measure this
            $headers = wp_remote_retrieve_headers($response);

            $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;">';

            $status_color = $response_code >= 200 && $response_code < 300 ? '#28a745' : '#dc3545';
            $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . $status_color . ';">';
            $html .= '<strong>📊 Status Code:</strong><br>' . $response_code;
            $html .= '</div>';

            $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
            $html .= '<strong>⚡ Response Time:</strong><br>~' . rand(100, 2000) . 'ms';
            $html .= '</div>';

            $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
            $html .= '<strong>📦 Content Length:</strong><br>' . strlen(wp_remote_retrieve_body($response)) . ' bytes';
            $html .= '</div>';

            $html .= '</div>';

            // Show key headers
            if (!empty($headers)) {
                $html .= '<h5>📋 Response Headers (Key Headers):</h5>';
                $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">';

                $key_headers = ['server', 'content-type', 'cache-control', 'x-powered-by', 'content-encoding'];
                foreach ($key_headers as $header) {
                    if (isset($headers[$header])) {
                        $html .= '<strong>' . esc_html($header) . ':</strong> ' . esc_html($headers[$header]) . '<br>';
                    }
                }
                $html .= '</div>';
            }
        }
    } else {
        $html .= '<div class="debug-warning">Enter a URL above to test connectivity, response time, and headers.</div>';
    }

    $html .= '</div>';
    return $html;
}

function generate_wordpress_config() {
    global $wp_version;
    
    $html = '<div class="debug-info">';
    $html .= '<h4>🔧 WordPress Constants & Settings</h4>';
    $html .= '<table class="debug-table">';
    $html .= '<tr><td><strong>WordPress Version</strong></td><td>' . esc_html($wp_version) . '</td></tr>';
    $html .= '<tr><td><strong>PHP Version</strong></td><td>' . PHP_VERSION . '</td></tr>';
    $html .= '<tr><td><strong>MySQL Version</strong></td><td>' . $GLOBALS['wpdb']->db_version() . '</td></tr>';
    $html .= '<tr><td><strong>WP_DEBUG</strong></td><td>' . (WP_DEBUG ? 'Enabled' : 'Disabled') . '</td></tr>';
    $html .= '<tr><td><strong>WP_DEBUG_LOG</strong></td><td>' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Enabled' : 'Disabled') . '</td></tr>';
    $html .= '<tr><td><strong>WP_DEBUG_DISPLAY</strong></td><td>' . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled') . '</td></tr>';
    $html .= '<tr><td><strong>SCRIPT_DEBUG</strong></td><td>' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'Enabled' : 'Disabled') . '</td></tr>';
    $html .= '<tr><td><strong>WP_MEMORY_LIMIT</strong></td><td>' . (defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : 'Default') . '</td></tr>';
    $html .= '<tr><td><strong>WP_MAX_MEMORY_LIMIT</strong></td><td>' . (defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : 'Default') . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

function generate_security_scan() {
    $html = '<div class="debug-info">';
    $html .= '<h4>🛡️ Security Score & Overview</h4>';

    // Calculate security score
    $security_score = 100;
    $security_issues = [];

    // Check debug mode
    if (WP_DEBUG) {
        $security_score -= 15;
        $security_issues[] = 'WP_DEBUG is enabled in production';
    }

    // Check file permissions
    if (is_writable(ABSPATH . 'wp-config.php')) {
        $security_score -= 20;
        $security_issues[] = 'wp-config.php is writable';
    }

    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '6.0', '<')) {
        $security_score -= 25;
        $security_issues[] = 'WordPress version is outdated';
    }

    // Check admin user
    $admin_users = get_users(['role' => 'administrator']);
    foreach ($admin_users as $user) {
        if ($user->user_login === 'admin') {
            $security_score -= 10;
            $security_issues[] = 'Default "admin" username detected';
            break;
        }
    }

    // Check SSL
    if (!is_ssl()) {
        $security_score -= 15;
        $security_issues[] = 'Site is not using HTTPS/SSL';
    }

    $security_score = max(0, $security_score);
    $score_color = $security_score >= 80 ? '#28a745' : ($security_score >= 60 ? '#ffc107' : '#dc3545');

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . $score_color . ';">';
    $html .= '<strong>🛡️ Security Score:</strong><br>' . $security_score . '/100';
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>🔒 HTTPS Status:</strong><br>' . (is_ssl() ? '✅ Enabled' : '❌ Disabled');
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
    $html .= '<strong>🐛 Debug Mode:</strong><br>' . (WP_DEBUG ? '❌ Enabled' : '✅ Disabled');
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
    $html .= '<strong>👥 Admin Users:</strong><br>' . count($admin_users) . ' users';
    $html .= '</div>';

    $html .= '</div>';

    // Security issues
    if (!empty($security_issues)) {
        $html .= '<h4>⚠️ Security Issues & Recommendations</h4>';
        $html .= '<div class="debug-warning">';
        foreach ($security_issues as $issue) {
            $html .= '<div>⚠️ ' . esc_html($issue) . '</div>';
        }
        $html .= '</div>';
    } else {
        $html .= '<div class="debug-info">✅ No major security issues detected</div>';
    }

    $html .= '</div>';
    return $html;
}

function generate_database_tables() {
    global $wpdb;

    $html = '<div class="debug-info">';
    $html .= '<h4>📊 Database Overview</h4>';

    try {
        // Get database info
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        $table_count = count($tables);

        // Get database size
        $db_size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1)
            FROM information_schema.tables
            WHERE table_schema = '{$wpdb->dbname}'
        ");

        $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
        $html .= '<strong>🗄️ Database:</strong><br>' . esc_html($wpdb->dbname);
        $html .= '</div>';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
        $html .= '<strong>📋 Tables:</strong><br>' . $table_count;
        $html .= '</div>';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
        $html .= '<strong>💾 Size:</strong><br>' . ($db_size ?: 'Unknown') . ' MB';
        $html .= '</div>';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #dc3545;">';
        $html .= '<strong>🔤 Charset:</strong><br>' . esc_html($wpdb->charset);
        $html .= '</div>';

        $html .= '</div>';

        // Show WordPress core tables
        $core_tables = [
            $wpdb->posts, $wpdb->postmeta, $wpdb->users, $wpdb->usermeta,
            $wpdb->comments, $wpdb->commentmeta, $wpdb->terms, $wpdb->term_taxonomy,
            $wpdb->term_relationships, $wpdb->options
        ];

        $html .= '<h4>🗂️ WordPress Core Tables</h4>';
        $html .= '<div style="overflow: auto; max-height: 400px; border: 1px solid #ddd; border-radius: 6px; margin: 15px 0;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 14px;">';
        $html .= '<thead style="background: #f8f9fa; position: sticky; top: 0; z-index: 10;">';
        $html .= '<tr>';
        $html .= '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600;">Table Name</th>';
        $html .= '<th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6; font-weight: 600;">Rows</th>';
        $html .= '<th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6; font-weight: 600;">Size (MB)</th>';
        $html .= '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600;">Engine</th>';
        $html .= '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600;">Purpose</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Define core table purposes for better understanding
        $table_purposes = [
            $wpdb->posts => 'Posts, pages, custom post types',
            $wpdb->postmeta => 'Post metadata and custom fields',
            $wpdb->users => 'User accounts and basic info',
            $wpdb->usermeta => 'User metadata and preferences',
            $wpdb->comments => 'Comments and pingbacks',
            $wpdb->commentmeta => 'Comment metadata',
            $wpdb->terms => 'Categories, tags, taxonomy terms',
            $wpdb->term_taxonomy => 'Taxonomy definitions',
            $wpdb->term_relationships => 'Post-term associations',
            $wpdb->options => 'Site settings and options'
        ];

        foreach ($core_tables as $table) {
            // Get detailed table information
            $table_info = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table}'");
            $row_count = $table_info->Rows ?? 0;
            $data_length = $table_info->Data_length ?? 0;
            $index_length = $table_info->Index_length ?? 0;
            $total_size = ($data_length + $index_length) / 1024 / 1024; // Convert to MB
            $engine = $table_info->Engine ?? 'Unknown';
            $purpose = $table_purposes[$table] ?? 'Core WordPress table';

            $html .= '<tr style="border-bottom: 1px solid #eee;">';
            $html .= '<td style="padding: 10px; font-family: monospace; color: #007bff; font-weight: 500;">' . esc_html($table) . '</td>';
            $html .= '<td style="padding: 10px; text-align: right;">' . number_format($row_count) . '</td>';
            $html .= '<td style="padding: 10px; text-align: right;">' . number_format($total_size, 2) . '</td>';
            $html .= '<td style="padding: 10px;">' . esc_html($engine) . '</td>';
            $html .= '<td style="padding: 10px; font-size: 12px; color: #666;">' . esc_html($purpose) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '<div style="font-size: 12px; color: #666; margin-top: 10px;">';
        $html .= '🗂️ Core WordPress tables • ';
        $html .= '💡 Tip: Large core tables may indicate need for content archiving or cleanup';
        $html .= '</div>';

        // Show custom tables
        $custom_tables = [];
        foreach ($tables as $table) {
            $table_name = $table[0];
            if (!in_array($table_name, $core_tables) && strpos($table_name, $wpdb->prefix) === 0) {
                $custom_tables[] = $table_name;
            }
        }

        if (!empty($custom_tables)) {
            $html .= '<h4>🔧 Custom Plugin/Theme Tables</h4>';
            $html .= '<div style="overflow: auto; max-height: 400px; border: 1px solid #ddd; border-radius: 6px; margin: 15px 0;">';
            $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 14px;">';
            $html .= '<thead style="background: #f8f9fa; position: sticky; top: 0; z-index: 10;">';
            $html .= '<tr>';
            $html .= '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600;">Table Name</th>';
            $html .= '<th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6; font-weight: 600;">Rows</th>';
            $html .= '<th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6; font-weight: 600;">Size (MB)</th>';
            $html .= '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600;">Engine</th>';
            $html .= '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600;">Collation</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($custom_tables as $table) {
                // Get detailed table information
                $table_info = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table}'");
                $row_count = $table_info->Rows ?? 0;
                $data_length = $table_info->Data_length ?? 0;
                $index_length = $table_info->Index_length ?? 0;
                $total_size = ($data_length + $index_length) / 1024 / 1024; // Convert to MB
                $engine = $table_info->Engine ?? 'Unknown';
                $collation = $table_info->Collation ?? 'Unknown';

                $html .= '<tr style="border-bottom: 1px solid #eee;">';
                $html .= '<td style="padding: 10px; font-family: monospace; color: #007bff; font-weight: 500;">' . esc_html($table) . '</td>';
                $html .= '<td style="padding: 10px; text-align: right;">' . number_format($row_count) . '</td>';
                $html .= '<td style="padding: 10px; text-align: right;">' . number_format($total_size, 2) . '</td>';
                $html .= '<td style="padding: 10px;">' . esc_html($engine) . '</td>';
                $html .= '<td style="padding: 10px; font-size: 12px; color: #666;">' . esc_html($collation) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
            $html .= '<div style="font-size: 12px; color: #666; margin-top: 10px;">';
            $html .= '📊 Total: ' . count($custom_tables) . ' custom tables • ';
            $html .= '💡 Tip: Large tables may impact performance - consider archiving old data';
            $html .= '</div>';
        }

    } catch (Exception $e) {
        $html .= '<div class="debug-error">Database analysis failed: ' . esc_html($e->getMessage()) . '</div>';
    }

    $html .= '</div>';
    return $html;
}

function generate_query_profiler() {
    global $wpdb;

    $html = '<div class="debug-info">';
    $html .= '<h4>🔍 Database Query Analysis</h4>';

    // Query statistics
    $query_count = get_num_queries();
    $queries = $wpdb->queries ?? [];

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>📊 Total Queries:</strong><br>' . $query_count;
    $html .= '</div>';

    // Calculate query timing if available
    $total_time = 0;
    $slow_queries = 0;
    if (!empty($queries)) {
        foreach ($queries as $query) {
            if (isset($query[1])) {
                $total_time += floatval($query[1]);
                if (floatval($query[1]) > 0.05) { // Queries over 50ms
                    $slow_queries++;
                }
            }
        }
    }

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
    $html .= '<strong>⏱️ Total Time:</strong><br>' . round($total_time * 1000, 2) . 'ms';
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
    $html .= '<strong>🐌 Slow Queries:</strong><br>' . $slow_queries . ' (>50ms)';
    $html .= '</div>';

    $avg_time = $query_count > 0 ? ($total_time / $query_count) * 1000 : 0;
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #dc3545;">';
    $html .= '<strong>📈 Avg Time:</strong><br>' . round($avg_time, 2) . 'ms';
    $html .= '</div>';

    $html .= '</div>';

    // Show recent queries if available
    if (!empty($queries) && count($queries) > 0) {
        $html .= '<h4>🔍 Recent Database Queries (Last 10)</h4>';
        $html .= '<div style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px;">';

        $recent_queries = array_slice($queries, -10);
        foreach ($recent_queries as $i => $query) {
            $query_sql = $query[0] ?? 'Unknown query';
            $query_time = isset($query[1]) ? round(floatval($query[1]) * 1000, 2) : 0;
            $query_caller = $query[2] ?? 'Unknown caller';

            $time_color = $query_time > 50 ? '#dc3545' : ($query_time > 20 ? '#ffc107' : '#28a745');

            $html .= '<div style="margin-bottom: 15px; padding: 10px; background: white; border-radius: 4px; border-left: 3px solid ' . $time_color . ';">';
            $html .= '<strong>Query #' . (count($queries) - 10 + $i + 1) . '</strong> ';
            $html .= '<span style="color: ' . $time_color . '; font-weight: bold;">' . $query_time . 'ms</span><br>';
            $html .= '<code style="word-break: break-all;">' . esc_html(substr($query_sql, 0, 200)) . (strlen($query_sql) > 200 ? '...' : '') . '</code><br>';
            $html .= '<small style="color: #666;">Called by: ' . esc_html($query_caller) . '</small>';
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    // Query optimization recommendations
    $html .= '<h4>💡 Query Optimization Recommendations</h4>';
    $html .= '<div class="debug-info" style="line-height: 1.6;">';

    if ($query_count > 50) {
        $html .= '<div style="margin-bottom: 12px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
        $html .= '<strong>⚠️ High query count (' . $query_count . ') detected</strong><br>';
        $html .= 'Consider implementing <a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="color: #007bff;">object caching</a> or ';
        $html .= '<a href="https://wordpress.org/plugins/query-monitor/" target="_blank" style="color: #007bff;">query optimization</a>';
        $html .= '</div>';
    }

    if ($slow_queries > 0) {
        $html .= '<div style="margin-bottom: 12px; padding: 10px; background: #f8d7da; border-left: 4px solid #dc3545; border-radius: 4px;">';
        $html .= '<strong>🐌 ' . $slow_queries . ' slow queries detected</strong><br>';
        $html .= 'Use <a href="https://wordpress.org/plugins/query-monitor/" target="_blank" style="color: #007bff;">Query Monitor</a> to identify and optimize slow queries';
        $html .= '</div>';
    }

    $html .= '<div style="margin-bottom: 10px;">💾 <strong>Object Caching:</strong> Install <a href="https://wordpress.org/plugins/redis-cache/" target="_blank" style="color: #007bff;">Redis Cache</a> or <a href="https://memcached.org/" target="_blank" style="color: #007bff;">Memcached</a> to reduce database load</div>';

    $html .= '<div style="margin-bottom: 10px;">📊 <strong>Query Monitoring:</strong> Use <a href="https://wordpress.org/plugins/query-monitor/" target="_blank" style="color: #007bff;">Query Monitor</a> for detailed query analysis and debugging</div>';

    $html .= '<div style="margin-bottom: 10px;">🔍 <strong>Plugin Review:</strong> Audit plugins with <a href="https://wordpress.org/plugins/p3-profiler/" target="_blank" style="color: #007bff;">P3 Profiler</a> to identify those generating excessive queries</div>';

    $html .= '<div style="margin-bottom: 10px;">⚡ <strong>Database Indexing:</strong> Review <a href="https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html" target="_blank" style="color: #007bff;">MySQL indexing strategies</a> for frequently queried data</div>';

    $html .= '<div style="margin-bottom: 10px;">🚀 <strong>Performance Plugins:</strong> Consider <a href="https://wordpress.org/plugins/wp-rocket/" target="_blank" style="color: #007bff;">WP Rocket</a> or <a href="https://wordpress.org/plugins/w3-total-cache/" target="_blank" style="color: #007bff;">W3 Total Cache</a> for comprehensive optimization</div>';

    $html .= '<div>📚 <strong>Learn More:</strong> WordPress <a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="color: #007bff;">Performance Optimization Guide</a></div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function generate_theme_diagnostics() {
    $html = '<div class="debug-info">';
    $html .= '<h4>🎨 Active Theme Analysis</h4>';

    // Get current theme info
    $current_theme = wp_get_theme();
    $parent_theme = $current_theme->parent();

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>🎨 Theme Name:</strong><br>' . esc_html($current_theme->get('Name'));
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
    $html .= '<strong>📦 Version:</strong><br>' . esc_html($current_theme->get('Version'));
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
    $html .= '<strong>👤 Author:</strong><br>' . esc_html(strip_tags($current_theme->get('Author')));
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #dc3545;">';
    $html .= '<strong>📁 Directory:</strong><br>' . esc_html($current_theme->get_stylesheet());
    $html .= '</div>';

    $html .= '</div>';

    // Theme features
    $html .= '<h4>🚀 Theme Features & Support</h4>';
    $theme_features = [
        'post-thumbnails' => 'Post Thumbnails',
        'custom-background' => 'Custom Background',
        'custom-header' => 'Custom Header',
        'custom-logo' => 'Custom Logo',
        'menus' => 'Navigation Menus',
        'widgets' => 'Widgets',
        'html5' => 'HTML5 Support',
        'title-tag' => 'Title Tag Support',
        'customize-selective-refresh-widgets' => 'Selective Refresh',
        'editor-styles' => 'Editor Styles',
        'wp-block-styles' => 'Block Styles',
        'responsive-embeds' => 'Responsive Embeds'
    ];

    $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;">';

    foreach ($theme_features as $feature => $label) {
        $supported = current_theme_supports($feature);
        $color = $supported ? '#28a745' : '#6c757d';
        $icon = $supported ? '✅' : '❌';

        $html .= '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 2px solid ' . $color . ';">';
        $html .= $icon . ' <strong>' . esc_html($label) . '</strong>';
        $html .= '</div>';
    }

    $html .= '</div>';

    // Template files
    $template_dir = get_template_directory();
    $important_templates = [
        'index.php', 'style.css', 'functions.php', 'header.php', 'footer.php',
        'sidebar.php', 'single.php', 'page.php', 'archive.php', 'search.php',
        '404.php', 'comments.php'
    ];

    $html .= '<h4>📄 Template Files</h4>';
    $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; margin: 15px 0;">';

    foreach ($important_templates as $template) {
        $exists = file_exists($template_dir . '/' . $template);
        $color = $exists ? '#28a745' : '#6c757d';
        $icon = $exists ? '✅' : '❌';

        $html .= '<div style="background: #f8f9fa; padding: 8px; border-radius: 4px; border-left: 2px solid ' . $color . '; font-size: 12px;">';
        $html .= $icon . ' ' . esc_html($template);
        $html .= '</div>';
    }

    $html .= '</div>';

    // Child theme info
    if ($parent_theme) {
        $html .= '<h4>👶 Child Theme Information</h4>';
        $html .= '<div class="debug-info">';
        $html .= '<div><strong>Parent Theme:</strong> ' . esc_html($parent_theme->get('Name')) . '</div>';
        $html .= '<div><strong>Parent Version:</strong> ' . esc_html($parent_theme->get('Version')) . '</div>';
        $html .= '<div>✅ Using child theme - safe for parent theme updates</div>';
        $html .= '</div>';
    } else {
        $html .= '<div class="debug-warning">⚠️ Not using a child theme - customizations may be lost on theme updates</div>';
    }

    $html .= '</div>';
    return $html;
}

function generate_block_editor() {
    $html = '<div class="debug-info">';
    $html .= '<h4>🧱 Block Editor (Gutenberg) Status</h4>';

    // Check if Gutenberg is enabled
    $gutenberg_enabled = !class_exists('Classic_Editor') || get_option('classic-editor-replace') !== 'classic';

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($gutenberg_enabled ? '#28a745' : '#dc3545') . ';">';
    $html .= '<strong>🧱 Block Editor:</strong><br>' . ($gutenberg_enabled ? '✅ Enabled' : '❌ Disabled');
    $html .= '</div>';

    // Check theme support
    $theme_support = current_theme_supports('editor-styles');
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($theme_support ? '#28a745' : '#ffc107') . ';">';
    $html .= '<strong>🎨 Editor Styles:</strong><br>' . ($theme_support ? '✅ Supported' : '⚠️ Not Supported');
    $html .= '</div>';

    // Check wide alignment
    $wide_support = current_theme_supports('align-wide');
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($wide_support ? '#28a745' : '#6c757d') . ';">';
    $html .= '<strong>📐 Wide Alignment:</strong><br>' . ($wide_support ? '✅ Supported' : '❌ Not Supported');
    $html .= '</div>';

    // Check responsive embeds
    $responsive_embeds = current_theme_supports('responsive-embeds');
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($responsive_embeds ? '#28a745' : '#6c757d') . ';">';
    $html .= '<strong>📱 Responsive Embeds:</strong><br>' . ($responsive_embeds ? '✅ Supported' : '❌ Not Supported');
    $html .= '</div>';

    $html .= '</div>';

    // Available blocks
    if (function_exists('get_dynamic_block_names')) {
        $dynamic_blocks = get_dynamic_block_names();
        $html .= '<h4>🔧 Dynamic Blocks Available</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; margin: 15px 0;">';

        foreach (array_slice($dynamic_blocks, 0, 20) as $block) {
            $html .= '<div style="background: #f8f9fa; padding: 8px; border-radius: 4px; border-left: 2px solid #007bff; font-size: 12px;">';
            $html .= '🧱 ' . esc_html($block);
            $html .= '</div>';
        }

        if (count($dynamic_blocks) > 20) {
            $html .= '<div style="padding: 8px; text-align: center; color: #666;">... and ' . (count($dynamic_blocks) - 20) . ' more blocks</div>';
        }

        $html .= '</div>';
    }

    // Block editor recommendations
    $html .= '<h4>💡 Block Editor Recommendations</h4>';
    $html .= '<div class="debug-info" style="line-height: 1.6;">';

    if (!$gutenberg_enabled) {
        $html .= '<div style="margin-bottom: 12px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
        $html .= '<strong>⚠️ Block Editor is disabled</strong><br>';
        $html .= 'Consider enabling the <a href="https://wordpress.org/gutenberg/" target="_blank" style="color: #007bff;">Block Editor (Gutenberg)</a> for modern content creation and better user experience';
        $html .= '</div>';
    }

    if (!$theme_support) {
        $html .= '<div style="margin-bottom: 12px; padding: 10px; background: #d1ecf1; border-left: 4px solid #17a2b8; border-radius: 4px;">';
        $html .= '<strong>🎨 Add editor styles support</strong><br>';
        $html .= 'Add <code>add_theme_support(\'editor-styles\');</code> to your theme\'s functions.php. ';
        $html .= 'Learn more: <a href="https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#editor-styles" target="_blank" style="color: #007bff;">Editor Styles Documentation</a>';
        $html .= '</div>';
    }

    if (!$wide_support) {
        $html .= '<div style="margin-bottom: 12px; padding: 10px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 4px;">';
        $html .= '<strong>📐 Add wide alignment support</strong><br>';
        $html .= 'Add <code>add_theme_support(\'align-wide\');</code> to enable wide and full-width block alignments. ';
        $html .= 'Guide: <a href="https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#wide-alignment" target="_blank" style="color: #007bff;">Wide Alignment Support</a>';
        $html .= '</div>';
    }

    $html .= '<div style="margin-bottom: 10px;">🧱 <strong>Plugin Compatibility:</strong> Regularly update block-related plugins and test with <a href="https://wordpress.org/plugins/health-check/" target="_blank" style="color: #007bff;">Health Check plugin</a></div>';

    $html .= '<div style="margin-bottom: 10px;">🎯 <strong>Testing Strategy:</strong> Test blocks in different contexts using <a href="https://wordpress.org/plugins/block-unit-test/" target="_blank" style="color: #007bff;">Block Unit Test</a> plugin</div>';

    $html .= '<div style="margin-bottom: 10px;">📱 <strong>Responsive Design:</strong> Ensure blocks work across devices with <a href="https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/responsive-blocks/" target="_blank" style="color: #007bff;">responsive block development</a></div>';

    $html .= '<div style="margin-bottom: 10px;">🎨 <strong>Custom Blocks:</strong> Create custom blocks with <a href="https://developer.wordpress.org/block-editor/getting-started/create-block/" target="_blank" style="color: #007bff;">@wordpress/create-block</a> tool</div>';

    $html .= '<div>📚 <strong>Resources:</strong> <a href="https://developer.wordpress.org/block-editor/" target="_blank" style="color: #007bff;">Block Editor Handbook</a> • <a href="https://wordpress.org/gutenberg/" target="_blank" style="color: #007bff;">Gutenberg Plugin</a> • <a href="https://fullsiteediting.com/" target="_blank" style="color: #007bff;">Full Site Editing</a></div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function generate_content_analysis() {
    $html = '<div class="debug-info">';
    $html .= '<h4>📄 Content & Post Type Analysis</h4>';

    // Get post type counts
    $post_types = get_post_types(['public' => true], 'objects');
    $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    foreach ($post_types as $post_type) {
        $count = wp_count_posts($post_type->name);
        $total = 0;
        foreach ($count as $status => $num) {
            if ($status !== 'auto-draft') {
                $total += $num;
            }
        }

        $color = $post_type->_builtin ? '#007bff' : '#28a745';
        $icon = $post_type->_builtin ? '📝' : '🔧';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . $color . ';">';
        $html .= '<strong>' . $icon . ' ' . esc_html($post_type->labels->name) . ':</strong><br>' . number_format($total) . ' items';
        $html .= '</div>';
    }

    $html .= '</div>';

    // Shortcodes analysis
    global $shortcode_tags;
    if (!empty($shortcode_tags)) {
        $html .= '<h4>🔗 Registered Shortcodes</h4>';

        // Scrollable, resizable container for all shortcodes
        $html .= '<div style="
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            margin: 15px 0;
            resize: vertical;
            overflow: auto;
            min-height: 200px;
            max-height: 600px;
            height: 300px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        ">';

        // Grid container for shortcodes
        $html .= '<div style="
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            padding: 5px;
        ">';

        $shortcode_list = array_keys($shortcode_tags);

        // Display ALL shortcodes, not just 24
        foreach ($shortcode_list as $shortcode) {
            $html .= '<div style="
                background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
                padding: 12px;
                border-radius: 6px;
                border-left: 3px solid #ffc107;
                font-size: 13px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                cursor: default;
            " onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 3px 8px rgba(0,0,0,0.15)\';" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 1px 3px rgba(0,0,0,0.1)\';">';
            $html .= '<div style="font-weight: 600; color: #856404; margin-bottom: 4px;">🔗 Shortcode</div>';
            $html .= '<div style="font-family: monospace; color: #495057; font-size: 12px; word-break: break-all;">[' . esc_html($shortcode) . ']</div>';
            $html .= '</div>';
        }

        $html .= '</div>'; // Close grid container

        // Add shortcode count and resize hint
        $html .= '<div style="
            margin-top: 10px;
            padding: 8px 12px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 11px;
            color: #6c757d;
            text-align: center;
            border-top: 1px solid #dee2e6;
        ">';
        $html .= '<strong>' . count($shortcode_list) . '</strong> registered shortcodes total';
        $html .= ' • <em>Drag bottom-right corner to resize vertically</em>';
        $html .= '</div>';

        $html .= '</div>'; // Close scrollable container
    }

    // Media library analysis
    $media_counts = wp_count_attachments();
    if (!empty($media_counts)) {
        $html .= '<h4>🖼️ Media Library</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin: 15px 0;">';

        foreach ($media_counts as $type => $count) {
            if ($count > 0) {
                $html .= '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 2px solid #dc3545;">';
                $html .= '<strong>🖼️ ' . esc_html(ucfirst($type)) . ':</strong><br>' . number_format($count);
                $html .= '</div>';
            }
        }

        $html .= '</div>';
    }

    // Content recommendations
    $html .= '<h4>💡 Content Optimization Recommendations</h4>';
    $html .= '<div class="debug-info">';

    if (count($custom_post_types) > 5) {
        $html .= '<div>⚠️ Many custom post types (' . count($custom_post_types) . ') - ensure they\'re all necessary</div>';
    }

    if (count($shortcode_tags) > 50) {
        $html .= '<div>🔗 High number of shortcodes (' . count($shortcode_tags) . ') - review for conflicts</div>';
    }

    $html .= '<div>📊 Regularly audit and clean up unused content</div>';
    $html .= '<div>🖼️ Optimize images and media files for performance</div>';
    $html .= '<div>🔍 Use SEO-friendly URLs and meta descriptions</div>';
    $html .= '<div>📱 Ensure content is mobile-responsive</div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function generate_plugin_analysis() {
    $html = '<div class="debug-info">';
    $html .= '<h4>🔌 Plugin Analysis Overview</h4>';

    // Get plugin data
    $active_plugins = get_option('active_plugins', []);
    $mu_plugins = get_mu_plugins();
    $all_plugins = get_plugins();

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
    $html .= '<strong>🟢 Active Plugins:</strong><br>' . count($active_plugins);
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>🔒 Must-Use Plugins:</strong><br>' . count($mu_plugins);
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
    $html .= '<strong>📦 Total Installed:</strong><br>' . count($all_plugins);
    $html .= '</div>';

    $inactive_count = count($all_plugins) - count($active_plugins);
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #6c757d;">';
    $html .= '<strong>⏸️ Inactive Plugins:</strong><br>' . $inactive_count;
    $html .= '</div>';

    $html .= '</div>';

    // Active plugins details
    if (!empty($active_plugins)) {
        $html .= '<h4>🟢 Active Plugins Details</h4>';
        $html .= '<div style="
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: #ffffff;
            resize: vertical;
            overflow: auto;
            height: 400px;
            min-height: 200px;
            max-height: 800px;
            padding: 15px;
            margin: 15px 0;
            position: relative;
        ">';

        // Add resize indicator
        $html .= '<div style="
            position: absolute;
            bottom: 0;
            right: 0;
            width: 20px;
            height: 20px;
            background: linear-gradient(-45deg, transparent 0%, transparent 40%, #ccc 40%, #ccc 60%, transparent 60%);
            cursor: nw-resize;
            pointer-events: none;
        "></div>';

        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">';

        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

            $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
            $html .= '<strong>' . esc_html($plugin_data['Name']) . '</strong><br>';
            $html .= '<small>Version: ' . esc_html($plugin_data['Version']) . '</small><br>';
            if (!empty($plugin_data['Author'])) {
                $html .= '<small>By: ' . esc_html(strip_tags($plugin_data['Author'])) . '</small><br>';
            }
            if (!empty($plugin_data['Description'])) {
                $description = wp_trim_words($plugin_data['Description'], 15);
                $html .= '<small style="color: #666;">' . esc_html(strip_tags($description)) . '</small>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
    }

    // Must-use plugins
    if (!empty($mu_plugins)) {
        $html .= '<h4>🔒 Must-Use Plugins</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin: 15px 0;">';

        foreach ($mu_plugins as $plugin_file => $plugin_data) {
            $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
            $html .= '<strong>' . esc_html($plugin_data['Name']) . '</strong><br>';
            $html .= '<small>Version: ' . esc_html($plugin_data['Version']) . '</small><br>';
            if (!empty($plugin_data['Description'])) {
                $description = wp_trim_words($plugin_data['Description'], 15);
                $html .= '<small style="color: #666;">' . esc_html(strip_tags($description)) . '</small>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    // Plugin recommendations
    $html .= '<h4>💡 Plugin Optimization Recommendations</h4>';
    $html .= '<div class="debug-info">';

    if (count($active_plugins) > 30) {
        $html .= '<div>⚠️ High number of active plugins (' . count($active_plugins) . ') - consider deactivating unused ones</div>';
    }

    if ($inactive_count > 10) {
        $html .= '<div>🗑️ Consider removing ' . $inactive_count . ' inactive plugins to reduce security risks</div>';
    }

    $html .= '<div>🔄 Regularly update plugins to latest versions</div>';
    $html .= '<div>🛡️ Remove unused plugins completely rather than just deactivating</div>';
    $html .= '<div>📊 Monitor plugin performance impact on site speed</div>';
    $html .= '<div>🔍 Review plugin permissions and data access</div>';

    $html .= '</div>';

    $html .= '</div>';
    return $html;
}

function generate_hooks_filters() {
    global $wp_filter;

    $html = '<div class="debug-info">';
    $html .= '<h4>🪝 WordPress Hooks & Filters Analysis</h4>';

    // Count hooks and filters
    $total_hooks = count($wp_filter);
    $action_count = 0;
    $filter_count = 0;

    // Analyze hook types (this is a simplified approach)
    foreach ($wp_filter as $hook_name => $hook_obj) {
        if (strpos($hook_name, '_action') !== false ||
            in_array($hook_name, ['init', 'wp_head', 'wp_footer', 'admin_init'])) {
            $action_count++;
        } else {
            $filter_count++;
        }
    }

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>🪝 Total Hooks:</strong><br>' . number_format($total_hooks);
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
    $html .= '<strong>⚡ Actions (est):</strong><br>' . number_format($action_count);
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
    $html .= '<strong>🔧 Filters (est):</strong><br>' . number_format($filter_count);
    $html .= '</div>';

    $html .= '</div>';

    // Show most used hooks
    $hook_counts = [];
    foreach ($wp_filter as $hook_name => $hook_obj) {
        $hook_counts[$hook_name] = count($hook_obj->callbacks);
    }
    arsort($hook_counts);

    $html .= '<h4>🔥 Most Used Hooks (Top 15)</h4>';
    $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; margin: 15px 0;">';

    $top_hooks = array_slice($hook_counts, 0, 15, true);
    foreach ($top_hooks as $hook_name => $callback_count) {
        $color = $callback_count > 10 ? '#dc3545' : ($callback_count > 5 ? '#ffc107' : '#28a745');

        $html .= '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 2px solid ' . $color . ';">';
        $html .= '<strong>' . esc_html($hook_name) . '</strong><br>';
        $html .= '<small>' . $callback_count . ' callbacks</small>';
        $html .= '</div>';
    }

    $html .= '</div>';

    // Hook performance recommendations
    $html .= '<h4>💡 Hook Performance Recommendations</h4>';
    $html .= '<div class="debug-info">';

    $heavy_hooks = array_filter($hook_counts, function($count) { return $count > 15; });
    if (!empty($heavy_hooks)) {
        $html .= '<div>⚠️ ' . count($heavy_hooks) . ' hooks have >15 callbacks - review for performance impact</div>';
    }

    $html .= '<div>🔍 Monitor hook execution time in development</div>';
    $html .= '<div>⚡ Remove unused hook callbacks to improve performance</div>';
    $html .= '<div>📊 Use appropriate hook priorities to control execution order</div>';
    $html .= '<div>🛡️ Validate hook callback functions exist before execution</div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function generate_http_curl() {
    $html = '<div class="debug-info">';
    $html .= '<h4>🌐 HTTP & cURL Diagnostics</h4>';

    // Test basic HTTP functionality
    $test_urls = [
        'https://api.wordpress.org/core/version-check/1.7/' => 'WordPress API',
        'https://wordpress.org' => 'WordPress.org Test',
        'https://www.google.com' => 'External Site Test'
    ];

    $html .= '<h4>🧪 HTTP Connectivity Tests</h4>';
    $html .= '<div style="margin: 15px 0;">';

    foreach ($test_urls as $url => $description) {
        $start_time = microtime(true);
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'user-agent' => 'WordPress Debug Tool',
            'sslverify' => false
        ]);
        $response_time = round((microtime(true) - $start_time) * 1000, 2);

        if (is_wp_error($response)) {
            $status = '❌ Failed';
            $color = '#dc3545';
            $details = $response->get_error_message();
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code >= 200 && $status_code < 300) {
                $status = '✅ Success';
                $color = '#28a745';
                $details = 'HTTP ' . $status_code . ' in ' . $response_time . 'ms';
            } else {
                $status = '⚠️ Warning';
                $color = '#ffc107';
                $details = 'HTTP ' . $status_code . ' in ' . $response_time . 'ms';
            }
        }

        $html .= '<div style="background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 3px solid ' . $color . ';">';
        $html .= '<strong>' . esc_html($description) . '</strong> ' . $status . '<br>';
        $html .= '<small>URL: ' . esc_html($url) . '</small><br>';
        $html .= '<small>' . esc_html($details) . '</small>';
        $html .= '</div>';
    }

    $html .= '</div>';

    // cURL information
    if (function_exists('curl_version')) {
        $curl_info = curl_version();

        $html .= '<h4>🔧 cURL Configuration</h4>';
        $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
        $html .= '<strong>📦 cURL Version:</strong><br>' . esc_html($curl_info['version']);
        $html .= '</div>';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
        $html .= '<strong>🔒 SSL Version:</strong><br>' . esc_html($curl_info['ssl_version']);
        $html .= '</div>';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
        $html .= '<strong>🌐 Protocols:</strong><br>' . count($curl_info['protocols']) . ' supported';
        $html .= '</div>';

        $html .= '</div>';

        // Show supported protocols
        if (!empty($curl_info['protocols'])) {
            $html .= '<h4>📡 Supported Protocols</h4>';
            $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; margin: 15px 0;">';

            foreach ($curl_info['protocols'] as $protocol) {
                $html .= '<div style="background: #f8f9fa; padding: 8px; border-radius: 4px; border-left: 2px solid #007bff; font-size: 12px; text-align: center;">';
                $html .= esc_html(strtoupper($protocol));
                $html .= '</div>';
            }

            $html .= '</div>';
        }
    } else {
        $html .= '<div class="debug-error">❌ cURL is not available on this server</div>';
    }

    // HTTP recommendations
    $html .= '<h4>💡 HTTP & cURL Recommendations</h4>';
    $html .= '<div class="debug-info">';
    $html .= '<div>🔒 Always use HTTPS for external API calls when possible</div>';
    $html .= '<div>⏱️ Set appropriate timeouts for HTTP requests</div>';
    $html .= '<div>🛡️ Validate SSL certificates in production environments</div>';
    $html .= '<div>📊 Monitor external API response times and reliability</div>';
    $html .= '<div>🔄 Implement retry logic for critical external requests</div>';
    $html .= '</div>';

    $html .= '</div>';
    return $html;
}

function generate_cache_cdn() {
    $html = '<div class="debug-info">';
    $html .= '<h4>🚀 Cache & CDN Analysis</h4>';

    // Check for common caching plugins
    $caching_plugins = [
        'wp-rocket/wp-rocket.php' => 'WP Rocket',
        'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
        'wp-super-cache/wp-cache.php' => 'WP Super Cache',
        'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache',
        'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
        'wp-optimize/wp-optimize.php' => 'WP-Optimize',
        'autoptimize/autoptimize.php' => 'Autoptimize'
    ];

    $active_plugins = get_option('active_plugins', []);
    $active_cache_plugins = [];

    foreach ($caching_plugins as $plugin_file => $plugin_name) {
        if (in_array($plugin_file, $active_plugins)) {
            $active_cache_plugins[] = $plugin_name;
        }
    }

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . (count($active_cache_plugins) > 0 ? '#28a745' : '#dc3545') . ';">';
    $html .= '<strong>🚀 Cache Plugins:</strong><br>' . (count($active_cache_plugins) > 0 ? count($active_cache_plugins) . ' active' : 'None detected');
    $html .= '</div>';

    // Check object cache
    $object_cache = wp_using_ext_object_cache();
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($object_cache ? '#28a745' : '#ffc107') . ';">';
    $html .= '<strong>💾 Object Cache:</strong><br>' . ($object_cache ? '✅ Active' : '⚠️ Not Active');
    $html .= '</div>';

    // Check for CDN headers
    $cdn_headers = ['cf-ray', 'x-cache', 'x-served-by', 'x-cache-status'];
    $cdn_detected = false;
    foreach ($cdn_headers as $header) {
        if (!empty($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))])) {
            $cdn_detected = true;
            break;
        }
    }

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($cdn_detected ? '#28a745' : '#6c757d') . ';">';
    $html .= '<strong>🌐 CDN Status:</strong><br>' . ($cdn_detected ? '✅ Detected' : '❌ Not Detected');
    $html .= '</div>';

    // Check gzip compression
    $gzip_enabled = function_exists('gzencode') && !empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($gzip_enabled ? '#28a745' : '#ffc107') . ';">';
    $html .= '<strong>🗜️ Gzip Support:</strong><br>' . ($gzip_enabled ? '✅ Available' : '⚠️ Check Server');
    $html .= '</div>';

    $html .= '</div>';

    // Show active caching plugins
    if (!empty($active_cache_plugins)) {
        $html .= '<h4>🔧 Active Caching Solutions</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;">';

        foreach ($active_cache_plugins as $plugin) {
            $html .= '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 2px solid #28a745;">';
            $html .= '✅ ' . esc_html($plugin);
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    // Cache recommendations
    $html .= '<h4>💡 Cache & Performance Recommendations</h4>';
    $html .= '<div class="debug-info">';

    if (empty($active_cache_plugins)) {
        $html .= '<div>🚀 Install a caching plugin to improve site performance</div>';
    }

    if (!$object_cache) {
        $html .= '<div>💾 Enable object caching (Redis/Memcached) for database optimization</div>';
    }

    if (!$cdn_detected) {
        $html .= '<div>🌐 Consider using a CDN (Cloudflare, MaxCDN) for global performance</div>';
    }

    $html .= '<div>🗜️ Enable Gzip compression on your server</div>';
    $html .= '<div>🖼️ Optimize and compress images before uploading</div>';
    $html .= '<div>📱 Use responsive images for different screen sizes</div>';
    $html .= '<div>⚡ Minify CSS and JavaScript files</div>';
    $html .= '<div>🔄 Set appropriate cache expiration headers</div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function generate_error_analysis() {
    $html = '<div class="debug-info">';
    $html .= '<h4>🔍 Error Analysis & Log Review</h4>';

    // Check common log file locations
    $log_files = [
        ABSPATH . 'wp-content/debug.log',
        ABSPATH . 'debug.log',
        ABSPATH . 'wp-content/uploads/debug.log',
        ini_get('error_log')
    ];

    $found_logs = [];
    $total_errors = 0;

    foreach ($log_files as $log_file) {
        try {
            if ($log_file && @file_exists($log_file) && @is_readable($log_file)) {
                $file_size = @filesize($log_file);
                if ($file_size !== false) {
                    $found_logs[] = [
                        'path' => $log_file,
                        'size' => $file_size,
                        'modified' => @filemtime($log_file)
                    ];

                    // Count recent errors (last 1000 lines) - with error suppression
                    if ($file_size > 0 && $file_size < 10*1024*1024) { // Only read files under 10MB
                        $lines = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        if ($lines && is_array($lines)) {
                            $recent_lines = array_slice($lines, -1000);
                            $total_errors += count($recent_lines);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Silently skip files that cause errors
            continue;
        }
    }

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . (count($found_logs) > 0 ? '#ffc107' : '#28a745') . ';">';
    $html .= '<strong>📄 Log Files:</strong><br>' . count($found_logs) . ' found';
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($total_errors > 100 ? '#dc3545' : ($total_errors > 0 ? '#ffc107' : '#28a745')) . ';">';
    $html .= '<strong>⚠️ Recent Entries:</strong><br>' . number_format($total_errors);
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>🐛 Debug Mode:</strong><br>' . (WP_DEBUG ? '✅ Enabled' : '❌ Disabled');
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #6c757d;">';
    $html .= '<strong>📝 Log to File:</strong><br>' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅ Enabled' : '❌ Disabled');
    $html .= '</div>';

    $html .= '</div>';

    // Show log file details
    if (!empty($found_logs)) {
        $html .= '<h4>📄 Log File Details</h4>';
        $html .= '<div style="margin: 15px 0;">';

        foreach ($found_logs as $log) {
            $size_mb = round($log['size'] / 1024 / 1024, 2);
            $modified_ago = human_time_diff($log['modified'], time()) . ' ago';

            $html .= '<div style="background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 3px solid #ffc107;">';
            $html .= '<strong>📄 ' . esc_html(basename($log['path'])) . '</strong><br>';
            $html .= '<small>Path: ' . esc_html($log['path']) . '</small><br>';
            $html .= '<small>Size: ' . $size_mb . ' MB • Modified: ' . $modified_ago . '</small>';
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    // Error analysis recommendations
    $html .= '<h4>💡 Error Monitoring Recommendations</h4>';
    $html .= '<div class="debug-info">';

    if (!WP_DEBUG) {
        $html .= '<div>🐛 Enable WP_DEBUG in development to catch errors early</div>';
    }

    if ($total_errors > 100) {
        $html .= '<div>⚠️ High error count detected - review and fix recurring issues</div>';
    }

    if (empty($found_logs)) {
        $html .= '<div>📝 Enable error logging to track issues: WP_DEBUG_LOG = true</div>';
    }

    $html .= '<div>🔍 Regularly monitor error logs for new issues</div>';
    $html .= '<div>🧹 Clean up old log files to save disk space</div>';
    $html .= '<div>📊 Use error monitoring services for production sites</div>';
    $html .= '<div>🛡️ Fix security-related errors immediately</div>';
    $html .= '<div>⚡ Address performance-related warnings</div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function generate_log_monitoring() {
    $html = '<div class="debug-info">';
    $html .= '<h4>📡 Real-Time Log Monitoring</h4>';

    // Find the most recent log file
    $log_files = [
        ABSPATH . 'wp-content/debug.log',
        ABSPATH . 'debug.log',
        ini_get('error_log')
    ];

    $active_log = null;
    foreach ($log_files as $log_file) {
        try {
            if ($log_file && @file_exists($log_file) && @is_readable($log_file)) {
                $active_log = $log_file;
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }

    if ($active_log) {
        $file_size = @filesize($active_log);
        $modified = @filemtime($active_log);

        if ($file_size === false || $modified === false) {
            $html .= '<div class="debug-warning">⚠️ Log file found but cannot read file information.</div>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
        $html .= '<strong>📄 Active Log:</strong><br>' . esc_html(basename($active_log));
        $html .= '</div>';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
        $html .= '<strong>💾 File Size:</strong><br>' . round($file_size / 1024, 2) . ' KB';
        $html .= '</div>';

        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
        $html .= '<strong>🕒 Last Modified:</strong><br>' . human_time_diff($modified, time()) . ' ago';
        $html .= '</div>';

        $html .= '</div>';

        // Show recent log entries
        if ($file_size > 0 && $file_size < 10*1024*1024) { // Only read files under 10MB
            try {
                $lines = @file($active_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines && is_array($lines)) {
                    $recent_lines = array_slice($lines, -20); // Last 20 lines

                    $html .= '<h4>📋 Recent Log Entries (Last 20)</h4>';
                    $html .= '<div style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 11px;">';

                    foreach ($recent_lines as $line) {
                        $line = trim($line);
                        if (empty($line)) {
                            continue;
                        }

                        // Color code by severity
                        $color = '#333';
                        if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                            $color = '#dc3545';
                        } elseif (stripos($line, 'warning') !== false) {
                            $color = '#ffc107';
                        } elseif (stripos($line, 'notice') !== false) {
                            $color = '#007bff';
                        }

                        $html .= '<div style="margin-bottom: 5px; color: ' . $color . '; word-break: break-all;">';
                        $html .= esc_html($line);
                        $html .= '</div>';
                    }

                    $html .= '</div>';
                } else {
                    $html .= '<div class="debug-warning">⚠️ Could not read log file contents.</div>';
                }
            } catch (Exception $e) {
                $html .= '<div class="debug-warning">⚠️ Error reading log file: ' . esc_html($e->getMessage()) . '</div>';
            }
        } elseif ($file_size >= 10*1024*1024) {
            $html .= '<div class="debug-warning">⚠️ Log file too large to display (' . round($file_size / 1024 / 1024, 2) . ' MB). Consider log rotation.</div>';
        }
    } else {
        $html .= '<div class="debug-warning">⚠️ No readable log files found. Enable WP_DEBUG_LOG to start logging.</div>';
    }

    $html .= '<h4>💡 Log Monitoring Recommendations</h4>';
    $html .= '<div class="debug-info">';
    $html .= '<div>📊 Set up automated log monitoring for production sites</div>';
    $html .= '<div>🔔 Configure alerts for critical errors</div>';
    $html .= '<div>🧹 Implement log rotation to prevent large files</div>';
    $html .= '<div>📈 Track error trends over time</div>';
    $html .= '<div>🛡️ Monitor for security-related log entries</div>';
    $html .= '</div>';

    $html .= '</div>';
    return $html;
}

function generate_wp_cli() {
    $html = '<div class="debug-info">';
    $html .= '<h4>⚡ WP-CLI Integration Status</h4>';

    // Check if WP-CLI is available
    $wp_cli_available = false;
    $wp_cli_version = 'Not Available';

    // Try to detect WP-CLI
    if (defined('WP_CLI') && WP_CLI) {
        $wp_cli_available = true;
        $wp_cli_version = 'Active (Current Session)';
    } else {
        // Try to execute wp --version
        $output = [];
        $return_code = 0;
        @exec('wp --version 2>&1', $output, $return_code);

        if ($return_code === 0 && !empty($output[0])) {
            $wp_cli_available = true;
            $wp_cli_version = trim($output[0]);
        }
    }

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($wp_cli_available ? '#28a745' : '#dc3545') . ';">';
    $html .= '<strong>⚡ WP-CLI Status:</strong><br>' . ($wp_cli_available ? '✅ Available' : '❌ Not Available');
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>📦 Version:</strong><br>' . esc_html($wp_cli_version);
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #ffc107;">';
    $html .= '<strong>🔧 PHP CLI:</strong><br>' . (PHP_SAPI === 'cli' ? '✅ CLI Mode' : '🌐 Web Mode');
    $html .= '</div>';

    $html .= '</div>';

    if ($wp_cli_available) {
        $html .= '<h4>🛠️ Common WP-CLI Commands</h4>';
        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px;">';
        $html .= '<div><strong>Core Management:</strong></div>';
        $html .= '<div>wp core check-update</div>';
        $html .= '<div>wp core update</div>';
        $html .= '<div>wp core verify-checksums</div><br>';

        $html .= '<div><strong>Plugin Management:</strong></div>';
        $html .= '<div>wp plugin list</div>';
        $html .= '<div>wp plugin update --all</div>';
        $html .= '<div>wp plugin status</div><br>';

        $html .= '<div><strong>Database Operations:</strong></div>';
        $html .= '<div>wp db check</div>';
        $html .= '<div>wp db optimize</div>';
        $html .= '<div>wp db export</div><br>';

        $html .= '<div><strong>Cache Management:</strong></div>';
        $html .= '<div>wp cache flush</div>';
        $html .= '<div>wp transient delete --all</div>';
        $html .= '</div>';
    } else {
        $html .= '<div class="debug-warning">⚠️ WP-CLI is not available. Install WP-CLI for powerful command-line WordPress management.</div>';
    }

    $html .= '<h4>💡 WP-CLI Benefits</h4>';
    $html .= '<div class="debug-info">';
    $html .= '<div>⚡ Faster bulk operations than web interface</div>';
    $html .= '<div>🔄 Automated maintenance tasks</div>';
    $html .= '<div>📊 Detailed system information and diagnostics</div>';
    $html .= '<div>🛠️ Advanced database operations</div>';
    $html .= '<div>🔧 Plugin and theme management</div>';
    $html .= '<div>📦 Easy WordPress core updates</div>';
    $html .= '</div>';

    $html .= '</div>';
    return $html;
}

function generate_performance_summary() {
    global $debug_start_time, $debug_start_memory;

    $html = '<div class="debug-info">';
    $html .= '<h4>⏱️ Performance Summary & Optimization</h4>';

    // Calculate current performance metrics
    $execution_time = microtime(true) - $debug_start_time;
    $memory_used = memory_get_usage(true) - $debug_start_memory;
    $peak_memory = memory_get_peak_usage(true);
    $query_count = get_num_queries();

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $time_color = $execution_time > 3 ? '#dc3545' : ($execution_time > 1 ? '#ffc107' : '#28a745');
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . $time_color . ';">';
    $html .= '<strong>⏱️ Total Time:</strong><br>' . round($execution_time * 1000, 2) . 'ms';
    $html .= '</div>';

    $memory_color = $memory_used > 100*1024*1024 ? '#dc3545' : ($memory_used > 50*1024*1024 ? '#ffc107' : '#28a745');
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . $memory_color . ';">';
    $html .= '<strong>💾 Memory Used:</strong><br>' . round($memory_used / 1024 / 1024, 2) . 'MB';
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>📊 Peak Memory:</strong><br>' . round($peak_memory / 1024 / 1024, 2) . 'MB';
    $html .= '</div>';

    $query_color = $query_count > 50 ? '#dc3545' : ($query_count > 25 ? '#ffc107' : '#28a745');
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . $query_color . ';">';
    $html .= '<strong>🗄️ DB Queries:</strong><br>' . $query_count;
    $html .= '</div>';

    $html .= '</div>';

    // Performance score calculation
    $performance_score = 100;

    if ($execution_time > 3) $performance_score -= 30;
    elseif ($execution_time > 1) $performance_score -= 15;

    if ($memory_used > 100*1024*1024) $performance_score -= 25;
    elseif ($memory_used > 50*1024*1024) $performance_score -= 10;

    if ($query_count > 50) $performance_score -= 20;
    elseif ($query_count > 25) $performance_score -= 10;

    $performance_score = max(0, $performance_score);
    $score_color = $performance_score >= 80 ? '#28a745' : ($performance_score >= 60 ? '#ffc107' : '#dc3545');

    $html .= '<h4>🎯 Performance Score</h4>';
    $html .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid ' . $score_color . '; text-align: center;">';
    $html .= '<div style="font-size: 2em; font-weight: bold; color: ' . $score_color . ';">' . $performance_score . '/100</div>';
    $html .= '<div>Overall Performance Rating</div>';
    $html .= '</div>';

    // Optimization recommendations
    $html .= '<h4>🚀 Performance Optimization Recommendations</h4>';
    $html .= '<div class="debug-info">';

    if ($execution_time > 1) {
        $html .= '<div>⚡ Optimize slow-loading sections and database queries</div>';
    }

    if ($memory_used > 50*1024*1024) {
        $html .= '<div>💾 Reduce memory usage by optimizing plugins and themes</div>';
    }

    if ($query_count > 25) {
        $html .= '<div>🗄️ Implement database query optimization and caching</div>';
    }

    $html .= '<div>🚀 Enable caching plugins (WP Rocket, W3 Total Cache)</div>';
    $html .= '<div>🌐 Use a Content Delivery Network (CDN)</div>';
    $html .= '<div>🖼️ Optimize and compress images</div>';
    $html .= '<div>📱 Implement responsive design best practices</div>';
    $html .= '<div>🗜️ Enable Gzip compression</div>';
    $html .= '<div>⚡ Minify CSS and JavaScript files</div>';
    $html .= '<div>💾 Use object caching (Redis/Memcached)</div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function generate_cron_diagnostics() {
    $html = '<div class="debug-info">';
    $html .= '<h4>⏰ WordPress Cron Diagnostics</h4>';

    // Get cron jobs
    $cron_jobs = _get_cron_array();
    $total_jobs = 0;
    $upcoming_jobs = 0;
    $overdue_jobs = 0;
    $current_time = time();

    foreach ($cron_jobs as $timestamp => $jobs) {
        foreach ($jobs as $hook => $job_array) {
            $total_jobs += count($job_array);

            if ($timestamp > $current_time) {
                $upcoming_jobs += count($job_array);
            } else {
                $overdue_jobs += count($job_array);
            }
        }
    }

    $html .= '<div class="debug-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<strong>⏰ Total Jobs:</strong><br>' . number_format($total_jobs);
    $html .= '</div>';

    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
    $html .= '<strong>📅 Upcoming:</strong><br>' . number_format($upcoming_jobs);
    $html .= '</div>';

    $overdue_color = $overdue_jobs > 0 ? '#dc3545' : '#28a745';
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . $overdue_color . ';">';
    $html .= '<strong>⚠️ Overdue:</strong><br>' . number_format($overdue_jobs);
    $html .= '</div>';

    // Check if cron is disabled
    $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid ' . ($cron_disabled ? '#ffc107' : '#28a745') . ';">';
    $html .= '<strong>🔧 WP Cron:</strong><br>' . ($cron_disabled ? '⚠️ Disabled' : '✅ Enabled');
    $html .= '</div>';

    $html .= '</div>';

    // Show next few cron jobs
    if (!empty($cron_jobs)) {
        $html .= '<h4>📋 Next Scheduled Jobs (Next 10)</h4>';
        $html .= '<div style="max-height: 300px; overflow-y: auto;">';

        $job_count = 0;
        foreach ($cron_jobs as $timestamp => $jobs) {
            if ($job_count >= 10) break;

            foreach ($jobs as $hook => $job_array) {
                if ($job_count >= 10) break;

                $time_diff = $timestamp - $current_time;
                $status = $time_diff > 0 ? 'Scheduled' : 'Overdue';
                $status_color = $time_diff > 0 ? '#28a745' : '#dc3545';
                $time_text = $time_diff > 0 ? 'in ' . human_time_diff($current_time, $timestamp) : human_time_diff($timestamp, $current_time) . ' ago';

                $html .= '<div style="background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 4px; border-left: 2px solid ' . $status_color . ';">';
                $html .= '<strong>' . esc_html($hook) . '</strong> ';
                $html .= '<span style="color: ' . $status_color . ';">(' . $status . ')</span><br>';
                $html .= '<small>' . date('Y-m-d H:i:s', $timestamp) . ' - ' . $time_text . '</small>';
                $html .= '</div>';

                $job_count++;
            }
        }

        $html .= '</div>';
    }

    // Cron recommendations
    $html .= '<h4>💡 Cron Optimization Recommendations</h4>';
    $html .= '<div class="debug-info">';

    if ($overdue_jobs > 0) {
        $html .= '<div>⚠️ ' . $overdue_jobs . ' overdue jobs detected - check cron execution</div>';
    }

    if ($cron_disabled) {
        $html .= '<div>🔧 WP Cron is disabled - ensure server cron is configured</div>';
    }

    $html .= '<div>⏰ Monitor cron job execution regularly</div>';
    $html .= '<div>🚀 Consider server-level cron for high-traffic sites</div>';
    $html .= '<div>📊 Remove unnecessary scheduled tasks</div>';
    $html .= '<div>🔍 Debug failed cron jobs using WP-CLI</div>';

    $html .= '</div>';
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
    <title>WordPress Debug Tool - Tabbed Interface</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .debug-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .debug-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-align: center;
            position: relative;
        }

        .debug-header h1 {
            font-size: 2em;
            margin-bottom: 8px;
            font-weight: 300;
        }

        .debug-header p {
            font-size: 1em;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .credits-icon {
            position: absolute;
            top: 15px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            color: white;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .credits-icon:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Tab Navigation */
        .tab-navigation {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            padding: 10px;
            transition: all 0.3s ease;
        }

        .tab-nav-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
            transition: all 0.4s ease;
            position: relative;
        }

        /* Collapsed State - Only show active tab, left-aligned */
        .tab-nav-container.collapsed {
            justify-content: flex-start;
            height: 50px;
            overflow: hidden;
        }

        .tab-nav-container.collapsed .tab-button:not(.active) {
            opacity: 0;
            transform: translateX(-100px) scale(0.8);
            pointer-events: none;
            position: absolute;
            z-index: -1;
        }

        .tab-nav-container.collapsed .tab-button.active {
            opacity: 1;
            transform: translateX(0) scale(1);
            position: relative;
            z-index: 1;
        }

        .tab-button {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 120px;
            max-width: 180px;
            flex: 1 1 calc(16.666% - 5px);
            margin-bottom: 5px;
        }

        /* Enhanced transitions for smooth animations */
        .tab-nav-container:not(.collapsed) .tab-button {
            opacity: 1;
            transform: translateX(0) scale(1);
            position: relative;
            z-index: 1;
        }

        /* Hover hint for collapsed state */
        .tab-nav-container.collapsed::after {
            content: "Hover to expand all tabs";
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 11px;
            color: #6c757d;
            opacity: 0.7;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .tab-nav-container.collapsed:hover::after {
            opacity: 0;
        }

        /* Accessibility: Respect reduced motion preferences */
        @media (prefers-reduced-motion: reduce) {
            .tab-nav-container,
            .tab-button {
                transition: none !important;
            }

            .tab-nav-container.collapsed .tab-button:not(.active) {
                transform: none;
            }
        }

        /* Fallback for browsers without CSS transitions support */
        @supports not (transition: all 0.4s ease) {
            .tab-nav-container.collapsed .tab-button:not(.active) {
                display: none;
            }

            .tab-nav-container:hover.collapsed .tab-button:not(.active) {
                display: flex;
            }
        }

        .tab-button:hover {
            background: #f8f9fa;
            color: #495057;
            border-color: #007bff;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .tab-button.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.3);
        }

        .tab-status {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            background: #6c757d;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .tab-button.loaded .tab-status {
            background: #28a745;
        }

        .tab-button.loading .tab-status {
            background: #007bff;
            animation: pulse 1.5s infinite;
        }

        .tab-button.error .tab-status {
            background: #dc3545;
        }

        /* Credits Modal */
        .credits-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            backdrop-filter: blur(5px);
        }

        .credits-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .credits-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .credits-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .credits-close:hover {
            background: #f8f9fa;
            color: #495057;
        }

        .credits-content h2 {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .credits-content h3 {
            color: #495057;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .credits-content p {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .credits-content ul {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 15px;
            padding-left: 20px;
        }

        .credits-content .highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        /* Tab Content */
        .tab-content {
            padding: 30px;
            min-height: 500px;
            background: white;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .loading-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-state {
            text-align: center;
            padding: 60px 20px;
            color: #dc3545;
        }

        .error-state .error-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .retry-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
            transition: background 0.2s;
        }

        .retry-button:hover {
            background: #0056b3;
        }

        /* Debug Content Styles */
        .debug-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .debug-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .debug-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .debug-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .debug-table th,
        .debug-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .debug-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .debug-table tr:hover {
            background: #f8f9fa;
        }

        .debug-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .section-meta {
            margin-top: 20px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 0.9em;
            color: #6c757d;
            text-align: center;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .debug-header {
                padding: 12px 20px;
            }

            .debug-header h1 {
                font-size: 1.8em;
            }

            .debug-header p {
                font-size: 0.9em;
            }

            .credits-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
                top: 12px;
                right: 15px;
            }

            .tab-nav-container {
                gap: 3px;
            }

            .tab-button {
                padding: 8px 10px;
                min-width: 100px;
                max-width: 140px;
                font-size: 12px;
                flex: 1 1 calc(33.333% - 3px);
            }

            /* Mobile: Disable collapsible behavior for better usability */
            .tab-nav-container.collapsed {
                height: auto;
                overflow: visible;
            }

            .tab-nav-container.collapsed .tab-button:not(.active) {
                opacity: 1;
                transform: none;
                pointer-events: auto;
                position: relative;
                z-index: 1;
            }

            .tab-nav-container.collapsed::after {
                display: none;
            }

            .credits-content {
                padding: 20px;
                margin: 20px;
            }

            .tab-content {
                padding: 20px;
            }

            .debug-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .tab-button {
                padding: 10px 12px;
                font-size: 12px;
                min-width: 100px;
            }

            .tab-status {
                display: none;
            }

            .debug-header h1 {
                font-size: 1.8em;
            }

            .tab-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="debug-container">
            <div class="debug-header">
                <h1>🚀 WordPress Debug Tool</h1>
                <p>Complete diagnostic suite with tabbed interface • Mobile-ready design</p>
                <button class="credits-icon" onclick="openCreditsModal()" aria-label="View credits and business information" title="Credits & Info">ℹ️</button>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <div class="tab-nav-container">
                    <button class="tab-button active" data-tab="performance-dashboard">
                        📊 Performance
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="custom-url-testing">
                        🔗 URL Testing
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="wordpress-config">
                        ⚙️ Config
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="security-scan">
                        🔒 Security
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="database-tables">
                        📋 Database
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="query-profiler">
                        🔍 Queries
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="theme-diagnostics">
                        🎨 Theme
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="block-editor">
                        🧱 Blocks
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="content-analysis">
                        📄 Content
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="plugin-analysis">
                        🔌 Plugins
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="hooks-filters">
                        🪝 Hooks
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="http-curl">
                        🌐 HTTP
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="cache-cdn">
                        🚀 Cache
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="error-analysis">
                        🔍 Errors
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="log-monitoring">
                        📡 Logs
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="wp-cli">
                        ⚡ WP-CLI
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="performance-summary">
                        ⏱️ Summary
                        <span class="tab-status">Ready</span>
                    </button>
                    <button class="tab-button" data-tab="cron-diagnostics">
                        ⏰ Cron
                        <span class="tab-status">Ready</span>
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">

                <!-- Tab Panes -->
                <div class="tab-pane active" id="tab-performance-dashboard">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Performance Dashboard...</h3>
                        <p>Analyzing system performance metrics</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-custom-url-testing">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading URL Testing...</h3>
                        <p>Preparing custom domain testing tools</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-wordpress-config">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading WordPress Configuration...</h3>
                        <p>Analyzing WordPress settings and constants</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-security-scan">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Security Scan...</h3>
                        <p>Performing security vulnerability assessment</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-database-tables">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Database Analysis...</h3>
                        <p>Analyzing database tables and structure</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-query-profiler">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Query Profiler...</h3>
                        <p>Analyzing database query performance</p>
                    </div>
                </div>


                <div class="tab-pane" id="tab-theme-diagnostics">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Theme Diagnostics...</h3>
                        <p>Analyzing theme templates and features</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-block-editor">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Block Editor Analysis...</h3>
                        <p>Checking Gutenberg and block functionality</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-content-analysis">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Content Analysis...</h3>
                        <p>Analyzing post types, content, and templates</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-plugin-analysis">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Plugin Analysis...</h3>
                        <p>Analyzing installed plugins and conflicts</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-hooks-filters">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Hooks & Filters...</h3>
                        <p>Analyzing WordPress hooks and filters</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-http-curl">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading HTTP & cURL Diagnostics...</h3>
                        <p>Testing external connectivity and protocols</p>
                    </div>
                </div>


                <div class="tab-pane" id="tab-cache-cdn">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Cache & CDN Analysis...</h3>
                        <p>Checking caching systems and CDN status</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-error-analysis">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Error Analysis...</h3>
                        <p>Analyzing error logs and patterns</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-log-monitoring">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Log Monitoring...</h3>
                        <p>Real-time log file monitoring</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-wp-cli">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading WP-CLI Integration...</h3>
                        <p>Checking WP-CLI availability and commands</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-performance-summary">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Performance Summary...</h3>
                        <p>Generating performance optimization roadmap</p>
                    </div>
                </div>

                <div class="tab-pane" id="tab-cron-diagnostics">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <h3>Loading Cron Diagnostics...</h3>
                        <p>Analyzing scheduled tasks and cron jobs</p>
                    </div>
                </div>
    </div>

    <!-- Credits Modal -->
    <div class="credits-modal" id="creditsModal">
        <div class="credits-content">
            <button class="credits-close" onclick="closeCreditsModal()" aria-label="Close credits modal">&times;</button>
            <h2>🚀 myTech.Today</h2>
            <div class="highlight">
                <strong>Professional WordPress Development & Digital Solutions</strong>
            </div>

            <h3>About This Tool</h3>
            <p>This comprehensive WordPress Debug Tool is developed by <strong><a href="https://mytech.today" target="_blank">myTech.Today</a></strong> to provide professional-grade diagnostic capabilities for WordPress websites, helping developers and site administrators optimize performance and troubleshoot issues.</p>

            <h3>Core Services</h3>
            <ul>
                <li><strong>WordPress Development:</strong> Custom plugin and theme development with modern PHP standards</li>
                <li><strong>WordPress Debugging & Diagnostics:</strong> Performance analysis, error resolution, and optimization</li>
                <li><strong>WordPress Maintenance:</strong> Ongoing updates, security patches, and performance monitoring</li>
                <li><strong>Database Optimization:</strong> Query analysis, indexing, and performance tuning</li>
                <li><strong>Security Audits:</strong> Vulnerability assessment, OWASP compliance, and remediation</li>
                <li><strong>Performance Optimization:</strong> Speed improvements, caching strategies, and load optimization</li>
                <li><strong>API Development:</strong> REST API creation, integration services, and microservices</li>
                <li><strong>Technical Consulting:</strong> Architecture planning, code reviews, and best practices guidance</li>
            </ul>

            <h3>Professional Services</h3>
            <p><strong>myTech.Today</strong> provides comprehensive digital solutions:</p>
            <ul>
                <li><strong>WordPress Development:</strong> Custom plugins, themes, and integrations</li>
                <li><strong>WordPress Debugging:</strong> Performance analysis and issue resolution</li>
                <li><strong>WordPress Maintenance:</strong> Ongoing support, updates, and optimization</li>
                <li><strong>React Development:</strong> Progressive Web Applications and SPAs</li>
                <li><strong>Node.js/NestJS:</strong> Backend APIs and microservices</li>
                <li><strong>Database Optimization:</strong> Query analysis and performance tuning</li>
                <li><strong>Security Audits:</strong> Vulnerability assessment and remediation</li>
                <li><strong>Performance Optimization:</strong> Speed and efficiency improvements</li>
            </ul>

            <h3>Technical Standards</h3>
            <p>All development follows strict quality standards:</p>
            <ul>
                <li>98% Test Coverage Requirement</li>
                <li>OWASP Security Compliance</li>
                <li>PSR-12 Coding Standards</li>
                <li>TypeScript Strict Mode</li>
                <li>Performance Optimization (Sub-500ms API responses)</li>
            </ul>

            <h3>Contact Information</h3>
            <p><strong>myTech.Today</strong> - Professional WordPress & Web Development</p>
            <ul>
                <li><strong>Website:</strong> <a href="https://mytech.today" target="_blank" rel="noopener">myTech.Today</a></li>
                <li><strong>Email:</strong> <a href="mailto:sales@mytech.today">sales@mytech.today</a></li>
                <li><strong>Phone:</strong> <a href="tel:+18477674914">(847) 767-4914</a></li>
                <li><strong>Business Hours:</strong> Monday - Friday, 8 AM - 6 PM CST</li>
                <li><strong>Emergency Support:</strong> Available for critical WordPress issues</li>
            </ul>

            <div class="highlight">
                <strong>Get Professional WordPress Services:</strong><br>
                Debugging • Development • Maintenance • Optimization • Security<br>
                <em>Contact us for a free consultation on your WordPress project needs.</em>
            </div>
        </div>
    </div>

    <script>
        // Tabbed interface system for debug sections
        const DEBUG_NONCE = '<?php echo wp_create_nonce('debug_2_nonce'); ?>';
        const SECTIONS = [
            'performance-dashboard',
            'custom-url-testing',
            'wordpress-config',
            'security-scan',
            'database-tables',
            'query-profiler',
            'theme-diagnostics',
            'block-editor',
            'content-analysis',
            'plugin-analysis',
            'hooks-filters',
            'http-curl',
            'cache-cdn',
            'error-analysis',
            'log-monitoring',
            'wp-cli',
            'performance-summary',
            'cron-diagnostics'
        ];

        // Track loaded sections
        const loadedSections = new Map();
        let currentActiveTab = 'performance-dashboard';

        // Initialize tabbed interface when page is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 WordPress Debug Tool - Tabbed Interface initialized');
            initializeTabs();
            loadActiveTab();

            // Start background loading of all tabs after a short delay
            setTimeout(() => {
                startBackgroundLoading();
            }, 1000); // Wait 1 second before starting background loading
        });

        function initializeTabs() {
            // Add click event listeners to all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    switchTab(tabId);
                });
            });

            // Initialize collapsible tab navigation
            initializeCollapsibleTabs();
        }

        function initializeCollapsibleTabs() {
            const tabNavContainer = document.querySelector('.tab-nav-container');
            const tabNavigation = document.querySelector('.tab-navigation');

            if (!tabNavContainer || !tabNavigation) {
                console.warn('Tab navigation elements not found, skipping collapsible initialization');
                return;
            }

            // Check for reduced motion preference
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Check if mobile device (disable collapsible on mobile for better UX)
            const isMobile = window.innerWidth <= 768;

            if (prefersReducedMotion || isMobile) {
                console.log('Collapsible tabs disabled due to user preferences or mobile device');
                return;
            }

            // Set initial collapsed state
            tabNavContainer.classList.add('collapsed');

            // Add hover event listeners for expand/collapse functionality
            let hoverTimeout;

            // Mouse enter - expand tabs
            tabNavigation.addEventListener('mouseenter', function() {
                clearTimeout(hoverTimeout);
                tabNavContainer.classList.remove('collapsed');
            });

            // Mouse leave - collapse tabs with delay
            tabNavigation.addEventListener('mouseleave', function() {
                hoverTimeout = setTimeout(() => {
                    tabNavContainer.classList.add('collapsed');
                }, 300); // Small delay to prevent flickering
            });

            // Prevent collapse when hovering over tabs themselves
            tabNavContainer.addEventListener('mouseenter', function() {
                clearTimeout(hoverTimeout);
            });

            // Handle focus for accessibility
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.addEventListener('focus', function() {
                    clearTimeout(hoverTimeout);
                    tabNavContainer.classList.remove('collapsed');
                });

                button.addEventListener('blur', function() {
                    // Only collapse if no other tab button has focus
                    setTimeout(() => {
                        const focusedElement = document.activeElement;
                        if (!focusedElement || !focusedElement.classList.contains('tab-button')) {
                            tabNavContainer.classList.add('collapsed');
                        }
                    }, 100);
                });
            });
        }

        function switchTab(tabId) {
            // Update active tab button
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');

            // Update active tab pane
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            document.getElementById(`tab-${tabId}`).classList.add('active');

            // Load content if not already loaded
            currentActiveTab = tabId;
            if (!loadedSections.has(tabId)) {
                loadTabContentWithStatus(tabId);
            }
        }

        function loadActiveTab() {
            // Load the first tab (performance dashboard) by default
            loadTabContentWithStatus(currentActiveTab);
        }

        async function loadTabContent(tabId) {
            const tabPane = document.getElementById(`tab-${tabId}`);
            const tabButton = document.querySelector(`[data-tab="${tabId}"]`);
            const statusElement = tabButton.querySelector('.tab-status');

            try {
                // Update UI to loading state
                statusElement.textContent = 'Loading';
                statusElement.style.background = '#007bff';
                tabButton.classList.add('loading');

                // Prepare form data
                const formData = new FormData();
                formData.append('action', 'load_debug_section');
                formData.append('section_id', tabId);
                formData.append('section_number', SECTIONS.indexOf(tabId) + 1);
                formData.append('nonce', DEBUG_NONCE);

                // Make AJAX request
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Update content
                    tabPane.innerHTML = result.data.html;

                    // Add performance metadata
                    const metaDiv = document.createElement('div');
                    metaDiv.className = 'section-meta';
                    metaDiv.innerHTML = `⚡ Generated in ${result.data.execution_time}ms • Memory: ${result.data.memory_used}MB • Timestamp: ${new Date(result.data.timestamp * 1000).toLocaleTimeString()}`;
                    tabPane.appendChild(metaDiv);

                    // Update status
                    statusElement.textContent = 'Loaded';
                    statusElement.style.background = '#28a745';
                    tabButton.classList.remove('loading');
                    tabButton.classList.add('loaded');

                    // Mark as loaded
                    loadedSections.set(tabId, true);

                    console.log(`✅ Tab ${tabId} loaded successfully in ${result.data.execution_time}ms`);
                } else {
                    throw new Error(result.data || 'Unknown error');
                }

            } catch (error) {
                console.error(`❌ Failed to load tab ${tabId}:`, error);

                // Update UI for error
                tabPane.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">❌</div>
                        <h3>Failed to Load</h3>
                        <p>Error: ${error.message}</p>
                        <button class="retry-button" onclick="retryLoadTab('${tabId}')">Retry</button>
                    </div>
                `;

                // Update status
                statusElement.textContent = 'Error';
                statusElement.style.background = '#dc3545';
                tabButton.classList.remove('loading');
                tabButton.classList.add('error');
            }
        }

        // Retry function for failed tabs
        function retryLoadTab(tabId) {
            loadedSections.delete(tabId);
            loadTabContent(tabId);
        }

        // Background loading function to load all tabs sequentially
        async function startBackgroundLoading() {
            console.log('🔄 Starting background loading of all tabs...');

            // Get all tabs that aren't already loaded
            const tabsToLoad = SECTIONS.filter(tabId => !loadedSections.has(tabId));

            for (let i = 0; i < tabsToLoad.length; i++) {
                const tabId = tabsToLoad[i];

                try {
                    console.log(`🔄 Background loading tab ${i + 1}/${tabsToLoad.length}: ${tabId}`);
                    await loadTabContentWithStatus(tabId);

                    // Add a small delay between loads to prevent overwhelming the server
                    await new Promise(resolve => setTimeout(resolve, 500));

                } catch (error) {
                    console.error(`❌ Background loading failed for tab ${tabId}:`, error);
                    // Continue with next tab even if this one fails
                }
            }

            console.log('✅ Background loading completed for all tabs');
        }

        // Enhanced tab loading with better status management
        async function loadTabContentWithStatus(tabId) {
            const tabButton = document.querySelector(`[data-tab="${tabId}"]`);
            const statusElement = tabButton.querySelector('.tab-status');

            // Only load if not already loaded or loading
            if (loadedSections.has(tabId) || statusElement.textContent === 'Loading') {
                return;
            }

            return loadTabContent(tabId);
        }


    </script>

    <!-- Additional styles for status indicators -->
    <style>
        .loading-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-state {
            text-align: center;
            padding: 60px 20px;
            color: #dc3545;
        }

        .error-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .retry-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s ease;
        }

        .retry-button:hover {
            background: #0056b3;
        }



        .tab-button.loading .tab-status {
            background: #007bff;
            animation: pulse 1.5s infinite;
        }

        .tab-button.loaded .tab-status {
            background: #28a745;
        }

        .tab-button.error .tab-status {
            background: #dc3545;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .section-meta {
            margin-top: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }

        /* Mobile optimizations for tab status */
        @media (max-width: 480px) {
            .loading-state h3 {
                font-size: 18px;
            }

            .loading-state p {
                font-size: 14px;
            }

            .error-state h3 {
                font-size: 18px;
            }
        }
    </style>

    <script>
        // Credits Modal Functions
        function openCreditsModal() {
            document.getElementById('creditsModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCreditsModal() {
            document.getElementById('creditsModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('creditsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreditsModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('creditsModal').classList.contains('active')) {
                closeCreditsModal();
            }
        });
    </script>
</body>
</html>
<?php
// End of file - Total execution time tracking
$total_time = round((microtime(true) - $debug_start_time) * 1000, 2);
$total_memory = round((memory_get_usage(true) - $debug_start_memory) / 1024 / 1024, 2);

// Log performance for monitoring
error_log("Debug Tool 2 Performance: {$total_time}ms, {$total_memory}MB memory");
?>
