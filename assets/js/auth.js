/**
 * SkillMatch — Dynamic Authentication & Session Controller
 * Connects to Spring Boot REST Endpoints:
 * - POST /api/auth/login
 * - POST /api/auth/register
 */

document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('loginForm');
  const adminLoginForm = document.getElementById('adminLoginForm');
  const registerForm = document.getElementById('registerForm') || document.getElementById('signupForm');

  // Handle standard user login (Client vs Freelancer)
  if (loginForm) {
    let chosenRole = 'FREELANCER';

    loginForm.querySelectorAll('button[type="submit"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const val = btn.getAttribute('value') || btn.name;
        if (val && val.toLowerCase().includes('client')) {
          chosenRole = 'CLIENT';
        } else {
          chosenRole = 'FREELANCER';
        }
      });
    });

    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const email = document.getElementById('email')?.value?.trim();
      const password = document.getElementById('password')?.value;

      if (!email || !password) return;

      const activeBtn = document.activeElement && document.activeElement.tagName === 'BUTTON' 
        ? document.activeElement 
        : loginForm.querySelector('button[type="submit"]');

      const origText = activeBtn.innerHTML;
      activeBtn.disabled = true;
      activeBtn.innerHTML = 'Authenticating...';

      try {
        const payload = { email, password, role: chosenRole };
        const res = await SkillMatch.api.post('/auth/login', payload);

        SkillMatch.auth.setUser(res.user, res.token);
        SkillMatch.showToast(`Welcome back, ${res.user.name || 'User'}!`, 'success');

        setTimeout(() => {
          if (chosenRole === 'CLIENT') {
            window.location.href = '../client/index.html';
          } else {
            window.location.href = '../freelancer/index.html';
          }
        }, 600);
      } catch (err) {
        SkillMatch.showToast(err.message || 'Invalid email or credentials.', 'error');
        activeBtn.disabled = false;
        activeBtn.innerHTML = origText;
      }
    });
  }

  // Handle Admin Login
  if (adminLoginForm) {
    adminLoginForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const email = document.getElementById('admin-email')?.value?.trim() || document.getElementById('email')?.value?.trim();
      const password = document.getElementById('admin-password')?.value || document.getElementById('password')?.value;

      const submitBtn = adminLoginForm.querySelector('button[type="submit"]');
      const origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Signing in to Admin Console...';

      try {
        const res = await SkillMatch.api.post('/auth/login', { email, password, role: 'ADMIN' });
        SkillMatch.auth.setUser(res.user || { name: 'System Admin', role: 'ADMIN' }, res.token);
        SkillMatch.showToast('Admin authentication successful.', 'success');

        setTimeout(() => {
          window.location.href = '../Admin/html/admin-dashboard.html';
        }, 500);
      } catch (err) {
        SkillMatch.showToast(err.message || 'Admin authentication failed.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
      }
    });
  }

  // Handle Registration
  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const name = document.getElementById('fullName')?.value?.trim() || document.getElementById('name')?.value?.trim();
      const email = document.getElementById('email')?.value?.trim();
      const password = document.getElementById('password')?.value;
      const role = document.querySelector('input[name="role"]:checked')?.value || 'FREELANCER';

      const submitBtn = registerForm.querySelector('button[type="submit"]');
      const origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Creating Account...';

      try {
        const res = await SkillMatch.api.post('/auth/register', { name, email, password, role });
        SkillMatch.auth.setUser(res.user, res.token);
        SkillMatch.showToast('Account created successfully! Redirecting...', 'success');

        setTimeout(() => {
          if (role.toUpperCase() === 'CLIENT') {
            window.location.href = '../client/index.html';
          } else {
            window.location.href = '../freelancer/index.html';
          }
        }, 800);
      } catch (err) {
        SkillMatch.showToast(err.message || 'Failed to create account.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
      }
    });
  }
});
