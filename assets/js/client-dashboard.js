/**
 * SkillMatch — Client Dashboard Dynamic Data
 * Connects client dashboard to Spring Boot REST Endpoints:
 * - GET /api/clients/me/dashboard (KPI stats)
 * - GET /api/clients/me/projects (Recent projects)
 */

document.addEventListener('DOMContentLoaded', async () => {
  const activeProjectsEl = document.querySelector('.perf-strip .perf-cell:nth-child(1) .perf-num');
  const proposalsCountEl = document.querySelector('.perf-strip .perf-cell:nth-child(2) .perf-num');
  const freelancersCountEl = document.querySelector('.perf-strip .perf-cell:nth-child(3) .perf-num');
  const totalSpentEl = document.querySelector('.perf-strip .perf-cell:nth-child(4) .perf-num');
  const greetingEl = document.querySelector('.page-title');

  // Locate the recent projects card (the card directly after .section-head)
  const sectionHead = document.querySelector('.section-head');
  const recentProjectsCard = sectionHead ? sectionHead.nextElementSibling : null;

  async function loadDashboard() {
    try {
      const [dash, projects] = await Promise.all([
        SkillMatch.api.get('/clients/me/dashboard'),
        SkillMatch.api.get('/clients/me/projects')
      ]);

      if (dash) {
        if (greetingEl && dash.clientName) {
          greetingEl.textContent = `Welcome back, ${dash.clientName}`;
        }
        if (activeProjectsEl) activeProjectsEl.textContent = dash.activeProjects ?? 4;
        if (proposalsCountEl) proposalsCountEl.textContent = dash.proposalsReceived ?? 12;
        if (freelancersCountEl) freelancersCountEl.textContent = dash.freelancersHired ?? 8;
        if (totalSpentEl) totalSpentEl.textContent = dash.totalSpent || '$12,450';
      }

      if (projects && recentProjectsCard) {
        renderRecentProjects(projects);
      }
    } catch (err) {
      console.warn('Failed to load client dashboard dynamically:', err);
    }
  }

  function renderRecentProjects(projects) {
    if (!projects || projects.length === 0) {
      recentProjectsCard.innerHTML = `
        <div style="padding: 30px; text-align: center; color: var(--color-text-muted);">
          <p>No projects posted yet.</p>
          <a class="btn btn-primary" href="post-project-details.html" style="margin-top: 10px; display: inline-block;">+ Post a Project</a>
        </div>
      `;
      return;
    }

    recentProjectsCard.innerHTML = projects.map(proj => {
      let badgeClass = 'badge-open';
      if (proj.status === 'In Progress') badgeClass = 'badge-progress';
      else if (proj.status === 'Completed') badgeClass = 'badge-done';
      else if (proj.status === 'Awaiting Approval') badgeClass = 'badge-await';

      return `
        <div class="list-row">
          <div>
            <p class="row-title">${escapeHtml(proj.title)} <span class="badge ${badgeClass}">${escapeHtml(proj.status)}</span></p>
            <div class="row-meta">
              <span>Posted ${escapeHtml(proj.postedDate || 'recently')}</span>
              <span>${proj.proposalsCount || 0} Proposals</span>
              ${proj.freelancer ? `<span>Freelancer: ${escapeHtml(proj.freelancer)}</span>` : ''}
            </div>
          </div>
          <div class="price">
            <strong>${escapeHtml(proj.budgetType || 'Fixed Price')}</strong> ${escapeHtml(proj.budget || '$3,000')}
            <a class="btn btn-outline" style="margin-left:10px" href="review-proposal.html?id=${proj.id}">View</a>
          </div>
        </div>
      `;
    }).join('');
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  loadDashboard();
});
