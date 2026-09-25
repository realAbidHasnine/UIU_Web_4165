/**
 * SkillMatch — Dynamic Proposals Management
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/freelancers/me/proposals
 * - DELETE /api/proposals/{id}
 */

document.addEventListener('DOMContentLoaded', () => {
  const proposalsFeed = document.getElementById('proposalsFeed');
  const tabs = document.querySelectorAll('.filter-tab');
  const modalOverlay = document.getElementById('withdrawModal');
  const confirmWithdrawBtn = document.getElementById('confirmWithdrawBtn');
  const cancelWithdrawBtn = document.getElementById('cancelWithdrawBtn');

  let activeFilter = 'all';
  let proposals = [];
  let pendingWithdrawId = null;

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  // Load proposals from API
  async function loadProposals() {
    if (!proposalsFeed) return;

    proposalsFeed.innerHTML = `
      <div style="padding: 48px; text-align: center; color: var(--color-text-muted);">
        <div class="spinner" style="display:inline-block; width:28px; height:28px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 12px; font-size: 14px;">Loading your submitted proposals...</p>
      </div>
    `;

    try {
      proposals = await SkillMatch.api.get('/freelancers/me/proposals');
      renderProposals();
    } catch (err) {
      proposalsFeed.innerHTML = `
        <div style="padding: 36px; text-align: center; color: var(--color-danger);">
          <p>Failed to load proposals.</p>
          <button type="button" class="btn btn--outline" id="retryProposals">Retry</button>
        </div>
      `;
      document.getElementById('retryProposals')?.addEventListener('click', loadProposals);
    }
  }

  // Render proposals based on active filter
  function renderProposals() {
    if (!proposalsFeed) return;

    let filtered = proposals;
    if (activeFilter !== 'all') {
      filtered = proposals.filter(p => (p.status || '').toLowerCase() === activeFilter.toLowerCase());
    }

    if (filtered.length === 0) {
      proposalsFeed.innerHTML = `
        <div style="padding: 48px; text-align: center; background: var(--color-white); border-radius: var(--radius-lg); border: 1px solid var(--color-border);">
          <div style="font-size: 32px; margin-bottom: 8px;">📋</div>
          <h3 style="font-size: 16px; font-weight: 600; color: var(--color-text-main); margin-bottom: 4px;">No ${activeFilter === 'all' ? '' : activeFilter} Proposals Found</h3>
          <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 16px;">Browse open jobs and submit tailored proposals to win new contracts.</p>
          <a href="job_listing.html" class="btn btn--solid">Explore Jobs &rarr;</a>
        </div>
      `;
      return;
    }

    proposalsFeed.innerHTML = filtered.map(prop => {
      const statusLower = (prop.status || 'pending').toLowerCase();
      let badgeClass = 'badge--pending';
      if (statusLower === 'active' || statusLower === 'accepted') badgeClass = 'badge--accepted';
      if (statusLower === 'declined' || statusLower === 'archived') badgeClass = 'badge--declined';

      const dateStr = new Date(prop.submittedAt || Date.now()).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      });

      return `
        <article class="proposal-card" data-proposal-id="${prop.id}" data-status="${statusLower}">
          <div class="proposal-card__header">
            <div>
              <h2 class="proposal-card__title">${escapeHtml(prop.jobTitle)}</h2>
              <div class="proposal-card__meta">
                <span>Client: <strong>${escapeHtml(prop.clientName || 'Confidential')}</strong></span> &bull;
                <span>Submitted on ${dateStr}</span>
              </div>
            </div>
            <span class="badge ${badgeClass}">${escapeHtml(prop.status)}</span>
          </div>

          <p class="proposal-card__cover">${escapeHtml(prop.coverLetter || 'No cover letter provided.')}</p>

          <div class="proposal-card__footer">
            <div class="proposal-card__terms">
              <span>Proposed Rate: <strong>$${prop.proposedRate}</strong></span>
              <span>Delivery: <strong>${prop.estimatedDays ? prop.estimatedDays + ' days' : '1-3 months'}</strong></span>
            </div>
            <div class="proposal-card__actions">
              <a href="freelancer_chat.html" class="btn btn--outline">Message Client</a>
              ${statusLower !== 'declined' && statusLower !== 'archived' ? `
                <button type="button" class="btn btn--danger btn-withdraw" data-id="${prop.id}">Withdraw</button>
              ` : ''}
            </div>
          </div>
        </article>
      `;
    }).join('');

    // Attach withdraw listeners
    proposalsFeed.querySelectorAll('.btn-withdraw').forEach(btn => {
      btn.addEventListener('click', () => {
        pendingWithdrawId = btn.getAttribute('data-id');
        openWithdrawModal();
      });
    });
  }

  // Filter tabs
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('filter-tab--active'));
      tab.classList.add('filter-tab--active');
      activeFilter = tab.getAttribute('data-filter') || 'all';
      renderProposals();
    });
  });

  // Modal actions
  function openWithdrawModal() {
    if (modalOverlay) modalOverlay.style.display = 'flex';
  }

  function closeWithdrawModal() {
    if (modalOverlay) modalOverlay.style.display = 'none';
    pendingWithdrawId = null;
  }

  cancelWithdrawBtn?.addEventListener('click', closeWithdrawModal);

  confirmWithdrawBtn?.addEventListener('click', async () => {
    if (!pendingWithdrawId) return;

    const id = pendingWithdrawId;
    closeWithdrawModal();

    try {
      await SkillMatch.api.delete(`/proposals/${id}`);
      proposals = proposals.filter(p => p.id !== id);
      renderProposals();
      SkillMatch.showToast('Proposal withdrawn successfully.', 'info');
    } catch (err) {
      SkillMatch.showToast('Failed to withdraw proposal: ' + err.message, 'error');
    }
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

  loadProposals();
});
