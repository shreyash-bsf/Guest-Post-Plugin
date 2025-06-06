<?php
/**
 * Class EmailFunctionsTest
 *
 * @package Guest_Post_Plugin
 */

/**
 * Email functions test case.
 */
class EmailFunctionsTest extends WP_UnitTestCase {

    /**
     * Test admin notification email function.
     */
    public function test_guest_post_send_admin_notification() {
        // Create a test post
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test Post',
            'post_status' => 'pending',
        ));
        
        // Add required meta data
        update_post_meta($post_id, '_guest_author_name', 'Test Author');
        update_post_meta($post_id, '_guest_author_email', 'test@example.com');
        update_post_meta($post_id, '_approve_token', 'test_approve_token');
        update_post_meta($post_id, '_reject_token', 'test_reject_token');
        update_post_meta($post_id, '_is_guest_post', true);
        
        // Mock wp_mail
        add_filter('wp_mail', function($args) {
            $this->assertContains('New Guest Post Submission: "Test Post"', $args['subject']);
            $this->assertContains('test@example.com', $args['message']);
            return $args;
        });
        
        // Set options
        update_option('guest_post_plugin_options', array(
            'send_admin_notification' => 'yes',
            'admin_email' => 'admin@example.com'
        ));
        
        // Call the function
        $result = guest_post_send_admin_notification($post_id);
        
        // Assert result
        $this->assertTrue($result);
    }
    
    /**
     * Test autoreply email function.
     */
    public function test_guest_post_send_autoreply() {
        // Create a test post
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test Post',
            'post_status' => 'pending',
        ));
        
        // Add required meta data
        update_post_meta($post_id, '_guest_author_name', 'Test Author');
        update_post_meta($post_id, '_guest_author_email', 'test@example.com');
        update_post_meta($post_id, '_is_guest_post', true);
        
        // Mock wp_mail
        add_filter('wp_mail', function($args) {
            $this->assertContains('Thank you for your guest post submission', $args['subject']);
            $this->assertContains('test@example.com', $args['to']);
            return $args;
        });
        
        // Set options
        update_option('guest_post_plugin_options', array(
            'send_autoreply' => 'yes',
            'autoreply_email_template' => 'Thank you {author_name} for submitting {post_title}'
        ));
        
        // Call the function
        $result = guest_post_send_autoreply($post_id);
        
        // Assert result
        $this->assertTrue($result);
    }
}