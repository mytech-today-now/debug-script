# WordPress Debug Tool - GitHub Merge Workflow

**Repository**: WP_meme_player (WordPress Debug Tool)
**Target Branch**: origin/main
**Current Branch**: main
**Workflow Version**: 2.0 (WordPress Debug Tool Specialized)
**Developed by**: myTech.Today

---

## Workflow Overview and Usage Instructions

### Purpose
This workflow provides a systematic approach to organizing, committing, and merging WordPress Debug Tool enhancements to the main branch with professional documentation and quality assurance following myTech.Today standards.

### Prerequisites
- Git repository with main branch as default
- PHP 8.1+ development environment
- WordPress 6.0+ testing environment
- Access to push to origin/main
- Understanding of WordPress development standards

### Usage Instructions
1. **Customize placeholders**: Replace all [PLACEHOLDER] values with debug tool specific information
2. **Adapt categories**: Focus on WordPress-specific commit categories
3. **Configure tools**: Ensure PHP linting, WordPress coding standards, and testing tools are configured
4. **Review standards**: Align with myTech.Today WordPress development standards

### Workflow Phases
1. **Analysis Phase**: WordPress debug tool status verification and change categorization
2. **Commit Phase**: Systematic organization of WordPress-specific changes into logical commits
3. **Verification Phase**: WordPress compatibility and functionality testing
4. **Merge Phase**: Push to remote and WordPress environment verification
5. **Monitoring Phase**: Post-merge validation and WordPress site testing

---

## Pre-Merge Analysis and Preparation

### 1. Repository Status Verification
- [ ] Confirm current branch is main and up-to-date with origin/main
- [ ] Verify no staged changes exist (git diff --cached should be empty)
- [ ] Confirm working directory status and identify all changes
- [ ] Check remote connectivity: git remote -v
- [ ] Document current version numbers (VERSION file, package.json, CHANGELOG.md)

### 2. Backup and Safety Measures
- [ ] Create backup branch: git checkout -b backup/pre-merge-$(date +%Y%m%d-%H%M%S)
- [ ] Return to main: git checkout main
- [ ] Test WordPress debug tool functionality on test site
- [ ] Run PHP syntax checks: php -l debug-script/debug.php
- [ ] Check WordPress coding standards: phpcs --standard=WordPress debug-script/
- [ ] Verify debug tool loads without errors in WordPress environment

