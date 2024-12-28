
describe('Testing Profile Updates', () => {

    it('denies an unauthenticated user from seeing the profile page', () => {
        // if I log out
        // and I go to my profile page
        // I should see a login form
    });

    it('allows a user to see their profile page', () => {
        // given I am authenticated
        // if I go to my profile page
        // I should see my profile information
    });

    it('allows a user to update their profile information', () => {
        // given I am authenticated
        // if I go to my profile page
        // and I click on the edit button
        // then I should see a form to update my profile information
        // if I change the details
        // and I click submit
        // then my profile information should be updated
    });

    it('allows a user to change their password', () => {
        // given I am authenticated
        // if I go to my profile page
        // and I click on the change password button
        // then I should see a form to change my password
        // if I fill out the form
        // and I click submit
        // then my password should be updated
    });
});
