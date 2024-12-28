before(() => {
    // login
    // seed some users
    // seed conversations with those users
});

describe('Testing Chat Initiation', () => {
    it('allows a user to see their past conversations', () => {
        // if I visit the chats page
        // then I should see a list of conversations
        // my conversations should be sorted by most recent
    });

    it('allows a user to see their chat history with a user', () => {
        // if I visit the chats page
        // and I click on a conversation
        // then I should see a chat history with that user
    });

    it('allows a user to block a user', () => {
        // if I visit the chats page
        // and i look at a conversation
        // and I click on the block button for a user
        // then that user should be added to my blocked list
        // the user should not be visible in my conversations
    });

    it('allows a user to report a user', () => {
        // if I visit the chats page
        // and i look at a conversation
        // and I click on the report button for a user
        // then that user should be reported (a success message should be displayed)
        // the user should not be visible in my conversations
    });

    it('allows a user to send a message to a user', () => {
        // if I visit the chats page
        // and I click on a conversation
        // and I type a message
        // and I click send
        // then the message should be displayed in the chat history
    });
});
