# Guest Post Plugin Architecture

This document outlines the technical architecture and code structure of the Guest Post Plugin.

## Overview

The Guest Post Plugin is built using a combination of PHP for server-side processing and React with Force UI components for the front-end interface. The plugin follows WordPress coding standards and best practices for plugin development.

## Directory Structure

```
guest-post-plugin/
├── css/                      # Compiled CSS files
│   ├── admin.css             # Admin interface styles
│   ├── dark-editor.css       # Dark mode editor styles
│   ├── dark-theme.css        # Dark theme styles
│   └── style.css             # Main plugin styles
├── includes/                 # PHP includes
│   └── email-functions.php   # Email handling functions
├── js/                       # Compiled JavaScript files
│   ├── admin.js              # Admin interface scripts
│   └── frontend.js           # Front-end scripts
├── src/                      # Source files
│   ├── css/                  # CSS source files
│   │   └── input.css         # TailwindCSS input file
│   └── js/                   # JavaScript source files
│       ├── admin.js          # Admin interface React code
│       ├── frontend.js       # Front-end React code
│       └── components/       # React components
│           ├── GuestPostForm.jsx    # Main form component
│           ├── SettingsSection.jsx  # Settings section component
│           └── Tooltip.jsx          # Tooltip component
├── tests/                    # Test files
│   ├── cypress/              # Cypress E2E tests
│   │   ├── fixtures/         # Test fixtures
│   │   ├── integration/      # Test specs
│   │   ├── plugins/          # Cypress plugins
│   │   └── support/          # Support files
│   └── phpunit/              # PHPUnit tests
│       ├── bootstrap.php     # Test bootstrap
│       └── test-*.php        # Test files
├── .gitignore                # Git ignore file
├── cypress.json              # Cypress configuration
├── guest-post-plugin.php     # Main plugin file
├── package.json              # npm package configuration
├── phpunit.xml               # PHPUnit configuration
├── README.md                 # Plugin documentation
├── ARCHITECTURE.md           # This file
└── PRD.md                    # Product Requirements Document
```

## Core Components

### 1. Main Plugin File (`guest-post-plugin.php`)

The entry point for the plugin that:
- Defines plugin metadata
- Registers activation/deactivation hooks
- Loads required files
- Registers shortcodes
- Sets up admin menus and settings
- Handles AJAX form submissions

### 2. Email Functions (`includes/email-functions.php`)

Contains functions for:
- Sending admin notifications
- Sending auto-reply emails to submitters
- Email template processing

### 3. Front-end Form (`src/js/components/GuestPostForm.jsx`)

React component that:
- Renders the submission form
- Handles form validation
- Processes form submissions via AJAX
- Displays success/error messages
- Adapts to light/dark themes

### 4. Admin Interface (`src/js/admin.js`)

React-based admin interface that:
- Renders settings sections
- Handles settings form submissions
- Provides tooltips and help text
- Manages conditional form fields

## Data Flow

1. **Form Submission Flow**:
   - User fills out the front-end form
   - React component validates input
   - Form data is sent via AJAX to WordPress
   - Server validates the submission
   - Post is created as pending/published based on settings
   - Notification emails are sent
   - Success/error response is returned to the form

2. **Admin Settings Flow**:
   - Admin configures plugin settings
   - Settings are saved to WordPress options table
   - Settings affect form behavior and email sending

3. **Approval/Rejection Flow**:
   - Admin receives email with approval/rejection links
   - Clicking a link triggers the corresponding action
   - Post status is updated
   - Author is notified of the decision

## Technologies Used

- **PHP**: Server-side processing
- **React**: Front-end UI components
- **Force UI**: UI component library
- **TailwindCSS**: Utility-first CSS framework
- **Webpack**: JavaScript bundling
- **PHPUnit**: Unit testing
- **Cypress**: End-to-end testing

## Design Patterns

1. **Separation of Concerns**:
   - Front-end code is separated from back-end logic
   - Email functions are isolated in their own file
   - React components are modular and reusable

2. **WordPress Hooks**:
   - Uses actions and filters for extensibility
   - Follows WordPress plugin API best practices

3. **Component-Based Architecture**:
   - UI is built with reusable React components
   - Components are organized by functionality

## Security Measures

1. **Input Validation**:
   - All user input is validated and sanitized
   - WordPress nonces are used for form submissions
   - AJAX requests verify user capabilities

2. **Spam Protection**:
   - Honeypot fields to catch bots
   - Optional reCAPTCHA integration
   - IP submission limiting
   - Content filtering with blocklists

## Performance Considerations

1. **Asset Loading**:
   - CSS and JavaScript are minified for production
   - Assets are only loaded when needed

2. **Database Efficiency**:
   - Minimal database queries
   - Uses WordPress transients for caching

## Extensibility

The plugin is designed to be extensible through:
- WordPress actions and filters
- Modular component architecture
- Clear separation of concerns