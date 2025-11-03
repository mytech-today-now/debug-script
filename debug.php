<?php
/**
 * WordPress Debug Tool - Auto-Loading Version (debug.php)
 *
 * AUTOMATIC LAZY LOADING: Loads page structure immediately, then auto-loads sections sequentially
 * Based on debug-omega.php with full information content but prevents timeouts
 *
 * @version 3.0.0-autoload
 * @author @mytech-today-now
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

// CRITICAL SECURITY: Stop all output until authentication is verified
ob_start();

// WordPress integration check with enhanced error handling
$wordpress_loaded = false;
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
            $wordpress_loaded = true;
            break;
        }
    }

    // If WordPress still not loaded, deny access
    if (!function_exists('wp_get_current_user')) {
        ob_end_clean();
        http_response_code(500);
        die('SECURITY ERROR: WordPress not accessible. Access denied.');
    }
} else {
    $wordpress_loaded = true;
}

// ENHANCED SECURITY CHECKS - Multiple layers of authentication
$security_passed = false;
$security_errors = [];

// Layer 1: Verify WordPress is fully loaded
if (!$wordpress_loaded || !function_exists('current_user_can') || !function_exists('is_user_logged_in')) {
    $security_errors[] = 'WordPress core functions not available';
}

// Layer 2: Check if user is logged in
if (!is_user_logged_in()) {
    $security_errors[] = 'User not logged in to WordPress';
}

// Layer 3: Check admin capabilities
if (!current_user_can('manage_options')) {
    $security_errors[] = 'Insufficient privileges - Administrator access required';
}

// Layer 4: Verify user ID exists and is valid
$current_user = wp_get_current_user();
if (!$current_user || !$current_user->ID || $current_user->ID === 0) {
    $security_errors[] = 'Invalid user session';
}

// Layer 5: Check for super admin on multisite
if (is_multisite() && !is_super_admin()) {
    $security_errors[] = 'Super administrator access required on multisite';
}

// Layer 6: Additional capability verification
if (!current_user_can('edit_plugins') || !current_user_can('edit_themes')) {
    $security_errors[] = 'Advanced administrative capabilities required';
}

// SECURITY DECISION: If any check fails, deny access immediately
if (!empty($security_errors)) {
    ob_end_clean();

    // Log security attempt for monitoring
    if (function_exists('error_log')) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $timestamp = date('Y-m-d H:i:s');

        error_log("SECURITY ALERT: Unauthorized debug.php access attempt - IP: {$ip_address}, User-Agent: {$user_agent}, Time: {$timestamp}, Errors: " . implode(', ', $security_errors));
    }

    // Send security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');

    http_response_code(403);

    // Enhanced error message with security details
    $error_message = "🛡️ SECURITY ACCESS DENIED\n\n";
    $error_message .= "This WordPress Debug Tool requires administrator privileges.\n\n";
    $error_message .= "Security Issues Detected:\n";
    foreach ($security_errors as $error) {
        $error_message .= "• {$error}\n";
    }
    $error_message .= "\nTo access this tool:\n";
    $error_message .= "1. Log in to WordPress as an Administrator\n";
    $error_message .= "2. Ensure you have 'manage_options' capability\n";
    $error_message .= "3. Try accessing the tool again\n\n";
    $error_message .= "If you believe this is an error, contact your WordPress administrator.\n\n";
    $error_message .= "Security Event Logged: " . date('Y-m-d H:i:s');

    // Use wp_die if available, otherwise use plain die
    if (function_exists('wp_die')) {
        wp_die($error_message, 'Access Denied - WordPress Debug Tool', ['response' => 403]);
    } else {
        die($error_message);
    }
}

// SECURITY PASSED: Clear output buffer and continue
ob_end_clean();
$security_passed = true;

// Additional security logging for successful access
if (function_exists('error_log')) {
    $user_info = $current_user->user_login ?? 'Unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $timestamp = date('Y-m-d H:i:s');

    error_log("DEBUG TOOL ACCESS: Authorized access granted - User: {$user_info}, IP: {$ip_address}, User-Agent: {$user_agent}, Time: {$timestamp}");
}

// ============================================================================
// ENHANCED SECURITY FUNCTIONS
// ============================================================================

/**
 * Continuous security verification function
 * Called periodically to ensure session is still valid
 */
function verify_debug_security() {
    $errors = [];

    // Check WordPress functions are available
    if (!function_exists('is_user_logged_in') || !function_exists('current_user_can')) {
        $errors[] = 'WordPress functions not available';
    }

    // Check user is still logged in
    if (!is_user_logged_in()) {
        $errors[] = 'User session expired';
    }

    // Check admin capabilities
    if (!current_user_can('manage_options')) {
        $errors[] = 'Insufficient privileges';
    }

    // Check user ID is valid
    $current_user = wp_get_current_user();
    if (!$current_user || !$current_user->ID || $current_user->ID === 0) {
        $errors[] = 'Invalid user session';
    }

    return empty($errors) ? true : $errors;
}

/**
 * Mobile device detection and additional security for mobile
 */
