/**
 * SkillMatch — Dynamic Freelancer Profile Management
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/freelancers/me
 * - PUT /api/freelancers/me
 */

document.addEventListener('DOMContentLoaded', async () => {
  const form = document.getElementById('profileEditForm');
  const avatarInput = document.getElementById('avatarFileInput');
  const avatarImg = document.getElementById('avatarImage');
  const defaultSvg = document.getElementById('defaultAvatarSvg');
  const fullNameInput = document.getElementById('fullName');
  const emailInput = document.getElementById('email');
  const bioInput = document.getElementById('bio');
  const skillsInput = document.getElementById('skills');

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  // Load current profile from API
  try {
    const user = await SkillMatch.api.get('/freelancers/me');
    if (user) {
      if (fullNameInput) fullNameInput.value = user.name || '';
      if (emailInput) emailInput.value = user.email || '';
      if (bioInput) bioInput.value = user.bio || '';
      if (skillsInput && Array.isArray(user.skills)) {
        skillsInput.value = user.skills.join(', ');
      }
      if (user.avatar && avatarImg) {
        avatarImg.src = user.avatar;
        avatarImg.style.display = 'block';
        if (defaultSvg) defaultSvg.style.display = 'none';
      }
    }
  } catch (err) {
    console.warn('Failed to load profile data:', err);
  }

  // Live avatar image preview via FileReader
  if (avatarInput) {
    avatarInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (!file) return;

      if (!file.type.startsWith('image/')) {
        SkillMatch.showToast('Please select a valid image file.', 'warning');
        return;
      }

      const reader = new FileReader();
      reader.onload = (event) => {
        if (avatarImg) {
          avatarImg.src = event.target.result;
          avatarImg.style.display = 'block';
          avatarImg.style.width = '100%';
          avatarImg.style.height = '100%';
          avatarImg.style.objectFit = 'cover';
          avatarImg.style.borderRadius = '50%';
        }
        if (defaultSvg) defaultSvg.style.display = 'none';
      };
      reader.readAsDataURL(file);
    });
  }

  // Save changes via PUT /api/freelancers/me
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      const origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Saving Changes...';

      const skillsArray = (skillsInput?.value || '')
        .split(',')
        .map(s => s.trim())
        .filter(Boolean);

      const payload = {
        name: fullNameInput?.value?.trim(),
        email: emailInput?.value?.trim(),
        bio: bioInput?.value?.trim(),
        skills: skillsArray
      };

      try {
        await SkillMatch.api.put('/freelancers/me', payload);
        SkillMatch.showToast('Profile updated successfully!', 'success');
      } catch (err) {
        SkillMatch.showToast('Failed to save profile: ' + err.message, 'error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
      }
    });
  }
});
