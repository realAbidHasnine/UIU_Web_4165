/**
 * SkillMatch — Admin Dashboard & Moderation Controller
 * All data fetched live from PHP REST endpoints:
 * - GET /api/admin/metrics
 * - GET /api/admin/users
 * - POST /api/admin/users/{id}/status
 */

document.addEventListener('DOMContentLoaded', () => {
  // Admin Dashboard Metrics
  const statUsers       = document.getElementById('statTotalUsers');
  const statFreelancers = document.getElementById('statActiveFreelancers');
  const statClients     = document.getElementById('statActiveClients');
  const statProjects    = document.getElementById('statProjectsPosted');
  const statRevenue     = document.getElementById('statPlatformRevenue');

  async function loadDashboardMetrics() {
    if (!statUsers && !statFreelancers) return;

    try {
      const metrics = await SkillMatch.api.get('/admin/metrics');
      if (statUsers)       statUsers.textContent       = (metrics.totalUsers       ?? '—').toLocaleString();
      if (statFreelancers) statFreelancers.textContent = (metrics.freelancers      ?? '—').toLocaleString();
      if (statClients)     statClients.textContent     = (metrics.clients          ?? '—').toLocaleString();
      if (statProjects)    statProjects.textContent    = (metrics.activeJobs       ?? '—').toLocaleString();
      if (statRevenue)     statRevenue.textContent     = metrics.platformRevenue !== undefined
        ? `$${Number(metrics.platformRevenue).toLocaleString()}`
        : '—';
    } catch (err) {
      [statUsers, statFreelancers, statClients, statProjects, statRevenue].forEach(el => {
        if (el) el.textContent = 'Error';
      });
      SkillMatch.showToast('Failed to load admin metrics. Check XAMPP connection.', 'error');
    }
  }

  // User Management
  const userTableBody = document.querySelector('.um-table tbody');
  const userSearch    = document.querySelector('input[name="user_search"]');
  const roleSelect    = document.querySelector('select[name="role_filter"]');

  let users = [];

  async function loadUsers() {
    if (!userTableBody) return;

    userTableBody.innerHTML = `
      <tr><td colspan="5" style="padding:32px;text-align:center;color:var(--color-text-muted);">
        <div class="spinner" style="display:inline-block;width:24px;height:24px;border:3px solid rgba(37,99,235,0.2);border-top-color:var(--color-primary);border-radius:50%;animation:spin 0.8s linear infinite;"></div>
        <p style="margin-top:10px;font-size:13px;">Loading users from database...</p>
      </td></tr>
    `;

    try {
      const res = await SkillMatch.api.get('/admin/users');
      if (Array.isArray(res)) {
        users = res;
        renderUserTable();
      } else {
        throw new Error('Unexpected response format');
      }
    } catch (err) {
      userTableBody.innerHTML = `
        <tr><td colspan="5" style="padding:32px;text-align:center;color:var(--color-danger);">
          Failed to load users from database.<br>
          <button type="button" class="btn btn--outline" id="retryUsers" style="margin-top:12px;">Retry</button>
        </td></tr>
      `;
      document.getElementById('retryUsers')?.addEventListener('click', loadUsers);
    }
  }

  function renderUserTable() {
    if (!userTableBody) return;

    const query      = (userSearch?.value || '').toLowerCase().trim();
    const roleFilter = (roleSelect?.value || 'all').toLowerCase();

    const filtered = users.filter(u => {
      const matchQuery = (u.name || '').toLowerCase().includes(query) || (u.email || '').toLowerCase().includes(query);
      const matchRole  = roleFilter === 'all' || (u.role || '').toLowerCase() === roleFilter;
      return matchQuery && matchRole;
    });

    if (filtered.length === 0) {
      userTableBody.innerHTML = `
        <tr><td colspan="5" style="padding:32px;text-align:center;color:var(--color-text-muted);">
          No users match your search criteria.
        </td></tr>
      `;
      return;
    }

    userTableBody.innerHTML = filtered.map(u => {
      const initials  = (u.name || 'U').substring(0, 2).toUpperCase();
      const joinedStr = u.joinedAt
        ? new Date(u.joinedAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        : '—';

      return `
        <tr>
          <td>
            <div class="user-cell" style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:50%;background:#e0e7ff;color:#1e40af;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;">
                ${initials}
              </div>
              <div>
                <div class="user-cell-name" style="font-weight:500;">${escapeHtml(u.name)}</div>
                <div class="user-cell-email" style="font-size:12px;color:var(--color-text-muted);">${escapeHtml(u.email)}</div>
              </div>
            </div>
          </td>
          <td><span class="pill pill-type">${escapeHtml(u.role)}</span></td>
          <td>
            <span class="pill ${u.status === 'Active' ? 'pill-active' : 'pill-suspended'}"
              style="${u.status !== 'Active' ? 'background:#fee2e2;color:#b91c1c;' : ''}">
              ${escapeHtml(u.status)}
            </span>
          </td>
          <td>${joinedStr}</td>
          <td class="col-actions">
            <div class="action-group" style="display:flex;gap:8px;">
              <button type="button" class="btn-outline btn-toggle-status" data-id="${u.id}"
                style="padding:4px 10px;font-size:12px;cursor:pointer;">
                ${u.status === 'Active' ? 'Suspend' : 'Activate'}
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    userTableBody.querySelectorAll('.btn-toggle-status').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id   = btn.getAttribute('data-id');
        const user = users.find(u => u.id === id);
        if (!user) return;

        const newStatus = user.status === 'Active' ? 'Suspended' : 'Active';
        btn.disabled    = true;
        btn.textContent = 'Updating...';

        try {
          await SkillMatch.api.post(`/admin/users/${id}/status`, { status: newStatus });
          user.status = newStatus;
          renderUserTable();
          SkillMatch.showToast(`User status updated to ${newStatus}.`, 'info');
        } catch (err) {
          SkillMatch.showToast('Failed to update user status: ' + err.message, 'error');
          btn.disabled    = false;
          btn.textContent = user.status === 'Active' ? 'Suspend' : 'Activate';
        }
      });
    });
  }

  if (userSearch) userSearch.addEventListener('input', renderUserTable);
  if (roleSelect) roleSelect.addEventListener('change', renderUserTable);

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  loadDashboardMetrics();
  loadUsers();
});
