describe('Admin Settings', () => {
  before(() => {
    // Login to WordPress admin
    cy.login();
    
    // Navigate to plugin settings
    cy.visit('/wp-admin/options-general.php?page=guest-post-plugin');
  });

  it('should display settings tabs', () => {
    // Check if tabs exist
    cy.get('.nav-tab-wrapper').should('exist');
    cy.get('.nav-tab').should('have.length.at.least', 3);
    cy.contains('.nav-tab', 'General Settings').should('exist');
    cy.contains('.nav-tab', 'Notification Settings').should('exist');
    cy.contains('.nav-tab', 'Form Style').should('exist');
  });

  it('should save general settings', () => {
    // Click on General Settings tab if not active
    cy.contains('.nav-tab', 'General Settings').click();
    
    // Change a setting
    cy.get('#require_moderation').select('No');
    
    // Save settings
    cy.get('input[type="submit"]').click();
    
    // Check for success message
    cy.contains('Settings saved').should('exist');
    
    // Verify setting was saved
    cy.get('#require_moderation').should('have.value', 'no');
  });

  it('should save notification settings', () => {
    // Click on Notification Settings tab
    cy.contains('.nav-tab', 'Notification Settings').click();
    
    // Change a setting
    cy.get('#admin_email').clear().type('newemail@example.com');
    
    // Save settings
    cy.get('input[type="submit"]').click();
    
    // Check for success message
    cy.contains('Settings saved').should('exist');
    
    // Verify setting was saved
    cy.get('#admin_email').should('have.value', 'newemail@example.com');
  });

  it('should save form style settings', () => {
    // Click on Form Style tab
    cy.contains('.nav-tab', 'Form Style').click();
    
    // Change theme to dark
    cy.get('input[name="guest_post_plugin_options[form_theme]"][value="dark"]').check();
    
    // Save settings
    cy.get('input[type="submit"]').click();
    
    // Check for success message
    cy.contains('Settings saved').should('exist');
    
    // Verify setting was saved
    cy.get('input[name="guest_post_plugin_options[form_theme]"][value="dark"]').should('be.checked');
  });
});