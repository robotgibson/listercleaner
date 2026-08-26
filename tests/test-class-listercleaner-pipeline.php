<?php
/**
 * Class ListerCleanerPipelineTest
 *
 * @package Listercleaner
 */

class ListerCleanerPipelineTest extends WP_UnitTestCase {

	/**
	 * Setup method run before every test case.
	 * Resets settings to ensure tests run in isolation.
	 */
	public function setUp() : void {
		parent::setUp();
		delete_option( 'listercleaner_settings_array' );
	}

	/**
	 * Test the execution instance of the pipeline core class.
	 */
	public function test_pipeline_instance_exists() {
		$instance = Lister_Cleaner_Pipeline::get_instance();
		$this->assertInstanceOf( 'Lister_Cleaner_Pipeline', $instance );
	}

	/**
	 * Test that forbidden script blocks are successfully dropped.
	 */
	public function test_strip_forbidden_tags() {
		update_option( 'listercleaner_settings_array', array( 'strip_tags' => 1 ) );

		$dirty_html = "<div><script>alert('evil');</script><p>Product Text</p></div>";
		$expected   = "<div><p>Product Text</p></div>";
		
		$cleaned_html = Lister_Cleaner_Pipeline::get_instance()->execute_pipeline( $dirty_html );
		$this->assertEquals( $expected, $cleaned_html );
	}

	/**
	 * Test that inline target attributes are properly injected.
	 */
	public function test_enforce_target_blank() {
		update_option( 'listercleaner_settings_array', array( 'target_blank' => 1 ) );

		$dirty_html = '<a href="https://example.com">Visit Store</a>';
		$expected   = '<a href="https://example.com" target="_blank">Visit Store</a>';

		$cleaned_html = Lister_Cleaner_Pipeline::get_instance()->execute_pipeline( $dirty_html );
		$this->assertEquals( $expected, $cleaned_html );
	}

	/**
	 * Test that whitelisted domains do not receive the target modification.
	 */
	public function test_whitelist_skips_target_blank() {
		update_option( 'listercleaner_settings_array', array(
			'target_blank'       => 1,
			'whitelist_textarea' => "my-safe-store.com\ninternal-link.org"
		) );

		$dirty_html = '<a href="https://my-safe-store.com">Safe Item</a>';
		$expected   = '<a href="https://my-safe-store.com">Safe Item</a>';

		$cleaned_html = Lister_Cleaner_Pipeline::get_instance()->execute_pipeline( $dirty_html );
		$this->assertEquals( $expected, $cleaned_html );
	}

	/**
	 * Mock Object Test: Simulates WP-Lister for eBay triggering its outbound hook.
	 */
	public function test_mock_wplister_ebay_hook_execution() {
		// 1. Enable multiple pipeline cleaning options
		update_option( 'listercleaner_settings_array', array(
			'strip_tags'   => 1,
			'force_https'  => 1,
			'target_blank' => 1
		) );

		// 2. Re-instantiate the main plugin loop to ensure filters register with our updated options
		Lister_Cleaner_Core_Plugin::get_instance();

		// 3. Create a simulated outbound product description layout from WP-Lister
		$mock_wplister_input = '<h2>Product Title</h2><script>console.log("drop me");</script><p>Buy from <a href="http://myshop.com">here</a>.</p>';
		
		// 4. Expected final output after running through filters
		$expected_wplister_output = '<h2>Product Title</h2><p>Buy from <a href="https://myshop.com" target="_blank">here</a>.</p>';

		// 5. Fire the actual WP-Lister filter hook programmatically
		$actual_output = apply_filters( 'wplister_ebay_processed_description', $mock_wplister_input, 999 );

		// 6. Assert that our plugin successfully intercepted the hook and sanitized the content
		$this->assertEquals( $expected_wplister_output, $actual_output );
	}

	/**
	 * Mock Object Test: Simulates WP-Lister for Amazon triggering its outbound hook.
	 */
	public function test_mock_wplister_amazon_hook_execution() {
		update_option( 'listercleaner_settings_array', array(
			'strip_js_events'  => 1,
			'clean_whitespace' => 1
		) );

		Lister_Cleaner_Core_Plugin::get_instance();

		// Input text with dynamic event attributes and runaway blank lines
		$mock_wplister_input = "<p onclick='doSomething()'>Product Details</p>\n\n\n\n<span>Footer</span>";
		$expected_wplister_output = "<p>Product Details</p>\n\n<span>Footer</span>";

		$actual_output = apply_filters( 'wplister_amazon_processed_description', $mock_wplister_input, 999 );

		$this->assertEquals( $expected_wplister_output, $actual_output );
	}
}

