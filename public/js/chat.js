// Sanitization utilities
const sanitizer = {
    DOMPurify: window.DOMPurify,
    sanitizeHTML: function(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },
    purify: function(html) {
        if (this.DOMPurify) {
            return this.DOMPurify.sanitize(html);
        }
        return this.sanitizeHTML(html);
    }
};

// Chat state
let currentChatId = null;
let chatConfig = {};
let messagePollingInterval = null;
let lastMessageTimestamp = null;

// DOM Elements
const elements = {
    chatList: document.querySelector('.chat-list'),
    noChat: document.querySelector('#no-chat-selected'),
    chatArea: document.querySelector('#chat-area'),
    contactName: document.querySelector('#contact-name'),
    messagesContainer: document.querySelector('#messages-container'),
    messageForm: document.querySelector('#message-form'),
    messageInput: document.querySelector('#message-input'),
};

// API endpoints
const API = {
    messages: (userId, timestamp) => `/chat/messages/${userId}${timestamp ? `?after=${timestamp}` : ''}`,
    send: '/chat/send'
};

function loadChat(userId, userName) {
    if (!userId || !userName) return;

    elements.chatList?.classList.remove('active');
    currentChatId = userId;
    lastMessageTimestamp = null;

    if (elements.noChat) elements.noChat.classList.add('d-none');
    if (elements.chatArea) elements.chatArea.classList.remove('d-none');
    if (elements.contactName) elements.contactName.textContent = sanitizer.sanitizeHTML(userName);

    fetchMessages(true);
    startMessagePolling();
}

function fetchMessages(isInitialLoad = false) {
    if (!currentChatId) return;

    const url = API.messages(currentChatId, isInitialLoad ? null : lastMessageTimestamp);

    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(messages => {
            if (Array.isArray(messages) && messages.length > 0) {
                if (isInitialLoad) {
                    renderMessages(messages);
                } else {
                    appendNewMessages(messages);
                }
                lastMessageTimestamp = new Date(messages[messages.length - 1].created_at).getTime();
                if (isInitialLoad) {
                    scrollToBottom();
                }
            }
        })
        .catch(error => {
            console.error('Error fetching messages:', error);
            showError('Failed to load messages. Please try again later.');
        });
}

function renderMessages(messages) {
    if (!elements.messagesContainer || !Array.isArray(messages)) return;

    let html = '';
    let currentDate = null;

    messages.forEach(message => {
        const messageDate = new Date(message.created_at).toLocaleDateString();

        if (messageDate !== currentDate) {
            html += `
                <div class="message-date-divider">
                    <span>${sanitizer.sanitizeHTML(messageDate)}</span>
                </div>
            `;
            currentDate = messageDate;
        }

        html += createMessageHTML(message);
    });

    elements.messagesContainer.innerHTML = sanitizer.purify(html);
}

function appendNewMessages(messages) {
    if (!elements.messagesContainer || !Array.isArray(messages) || messages.length === 0) return;

    const fragment = document.createDocumentFragment();
    let lastElement = elements.messagesContainer.lastElementChild;
    let currentDate = lastElement?.querySelector('.message-date-divider span')?.textContent;

    messages.forEach(message => {
        const messageDate = new Date(message.created_at).toLocaleDateString();

        if (messageDate !== currentDate) {
            const dateDiv = document.createElement('div');
            dateDiv.className = 'message-date-divider';
            dateDiv.innerHTML = sanitizer.purify(`<span>${sanitizer.sanitizeHTML(messageDate)}</span>`);
            fragment.appendChild(dateDiv);
            currentDate = messageDate;
        }

        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = sanitizer.purify(createMessageHTML(message));
        fragment.appendChild(messageDiv.firstChild);
    });

    elements.messagesContainer.appendChild(fragment);
    scrollToBottom();
}

function createMessageHTML(message) {
    const isOwn = message.sender_id === chatConfig.currentUserId;
    const time = new Date(message.created_at).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });

    return `
        <div class="message ${isOwn ? 'message-own' : 'message-other'}">
            <div class="message-content">
                <p>${sanitizer.sanitizeHTML(message.content)}</p>
                <div class="message-meta">
                    <span class="message-time">${sanitizer.sanitizeHTML(time)}</span>
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
    `;
}

function startMessagePolling() {
    stopMessagePolling();
    messagePollingInterval = setInterval(() => fetchMessages(false), 5000);
}

function stopMessagePolling() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
        messagePollingInterval = null;
    }
}

function scrollToBottom() {
    if (elements.messagesContainer) {
        elements.messagesContainer.scrollTop = elements.messagesContainer.scrollHeight;
    }
}

function showError(message) {
    console.error(message);
}

// Initialize chat
function initializeChat() {
    const chatContainer = document.querySelector('.chat-container');
    if (chatContainer) {
        try {
            const configStr = chatContainer.getAttribute('data-chat-config');
            if (!configStr) {
                throw new Error('Chat configuration not found');
            }
            chatConfig = JSON.parse(configStr);
            if (!chatConfig.currentUserId || !chatConfig.csrfToken) {
                throw new Error('Invalid chat configuration');
            }
        } catch (e) {
            console.error('Error parsing chat configuration:', e);
            showError('Failed to initialize chat. Please refresh the page.');
            return;
        }
    }

    // Setup form submission
    elements.messageForm?.addEventListener('submit', function(e) {
        e.preventDefault();

        const content = elements.messageInput?.value.trim();
        if (!content || !currentChatId || !chatConfig.csrfToken) return;

        const formData = new FormData();
        formData.append('receiver_id', currentChatId);
        formData.append('content', content);
        formData.append('_token', chatConfig.csrfToken);

        fetch(API.send, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to send message');
            if (elements.messageInput) elements.messageInput.value = '';
            fetchMessages(false);
        })
        .catch(error => {
            console.error('Error sending message:', error);
            showError('Failed to send message. Please try again.');
        });
    });

    // Add click event listeners for chat items
    document.querySelectorAll('.chat-item').forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            if (userId && userName) loadChat(userId, userName);
        });
    });

    // Toggle chat list on mobile
    document.querySelector('.chat-sidebar-header')?.addEventListener('click', function() {
        elements.chatList?.classList.toggle('active');
    });

    // Auto-load chat with manager if available
    if (chatConfig.managerData?.id && chatConfig.managerData?.name) {
        loadChat(chatConfig.managerData.id, chatConfig.managerData.name);
    }
}

// Start initialization when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeChat);
} else {
    initializeChat();
}
