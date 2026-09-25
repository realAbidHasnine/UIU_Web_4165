/**
 * SkillMatch — Freelancer Job Listings & Dynamic Proposals
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/jobs (with category, budget, duration filters)
 * - GET /api/jobs/{id}
 * - POST /api/jobs/{id}/proposals
 */

document.addEventListener('DOMContentLoaded', () => {
  const jobFeed = document.getElementById('jobFeed');
  const detailsSidebar = document.getElementById('detailsSidebar');
  const detailsContent = document.getElementById('jobDetailsContent');
  const proposalForm = document.getElementById('jobProposalForm');
  const proposedRateInput = document.getElementById('proposedRate');
  const filtersForm = document.getElementById('filtersForm');
  const resetFiltersBtn = document.getElementById('resetFilters');

  let currentJobs = [];
  let selectedJob = null;

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  // Load and render jobs
  async function loadJobs() {
    if (!jobFeed) return;

    jobFeed.innerHTML = `
      <div class="loading-state" style="padding: 40px; text-align: center; color: var(--color-text-muted);">
        <div class="spinner" style="display:inline-block; width:32px; height:32px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 12px; font-size: 14px;">Loading available opportunities...</p>
      </div>
    `;

    // Collect active filter values
    const selectedCategories = Array.from(document.querySelectorAll('.filter-category:checked'))
      .map(cb => cb.value)
      .join(',');

    const budget = document.getElementById('filterBudget')?.value || 'any';
    const duration = document.getElementById('filterLength')?.value || 'any';

    const params = {};
    if (selectedCategories) params.category = selectedCategories;
    if (budget !== 'any') params.budget = budget;
    if (duration !== 'any') params.duration = duration;

    try {
      currentJobs = await SkillMatch.api.get('/jobs', params);
      renderJobList(currentJobs);
    } catch (err) {
      jobFeed.innerHTML = `
        <div class="empty-state" style="padding: 40px; text-align: center;">
          <p style="color: var(--color-danger); margin-bottom: 8px;">Failed to load jobs.</p>
          <button type="button" class="btn btn--outline" id="retryLoadJobs">Retry</button>
        </div>
      `;
      document.getElementById('retryLoadJobs')?.addEventListener('click', loadJobs);
    }
  }

  // Render job cards feed
  function renderJobList(jobs) {
    if (!jobFeed) return;

    if (!jobs || jobs.length === 0) {
      jobFeed.innerHTML = `
        <div class="empty-state" style="padding: 48px; text-align: center; background: var(--color-white); border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
          <div style="font-size: 36px; margin-bottom: 12px;">🔍</div>
          <h3 style="font-size: 18px; font-weight: 600; color: var(--color-text-main); margin-bottom: 6px;">No Matching Jobs Found</h3>
          <p style="color: var(--color-text-muted); font-size: 14px; max-width: 320px; margin: 0 auto 16px;">Try adjusting your category checkboxes or budget range to see more projects.</p>
          <button type="button" class="btn btn--solid" id="emptyResetBtn">Reset Filters</button>
        </div>
      `;
      document.getElementById('emptyResetBtn')?.addEventListener('click', () => {
        resetAllFilters();
      });
      if (detailsContent) {
        detailsContent.innerHTML = `
          <div style="padding: 40px; text-align: center; color: var(--color-text-muted);">
            <p>Select a job from the list to view specifications and submit your proposal.</p>
          </div>
        `;
      }
      return;
    }

    jobFeed.innerHTML = jobs.map((job, index) => `
      <article class="job-card ${selectedJob && selectedJob.id === job.id ? 'job-card--active' : (index === 0 && !selectedJob ? 'job-card--active' : '')}" 
               data-job-id="${job.id}" tabindex="0" role="button" aria-label="Select job: ${job.title}">
        <div class="job-card__header">
          <h3 class="job-card__title">${escapeHtml(job.title)}</h3>
          <span class="tag-budget">${escapeHtml(job.budgetDisplay || '$' + job.budget)}</span>
        </div>
        <p class="job-card__desc">${escapeHtml(job.desc)}</p>
        <div class="job-card__footer">
          <div class="tag-group">
            ${(job.skills || []).slice(0, 4).map(skill => `<span class="tag-skill">${escapeHtml(skill)}</span>`).join('')}
          </div>
          <button type="button" class="btn ${selectedJob && selectedJob.id === job.id ? 'btn--solid' : (index === 0 && !selectedJob ? 'btn--solid' : 'btn--outline')} btn-select-job">
            Apply Now
          </button>
        </div>
      </article>
    `).join('');

    // Attach click events
    jobFeed.querySelectorAll('.job-card').forEach(card => {
      card.addEventListener('click', () => {
        const jobId = card.getAttribute('data-job-id');
        const found = jobs.find(j => j.id === jobId);
        if (found) {
          selectJob(found, card);
        }
      });
    });

    // Auto-select first job if none selected
    if (!selectedJob || !jobs.some(j => j.id === selectedJob.id)) {
      selectJob(jobs[0], jobFeed.querySelector('.job-card'));
    }
  }

  // Select a job and populate detail sidebar
  function selectJob(job, cardEl) {
    selectedJob = job;

    // Highlight active card
    jobFeed.querySelectorAll('.job-card').forEach(c => {
      c.classList.remove('job-card--active');
      const btn = c.querySelector('.btn-select-job');
      if (btn) {
        btn.classList.remove('btn--solid');
        btn.classList.add('btn--outline');
      }
    });

    if (cardEl) {
      cardEl.classList.add('job-card--active');
      const btn = cardEl.querySelector('.btn-select-job');
      if (btn) {
        btn.classList.remove('btn--outline');
        btn.classList.add('btn--solid');
      }
    }

    // Populate sidebar details
    if (detailsContent) {
      detailsContent.innerHTML = `
        <h1 class="job-details__title" id="detailTitle">${escapeHtml(job.title)}</h1>
        <div class="job-details__meta">
          <span class="tag-budget" id="detailBudget">${escapeHtml(job.budgetDisplay || '$' + job.budget)}</span>
          <span class="job-details__time" id="detailTime">Posted ${escapeHtml(job.posted || 'Recently')} • ${escapeHtml(job.company || 'Verified Client')}</span>
        </div>
        <div class="tag-group" id="detailSkills">
          ${(job.skills || []).map(skill => `<span class="tag-skill">${escapeHtml(skill)}</span>`).join('')}
        </div>
        <div class="job-details__section">
          <h3 class="job-details__subtitle">Job Description</h3>
          <p class="job-details__text" id="detailDesc">${escapeHtml(job.desc)}</p>
        </div>
        <div class="job-details__section">
          <h3 class="job-details__subtitle">Key Responsibilities</h3>
          <ul class="job-details__list" id="detailResponsibilities">
            ${(job.responsibilities || []).map(item => `<li>${escapeHtml(item)}</li>`).join('')}
          </ul>
        </div>
      `;
    }

    if (proposedRateInput && job.suggestedRate) {
      proposedRateInput.value = job.suggestedRate;
    }

    // On mobile, scroll to sidebar smoothly
    if (window.innerWidth <= 1120 && detailsSidebar) {
      detailsSidebar.scrollIntoView({ behavior: 'smooth' });
    }
  }

  // Filter change listeners
  document.querySelectorAll('.filter-category').forEach(cb => {
    cb.addEventListener('change', () => loadJobs());
  });

  document.getElementById('filterBudget')?.addEventListener('change', () => loadJobs());
  document.getElementById('filterLength')?.addEventListener('change', () => loadJobs());

  function resetAllFilters() {
    document.querySelectorAll('.filter-category').forEach(cb => {
      cb.checked = true;
    });
    const b = document.getElementById('filterBudget');
    if (b) b.value = 'any';
    const l = document.getElementById('filterLength');
    if (l) l.value = 'any';
    loadJobs();
    SkillMatch.showToast('Job filters have been reset', 'info');
  }

  resetFiltersBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    resetAllFilters();
  });

  // Handle Proposal Submission via REST API
  if (proposalForm) {
    proposalForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!selectedJob) {
        SkillMatch.showToast('Please select an active job first.', 'error');
        return;
      }

      const submitBtn = proposalForm.querySelector('.btn-submit');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = `
        <span class="spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;vertical-align:middle;margin-right:6px;"></span>
        Submitting...
      `;

      const coverLetter = document.getElementById('coverLetter')?.value || '';
      const proposedRate = document.getElementById('proposedRate')?.value || selectedJob.suggestedRate;
      const estimatedDuration = document.getElementById('estimatedDuration')?.value || '1to3';

      const payload = {
        jobId: selectedJob.id,
        jobTitle: selectedJob.title,
        coverLetter,
        proposedRate: Number(proposedRate),
        estimatedDuration
      };

      try {
        const res = await SkillMatch.api.post(`/jobs/${selectedJob.id}/proposals`, payload);
        SkillMatch.showToast(`Proposal successfully submitted for "${selectedJob.title}"!`, 'success');
        proposalForm.reset();
        if (proposedRateInput && selectedJob.suggestedRate) {
          proposedRateInput.value = selectedJob.suggestedRate;
        }
      } catch (err) {
        SkillMatch.showToast(err.message || 'Failed to submit proposal. Please try again.', 'error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });
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
  loadJobs();
});
