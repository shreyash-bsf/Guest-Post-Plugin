<?php
/**
 * Plugin Name: Guest Post Plugin
 * Plugin URI: https://example.com/plugins/guest-post-plugin
 * Description: Streamline guest post submissions with front-end form, draft creation, and quick approval links
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: guest-post-plugin
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('GUEST_POST_PLUGIN_VERSION', '1.0.0');

// Include email functions
require_once plugin_dir_path(__FILE__) . 'includes/email-functions.php';

/**
 * Plugin activation
 */
function activate_guest_post_plugin() {
    // Set default options
    $default_options = array(
        // General Settings
        'default_category' => 1,
        'require_moderation' => 'yes',
        'limit_submissions' => 3,
        
        // Notification Settings
        'admin_email' => get_option('admin_email'),
        'send_admin_notification' => 'yes',
        'send_autoreply' => 'yes',
        'approve_email_template' => "Hello {author_name},\n\nYour guest post \"{post_title}\" has been approved.\n\nThank you for your contribution!",
        'reject_email_template' => "Hello {author_name},\n\nUnfortunately, your guest post \"{post_title}\" was not approved.\n\nThank you for your interest.",
        'autoreply_email_template' => "Hello {author_name},\n\nThank you for submitting your guest post \"{post_title}\".\n\nYour submission has been received and is currently pending review. We will notify you once a decision has been made.\n\nBest regards,\n{site_name} Team",
        
        // Spam Protection
        'enable_recaptcha' => 'no',
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
        'enable_honeypot' => 'yes',
        'enable_ip_limit' => 'yes',
        'blocklist_domains' => 'spam.com, example.xyz, temp-mail.org',
        'blocklist_keywords' => 'viagra, casino, poker, loan',
        
        // Newsletter Integration
        'enable_mailchimp' => 'no',
        'mailchimp_api_key' => '',
        'mailchimp_list_id' => '',
        'newsletter_checkbox_label' => 'Subscribe to our newsletter',
        'newsletter_checkbox_default' => 'no',
        
        // Form Style
        'form_theme' => 'light',
    );
    
    add_option('guest_post_plugin_options', $default_options);
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'activate_guest_post_plugin');

/**
 * Plugin deactivation
 */
function deactivate_guest_post_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'deactivate_guest_post_plugin');

/**
 * Register the submission form shortcode
 */
function guest_post_form_shortcode() {
    // Get plugin options
    $options = get_option('guest_post_plugin_options');
    $theme_class = ($options['form_theme'] === 'dark') ? 'guest-post-form-dark' : 'guest-post-form-light';
    
    // Set up the React container with data attributes
    $container_attrs = array(
        'id' => 'guest-post-form-container',
        'class' => $theme_class,
        'data-ajax-url' => admin_url('admin-ajax.php'),
        'data-nonce' => wp_create_nonce('guest_post_nonce'),
        'data-label-title' => __('Post Title', 'guest-post-plugin'),
        'data-label-content' => __('Post Content', 'guest-post-plugin'),
        'data-label-name' => __('Your Name', 'guest-post-plugin'),
        'data-label-email' => __('Your Email', 'guest-post-plugin'),
        'data-label-bio' => __('Short Bio', 'guest-post-plugin'),
        'data-label-image' => __('Featured Image', 'guest-post-plugin'),
        'data-label-image-desc' => __('Recommended size: 1200x628 pixels', 'guest-post-plugin'),
        'data-label-submit' => __('Submit Guest Post', 'guest-post-plugin'),
        'data-label-submitting' => __('Submitting...', 'guest-post-plugin'),
        'data-enable-mailchimp' => $options['enable_mailchimp'],
        'data-newsletter-label' => isset($options['newsletter_checkbox_label']) ? esc_attr($options['newsletter_checkbox_label']) : '',
        'data-newsletter-default' => isset($options['newsletter_checkbox_default']) ? $options['newsletter_checkbox_default'] : 'no',
        'data-enable-honeypot' => $options['enable_honeypot']
    );
    
    $attrs_html = '';
    foreach ($container_attrs as $key => $value) {
        $attrs_html .= ' ' . $key . '="' . esc_attr($value) . '"';
    }
    
    // Return the container div that React will render into
    return '<div' . $attrs_html . '></div>';

}
add_shortcode('guest_post_form', 'guest_post_form_shortcode');

/**
 * Handle form submission
 */
