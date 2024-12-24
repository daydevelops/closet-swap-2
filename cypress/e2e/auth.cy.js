import { faker } from '@faker-js/faker';

describe('Testing Authentication', () => {
    it('tests a user can register', () => {
        cy.visit(Cypress.env('APP_URL') + '/register');
        fillRegisterForm(
            Date.now() + faker.internet.username(),
            (Date.now() + faker.internet.email()).toLowerCase(),
            'password',
            'password'
        );
        cy.url().should('include', '/dashboard');
    })

    it('should not let a user register with an existing email', function () {
        cy.visit(Cypress.env('APP_URL') + '/register');
        let email = (Date.now() + faker.internet.email()).toLowerCase()
        fillRegisterForm(
            Date.now() + faker.internet.username(),
            email,
            'password',
            'password'
        );
        cy.url().should('include', '/dashboard');

        cy.get('[data-cy=user_dropdown]').click();
        cy.get('[data-cy=logout_btn]').click();

        cy.visit(Cypress.env('APP_URL') + '/register');
        fillRegisterForm(
            Date.now() + faker.internet.username(),
            email,
            'password',
            'password'
        )
        cy.url().should('not.include', '/dashboard');
    });

    it('should not let a user register with an invalid email', function () {
        cy.visit(Cypress.env('APP_URL') + '/register');
        fillRegisterForm(
            Date.now() + faker.internet.username(),
            'invalid email',
            'password',
            'password'
        )
        cy.get('[data-cy=submit]').click();
        cy.url().should('not.include', '/dashboard');
    });

    it('should not let a user register with a password confirmation that does not match the password', function () {
        cy.visit(Cypress.env('APP_URL') + '/register');
        fillRegisterForm(
            Date.now() + faker.internet.username(),
            (Date.now() + faker.internet.email()).toLowerCase(),
            'password',
            'password1'
        );
        cy.url().should('not.include', '/dashboard');
    });

})

function fillRegisterForm(name, email, password, password_confirmation) {
    cy.get('[data-cy=name]').type(name);
    cy.get('[data-cy=email]').type(email);
    cy.get('[data-cy=password]').type(password);
    cy.get('[data-cy=password_confirmation]').type(password_confirmation);
    cy.get('[data-cy=submit]').click();
}
