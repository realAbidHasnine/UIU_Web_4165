/**
 * SkillMatch — Admin Management Suite
 * Powers all pages in Admin/html/ via live PHP REST endpoints:
 * - /api/admin/metrics
 * - /api/admin/users
 * - /api/admin/approvals
 * - /api/admin/skill-categories
 * - /api/admin/skills/{category}/questions
 * - /api/admin/reports
 */

document.addEventListener('DOMContentLoaded', () => {
  const path = window.location.pathname;

  // Header logout
  document.querySelectorAll('.logout-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      SkillMatch.auth.logout();
    });
  });

  // -------------------------------------------------------------------------
  // 1. Dashboard Metrics (admin-dashboard.html)
  // -------------------------------------------------------------------------
  if (path.includes('admin-dashboard.html') || document.getElementById('statTotalUsers')) {
    initDashboardMetrics();
  }

  async function initDashboardMetrics() {
    const statUsers       = document.getElementById('statTotalUsers');
    const statFreelancers = document.getElementById('statActiveFreelancers');
    const statClients     = document.getElementById('statActiveClients');
    const statProjects    = document.getElementById('statProjectsPosted');
    const statRevenue     = document.getElementById('statPlatformRevenue');

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
      SkillMatch.showToast('Failed to load admin metrics.', 'error');
    }
  }

  // -------------------------------------------------------------------------
  // 2. User Management (user-management.html)
  // -------------------------------------------------------------------------
  if (path.includes('user-management.html') || document.querySelector('.um-table')) {
    initUserManagement();
  }

  function initUserManagement() {
    const userTableBody = document.querySelector('.um-table tbody');
    const userSearch    = document.querySelector('input[name="user_search"]');
    const roleSelect    = document.querySelector('select[name="role_filter"]');
    if (!userTableBody) return;

    let users = [];

    async function loadUsers() {
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

    loadUsers();
  }

  // -------------------------------------------------------------------------
  // 3. Approvals: Clients & Freelancers
  // -------------------------------------------------------------------------
  if (path.includes('client-approvals.html')) {
    initApprovals('CLIENT');
  } else if (path.includes('freelancer-approvals.html')) {
    initApprovals('FREELANCER');
  }

  function initApprovals(type) {
    const tableBody = document.querySelector('section.table-card table tbody');
    const searchInput = document.querySelector('input[type="text"][name*="search"], .search-box input');
    const entriesInfo = document.querySelector('.entries-info');
    if (!tableBody) return;

    let items = [];

    async function loadApprovals() {
      tableBody.innerHTML = `
        <tr><td colspan="${type === 'CLIENT' ? 4 : 6}" style="padding:32px;text-align:center;color:var(--color-text-muted,#6b7280);">
          <div class="spinner" style="display:inline-block;width:24px;height:24px;border:3px solid rgba(37,99,235,0.2);border-top-color:#2563eb;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
          <p style="margin-top:10px;font-size:13px;">Loading ${type.toLowerCase()} approvals...</p>
        </td></tr>
      `;

      try {
        const res = await SkillMatch.api.get('/admin/approvals', { type, status: 'all' });
        items = Array.isArray(res) ? res : [];
        renderApprovals();
      } catch (err) {
        tableBody.innerHTML = `
          <tr><td colspan="${type === 'CLIENT' ? 4 : 6}" style="padding:24px;text-align:center;color:#b91c1c;">
            Failed to load approvals: ${escapeHtml(err.message)}
          </td></tr>
        `;
      }
    }

    function renderApprovals() {
      const q = (searchInput?.value || '').toLowerCase().trim();
      const filtered = items.filter(it => 
        (it.name || '').toLowerCase().includes(q) ||
        (it.email || '').toLowerCase().includes(q) ||
        (it.company || '').toLowerCase().includes(q)
      );

      if (entriesInfo) {
        entriesInfo.textContent = `Showing ${filtered.length} of ${items.length} applications`;
      }

      if (filtered.length === 0) {
        tableBody.innerHTML = `
          <tr><td colspan="${type === 'CLIENT' ? 4 : 6}" style="padding:32px;text-align:center;color:#6b7280;">
            No applications match your criteria.
          </td></tr>
        `;
        return;
      }

      tableBody.innerHTML = filtered.map(it => {
        const isPending = it.status === 'Pending';
        const dateStr = it.createdAt ? new Date(it.createdAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Recently';

        if (type === 'CLIENT') {
          return `
            <tr>
              <td>
                <div class="company-cell" style="display:flex;align-items:center;gap:10px;">
                  <div class="company-logo" style="background:#2f6fed;color:#fff;width:36px;height:36px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;">
                    ${(it.company || it.name || 'C').substring(0, 2).toUpperCase()}
                  </div>
                  <div>
                    <div class="company-name" style="font-weight:600;">${escapeHtml(it.company || it.name)}</div>
                    <div class="contact-name" style="font-size:12px;color:#6b7280;">${escapeHtml(it.name)}</div>
                  </div>
                </div>
              </td>
              <td class="email-cell">${escapeHtml(it.email)}</td>
              <td class="submitted-date">${dateStr}</td>
              <td>
                <div class="actions-cell" style="display:flex;gap:8px;">
                  ${isPending ? `
                    <button type="button" class="btn btn-approve" data-id="${it.id}" style="padding:6px 12px;background:#10b981;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Approve</button>
                    <button type="button" class="btn btn-reject" data-id="${it.id}" style="padding:6px 12px;background:#ef4444;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Reject</button>
                  ` : `
                    <span class="pill ${it.status === 'Approved' ? 'pill-active' : 'pill-suspended'}" style="padding:4px 10px;font-size:12px;border-radius:12px;${it.status === 'Approved' ? 'background:#d1fae5;color:#065f46;' : 'background:#fee2e2;color:#991b1b;'}">${escapeHtml(it.status)}</span>
                  `}
                </div>
              </td>
            </tr>
          `;
        } else {
          // Freelancer row
          const skills = it.skills || [];
          return `
            <tr>
              <td>
                <div class="freelancer-cell" style="display:flex;align-items:center;gap:10px;">
                  <div class="freelancer-avatar" style="width:36px;height:36px;border-radius:50%;background:#e0e7ff;color:#1e40af;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;">
                    ${(it.name || 'F').substring(0, 2).toUpperCase()}
                  </div>
                  <div>
                    <div class="freelancer-name" style="font-weight:600;">${escapeHtml(it.name)}</div>
                    <div class="freelancer-email" style="font-size:12px;color:#6b7280;">${escapeHtml(it.email)}</div>
                  </div>
                </div>
              </td>
              <td class="submitted-date">${dateStr}</td>
              <td>
                <div class="skill-tags" style="display:flex;gap:4px;flex-wrap:wrap;">
                  ${skills.slice(0, 3).map(s => `<span class="tag" style="background:#f1f5f9;font-size:11px;padding:2px 6px;border-radius:4px;">${escapeHtml(s)}</span>`).join('')}
                  ${skills.length > 3 ? `<span class="tag more" style="font-size:11px;color:#6b7280;">+${skills.length - 3}</span>` : ''}
                </div>
              </td>
              <td>
                ${it.portfolioUrl ? `<a href="${escapeHtml(it.portfolioUrl)}" target="_blank" class="portfolio-link" style="font-size:12px;color:#2563eb;">View Portfolio ↗</a>` : '—'}
              </td>
              <td>
                ${it.githubUrl ? `<a href="${escapeHtml(it.githubUrl)}" target="_blank" class="github-link" style="font-size:12px;color:#2563eb;">GitHub ↗</a>` : '—'}
              </td>
              <td>
                <div class="actions-cell" style="display:flex;gap:8px;">
                  ${isPending ? `
                    <button type="button" class="btn btn-approve" data-id="${it.id}" style="padding:6px 12px;background:#10b981;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Approve</button>
                    <button type="button" class="btn btn-reject" data-id="${it.id}" style="padding:6px 12px;background:#ef4444;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Reject</button>
                  ` : `
                    <span class="pill ${it.status === 'Approved' ? 'pill-active' : 'pill-suspended'}" style="padding:4px 10px;font-size:12px;border-radius:12px;${it.status === 'Approved' ? 'background:#d1fae5;color:#065f46;' : 'background:#fee2e2;color:#991b1b;'}">${escapeHtml(it.status)}</span>
                  `}
                </div>
              </td>
            </tr>
          `;
        }
      }).join('');

      // Wire Approve / Reject actions
      tableBody.querySelectorAll('.btn-approve').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = btn.getAttribute('data-id');
          btn.disabled = true;
          btn.textContent = 'Approving...';
          try {
            await SkillMatch.api.post(`/admin/approvals/${id}/decide`, { decision: 'Approved' });
            SkillMatch.showToast('Application approved successfully!', 'success');
            loadApprovals();
          } catch (err) {
            SkillMatch.showToast('Failed to approve: ' + err.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Approve';
          }
        });
      });

      tableBody.querySelectorAll('.btn-reject').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = btn.getAttribute('data-id');
          const reason = prompt('Please enter a rejection reason:') || 'Does not meet platform requirements';
          btn.disabled = true;
          btn.textContent = 'Rejecting...';
          try {
            await SkillMatch.api.post(`/admin/approvals/${id}/decide`, { decision: 'Rejected', reason });
            SkillMatch.showToast('Application rejected.', 'info');
            loadApprovals();
          } catch (err) {
            SkillMatch.showToast('Failed to reject: ' + err.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Reject';
          }
        });
      });
    }

    if (searchInput) searchInput.addEventListener('input', renderApprovals);
    loadApprovals();
  }

  // -------------------------------------------------------------------------
  // 4. Skill Categories Manager (skill-category-manager.html)
  // -------------------------------------------------------------------------
  if (path.includes('skill-category-manager.html')) {
    initSkillCategories();
  }

  function initSkillCategories() {
    const tableBody = document.querySelector('section.table-card table tbody');
    const addBtn = document.querySelector('.add-category-btn');
    if (!tableBody) return;

    let categories = [];

    async function loadCategories() {
      tableBody.innerHTML = `
        <tr><td colspan="5" style="padding:32px;text-align:center;color:#6b7280;">
          <div class="spinner" style="display:inline-block;width:24px;height:24px;border:3px solid rgba(37,99,235,0.2);border-top-color:#2563eb;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
          <p style="margin-top:10px;font-size:13px;">Loading skill categories...</p>
        </td></tr>
      `;

      try {
        const res = await SkillMatch.api.get('/admin/skill-categories');
        categories = Array.isArray(res) ? res : [];
        renderCategories();
      } catch (err) {
        tableBody.innerHTML = `
          <tr><td colspan="5" style="padding:24px;text-align:center;color:#b91c1c;">
            Failed to load categories: ${escapeHtml(err.message)}
          </td></tr>
        `;
      }
    }

    function renderCategories() {
      if (categories.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" style="padding:24px;text-align:center;">No skill categories found.</td></tr>';
        return;
      }

      tableBody.innerHTML = categories.map(cat => {
        const subs = (cat.subcategories || []).join(', ') || 'All levels';
        const isActive = cat.isActive !== false;

        return `
          <tr>
            <td>
              <div class="category-name" style="font-weight:600;">${escapeHtml(cat.icon ? cat.icon + ' ' : '')}${escapeHtml(cat.name)}</div>
              <div class="category-sub" style="font-size:12px;color:#6b7280;">${escapeHtml(subs)}</div>
            </td>
            <td class="count-cell">${cat.freelancersCount ?? '—'}</td>
            <td class="count-cell">${cat.questionCount ?? 5}</td>
            <td class="active-cell">
              <label class="toggle" style="cursor:pointer;">
                <input type="checkbox" class="cat-toggle" data-id="${cat.id}" ${isActive ? 'checked' : ''}>
                <span class="toggle-slider"></span>
              </label>
            </td>
            <td>
              <div class="actions-cell" style="display:flex;gap:8px;">
                <button type="button" class="action-link btn-del-cat" data-id="${cat.id}" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:13px;">Delete</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');

      tableBody.querySelectorAll('.cat-toggle').forEach(input => {
        input.addEventListener('change', async () => {
          const id = input.getAttribute('data-id');
          const isActive = input.checked;
          try {
            await SkillMatch.api.put(`/admin/skill-categories/${id}`, { isActive });
            SkillMatch.showToast(`Category ${isActive ? 'activated' : 'deactivated'}`, 'info');
          } catch (err) {
            SkillMatch.showToast('Update failed: ' + err.message, 'error');
            input.checked = !isActive;
          }
        });
      });

      tableBody.querySelectorAll('.btn-del-cat').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = btn.getAttribute('data-id');
          if (!confirm(`Are you sure you want to delete category '${id}'? This only succeeds if it has no associated questions.`)) return;

          try {
            await SkillMatch.api.delete(`/admin/skill-categories/${id}`);
            SkillMatch.showToast('Category deleted', 'success');
            loadCategories();
          } catch (err) {
            SkillMatch.showToast('Cannot delete category: ' + err.message, 'error');
          }
        });
      });
    }

    if (addBtn) {
      addBtn.addEventListener('click', async () => {
        const name = prompt('Enter new Skill Category name:');
        if (!name || !name.trim()) return;

        const icon = prompt('Enter an icon emoji for this category (e.g. 💻, 🎨, 🔒):') || '💡';
        const subcategories = prompt('Enter comma-separated subcategories (optional):') || '';

        try {
          await SkillMatch.api.post('/admin/skill-categories', {
            name: name.trim(),
            icon: icon.trim(),
            subcategories: subcategories ? subcategories.split(',').map(s => s.trim()) : []
          });
          SkillMatch.showToast('Skill Category added successfully!', 'success');
          loadCategories();
        } catch (err) {
          SkillMatch.showToast('Failed to add category: ' + err.message, 'error');
        }
      });
    }

    loadCategories();
  }

  // -------------------------------------------------------------------------
  // 5. Question Bank Manager (question-bank-manager.html)
  // -------------------------------------------------------------------------
  if (path.includes('question-bank-manager.html')) {
    initQuestionBank();
  }

  function initQuestionBank() {
    const tableBody = document.querySelector('.table-card table tbody');
    const pageHeader = document.querySelector('.page-header');
    if (!tableBody) return;

    let currentCategory = 'web';
    let questions = [];

    // Inject category selector if missing
    let catSelect = document.getElementById('categorySelect');
    if (!catSelect && pageHeader) {
      const controlsWrap = document.createElement('div');
      controlsWrap.style.cssText = 'display:flex;gap:12px;align-items:center;';

      catSelect = document.createElement('select');
      catSelect.id = 'categorySelect';
      catSelect.style.cssText = 'padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;background:#fff;';
      catSelect.innerHTML = `
        <option value="web">Web Development</option>
        <option value="uiux">UI / UX Design</option>
        <option value="mobile">Mobile App Development</option>
        <option value="cloud">Cloud & DevOps</option>
      `;

      const addBtn = pageHeader.querySelector('.add-btn');
      if (addBtn) {
        pageHeader.insertBefore(controlsWrap, addBtn);
        controlsWrap.appendChild(catSelect);
        controlsWrap.appendChild(addBtn);
      } else {
        pageHeader.appendChild(controlsWrap);
        controlsWrap.appendChild(catSelect);
      }
    }

    if (catSelect) {
      catSelect.addEventListener('change', () => {
        currentCategory = catSelect.value;
        const title = pageHeader.querySelector('h1');
        if (title) title.textContent = `Question Bank: ${catSelect.options[catSelect.selectedIndex].text}`;
        loadQuestions();
      });
    }

    async function loadQuestions() {
      tableBody.innerHTML = `
        <tr><td colspan="4" style="padding:32px;text-align:center;color:#6b7280;">
          <div class="spinner" style="display:inline-block;width:24px;height:24px;border:3px solid rgba(37,99,235,0.2);border-top-color:#2563eb;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
          <p style="margin-top:10px;font-size:13px;">Loading questions for ${currentCategory}...</p>
        </td></tr>
      `;

      try {
        const res = await SkillMatch.api.get(`/admin/skills/${encodeURIComponent(currentCategory)}/questions`);
        questions = Array.isArray(res) ? res : [];
        renderQuestions();
      } catch (err) {
        tableBody.innerHTML = `
          <tr><td colspan="4" style="padding:24px;text-align:center;color:#b91c1c;">
            Failed to load questions: ${escapeHtml(err.message)}
          </td></tr>
        `;
      }
    }

    function renderQuestions() {
      const paginationInfo = document.querySelector('.pagination-info');
      if (paginationInfo) {
        paginationInfo.textContent = `Showing ${questions.length} questions in this category`;
      }

      if (questions.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" style="padding:32px;text-align:center;color:#6b7280;">No questions found for this category.</td></tr>';
        return;
      }

      tableBody.innerHTML = questions.map(q => {
        const diffPill = q.difficulty === 'Hard' ? 'pill-hard' : (q.difficulty === 'Easy' ? 'pill-easy' : 'pill-medium');

        return `
          <tr>
            <td style="max-width:500px;">
              <div style="font-weight:500;margin-bottom:6px;">${escapeHtml(q.question)}</div>
              <div style="font-size:12px;color:#6b7280;">
                Options: ${(q.options || []).map((opt, i) => `${i === q.correctIndex ? '<strong>✓ ' : ''}${escapeHtml(opt)}${i === q.correctIndex ? '</strong>' : ''}`).join(' &bull; ')}
              </div>
            </td>
            <td><span class="pill pill-type">${escapeHtml(q.type || 'Multiple Choice')}</span></td>
            <td><span class="pill ${diffPill}">${escapeHtml(q.difficulty || 'Medium')}</span></td>
            <td class="col-actions">
              <button type="button" class="btn-del-question" data-id="${q.id}" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:13px;">Delete</button>
            </td>
          </tr>
        `;
      }).join('');

      tableBody.querySelectorAll('.btn-del-question').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = btn.getAttribute('data-id');
          if (!confirm('Are you sure you want to delete this question?')) return;

          try {
            await SkillMatch.api.delete(`/admin/skills/${encodeURIComponent(currentCategory)}/questions/${id}`);
            SkillMatch.showToast('Question deleted', 'info');
            loadQuestions();
          } catch (err) {
            SkillMatch.showToast('Failed to delete question: ' + err.message, 'error');
          }
        });
      });
    }

    const addBtn = document.querySelector('.add-btn');
    if (addBtn) {
      addBtn.addEventListener('click', async () => {
        const text = prompt('Enter the question text:');
        if (!text || !text.trim()) return;

        const opt1 = prompt('Option 1:') || '';
        const opt2 = prompt('Option 2:') || '';
        const opt3 = prompt('Option 3:') || '';
        const opt4 = prompt('Option 4:') || '';
        const correctStr = prompt('Which option is correct? (1, 2, 3, or 4):') || '1';
        const correctIndex = Math.max(0, Math.min(3, parseInt(correctStr, 10) - 1));

        try {
          await SkillMatch.api.post(`/admin/skills/${encodeURIComponent(currentCategory)}/questions`, {
            question: text.trim(),
            qtype: 'Multiple Choice',
            difficulty: 'Medium',
            options: [opt1, opt2, opt3, opt4].filter(Boolean),
            correctIndex
          });
          SkillMatch.showToast('Question added successfully!', 'success');
          loadQuestions();
        } catch (err) {
          SkillMatch.showToast('Failed to add question: ' + err.message, 'error');
        }
      });
    }

    loadQuestions();
  }

  // -------------------------------------------------------------------------
  // 6. Reports Generator (reports-generator.html)
  // -------------------------------------------------------------------------
  if (path.includes('reports-generator.html')) {
    initReports();
  }

  function initReports() {
    const tableBody = document.querySelector('.reports-table-card table tbody');
    const reportCard = document.querySelector('.report-card');
    const typeSelect = document.getElementById('report-type');
    const fromInput  = document.getElementById('date-from');
    const toInput    = document.getElementById('date-to');
    if (!tableBody || !reportCard) return;

    // Inject generate button if not already present
    let genBtn = document.getElementById('btnGenerateReport');
    if (!genBtn) {
      const btnWrap = document.createElement('div');
      btnWrap.style.cssText = 'margin-top: 18px; text-align: right;';
      genBtn = document.createElement('button');
      genBtn.type = 'button';
      genBtn.id = 'btnGenerateReport';
      genBtn.textContent = 'Generate Report';
      genBtn.style.cssText = 'background:#2563eb;color:#fff;padding:9px 20px;border-radius:6px;border:none;cursor:pointer;font-weight:600;font-size:14px;';
      btnWrap.appendChild(genBtn);
      reportCard.appendChild(btnWrap);
    }

    let reports = [];

    async function loadReports() {
      tableBody.innerHTML = `
        <tr><td colspan="3" style="padding:24px;text-align:center;color:#6b7280;">
          <div class="spinner" style="display:inline-block;width:20px;height:20px;border:2px solid rgba(37,99,235,0.2);border-top-color:#2563eb;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
          <p style="margin-top:8px;font-size:13px;">Loading generated reports...</p>
        </td></tr>
      `;

      try {
        const res = await SkillMatch.api.get('/admin/reports');
        reports = Array.isArray(res) ? res : [];
        renderReports();
      } catch (err) {
        tableBody.innerHTML = `
          <tr><td colspan="3" style="padding:20px;text-align:center;color:#b91c1c;">
            Failed to load reports: ${escapeHtml(err.message)}
          </td></tr>
        `;
      }
    }

    function renderReports() {
      if (reports.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="3" style="padding:24px;text-align:center;color:#6b7280;">No reports generated yet. Select a type above and click "Generate Report".</td></tr>';
        return;
      }

      tableBody.innerHTML = reports.map(r => {
        const dateStr = r.createdAt ? new Date(r.createdAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Today';

        return `
          <tr>
            <td>
              <div style="font-weight:600;">${escapeHtml(r.filename)}</div>
              <div style="font-size:12px;color:#6b7280;">Type: ${escapeHtml(r.reportType)} &bull; ${escapeHtml(r.dateFrom || '')} to ${escapeHtml(r.dateTo || '')}</div>
            </td>
            <td>${dateStr}</td>
            <td class="col-actions">
              <button type="button" class="btn-dl-report" data-id="${r.id}" style="color:#2563eb;background:none;border:none;cursor:pointer;font-weight:600;font-size:13px;">Download PDF ↓</button>
            </td>
          </tr>
        `;
      }).join('');

      tableBody.querySelectorAll('.btn-dl-report').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = btn.getAttribute('data-id');
          btn.textContent = 'Downloading...';
          try {
            const user = SkillMatch.auth.getUser();
            const token = user?.token;
            const dlUrl = `${SkillMatch.config.BASE_URL}/admin/reports/${id}/download`;

            const resp = await fetch(dlUrl, {
              headers: token ? { 'Authorization': `Bearer ${token}` } : {}
            });
            if (!resp.ok) throw new Error('Download failed');

            const blob = await resp.blob();
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `report-${id}.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();

            btn.textContent = 'Download PDF ↓';
            SkillMatch.showToast('Report PDF downloaded', 'success');
          } catch (err) {
            SkillMatch.showToast('Download error: ' + err.message, 'error');
            btn.textContent = 'Download PDF ↓';
          }
        });
      });
    }

    genBtn.addEventListener('click', async () => {
      const reportType = typeSelect?.value;
      if (!reportType) {
        SkillMatch.showToast('Please select a report type first.', 'error');
        typeSelect?.focus();
        return;
      }

      const dateFrom = fromInput?.value.trim() || undefined;
      const dateTo   = toInput?.value.trim() || undefined;

      genBtn.disabled = true;
      genBtn.textContent = 'Generating PDF...';

      try {
        const res = await SkillMatch.api.post('/admin/reports', {
          reportType,
          dateFrom,
          dateTo
        });

        SkillMatch.showToast(`Report '${res.filename}' generated successfully!`, 'success');
        genBtn.disabled = false;
        genBtn.textContent = 'Generate Report';
        loadReports();
      } catch (err) {
        SkillMatch.showToast('Failed to generate report: ' + err.message, 'error');
        genBtn.disabled = false;
        genBtn.textContent = 'Generate Report';
      }
    });

    loadReports();
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
});
