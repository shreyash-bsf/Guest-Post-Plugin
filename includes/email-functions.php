<?php
/**
 * Email Functions for Guest Post Plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send admin notification email
 *
 * @param int $post_id The post ID
 * @return bool Whether the email was sent successfully
 */
function guest_post_send_admin_notification($post_id) {
    $options = get_option('guest_post_plugin_options', array());
    
    if (isset($options['send_admin_notification']) && $options['send_admin_notification'] !== 'yes') {
        return false;
    }

    
    $admin_email = !empty($options['admin_email']) ? $options['admin_email'] : get_option('admin_email');
    
    // Get post data
    $post = get_post($post_id);
    $post_title = $post->post_title;
    $author_name = get_post_meta($post_id, '_guest_author_name', true);
    $author_email = get_post_meta($post_id, '_guest_author_email', true);
    
    // Get approval/rejection tokens
    $approve_token = get_post_meta($post_id, '_approve_token', true);
    $reject_token = get_post_meta($post_id, '_reject_token', true);
    
    $subject = 'New Guest Post Submission: "' . $post_title . '"';
    
    $approve_url = add_query_arg(array(
        'guest_post_action' => 'approve',
        'post_id' => $post_id,
        'token' => $approve_token
    ), home_url());
    
    $reject_url = add_query_arg(array(
        'guest_post_action' => 'reject',
        'post_id' => $post_id,
        'token' => $reject_token
    ), home_url());
    
    $preview_url = get_preview_post_link($post_id);
    $admin_url = admin_url('post.php?post=' . $post_id . '&action=edit');
    
    $message = "A new guest post was submitted:\n\n";
    $message .= "Title: " . $post_title . "\n";
    $message .= "Submitted by: " . $author_name . " (" . $author_email . ")\n\n";
    $message .= "Preview: " . $preview_url . "\n";
    $message .= "Approve: " . $approve_url . "\n";
    $message .= "Reject: " . $reject_url . "\n\n";
    $message .= "Login to review: " . $admin_url;
    
    // Add headers to improve email delivery
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <wordpress@' . parse_url(home_url(), PHP_URL_HOST) . '>'
    );
    
    $mail_sent = wp_mail($admin_email, $subject, $message, $headers);
    

    
    return $mail_sent;
}

/**
 * Send auto-reply email to submitter
 *
 * @param int $post_id The post ID
 * @return bool Whether the email was sent successfully
 */
function guest_post_send_autoreply($post_id) {
    $options = get_option('guest_post_plugin_options', array());
    
    if (isset($options['send_autoreply']) && $options['send_autoreply'] !== 'yes') {
        return false;
    }
    
    // Get author data directly from post meta
    $author_email = sanitize_email(get_post_meta($post_id, '_guest_author_email', true));
    $author_name = sanitize_text_field(get_post_meta($post_id, '_guest_author_name', true));
    $post_title = get_the_title($post_id);
    $site_name = get_bloginfo('name');
    
    $subject = 'Thank you for your guest post submission';
    
    $template = isset($options['autoreply_email_template']) ? $options['autoreply_email_template'] : "Hello {author_name},\n\nThank you for submitting your guest post \"{post_title}\".\n\nYour submission has been received and is currently pending review. We will notify you once a decision has been made.\n\nBest regards,\n{site_name} Team";
    $message = str_replace(
        array('{author_name}', '{post_title}', '{site_name}'),
        array($author_name, $post_title, $site_name),
        $template
    );
    
    // Add headers to improve email delivery
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <wordpress@' . parse_url(home_url(), PHP_URL_HOST) . '>'
    );
    
    $mail_sent = wp_mail($author_email, $subject, $message, $headers);
    

    
    return $mail_sent;
}