### 3. Change Analysis and Categorization
- [ ] Analyze modified files: git diff --name-only
- [ ] Analyze untracked files: git status --porcelain | grep "^??"
- [ ] Analyze deleted files: git status --porcelain | grep "^.D"
- [ ] Count total changes: git status --porcelain | wc -l
- [ ] Categorize changes using WordPress-specific file patterns:
  - **Debug Tool Core**: debug-script/debug.php, debug-script/*.php
  - **Documentation**: *.md, README*, CHANGELOG*, debug-script/README.md
  - **AI Prompts**: debug-script/ai_prompts/, *.md prompts
  - **Test Results**: debug-script/debug_results.html, test outputs
  - **Scripts**: debug-script/scripts/, *.sh, *.ps1, utility scripts
  - **Configuration**: *.json, *.yml, .htaccess, wp-config related
- [ ] Determine commit grouping strategy based on WordPress development patterns
- [ ] Plan semantic versioning increment (patch/minor/major) for debug tool
- [ ] Estimate total number of commits needed (recommended: 3-6 commits for WordPress tools)
- [ ] Document WordPress-specific change summary for reference

---

## Staged Commit Workflow

### 4. WordPress Debug Tool Core Enhancements
**Commit Type**: feat(debug) or fix(debug)
**Target Files**: debug-script/debug.php, core debug functionality
**Priority**: High (affects WordPress diagnostic capabilities)

**Identification Process**:
```bash
# Identify debug tool core files to commit
git status --porcelain | grep -E "debug-script/debug\.php|debug-script/.*\.php"
```

**Validation Checklist**:
- [ ] Verify PHP syntax is valid: php -l debug-script/debug.php
- [ ] Check WordPress coding standards compliance
- [ ] Test debug tool functionality on WordPress test site
- [ ] Ensure no security vulnerabilities in diagnostic code

**Commit Process**:
```bash
# Add debug tool core files
git add debug-script/debug.php [OTHER_DEBUG_PHP_FILES]

# Commit with structured professional message
git commit -m "feat(debug): Enhance WordPress debug tool diagnostic capabilities

Summary: [ONE_LINE_SUMMARY_OF_DEBUG_ENHANCEMENTS]

Enhancements:
- [SPECIFIC_DIAGNOSTIC_FEATURE_1]
- [SPECIFIC_ANALYSIS_IMPROVEMENT_2]
- [SPECIFIC_ACTIONABLE_SOLUTION_3]
- [SPECIFIC_PERFORMANCE_OPTIMIZATION_4]

WordPress Compatibility:
- WordPress: [MINIMUM_WP_VERSION_SUPPORTED]
- PHP: [MINIMUM_PHP_VERSION_SUPPORTED]
- Security: [DESCRIBE_SECURITY_IMPROVEMENTS]

Impact:
- Diagnostics: [DESCRIBE_DIAGNOSTIC_IMPROVEMENTS]
- User Experience: [DESCRIBE_UX_IMPROVEMENTS]
- Performance: [DESCRIBE_PERFORMANCE_IMPACT]

Breaking Changes: [NONE_OR_DESCRIBE_BREAKING_CHANGES]
CHANGELOG: [DESCRIBE_CHANGELOG_IMPACT]"

# Verify commit was created successfully
git log --oneline -1
```

### 5. WordPress Debug Tool Testing and Validation
**Commit Type**: test(debug) or chore(testing)
**Target Files**: Test results, validation reports, debug output samples
**Priority**: High (critical for WordPress compatibility and reliability)

**Identification Process**:
```bash
# Identify test and validation files
git status --porcelain | grep -E "debug_results\.html|test.*\.html|validation|sample.*output"
```

**Validation Checklist**:
- [ ] Verify debug tool works on multiple WordPress versions
- [ ] Test with various WordPress themes and plugins
- [ ] Validate output accuracy and completeness
- [ ] Ensure no PHP errors or warnings generated

**Commit Process**:
```bash
# Add test and validation files
git add debug-script/debug_results.html [OTHER_TEST_FILES]

# Commit with structured professional message
git commit -m "test(debug): Add WordPress debug tool validation and test results

Summary: [ONE_LINE_SUMMARY_OF_TESTING_CHANGES]

Test Results:
- [SPECIFIC_WORDPRESS_VERSION_TESTED]
- [SPECIFIC_THEME_COMPATIBILITY_TESTED]
- [SPECIFIC_PLUGIN_COMPATIBILITY_TESTED]

Validation:
- [DESCRIBE_DIAGNOSTIC_ACCURACY]
- [DESCRIBE_PERFORMANCE_VALIDATION]
- [DESCRIBE_SECURITY_VALIDATION]

WordPress Compatibility:
- WordPress: [TESTED_WP_VERSIONS]
- PHP: [TESTED_PHP_VERSIONS]
- Themes: [TESTED_THEME_TYPES]

Quality Impact: [DESCRIBE_RELIABILITY_IMPROVEMENTS]
CHANGELOG: [DESCRIBE_CHANGELOG_IMPACT]"

# Verify commit and test WordPress functionality
git log --oneline -1
# Test on WordPress site if available
```

### 6. WordPress Debug Tool Utility Scripts
**Commit Type**: chore(scripts) or feat(tooling)
**Target Files**: Utility scripts, automation tools, helper scripts

**Process**:
```bash
# Identify script and utility files
git status --porcelain | grep -E "debug-script/scripts/|\.sh$|\.ps1$|utility|helper"

# Add script files
git add debug-script/scripts/ [SCRIPT_FILES]

# Commit with professional message
git commit -m "chore(scripts): Add WordPress debug tool utility scripts

- [DESCRIBE_SCRIPT_ADDITIONS]
- [DESCRIBE_AUTOMATION_IMPROVEMENTS]
- [DESCRIBE_UTILITY_FUNCTIONS]
- [DESCRIBE_HELPER_TOOLS]

Tooling: [DESCRIBE_TOOLING_IMPROVEMENTS]
WordPress: [DESCRIBE_WP_INTEGRATION]
CHANGELOG: [DESCRIBE_CHANGELOG_IMPACT]"
```

### 7. AI Prompts and Automation Templates
**Commit Type**: docs(prompts) or feat(automation)
**Target Files**: AI prompt files, automation templates, workflow guides

**Process**:
```bash
# Identify AI prompt and automation files
git status --porcelain | grep -E "ai_prompts/|prompts/|automation|workflow|template"

# Add AI prompt files
git add debug-script/ai_prompts/ [PROMPT_FILES]

# Commit with professional message
git commit -m "docs(prompts): Enhance AI prompts and automation templates

- [DESCRIBE_PROMPT_IMPROVEMENTS]
- [DESCRIBE_AUTOMATION_ENHANCEMENTS]
- [DESCRIBE_WORKFLOW_UPDATES]
- [DESCRIBE_TEMPLATE_ADDITIONS]

Automation: [DESCRIBE_AUTOMATION_BENEFITS]
AI Integration: [DESCRIBE_AI_IMPROVEMENTS]
CHANGELOG: [DESCRIBE_CHANGELOG_IMPACT]"
```

### 8. WordPress Debug Tool Documentation
**Commit Type**: docs(debug) or docs(enhancement)
**Target Files**: README files, documentation, usage guides

**Process**:
```bash
# Identify documentation files
git status --porcelain | grep -E "debug-script/README|docs/|\.md$|documentation"

# Add documentation files
git add debug-script/README.md [DOCUMENTATION_FILES]

# Commit with professional message
git commit -m "docs(debug): Update WordPress debug tool documentation

- [DESCRIBE_DOCUMENTATION_UPDATES]
- [DESCRIBE_README_IMPROVEMENTS]
- [DESCRIBE_USAGE_GUIDE_ENHANCEMENTS]
- [DESCRIBE_INSTALLATION_INSTRUCTIONS]

Documentation: [DESCRIBE_COVERAGE_IMPROVEMENTS]
WordPress: [DESCRIBE_WP_SPECIFIC_DOCS]
CHANGELOG: [DESCRIBE_CHANGELOG_IMPACT]"
```

### 10. Scripts and Automation Tools
**Commit Type**: chore(scripts) or feat(tooling)
**Target Files**: Build scripts, automation tools, utility scripts

**Process**:
```bash
# Identify script and tooling files
git status --porcelain | grep -E "scripts/|tools/|bin/|\.sh$|\.ps1$"

# Add script files
git add [SCRIPT_FILES]

# Commit with professional message
git commit -m "chore(scripts): Add automation scripts and development tools

- [DESCRIBE_SCRIPT_ADDITIONS]
- [DESCRIBE_AUTOMATION_IMPROVEMENTS]
- [DESCRIBE_DEVELOPMENT_TOOLS]
- [DESCRIBE_BUILD_ENHANCEMENTS]

Tooling: [DESCRIBE_TOOLING_IMPROVEMENTS]
Development: [DESCRIBE_DEVELOPER_EXPERIENCE]
CHANGELOG: [DESCRIBE_CHANGELOG_IMPACT]"
```

### 11. Documentation and Knowledge Management
**Commit Type**: docs(enhancement) or docs(update)
**Target Files**: Documentation files, README, guides, AI prompts

**Process**:
```bash
# Identify documentation files
git status --porcelain | grep -E "docs/|README|\.md$|\.txt$|prompts/"

# Add documentation files
git add [DOCUMENTATION_FILES]

# Commit with professional message
git commit -m "docs(enhancement): Update documentation and knowledge resources

- [DESCRIBE_DOCUMENTATION_UPDATES]
- [DESCRIBE_README_IMPROVEMENTS]
- [DESCRIBE_GUIDE_ADDITIONS]
- [DESCRIBE_KNOWLEDGE_RESOURCES]

Documentation: [DESCRIBE_COVERAGE_IMPROVEMENTS]
Maintenance: [DESCRIBE_MAINTENANCE_IMPROVEMENTS]
CHANGELOG: [DESCRIBE_CHANGELOG_IMPACT]"
```

### 12. Test Reports and Analysis Data
**Commit Type**: chore(reports) or chore(logs)
**Target Files**: Test logs, analysis reports, metrics, audit trails

**Process**:
```bash
# Identify report and log files
git status --porcelain | grep -E "test-logs/|reports/|\.log$|analysis|metrics"

# Add report files
git add [REPORT_AND_LOG_FILES]

# Commit with professional message
git commit -m "chore(reports): Add test execution logs and analysis reports

- [DESCRIBE_TEST_LOGS]
- [DESCRIBE_ANALYSIS_REPORTS]
- [DESCRIBE_METRICS_DATA]
- [DESCRIBE_AUDIT_TRAILS]

Testing: [DESCRIBE_TESTING_DOCUMENTATION]
Analysis: [DESCRIBE_ANALYSIS_IMPROVEMENTS]
Compliance: [DESCRIBE_COMPLIANCE_DOCUMENTATION]
CHANGELOG: [DESCRIBE_CHANGELOG_IMPACT]"
```

### 9. WordPress Debug Tool Version and Release Management
**Commit Type**: chore(release) or chore(version)
**Target Files**: VERSION file, CHANGELOG.md, debug tool version headers

**Process**:
```bash
# Synchronize version numbers across WordPress debug tool files
# Update VERSION file, CHANGELOG.md, and debug.php version header

# Add version-related files
git add VERSION CHANGELOG.md debug-script/debug.php

# Commit with professional message
git commit -m "chore(release): Update WordPress debug tool version and changelog

- Updated debug tool version from [OLD_VERSION] to [NEW_VERSION]
- Added comprehensive changelog entries for WordPress enhancements
- Documented WordPress compatibility and requirements
- Updated semantic versioning for [PATCH/MINOR/MAJOR] release
- Synchronized version numbers across debug tool files

Version: [OLD_VERSION] → [NEW_VERSION] ([RELEASE_TYPE] release)
WordPress: [DESCRIBE_WP_COMPATIBILITY]
Features: [DESCRIBE_DEBUG_FEATURES]
Breaking: [NONE_OR_DESCRIBE_BREAKING_CHANGES]
CHANGELOG: [DESCRIBE_CHANGELOG_COMPLETENESS]"
```

---

## Branch Management and Merge Strategy

### 10. Pre-Merge Verification and Quality Assurance
**Critical Phase**: All checks must pass before proceeding to merge

**WordPress Debug Tool Quality Verification**:
- [ ] Test debug tool on WordPress test site: Load debug.php in browser
- [ ] Verify PHP syntax is valid: php -l debug-script/debug.php
- [ ] Check WordPress coding standards: phpcs --standard=WordPress debug-script/
- [ ] Test with multiple WordPress versions (6.0+, 6.1+, 6.2+)
- [ ] Verify no PHP errors or warnings in WordPress error log
- [ ] Test debug tool with various themes and plugins active

**Git and Commit Verification**:
- [ ] Verify all commits follow conventional commit format (feat, fix, chore, docs)
- [ ] Check for any remaining unstaged changes: git status
- [ ] Validate commit message quality and WordPress-specific details
- [ ] Ensure debug tool version numbers are synchronized across files
- [ ] Verify no merge conflicts exist
- [ ] Check that all intended WordPress debug files are committed

**WordPress Documentation and Metadata Verification**:
- [ ] Ensure CHANGELOG.md is updated with WordPress-specific changes
- [ ] Verify debug-script/README.md reflects new features or changes
- [ ] Check that debug tool version numbers are consistent
- [ ] Validate WordPress compatibility requirements are documented
- [ ] Ensure installation and usage instructions are current

**Final Pre-Push Checklist**:
- [ ] WordPress debug tool functionality verified on test site
- [ ] Manual testing completed with various WordPress configurations
- [ ] Code review completed (if required by team process)
- [ ] All WordPress compatibility requirements met
- [ ] Backup branch created and verified

### 11. Remote Repository Push
```bash
# Push all commits to remote main branch
git push origin main

# Verify push was successful
git log --oneline -5
```

### 12. Remote Merge Verification
- [ ] Check GitHub repository displays all new WordPress debug tool commits
- [ ] Verify any automated checks pass (if configured)
- [ ] Confirm debug tool files are properly displayed on GitHub
- [ ] Validate README.md renders correctly on repository page
- [ ] Check for any merge conflicts or issues
- [ ] Verify all debug tool files are accessible and properly formatted

---

## Post-Merge Verification and Monitoring

### 13. WordPress Debug Tool Deployment and Functionality Verification
- [ ] Deploy debug tool to WordPress test environment
- [ ] Test debug tool functionality on live WordPress site
- [ ] Verify all diagnostic sections work correctly
- [ ] Validate actionable solutions links are functional
- [ ] Confirm no PHP errors or warnings in WordPress environment
- [ ] Test debug tool with different WordPress themes
- [ ] Verify debug tool works with common WordPress plugins
- [ ] Check debug tool performance and load times

### 14. WordPress Debug Tool Monitoring and Validation
- [ ] Monitor debug tool usage and performance for initial period
- [ ] Check for any PHP errors or WordPress compatibility issues
- [ ] Verify debug tool provides accurate diagnostic information
- [ ] Confirm actionable solutions are helpful and current
- [ ] Validate WordPress version compatibility
- [ ] Check debug tool security and access controls

---

## Emergency Rollback Procedures

### 19. Rollback Strategy (If Critical Issues Detected)

**Immediate Assessment**:
- [ ] Identify severity level: Critical, High, Medium, Low
- [ ] Determine impact scope: Production, Staging, Development
- [ ] Assess rollback urgency: Immediate, Scheduled, Next Release

**Rollback Options (Choose based on situation)**:

**Option 1: Selective Commit Revert (Recommended for isolated issues)**
```bash
# Identify problematic commit
git log --oneline -10

# Revert specific commit(s)
git revert [COMMIT_HASH] --no-edit

# Push revert commit
git push origin main
```

**Option 2: Complete Rollback to Previous Stable State**
```bash
# Reset to previous stable version (DESTRUCTIVE - use with caution)
git reset --hard [PREVIOUS_STABLE_COMMIT_HASH]

# Force push (requires team coordination)
git push origin main --force-with-lease
```

**Option 3: Emergency Hotfix Branch**
```bash
# Create hotfix branch from stable commit
git checkout [STABLE_COMMIT_HASH]
git checkout -b hotfix/emergency-fix-$(date +%Y%m%d-%H%M%S)

# Apply minimal critical fixes
# [APPLY_FIXES]

# Push hotfix branch
git push origin hotfix/emergency-fix-$(date +%Y%m%d-%H%M%S)

# Create pull request for review and merge
```

**Option 4: Backup Branch Restoration**
```bash
# Use pre-merge backup branch
git checkout backup/pre-merge-[TIMESTAMP]
git checkout -b restore/rollback-$(date +%Y%m%d-%H%M%S)

# Cherry-pick any critical fixes if needed
git cherry-pick [CRITICAL_FIX_COMMITS]

# Push restoration branch
git push origin restore/rollback-$(date +%Y%m%d-%H%M%S)
```

### 20. Incident Response and Recovery

**Immediate Response (0-15 minutes)**:
- [ ] Document critical issue in GitHub Issues with "critical" and "production" labels
- [ ] Notify stakeholders via established communication channels
- [ ] Implement chosen rollback strategy
- [ ] Verify rollback success and system stability

**Short-term Response (15 minutes - 2 hours)**:
- [ ] Conduct initial root cause analysis
- [ ] Document timeline of events and actions taken
- [ ] Communicate status updates to stakeholders
- [ ] Monitor system metrics for stability

**Medium-term Response (2-24 hours)**:
- [ ] Complete comprehensive root cause analysis
- [ ] Plan detailed remediation strategy
- [ ] Update monitoring and alerting based on incident
- [ ] Prepare incident report with lessons learned

**Long-term Response (1-7 days)**:
- [ ] Schedule post-incident review meeting with all stakeholders
- [ ] Update rollback procedures based on lessons learned
- [ ] Implement preventive measures to avoid similar issues
- [ ] Update team training and documentation
- [ ] Review and improve deployment and testing processes

---

## Success Metrics and Validation

### Quality Assurance Metrics
- **Commit Quality**: All commits follow conventional commit format
- **Test Coverage**: Maintain or improve existing test coverage percentage
- **Security**: No new security vulnerabilities introduced
- **Performance**: No degradation in key performance metrics
- **Documentation**: All changes properly documented
- **Compatibility**: No breaking changes without proper migration path

### Operational Metrics
- **Build Success**: All CI/CD pipeline stages pass successfully
- **Deployment**: Successful deployment to staging and production
- **Monitoring**: All health checks and monitoring systems operational
- **Rollback Readiness**: Clear rollback path available if needed

---

---

## Troubleshooting Guide

### Common Issues and Solutions

**Issue: Tests failing after organizing commits**
- Solution: Run tests after each commit group to isolate failures
- Prevention: Ensure test dependencies are committed in correct order

**Issue: Build failures due to missing dependencies**
- Solution: Verify package.json and lock files are committed together
- Prevention: Always commit dependency files as a group

**Issue: Merge conflicts during push**
- Solution: Pull latest changes, resolve conflicts, and re-push
- Prevention: Ensure branch is up-to-date before starting workflow

**Issue: CI/CD pipeline failures**
- Solution: Check pipeline logs, fix issues, and re-run
- Prevention: Test pipeline locally before pushing

**Issue: Version number inconsistencies**
- Solution: Manually synchronize all version files before final commit
- Prevention: Use automated version management tools

### Workflow Customization Guidelines

**For Small Teams (1-5 developers)**:
- Reduce commit groups to 3-5 categories
- Simplify verification steps
- Use shorter commit messages

**For Large Teams (10+ developers)**:
- Increase commit granularity
- Add mandatory code review steps
- Implement stricter verification processes

**For High-Risk Projects**:
- Add additional testing phases
- Require multiple approvals
- Implement staged deployment verification

---

## Workflow Metadata and Information

**Workflow Type**: WordPress Debug Tool GitHub Merge Workflow
**Version**: 2.0 (WordPress Specialized)
**Last Updated**: [UPDATE_DATE]
**Developed by**: myTech.Today
**Estimated Completion Time**: 30 minutes - 2 hours (varies by debug tool enhancement complexity)
**Risk Level**: Low to Medium (WordPress compatibility testing required)
**Rollback Complexity**: Low (clear revert path with WordPress-specific backup strategy)
**Team Size Compatibility**: 1-10 developers (WordPress development teams)
**Project Type Compatibility**: WordPress plugins, debug tools, diagnostic utilities

**WordPress-Specific Maintenance Notes**:
- Review and update workflow when WordPress versions change
- Adapt commit categories based on WordPress development patterns
- Update PHP and WordPress compatibility requirements
- Test with latest WordPress versions and popular plugins
- Gather feedback from WordPress development community

**Support and Documentation**:
- WordPress questions: myTech.Today WordPress Development Team
- Technical issues: WordPress Debug Tool Repository Issues
- Process improvements: myTech.Today Development Standards
