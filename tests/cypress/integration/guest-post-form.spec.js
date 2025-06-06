describe('Guest Post Form', () => {
  before(() => {
    // Visit a page with the guest post form shortcode
    cy.visit('/guest-post-form/');
  });

  it('should display the form correctly', () => {
    // Check if form elements exist
    cy.get('#title').should('exist');
    cy.get('#wp-editor-container').should('exist');
    cy.get('#authorName').should('exist');
    cy.get('#authorEmail').should('exist');
    cy.get('#authorBio').should('exist');
  });

  it('should validate required fields', () => {
    // Try to submit without filling required fields
    cy.get('button[type="submit"]').click();
    
    // Check for validation messages
    cy.get('#title:invalid').should('exist');
    cy.get('#authorName:invalid').should('exist');
    cy.get('#authorEmail:invalid').should('exist');
    cy.get('#authorBio:invalid').should('exist');
  });

  it('should submit the form successfully', () => {
    // Fill out the form
    cy.get('#title').type('Test Guest Post');
    
    // For TinyMCE editor, we need to use its iframe
    cy.get('iframe').then($iframe => {
      const iframe = $iframe.contents();
      const body = iframe.find('body');
      cy.wrap(body).type('This is a test post content.');
    });
    
    cy.get('#authorName').type('Test Author');
    cy.get('#authorEmail').type('test@example.com');
    cy.get('#authorBio').type('This is a test author bio.');
    
    // Submit the form
    cy.get('button[type="submit"]').click();
    
    // Check for success message
    cy.get('#form-response').should('contain', 'Your guest post has been submitted successfully');
  });
});