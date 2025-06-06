# Guest Post Plugin

A WordPress plugin for guest post submissions with front-end form, draft creation, and quick approval links.

## Features

- Front-end submission form using React and Force UI components
- Admin notification emails
- Auto-reply emails to submitters
- Approval/rejection links in emails
- Spam protection with honeypot and reCAPTCHA
- Newsletter integration with Mailchimp
- Light and dark theme support
- IP submission limiting
- Content filtering with blocklists

## Installation

1. Upload the plugin files to the `/wp-content/plugins/guest-post-plugin` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the plugin settings under 'Settings > Guest Post Plugin'

## Usage

Add the shortcode `[guest_post_form]` to any page or post where you want the submission form to appear.

### Configuration Options

#### General Settings
- **Default Category**: Choose which category guest posts will be assigned to
- **Require Moderation**: Set whether posts require approval before publishing
- **Enable IP Submission Limit**: Control whether to limit submissions per IP address
- **Limit Submissions per IP**: Set maximum number of submissions per IP address per day

#### Notification Settings
- **Send Admin Notifications**: Enable/disable email notifications for new submissions
- **Notification Email**: Set the email address to receive notifications
- **Email Templates**: Customize templates for approval, rejection, and auto-reply emails

#### Form Style
- **Form Theme**: Choose between light and dark themes for the submission form

#### Spam Protection
- **Enable reCAPTCHA**: Add Google reCAPTCHA to protect against spam
- **Enable Honeypot**: Add invisible honeypot field to catch bots
- **Blocklisted Domains**: Block submissions from specific email domains
- **Blocklisted Keywords**: Block submissions containing specific keywords

#### Newsletter Integration
- **Enable Mailchimp**: Connect to Mailchimp for newsletter subscriptions
- **Newsletter Checkbox**: Customize label and default state

## Development

### Prerequisites

- Node.js and npm
- Composer (for PHP dependencies)
- WordPress development environment

### Setup

1. Clone the repository
2. Install dependencies:
   ```
   npm install
   composer install
   ```

### Build

```
npm run build
```

### Watch for changes

```
npm run watch
```

## Testing

### Unit Tests

To run unit tests:

```
npm run test:unit
```

This requires a WordPress test environment. Set the `WP_TESTS_DIR` environment variable to your WordPress tests directory.

### End-to-End Tests

To run E2E tests:

```
npm run test:e2e
```

To open Cypress test runner:

```
npm run cypress:open
```

## Documentation

- [Architecture](ARCHITECTURE.md) - Technical architecture and code structure
- [PRD](PRD.md) - Product Requirements Document

## Support

For support, please open an issue on the GitHub repository.

## License

GPL-2.0+