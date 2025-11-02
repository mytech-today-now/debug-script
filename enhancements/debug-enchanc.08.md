🎯 Task Overview
You are an expert WordPress developer and UI/UX designer specializing in debugging tools. Enhance the comprehensive PHP-based WordPress debug tool called "Ultimate WordPress Debug Tool - Omega Version" located at debug-script/debug-omega.php.

The current tool includes sections for configuration analysis, security scans, database tables/queries, theme templates, block editor diagnostics, content/shortcode detection, plugin conflict testing, hooks/filters tracking, HTTP/cURL tests, cache/CDN health, error log pattern analysis, performance breakdowns, and cron job diagnostics. The GUI uses collapsible sections, metrics grids, tables, badges, and basic JS for toggles/export/theme switching.

🗄️ Database Query Optimizer with EXPLAIN Analysis
Query Optimization
Database Profiler
Why Useful: Analyze slow queries, suggest indexes, identify N+1 problems
Integration: Extend existing Database Query Profiler with optimization suggestions
function analyze_query_performance($query) { $explain = $wpdb->get_results("EXPLAIN " . $query); $suggestions = []; foreach($explain as $row) { if($row->key === null) $suggestions[] = "Consider adding index on {$row->table}"; if($row->rows > 1000) $suggestions[] = "Query scans too many rows"; } return $suggestions; }

🔒 Security Considerations
All new features must check current_user_can('manage_options')
WP-CLI commands must use whitelist approach
WebSocket connections should use authentication tokens
File operations must validate paths and permissions
All user inputs must be sanitized and validated
⚡ Performance Guidelines
Use WordPress transients for caching expensive operations
Implement lazy loading for heavy diagnostic sections
Use Web Workers for intensive JavaScript operations
Optimize database queries with proper indexing
Implement request throttling for real-time features