function handle_guest_post_submission() {
    if (!isset($_POST['action']) || $_POST['action'] != 'submit_guest_post') {
        return;
    }

    // Verify nonce
    if (!isset($_POST['guest_post_nonce']) || !wp_verify_nonce($_POST['guest_post_nonce'], 'guest_post_nonce')) {
        wp_send_json_error('Security check failed');
        exit;
    }
    
    // Get plugin options
    $options = get_option('guest_post_plugin_options');
    
    // Check honeypot field if enabled
    if ($options['enable_honeypot'] === 'yes' && !empty($_POST['website'])) {
        // This is likely a bot submission, silently reject but return success message
        wp_send_json_success('Your guest post has been submitted successfully and is pending review.');
        exit;
    }

    // Validate required fields
    $required_fields = array('post-title', 'post-content', 'author-name', 'author-email', 'author-bio');
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error('Please fill in all required fields');
            exit;
        }
    }

    // Validate email
    if (!is_email($_POST['author-email'])) {
        wp_send_json_error('Please enter a valid email address');
        exit;
    }
    
    // Check for blocklisted email domains
    if (!empty($options['blocklist_domains'])) {
        $email = sanitize_email($_POST['author-email']);
        $domain = substr(strrchr($email, "@"), 1);
        $blocklist_domains = array_map('trim', explode(',', $options['blocklist_domains']));
        
        foreach ($blocklist_domains as $blocked_domain) {
            if (!empty($blocked_domain) && stripos($domain, $blocked_domain) !== false) {
                wp_send_json_error('This email domain is not allowed');
                exit;
            }
        }
    }
    
    // Check for blocklisted keywords in content
    if (!empty($options['blocklist_keywords'])) {
        $content = strtolower($_POST['post-title'] . ' ' . $_POST['post-content'] . ' ' . $_POST['author-bio']);
        $blocklist_keywords = array_map('trim', explode(',', $options['blocklist_keywords']));
        
        foreach ($blocklist_keywords as $keyword) {
            if (!empty($keyword) && stripos($content, $keyword) !== false) {
                wp_send_json_error('Your submission contains prohibited content');
                exit;
            }
        }
    }
    
    // Verify reCAPTCHA if enabled
    if ($options['enable_recaptcha'] === 'yes' && !empty($options['recaptcha_secret_key'])) {
        if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
            wp_send_json_error('Please complete the reCAPTCHA verification');
            exit;
        }
        
        $recaptcha_response = $_POST['g-recaptcha-response'];
        $recaptcha_secret = $options['recaptcha_secret_key'];
        
        // Make a request to the reCAPTCHA API
        $verify_response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $recaptcha_secret,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        ));
        
        if (is_wp_error($verify_response)) {
            wp_send_json_error('reCAPTCHA verification failed. Please try again.');
            exit;
        }
        
        $response_body = wp_remote_retrieve_body($verify_response);
        $response_data = json_decode($response_body, true);
        
        if (!$response_data['success']) {
            wp_send_json_error('reCAPTCHA verification failed. Please try again.');
            exit;
        }
    }
    
    // Check IP submission limit
    if (isset($options['enable_ip_limit']) && $options['enable_ip_limit'] === 'yes' && !empty($options['limit_submissions'])) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $submission_count = get_transient('guest_post_ip_' . md5($ip_address));
        
        if ($submission_count && $submission_count >= $options['limit_submissions']) {
            wp_send_json_error('You have reached the maximum number of submissions allowed. Please try again later.');
            exit;
        }
    }

    // Create post object
    $post_data = array(
        'post_title'    => sanitize_text_field($_POST['post-title']),
        'post_content'  => wp_kses_post($_POST['post-content']),
        'post_status'   => ($options['require_moderation'] === 'yes') ? 'pending' : 'publish',
        'post_type'     => 'post',
        'post_category' => array(intval($options['default_category'])),
    );

    // Insert the post into the database
    $post_id = wp_insert_post($post_data);

    if (!is_wp_error($post_id)) {
        // Add author meta
        update_post_meta($post_id, '_guest_author_name', sanitize_text_field($_POST['author-name']));
        update_post_meta($post_id, '_guest_author_email', sanitize_email($_POST['author-email']));
        update_post_meta($post_id, '_guest_author_bio', wp_kses_post($_POST['author-bio']));
        update_post_meta($post_id, '_is_guest_post', true);
        
        // Handle featured image upload
        if (!empty($_FILES['featured-image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('featured-image', $post_id);
            
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
        
        // Generate approval/rejection tokens
        $approve_token = wp_generate_password(32, false);
        $reject_token = wp_generate_password(32, false);
        
        update_post_meta($post_id, '_approve_token', $approve_token);
        update_post_meta($post_id, '_reject_token', $reject_token);
        
        // Update IP submission count
        if (isset($options['enable_ip_limit']) && $options['enable_ip_limit'] === 'yes' && !empty($options['limit_submissions'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $submission_count = get_transient('guest_post_ip_' . md5($ip_address));
            
            if (!$submission_count) {
                $submission_count = 1;
            } else {
                $submission_count++;
            }
            
            set_transient('guest_post_ip_' . md5($ip_address), $submission_count, DAY_IN_SECONDS);
        }
        
        // Send notification email to admin using the dedicated function
        guest_post_send_admin_notification($post_id);
        
        // Send auto-reply to submitter using the dedicated function
        guest_post_send_autoreply($post_id);
        
        // Subscribe to Mailchimp if enabled and user opted in
        if ($options['enable_mailchimp'] === 'yes' && 
            !empty($options['mailchimp_api_key']) && 
            !empty($options['mailchimp_list_id']) && 
            isset($_POST['subscribe_newsletter']) && 
            $_POST['subscribe_newsletter'] === 'yes') {
            
            $author_email = sanitize_email($_POST['author-email']);
            $author_name = sanitize_text_field($_POST['author-name']);
            
            // Split name into first and last name (best effort)
            $name_parts = explode(' ', $author_name, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
            
            // Prepare data for Mailchimp
            $api_key = $options['mailchimp_api_key'];
            $list_id = $options['mailchimp_list_id'];
            
            // Get API server from API key
            $api_parts = explode('-', $api_key);
            $server = isset($api_parts[1]) ? $api_parts[1] : '';
            
            if (!empty($server)) {
                $url = 'https://' . $server . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/';
                
                $data = array(
                    'email_address' => $author_email,
                    'status' => 'subscribed',
                    'merge_fields' => array(
                        'FNAME' => $first_name,
                        'LNAME' => $last_name
                    )
                );
                
                $response = wp_remote_post($url, array(
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode('anystring:' . $api_key),
                        'Content-Type' => 'application/json'
                    ),
                    'body' => json_encode($data),
                    'timeout' => 15
                ));
                
                // Log subscription attempt (optional)
                if (is_wp_error($response)) {
                    error_log('Mailchimp subscription failed: ' . $response->get_error_message());
                }
            }
        }
        
        wp_send_json_success('Your guest post has been submitted successfully and is pending review.');
    } else {
        wp_send_json_error('There was an error submitting your post. Please try again.');
    }
    
    exit;
}
add_action('wp_ajax_submit_guest_post', 'handle_guest_post_submission');
add_action('wp_ajax_nopriv_submit_guest_post', 'handle_guest_post_submission');

/**
 * Handle approval/rejection links
 */
function handle_guest_post_actions() {
    if (!isset($_GET['guest_post_action']) || !isset($_GET['post_id']) || !isset($_GET['token'])) {
        return;
    }
    
    $action = sanitize_text_field($_GET['guest_post_action']);
    $post_id = intval($_GET['post_id']);
    $token = sanitize_text_field($_GET['token']);
    
    $post = get_post($post_id);
    if (!$post) {
        return;
    }
    
    $options = get_option('guest_post_plugin_options');
    
    if ($action === 'approve') {
        $stored_token = get_post_meta($post_id, '_approve_token', true);
        if ($token === $stored_token) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish'
            ));
            delete_post_meta($post_id, '_approve_token');
            delete_post_meta($post_id, '_reject_token');
            
            // Notify author
            $author_email = get_post_meta($post_id, '_guest_author_email', true);
            $author_name = get_post_meta($post_id, '_guest_author_name', true);
            
            if ($author_email) {
                $subject = 'Your Guest Post Has Been Approved';
                
                $template = $options['approve_email_template'];
                $message = str_replace(
                    array('{author_name}', '{post_title}'),
                    array($author_name, $post->post_title),
                    $template
                );
                
                wp_mail($author_email, $subject, $message);
            }
            
            wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit&message=1'));
            exit;
        }
    } elseif ($action === 'reject') {
        $stored_token = get_post_meta($post_id, '_reject_token', true);
        if ($token === $stored_token) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'trash'
            ));
            delete_post_meta($post_id, '_approve_token');
            delete_post_meta($post_id, '_reject_token');
            
            // Notify author
            $author_email = get_post_meta($post_id, '_guest_author_email', true);
            $author_name = get_post_meta($post_id, '_guest_author_name', true);
            
            if ($author_email) {
                $subject = 'Your Guest Post Was Not Approved';
                
                $template = $options['reject_email_template'];
                $message = str_replace(
                    array('{author_name}', '{post_title}'),
                    array($author_name, $post->post_title),
                    $template
                );
                
                wp_mail($author_email, $subject, $message);
            }
            
            wp_redirect(admin_url('edit.php?post_status=trash&post_type=post&message=1'));
            exit;
        }
    }
}
add_action('template_redirect', 'handle_guest_post_actions');

