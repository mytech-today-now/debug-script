<?php
/**
 * WordPress Debug Tool - Medium Version
 * More advanced than minimal, less complex than full version
 * 
 * Upload and access: https://mytech.today/debug-medium.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Performance timing
$debug_start_time = microtime(true);
$debug_timings = [];

function debug_time($label) {
    global $debug_timings, $debug_start_time;
    $debug_timings[$label] = round((microtime(true) - $debug_start_time) * 1000, 2);
}

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

debug_time('wordpress_loaded');

// Global variables for content detection
global $debug_has_the_content, $debug_content_filters;
$debug_has_the_content = false;
$debug_content_filters = [];

// Add content detection filter
add_filter('the_content', function($content) {
    global $debug_has_the_content, $debug_content_filters;
    $debug_has_the_content = true;
    $debug_content_filters[] = 'the_content filter called at ' . date('H:i:s');
    return $content;
}, 1);

debug_time('filters_added');

// Get current page info
$current_post = null;
$page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;
if ($page_id > 0) {
    $current_post = get_post($page_id);
}

debug_time('page_data_loaded');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Debug Tool - Medium Version</title>
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
        }
        
        [data-theme="dark"] {
            --debug-bg: #2c3e50;
            --debug-text: #ecf0f1;
            --debug-border: #34495e;
            --debug-success: #27ae60;
            --debug-warning: #f39c12;
            --debug-error: #e74c3c;
            --debug-info: #3498db;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: var(--debug-bg);
            color: var(--debug-text);
            line-height: 1.6;
        }
        
        .debug-container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--debug-bg);
            border: 1px solid var(--debug-border);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .debug-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--debug-primary);
        }
        
        .debug-controls {
            display: flex;
            gap: 10px;
        }
        
        .debug-btn {
            background: var(--debug-primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: opacity 0.3s;
        }
        
        .debug-btn:hover {
            opacity: 0.8;
        }
        
        .debug-section {
            margin: 20px 0;
            border: 1px solid var(--debug-border);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .debug-section-header {
            background: var(--debug-info);
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
        }
        
        .debug-section-content {
            padding: 15px;
            background: var(--debug-bg);
        }
        
        .debug-section.collapsed .debug-section-content {
            display: none;
        }
        
        .debug-status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .debug-success { background: var(--debug-success); color: #155724; }
        .debug-warning { background: var(--debug-warning); color: #856404; }
        .debug-error { background: var(--debug-error); color: #721c24; }
        .debug-info { background: var(--debug-info); color: #0c5460; }
        
        .debug-code {
            background: #f8f9fa;
            border: 1px solid var(--debug-border);
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .debug-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .debug-metric {
            background: var(--debug-bg);
            border: 1px solid var(--debug-border);
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        
        .debug-metric-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--debug-primary);
        }
        
        .debug-metric-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .debug-header { flex-direction: column; gap: 10px; }
            .debug-controls { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <h1>🔧 WordPress Debug Tool - Medium Version</h1>
            <div class="debug-controls">
                <button class="debug-btn" onclick="toggleTheme()">🌓 Theme</button>
                <button class="debug-btn" onclick="toggleAll()">📦 Toggle All</button>
                <button class="debug-btn" onclick="exportResults()">💾 Export</button>
            </div>
        </div>

        <!-- Performance Overview -->
        <div class="debug-grid">
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo $debug_timings['wordpress_loaded'] ?? '0'; ?>ms</div>
                <div class="debug-metric-label">WordPress Load Time</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo count(get_option('active_plugins', [])); ?></div>
                <div class="debug-metric-label">Active Plugins</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo $debug_has_the_content ? 'YES' : 'NO'; ?></div>
                <div class="debug-metric-label">the_content() Called</div>
            </div>
            <div class="debug-metric">
                <div class="debug-metric-value"><?php echo PHP_VERSION; ?></div>
                <div class="debug-metric-label">PHP Version</div>
            </div>
        </div>

        <!-- WordPress Information -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📊 WordPress Information
            </div>
            <div class="debug-section-content">
                <div class="debug-code">
                    <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?><br>
                    <strong>Site URL:</strong> <?php echo home_url(); ?><br>
                    <strong>Admin URL:</strong> <?php echo admin_url(); ?><br>
                    <strong>Theme:</strong> <?php echo wp_get_theme()->get('Name') . ' v' . wp_get_theme()->get('Version'); ?><br>
                    <strong>Multisite:</strong> <?php echo is_multisite() ? 'Yes' : 'No'; ?><br>
                    <strong>Debug Mode:</strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled'; ?><br>
                </div>
                
                <?php
                $current_user = wp_get_current_user();
                if ($current_user->ID) {
                    echo '<div class="debug-success">';
                    echo '<strong>👤 Current User:</strong> ' . esc_html($current_user->display_name) . ' (ID: ' . $current_user->ID . ')<br>';
                    echo '<strong>Roles:</strong> ' . implode(', ', $current_user->roles) . '<br>';
                    echo '<strong>Admin Access:</strong> ' . (current_user_can('manage_options') ? 'Yes' : 'No');
                    echo '</div>';
                } else {
                    echo '<div class="debug-warning">';
                    echo '<strong>👤 User Status:</strong> Not logged in. <a href="' . wp_login_url() . '">Login here</a>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Content Detection -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                📄 Content Detection & Analysis
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('content_analysis_start');

                if ($current_post) {
                    echo '<div class="debug-info">';
                    echo '<strong>🎯 Testing Page:</strong> ' . esc_html($current_post->post_title) . ' (ID: ' . $current_post->ID . ')';
                    echo '</div>';

                    // Simulate content processing
                    $content = apply_filters('the_content', $current_post->post_content);
                } else {
                    echo '<div class="debug-warning">';
                    echo '<strong>⚠️ No specific page selected.</strong> Add ?page_id=123 to test a specific page.';
                    echo '</div>';
                }

                echo '<div class="debug-code">';
                echo '<strong>the_content() Status:</strong> ' . ($debug_has_the_content ? '✅ Called' : '❌ Not called') . '<br>';
                echo '<strong>Content Filters:</strong> ' . count($debug_content_filters) . ' filter calls<br>';

                if (!empty($debug_content_filters)) {
                    echo '<strong>Filter Timeline:</strong><br>';
                    foreach ($debug_content_filters as $filter_call) {
                        echo '&nbsp;&nbsp;• ' . esc_html($filter_call) . '<br>';
                    }
                }
                echo '</div>';

                debug_time('content_analysis_end');
                ?>
            </div>
        </div>

        <!-- Plugin Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🔌 Plugin Analysis
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('plugin_analysis_start');

                $active_plugins = get_option('active_plugins', []);
                $all_plugins = get_plugins();

                echo '<div class="debug-grid">';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($active_plugins) . '</div>';
                echo '<div class="debug-metric-label">Active Plugins</div>';
                echo '</div>';
                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . count($all_plugins) . '</div>';
                echo '<div class="debug-metric-label">Total Plugins</div>';
                echo '</div>';
                echo '</div>';

                if (!empty($active_plugins)) {
                    echo '<h4>🟢 Active Plugins:</h4>';
                    echo '<div class="debug-code">';
                    foreach ($active_plugins as $plugin_file) {
                        $plugin_data = $all_plugins[$plugin_file] ?? null;
                        if ($plugin_data) {
                            echo '<strong>' . esc_html($plugin_data['Name']) . '</strong> v' . esc_html($plugin_data['Version']);
                            echo ' <small>(' . esc_html($plugin_file) . ')</small><br>';
                        } else {
                            echo '<span style="color: #e74c3c;">' . esc_html($plugin_file) . ' (Plugin data not found)</span><br>';
                        }
                    }
                    echo '</div>';

                    // Simple conflict test option
                    echo '<div class="debug-info">';
                    echo '<strong>🧪 Plugin Conflict Testing:</strong><br>';
                    echo 'To test for plugin conflicts, you can disable plugins by adding to URL:<br>';
                    echo '<code>?debug_disable_plugins=plugin-folder/plugin-file.php</code>';
                    echo '</div>';
                } else {
                    echo '<div class="debug-warning">No active plugins found.</div>';
                }

                debug_time('plugin_analysis_end');
                ?>
            </div>
        </div>

        <!-- Shortcode Analysis -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🏷️ Shortcode Analysis
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('shortcode_analysis_start');

                global $shortcode_tags;
                $shortcode_count = count($shortcode_tags);

                echo '<div class="debug-metric">';
                echo '<div class="debug-metric-value">' . $shortcode_count . '</div>';
                echo '<div class="debug-metric-label">Registered Shortcodes</div>';
                echo '</div>';

                if ($shortcode_count > 0) {
                    echo '<h4>📋 Registered Shortcodes:</h4>';
                    echo '<div class="debug-code" style="max-height: 200px; overflow-y: auto;">';

                    $shortcode_list = array_keys($shortcode_tags);
                    sort($shortcode_list);

                    foreach ($shortcode_list as $shortcode) {
                        echo '[' . esc_html($shortcode) . ']<br>';
                    }
                    echo '</div>';

                    // Check for shortcodes in current content
                    if ($current_post && !empty($current_post->post_content)) {
                        $content = $current_post->post_content;
                        $found_shortcodes = [];

                        foreach ($shortcode_list as $shortcode) {
                            if (strpos($content, '[' . $shortcode) !== false) {
                                $found_shortcodes[] = $shortcode;
                            }
                        }

                        if (!empty($found_shortcodes)) {
                            echo '<div class="debug-success">';
                            echo '<strong>✅ Shortcodes found in current content:</strong> [' . implode('], [', $found_shortcodes) . ']';
                            echo '</div>';
                        } else {
                            echo '<div class="debug-info">';
                            echo '<strong>ℹ️ No shortcodes found in current content.</strong>';
                            echo '</div>';
                        }
                    }
                }

                debug_time('shortcode_analysis_end');
                ?>
            </div>
        </div>

        <!-- HTTP & Connectivity -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                🌐 HTTP & Connectivity
            </div>
            <div class="debug-section-content">
                <?php
                debug_time('http_test_start');

                // Test external HTTP request
                echo '<h4>🔗 External HTTP Test:</h4>';
                $test_url = 'https://api.wordpress.org/core/version-check/1.7/';
                $response = wp_remote_get($test_url, ['timeout' => 10]);

                if (is_wp_error($response)) {
                    echo '<div class="debug-error">';
                    echo '<strong>❌ External HTTP Failed:</strong> ' . esc_html($response->get_error_message());
                    echo '</div>';
                } else {
                    $code = wp_remote_retrieve_response_code($response);
                    echo '<div class="debug-success">';
                    echo '<strong>✅ External HTTP Success:</strong> Response code ' . $code;
                    echo '</div>';
                }

                // Test loopback request
                echo '<h4>🔄 Loopback Test:</h4>';
                $loopback_url = home_url('/');
                $loopback_response = wp_remote_get($loopback_url, ['timeout' => 10]);

                if (is_wp_error($loopback_response)) {
                    echo '<div class="debug-warning">';
                    echo '<strong>⚠️ Loopback Warning:</strong> ' . esc_html($loopback_response->get_error_message());
                    echo '</div>';
                } else {
                    $loopback_code = wp_remote_retrieve_response_code($loopback_response);
                    echo '<div class="debug-success">';
                    echo '<strong>✅ Loopback Success:</strong> Response code ' . $loopback_code;
                    echo '</div>';
                }

                // cURL information
                echo '<h4>🔧 cURL Information:</h4>';
                echo '<div class="debug-code">';
                if (extension_loaded('curl')) {
                    $curl_version = curl_version();
                    echo '<strong>cURL Status:</strong> ✅ Loaded (v' . $curl_version['version'] . ')<br>';
                    echo '<strong>SSL Support:</strong> ' . (($curl_version['features'] & CURL_VERSION_SSL) ? 'Yes' : 'No') . '<br>';
                } else {
                    echo '<strong>cURL Status:</strong> ❌ Not loaded<br>';
                }
                echo '<strong>PHP Timeout:</strong> ' . ini_get('default_socket_timeout') . ' seconds<br>';
                echo '</div>';

                debug_time('http_test_end');
                ?>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="debug-section">
            <div class="debug-section-header" onclick="toggleSection(this)">
                ⚡ Performance Summary
            </div>
            <div class="debug-section-content">
                <?php
                $total_time = round((microtime(true) - $debug_start_time) * 1000, 2);
                debug_time('total_execution');

                echo '<div class="debug-grid">';
                foreach ($debug_timings as $label => $time) {
                    $clean_label = ucwords(str_replace('_', ' ', $label));
                    echo '<div class="debug-metric">';
                    echo '<div class="debug-metric-value">' . $time . 'ms</div>';
                    echo '<div class="debug-metric-label">' . $clean_label . '</div>';
                    echo '</div>';
                }
                echo '</div>';

                echo '<div class="debug-info">';
                echo '<strong>📊 Performance Notes:</strong><br>';
                echo '• WordPress load time: ' . ($debug_timings['wordpress_loaded'] ?? 0) . 'ms<br>';
                echo '• Total execution time: ' . $total_time . 'ms<br>';
                echo '• Memory usage: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB<br>';
                echo '• Peak memory: ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';
                echo '</div>';
                ?>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--debug-border); color: #666;">
            <strong>WordPress Debug Tool - Medium Version</strong><br>
            More features than minimal, fewer restrictions than full version<br>
            <a href="<?php echo home_url(); ?>">← Return to Site</a> |
            <a href="debug-minimal.php">Minimal Version</a> |
            <a href="debug.php">Full Version</a>
        </div>
    </div>

    <script>
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('debug-theme', newTheme);
        }

        function toggleSection(header) {
            const section = header.parentElement;
            section.classList.toggle('collapsed');
        }

        function toggleAll() {
            const sections = document.querySelectorAll('.debug-section');
            const allCollapsed = Array.from(sections).every(s => s.classList.contains('collapsed'));

            sections.forEach(section => {
                if (allCollapsed) {
                    section.classList.remove('collapsed');
                } else {
                    section.classList.add('collapsed');
                }
            });
        }

        function exportResults() {
            const data = {
                timestamp: new Date().toISOString(),
                site_url: '<?php echo home_url(); ?>',
                wordpress_version: '<?php echo get_bloginfo("version"); ?>',
                php_version: '<?php echo PHP_VERSION; ?>',
                theme: '<?php echo wp_get_theme()->get("Name"); ?>',
                active_plugins: <?php echo json_encode(array_values(get_option('active_plugins', []))); ?>,
                the_content_called: <?php echo $debug_has_the_content ? 'true' : 'false'; ?>,
                performance: <?php echo json_encode($debug_timings); ?>,
                total_execution_time: '<?php echo $total_time; ?>ms',
                memory_usage: '<?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB'
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'debug-medium-results-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('debug-theme');
        if (savedTheme) {
            document.body.setAttribute('data-theme', savedTheme);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                toggleAll();
            }
        });
    </script>
</body>
</html>
