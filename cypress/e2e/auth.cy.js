describe('Testing Authentication', () => {
  it('a user can register', () => {
    cy.visit(Cypress.env('APP_URL') + '/register');
    cy.get('[data-cy=name]').type('Jane Doe');
    cy.get('[data-cy=email]').type('test4@test.com');
    cy.get('[data-cy=password]').type('password');
    cy.get('[data-cy=password_confirmation]').type('password');
    cy.get('[data-cy=submit]').click();
  })
})
