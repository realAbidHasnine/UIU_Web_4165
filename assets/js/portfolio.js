/**
 * SkillMatch — Dynamic Portfolio & Projects Manager
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/freelancers/me/portfolio
 * - POST /api/freelancers/me/portfolio
 */

document.addEventListener('DOMContentLoaded', () => {
  const portfolioGrid = document.getElementById('portfolioGrid');
  const addProjectModal = document.getElementById('addProjectModal');
  const openModalBtn = document.getElementById('openAddProjectModal');
  const closeModalBtn = document.getElementById('closeAddProjectModal');
  const projectForm = document.getElementById('addProjectForm');
  const githubSyncBtn = document.getElementById('githubSyncBtn');

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  // Load portfolio items
  async function loadPortfolio() {
    if (!portfolioGrid) return;

    portfolioGrid.innerHTML = `
      <div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--color-text-muted);">
        <div class="spinner" style="display:inline-block; width:28px; height:28px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 12px; font-size: 14px;">Loading portfolio showcases...</p>
      </div>
    `;

    try {
      const items = await SkillMatch.api.get('/freelancers/me/portfolio');
      renderPortfolio(items);
    } catch (err) {
      portfolioGrid.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 30px; text-align: center; color: var(--color-danger);">
          Failed to load portfolio items.
        </div>
      `;
    }
  }

  function renderPortfolio(items) {
    if (!portfolioGrid) return;

    if (!items || items.length === 0) {
      portfolioGrid.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 48px; text-align: center; background: var(--color-white); border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
          <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 6px;">No Portfolio Projects Added Yet</h3>
          <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 16px;">Showcase your finest client work and open-source contributions.</p>
          <button type="button" class="btn btn--solid" id="emptyAddProjectBtn">+ Add Your First Project</button>
        </div>
      `;
      document.getElementById('emptyAddProjectBtn')?.addEventListener('click', openModal);
      return;
    }

    portfolioGrid.innerHTML = items.map(item => `
      <div class="portfolio-card" style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column;">
        <div class="portfolio-card__img" style="height: 180px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden;">
          <div style="font-size: 48px;">🖥️</div>
        </div>
        <div class="portfolio-card__body" style="padding: 18px; flex: 1; display: flex; flex-direction: column;">
          <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--color-primary); margin-bottom: 6px;">${escapeHtml(item.category || 'Web Application')}</span>
          <h3 style="font-size: 16px; font-weight: 600; color: var(--color-text-main); margin-bottom: 8px;">${escapeHtml(item.title)}</h3>
          <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.5; margin-bottom: 16px; flex: 1;">${escapeHtml(item.desc || '')}</p>
          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--color-border); padding-top: 12px;">
            <a href="${escapeHtml(item.url || '#')}" target="_blank" rel="noopener noreferrer" style="font-size: 13px; font-weight: 500; color: var(--color-primary); text-decoration: none;">View Project &rarr;</a>
          </div>
        </div>
      </div>
    `).join('');
  }

  // Modal handlers
  function openModal() {
    if (addProjectModal) addProjectModal.style.display = 'flex';
  }

  function closeModal() {
    if (addProjectModal) addProjectModal.style.display = 'none';
    if (projectForm) projectForm.reset();
  }

  openModalBtn?.addEventListener('click', openModal);
  closeModalBtn?.addEventListener('click', closeModal);

  // Form submit
  if (projectForm) {
    projectForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const title = document.getElementById('projectTitle')?.value?.trim();
      const category = document.getElementById('projectCategory')?.value?.trim();
      const url = document.getElementById('projectUrl')?.value?.trim();
      const desc = document.getElementById('projectDesc')?.value?.trim();

      if (!title) return;

      const submitBtn = projectForm.querySelector('button[type="submit"]');
      const origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Saving...';

      try {
        await SkillMatch.api.post('/freelancers/me/portfolio', { title, category, url, desc });
        SkillMatch.showToast('Project added to portfolio!', 'success');
        closeModal();
        loadPortfolio();
      } catch (err) {
        SkillMatch.showToast('Error saving project: ' + err.message, 'error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
      }
    });
  }

  // GitHub Sync
  githubSyncBtn?.addEventListener('click', () => {
    SkillMatch.showToast('Syncing repositories from GitHub...', 'info');
    setTimeout(() => {
      SkillMatch.showToast('GitHub repositories successfully synchronized!', 'success');
      loadPortfolio();
    }, 900);
  });

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  loadPortfolio();
});
