/**
 * SkillMatch — Client Project Publishing Flow
 * Sends created project to Spring Boot REST API (POST /api/jobs)
 */

document.addEventListener('DOMContentLoaded', () => {
  const publishBtn = document.querySelector('a.btn-primary[href="my-projects.html"]');

  if (publishBtn) {
    publishBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      publishBtn.textContent = 'Publishing...';
      publishBtn.style.pointerEvents = 'none';

      try {
        const newProject = await SkillMatch.api.post('/jobs', {
          title: 'E-commerce Redesign and Migration',
          category: 'web',
          budgetType: 'fixed',
          budget: '4500',
          budgetDisplay: '$4,500 Fixed',
          duration: '1-3-months',
          durationDisplay: '1-3 Months',
          level: 'Expert',
          skills: ['React', 'TypeScript', 'UI Design'],
          desc: 'Complete redesign and headless cloud migration with performance benchmarking.'
        });

        SkillMatch.showToast('Project published successfully! Candidates can now submit proposals.', 'success');
        setTimeout(() => {
          window.location.href = 'my-projects.html';
        }, 1200);
      } catch (err) {
        publishBtn.textContent = 'Publish project';
        publishBtn.style.pointerEvents = 'auto';
        SkillMatch.showToast('Failed to publish project: ' + err.message, 'danger');
      }
    });
  }
});
