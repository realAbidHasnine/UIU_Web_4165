/**
 * SkillMatch — Dynamic Chat & Messaging Client
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/chat/threads
 * - GET /api/chat/threads/{id}/messages
 * - POST /api/chat/threads/{id}/messages
 */

document.addEventListener('DOMContentLoaded', () => {
  const inboxList = document.getElementById('inboxList');
  const chatHeaderName = document.getElementById('chatHeaderName');
  const chatHeaderBadge = document.getElementById('chatHeaderBadge');
  const chatMessages = document.getElementById('chatMessages');
  const chatInputForm = document.getElementById('chatInputForm');
  const messageInput = document.getElementById('messageInput');
  const totalUnreadBadge = document.getElementById('totalUnread');

  let activeThreadId = null;
  let threads = [];

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  // Load chat threads
  async function loadThreads() {
    if (!inboxList) return;

    try {
      threads = await SkillMatch.api.get('/chat/threads');
      renderThreads(threads);

      // Select first thread by default
      if (threads.length > 0 && !activeThreadId) {
        selectThread(threads[0].id);
      }
    } catch (err) {
      console.error('Failed to load chat threads:', err);
      inboxList.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--color-danger); font-size: 13px;">Error loading messages.</div>`;
    }
  }

  // Render thread list in sidebar
  function renderThreads(list) {
    if (!inboxList) return;

    let totalUnread = 0;
    inboxList.innerHTML = list.map(t => {
      totalUnread += (t.unread || 0);
      const initials = getInitials(t.name);
      const isActive = t.id === activeThreadId;

      return `
        <div class="inbox-item ${isActive ? 'inbox-item--active' : ''}" 
             data-thread-id="${t.id}" 
             role="button" 
             tabindex="0" 
             style="cursor: pointer; display: flex;">
          <div class="avatar avatar--blue">${initials}</div>
          <div class="inbox-item__content">
            <div class="inbox-item__top">
              <div class="inbox-item__name-wrap">
                <span class="inbox-item__name">${escapeHtml(t.name)}</span>
                <span class="badge-client">${escapeHtml(t.role || 'Client')}</span>
              </div>
              <span class="inbox-item__time">${escapeHtml(t.time || '')}</span>
            </div>
            <div class="inbox-item__bottom">
              <span class="inbox-item__preview">${escapeHtml(t.lastMessage || 'No messages yet')}</span>
              ${t.unread ? `<span class="unread-badge client-unread">${t.unread}</span>` : ''}
            </div>
          </div>
        </div>
      `;
    }).join('');

    if (totalUnreadBadge) {
      if (totalUnread > 0) {
        totalUnreadBadge.textContent = totalUnread;
        totalUnreadBadge.style.display = 'inline-block';
      } else {
        totalUnreadBadge.style.display = 'none';
      }
    }

    // Attach click listeners
    inboxList.querySelectorAll('.inbox-item').forEach(item => {
      item.addEventListener('click', () => {
        const id = item.getAttribute('data-thread-id');
        selectThread(id);
      });
    });
  }

  // Select a thread and load its message history
  async function selectThread(threadId) {
    activeThreadId = threadId;
    const thread = threads.find(t => t.id === threadId);
    if (!thread) return;

    // Clear unread
    thread.unread = 0;
    renderThreads(threads);

    // Update header
    if (chatHeaderName) chatHeaderName.textContent = thread.name;
    if (chatHeaderBadge) chatHeaderBadge.textContent = thread.company ? `${thread.role} • ${thread.company}` : (thread.role || 'Client');

    // Show loading state
    if (chatMessages) {
      chatMessages.innerHTML = `
        <div style="padding: 30px; text-align: center; color: var(--color-text-muted); font-size: 13px;">
          Loading conversation...
        </div>
      `;
    }

    try {
      const messages = await SkillMatch.api.get(`/chat/threads/${threadId}/messages`);
      renderMessages(messages);
    } catch (err) {
      if (chatMessages) {
        chatMessages.innerHTML = `
          <div style="padding: 30px; text-align: center; color: var(--color-danger); font-size: 13px;">
            Failed to load message history.
          </div>
        `;
      }
    }
  }

  // Render message bubbles in chat window
  function renderMessages(messages) {
    if (!chatMessages) return;

    if (!messages || messages.length === 0) {
      chatMessages.innerHTML = `
        <div style="padding: 40px; text-align: center; color: var(--color-text-muted); font-size: 14px;">
          Start the conversation by sending a message below.
        </div>
      `;
      return;
    }

    chatMessages.innerHTML = `
      <div class="chat__date-separator">
        <span>Conversation History</span>
      </div>
      ${messages.map(msg => `
        <div class="message ${msg.isMe ? 'message--sent' : 'message--received'}">
          <div class="message__bubble">
            ${escapeHtml(msg.text)}
          </div>
          <span class="message__time">${escapeHtml(msg.time || '')}</span>
        </div>
      `).join('')}
    `;

    scrollToBottom();
  }

  // Send message
  if (chatInputForm) {
    chatInputForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const text = messageInput?.value?.trim();
      if (!text || !activeThreadId) return;

      messageInput.value = '';

      // Optimistically append sent bubble
      const timeNow = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      appendMessageBubble({ text, time: timeNow, isMe: true });

      try {
        await SkillMatch.api.post(`/chat/threads/${activeThreadId}/messages`, { text });

        // Update preview in list
        const activeThread = threads.find(t => t.id === activeThreadId);
        if (activeThread) {
          activeThread.lastMessage = text;
          activeThread.time = timeNow;
          renderThreads(threads);
        }
      } catch (err) {
        SkillMatch.showToast('Failed to send message.', 'error');
      }
    });
  }

  function appendMessageBubble(msg) {
    if (!chatMessages) return;

    const div = document.createElement('div');
    div.className = `message ${msg.isMe ? 'message--sent' : 'message--received'}`;
    div.innerHTML = `
      <div class="message__bubble">${escapeHtml(msg.text)}</div>
      <span class="message__time">${escapeHtml(msg.time)}</span>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
  }

  function scrollToBottom() {
    if (chatMessages) {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
  }

  function getInitials(name) {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Initial load
  loadThreads();
});
