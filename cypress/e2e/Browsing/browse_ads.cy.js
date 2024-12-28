before(() => {
    // login
    // seed some users
    // seed ads for those users
});

describe('Testing Wanted Ad Management', () => {
    it('allows a user to see their default feed', () => {
        // when I visit the home page
        // then I should see a feed of ads from all users
    });

    it('allows a user to search their feed', () => {
        // given I am an authenticated user
        // and some other users exist with ads
        // when I visit the home page
        // and I search for a specific ads by title
        // then I should see only ads that match my search
    });

    it('allows a user to browse only ads from friends', () => {
        // given some users are my friends
        // when I visit the home page
        // and select the friends only toggle
        // then I should see a feed of ads only from my friends
    });

    it('does not show ads from a blocked user', () => {
        // given some users are blocked
        // when I visit the home page
        // then I should not see ads from blocked users
    });

    it('allows a user to start a chat for an ad', () => {
        // if I visit the home page
        // and I click on an ads
        // then I should see a chat button
        // clicking the button should direct me to a chat with the ads owner
    });
});
