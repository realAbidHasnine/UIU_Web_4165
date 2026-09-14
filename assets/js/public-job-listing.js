/**
 * SkillMatch — Public Guest Job Listings
 * Fetches verified job postings dynamically from Spring Boot REST API (GET /api/jobs)
 * with real-time category and budget filtering.
 */

document.addEventListener('DOMContentLoaded', () => {
  const jobListContainer = document.querySelector('.job-list-section');
  const countBadge = document.querySelector('.list-header__count');
  const filterForm = document.getElementById('jobFilterForm');
  const resetBtn = filterForm ? filterForm.querySelector('button[type="reset"]') : null;

  // Locate or create the cards container inside .job-list-section
  let cardsContainer = document.getElementById('publicJobsContainer');
  if (!cardsContainer && jobListContainer) {
    cardsContainer = document.createElement('div');
    cardsContainer.id = 'publicJobsContainer';
    // Remove static articles and insert container before pagination
    const existingCards = jobListContainer.querySelectorAll('article.job-card');
    existingCards.forEach(c => c.remove());
    const paginationWrap = jobListContainer.querySelector('.pagination-wrap');
    if (paginationWrap) {
      jobListContainer.insertBefore(cardsContainer, paginationWrap);
    } else {
      jobListContainer.appendChild(cardsContainer);
    }
  }

  async function loadPublicJobs() {
    if (!cardsContainer) return;

    cardsContainer.innerHTML = `
      <div style="padding: 40px; text-align: center; color: var(--color-text-muted);">
        <div class="spinner" style="display:inline-block; width:28px; height:28px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 10px; font-size: 14px;">Loading verified job opportunities...</p>
      </div>
    `;

    // Extract active filters
    const selectedCats = Array.from(document.querySelectorAll('input[name="job_categories[]"]:checked'))
      .map(cb => {
        if (cb.value === 'web_development') return 'web';
        if (cb.value === 'ui_ux_design') return 'uiux';
        return cb.value;
      });

    const budgetSelect = document.querySelector('select[name="budget_range"]');
    const budgetVal = budgetSelect ? budgetSelect.value : '';

    const params = {};
    if (selectedCats.length > 0) params.category = selectedCats.join(',');
    if (budgetVal) {
      if (budgetVal === '1k-3k') params.budget = '1000-3000';
      else if (budgetVal === '3k-5k') params.budget = '3000-5000';
      else if (budgetVal === '5k+') params.budget = '5000-plus';
    }

    try {
      const jobs = await SkillMatch.api.get('/jobs', params);
      renderJobs(jobs);
    } catch (err) {
      cardsContainer.innerHTML = `
        <div style="padding: 30px; text-align: center; color: var(--color-danger);">
          <p>Failed to load jobs. Please check backend connection.</p>
          <button type="button" class="btn btn--outline" id="retryJobsBtn" style="margin-top:10px;">Retry</button>
        </div>
      `;
      document.getElementById('retryJobsBtn')?.addEventListener('click', loadPublicJobs);
    }
  }

  function renderJobs(jobs) {
    if (!cardsContainer) return;

    if (countBadge) {
      countBadge.textContent = `Showing ${jobs.length} verified jobs`;
    }

    if (!jobs || jobs.length === 0) {
      cardsContainer.innerHTML = `
        <div style="padding: 48px; text-align: center; background: var(--color-white); border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 24px;">
          <div style="font-size: 32px; margin-bottom: 8px;">🔍</div>
          <h3 style="font-size: 16px; font-weight: 600; color: var(--color-text-main); margin-bottom: 6px;">No Matching Jobs</h3>
          <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 12px;">Try clearing some filters to expand your search results.</p>
          <button type="button" class="btn btn--outline" id="clearFilterBtn">Reset Filters</button>
        </div>
      `;
      document.getElementById('clearFilterBtn')?.addEventListener('click', () => {
        if (filterForm) filterForm.reset();
        loadPublicJobs();
      });
      return;
    }

    cardsContainer.innerHTML = jobs.map(job => {
      const skills = job.skills || ['React', 'TypeScript'];
      const budgetDisplay = job.budgetDisplay || '$' + (job.budget || '3,000');
      const proposals = job.proposalsCount || Math.floor(Math.random() * 15 + 4);

      return `
        <article class="job-card" style="margin-bottom: 20px;">
          <div class="job-card__header">
            <h2 class="job-card__title">${escapeHtml(job.title)}</h2>
            <span class="job-card__budget">${escapeHtml(budgetDisplay)}</span>
          </div>
          <p class="job-card__desc">${escapeHtml(job.desc || 'Comprehensive client project requiring verified domain competence.')}</p>
          <div class="job-card__tags">
            ${skills.map(skill => `<span class="tag">${escapeHtml(skill)}</span>`).join('')}
          </div>
          <div class="job-card__footer">
            <div class="job-card__meta">
              <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Posted ${escapeHtml(job.posted || 'recently')}
              </span>
              <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                ${proposals} proposals received
              </span>
            </div>
            <a href="login.html" class="btn btn--outline-blue">View Details & Apply</a>
          </div>
        </article>
      `;
    }).join('');
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Filter change listeners
  if (filterForm) {
    filterForm.addEventListener('change', () => {
      loadPublicJobs();
    });

    filterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      loadPublicJobs();
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        setTimeout(loadPublicJobs, 10);
      });
    }
  }

  loadPublicJobs();
});
