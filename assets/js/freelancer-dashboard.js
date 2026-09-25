/**
 * SkillMatch — Freelancer Dashboard Controller
 * All data fetched live from PHP REST endpoints:
 * - GET /api/freelancers/me
 * - GET /api/freelancers/me/proposals
 */

document.addEventListener('DOMContentLoaded', async () => {
  const welcomeTitle       = document.getElementById('welcomeTitle');
  const welcomeSubtitle    = document.getElementById('welcomeSubtitle');
  const statActiveProjects = document.getElementById('statActiveProjects');
  const statPendingProposals = document.getElementById('statPendingProposals');
  const statEarnings       = document.getElementById('statEarnings');
  const statRating         = document.getElementById('statRating');
  const navAvatar          = document.querySelector('.navbar__avatar');

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu   = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  // Show loading state in stat cards
  [statActiveProjects, statPendingProposals, statEarnings, statRating].forEach(el => {
    if (el) el.textContent = '—';
  });

  try {
    const [user, proposals] = await Promise.all([
      SkillMatch.api.get('/freelancers/me'),
      SkillMatch.api.get('/freelancers/me/proposals')
    ]);

    if (user) {
      const firstName = (user.name || 'Freelancer').split(' ')[0];
      const activeCount = proposals.filter(p =>
        ['submitted', 'active', 'accepted'].includes((p.status || '').toLowerCase())
      ).length;

      if (welcomeTitle) {
        welcomeTitle.textContent = `Welcome back, ${firstName}`;
      }
      if (welcomeSubtitle) {
        welcomeSubtitle.textContent =
          `You have ${activeCount} active proposal${activeCount !== 1 ? 's' : ''} and a ${user.verifiedScore ?? user.score ?? 0}% verified skill score.`;
      }
      if (navAvatar && user.name) {
        const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        navAvatar.textContent = initials;
      }

      if (statActiveProjects)   statActiveProjects.textContent   = user.completedJobs ?? '0';
      if (statPendingProposals) statPendingProposals.textContent  = proposals.length;
      if (statEarnings)         statEarnings.textContent          = `$${Number(user.earnings ?? 0).toLocaleString()}`;
      if (statRating)           statRating.textContent            = `${user.verifiedScore ?? user.score ?? 0}%`;
    }
  } catch (err) {
    console.error('Failed to load dashboard metrics:', err);
    if (welcomeSubtitle) {
      welcomeSubtitle.textContent = 'Could not load dashboard data. Ensure XAMPP and MySQL are running.';
      welcomeSubtitle.style.color = 'var(--color-danger, #ef4444)';
    }
    [statActiveProjects, statPendingProposals, statEarnings, statRating].forEach(el => {
      if (el) el.textContent = 'Error';
    });
    SkillMatch.showToast('Dashboard data unavailable — check backend connection.', 'error');
  }
});
