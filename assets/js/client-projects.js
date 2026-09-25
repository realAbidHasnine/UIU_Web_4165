/**
 * SkillMatch — Client Projects List
 * Connects client projects management to Spring Boot REST API:
 * - GET /api/clients/me/projects (with status tabs and search filtering)
 */

document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.tabs a');
  const searchInput = document.querySelector('.search');
  const mainContainer = document.querySelector('main.container');

  let currentProjects = [];
  let currentTab = 'all';

  // Create or identify projects list container
  let projectsContainer = document.getElementById('clientProjectsContainer');
  if (!projectsContainer && mainContainer) {
    projectsContainer = document.createElement('div');
    projectsContainer.id = 'clientProjectsContainer';

    // Remove static project cards
    const existingCards = mainContainer.querySelectorAll('.card');
    existingCards.forEach(c => c.remove());

    const paginationRow = mainContainer.querySelector('div[style*="justify-content:space-between"]');
    if (paginationRow) {
      mainContainer.insertBefore(projectsContainer, paginationRow);
    } else {
      mainContainer.appendChild(projectsContainer);
    }
  }

  async function loadProjects() {
    if (!projectsContainer) return;

    projectsContainer.innerHTML = `
      <div style="padding: 40px; text-align: center; color: var(--color-text-muted);">
        <div class="spinner" style="display:inline-block; width:28px; height:28px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 10px; font-size: 14px;">Loading your projects...</p>
      </div>
    `;

    try {
      const params = {};
      if (currentTab !== 'all') {
        params.status = currentTab;
      }
      currentProjects = await SkillMatch.api.get('/clients/me/projects', params);
      filterAndRender();
    } catch (err) {
      projectsContainer.innerHTML = `
        <div style="padding: 30px; text-align: center; color: var(--color-danger);">
          <p>Failed to load projects.</p>
          <button type="button" class="btn btn-outline" id="retryProjBtn" style="margin-top: 10px;">Retry</button>
        </div>
      `;
      document.getElementById('retryProjBtn')?.addEventListener('click', loadProjects);
    }
  }

  function filterAndRender() {
    if (!projectsContainer) return;

    const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    let filtered = currentProjects;

    if (query) {
      filtered = filtered.filter(p => 
        p.title.toLowerCase().includes(query) ||
        (p.freelancer && p.freelancer.toLowerCase().includes(query)) ||
        p.status.toLowerCase().includes(query)
      );
    }

    if (filtered.length === 0) {
      projectsContainer.innerHTML = `
        <div class="card" style="padding: 40px; text-align: center; color: var(--color-text-muted); margin-bottom: 20px;">
          <p>No projects found matching your criteria.</p>
          <a class="btn btn-primary" href="post-project-details.html" style="margin-top: 12px; display: inline-block;">+ Post a New Project</a>
        </div>
      `;
      return;
    }

    projectsContainer.innerHTML = filtered.map(proj => {
      let badgeClass = 'badge-open';
      if (proj.status === 'In Progress') badgeClass = 'badge-progress';
      else if (proj.status === 'Completed') badgeClass = 'badge-done';
      else if (proj.status === 'Awaiting Approval') badgeClass = 'badge-await';

      return `
        <div class="card" style="margin-bottom: 20px;">
          <div class="list-row">
            <div>
              <p class="row-title">${escapeHtml(proj.title)} <span class="badge ${badgeClass}">${escapeHtml(proj.status)}</span></p>
              <div class="row-meta">
                <span>${escapeHtml(proj.budget || '$3,000')} ${escapeHtml(proj.budgetType || 'Fixed')}</span>
                <span>${proj.proposalsCount || 0} proposals</span>
                ${proj.freelancer ? `<span>Freelancer: ${escapeHtml(proj.freelancer)}</span>` : `<span>Posted ${escapeHtml(proj.postedDate || 'recently')}</span>`}
                <span>${escapeHtml(proj.dueDate || 'Active')}</span>
              </div>
            </div>
            <a class="btn btn-outline" href="review-proposal.html?id=${proj.id}">View details</a>
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

  // Tabs click handler
  tabs.forEach(tab => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');

      const label = tab.textContent.trim().toLowerCase();
      currentTab = label === 'in progress' ? 'in progress' : label;
      loadProjects();
    });
  });

  // Search input live handler
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      filterAndRender();
    });
  }

  loadProjects();
});
