# WordPress Debug Tool - Ultimate Enhancement Specifications v4

## Task Overview
You are an expert WordPress developer and UI/UX designer specializing in debugging tools. Enhance the comprehensive PHP-based WordPress debug tool called "Ultimate WordPress Debug Tool - Omega Version" located at `debug-script/debug-omega.php`.

The current tool includes sections for configuration analysis, security scans, database tables/queries, theme templates, block editor diagnostics, content/shortcode detection, plugin conflict testing, hooks/filters tracking, HTTP/cURL tests, cache/CDN health, error log pattern analysis, performance breakdowns, and cron job diagnostics. The GUI uses collapsible sections, metrics grids, tables, badges, and basic JS for toggles/export/theme switching.

## Implementation Requirements

### **New Features** (8-10 Advanced WordPress Debugging Features)

1. **Real-Time Log Tailing with WebSocket Integration**
   - **Why Useful**: Monitor WordPress errors, PHP errors, and custom logs in real-time without page refresh
   - **Integration**: Add new section after Error Pattern Analysis, use existing `$debug_timings` for performance tracking
   - **Implementation Outline**:
     ```php
     function start_log_tail_websocket() {
         // WebSocket server using ReactPHP or Ratchet
         // Monitor error_log, debug.log, and custom log files
         // Send real-time updates to frontend via WebSocket
     }
     
     // JavaScript WebSocket client
     const logSocket = new WebSocket('ws://localhost:8080/debug-logs');
     logSocket.onmessage = function(event) {
         appendLogEntry(JSON.parse(event.data));
     };
     ```

2. **WP-CLI Integration & Command Runner**
   - **Why Useful**: Execute WP-CLI commands directly from debug interface for database repairs, cache clearing, plugin management
   - **Integration**: Add new section with command history, integrate with existing security checks
   - **Implementation Outline**:
     ```php
     function execute_wp_cli_command($command) {
         // Security: Whitelist allowed commands
         $allowed_commands = ['cache flush', 'db check', 'plugin list', 'theme list'];
         if (!in_array($command, $allowed_commands)) return false;
         
         $output = shell_exec("wp {$command} --path=" . ABSPATH);
         return ['output' => $output, 'timestamp' => time()];
     }
     ```

3. **Automated Fix Runner with Rollback System**
   - **Why Useful**: Automatically apply common fixes (clear cache, repair database, update .htaccess) with ability to rollback
   - **Integration**: Extend existing error analysis with actionable fix buttons, use WordPress transients for rollback data
   - **Implementation Outline**:
     ```php
     function apply_automated_fix($fix_type) {
         // Create rollback point
         $rollback_data = create_rollback_point();
         set_transient('debug_rollback_' . $fix_type, $rollback_data, 3600);
         
         switch($fix_type) {
             case 'clear_cache': wp_cache_flush(); break;
             case 'repair_db': $wpdb->query("REPAIR TABLE {$table}"); break;
         }
     }
     ```

4. **Multisite Network Diagnostics**
   - **Why Useful**: Debug multisite-specific issues like subdomain routing, shared tables, network plugin conflicts
   - **Integration**: Add conditional section that appears only on multisite installations
   - **Implementation Outline**:
     ```php
     if (is_multisite()) {
         function analyze_multisite_network() {
             $sites = get_sites(['number' => 100]);
             $network_plugins = get_site_option('active_sitewide_plugins');
             $domain_mapping = analyze_domain_mapping();
             return compact('sites', 'network_plugins', 'domain_mapping');
         }
     }
     ```

5. **REST API Endpoint Testing & Documentation**
   - **Why Useful**: Test all registered REST API endpoints, check authentication, validate responses
   - **Integration**: New section with interactive API explorer, use existing HTTP diagnostics functions
   - **Implementation Outline**:
     ```php
     function get_rest_endpoints() {
         $server = rest_get_server();
         $routes = $server->get_routes();
         
         foreach($routes as $route => $handlers) {
             $endpoints[] = [
                 'route' => $route,
                 'methods' => array_keys($handlers),
                 'auth_required' => check_endpoint_auth($route)
             ];
         }
         return $endpoints;
     }
     ```

