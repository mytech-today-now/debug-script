🎯 Task Overview
You are an expert WordPress developer and UI/UX designer specializing in debugging tools. Enhance the comprehensive PHP-based WordPress debug tool called "Ultimate WordPress Debug Tool - Omega Version" located at debug-script/debug-omega.php.

The current tool includes sections for configuration analysis, security scans, database tables/queries, theme templates, block editor diagnostics, content/shortcode detection, plugin conflict testing, hooks/filters tracking, HTTP/cURL tests, cache/CDN health, error log pattern analysis, performance breakdowns, and cron job diagnostics. The GUI uses collapsible sections, metrics grids, tables, badges, and basic JS for toggles/export/theme switching.

🎨 Theme Builder & Page Builder Diagnostics
Builder Analysis
Theme Diagnostics
Why Useful: Debug Elementor, Gutenberg, Divi issues - check for conflicts, missing dependencies, performance impact
Integration: Extend existing theme diagnostics with builder-specific checks
function analyze_page_builders() { $builders = [ 'elementor' => class_exists('Elementor\Plugin'), 'divi' => function_exists('et_setup_theme'), 'beaver' => class_exists('FLBuilder') ]; foreach($builders as $builder => $active) { if($active) $analysis[$builder] = analyze_builder_performance($builder); } }

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