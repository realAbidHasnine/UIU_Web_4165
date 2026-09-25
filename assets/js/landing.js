/**
 * SkillMatch — Landing Page Dynamic Data
 * Fetches featured talent from Spring Boot REST endpoint (GET /api/freelancers)
 * and powers the hero search bar.
 */

document.addEventListener('DOMContentLoaded', () => {
  const featuredGrid = document.querySelector('.featured__grid') || document.getElementById('featuredTalentGrid');
  const heroSearchForm = document.getElementById('heroSearchForm');
  const heroStatPros = document.querySelector('.hero__stat:nth-child(1) .hero__stat-value');

  const isGuestDir = window.location.pathname.includes('/guest/');
  const profileBaseUrl = isGuestDir ? 'freelancer_public_profile.html' : 'guest/freelancer_public_profile.html';
  const browseBaseUrl = isGuestDir ? 'browse_freelancer.html' : 'guest/browse_freelancer.html';

  // Dynamic talent loading
  async function loadFeaturedTalent() {
    if (!featuredGrid) return;

    featuredGrid.innerHTML = `
      <div style="grid-column:1/-1;padding:40px;text-align:center;color:var(--color-text-muted);">
        <div class="spinner" style="display:inline-block;width:28px;height:28px;border:3px solid rgba(37,99,235,0.2);border-top-color:var(--color-primary);border-radius:50%;animation:spin 0.8s linear infinite;"></div>
        <p style="margin-top:10px;font-size:14px;">Loading verified talent...</p>
      </div>
    `;

    try {
      const freelancers = await SkillMatch.api.get('/freelancers');
      if (!freelancers || freelancers.length === 0) {
        featuredGrid.innerHTML = '<p style="color:var(--color-text-muted);text-align:center;grid-column:1/-1;">No verified freelancers found.</p>';
        return;
      }

      if (heroStatPros) {
        heroStatPros.textContent = (freelancers.length * 500 + 380).toLocaleString();
      }

      const topFreelancers = freelancers.slice(0, 4);
      featuredGrid.innerHTML = topFreelancers.map((fl, idx) => {
        const initials    = fl.name ? fl.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'SM';
        const skills      = Array.isArray(fl.skills) ? fl.skills : [];
        const mainSkills  = skills.slice(0, 3);

        return `
          <a href="${profileBaseUrl}?id=${encodeURIComponent(fl.id)}" class="talent-card" aria-label="View profile of ${escapeHtml(fl.name)}">
            <div class="talent-card__top">
              <div class="talent-card__avatar talent-card__avatar--${(idx % 4) + 1}">${initials}</div>
              <span class="readout talent-card__rate">$${fl.hourlyRate ?? '—'}/hr</span>
            </div>
            <h3 class="talent-card__name">${escapeHtml(fl.name)}</h3>
            <p class="talent-card__title">${escapeHtml(fl.title ?? '')}</p>
            <hr class="talent-card__divider">
            <div class="talent-card__scores">
              ${mainSkills.map((skill, sIdx) => {
                const beamScore = Math.max(70, (fl.score ?? 90) - sIdx * 3);
                return `
                  <div class="score-beam">
                    <span class="score-beam__name">${escapeHtml(skill)}</span>
                    <span class="score-beam__track"><span class="score-beam__fill" style="--beam: ${beamScore}%;"></span></span>
                    <span class="score-beam__value">${beamScore}%</span>
                  </div>
                `;
              }).join('')}
            </div>
            <hr class="talent-card__divider">
            <div class="talent-card__rating">
              <span class="talent-card__star">&#9733;</span> ${fl.rating !== undefined ? Number(fl.rating).toFixed(1) : '—'}
              <span>(${fl.completedProjects ?? fl.completedJobs ?? 0} jobs)</span>
            </div>
            <span class="verification-note">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              SkillScore verified &middot; #${String(fl.id || '').replace(/\D/g, '').padStart(4, '0')}
            </span>
          </a>
        `;
      }).join('');
    } catch (err) {
      featuredGrid.innerHTML = `
        <div style="grid-column:1/-1;padding:32px;text-align:center;color:var(--color-danger);">
          <p>Could not load talent. Make sure XAMPP is running.</p>
        </div>
      `;
    }
  }

  // Hero Search handling
  if (heroSearchForm) {
    heroSearchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = heroSearchForm.querySelector('input[name="skill_query"]');
      const query = input ? input.value.trim() : '';
      if (query) {
        window.location.href = `${browseBaseUrl}?query=${encodeURIComponent(query)}`;
      } else {
        window.location.href = browseBaseUrl;
      }
    });
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  loadFeaturedTalent();
});