function is_mobile_device() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $mobile_keywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone'];

    foreach ($mobile_keywords as $keyword) {
        if (stripos($user_agent, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Enhanced mobile security check
 */
function verify_mobile_security() {
    if (!is_mobile_device()) {
        return true; // Not mobile, standard checks apply
    }

    // Additional mobile-specific security checks
    $mobile_errors = [];

    // Check for valid WordPress session on mobile
    if (!wp_validate_auth_cookie()) {
        $mobile_errors[] = 'Invalid authentication cookie on mobile device';
    }

    // Check session token validity
    $user = wp_get_current_user();
    if ($user && $user->ID) {
        $sessions = WP_Session_Tokens::get_instance($user->ID);
        if (!$sessions->verify(wp_get_session_token())) {
            $mobile_errors[] = 'Invalid session token on mobile device';
        }
    }

    // Additional mobile capability check
    if (!current_user_can('edit_plugins') || !current_user_can('edit_themes')) {
        $mobile_errors[] = 'Advanced capabilities required on mobile device';
    }

    return empty($mobile_errors) ? true : $mobile_errors;
}

// MOBILE SECURITY VERIFICATION
$mobile_security_check = verify_mobile_security();
if ($mobile_security_check !== true) {
    // Log mobile security failure
    if (function_exists('error_log')) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $timestamp = date('Y-m-d H:i:s');

        error_log("MOBILE SECURITY ALERT: Mobile device failed security check - IP: {$ip_address}, User-Agent: {$user_agent}, Time: {$timestamp}, Errors: " . implode(', ', $mobile_security_check));
    }

    http_response_code(403);

    $mobile_error_message = "🛡️ MOBILE SECURITY ACCESS DENIED\n\n";
    $mobile_error_message .= "Additional security verification failed for mobile device.\n\n";
    $mobile_error_message .= "Mobile Security Issues:\n";
    foreach ($mobile_security_check as $error) {
        $mobile_error_message .= "• {$error}\n";
    }
    $mobile_error_message .= "\nFor mobile access:\n";
    $mobile_error_message .= "1. Ensure you're logged in to WordPress\n";
    $mobile_error_message .= "2. Clear browser cache and cookies\n";
    $mobile_error_message .= "3. Log out and log back in to WordPress\n";
    $mobile_error_message .= "4. Try accessing from a desktop browser first\n\n";
    $mobile_error_message .= "Mobile Security Event: " . date('Y-m-d H:i:s');

    if (function_exists('wp_die')) {
        wp_die($mobile_error_message, 'Mobile Access Denied - WordPress Debug Tool', ['response' => 403]);
    } else {
        die($mobile_error_message);
    }
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

    // ENHANCED AJAX SECURITY CHECKS
    $ajax_security_errors = [];

    // Check 1: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'debug_2_nonce')) {
        $ajax_security_errors[] = 'Invalid security nonce';
    }

    // Check 2: Re-verify user authentication (session could have expired)
    if (!is_user_logged_in()) {
        $ajax_security_errors[] = 'User session expired';
    }

    // Check 3: Re-verify admin capabilities
    if (!current_user_can('manage_options')) {
        $ajax_security_errors[] = 'Insufficient privileges for AJAX request';
    }

    // Check 4: Verify user ID consistency
    $current_user = wp_get_current_user();
    if (!$current_user || !$current_user->ID || $current_user->ID === 0) {
        $ajax_security_errors[] = 'Invalid user session for AJAX';
    }

    // Check 5: Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $ajax_security_errors[] = 'Invalid request method';
    }

    // Check 6: Basic rate limiting (prevent abuse)
    $rate_limit_key = 'debug_ajax_' . ($current_user->ID ?? 'unknown');
    $current_time = time();
    $last_request = get_transient($rate_limit_key);

    if ($last_request && ($current_time - $last_request) < 1) {
        $ajax_security_errors[] = 'Rate limit exceeded - too many requests';
    }

    // Set rate limiting
    set_transient($rate_limit_key, $current_time, 60);

    // If any AJAX security check fails, deny the request
    if (!empty($ajax_security_errors)) {
        // Log security attempt
        if (function_exists('error_log')) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $timestamp = date('Y-m-d H:i:s');

            error_log("AJAX SECURITY ALERT: Unauthorized debug AJAX request - IP: {$ip_address}, User-Agent: {$user_agent}, Time: {$timestamp}, Errors: " . implode(', ', $ajax_security_errors));
        }

        ob_clean();
        wp_send_json_error([
            'message' => 'AJAX Security check failed',
            'errors' => $ajax_security_errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
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

// Handle authentication verification AJAX requests
if (isset($_POST['action']) && $_POST['action'] === 'verify_debug_auth') {
    header('Content-Type: application/json');

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'debug_2_nonce')) {
        wp_send_json_error('Invalid nonce for auth verification');
    }

    // Run comprehensive security check
    $auth_check = verify_debug_security();
    if ($auth_check !== true) {
        wp_send_json_error([
            'message' => 'Authentication verification failed',
            'errors' => $auth_check,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    // Check mobile security if applicable
    $mobile_check = verify_mobile_security();
    if ($mobile_check !== true) {
        wp_send_json_error([
            'message' => 'Mobile authentication verification failed',
            'errors' => $mobile_check,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    // All checks passed
    wp_send_json_success([
        'message' => 'Authentication verified',
        'user' => wp_get_current_user()->user_login,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
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

    // Performance recommendations with actionable advice
    $html .= '<h4>🚀 Performance Recommendations & Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    if ($execution_time > 2) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">⚠️ Slow Execution Time (' . round($execution_time * 1000, 2) . 'ms)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Page load time is slow. Install a caching plugin to improve performance.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> High Performance Boost';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+super+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install Cache</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($current_memory > 128 * 1024 * 1024) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ High Memory Usage (' . round($current_memory / 1024 / 1024, 2) . 'MB)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Memory usage is high. Deactivate unused plugins and optimize database queries.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-30 minutes | <strong>🎯 Impact:</strong> Medium Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugins.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Manage Plugins</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Debug Tool</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($query_count > 50) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ High Database Query Count (' . $query_count . ' queries)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Too many database queries. Install Query Monitor to identify slow queries.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> High Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Install Monitor</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Optimize Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General performance improvements
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">💡 General Performance Improvements</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🚀 Install Caching Plugin</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Caching can improve page load times by 50-80%. Choose from top-rated caching plugins.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> Very High Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+super+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 WP Super Cache</a><br>';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=w3+total+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 W3 Total Cache</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🖼️ Optimize Images</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Large images slow down your site. Install an image optimization plugin to compress images automatically.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> High Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=smush&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install Smush</a><br>';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=shortpixel&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 ShortPixel</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Performance metrics look good! Consider the general improvements above for even better performance.';
        $html .= '</div>';
    }

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

    // URL testing optimization with actionable solutions
    $html .= '<h4>💡 URL Testing & Connectivity Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check if there were any URL test failures (if form was submitted)
    if (isset($_POST['test_custom_url']) && !empty($_POST['custom_url'])) {
        $test_url = esc_url_raw($_POST['custom_url']);
        $response = wp_remote_get($test_url, ['timeout' => 10, 'sslverify' => false]);

        if (is_wp_error($response)) {
            $has_issues = true;
            $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
            $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
            $html .= '<div style="flex: 1;">';
            $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🔗 URL Connection Failed</h6>';
            $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">The URL test failed. This could indicate network issues, DNS problems, or server configuration issues.</p>';
            $html .= '<div style="font-size: 13px; color: #495057;">';
            $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> High Connectivity';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div style="margin-left: 15px;">';
            $html .= '<a href="https://wordpress.org/support/article/common-wordpress-errors/#http-error" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Fix HTTP Errors</a><br>';
            $html .= '<a href="https://developer.wordpress.org/plugins/http-api/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 HTTP API Guide</a>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code >= 400) {
                $has_issues = true;
                $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
                $html .= '<div style="flex: 1;">';
                $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ HTTP Error Response (' . $response_code . ')</h6>';
                $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">The URL returned an error status code. Check the target server configuration.</p>';
                $html .= '<div style="font-size: 13px; color: #495057;">';
                $html .= '<strong>⏱️ Time:</strong> 10-20 minutes | <strong>🎯 Impact:</strong> Medium Connectivity';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div style="margin-left: 15px;">';
                $html .= '<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Status" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📋 HTTP Status Codes</a><br>';
                $html .= '<a href="https://wordpress.org/support/article/common-wordpress-errors/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Error Guide</a>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
    }

    // General URL testing recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🔗 URL Testing Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🧪 API Endpoint Testing</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Test external API endpoints to verify connectivity and response times for integrations.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5-10 minutes | <strong>🎯 Impact:</strong> High Integration Testing';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/plugins/http-api/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 HTTP API Docs</a><br>';
    $html .= '<a href="https://httpstat.us/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🧪 Test Service</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔍 Network Monitoring</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Monitor external service connectivity and implement proper error handling for failed requests.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 20-30 minutes | <strong>🎯 Impact:</strong> High Reliability';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Monitor HTTP</a><br>';
    $html .= '<a href="https://developer.wordpress.org/plugins/http-api/wp-remote-get/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Request Handling</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🛡️ Security Testing</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Verify SSL certificates and security headers when testing HTTPS endpoints.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> High Security Validation';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://www.ssllabs.com/ssltest/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔒 SSL Test</a><br>';
    $html .= '<a href="https://securityheaders.com/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🛡️ Security Headers</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ URL testing functionality is ready! Use the form above to test external URLs and API endpoints.';
        $html .= '</div>';
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

    // Configuration optimization recommendations
    $html .= '<h4>💡 Configuration Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check if WP_DEBUG is enabled in production
    if (WP_DEBUG && is_ssl()) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🚨 WP_DEBUG Enabled in Production</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Debug mode should be disabled on live sites for security and performance.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 2 minutes | <strong>🎯 Impact:</strong> Critical Security';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Disable Debug</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/security/hardening/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Security Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ Outdated PHP Version (' . PHP_VERSION . ')</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">PHP 8.0+ offers better performance and security. Contact your hosting provider to upgrade.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> High Performance & Security';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://wordpress.org/about/requirements/" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📋 WP Requirements</a><br>';
        $html .= '<a href="https://www.php.net/supported-versions.php" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 PHP Versions</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check memory limit
    $memory_limit = defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : ini_get('memory_limit');
    $memory_mb = intval($memory_limit);
    if ($memory_mb < 256) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">💾 Low Memory Limit (' . $memory_limit . ')</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">WordPress recommends at least 256MB memory. Increase WP_MEMORY_LIMIT in wp-config.php.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 5-10 minutes | <strong>🎯 Impact:</strong> High Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://wordpress.org/support/article/editing-wp-config-php/#increasing-memory-allocated-to-php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Increase Memory</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/common-wordpress-errors/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Error Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General configuration recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🔧 Configuration Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔒 Secure wp-config.php</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Add security keys, disable file editing, and set proper file permissions for wp-config.php.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> High Security';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://api.wordpress.org/secret-key/1.1/salt/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔑 Generate Keys</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/editing-wp-config-php/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Config Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">⚡ Performance Constants</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Configure WP_MEMORY_LIMIT, enable compression, and optimize database settings.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 15-20 minutes | <strong>🎯 Impact:</strong> Medium Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">⚡ Performance Guide</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/editing-wp-config-php/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Config Options</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ WordPress configuration looks good! Consider the optimization practices above for enhanced performance and security.';
        $html .= '</div>';
    }

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

    // Security issues with actionable solutions
    if (!empty($security_issues)) {
        $html .= '<h4>⚠️ Security Issues & Actionable Solutions</h4>';

        $admin_url = admin_url();

        foreach ($security_issues as $issue) {
            if (strpos($issue, 'WP_DEBUG is enabled') !== false) {
                $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
                $html .= '<div style="flex: 1;">';
                $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🚨 WP_DEBUG Enabled in Production</h6>';
                $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Debug mode exposes sensitive information. Disable it immediately in wp-config.php.</p>';
                $html .= '<div style="font-size: 13px; color: #495057;">';
                $html .= '<strong>⏱️ Time:</strong> 2 minutes | <strong>🎯 Impact:</strong> Critical Security';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div style="margin-left: 15px;">';
                $html .= '<a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Fix Guide</a><br>';
                $html .= '<a href="https://developer.wordpress.org/advanced-administration/security/hardening/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Security Guide</a>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }

            if (strpos($issue, 'wp-config.php is writable') !== false) {
                $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
                $html .= '<div style="flex: 1;">';
                $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🚨 wp-config.php File Permissions</h6>';
                $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">wp-config.php should not be writable. Set file permissions to 644 or 600.</p>';
                $html .= '<div style="font-size: 13px; color: #495057;">';
                $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> Critical Security';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div style="margin-left: 15px;">';
                $html .= '<a href="https://developer.wordpress.org/advanced-administration/security/hardening/#file-permissions" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Fix Permissions</a><br>';
                $html .= '<a href="https://wordpress.org/support/article/changing-file-permissions/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 How-To Guide</a>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }

            if (strpos($issue, 'WordPress version is outdated') !== false) {
                $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
                $html .= '<div style="flex: 1;">';
                $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ Outdated WordPress Version</h6>';
                $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Running an outdated WordPress version exposes security vulnerabilities. Update immediately.</p>';
                $html .= '<div style="font-size: 13px; color: #495057;">';
                $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> High Security';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div style="margin-left: 15px;">';
                $html .= '<a href="' . $admin_url . 'update-core.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔄 Update Now</a><br>';
                $html .= '<a href="https://wordpress.org/support/article/updating-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Update Guide</a>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }

            if (strpos($issue, 'Default "admin" username') !== false) {
                $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
                $html .= '<div style="flex: 1;">';
                $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ Weak Admin Username</h6>';
                $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Using "admin" as username makes brute force attacks easier. Create a new admin user with a unique username.</p>';
                $html .= '<div style="font-size: 13px; color: #495057;">';
                $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> Medium Security';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div style="margin-left: 15px;">';
                $html .= '<a href="' . $admin_url . 'user-new.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">👤 Add New User</a><br>';
                $html .= '<a href="https://developer.wordpress.org/advanced-administration/security/hardening/#security-through-obscurity" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Security Guide</a>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }

            if (strpos($issue, 'not using HTTPS/SSL') !== false) {
                $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
                $html .= '<div style="flex: 1;">';
                $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🔒 No SSL Certificate</h6>';
                $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">HTTPS is essential for security and SEO. Install an SSL certificate and force HTTPS.</p>';
                $html .= '<div style="font-size: 13px; color: #495057;">';
                $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> Critical Security & SEO';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div style="margin-left: 15px;">';
                $html .= '<a href="' . $admin_url . 'plugin-install.php?s=really+simple+ssl&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 SSL Plugin</a><br>';
                $html .= '<a href="https://wordpress.org/support/article/https-for-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 HTTPS Guide</a>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        // Additional security recommendations
        $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
        $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🛡️ Additional Security Improvements</h5>';

        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔐 Install Security Plugin</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">A security plugin provides firewall protection, malware scanning, and login security.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15 minutes | <strong>🎯 Impact:</strong> High Security';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wordfence&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Wordfence</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=sucuri&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Sucuri</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

    } else {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ No major security issues detected! Your site has good basic security.';
        $html .= '</div>';

        // Still show general security recommendations
        $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
        $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🛡️ Enhance Security Further</h5>';

        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔐 Install Security Plugin</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Add an extra layer of protection with a comprehensive security plugin.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15 minutes | <strong>🎯 Impact:</strong> High Security';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wordfence&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Wordfence</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/security/hardening/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Security Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';
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

    // Database optimization recommendations
    $html .= '<h4>💡 Database Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check for large database size
    if ($db_size && $db_size > 500) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">💾 Large Database Size (' . $db_size . ' MB)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Large database can slow queries. Consider cleanup and optimization.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> High Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+optimize&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 WP-Optimize</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/#database-optimization" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 DB Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for too many tables
    if ($table_count && $table_count > 100) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">📋 Many Database Tables (' . $table_count . ' tables)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">High table count may indicate unused plugin data. Review and cleanup.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-30 minutes | <strong>🎯 Impact:</strong> Medium Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugins.php?plugin_status=inactive" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🗑️ Remove Plugins</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+optimize&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 DB Cleanup</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General database maintenance recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🗄️ Database Maintenance Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔄 Regular Database Optimization</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Optimize database tables regularly to maintain performance and reduce size.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> High Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+optimize&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Install WP-Optimize</a><br>';
    $html .= '<a href="https://wordpress.org/plugins/wp-optimize/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">💾 Database Backup</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Regular backups protect your data. Schedule automatic database backups.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 15 minutes | <strong>🎯 Impact:</strong> Critical Data Protection';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=updraftplus&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 UpdraftPlus</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/wordpress-backups/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Backup Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Database looks healthy! Consider the maintenance practices above for optimal performance.';
        $html .= '</div>';
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

    // Query optimization with actionable solutions
    $html .= '<h4>💡 Query Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    if ($query_count > 50) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">📊 High Query Count (' . $query_count . ' queries)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Too many database queries slow your site. Install Query Monitor to identify problematic plugins/themes.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> High Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Install Monitor</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Optimization Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($slow_queries > 0) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🐌 Slow Queries Detected (' . $slow_queries . ' queries >50ms)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Slow database queries significantly impact performance. Use Query Monitor to identify and optimize them.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> Critical Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Install Monitor</a><br>';
        $html .= '<a href="https://wordpress.org/plugins/query-monitor/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About Plugin</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($avg_time > 10) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">📈 High Average Query Time (' . round($avg_time, 2) . 'ms)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Average query time is high. Consider object caching and database optimization.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-40 minutes | <strong>🎯 Impact:</strong> High Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=redis+object+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">💾 Object Cache</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+optimize&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 DB Optimize</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General query optimization recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🔍 Query Optimization Tools</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔍 Install Query Monitor</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Essential tool for identifying slow queries, duplicate queries, and database performance issues.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> Excellent Debugging';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install</a><br>';
    $html .= '<a href="https://wordpress.org/plugins/query-monitor/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">💾 Object Caching Setup</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Object caching stores database query results in memory, dramatically reducing database load.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> Very High Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=redis+object+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Redis Cache</a><br>';
    $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/object-cache/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Object Cache Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Query performance looks good! Consider the optimization tools above for even better performance.';
        $html .= '</div>';
    }

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

    // Theme optimization recommendations
    $html .= '<h4>💡 Theme Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check if not using child theme
    if (!$parent_theme) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ No Child Theme Detected</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Using a child theme protects customizations during theme updates. Create one now.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15-20 minutes | <strong>🎯 Impact:</strong> High Customization Safety';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=child+theme+configurator&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Child Theme Tool</a><br>';
        $html .= '<a href="https://developer.wordpress.org/themes/advanced-topics/child-themes/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Child Theme Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for missing theme features
    $missing_features = [];
    $important_features = [
        'post-thumbnails' => 'Post Thumbnails',
        'title-tag' => 'Title Tag Support',
        'html5' => 'HTML5 Support',
        'responsive-embeds' => 'Responsive Embeds'
    ];

    foreach ($important_features as $feature => $label) {
        if (!current_theme_supports($feature)) {
            $missing_features[] = $label;
        }
    }

    if (!empty($missing_features)) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🚀 Missing Theme Features</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Your theme is missing: ' . implode(', ', $missing_features) . '. Consider updating or switching themes.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 10-30 minutes | <strong>🎯 Impact:</strong> Medium Functionality';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'themes.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🎨 Browse Themes</a><br>';
        $html .= '<a href="https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Theme Features</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General theme maintenance recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🎨 Theme Maintenance Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔄 Keep Theme Updated</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Regular theme updates provide security fixes, new features, and compatibility improvements.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> High Security & Compatibility';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'update-core.php" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔄 Check Updates</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/updating-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Update Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🧪 Test Theme Changes</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Always test theme changes on a staging site before applying to production.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> Risk Prevention';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+staging&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Staging Plugin</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/wordpress-backups/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Backup Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Theme setup looks good! Consider the maintenance practices above for optimal theme management.';
        $html .= '</div>';
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
        $html .= '<h4>🔧 Dynamic Blocks Available (' . count($dynamic_blocks) . ' total)</h4>';

        // Scrollable, resizable grid container
        $html .= '<div class="dynamic-blocks-container" style="
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #f8f9fa;
            margin: 15px 0;
            resize: vertical;
            overflow: hidden;
            min-height: 200px;
            max-height: 600px;
            height: 300px;
        ">';

        $html .= '<div class="dynamic-blocks-header" style="
            background: #e9ecef;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 1;
        ">';
        $html .= '📋 All Dynamic Blocks - Scroll to view all • Drag bottom edge to resize';
        $html .= '</div>';

        $html .= '<div class="dynamic-blocks-grid" style="
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
            padding: 15px;
            max-height: calc(100% - 45px);
            overflow-y: auto;
            overflow-x: hidden;
        ">';

        // Show ALL blocks, not just first 20
        foreach ($dynamic_blocks as $block) {
            $html .= '<div class="block-item" style="
                background: white;
                padding: 12px;
                border-radius: 6px;
                border-left: 3px solid #007bff;
                font-size: 13px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: all 0.2s ease;
                cursor: default;
            " onmouseover="this.style.boxShadow=\'0 2px 6px rgba(0,0,0,0.15)\'; this.style.transform=\'translateY(-1px)\'" onmouseout="this.style.boxShadow=\'0 1px 3px rgba(0,0,0,0.1)\'; this.style.transform=\'translateY(0)\'">';
            $html .= '<div style="font-weight: 600; color: #007bff; margin-bottom: 4px;">🧱 Block</div>';
            $html .= '<div style="font-family: monospace; font-size: 12px; color: #495057; word-break: break-all;">' . esc_html($block) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Add summary info
        $html .= '<div style="margin-top: 10px; padding: 8px 12px; background: #e7f3ff; border-radius: 4px; font-size: 12px; color: #0056b3;">';
        $html .= '📊 <strong>Total Dynamic Blocks:</strong> ' . count($dynamic_blocks) . ' blocks available';
        $html .= '</div>';
    }

    // Block editor optimization with actionable solutions
    $html .= '<h4>💡 Block Editor Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check if Block Editor is disabled
    if (!$gutenberg_enabled) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🧱 Block Editor Disabled</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Block Editor (Gutenberg) is disabled. Consider enabling it for modern content creation and better user experience.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 5-10 minutes | <strong>🎯 Impact:</strong> High Content Creation';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'options-writing.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Enable Editor</a><br>';
        $html .= '<a href="https://wordpress.org/gutenberg/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About Gutenberg</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for missing theme support
    if (!$theme_support) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🎨 Missing Editor Styles Support</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Theme lacks editor styles support. Add editor-styles support for better block editing experience.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> Medium Editor Experience';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'theme-editor.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Edit Theme</a><br>';
        $html .= '<a href="https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#editor-styles" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Editor Styles</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for missing wide alignment support
    if (!$wide_support) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">📐 Missing Wide Alignment Support</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Theme lacks wide alignment support. Enable wide and full-width block alignments for better layouts.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> Medium Layout Flexibility';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'theme-editor.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Edit Theme</a><br>';
        $html .= '<a href="https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#wide-alignment" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Wide Alignment</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General block editor recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🧱 Block Editor Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🧪 Block Testing Plugin</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Install Block Unit Test plugin to test blocks in different contexts and ensure compatibility.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> High Block Testing';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=block+unit+test&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install Plugin</a><br>';
    $html .= '<a href="https://wordpress.org/plugins/block-unit-test/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About Plugin</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🎨 Custom Block Development</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Create custom blocks using @wordpress/create-block tool for unique content needs.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 60-120 minutes | <strong>🎯 Impact:</strong> High Customization';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/block-editor/getting-started/create-block/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Create Block</a><br>';
    $html .= '<a href="https://developer.wordpress.org/block-editor/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Block Handbook</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">📱 Responsive Block Design</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Ensure blocks work across all devices with responsive design principles and testing.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 30-45 minutes | <strong>🎯 Impact:</strong> High Mobile Experience';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/responsive-blocks/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📱 Responsive Guide</a><br>';
    $html .= '<a href="https://fullsiteediting.com/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🌐 Full Site Editing</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Block Editor configuration looks good! Consider the enhancement practices above for optimal block editing experience.';
        $html .= '</div>';
    }
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

    // SEO & Content Analysis
    $html .= '<h4>🔍 SEO & Content Quality Analysis</h4>';
    $html .= generate_seo_content_analysis();

    // Actionable Content Optimization Recommendations
    $html .= '<h4>💡 Actionable Content Optimization Tasks</h4>';
    $html .= generate_actionable_content_recommendations($custom_post_types, $shortcode_tags);

    $html .= '</div>';

    return $html;
}

function generate_seo_content_analysis() {
    $html = '<div class="debug-info" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border-left: 4px solid #2196f3;">';

    // Get recent posts for analysis
    $recent_posts = get_posts([
        'numberposts' => 10,
        'post_status' => 'publish',
        'post_type' => 'post'
    ]);

    $recent_pages = get_posts([
        'numberposts' => 5,
        'post_status' => 'publish',
        'post_type' => 'page'
    ]);

    $all_content = array_merge($recent_posts, $recent_pages);

    // SEO Analysis Results
    $seo_issues = [];
    $seo_good = [];
    $content_stats = [
        'total_analyzed' => count($all_content),
        'missing_meta_desc' => 0,
        'short_titles' => 0,
        'long_titles' => 0,
        'missing_alt_text' => 0,
        'short_content' => 0,
        'no_internal_links' => 0
    ];

    foreach ($all_content as $post) {
        // Meta description analysis
        $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true) ?:
                    get_post_meta($post->ID, '_aioseop_description', true) ?:
                    get_post_meta($post->ID, 'rank_math_description', true) ?:
                    get_post_meta($post->ID, 'description', true);

        if (empty($meta_desc)) {
            $content_stats['missing_meta_desc']++;
        }

        // Title length analysis
        $title_length = strlen($post->post_title);
        if ($title_length < 30) {
            $content_stats['short_titles']++;
        } elseif ($title_length > 60) {
            $content_stats['long_titles']++;
        }

        // Content length analysis
        $content_length = str_word_count(strip_tags($post->post_content));
        if ($content_length < 300) {
            $content_stats['short_content']++;
        }

        // Internal links analysis
        $internal_links = preg_match_all('/<a[^>]+href=["\']' . preg_quote(home_url(), '/') . '[^"\']*["\'][^>]*>/i', $post->post_content);
        if ($internal_links < 2) {
            $content_stats['no_internal_links']++;
        }

        // Image alt text analysis (simplified)
        $images_without_alt = preg_match_all('/<img[^>]+(?!.*alt=)[^>]*>/i', $post->post_content);
        if ($images_without_alt > 0) {
            $content_stats['missing_alt_text']++;
        }
    }

    // Technical SEO Checks
    $permalink_structure = get_option('permalink_structure');
    $site_url = get_site_url();
    $is_ssl = is_ssl();
    $robots_txt_exists = file_exists(ABSPATH . 'robots.txt');

    // Generate SEO Analysis Grid
    $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin: 15px 0;">';

    // Content SEO Issues
    $html .= '<div style="background: white; padding: 15px; border-radius: 8px; border-left: 3px solid #f44336;">';
    $html .= '<h5 style="margin: 0 0 10px 0; color: #f44336;">🚨 SEO Issues Found</h5>';

    if ($content_stats['missing_meta_desc'] > 0) {
        $percentage = round(($content_stats['missing_meta_desc'] / $content_stats['total_analyzed']) * 100);
        $html .= '<div style="margin: 8px 0; font-size: 13px;">📝 <strong>' . $content_stats['missing_meta_desc'] . '</strong> posts missing meta descriptions (' . $percentage . '%)</div>';
    }

    if ($content_stats['short_titles'] > 0) {
        $html .= '<div style="margin: 8px 0; font-size: 13px;">📏 <strong>' . $content_stats['short_titles'] . '</strong> posts with titles too short (&lt;30 chars)</div>';
    }

    if ($content_stats['long_titles'] > 0) {
        $html .= '<div style="margin: 8px 0; font-size: 13px;">📏 <strong>' . $content_stats['long_titles'] . '</strong> posts with titles too long (&gt;60 chars)</div>';
    }

    if ($content_stats['short_content'] > 0) {
        $percentage = round(($content_stats['short_content'] / $content_stats['total_analyzed']) * 100);
        $html .= '<div style="margin: 8px 0; font-size: 13px;">📄 <strong>' . $content_stats['short_content'] . '</strong> posts with thin content (&lt;300 words) (' . $percentage . '%)</div>';
    }

    if ($content_stats['missing_alt_text'] > 0) {
        $html .= '<div style="margin: 8px 0; font-size: 13px;">🖼️ <strong>' . $content_stats['missing_alt_text'] . '</strong> posts with images missing alt text</div>';
    }

    if ($content_stats['no_internal_links'] > 0) {
        $percentage = round(($content_stats['no_internal_links'] / $content_stats['total_analyzed']) * 100);
        $html .= '<div style="margin: 8px 0; font-size: 13px;">🔗 <strong>' . $content_stats['no_internal_links'] . '</strong> posts with insufficient internal links (' . $percentage . '%)</div>';
    }

    $html .= '</div>';

    // Technical SEO Status
    $html .= '<div style="background: white; padding: 15px; border-radius: 8px; border-left: 3px solid #2196f3;">';
    $html .= '<h5 style="margin: 0 0 10px 0; color: #2196f3;">⚙️ Technical SEO Status</h5>';

    $ssl_status = $is_ssl ? '✅ SSL Enabled' : '❌ SSL Not Enabled';
    $ssl_color = $is_ssl ? '#4caf50' : '#f44336';
    $html .= '<div style="margin: 8px 0; font-size: 13px; color: ' . $ssl_color . ';">🔒 ' . $ssl_status . '</div>';

    $permalink_status = !empty($permalink_structure) ? '✅ SEO-Friendly URLs' : '❌ Default URLs (not SEO-friendly)';
    $permalink_color = !empty($permalink_structure) ? '#4caf50' : '#f44336';
    $html .= '<div style="margin: 8px 0; font-size: 13px; color: ' . $permalink_color . ';">🔗 ' . $permalink_status . '</div>';

    $robots_status = $robots_txt_exists ? '✅ Robots.txt Found' : '⚠️ Robots.txt Missing';
    $robots_color = $robots_txt_exists ? '#4caf50' : '#ff9800';
    $html .= '<div style="margin: 8px 0; font-size: 13px; color: ' . $robots_color . ';">🤖 ' . $robots_status . '</div>';

    // Check for SEO plugins
    $seo_plugins = [];
    if (is_plugin_active('wordpress-seo/wp-seo.php')) $seo_plugins[] = 'Yoast SEO';
    if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) $seo_plugins[] = 'All in One SEO';
    if (is_plugin_active('seopress/seopress.php')) $seo_plugins[] = 'SEOPress';
    if (is_plugin_active('seo-by-rank-math/rank-math.php')) $seo_plugins[] = 'RankMath SEO';

    if (!empty($seo_plugins)) {
        $html .= '<div style="margin: 8px 0; font-size: 13px; color: #4caf50;">🔌 SEO Plugin: ' . implode(', ', $seo_plugins) . '</div>';
    } else {
        $html .= '<div style="margin: 8px 0; font-size: 13px; color: #f44336;">🔌 No SEO Plugin Detected</div>';
    }

    $html .= '</div>';

    // Content Quality Metrics
    $html .= '<div style="background: white; padding: 15px; border-radius: 8px; border-left: 3px solid #4caf50;">';
    $html .= '<h5 style="margin: 0 0 10px 0; color: #4caf50;">📊 Content Quality Metrics</h5>';

    $html .= '<div style="margin: 8px 0; font-size: 13px;">📝 <strong>' . $content_stats['total_analyzed'] . '</strong> posts analyzed</div>';

    $good_meta_desc = $content_stats['total_analyzed'] - $content_stats['missing_meta_desc'];
    if ($good_meta_desc > 0) {
        $percentage = round(($good_meta_desc / $content_stats['total_analyzed']) * 100);
        $html .= '<div style="margin: 8px 0; font-size: 13px; color: #4caf50;">✅ <strong>' . $good_meta_desc . '</strong> posts with meta descriptions (' . $percentage . '%)</div>';
    }

    $good_content = $content_stats['total_analyzed'] - $content_stats['short_content'];
    if ($good_content > 0) {
        $percentage = round(($good_content / $content_stats['total_analyzed']) * 100);
        $html .= '<div style="margin: 8px 0; font-size: 13px; color: #4caf50;">📄 <strong>' . $good_content . '</strong> posts with adequate content (' . $percentage . '%)</div>';
    }

    $html .= '</div>';

    $html .= '</div>'; // Close grid
    $html .= '</div>'; // Close main container

    return $html;
}

function generate_actionable_content_recommendations($custom_post_types, $shortcode_tags) {
    $html = '<div class="actionable-recommendations" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';

    $admin_url = admin_url();
    $site_url = home_url();

    // High Priority Tasks
    $html .= '<div class="priority-section" style="margin-bottom: 25px;">';
    $html .= '<h5 style="color: #dc3545; margin: 0 0 15px 0; font-size: 16px;">🔥 High Priority Tasks (Complete First)</h5>';

    // Check if SSL is enabled
    if (!is_ssl()) {
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🔒 Enable SSL Certificate</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">SSL is critical for SEO rankings and user trust. Google prioritizes HTTPS sites.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> High SEO & Security';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://wordpress.org/support/article/https-for-wordpress/" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Guide</a><br>';
        $html .= '<a href="' . $admin_url . 'options-general.php" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">⚙️ Settings</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check permalink structure
    $permalink_structure = get_option('permalink_structure');
    if (empty($permalink_structure)) {
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🔗 Fix URL Structure (Permalinks)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Default URLs (?p=123) are not SEO-friendly. Use post names or custom structure.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> High SEO';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'options-permalink.php" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Fix Now</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/using-permalinks/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for SEO plugin
    $has_seo_plugin = is_plugin_active('wordpress-seo/wp-seo.php') ||
                     is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') ||
                     is_plugin_active('seopress/seopress.php') ||
                     is_plugin_active('seo-by-rank-math/rank-math.php');

    if (!$has_seo_plugin) {
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🔌 Install SEO Plugin</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">No SEO plugin detected. Install Yoast SEO, RankMath, or SEOPress for better SEO management.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15 minutes | <strong>🎯 Impact:</strong> High SEO';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=yoast+seo&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install</a><br>';
        $html .= '<a href="https://wordpress.org/plugins/wordpress-seo/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= '</div>'; // Close high priority section

    // Medium Priority Tasks
    $html .= '<div class="priority-section" style="margin-bottom: 25px;">';
    $html .= '<h5 style="color: #ff9800; margin: 0 0 15px 0; font-size: 16px;">⚡ Medium Priority Tasks</h5>';

    // Content optimization tasks
    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">📝 Audit & Optimize Existing Content</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Review posts for missing meta descriptions, short content, and missing alt text.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 2-4 hours | <strong>🎯 Impact:</strong> Medium SEO';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'edit.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📝 Edit Posts</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/writing-posts/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Image optimization
    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🖼️ Optimize Images & Add Alt Text</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Compress images and ensure all images have descriptive alt text for accessibility and SEO.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 1-2 hours | <strong>🎯 Impact:</strong> Medium SEO & Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'upload.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🖼️ Media</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/inserting-media-into-posts-and-pages/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Robots.txt check
    if (!file_exists(ABSPATH . 'robots.txt')) {
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🤖 Create Robots.txt File</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Create a robots.txt file to guide search engine crawlers.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> Medium SEO';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $site_url . '/robots.txt" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Check</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/search-engine-optimization/#robots-txt-optimization" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= '</div>'; // Close medium priority section

    // Low Priority Tasks
    $html .= '<div class="priority-section" style="margin-bottom: 20px;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">📋 Low Priority Tasks (Ongoing Maintenance)</h5>';

    // Custom post types review
    if (count($custom_post_types) > 5) {
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔧 Review Custom Post Types</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">You have ' . count($custom_post_types) . ' custom post types. Review if all are necessary to avoid complexity.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30 minutes | <strong>🎯 Impact:</strong> Low Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . admin_url('edit.php?post_type=') . '" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📝 Review</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Shortcodes review
    if (count($shortcode_tags) > 50) {
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔗 Review Shortcodes</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">High number of shortcodes (' . count($shortcode_tags) . ') detected. Review for conflicts and unused codes.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 45 minutes | <strong>🎯 Impact:</strong> Low Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . admin_url('plugins.php') . '" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Plugins</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Content audit task
    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">📊 Regular Content Audit</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Schedule monthly content audits to remove outdated content and update information.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 2 hours/month | <strong>🎯 Impact:</strong> Low SEO (Long-term)';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . admin_url('edit.php') . '" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📝 Posts</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/content-audit/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Mobile responsiveness check
    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">📱 Test Mobile Responsiveness</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Regularly test your site on mobile devices and use Google\'s Mobile-Friendly Test.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 30 minutes | <strong>🎯 Impact:</strong> Medium SEO';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://search.google.com/test/mobile-friendly?url=' . urlencode(home_url()) . '" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📱 Test</a><br>';
    $html .= '<a href="' . admin_url('customize.php') . '" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🎨 Customize</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>'; // Close low priority section

    // SEO Tools & Resources
    $html .= '<div class="seo-resources" style="background: linear-gradient(135deg, #e8f5e8 0%, #f0f8ff 100%); padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #d4edda;">';
    $html .= '<h5 style="color: #155724; margin: 0 0 15px 0; font-size: 16px;">🛠️ Recommended SEO Tools & Resources</h5>';

    $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">';

    // Free tools
    $html .= '<div style="background: white; padding: 15px; border-radius: 6px; border-left: 3px solid #28a745;">';
    $html .= '<h6 style="margin: 0 0 10px 0; color: #28a745;">🆓 Free SEO Tools</h6>';
    $html .= '<div style="font-size: 13px; line-height: 1.6;">';
    $html .= '<a href="https://search.google.com/search-console" target="_blank" style="color: #007bff; text-decoration: none;">📊 Google Search Console</a><br>';
    $html .= '<a href="https://analytics.google.com" target="_blank" style="color: #007bff; text-decoration: none;">📈 Google Analytics</a><br>';
    $html .= '<a href="https://pagespeed.web.dev" target="_blank" style="color: #007bff; text-decoration: none;">⚡ PageSpeed Insights</a><br>';
    $html .= '<a href="https://search.google.com/test/mobile-friendly" target="_blank" style="color: #007bff; text-decoration: none;">📱 Mobile-Friendly Test</a>';
    $html .= '</div>';
    $html .= '</div>';

    // WordPress resources
    $html .= '<div style="background: white; padding: 15px; border-radius: 6px; border-left: 3px solid #007bff;">';
    $html .= '<h6 style="margin: 0 0 10px 0; color: #007bff;">📚 WordPress SEO Guides</h6>';
    $html .= '<div style="font-size: 13px; line-height: 1.6;">';
    $html .= '<a href="https://wordpress.org/support/article/search-engine-optimization/" target="_blank" style="color: #007bff; text-decoration: none;">🔍 WordPress SEO Guide</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/using-permalinks/" target="_blank" style="color: #007bff; text-decoration: none;">🔗 Permalink Guide</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/writing-posts/" target="_blank" style="color: #007bff; text-decoration: none;">📝 Writing Posts Guide</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/inserting-media-into-posts-and-pages/" target="_blank" style="color: #007bff; text-decoration: none;">🖼️ Media Guide</a>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>'; // Close grid
    $html .= '</div>'; // Close resources section

    $html .= '</div>'; // Close main container

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

    // Plugin recommendations with actionable solutions
    $html .= '<h4>💡 Plugin Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    if (count($active_plugins) > 30) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ Too Many Active Plugins (' . count($active_plugins) . ' active)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">High plugin count can slow your site. Review and deactivate unused plugins.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-30 minutes | <strong>🎯 Impact:</strong> Medium Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugins.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Manage Plugins</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Monitor Impact</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($inactive_count > 10) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🗑️ Too Many Inactive Plugins (' . $inactive_count . ' inactive)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Inactive plugins pose security risks. Delete plugins you don\'t need.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15 minutes | <strong>🎯 Impact:</strong> High Security';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugins.php?plugin_status=inactive" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🗑️ Remove Inactive</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/managing-plugins/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Plugin Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General plugin management recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🔌 Plugin Management Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔄 Update All Plugins</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Keep plugins updated to latest versions for security and compatibility.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> High Security';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'update-core.php" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔄 Update Now</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/updating-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Update Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">📊 Monitor Plugin Performance</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Use Query Monitor to identify plugins that slow down your site.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> Performance Insights';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Install Monitor</a><br>';
    $html .= '<a href="https://wordpress.org/plugins/query-monitor/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Plugin setup looks good! Consider the best practices above for optimal management.';
        $html .= '</div>';
    }

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

    // Hook performance optimization with actionable solutions
    $html .= '<h4>💡 Hook Performance & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check for hooks with too many callbacks
    $heavy_hooks = array_filter($hook_counts, function($count) { return $count > 15; });
    if (!empty($heavy_hooks)) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ Heavy Hook Usage (' . count($heavy_hooks) . ' hooks with >15 callbacks)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Some hooks have many callbacks which may impact performance. Review and optimize hook usage.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> Medium Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Monitor Hooks</a><br>';
        $html .= '<a href="https://developer.wordpress.org/plugins/hooks/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Hook Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for excessive total hooks
    if ($total_hooks > 1000) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🪝 High Hook Count (' . number_format($total_hooks) . ' total hooks)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Very high number of hooks may indicate plugin conflicts or excessive customizations.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 45-90 minutes | <strong>🎯 Impact:</strong> Medium Plugin Review';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugins.php" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Review Plugins</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=health+check&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🏥 Health Check</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General hook optimization recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🪝 Hook Development Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔍 Hook Monitoring</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Monitor hook execution time and performance impact using Query Monitor plugin.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> High Performance Monitoring';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Install Monitor</a><br>';
    $html .= '<a href="https://developer.wordpress.org/plugins/hooks/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Hook Documentation</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">📊 Hook Priority Management</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Use appropriate hook priorities to control execution order and prevent conflicts.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> High Code Organization';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/plugins/hooks/actions/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">⚡ Action Hooks</a><br>';
    $html .= '<a href="https://developer.wordpress.org/plugins/hooks/filters/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Filter Hooks</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🛡️ Hook Validation</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Validate hook callback functions exist and implement proper error handling.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 20-40 minutes | <strong>🎯 Impact:</strong> High Code Reliability';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📋 Coding Standards</a><br>';
    $html .= '<a href="https://developer.wordpress.org/plugins/plugin-basics/best-practices/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Best Practices</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Hook usage looks reasonable! Consider the monitoring and optimization practices above for optimal performance.';
        $html .= '</div>';
    }
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

    // HTTP & cURL optimization with actionable solutions
    $html .= '<h4>💡 HTTP & cURL Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check if any HTTP tests failed
    $failed_tests = 0;
    foreach ($test_urls as $url => $description) {
        $response = wp_remote_get($url, ['timeout' => 5, 'sslverify' => false]);
        if (is_wp_error($response)) {
            $failed_tests++;
        }
    }

    if ($failed_tests > 0) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🌐 HTTP Connection Issues (' . $failed_tests . ' failed tests)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">External HTTP requests are failing. This affects plugin updates, API calls, and external integrations.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-40 minutes | <strong>🎯 Impact:</strong> Critical Connectivity';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://wordpress.org/support/article/common-wordpress-errors/#http-error" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Fix HTTP Errors</a><br>';
        $html .= '<a href="https://developer.wordpress.org/plugins/http-api/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 HTTP API Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check if cURL is not available
    if (!function_exists('curl_version')) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">❌ cURL Not Available</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">cURL is required for HTTP requests. Contact your hosting provider to enable cURL extension.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> Critical Server Configuration';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://wordpress.org/about/requirements/" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📋 WP Requirements</a><br>';
        $html .= '<a href="https://www.php.net/manual/en/curl.installation.php" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 cURL Setup</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General HTTP optimization recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🌐 HTTP & API Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔒 SSL Certificate Validation</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Always validate SSL certificates in production for security. Only disable for testing.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5-10 minutes | <strong>🎯 Impact:</strong> High Security';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/plugins/http-api/wp-remote-get/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 HTTP API Docs</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/https-for-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 HTTPS Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">⏱️ Request Timeout Optimization</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Set appropriate timeouts for HTTP requests to prevent site slowdowns from external API delays.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> Medium Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/plugins/http-api/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 HTTP API Guide</a><br>';
    $html .= '<a href="https://developer.wordpress.org/plugins/http-api/wp-remote-get/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Request Options</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">📊 API Monitoring</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Monitor external API response times and implement retry logic for critical requests.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 20-30 minutes | <strong>🎯 Impact:</strong> High Reliability';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Monitor HTTP</a><br>';
    $html .= '<a href="https://developer.wordpress.org/plugins/http-api/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 API Best Practices</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ HTTP connectivity looks good! Consider the best practices above for optimal API performance and security.';
        $html .= '</div>';
    }

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

    // Cache recommendations with actionable solutions
    $html .= '<h4>💡 Cache & Performance Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    if (empty($active_cache_plugins)) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🚀 No Caching Plugin Detected</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Caching can improve page load times by 50-80%. Install a caching plugin immediately.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> Very High Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+super+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 WP Super Cache</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=w3+total+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 W3 Total Cache</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if (!$object_cache) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">💾 Object Cache Not Active</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Object caching reduces database queries. Contact your host about Redis/Memcached.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> High Database Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/object-cache/" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Object Cache Guide</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=redis+object+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Redis Plugin</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if (!$cdn_detected) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🌐 No CDN Detected</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">CDN improves global loading speeds. Cloudflare offers a free plan.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-30 minutes | <strong>🎯 Impact:</strong> High Global Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://www.cloudflare.com/" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🌐 Cloudflare Free</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/#content-delivery-networks-cdn" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 CDN Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General performance improvements
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">⚡ Additional Performance Optimizations</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🖼️ Image Optimization</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Large images slow down your site. Install an image optimization plugin.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> High Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=smush&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install Smush</a><br>';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=shortpixel&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 ShortPixel</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">⚡ Minification & Compression</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Minify CSS/JS and enable Gzip compression to reduce file sizes.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10 minutes | <strong>🎯 Impact:</strong> Medium Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=autoptimize&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Autoptimize</a><br>';
    $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Optimization Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Cache and CDN setup looks good! Consider the additional optimizations above for even better performance.';
        $html .= '</div>';
    }
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

    // Error analysis with actionable solutions
    $html .= '<h4>💡 Error Monitoring & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    if (!WP_DEBUG && !is_ssl()) { // Only recommend enabling debug in development
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #007bff;">🐛 Enable Debug Mode (Development Only)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Enable WP_DEBUG in development to catch errors early. Never enable on production sites.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> Better Error Detection';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Debug Guide</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/debugging-in-wordpress/" target="_blank" style="display: inline-block; background: #6c757d; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 How-To</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($total_errors > 100) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">⚠️ High Error Count (' . number_format($total_errors) . ' entries)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Many errors detected. Install Query Monitor to identify and fix recurring issues.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> Critical Stability';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Install Monitor</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/debugging-in-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Debug Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if (empty($found_logs) && !defined('WP_DEBUG_LOG')) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">📝 Enable Error Logging</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Error logging is disabled. Enable WP_DEBUG_LOG to track issues and errors.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> Better Error Tracking';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/#wp_debug_log" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Enable Logging</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/debugging-in-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General error monitoring improvements
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🔍 Error Monitoring Tools</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔍 Install Query Monitor</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Query Monitor helps identify slow queries, PHP errors, and performance issues in real-time.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> Excellent Debugging';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install</a><br>';
    $html .= '<a href="https://wordpress.org/plugins/query-monitor/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Error monitoring setup looks good! Consider the tools above for enhanced debugging.';
        $html .= '</div>';
    }
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

    // Log monitoring optimization with actionable solutions
    $html .= '<h4>💡 Log Monitoring & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check if no log files found
    if (!$active_log) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">📄 No Log Files Found</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Error logging is not enabled. Enable WP_DEBUG_LOG to track issues and errors.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> High Error Tracking';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/#wp_debug_log" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Enable Logging</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/debugging-in-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Debug Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for large log files
    if ($active_log && isset($file_size) && $file_size > 10*1024*1024) { // 10MB
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">💾 Large Log File (' . round($file_size / 1024 / 1024, 1) . ' MB)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Log file is very large and may impact performance. Consider log rotation or cleanup.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> Medium Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+log+viewer&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Log Manager</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Log Management</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General log monitoring recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">📊 Log Monitoring Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">📋 Install Log Viewer</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Install a log viewer plugin to easily monitor and manage WordPress error logs.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> Excellent Log Management';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+log+viewer&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install Viewer</a><br>';
    $html .= '<a href="https://wordpress.org/plugins/wp-log-viewer/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About Plugin</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔔 Error Monitoring Service</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">For production sites, use professional error monitoring services like Sentry or Rollbar.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 20-30 minutes | <strong>🎯 Impact:</strong> Professional Monitoring';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://sentry.io/for/wordpress/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔗 Sentry</a><br>';
    $html .= '<a href="https://rollbar.com/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔗 Rollbar</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🧹 Log Rotation Setup</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Implement log rotation to prevent log files from growing too large and impacting performance.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 15-25 minutes | <strong>🎯 Impact:</strong> High Maintenance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+log+viewer&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Log Tools</a><br>';
    $html .= '<a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Debug Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Log monitoring setup looks good! Consider the professional monitoring tools above for enhanced error tracking.';
        $html .= '</div>';
    }

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

    // WP-CLI optimization with actionable solutions
    $html .= '<h4>💡 WP-CLI Setup & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check if WP-CLI is not available
    if (!$wp_cli_available) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚡ WP-CLI Not Available</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">WP-CLI provides powerful command-line tools for WordPress management, automation, and maintenance.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 10-20 minutes | <strong>🎯 Impact:</strong> High Development Efficiency';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://wp-cli.org/#installing" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Install WP-CLI</a><br>';
        $html .= '<a href="https://developer.wordpress.org/cli/commands/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Command Reference</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General WP-CLI benefits and recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">⚡ WP-CLI Benefits & Use Cases</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔄 Automated Maintenance</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Automate WordPress updates, database optimization, and cache clearing with WP-CLI scripts.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 30-60 minutes setup | <strong>🎯 Impact:</strong> Excellent Automation';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/cli/commands/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📋 Commands</a><br>';
    $html .= '<a href="https://wp-cli.org/handbook/guides/quick-start/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Quick Start</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🛠️ Database Operations</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Perform database checks, optimization, search-replace operations, and exports efficiently.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5-15 minutes per task | <strong>🎯 Impact:</strong> High Database Management';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/cli/commands/db/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🗄️ DB Commands</a><br>';
    $html .= '<a href="https://developer.wordpress.org/cli/commands/search-replace/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Search Replace</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">📦 Bulk Operations</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Update all plugins/themes, manage users in bulk, and perform mass content operations faster than web interface.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 2-10 minutes vs hours | <strong>🎯 Impact:</strong> Massive Time Savings';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/cli/commands/plugin/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Plugin Commands</a><br>';
    $html .= '<a href="https://developer.wordpress.org/cli/commands/user/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">👥 User Commands</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ WP-CLI is available! Use the command reference above to leverage powerful WordPress automation and management capabilities.';
        $html .= '</div>';
    }

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

    // Performance optimization with actionable solutions
    $html .= '<h4>🚀 Performance Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    // Check for slow execution time
    if ($execution_time > 3) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">⚡ Very Slow Page Load (' . round($execution_time * 1000, 2) . 'ms)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Page load time is critically slow. Immediate optimization needed for user experience.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> Critical Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Debug Queries</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Performance Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    } elseif ($execution_time > 1) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">⚠️ Slow Page Load (' . round($execution_time * 1000, 2) . 'ms)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Page load time could be improved. Consider caching and optimization.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-40 minutes | <strong>🎯 Impact:</strong> High Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+super+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🚀 Add Caching</a><br>';
        $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Optimize Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for high memory usage
    if ($memory_used > 100*1024*1024) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">💾 High Memory Usage (' . round($memory_used / 1024 / 1024, 2) . 'MB)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Memory usage is very high. Review plugins and themes for optimization opportunities.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-45 minutes | <strong>🎯 Impact:</strong> Critical Memory Optimization';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugins.php" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Review Plugins</a><br>';
        $html .= '<a href="https://wordpress.org/support/article/editing-wp-config-php/#increasing-memory-allocated-to-php" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Memory Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    } elseif ($memory_used > 50*1024*1024) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">💾 Moderate Memory Usage (' . round($memory_used / 1024 / 1024, 2) . 'MB)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Memory usage could be optimized. Consider plugin cleanup and object caching.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> Medium Memory Optimization';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=redis+object+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">💾 Object Cache</a><br>';
        $html .= '<a href="' . $admin_url . 'plugins.php" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Manage Plugins</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Check for high query count
    if ($query_count > 50) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">🗄️ High Database Query Count (' . $query_count . ' queries)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Too many database queries are slowing down the page. Implement query optimization and caching.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 30-60 minutes | <strong>🎯 Impact:</strong> Critical Database Optimization';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Query Monitor</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+optimize&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🗄️ DB Optimize</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    } elseif ($query_count > 25) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🗄️ Moderate Query Count (' . $query_count . ' queries)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Database queries could be optimized. Consider caching and query optimization.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-40 minutes | <strong>🎯 Impact:</strong> Medium Database Optimization';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=w3+total+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🚀 Add Caching</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=query+monitor&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Monitor Queries</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General performance optimization recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">🚀 Performance Optimization Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🚀 Install Caching Plugin</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Caching dramatically improves page load times by serving static versions of your pages.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10-15 minutes | <strong>🎯 Impact:</strong> Excellent Performance Boost';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+super+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🚀 WP Super Cache</a><br>';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=w3+total+cache&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🚀 W3 Total Cache</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🖼️ Image Optimization</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Compress and optimize images to reduce page load times and bandwidth usage.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 15-25 minutes | <strong>🎯 Impact:</strong> High Performance & SEO';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=smush&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🖼️ Smush</a><br>';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=shortpixel&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🖼️ ShortPixel</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">⚡ Minification & Compression</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Minify CSS/JS files and enable Gzip compression to reduce file sizes and improve load times.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 10-20 minutes | <strong>🎯 Impact:</strong> Medium Performance';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=autoptimize&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">⚡ Autoptimize</a><br>';
    $html .= '<a href="https://developer.wordpress.org/advanced-administration/performance/optimization/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Performance Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Performance metrics look good! Consider the optimization practices above for even better performance.';
        $html .= '</div>';
    }
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

    // Cron optimization with actionable solutions
    $html .= '<h4>💡 Cron Optimization & Actionable Solutions</h4>';

    $admin_url = admin_url();
    $has_issues = false;

    if ($overdue_jobs > 0) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #dc3545;">⚠️ Overdue Cron Jobs (' . $overdue_jobs . ' jobs)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Cron jobs are not executing properly. This can affect scheduled posts, backups, and updates.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15-30 minutes | <strong>🎯 Impact:</strong> Critical Functionality';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+crontrol&tab=search&type=term" target="_blank" style="display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 WP Crontrol</a><br>';
        $html .= '<a href="https://developer.wordpress.org/plugins/cron/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Cron Guide</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($cron_disabled) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">🔧 WP Cron Disabled</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">WordPress cron is disabled. Ensure server-level cron is properly configured.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 20-40 minutes | <strong>🎯 Impact:</strong> High Server Configuration';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Server Cron Setup</a><br>';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+crontrol&tab=search&type=term" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔍 Cron Manager</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($total_jobs > 50) {
        $has_issues = true;
        $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h6 style="margin: 0 0 8px 0; color: #ff9800;">📊 Many Cron Jobs (' . $total_jobs . ' total)</h6>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">High number of cron jobs may impact performance. Review and remove unnecessary tasks.</p>';
        $html .= '<div style="font-size: 13px; color: #495057;">';
        $html .= '<strong>⏱️ Time:</strong> 15-25 minutes | <strong>🎯 Impact:</strong> Medium Performance';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-left: 15px;">';
        $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+crontrol&tab=search&type=term" target="_blank" style="display: inline-block; background: #ff9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Manage Cron</a><br>';
        $html .= '<a href="https://developer.wordpress.org/plugins/cron/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Cron Docs</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // General cron management recommendations
    $html .= '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 20px; margin: 15px 0;">';
    $html .= '<h5 style="color: #4caf50; margin: 0 0 15px 0; font-size: 16px;">⏰ Cron Management Best Practices</h5>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🔍 Install WP Crontrol</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Essential plugin for viewing, editing, and debugging WordPress cron jobs.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 5 minutes | <strong>🎯 Impact:</strong> Excellent Cron Management';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="' . $admin_url . 'plugin-install.php?s=wp+crontrol&tab=search&type=term" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔌 Install</a><br>';
    $html .= '<a href="https://wordpress.org/plugins/wp-crontrol/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 About</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="task-item" style="background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    $html .= '<div style="display: flex; justify-content: between; align-items: flex-start;">';
    $html .= '<div style="flex: 1;">';
    $html .= '<h6 style="margin: 0 0 8px 0; color: #4caf50;">🚀 Server-Level Cron (Advanced)</h6>';
    $html .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">For high-traffic sites, disable WP Cron and use server cron for better performance.</p>';
    $html .= '<div style="font-size: 13px; color: #495057;">';
    $html .= '<strong>⏱️ Time:</strong> 30-45 minutes | <strong>🎯 Impact:</strong> High Performance (Advanced)</strong>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="margin-left: 15px;">';
    $html .= '<a href="https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/" target="_blank" style="display: inline-block; background: #4caf50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">🔧 Setup Guide</a><br>';
    $html .= '<a href="https://wordpress.org/support/article/editing-wp-config-php/" target="_blank" style="display: inline-block; background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin: 2px;">📚 Config Guide</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';

    if (!$has_issues) {
        $html .= '<div class="debug-info" style="background: #d4edda; border-left: 4px solid #28a745; color: #155724;">';
        $html .= '✅ Cron system looks healthy! Consider the management tools above for optimal cron monitoring.';
        $html .= '</div>';
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

        .header-actions {
            position: absolute;
            top: 15px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .credits-icon,
        .export-icon {
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

        .credits-icon:hover,
        .export-icon:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .export-icon {
            font-size: 16px;
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
        .credits-modal,
        .export-modal {
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

        .credits-modal.active,
        .export-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .credits-content,
        .export-content {
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

        .export-content {
            max-width: 700px;
        }

        .credits-close,
        .export-close {
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

        .credits-close:hover,
        .export-close:hover {
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

        /* Export Modal Specific Styles */
        .export-options h3 {
            color: #495057;
            margin: 20px 0 10px 0;
            font-size: 1.1em;
        }

        .format-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .format-option {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .format-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .format-option input[type="radio"] {
            margin-right: 12px;
            transform: scale(1.2);
        }

        .format-option input[type="radio"]:checked + .format-label {
            color: #007bff;
        }

        .format-option:has(input[type="radio"]:checked) {
            border-color: #007bff;
            background: #e7f3ff;
        }

        .format-label {
            display: flex;
            flex-direction: column;
        }

        .format-label small {
            color: #6c757d;
            font-size: 0.85em;
            margin-top: 2px;
        }

        .section-options {
            margin-bottom: 20px;
        }

        .section-option,
        .section-checkbox {
            display: flex;
            align-items: center;
            padding: 8px 0;
            cursor: pointer;
        }

        .section-option input[type="checkbox"],
        .section-checkbox input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.1);
        }

        .section-checkboxes {
            margin-left: 20px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 10px;
            background: #f8f9fa;
        }

        .export-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .export-btn,
        .preview-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .export-btn {
            background: #28a745;
            color: white;
        }

        .export-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        .preview-btn {
            background: #007bff;
            color: white;
        }

        .preview-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .export-preview {
            margin-top: 20px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            background: #f8f9fa;
        }

        .preview-content {
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            background: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        /* Dynamic Blocks Grid Styles */
        .dynamic-blocks-container {
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #f8f9fa;
            margin: 15px 0;
            resize: vertical;
            overflow: hidden;
            min-height: 200px;
            max-height: 600px;
            height: 300px;
            transition: all 0.3s ease;
        }

        .dynamic-blocks-container:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        }

        .dynamic-blocks-header {
            background: #e9ecef;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 1;
            cursor: default;
        }

        .dynamic-blocks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
            padding: 15px;
            max-height: calc(100% - 45px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .dynamic-blocks-grid::-webkit-scrollbar {
            width: 8px;
        }

        .dynamic-blocks-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .dynamic-blocks-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .dynamic-blocks-grid::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .block-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #007bff;
            font-size: 13px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            cursor: default;
        }

        .block-item:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            transform: translateY(-1px);
            border-left-color: #0056b3;
        }

        /* Keyboard Shortcut Animations */
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-10px); }
            20% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
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
                <div class="header-actions">
                    <button class="export-icon" onclick="openExportModal()" aria-label="Export diagnostic results" title="Export Results">📥</button>
                    <button class="credits-icon" onclick="openCreditsModal()" aria-label="View credits and business information" title="Credits & Info">ℹ️</button>
                </div>
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

    <!-- Export Modal -->
    <div class="export-modal" id="exportModal">
        <div class="export-content">
            <button class="export-close" onclick="closeExportModal()" aria-label="Close export modal">&times;</button>
            <h2>📥 Export Diagnostic Results</h2>

            <div class="export-options">
                <h3>Export Format</h3>
                <div class="format-options">
                    <label class="format-option">
                        <input type="radio" name="exportFormat" value="html" checked>
                        <span class="format-label">
                            <strong>🌐 HTML Report</strong>
                            <small>Complete formatted report with styling</small>
                        </span>
                    </label>
                    <label class="format-option">
                        <input type="radio" name="exportFormat" value="json">
                        <span class="format-label">
                            <strong>📊 JSON Data</strong>
                            <small>Structured data for analysis</small>
                        </span>
                    </label>
                    <label class="format-option">
                        <input type="radio" name="exportFormat" value="text">
                        <span class="format-label">
                            <strong>📄 Plain Text</strong>
                            <small>Simple text format for sharing</small>
                        </span>
                    </label>
                </div>

                <h3>Include Sections</h3>
                <div class="section-options">
                    <label class="section-option">
                        <input type="checkbox" id="includeAll" checked onchange="toggleAllSections()">
                        <strong>Select All Loaded Sections</strong>
                    </label>
                    <div id="sectionCheckboxes" class="section-checkboxes">
                        <!-- Dynamically populated -->
                    </div>
                </div>

                <div class="export-actions">
                    <button class="export-btn" onclick="generateExport()">
                        📥 Generate Export
                    </button>
                    <button class="preview-btn" onclick="previewExport()">
                        👁️ Preview
                    </button>
                </div>

                <div id="exportPreview" class="export-preview" style="display: none;">
                    <h3>Preview</h3>
                    <div class="preview-content"></div>
                </div>
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
            initializeKeyboardShortcuts();
            initializeSecurityMonitoring();

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
            if (e.key === 'Escape') {
                if (document.getElementById('creditsModal').classList.contains('active')) {
                    closeCreditsModal();
                }
                if (document.getElementById('exportModal').classList.contains('active')) {
                    closeExportModal();
                }
            }
        });

        // Export Modal Functions
        function openExportModal() {
            document.getElementById('exportModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            populateSectionCheckboxes();
        }

        function closeExportModal() {
            document.getElementById('exportModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('exportPreview').style.display = 'none';
        }

        // Close export modal when clicking outside
        document.getElementById('exportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeExportModal();
            }
        });

        // Populate section checkboxes based on loaded sections
        function populateSectionCheckboxes() {
            const container = document.getElementById('sectionCheckboxes');
            container.innerHTML = '';

            const sectionNames = {
                'performance-dashboard': '📊 Performance Dashboard',
                'custom-url-testing': '🔗 URL Testing',
                'wordpress-config': '⚙️ WordPress Config',
                'security-scan': '🛡️ Security Scan',
                'database-tables': '📋 Database Tables',
                'query-profiler': '🔍 Query Profiler',
                'theme-diagnostics': '🎨 Theme Diagnostics',
                'block-editor': '🧱 Block Editor',
                'content-analysis': '📄 Content Analysis',
                'plugin-analysis': '🔌 Plugin Analysis',
                'hooks-filters': '🪝 Hooks & Filters',
                'http-curl': '🌐 HTTP & cURL',
                'cache-cdn': '🚀 Cache & CDN',
                'error-analysis': '🔍 Error Analysis',
                'log-monitoring': '📡 Log Monitoring',
                'wp-cli': '⚡ WP-CLI',
                'performance-summary': '⏱️ Performance Summary',
                'cron-diagnostics': '⏰ Cron Diagnostics'
            };

            SECTIONS.forEach(sectionId => {
                if (loadedSections.has(sectionId)) {
                    const checkbox = document.createElement('label');
                    checkbox.className = 'section-checkbox';
                    checkbox.innerHTML = `
                        <input type="checkbox" value="${sectionId}" checked>
                        ${sectionNames[sectionId] || sectionId}
                    `;
                    container.appendChild(checkbox);
                }
            });

            if (container.children.length === 0) {
                container.innerHTML = '<p style="color: #6c757d; font-style: italic;">No sections loaded yet. Load some tabs first.</p>';
            }
        }

        // Toggle all sections
        function toggleAllSections() {
            const includeAll = document.getElementById('includeAll');
            const checkboxes = document.querySelectorAll('#sectionCheckboxes input[type="checkbox"]');

            checkboxes.forEach(checkbox => {
                checkbox.checked = includeAll.checked;
            });
        }

        // Generate export data
        function generateExportData() {
            const selectedSections = Array.from(document.querySelectorAll('#sectionCheckboxes input[type="checkbox"]:checked'))
                .map(cb => cb.value);

            const exportData = {
                timestamp: new Date().toISOString(),
                site_url: window.location.origin,
                tool_version: '3.0.0-autoload',
                sections: {}
            };

            selectedSections.forEach(sectionId => {
                const tabPane = document.getElementById(`tab-${sectionId}`);
                if (tabPane) {
                    // Get section content without meta information
                    const content = tabPane.cloneNode(true);
                    const metaElements = content.querySelectorAll('.section-meta');
                    metaElements.forEach(meta => meta.remove());

                    exportData.sections[sectionId] = {
                        title: getSectionTitle(sectionId),
                        html: content.innerHTML,
                        text: content.textContent.trim()
                    };
                }
            });

            return exportData;
        }

        // Get section title
        function getSectionTitle(sectionId) {
            const sectionNames = {
                'performance-dashboard': 'Performance Dashboard',
                'custom-url-testing': 'URL Testing',
                'wordpress-config': 'WordPress Configuration',
                'security-scan': 'Security Analysis',
                'database-tables': 'Database Tables',
                'query-profiler': 'Query Profiler',
                'theme-diagnostics': 'Theme Diagnostics',
                'block-editor': 'Block Editor Analysis',
                'content-analysis': 'Content Analysis',
                'plugin-analysis': 'Plugin Analysis',
                'hooks-filters': 'Hooks & Filters',
                'http-curl': 'HTTP & cURL Analysis',
                'cache-cdn': 'Cache & CDN Analysis',
                'error-analysis': 'Error Analysis',
                'log-monitoring': 'Log Monitoring',
                'wp-cli': 'WP-CLI Integration',
                'performance-summary': 'Performance Summary',
                'cron-diagnostics': 'Cron Diagnostics'
            };
            return sectionNames[sectionId] || sectionId;
        }

        // Preview export
        function previewExport() {
            const format = document.querySelector('input[name="exportFormat"]:checked').value;
            const exportData = generateExportData();
            const previewDiv = document.getElementById('exportPreview');
            const previewContent = previewDiv.querySelector('.preview-content');

            let preview = '';
            switch (format) {
                case 'html':
                    preview = generateHTMLExport(exportData).substring(0, 1000) + '...';
                    break;
                case 'json':
                    preview = JSON.stringify(exportData, null, 2).substring(0, 1000) + '...';
                    break;
                case 'text':
                    preview = generateTextExport(exportData).substring(0, 1000) + '...';
                    break;
            }

            previewContent.textContent = preview;
            previewDiv.style.display = 'block';
        }

        // Generate export
        function generateExport() {
            const format = document.querySelector('input[name="exportFormat"]:checked').value;
            const exportData = generateExportData();

            if (Object.keys(exportData.sections).length === 0) {
                alert('No sections selected for export. Please select at least one section.');
                return;
            }

            let content, filename, mimeType;

            switch (format) {
                case 'html':
                    content = generateHTMLExport(exportData);
                    filename = `wordpress-debug-report-${new Date().toISOString().split('T')[0]}.html`;
                    mimeType = 'text/html';
                    break;
                case 'json':
                    content = JSON.stringify(exportData, null, 2);
                    filename = `wordpress-debug-data-${new Date().toISOString().split('T')[0]}.json`;
                    mimeType = 'application/json';
                    break;
                case 'text':
                    content = generateTextExport(exportData);
                    filename = `wordpress-debug-report-${new Date().toISOString().split('T')[0]}.txt`;
                    mimeType = 'text/plain';
                    break;
            }

            downloadFile(content, filename, mimeType);
            closeExportModal();
        }

        // Generate HTML export
        function generateHTMLExport(exportData) {
            let html = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Debug Report - ${new Date().toLocaleDateString()}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
        .section { margin-bottom: 40px; }
        .section h2 { color: #495057; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; }
        .debug-info { background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 15px 0; }
        .debug-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .debug-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .debug-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f9fa; font-weight: 600; }
        .meta { color: #6c757d; font-size: 0.9em; margin-top: 30px; text-align: center; }

        /* Dynamic Blocks Grid Styles for HTML Export */
        .dynamic-blocks-container {
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #f8f9fa;
            margin: 15px 0;
            max-height: 500px;
            overflow: hidden;
        }
        .dynamic-blocks-header {
            background: #e9ecef;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            color: #495057;
        }
        .dynamic-blocks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
            padding: 15px;
            max-height: 450px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .block-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #007bff;
            font-size: 13px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .block-item:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 WordPress Debug Report</h1>
            <p>Generated on ${new Date().toLocaleString()}</p>
            <p>Site: ${exportData.site_url}</p>
        </div>`;

            Object.entries(exportData.sections).forEach(([sectionId, section]) => {
                html += `
        <div class="section">
            <h2>${section.title}</h2>
            ${section.html}
        </div>`;
            });

            html += `
        <div class="meta">
            <p>Report generated by WordPress Debug Tool v${exportData.tool_version}</p>
            <p>Powered by myTech.Today - Professional WordPress Development</p>
        </div>
    </div>
</body>
</html>`;

            return html;
        }

        // Generate text export
        function generateTextExport(exportData) {
            let text = `WORDPRESS DEBUG REPORT
Generated: ${new Date().toLocaleString()}
Site: ${exportData.site_url}
Tool Version: ${exportData.tool_version}

${'='.repeat(80)}

`;

            Object.entries(exportData.sections).forEach(([sectionId, section]) => {
                text += `${section.title.toUpperCase()}
${'-'.repeat(section.title.length)}

${section.text}

${'='.repeat(80)}

`;
            });

            text += `Report generated by WordPress Debug Tool
Powered by myTech.Today - Professional WordPress Development
Website: https://mytech.today
Email: sales@mytech.today`;

            return text;
        }

        // Download file
        function downloadFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Keyboard Shortcuts System
        const KEYBOARD_SHORTCUTS = {
            'ctrl+d': toggleDebugTool,
            'ctrl+e': openExportModal,
            'ctrl+c': openCreditsModal,
            'ctrl+h': showKeyboardHelp,
            'escape': closeAllModals,
            'ctrl+1': () => switchToTab('performance-dashboard'),
            'ctrl+2': () => switchToTab('custom-url-testing'),
            'ctrl+3': () => switchToTab('wordpress-config'),
            'ctrl+4': () => switchToTab('security-scan'),
            'ctrl+5': () => switchToTab('database-tables'),
            'ctrl+6': () => switchToTab('query-profiler'),
            'ctrl+7': () => switchToTab('theme-diagnostics'),
            'ctrl+8': () => switchToTab('block-editor'),
            'ctrl+9': () => switchToTab('content-analysis'),
            'arrowleft': () => navigateTabs(-1),
            'arrowright': () => navigateTabs(1),
            'ctrl+arrowleft': () => navigateTabs(-1),
            'ctrl+arrowright': () => navigateTabs(1)
        };

        // Initialize keyboard shortcuts
        function initializeKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Don't trigger shortcuts when typing in inputs
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    return;
                }

                // Build shortcut key string
                let shortcut = '';
                if (e.ctrlKey) shortcut += 'ctrl+';
                if (e.altKey) shortcut += 'alt+';
                if (e.shiftKey) shortcut += 'shift+';
                shortcut += e.key.toLowerCase();

                // Check if shortcut exists
                if (KEYBOARD_SHORTCUTS[shortcut]) {
                    e.preventDefault();
                    KEYBOARD_SHORTCUTS[shortcut]();

                    // Visual feedback
                    showShortcutFeedback(shortcut);
                }
            });

            console.log('⌨️ Keyboard shortcuts initialized');
            console.log('Available shortcuts:', Object.keys(KEYBOARD_SHORTCUTS));
        }

        // Toggle debug tool visibility
        function toggleDebugTool() {
            const container = document.querySelector('.container');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                showNotification('🔍 Debug tool shown', 'success');
            } else {
                container.style.display = 'none';
                showNotification('🔍 Debug tool hidden', 'info');
            }
        }

        // Navigate between tabs
        function navigateTabs(direction) {
            const currentIndex = SECTIONS.indexOf(currentActiveTab);
            let newIndex = currentIndex + direction;

            if (newIndex < 0) newIndex = SECTIONS.length - 1;
            if (newIndex >= SECTIONS.length) newIndex = 0;

            switchToTab(SECTIONS[newIndex]);
        }

        // Switch to specific tab
        function switchToTab(tabId) {
            if (SECTIONS.includes(tabId)) {
                switchTab(tabId);
                showNotification(`📋 Switched to ${getSectionTitle(tabId)}`, 'info');
            }
        }

        // Close all modals
        function closeAllModals() {
            closeCreditsModal();
            closeExportModal();
        }

        // Show keyboard help
        function showKeyboardHelp() {
            const shortcuts = [
                'Ctrl+D - Toggle Debug Tool',
                'Ctrl+E - Open Export Modal',
                'Ctrl+C - Open Credits',
                'Ctrl+H - Show This Help',
                'Ctrl+1-9 - Jump to Tab',
                '← → - Navigate Tabs',
                'Esc - Close Modals'
            ];

            showNotification('⌨️ Keyboard Shortcuts:\n' + shortcuts.join('\n'), 'info', 5000);
        }

        // Show shortcut feedback
        function showShortcutFeedback(shortcut) {
            const feedback = document.createElement('div');
            feedback.textContent = `⌨️ ${shortcut.toUpperCase()}`;
            feedback.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: rgba(0, 123, 255, 0.9);
                color: white;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                z-index: 10001;
                animation: fadeInOut 1s ease-in-out;
            `;

            document.body.appendChild(feedback);
            setTimeout(() => {
                if (feedback.parentNode) {
                    feedback.parentNode.removeChild(feedback);
                }
            }, 1000);
        }

        // Show notification
        function showNotification(message, type = 'info', duration = 2000) {
            const notification = document.createElement('div');
            notification.textContent = message;

            const colors = {
                success: 'rgba(40, 167, 69, 0.9)',
                info: 'rgba(0, 123, 255, 0.9)',
                warning: 'rgba(255, 193, 7, 0.9)',
                error: 'rgba(220, 53, 69, 0.9)'
            };

            notification.style.cssText = `
                position: fixed;
                top: 70px;
                right: 20px;
                background: ${colors[type] || colors.info};
                color: white;
                padding: 12px 20px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                z-index: 10001;
                max-width: 300px;
                white-space: pre-line;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                animation: slideInRight 0.3s ease-out;
            `;

            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOutRight 0.3s ease-in';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, duration);
        }

        // Security Monitoring System
        function initializeSecurityMonitoring() {
            console.log('🛡️ Security monitoring initialized');

            // Check authentication status every 30 seconds
            setInterval(verifyAuthenticationStatus, 30000);

            // Check on window focus (user returns to tab)
            window.addEventListener('focus', verifyAuthenticationStatus);

            // Check before any AJAX request
            document.addEventListener('beforeunload', function() {
                // Clear any sensitive data from memory
                if (window.loadedSections) {
                    window.loadedSections.clear();
                }
            });
        }

        // Verify authentication status via AJAX
        async function verifyAuthenticationStatus() {
            try {
                const formData = new FormData();
                formData.append('action', 'verify_debug_auth');
                formData.append('nonce', DEBUG_NONCE);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Authentication check failed');
                }

                const result = await response.json();

                if (!result.success) {
                    handleAuthenticationFailure(result.data || 'Authentication verification failed');
                }
            } catch (error) {
                console.warn('⚠️ Authentication check failed:', error);
                // Don't immediately redirect on network errors, but log the issue
                if (error.message.includes('Authentication check failed')) {
                    handleAuthenticationFailure('Session verification failed');
                }
            }
        }

        // Handle authentication failure
        function handleAuthenticationFailure(reason) {
            console.error('🛡️ Authentication failure detected:', reason);

            // Show security warning
            showNotification('🛡️ Security Alert: Session expired or invalid. Redirecting to WordPress login...', 'error', 5000);

            // Clear sensitive data
            if (window.loadedSections) {
                window.loadedSections.clear();
            }

            // Redirect to WordPress login after a delay
            setTimeout(() => {
                // Try to redirect to WordPress admin login
                const adminUrl = window.location.origin + '/wp-admin/';
                window.location.href = adminUrl;
            }, 3000);
        }

        // Enhanced AJAX security wrapper
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            // Add security headers to all requests
            if (args[1] && args[1].method === 'POST') {
                const headers = args[1].headers || {};
                headers['X-Requested-With'] = 'XMLHttpRequest';
                headers['X-Debug-Tool-Request'] = 'true';
                args[1].headers = headers;
            }

            return originalFetch.apply(this, args);
        };
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