6. **Theme Builder & Page Builder Diagnostics**
   - **Why Useful**: Debug Elementor, Gutenberg, Divi issues - check for conflicts, missing dependencies, performance impact
   - **Integration**: Extend existing theme diagnostics with builder-specific checks
   - **Implementation Outline**:
     ```php
     function analyze_page_builders() {
         $builders = [
             'elementor' => class_exists('Elementor\Plugin'),
             'divi' => function_exists('et_setup_theme'),
             'beaver' => class_exists('FLBuilder')
         ];
         
         foreach($builders as $builder => $active) {
             if($active) $analysis[$builder] = analyze_builder_performance($builder);
         }
     }
     ```

7. **Memory Usage Profiler with Call Stack**
   - **Why Useful**: Track memory usage by function calls, identify memory leaks and heavy operations
   - **Integration**: Enhance existing performance metrics with detailed memory profiling
   - **Implementation Outline**:
     ```php
     function start_memory_profiling() {
         register_tick_function('memory_tick_handler');
         declare(ticks=1);
     }
     
     function memory_tick_handler() {
         $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
         $memory_usage[serialize($backtrace)] = memory_get_usage();
     }
     ```

8. **Database Query Optimizer with EXPLAIN Analysis**
   - **Why Useful**: Analyze slow queries, suggest indexes, identify N+1 problems
   - **Integration**: Extend existing Database Query Profiler with optimization suggestions
   - **Implementation Outline**:
     ```php
     function analyze_query_performance($query) {
         $explain = $wpdb->get_results("EXPLAIN " . $query);
         $suggestions = [];
         
         foreach($explain as $row) {
             if($row->key === null) $suggestions[] = "Consider adding index on {$row->table}";
             if($row->rows > 1000) $suggestions[] = "Query scans too many rows";
         }
         return $suggestions;
     }
     ```

9. **Security Vulnerability Scanner with CVE Database**
   - **Why Useful**: Check plugins/themes against known vulnerabilities, scan for malware patterns
   - **Integration**: Enhance existing security scan with external vulnerability database
   - **Implementation Outline**:
     ```php
     function check_vulnerability_database($plugin_slug, $version) {
         $api_url = "https://wpscan.com/api/v3/plugins/{$plugin_slug}";
         $response = wp_remote_get($api_url);
         $vulnerabilities = json_decode(wp_remote_retrieve_body($response), true);
         
         return filter_vulnerabilities_by_version($vulnerabilities, $version);
     }
     ```

10. **Custom Hook & Filter Debugger**
    - **Why Useful**: Track custom hooks, see execution order, identify hook conflicts
    - **Integration**: Extend existing hooks analysis with custom hook tracking
    - **Implementation Outline**:
    ```php
    function track_custom_hooks() {
        add_action('all', function($hook) {
            global $debug_custom_hooks;
            if(strpos($hook, 'wp_') !== 0) {
                $debug_custom_hooks[$hook][] = [
                    'time' => microtime(true),
                    'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
                ];
            }
        });
    }
    ```

### **GUI Enhancements** (6-8 Advanced Interface Improvements)

1. **Interactive Performance Charts with Chart.js**
   - **Description**: Replace static metrics with interactive line/bar charts showing performance over time
   - **Target Sections**: Performance Dashboard, Database Query Profiler, Memory Usage
   - **Implementation**:
     ```javascript
     function createPerformanceChart(canvasId, data) {
         new Chart(document.getElementById(canvasId), {
             type: 'line',
             data: {
                 labels: data.timestamps,
                 datasets: [{
                     label: 'Response Time (ms)',
                     data: data.response_times,
                     borderColor: 'rgb(75, 192, 192)'
                 }]
             }
         });
     }
     ```