/**
 * Add meta box for guest post information
 */
function add_guest_post_meta_box() {
    add_meta_box(
        'guest_post_meta_box',
        'Guest Post Information',
        'display_guest_post_meta_box',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_guest_post_meta_box');

/**
 * Display guest post meta box
 */
function display_guest_post_meta_box($post) {
    $is_guest_post = get_post_meta($post->ID, '_is_guest_post', true);
    
    if (!$is_guest_post) {
        echo '<p>This is not a guest post submission.</p>';
        return;
    }
    
    $author_name = get_post_meta($post->ID, '_guest_author_name', true);
    $author_email = get_post_meta($post->ID, '_guest_author_email', true);
    $author_bio = get_post_meta($post->ID, '_guest_author_bio', true);
    
    echo '<p><strong>Guest Author:</strong> ' . esc_html($author_name) . '</p>';
    echo '<p><strong>Email:</strong> ' . esc_html($author_email) . '</p>';
    echo '<p><strong>Bio:</strong> ' . esc_html($author_bio) . '</p>';
}

/**
 * Add admin menu
 */
function guest_post_plugin_admin_menu() {
    add_options_page(
        'Guest Post Settings',
        'Guest Post Plugin',
        'manage_options',
        'guest-post-plugin',
        'guest_post_plugin_settings_page'
    );
}
add_action('admin_menu', 'guest_post_plugin_admin_menu');

/**
 * Register settings
 */
function guest_post_plugin_register_settings() {
    register_setting('guest_post_plugin_options', 'guest_post_plugin_options');
}
add_action('admin_init', 'guest_post_plugin_register_settings');

/**
 * Enqueue admin scripts and styles
 */
function guest_post_plugin_admin_scripts($hook) {
    if ($hook != 'settings_page_guest-post-plugin' && $hook != 'index.php') {
        return;
    }
    
    wp_enqueue_style('guest-post-plugin-admin', plugin_dir_url(__FILE__) . 'css/admin.css', array(), GUEST_POST_PLUGIN_VERSION);
    wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.2.0', true);
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.2.0', true);
    wp_enqueue_script('guest-post-plugin-admin', plugin_dir_url(__FILE__) . 'js/admin.js', array('react', 'react-dom'), GUEST_POST_PLUGIN_VERSION, true);
}
add_action('admin_enqueue_scripts', 'guest_post_plugin_admin_scripts');

/**
 * Settings page
 */
function guest_post_plugin_settings_page() {
    $options = get_option('guest_post_plugin_options');
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
    ?>
    <div class="wrap">
        <h1 class="text-2xl font-bold mb-4"><?php _e('Guest Post Plugin Settings', 'guest-post-plugin'); ?></h1>
        
        <nav class="nav-tab-wrapper wp-clearfix">
            <a href="?page=guest-post-plugin&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General Settings', 'guest-post-plugin'); ?></a>
            <a href="?page=guest-post-plugin&tab=notification" class="nav-tab <?php echo $active_tab == 'notification' ? 'nav-tab-active' : ''; ?>"><?php _e('Notification Settings', 'guest-post-plugin'); ?></a>
            <a href="?page=guest-post-plugin&tab=style" class="nav-tab <?php echo $active_tab == 'style' ? 'nav-tab-active' : ''; ?>"><?php _e('Form Style', 'guest-post-plugin'); ?></a>
        </nav>
        
        <form method="post" action="options.php">
            <?php settings_fields('guest_post_plugin_options'); ?>
            
            <?php if ($active_tab == 'general') : ?>
                <div id="general-settings" class="settings-tab">
                    <!-- Basic Settings Section -->
                    <div class="bg-white border border-gray-200 shadow-sm mb-5 rounded w-full open">
                        <h3 class="border-b border-gray-200 m-0 py-4 px-5 text-base font-semibold bg-gray-50 cursor-pointer relative break-words" tabindex="0" role="button" aria-expanded="true"><?php _e('Basic Settings', 'guest-post-plugin'); ?></h3>
                        <div class="settings-section-content p-4">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="default_category"><?php _e('Default Category', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Select the default category for guest post submissions', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <select name="guest_post_plugin_options[default_category]" id="default_category">
                                            <?php
                                            $categories = get_categories(array('hide_empty' => false));
                                            foreach ($categories as $category) {
                                                echo '<option value="' . $category->term_id . '" ' . selected($options['default_category'], $category->term_id, false) . '>' . $category->name . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="require_moderation"><?php _e('Require Moderation', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('If enabled, posts will require approval before publishing', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <select name="guest_post_plugin_options[require_moderation]" id="require_moderation">
                                            <option value="yes" <?php selected($options['require_moderation'], 'yes'); ?>><?php _e('Yes', 'guest-post-plugin'); ?></option>
                                            <option value="no" <?php selected($options['require_moderation'], 'no'); ?>><?php _e('No', 'guest-post-plugin'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="enable_ip_limit"><?php _e('Enable IP Submission Limit', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Enable or disable limiting submissions per IP address', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <select name="guest_post_plugin_options[enable_ip_limit]" id="enable_ip_limit">
                                            <option value="yes" <?php selected(isset($options['enable_ip_limit']) ? $options['enable_ip_limit'] : 'yes', 'yes'); ?>><?php _e('Yes', 'guest-post-plugin'); ?></option>
                                            <option value="no" <?php selected(isset($options['enable_ip_limit']) ? $options['enable_ip_limit'] : 'yes', 'no'); ?>><?php _e('No', 'guest-post-plugin'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="ip-limit-setting">
                                    <th scope="row">
                                        <label for="limit_submissions"><?php _e('Limit Submissions per IP', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Maximum number of submissions per IP address per day. Set to 0 for unlimited', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <input type="number" name="guest_post_plugin_options[limit_submissions]" id="limit_submissions" value="<?php echo esc_attr($options['limit_submissions']); ?>" min="0" step="1">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Newsletter Integration Section -->
                    <div class="bg-white border border-gray-200 shadow-sm mb-5 rounded w-full">
                        <h3 class="border-b border-gray-200 m-0 py-4 px-5 text-base font-semibold bg-gray-50 cursor-pointer relative break-words" tabindex="0" role="button" aria-expanded="false"><?php _e('Newsletter Integration', 'guest-post-plugin'); ?></h3>
                        <div class="settings-section-content p-4">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="enable_mailchimp"><?php _e('Enable Mailchimp', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Enable Mailchimp integration to add submitters to your newsletter', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <select name="guest_post_plugin_options[enable_mailchimp]" id="enable_mailchimp">
                                            <option value="yes" <?php selected($options['enable_mailchimp'], 'yes'); ?>><?php _e('Yes', 'guest-post-plugin'); ?></option>
                                            <option value="no" <?php selected($options['enable_mailchimp'], 'no'); ?>><?php _e('No', 'guest-post-plugin'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr class="mailchimp-setting">
                                    <th scope="row">
                                        <label for="mailchimp_api_key"><?php _e('Mailchimp API Key', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Enter your Mailchimp API key', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <input type="text" name="guest_post_plugin_options[mailchimp_api_key]" id="mailchimp_api_key" value="<?php echo esc_attr($options['mailchimp_api_key']); ?>" class="regular-text">
                                        <p class="description"><?php _e('Get your API key from your Mailchimp account under Account > Extras > API Keys', 'guest-post-plugin'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr class="mailchimp-setting">
                                    <th scope="row">
                                        <label for="mailchimp_list_id"><?php _e('Mailchimp List ID', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Enter your Mailchimp audience/list ID', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <input type="text" name="guest_post_plugin_options[mailchimp_list_id]" id="mailchimp_list_id" value="<?php echo esc_attr($options['mailchimp_list_id']); ?>" class="regular-text">
                                        <p class="description"><?php _e('Find your List ID in Mailchimp under Audience > Settings > Audience name and defaults', 'guest-post-plugin'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr class="mailchimp-setting">
                                    <th scope="row">
                                        <label for="newsletter_checkbox_label"><?php _e('Checkbox Label', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Text displayed next to the newsletter subscription checkbox', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <input type="text" name="guest_post_plugin_options[newsletter_checkbox_label]" id="newsletter_checkbox_label" value="<?php echo esc_attr($options['newsletter_checkbox_label']); ?>" class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr class="mailchimp-setting">
                                    <th scope="row">
                                        <label for="newsletter_checkbox_default"><?php _e('Checkbox Default State', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Should the newsletter subscription checkbox be checked by default?', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <select name="guest_post_plugin_options[newsletter_checkbox_default]" id="newsletter_checkbox_default">
                                            <option value="yes" <?php selected($options['newsletter_checkbox_default'], 'yes'); ?>><?php _e('Checked', 'guest-post-plugin'); ?></option>
                                            <option value="no" <?php selected($options['newsletter_checkbox_default'], 'no'); ?>><?php _e('Unchecked', 'guest-post-plugin'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                        
                    <!-- Spam Protection Section -->
                    <div class="bg-white border border-gray-200 shadow-sm mb-5 rounded w-full">
                        <h3 class="border-b border-gray-200 m-0 py-4 px-5 text-base font-semibold bg-gray-50 cursor-pointer relative break-words" tabindex="0" role="button" aria-expanded="false"><?php _e('Spam Protection', 'guest-post-plugin'); ?></h3>
                        <div class="settings-section-content p-4">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="enable_recaptcha"><?php _e('Enable reCAPTCHA', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Enable Google reCAPTCHA to protect against spam submissions', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <select name="guest_post_plugin_options[enable_recaptcha]" id="enable_recaptcha">
                                            <option value="yes" <?php selected($options['enable_recaptcha'], 'yes'); ?>><?php _e('Yes', 'guest-post-plugin'); ?></option>
                                            <option value="no" <?php selected($options['enable_recaptcha'], 'no'); ?>><?php _e('No', 'guest-post-plugin'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr class="recaptcha-setting">
                                    <th scope="row">
                                        <label for="recaptcha_site_key"><?php _e('reCAPTCHA Site Key', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Enter your Google reCAPTCHA v2 site key', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <input type="text" name="guest_post_plugin_options[recaptcha_site_key]" id="recaptcha_site_key" value="<?php echo esc_attr($options['recaptcha_site_key']); ?>" class="regular-text">
                                        <p class="description"><?php _e('Get your keys at <a href="https://www.google.com/recaptcha/admin" target="_blank">https://www.google.com/recaptcha/admin</a>', 'guest-post-plugin'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr class="recaptcha-setting">
                                    <th scope="row">
                                        <label for="recaptcha_secret_key"><?php _e('reCAPTCHA Secret Key', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Enter your Google reCAPTCHA v2 secret key', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <input type="text" name="guest_post_plugin_options[recaptcha_secret_key]" id="recaptcha_secret_key" value="<?php echo esc_attr($options['recaptcha_secret_key']); ?>" class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="enable_honeypot"><?php _e('Enable Honeypot', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Add a hidden field to catch spam bots', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <select name="guest_post_plugin_options[enable_honeypot]" id="enable_honeypot">
                                            <option value="yes" <?php selected($options['enable_honeypot'], 'yes'); ?>><?php _e('Yes', 'guest-post-plugin'); ?></option>
                                            <option value="no" <?php selected($options['enable_honeypot'], 'no'); ?>><?php _e('No', 'guest-post-plugin'); ?></option>
                                        </select>
                                        <p class="description"><?php _e('Honeypot is a simple anti-spam technique that adds an invisible field that only bots will fill out', 'guest-post-plugin'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Blocklist Section -->
                    <div class="bg-white border border-gray-200 shadow-sm mb-5 rounded w-full">
                        <h3 class="border-b border-gray-200 m-0 py-4 px-5 text-base font-semibold bg-gray-50 cursor-pointer relative break-words" tabindex="0" role="button" aria-expanded="false"><?php _e('Content Filtering', 'guest-post-plugin'); ?></h3>
                        <div class="settings-section-content p-4">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="blocklist_domains"><?php _e('Blocklisted Email Domains', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Comma-separated list of email domains to block', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <textarea name="guest_post_plugin_options[blocklist_domains]" id="blocklist_domains" rows="3" cols="50" class="large-text"><?php echo esc_textarea($options['blocklist_domains']); ?></textarea>
                                        <p class="description"><?php _e('Example: spam.com, temp-mail.org (comma-separated)', 'guest-post-plugin'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="blocklist_keywords"><?php _e('Blocklisted Keywords', 'guest-post-plugin'); ?></label>
                                        <span class="tooltip" title="<?php _e('Comma-separated list of keywords to block in post content', 'guest-post-plugin'); ?>">?</span>
                                    </th>
                                    <td>
                                        <textarea name="guest_post_plugin_options[blocklist_keywords]" id="blocklist_keywords" rows="3" cols="50" class="large-text"><?php echo esc_textarea($options['blocklist_keywords']); ?></textarea>
                                        <p class="description"><?php _e('Example: casino, viagra, loan (comma-separated)', 'guest-post-plugin'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                        
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if ($active_tab == 'notification') : ?>
                <div id="notification-settings" class="settings-tab">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="send_admin_notification"><?php _e('Send Admin Notifications', 'guest-post-plugin'); ?></label>
                                <span class="tooltip" title="<?php _e('Send email notifications to admin when new guest posts are submitted', 'guest-post-plugin'); ?>">?</span>
                            </th>
                            <td>
                                <select name="guest_post_plugin_options[send_admin_notification]" id="send_admin_notification">
                                    <option value="yes" <?php selected($options['send_admin_notification'], 'yes'); ?>><?php _e('Yes', 'guest-post-plugin'); ?></option>
                                    <option value="no" <?php selected($options['send_admin_notification'], 'no'); ?>><?php _e('No', 'guest-post-plugin'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="admin_email"><?php _e('Notification Email', 'guest-post-plugin'); ?></label>
                                <span class="tooltip" title="<?php _e('Email address to receive notifications. Leave blank to use admin email', 'guest-post-plugin'); ?>">?</span>
                            </th>
                            <td>
                                <input type="email" name="guest_post_plugin_options[admin_email]" id="admin_email" value="<?php echo esc_attr($options['admin_email']); ?>" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="approve_email_template"><?php _e('Approval Email Template', 'guest-post-plugin'); ?></label>
                                <span class="tooltip" title="<?php _e('Email template sent to authors when their post is approved. Available variables: {author_name}, {post_title}', 'guest-post-plugin'); ?>">?</span>
                            </th>
                            <td>
                                <textarea name="guest_post_plugin_options[approve_email_template]" id="approve_email_template" rows="5" cols="50" class="large-text"><?php echo esc_textarea($options['approve_email_template']); ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="reject_email_template"><?php _e('Rejection Email Template', 'guest-post-plugin'); ?></label>
                                <span class="tooltip" title="<?php _e('Email template sent to authors when their post is rejected. Available variables: {author_name}, {post_title}', 'guest-post-plugin'); ?>">?</span>
                            </th>
                            <td>
                                <textarea name="guest_post_plugin_options[reject_email_template]" id="reject_email_template" rows="5" cols="50" class="large-text"><?php echo esc_textarea($options['reject_email_template']); ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="send_autoreply"><?php _e('Send Auto-Reply', 'guest-post-plugin'); ?></label>
                                <span class="tooltip" title="<?php _e('Send an automatic confirmation email to users when they submit a guest post', 'guest-post-plugin'); ?>">?</span>
                            </th>
                            <td>
                                <select name="guest_post_plugin_options[send_autoreply]" id="send_autoreply">
                                    <option value="yes" <?php selected($options['send_autoreply'], 'yes'); ?>><?php _e('Yes', 'guest-post-plugin'); ?></option>
                                    <option value="no" <?php selected($options['send_autoreply'], 'no'); ?>><?php _e('No', 'guest-post-plugin'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="autoreply_email_template"><?php _e('Auto-Reply Email Template', 'guest-post-plugin'); ?></label>
                                <span class="tooltip" title="<?php _e('Email template sent to authors when they submit a post. Available variables: {author_name}, {post_title}, {site_name}', 'guest-post-plugin'); ?>">?</span>
                            </th>
                            <td>
                                <textarea name="guest_post_plugin_options[autoreply_email_template]" id="autoreply_email_template" rows="5" cols="50" class="large-text"><?php echo esc_textarea($options['autoreply_email_template']); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if ($active_tab == 'style') : ?>
                <div id="style-settings" class="settings-tab">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label><?php _e('Form Theme', 'guest-post-plugin'); ?></label>
                                <span class="tooltip" title="<?php _e('Choose between light and dark theme for the submission form', 'guest-post-plugin'); ?>">?</span>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="guest_post_plugin_options[form_theme]" value="light" <?php checked($options['form_theme'], 'light'); ?> class="theme-selector" data-theme="light">
                                        <?php _e('Light', 'guest-post-plugin'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="radio" name="guest_post_plugin_options[form_theme]" value="dark" <?php checked($options['form_theme'], 'dark'); ?> class="theme-selector" data-theme="dark">
                                        <?php _e('Dark', 'guest-post-plugin'); ?>
                                    </label>
                                </fieldset>
                                
                                <div id="form-preview-light" class="form-preview" style="<?php echo $options['form_theme'] === 'light' ? 'display:block;' : 'display:none;'; ?> background-color: #f9f9f9; color: #333; border: 1px solid #ddd; padding: 15px; margin-top: 15px; border-radius: 4px;">
                                    <p style="font-weight: bold; margin-bottom: 10px;"><?php _e('Form Preview (Light)', 'guest-post-plugin'); ?></p>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px;"><?php _e('Sample Field', 'guest-post-plugin'); ?></label>
                                        <input type="text" disabled value="<?php _e('Sample input', 'guest-post-plugin'); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background-color: #fff; color: #333;">
                                    </div>
                                </div>
                                
                                <div id="form-preview-dark" class="form-preview" style="<?php echo $options['form_theme'] === 'dark' ? 'display:block;' : 'display:none;'; ?> background-color: #333; color: #f9f9f9; border: 1px solid #555; padding: 15px; margin-top: 15px; border-radius: 4px;">
                                    <p style="font-weight: bold; margin-bottom: 10px;"><?php _e('Form Preview (Dark)', 'guest-post-plugin'); ?></p>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px;"><?php _e('Sample Field', 'guest-post-plugin'); ?></label>
                                        <input type="text" disabled value="<?php _e('Sample input', 'guest-post-plugin'); ?>" style="width: 100%; padding: 8px; border: 1px solid #555; border-radius: 4px; background-color: #444; color: #f9f9f9;">
                                    </div>
                                </div>
                                
                                <script>
                                jQuery(document).ready(function($) {
                                    $('.theme-selector').on('change', function() {
                                        var theme = $(this).data('theme');
                                        $('.form-preview').hide();
                                        $('#form-preview-' + theme).show();
                                    });
                                });
                                </script>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Enqueue scripts and styles
 */
function guest_post_plugin_scripts() {
    $options = get_option('guest_post_plugin_options');
    $theme = isset($options['form_theme']) ? $options['form_theme'] : 'light';
    
    wp_enqueue_style('guest-post-plugin-style', plugin_dir_url(__FILE__) . 'css/style.css', array(), GUEST_POST_PLUGIN_VERSION);
    
    if ($theme === 'dark') {
        wp_enqueue_style('guest-post-plugin-dark', plugin_dir_url(__FILE__) . 'css/dark-theme.css', array('guest-post-plugin-style'), GUEST_POST_PLUGIN_VERSION);
        wp_enqueue_style('guest-post-plugin-dark-editor', plugin_dir_url(__FILE__) . 'css/dark-editor.css', array('guest-post-plugin-dark'), GUEST_POST_PLUGIN_VERSION);
    }
    
    // Load WordPress editor scripts
    wp_enqueue_editor();
    
    // Load reCAPTCHA if enabled
    if ($options['enable_recaptcha'] === 'yes' && !empty($options['recaptcha_site_key'])) {
        wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true);
    }
    
    wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.2.0', true);
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.2.0', true);
    wp_enqueue_script('guest-post-plugin-frontend', plugin_dir_url(__FILE__) . 'js/frontend.js', array('react', 'react-dom'), GUEST_POST_PLUGIN_VERSION, true);
    
    wp_localize_script('guest-post-plugin-frontend', 'guest_post_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('guest_post_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'guest_post_plugin_scripts');

/**
 * Add dashboard widget
 */
function guest_post_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'guest_post_dashboard_widget',
        'Recent Guest Post Submissions',
        'guest_post_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'guest_post_add_dashboard_widget');

/**
 * Dashboard widget content
 */
function guest_post_dashboard_widget_content() {
    $args = array(
        'post_type'      => 'post',
        'post_status'    => array('pending', 'draft', 'publish'),
        'meta_key'       => '_is_guest_post',
        'meta_value'     => true,
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC'
    );
    
    $guest_posts = new WP_Query($args);
    
    echo '<div class="guest-post-dashboard-widget">';
    
    if ($guest_posts->have_posts()) {
        while ($guest_posts->have_posts()) {
            $guest_posts->the_post();
            $post_id = get_the_ID();
            $author_name = get_post_meta($post_id, '_guest_author_name', true);
            $author_email = get_post_meta($post_id, '_guest_author_email', true);
            $status = get_post_status();
            $status_label = '';
            
            switch ($status) {
                case 'pending':
                    $status_label = '<span style="color:#f56e28;">Pending</span>';
                    break;
                case 'draft':
                    $status_label = '<span style="color:#82878c;">Draft</span>';
                    break;
                case 'publish':
                    $status_label = '<span style="color:#46b450;">Published</span>';
                    break;
            }
            
            echo '<div class="post-item">';
            echo '<div class="post-title">' . get_the_title() . ' - ' . $status_label . '</div>';
            echo '<div class="post-meta">By ' . esc_html($author_name) . ' (' . esc_html($author_email) . ')<br>';
            echo 'Submitted on ' . get_the_date() . ' at ' . get_the_time() . '</div>';
            echo '<div class="post-actions">';
            echo '<a href="' . get_edit_post_link($post_id) . '">Edit</a>';
            
            if ($status === 'pending') {
                echo '<a href="' . admin_url('post.php?post=' . $post_id . '&action=edit') . '">Review</a>';
            }
            
            echo '<a href="' . get_permalink($post_id) . '">View</a>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p class="no-posts">No guest post submissions found.</p>';
    }
    
    echo '<p><a href="' . admin_url('edit.php?post_type=post&meta_key=_is_guest_post&meta_value=1') . '">View all guest posts</a></p>';
    echo '</div>';
    
    wp_reset_postdata();
}