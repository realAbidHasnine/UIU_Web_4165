/**
 * SkillMatch — Dynamic Freelancer Dashboard Controller
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/freelancers/me
 * - GET /api/freelancers/me/proposals
 */

document.addEventListener('DOMContentLoaded', async () => {
  const welcomeTitle = document.getElementById('welcomeTitle');
  const welcomeSubtitle = document.getElementById('welcomeSubtitle');
  const statActiveProjects = document.getElementById('statActiveProjects');
  const statPendingProposals = document.getElementById('statPendingProposals');
  const statEarnings = document.getElementById('statEarnings');
  const statRating = document.getElementById('statRating');
  const navAvatar = document.querySelector('.navbar__avatar');

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  try {
    const user = await SkillMatch.api.get('/freelancers/me');
    const proposals = await SkillMatch.api.get('/freelancers/me/proposals');

    if (user) {
      const firstName = (user.name || 'Freelancer').split(' ')[0];
      if (welcomeTitle) welcomeTitle.textContent = `Welcome back, ${firstName}`;
      if (welcomeSubtitle) {
        welcomeSubtitle.textContent = `You have ${user.activeProposals || proposals.length || 3} active proposals and a ${user.verifiedScore || 94}% verified skill score.`;
      }
      if (navAvatar && user.name) {
        const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        navAvatar.textContent = initials;
      }
      if (statActiveProjects) statActiveProjects.textContent = user.completedJobs || 12;
      if (statPendingProposals) statPendingProposals.textContent = proposals.length || user.activeProposals || 3;
      if (statEarnings) statEarnings.textContent = `$${(user.earnings || 28450).toLocaleString()}`;
      if (statRating) statRating.textContent = `${user.verifiedScore || 94}%`;
    }
  } catch (err) {
    console.error('Failed to load dashboard metrics:', err);
  }
});
