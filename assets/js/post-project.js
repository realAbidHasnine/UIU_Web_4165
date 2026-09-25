/**
 * SkillMatch — Client Project Publishing Multi-Step Controller
 * Manages steps across:
 * 1. post-project-details.html (Title, Category, Description)
 * 2. post-project.html (Skills, Budget Type, Budget Range, Duration)
 * 3. post-project-screening.html (Screening Questions, Requirements)
 * 4. post-project-review.html (Review Summary, Escrow Milestones, Publish to REST API)
 */

document.addEventListener('DOMContentLoaded', () => {
  const STORAGE_KEY = 'skillmatch_draft_project';

  function getDraft() {
    try {
      const data = sessionStorage.getItem(STORAGE_KEY);
      return data ? JSON.parse(data) : getDefaultProject();
    } catch (e) {
      return getDefaultProject();
    }
  }

  function saveDraft(draft) {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
    } catch (e) { }
  }

  function getDefaultProject() {
    return {
      title: 'E-commerce Redesign and Migration',
      category: 'web',
      categoryDisplay: 'Web Development',
      budgetType: 'fixed',
      budget: '4500',
      budgetDisplay: '$4,500 Fixed',
      duration: '1-3-months',
      durationDisplay: '1-3 Months',
      level: 'Expert',
      skills: ['React', 'TypeScript', 'UI Design'],
      desc: 'Complete redesign and headless cloud migration with performance benchmarking.',
      questions: [
        'Describe a similar project you shipped in the last year.',
        'What would you deliver in the first two weeks?'
      ],
      milestones: [
        { title: 'Design mockups approved', amount: 1500 },
        { title: 'Frontend build complete', amount: 2000 },
        { title: 'Launch and handover', amount: 1000 }
      ]
    };
  }

  const currentPath = window.location.pathname;

  // =========================================================================
  // STEP 1: Project Details (post-project-details.html)
  // =========================================================================
  if (currentPath.includes('post-project-details.html')) {
    const titleInput = document.querySelector('input.input');
    const categorySelect = document.querySelector('select.select');
    const descTextarea = document.querySelector('textarea.textarea');
    const continueBtn = document.querySelector('a.btn-primary[href="post-project.html"]');

    const draft = getDraft();
    if (titleInput && draft.title) titleInput.value = draft.title;
    if (categorySelect && draft.categoryDisplay) {
      for (let i = 0; i < categorySelect.options.length; i++) {
        if (categorySelect.options[i].text.toLowerCase().includes(draft.category.toLowerCase())) {
          categorySelect.selectedIndex = i;
          break;
        }
      }
    }
    if (descTextarea && draft.desc) descTextarea.value = draft.desc;

    if (continueBtn) {
      continueBtn.addEventListener('click', (e) => {
        const title = titleInput?.value.trim() || draft.title;
        const catText = categorySelect?.options[categorySelect.selectedIndex]?.text || 'Web Development';
        const catVal = catText.toLowerCase().includes('mobile') ? 'mobile' : catText.toLowerCase().includes('data') ? 'data' : 'web';
        const desc = descTextarea?.value.trim() || draft.desc;

        draft.title = title;
        draft.category = catVal;
        draft.categoryDisplay = catText;
        draft.desc = desc;
        saveDraft(draft);
      });
    }
  }

  // =========================================================================
  // STEP 2: Skills & Budget (post-project.html)
  // =========================================================================
  if (currentPath.includes('post-project.html') && !currentPath.includes('details') && !currentPath.includes('screening') && !currentPath.includes('review')) {
    const draft = getDraft();
    const skillsInput = document.querySelector('input[name="required_skills"]');
    const addSkillBtn = skillsInput?.parentElement?.querySelector('button');
    const chipsContainer = skillsInput?.closest('.field')?.querySelector('div[style*="margin-top"]');
    const budgetButtons = document.querySelectorAll('.field .inline button.btn');
    const minBudgetInput = document.querySelector('input[name="budget_min"]');
    const maxBudgetInput = document.querySelector('input[name="budget_max"]');
    const durationSelect = document.querySelector('select[name="estimated_duration"]');
    const continueBtn = document.querySelector('a.btn-primary[href="post-project-screening.html"]');

    let currentSkills = Array.isArray(draft.skills) && draft.skills.length > 0 ? [...draft.skills] : ['React', 'TypeScript', 'UI Design'];
    let chosenBudgetType = draft.budgetType || 'fixed';

    function renderSkillChips() {
      if (!chipsContainer) return;
      chipsContainer.innerHTML = currentSkills.map((s, idx) => `
        <span class="chip" data-idx="${idx}" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
          ${escapeHtml(s)} <span style="font-weight:bold; font-size:12px;">&times;</span>
        </span>
      `).join(' ');

      chipsContainer.querySelectorAll('.chip').forEach(chip => {
        chip.addEventListener('click', () => {
          const idx = Number(chip.getAttribute('data-idx'));
          currentSkills.splice(idx, 1);
          renderSkillChips();
        });
      });
    }

    renderSkillChips();

    if (addSkillBtn && skillsInput) {
      addSkillBtn.addEventListener('click', () => {
        const val = skillsInput.value.trim();
        if (val && !currentSkills.includes(val)) {
          currentSkills.push(val);
          renderSkillChips();
          skillsInput.value = '';
        }
      });

      skillsInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          addSkillBtn.click();
        }
      });
    }

    budgetButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        budgetButtons.forEach(b => {
          b.classList.remove('btn-primary');
          b.classList.add('btn-outline');
        });
        btn.classList.remove('btn-outline');
        btn.classList.add('btn-primary');
        chosenBudgetType = btn.textContent.toLowerCase().includes('hourly') ? 'hourly' : 'fixed';
      });
    });

    if (continueBtn) {
      continueBtn.addEventListener('click', () => {
        draft.skills = currentSkills;
        draft.budgetType = chosenBudgetType;
        const maxVal = maxBudgetInput?.value.replace(/\D/g, '') || draft.budget;
        draft.budget = maxVal;
        draft.budgetDisplay = chosenBudgetType === 'hourly' ? `$${maxVal}/hr` : `$${Number(maxVal).toLocaleString()} Fixed`;
        const durVal = durationSelect?.value || '1-3-months';
        draft.duration = durVal;
        draft.durationDisplay = durationSelect?.options[durationSelect.selectedIndex]?.text || '1-3 Months';
        saveDraft(draft);
      });
    }
  }

  // =========================================================================
  // STEP 3: Screening (post-project-screening.html)
  // =========================================================================
  if (currentPath.includes('post-project-screening.html')) {
    const draft = getDraft();
    const continueBtn = document.querySelector('a.btn-primary[href="post-project-review.html"]');

    if (continueBtn) {
      continueBtn.addEventListener('click', () => {
        const questionInputs = document.querySelectorAll('.form-card .field input.input');
        const qs = [];
        questionInputs.forEach(inp => {
          const val = inp.value.trim();
          if (val) qs.push(val);
        });
        if (qs.length > 0) draft.questions = qs;
        saveDraft(draft);
      });
    }
  }

  // =========================================================================
  // STEP 4: Review & Publish (post-project-review.html)
  // =========================================================================
  if (currentPath.includes('post-project-review.html')) {
    const draft = getDraft();
    const publishBtn = document.querySelector('a.btn-primary[href="my-projects.html"]');

    // Dynamically update chips in summary
    const chipsWrapper = document.querySelector('.form-card .field div[style*="margin-top"]');
    if (chipsWrapper && draft.skills && draft.skills.length > 0) {
      chipsWrapper.innerHTML = draft.skills.map(s => `<span class="chip">${escapeHtml(s)}</span>`).join(' ');
    }

    if (publishBtn) {
      publishBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        publishBtn.textContent = 'Publishing to Marketplace...';
        publishBtn.style.pointerEvents = 'none';

        try {
          const projectPayload = {
            title: draft.title || 'E-commerce Redesign and Migration',
            category: draft.category || 'web',
            budgetType: draft.budgetType || 'fixed',
            budget: String(draft.budget || '4500'),
            budgetDisplay: draft.budgetDisplay || '$4,500 Fixed',
            duration: draft.duration || '1-3-months',
            durationDisplay: draft.durationDisplay || '1-3 Months',
            level: draft.level || 'Expert',
            skills: draft.skills || ['React', 'TypeScript', 'UI Design'],
            desc: draft.desc || 'Complete redesign and headless cloud migration with performance benchmarking.'
          };

          const newProject = await SkillMatch.api.post('/jobs', projectPayload);

          SkillMatch.showToast('Project published successfully! Candidates can now submit proposals.', 'success');
          sessionStorage.removeItem(STORAGE_KEY);

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
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
});