2. **Advanced Search & Filter System**
   - **Description**: Global search across all sections, filter tables by multiple criteria, save filter presets
   - **Target Sections**: Database Tables, Plugin Analysis, Error Logs, Hooks Analysis
   - **Implementation**:
     ```javascript
     class DebugSearchFilter {
         constructor() {
             this.filters = new Map();
             this.searchIndex = this.buildSearchIndex();
         }
         
         applyFilters(section, criteria) {
             const rows = document.querySelectorAll(`#${section} tbody tr`);
             rows.forEach(row => {
                 row.style.display = this.matchesCriteria(row, criteria) ? '' : 'none';
             });
         }
     }
     ```

3. **Modal-Based Detail Views**
   - **Description**: Click any table row to open detailed modal with expanded information and actions
   - **Target Sections**: Database Tables, Plugin Analysis, Error Patterns, Security Scan
   - **Implementation**:
     ```css
     .debug-modal {
         position: fixed; top: 0; left: 0; width: 100%; height: 100%;
         background: rgba(0,0,0,0.8); z-index: 1000;
         display: flex; align-items: center; justify-content: center;
     }
     .debug-modal-content {
         background: var(--debug-bg); padding: 30px; border-radius: 12px;
         max-width: 80%; max-height: 80%; overflow-y: auto;
     }
     ```

4. **Drag & Drop Section Reordering**
   - **Description**: Allow users to reorder debug sections based on their workflow preferences
   - **Target Sections**: All collapsible sections
   - **Implementation**:
     ```javascript
     function initializeDragDrop() {
         new Sortable(document.querySelector('.debug-sections'), {
             handle: '.debug-section-header',
             animation: 150,
             onEnd: function(evt) {
                 localStorage.setItem('debug-section-order', 
                     JSON.stringify(Array.from(evt.to.children).map(el => el.id)));
             }
         });
     }
     ```

5. **Real-Time Status Indicators with WebSocket**
   - **Description**: Live status badges showing real-time system health (CPU, memory, active users)
   - **Target Sections**: Performance Dashboard header
   - **Implementation**:
     ```javascript
     function updateRealTimeStatus() {
         const statusSocket = new WebSocket('ws://localhost:8080/status');
         statusSocket.onmessage = function(event) {
             const status = JSON.parse(event.data);
             document.getElementById('cpu-usage').textContent = status.cpu + '%';
             document.getElementById('memory-usage').textContent = status.memory + '%';
         };
     }
     ```

6. **Tabbed Sub-Sections with State Persistence**
   - **Description**: Convert large sections into tabbed interfaces, remember active tabs across sessions
   - **Target Sections**: Database Analysis, Security Scan, Plugin Analysis
   - **Implementation**:
     ```javascript
     class TabbedSection {
         constructor(sectionId) {
             this.sectionId = sectionId;
             this.activeTab = localStorage.getItem(`${sectionId}-active-tab`) || 'tab1';
             this.initializeTabs();
         }
         
         switchTab(tabId) {
             document.querySelectorAll(`#${this.sectionId} .tab-content`).forEach(tab => 
                 tab.style.display = 'none');
             document.getElementById(tabId).style.display = 'block';
             localStorage.setItem(`${this.sectionId}-active-tab`, tabId);
         }
     }
     ```

7. **Progressive Data Loading with Pagination**
   - **Description**: Load large datasets progressively, implement virtual scrolling for huge tables
   - **Target Sections**: Database Tables, Error Logs, Hooks Analysis
   - **Implementation**:
     ```javascript
     class VirtualTable {
         constructor(containerId, data, rowHeight = 40) {
             this.container = document.getElementById(containerId);
             this.data = data;
             this.rowHeight = rowHeight;
             this.visibleRows = Math.ceil(this.container.clientHeight / rowHeight);
             this.render();
         }
         
         render() {
             const startIndex = Math.floor(this.container.scrollTop / this.rowHeight);
             const endIndex = Math.min(startIndex + this.visibleRows, this.data.length);
             // Render only visible rows
         }
     }
     ```

8. **Contextual Help System with Tooltips**
   - **Description**: Interactive help tooltips explaining each metric, expandable help sections
   - **Target Sections**: All sections with complex metrics or technical terms
   - **Implementation**:
     ```javascript
     function initializeTooltips() {
         tippy('[data-tooltip]', {
             content(reference) {
                 return reference.getAttribute('data-tooltip');
             },
             theme: 'debug-theme',
             placement: 'top',
             arrow: true
         });
     }
     ```

## Implementation Priority
1. **High Priority**: Real-Time Log Tailing, Interactive Charts, Advanced Search, Modal Details
2. **Medium Priority**: WP-CLI Integration, Automated Fixes, Tabbed Sections, Progressive Loading
3. **Low Priority**: Multisite Diagnostics, Custom Hook Debugger, Drag & Drop, Contextual Help

## Security Considerations
- All new features must check `current_user_can('manage_options')`
- WP-CLI commands must use whitelist approach
- WebSocket connections should use authentication tokens
- File operations must validate paths and permissions
- All user inputs must be sanitized and validated

## Performance Guidelines
- Use WordPress transients for caching expensive operations
- Implement lazy loading for heavy diagnostic sections
- Use Web Workers for intensive JavaScript operations
- Optimize database queries with proper indexing
- Implement request throttling for real-time features
