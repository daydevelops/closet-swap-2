before(() => {
    // login
    // seed some users
    // seed items for those users
});

describe('Testing Closet Management', () => {
    it('allows a user to see their default feed', () => {
        // when I visit the home page
        // then I should see a feed of items from all users
    });

    it('allows a user to filter their feed', () => {
        // when I visit the home page
        // and I filter by category
        // then I should see a feed of items only in that category
    });

    it('allows a user to search their feed', () => {
        // when I visit the home page
        // and I search for a specific item by title
        // then I should see only items that match my search
    });

    it('allows a user to browse their liked items', () => {
        // given I have liked some items
        // when I visit the home page
        // and I click on the liked items tab
        // then I should see a feed of items that I have liked
    });

    it('allows a user to browse only items from friends', () => {
        // given some users are my friends
        // when I visit the home page
        // and select the friends only toggle
        // then I should see a feed of items only from my friends
    });

    it('does not show items from a blocked user', () => {
        // given some users are blocked
        // when I visit the home page
        // then I should not see items from blocked users
    });

    it('allows a user to like an item', () => {
        // when I visit the home page
        // and I click on the like button for an item
        // then the item should be added to my liked items
    });

    it('allows a user to start a chat for an item', () => {
        // if I visit the home page
        // and I click on an item
        // then I should see a chat button
        // clicking the button should direct me to a chat with the item owner
    });

});
