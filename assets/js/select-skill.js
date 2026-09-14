/**
 * SkillMatch — Dynamic Skill Category Selection
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/skills/categories
 */

document.addEventListener('DOMContentLoaded', async () => {
  const skillsGroup = document.getElementById('skillsGroup');
  const selectionCounter = document.getElementById('selectionCounter');
  const form = document.getElementById('skillCategoryForm');

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  try {
    const categories = await SkillMatch.api.get('/skills/categories');
    renderCategories(categories);
  } catch (err) {
    console.error('Failed to load categories:', err);
  }

  function renderCategories(list) {
    if (!skillsGroup) return;

    skillsGroup.innerHTML = list.map((cat, idx) => `
      <div class="skill-pill">
        <input type="checkbox" id="cat_${cat.id}" name="category" value="${cat.id}" class="skill-pill__input" ${idx === 0 ? 'checked' : ''}>
        <label for="cat_${cat.id}" class="skill-pill__label">
          <span>${escapeHtml(cat.name)}</span>
          <svg class="skill-pill__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </label>
      </div>
    `).join('');

    updateCounter();

    skillsGroup.querySelectorAll('.skill-pill__input').forEach(input => {
      input.addEventListener('change', () => {
        const checked = skillsGroup.querySelectorAll('.skill-pill__input:checked');
        if (checked.length > 5) {
          input.checked = false;
          SkillMatch.showToast('You can select a maximum of 5 categories.', 'warning');
        }
        updateCounter();
      });
    });
  }

  function updateCounter() {
    if (!selectionCounter || !skillsGroup) return;
    const count = skillsGroup.querySelectorAll('.skill-pill__input:checked').length;
    selectionCounter.textContent = `Selected: ${count}/5`;
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const checked = skillsGroup?.querySelectorAll('.skill-pill__input:checked');
      if (!checked || checked.length === 0) {
        SkillMatch.showToast('Please select at least 1 skill category to test.', 'warning');
        return;
      }

      const firstCat = checked[0].value;
      window.location.href = `skill_test.html?category=${encodeURIComponent(firstCat)}`;
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
});
