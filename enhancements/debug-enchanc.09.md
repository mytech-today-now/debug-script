🎯 Task Overview
You are an expert WordPress developer and UI/UX designer specializing in debugging tools. Enhance the comprehensive PHP-based WordPress debug tool called "Ultimate WordPress Debug Tool - Omega Version" located at debug-script/debug-omega.php.

The current tool includes sections for configuration analysis, security scans, database tables/queries, theme templates, block editor diagnostics, content/shortcode detection, plugin conflict testing, hooks/filters tracking, HTTP/cURL tests, cache/CDN health, error log pattern analysis, performance breakdowns, and cron job diagnostics. The GUI uses collapsible sections, metrics grids, tables, badges, and basic JS for toggles/export/theme switching.

🛡️ Security Vulnerability Scanner with CVE Database
Vulnerability Check
Security Scan
Why Useful: Check plugins/themes against known vulnerabilities, scan for malware patterns
Integration: Enhance existing security scan with external vulnerability database
function check_vulnerability_database($plugin_slug, $version) { $api_url = "https://wpscan.com/api/v3/plugins/{$plugin_slug}"; $response = wp_remote_get($api_url); $vulnerabilities = json_decode(wp_remote_retrieve_body($response), true); return filter_vulnerabilities_by_version($vulnerabilities, $version); }

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