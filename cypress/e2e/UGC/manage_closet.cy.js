before(() => {
    // login
});

describe('Testing Closet Management', () => {
    it('allows a user to see their closet', () => {
        // given I have items in my closet
        // if I go to my profile page
        // then I should see a list of my items
    });

    it('allows a user to view their item', () => {
        // given I have an item in my closet
        // if I go to my profile page
        // and I click on the item
        // then I should see the item details
    });

    it('allows a user to create a new item', () => {
        // given I am on my profile page
        // when I click on the add item button
        // then I should see a form to add a new item
        // if I fill out all the details
        // and I click submit
        // then the item should be added to my closet
    });

    it('allows a user to edit their item', () => {
        // given I have an item in my closet
        // if I go to my profile page
        // and I click on the edit button for the item
        // then I should see a form to edit the item
        // if I change the details
        // and I click submit
        // then the item should be updated
    });

    it('allows a user to delete their item', () => {
        // given I have an item in my closet
        // if I go to my profile page
        // and I click on the delete button for the item
        // then the item should be removed from my closet
    });

    it('allows a user to see who has liked their item', () => {
        // given I have an item in my closet
        // and some users have liked the item
        // if I go to my profile page
        // and I click on the likes button for the item
        // then I should see a list of users who have liked the item
    });

});
