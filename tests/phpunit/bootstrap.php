<?php
/**
 * PHPUnit bootstrap file
 */

// First, we need to load the WordPress testing environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Load the WordPress tests setup file
require_once $_tests_dir . '/includes/bootstrap.php';

// Load the plugin files
require_once dirname(dirname(__DIR__)) . '/guest-post-plugin.php';