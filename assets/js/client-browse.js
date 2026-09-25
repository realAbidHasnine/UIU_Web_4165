/**
 * SkillMatch — Dynamic Client Freelancer Discovery
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/freelancers
 */

document.addEventListener('DOMContentLoaded', () => {
  const freelGrid = document.querySelector('.freel-grid');
  const searchInput = document.querySelector('input[name="freelancer_search"]');
  const categoryBoxes = document.querySelectorAll('input[name="skill_filter[]"]');

  let freelancers = [];

  async function loadFreelancers() {
    if (!freelGrid) return;

    freelGrid.innerHTML = `
      <div style="grid-column: 1 / -1; padding: 48px; text-align: center; color: var(--muted);">
        <div class="spinner" style="display:inline-block; width:28px; height:28px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 12px; font-size: 14px;">Loading verified talent...</p>
      </div>
    `;

    try {
      freelancers = await SkillMatch.api.get('/freelancers');
      renderFreelancers(freelancers);
    } catch (err) {
      freelGrid.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 32px; text-align: center; color: var(--color-danger);">
          Failed to load freelancers.
        </div>
      `;
    }
  }

  function renderFreelancers(list) {
    if (!freelGrid) return;

    if (!list || list.length === 0) {
      freelGrid.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 40px; text-align: center; background: white; border-radius: 8px; border: 1px solid var(--border);">
          <p>No verified freelancers found matching your criteria.</p>
        </div>
      `;
      return;
    }

    freelGrid.innerHTML = list.map(f => {
      const initials = getInitials(f.name);
      return `
        <div class="card freel-card">
          <div style="display:flex; gap:12px; align-items:center;">
            <span class="avatar-lg" style="color:var(--primary-ink); display:flex; align-items:center; justify-content:center; background:#e0e7ff; border-radius:50%; width:48px; height:48px; font-weight:bold;">${initials}</span>
            <div style="flex:1;">
              <strong>${escapeHtml(f.name)}</strong>
              <div style="color:var(--muted); font-size:13px;">${escapeHtml(f.title)}</div>
            </div>
            <span class="badge badge-match-dark">${f.score}% Verified</span>
          </div>

          <div style="margin:12px 0;">
            ${(f.skills || []).map(s => `<span class="skill">${escapeHtml(s)}</span>`).join('')}
          </div>

          <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:12px;">
            <span>&#9733; ${f.rating || 4.9} (${f.completedProjects || 30}+ jobs)</span>
            <strong>$${f.hourlyRate}/hr</strong>
          </div>

          <hr class="hr">
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px;">
            <a class="btn btn-outline" href="freelancer-profile.html?id=${f.id}">View Profile</a>
            <a class="btn btn-primary" href="client-chat.html?freelancerId=${f.id}">Invite to Project</a>
          </div>
        </div>
      `;
    }).join('');
  }

  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase().trim();
      const filtered = freelancers.filter(f =>
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
