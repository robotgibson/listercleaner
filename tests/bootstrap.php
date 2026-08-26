<?php
/**
 * PHPUnit Bootstrap for ListerCleaner
 *
 * @package Listercleaner
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/functions.php' ) ) {
	echo "Could not find $_tests_dir/functions.php, have you run bin/install-wp-tests.sh?" . PHP_EOL; // phpcs:ignore Welcome.Echo
	exit( 1 );
}

// Give access to core testing framework utilities
require_once $_tests_dir . '/functions.php';

/**
 * Manually load the ListerCleaner plugin into the test runtime environment.
 */
function _manually_load_listercleaner_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/listercleaner.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_listercleaner_plugin' );

// Start the WordPress Core test suite
require $_tests_dir . '/bootstrap.php';

