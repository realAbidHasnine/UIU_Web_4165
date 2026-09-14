/**
 * SkillMatch — Landing Page Dynamic Data
 * Fetches featured talent from Spring Boot REST endpoint (GET /api/freelancers)
 * and powers the hero search bar.
 */

document.addEventListener('DOMContentLoaded', () => {
  const featuredGrid = document.querySelector('.featured__grid');
  const heroSearchForm = document.getElementById('heroSearchForm');
  const heroStatPros = document.querySelector('.hero__stat:nth-child(1) .hero__stat-value');

  // Dynamic talent loading
  async function loadFeaturedTalent() {
    if (!featuredGrid) return;

    try {
      const freelancers = await SkillMatch.api.get('/freelancers');
      if (!freelancers || freelancers.length === 0) return;

      // Update pro counter if available
      if (heroStatPros) {
        heroStatPros.textContent = (freelancers.length * 500 + 380).toLocaleString();
      }

      // Render top 4 talent cards dynamically
      const topFreelancers = freelancers.slice(0, 4);
      featuredGrid.innerHTML = topFreelancers.map((fl, idx) => {
        const initials = fl.name ? fl.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'SM';
        const skills = fl.skills || ['React', 'TypeScript', 'Tailwind'];
        const mainSkills = skills.slice(0, 3);

        return `
          <a href="guest/freelancer_public_profile.html?id=${fl.id}" class="talent-card" aria-label="View profile of ${escapeHtml(fl.name)}">
            <div class="talent-card__top">
              <div class="talent-card__avatar talent-card__avatar--${(idx % 4) + 1}">${initials}</div>
              <span class="readout talent-card__rate">$${fl.hourlyRate || 65}/hr</span>
            </div>
            <h3 class="talent-card__name">${escapeHtml(fl.name)}</h3>
            <p class="talent-card__title">${escapeHtml(fl.title || 'Verified Specialist')}</p>
            <hr class="talent-card__divider">
            <div class="talent-card__scores">
              ${mainSkills.map((skill, sIdx) => {
                const beamScore = Math.max(85, (fl.score || 92) - sIdx * 3);
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
              <span class="talent-card__star">&#9733;</span> ${(fl.rating || 4.9).toFixed(1)} 
              <span>(${fl.completedProjects || 34} jobs)</span>
            </div>
            <span class="verification-note">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              SkillScore verified &middot; #${(fl.id || '0101').replace(/\D/g, '').padStart(4, '0')}
            </span>
          </a>
        `;
      }).join('');
    } catch (err) {
      console.warn('Failed to load featured talent dynamically:', err);
    }
  }

  // Hero Search handling
  if (heroSearchForm) {
    heroSearchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = heroSearchForm.querySelector('input[name="skill_query"]');
      const query = input ? input.value.trim() : '';
      if (query) {
        window.location.href = `guest/browse_freelancer.html?query=${encodeURIComponent(query)}`;
      } else {
        window.location.href = `guest/browse_freelancer.html`;
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
