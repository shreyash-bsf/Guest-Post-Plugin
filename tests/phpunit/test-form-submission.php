<?php
/**
 * Class FormSubmissionTest
 *
 * @package Guest_Post_Plugin
 */

/**
 * Form submission test case.
 */
class FormSubmissionTest extends WP_UnitTestCase {

    /**
     * Test form submission handler.
     */
    public function test_handle_guest_post_submission() {
        // Set up test data
        $_POST = array(
            'action' => 'submit_guest_post',
            'guest_post_nonce' => wp_create_nonce('guest_post_nonce'),
            'post-title' => 'Test Post Title',
            'post-content' => 'Test post content',
            'author-name' => 'Test Author',
            'author-email' => 'test@example.com',
            'author-bio' => 'Test author bio'
        );
        
        // Set up options
        update_option('guest_post_plugin_options', array(
            'default_category' => 1,
            'require_moderation' => 'yes',
            'send_admin_notification' => 'no',
            'send_autoreply' => 'no'
        ));
        
        // Mock wp_insert_post to avoid actually creating a post
        add_filter('wp_insert_post_data', function($data) {
            $this->assertEquals('Test Post Title', $data['post_title']);
            $this->assertEquals('Test post content', $data['post_content']);
            $this->assertEquals('pending', $data['post_status']);
            return $data;
        });
        
        // Capture the JSON response
        ob_start();
        handle_guest_post_submission();
        $output = ob_get_clean();
        
        // Check if the response contains success message
        $this->assertContains('success', $output);
    }
    
    /**
     * Test form validation.
     */
    public function test_form_validation() {
        // Set up test data with missing fields
        $_POST = array(
            'action' => 'submit_guest_post',
            'guest_post_nonce' => wp_create_nonce('guest_post_nonce'),
            'post-title' => '', // Empty title
            'post-content' => 'Test post content',
            'author-name' => 'Test Author',
            'author-email' => 'test@example.com',
            'author-bio' => 'Test author bio'
        );
        
        // Capture the JSON response
        ob_start();
        handle_guest_post_submission();
        $output = ob_get_clean();
        
        // Check if the response contains error message
        $this->assertContains('error', $output);
        $this->assertContains('required fields', $output);
    }
}