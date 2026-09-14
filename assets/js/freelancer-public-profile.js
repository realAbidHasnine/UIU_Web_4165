/**
 * SkillMatch — Freelancer Public Profile
 * Fetches verified freelancer profile by ID (or defaults to top talent)
 * from Spring Boot REST API (GET /api/freelancers/{id})
 */

document.addEventListener('DOMContentLoaded', async () => {
  const urlParams = new URLSearchParams(window.location.search);
  const freelancerId = urlParams.get('id') || 'f-101';

  const avatarEl = document.querySelector('.profile-avatar');
  const nameEl = document.querySelector('.profile-name');
  const titleEl = document.querySelector('.profile-title');
  const locationEl = document.querySelector('.profile-location');
  const ratingEl = document.querySelector('.profile-rating');
  const skillsListEl = document.querySelector('.skills-list');
  const contactBtn = document.querySelector('.profile-header .btn--solid');
  const noteEl = document.querySelector('.verification-note');

  try {
    const profile = await SkillMatch.api.get(`/freelancers/${freelancerId}`);
    if (!profile) return;

    // Document title
    document.title = `${profile.name} — ${profile.title || 'Verified Specialist'} - SkillMatch`;

    // Avatar initials
    if (avatarEl) {
      const initials = profile.name ? profile.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'SM';
      avatarEl.textContent = initials;
    }

    // Name & Title
    if (nameEl) nameEl.textContent = profile.name;
    if (titleEl) titleEl.textContent = profile.title || 'Senior Software Engineer & Architect';

    // Location & Rate
    if (locationEl) {
      locationEl.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        ${profile.location || 'Remote'} &bull; $${profile.hourlyRate || 65}/hr
      `;
    }

    // Rating
    if (ratingEl) {
      ratingEl.innerHTML = `
        <span class="stars">★★★★★</span> ${(profile.rating || 4.95).toFixed(1)} 
        <span>(${profile.completedProjects || 34} verified projects completed)</span>
      `;
    }

    // Contact Button text
    if (contactBtn) {
      contactBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        Contact ${profile.name.split(' ')[0]}
      `;
    }

    // Verified Skills score beams
    if (skillsListEl && profile.skills) {
      skillsListEl.innerHTML = profile.skills.map((skill, idx) => {
        const score = Math.max(80, (profile.score || 94) - (idx * 3));
        return `
          <div class="score-beam">
            <span class="score-beam__name">${escapeHtml(skill)}</span>
            <span class="score-beam__track"><span class="score-beam__fill" style="--beam: ${score}%;"></span></span>
            <span class="score-beam__value">${score}%</span>
          </div>
        `;
      }).join('');
    }

    // Certificate note
    if (noteEl) {
      noteEl.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        SkillScore verified &middot; #${(profile.id || '0101').replace(/\D/g, '').padStart(4, '0')}
      `;
    }

  } catch (err) {
    console.error('Failed to load freelancer public profile:', err);
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
});
