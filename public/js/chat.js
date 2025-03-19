let currentChatId = null;
let chatConfig = {};

function loadChat(userId, userName) {
    document.querySelector('.chat-list').classList.remove('active');
    currentChatId = userId;
    $('#no-chat-selected').addClass('d-none');
    $('#chat-area').removeClass('d-none');
    $('#contact-name').text(userName);

    fetchMessages();
    startMessagePolling();
    updateUserStatus(userId);
}

function fetchMessages() {
    if (!currentChatId) return;

    $.get(`/chat/messages/${currentChatId}`, function(messages) {
        renderMessages(messages);
        scrollToBottom();
    });
}

function renderMessages(messages) {
    const container = $('#messages-container');
    container.empty();

    let currentDate = null;

    messages.forEach(message => {
        const messageDate = new Date(message.created_at).toLocaleDateString();

        if (messageDate !== currentDate) {
            container.append(`
                <div class="message-date-divider">
                    <span>${messageDate}</span>
                </div>
            `);
            currentDate = messageDate;
        }

        const isOwn = message.sender_id === chatConfig.currentUserId;
        const time = new Date(message.created_at).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        container.append(`
            <div class="message ${isOwn ? 'message-own' : 'message-other'}">
                <div class="message-content">
                    <p>${message.content}</p>
                    <div class="message-meta">
                        <span class="message-time">${time}</span>
                        ${isOwn ? `
                            <span class="message-status">
                                ${message.is_seen ?
                                    '<i class="fas fa-check-double seen"></i>' :
                                    '<i class="fas fa-check"></i>'}
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>
        `);
    });
}

function startMessagePolling() {
    stopMessagePolling();
    window.messagePolling = setInterval(fetchMessages, 3000);
}

function stopMessagePolling() {
    if (window.messagePolling) {
        clearInterval(window.messagePolling);
    }
}

function updateUserStatus(userId) {
    function checkStatus() {
        $.get(`/status/user/${userId}`, function(response) {
            const statusText = response.is_online ? 'Online' : 'Last seen ' +
                new Date(response.last_seen_at).toLocaleString();
            $('#contact-status').text(statusText);
        });
    }

    checkStatus();
    window.statusInterval = setInterval(checkStatus, 30000);
}

function scrollToBottom() {
    const container = $('#messages-container');
    container.scrollTop(container[0].scrollHeight);
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize chat configuration from data attribute
    const chatContainer = document.querySelector('.chat-container');
    if (chatContainer) {
        try {
            chatConfig = JSON.parse(chatContainer.getAttribute('data-chat-config'));
        } catch (e) {
            console.error('Error parsing chat configuration:', e);
        }
    }

    // Setup form submission
    $('#message-form').on('submit', function(e) {
        e.preventDefault();

        const input = $('#message-input');
        const content = input.val().trim();

        if (!content || !currentChatId) return;

        $.post('/chat/send', {
            receiver_id: currentChatId,
            content: content,
            _token: chatConfig.csrfToken
        }, function() {
            input.val('');
            fetchMessages();
        });
    });

    // Add click event listener for chat items
    document.querySelectorAll('.chat-item').forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            loadChat(userId, userName);
        });
    });

    // Toggle chat list on mobile
    document.querySelector('.chat-sidebar-header').addEventListener('click', function() {
        document.querySelector('.chat-list').classList.toggle('active');
    });

    // Auto-load chat with manager if available
    if (chatConfig.managerData) {
        loadChat(chatConfig.managerData.id, chatConfig.managerData.name);
    }
});
