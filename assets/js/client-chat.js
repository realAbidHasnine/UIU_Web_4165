/**
 * SkillMatch — Dynamic Client Chat & Messaging
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/chat/threads
 * - GET /api/chat/threads/{id}/messages
 * - POST /api/chat/threads/{id}/messages
 */

document.addEventListener('DOMContentLoaded', () => {
  const docketSide = document.querySelector('.docket-side');
  const transcript = document.querySelector('.transcript');
  const chatInput = document.getElementById('chat_message');
  const sendBtn = document.querySelector('.composer .btn-file') || document.querySelector('.composer button');
  const headerName = document.querySelector('.project-rail strong');

  let activeThreadId = 'thread-1';
  let threads = [];

  async function loadThreads() {
    try {
      threads = await SkillMatch.api.get('/chat/threads');
      renderThreads(threads);
      loadMessages(activeThreadId);
    } catch (err) {
      console.error('Failed to load client chat:', err);
    }
  }

  function renderThreads(list) {
    if (!docketSide) return;

    const searchBox = docketSide.querySelector('div:first-child');
    docketSide.innerHTML = '';
    if (searchBox) docketSide.appendChild(searchBox);

    list.forEach(t => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = `thread ${t.id === activeThreadId ? 'active' : ''} ${t.unread ? 'unread' : ''}`;
      btn.innerHTML = `
        <span class="avatar-lg" style="background:#dcefe4;color:#065f46;font-size:14px">${getInitials(t.name)}</span>
        <span style="flex:1;min-width:0">
          <span class="tname">${escapeHtml(t.name)}</span>
          <span class="tmeta">${escapeHtml(t.role)} · ${escapeHtml(t.time || '')}</span>
          <span class="tsnip">${escapeHtml(t.lastMessage || '')}</span>
        </span>
      `;
      btn.addEventListener('click', () => {
        activeThreadId = t.id;
        renderThreads(threads);
        loadMessages(t.id);
      });
      docketSide.appendChild(btn);
    });
  }

  async function loadMessages(threadId) {
    if (!transcript) return;
    const thread = threads.find(t => t.id === threadId);
    if (headerName && thread) headerName.textContent = thread.name;

    try {
      const messages = await SkillMatch.api.get(`/chat/threads/${threadId}/messages`);
      renderMessages(messages);
    } catch (err) {
      transcript.innerHTML = '<div style="padding:20px;text-align:center;color:red">Failed to load messages</div>';
    }
  }

  function renderMessages(messages) {
    if (!transcript) return;

    transcript.innerHTML = `
      <div class="date-rule">Live Messages · Attached to Project</div>
      ${messages.map(msg => `
        <div class="slip ${msg.isMe ? 'out' : ''}">
          ${escapeHtml(msg.text)}
          <span class="when">${escapeHtml(msg.time || '')}</span>
        </div>
      `).join('')}
    `;
    transcript.scrollTop = transcript.scrollHeight;
  }

  function sendMessage() {
    const text = chatInput?.value?.trim();
    if (!text || !activeThreadId) return;

    chatInput.value = '';
    const timeNow = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    // Optimistic append
    const slip = document.createElement('div');
    slip.className = 'slip out';
    slip.innerHTML = `${escapeHtml(text)}<span class="when">${timeNow}</span>`;
    transcript.appendChild(slip);
    transcript.scrollTop = transcript.scrollHeight;

    SkillMatch.api.post(`/chat/threads/${activeThreadId}/messages`, { text });
  }

  if (sendBtn) {
    sendBtn.addEventListener('click', (e) => {
      e.preventDefault();
      sendMessage();
    });
  }

  if (chatInput) {
    chatInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
      }
    });
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

  loadThreads();
});
