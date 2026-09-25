/**
 * SkillMatch Core API Client — Pure Live Backend
 * Connects frontend to PHP REST Backend via XAMPP Apache.
 * No mock data. No static fallback. All data from MySQL via PHP.
 */

(function () {
  'use strict';

  function resolveApiBaseUrl() {
    if (window.SKILLMATCH_API_BASE) return window.SKILLMATCH_API_BASE;
    const loc = window.location;
    // XAMPP: http://localhost/UIU_Web_4165/... → /api
    if (loc && loc.origin && loc.origin.startsWith('http://localhost') && !loc.port.includes('8080')) {
      const match = loc.pathname.match(/^(\/[^\/]+)/);
      const projectRoot = match ? match[1] : '';
      return `${loc.origin}${projectRoot}/api`;
    }
    return 'http://localhost:8080/api';
  }

  const CONFIG = {
    BASE_URL: resolveApiBaseUrl(),
    TIMEOUT: 8000,
    SESSION_KEY: 'skillmatch_session_v1'
  };

  // Session / Authentication Manager
  const AuthManager = {
    getUser() {
      const sess = localStorage.getItem(CONFIG.SESSION_KEY);
      if (sess) {
        try { return JSON.parse(sess); } catch (e) {}
      }
      return null;
    },

    setUser(user, token) {
      const session = { ...user, token, lastLogin: new Date().toISOString() };
      localStorage.setItem(CONFIG.SESSION_KEY, JSON.stringify(session));
      return session;
    },

    logout() {
      localStorage.removeItem(CONFIG.SESSION_KEY);
      // Redirect to login from wherever we are
      const path = window.location.pathname;
      if (path.includes('/freelancer/') || path.includes('/client/') || path.includes('/Admin/')) {
        window.location.href = '../guest/login.html';
      } else {
        window.location.href = 'guest/login.html';
      }
    },

    isAuthenticated() {
      return !!this.getUser();
    },

    getRole() {
      const u = this.getUser();
      return u ? (u.role || 'GUEST') : 'GUEST';
    }
  };

  // Unified API Client — live PHP backend only
  const apiClient = {
    baseUrl: CONFIG.BASE_URL,

    async request(endpoint, options = {}) {
      const url = `${this.baseUrl}${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;
      const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {})
      };

      const user = AuthManager.getUser();
      if (user && user.token) {
        headers['Authorization'] = `Bearer ${user.token}`;
      }

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), CONFIG.TIMEOUT);

      try {
        const response = await fetch(url, {
          ...options,
          headers,
          signal: controller.signal
        });
        clearTimeout(timeoutId);

        if (!response.ok) {
          const errData = await response.json().catch(() => ({}));
          throw new Error(errData.message || `HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
      } catch (err) {
        clearTimeout(timeoutId);
        if (err.name === 'AbortError') {
          throw new Error('Request timed out. Make sure XAMPP is running and the database is imported.');
        }
        throw err;
      }
    },

    get(endpoint, params = {}) {
      const query = new URLSearchParams(
        Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== ''))
      ).toString();
      const path = query ? `${endpoint}?${query}` : endpoint;
      return this.request(path, { method: 'GET' });
    },

    post(endpoint, body) {
      return this.request(endpoint, {
        method: 'POST',
        body: JSON.stringify(body)
      });
    },

    put(endpoint, body) {
      return this.request(endpoint, {
        method: 'PUT',
        body: JSON.stringify(body)
      });
    },

    delete(endpoint) {
      return this.request(endpoint, { method: 'DELETE' });
    }
  };

  // Toast notification helper
  function showToast(message, type = 'success', duration = 3500) {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
      <span>${message}</span>
      <button type="button" style="background:none;border:none;color:inherit;font-size:16px;cursor:pointer;margin-left:12px;" aria-label="Close">&times;</button>
    `;

    const closeBtn = toast.querySelector('button');
    closeBtn.addEventListener('click', () => toast.remove());

    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }

  // Show a persistent error banner when the backend is completely unreachable
  function showBackendError(container, retryFn) {
    if (!container) return;
    container.innerHTML = `
      <div style="padding:48px 24px; text-align:center; background:var(--color-white,#fff); border-radius:12px; border:1px solid #fee2e2;">
        <div style="font-size:36px; margin-bottom:12px;">⚠️</div>
        <h3 style="font-size:17px; font-weight:600; color:#b91c1c; margin-bottom:8px;">Cannot Connect to Backend</h3>
        <p style="color:#6b7280; font-size:14px; max-width:380px; margin:0 auto 20px;">
          Make sure XAMPP is running, Apache &amp; MySQL are started, and the 
          <strong>skillmatch_db</strong> database has been imported.
        </p>
        ${retryFn ? `<button type="button" onclick="(${retryFn.toString()})()" class="btn btn--solid" style="cursor:pointer;">Retry Connection</button>` : ''}
      </div>
    `;
  }

  // Expose globally
  window.SkillMatch = {
    config: CONFIG,
    auth: AuthManager,
    api: apiClient,
    showToast,
    showBackendError
  };

})();
