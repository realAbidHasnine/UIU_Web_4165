/**
 * SkillMatch — Dynamic Freelancers Directory
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/freelancers
 */

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('freelancersGrid') || document.querySelector('.freelancer-list') || document.getElementById('freelancerList');
  const searchInput = document.getElementById('searchFreelancers') || document.querySelector('input[type="search"]') || document.getElementById('keyword');
  const sortSelect = document.getElementById('sortSelect') || document.getElementById('sort');

  let allFreelancers = [];

  async function loadFreelancers() {
    if (!container) return;

    container.innerHTML = `
      <div style="grid-column: 1 / -1; padding: 48px; text-align: center; color: var(--color-text-muted);">
        <div class="spinner" style="display:inline-block; width:32px; height:32px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 12px; font-size: 14px;">Loading verified talent network...</p>
      </div>
    `;

    try {
      allFreelancers = await SkillMatch.api.get('/freelancers');
      renderFreelancers(allFreelancers);
    } catch (err) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 32px; text-align: center; color: var(--color-danger);">
          Failed to load freelancers.
        </div>
      `;
    }
  }

  function renderFreelancers(list) {
    if (!container) return;

    if (!list || list.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 40px; text-align: center; background: white; border-radius: 8px; border: 1px solid var(--color-border);">
          <h3>No freelancers found matching your criteria.</h3>
        </div>
      `;
      return;
    }

    container.innerHTML = list.map(f => `
      <article class="freelancer-card" style="background: white; border: 1px solid var(--color-border); border-radius: 12px; padding: 24px; margin-bottom: 20px; transition: all 0.2s ease;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
          <div style="display: flex; gap: 16px; align-items: center;">
            <div class="avatar" style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-primary, #2563eb); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">
              ${getInitials(f.name)}
            </div>
            <div>
              <h3 style="font-size: 18px; font-weight: 600; margin: 0 0 4px 0; color: var(--color-text-main);">
                ${escapeHtml(f.name)}
                ${f.score >= 90 ? `<span style="margin-left: 8px; font-size: 12px; padding: 2px 8px; border-radius: 12px; background: #dbeafe; color: #1e40af;">★ Verified ${f.score}%</span>` : ''}
              </h3>
              <p style="font-size: 14px; color: var(--color-text-muted); margin: 0;">${escapeHtml(f.title)}</p>
            </div>
          </div>
          <div style="text-align: right;">
            <span style="font-size: 20px; font-weight: 700; color: var(--color-text-main);">$${f.hourlyRate}</span>
            <span style="font-size: 12px; color: var(--color-text-muted);">/ hr</span>
          </div>
        </div>

        <p style="font-size: 14px; line-height: 1.5; color: var(--color-text-main); margin: 16px 0;">
          ${escapeHtml(f.bio)}
        </p>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--color-border); padding-top: 14px; margin-top: 14px;">
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            ${(f.skills || []).map(s => `<span style="font-size: 12px; padding: 3px 10px; border-radius: 6px; background: #f1f5f9; color: #475569;">${escapeHtml(s)}</span>`).join('')}
          </div>
          <a href="freelancer_public_profile.html?id=${f.id}" class="btn btn--outline" style="text-decoration: none; font-size: 13px; padding: 6px 14px;">View Profile</a>
        </div>
      </article>
    `).join('');
  }

  // Live search
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase().trim();
      const filtered = allFreelancers.filter(f =>
        f.name.toLowerCase().includes(q) ||
        f.title.toLowerCase().includes(q) ||
        (f.skills || []).some(s => s.toLowerCase().includes(q))
      );
      renderFreelancers(filtered);
    });
  }

  function getInitials(name) {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
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

  loadFreelancers();
});
