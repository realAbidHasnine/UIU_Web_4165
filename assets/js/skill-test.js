/**
 * SkillMatch — Dynamic Skill Verification Test Engine
 * Connects to Spring Boot REST Endpoints:
 * - GET /api/skills/tests/{category}
 * - POST /api/skills/tests/{category}/submit
 */

document.addEventListener('DOMContentLoaded', async () => {
  const urlParams = new URLSearchParams(window.location.search);
  const category = urlParams.get('category') || 'web';

  const testTitleEl = document.getElementById('testTitle');
  const timerText = document.getElementById('timerText');
  const testTimer = document.getElementById('testTimer');
  const progressText = document.getElementById('progressText');
  const progressBar = document.getElementById('progressBar');
  const testForm = document.getElementById('testForm');
  const questionContainer = document.getElementById('questionContainer');

  let questions = [];
  let currentStep = 0;
  const userAnswers = {};
  let timerInterval = null;
  let remainingSeconds = 15 * 60; // 15 minutes

  // Start countdown timer
  function startTimer() {
    updateTimerDisplay();
    timerInterval = setInterval(() => {
      remainingSeconds--;
      updateTimerDisplay();

      if (remainingSeconds <= 60 && testTimer) {
        testTimer.style.color = 'var(--color-danger, #ef4444)';
        testTimer.style.borderColor = 'var(--color-danger, #ef4444)';
      }

      if (remainingSeconds <= 0) {
        clearInterval(timerInterval);
        SkillMatch.showToast('Time expired! Submitting test automatically...', 'warning');
        submitTest(true);
      }
    }, 1000);
  }

  function updateTimerDisplay() {
    if (!timerText) return;
    const mins = Math.floor(remainingSeconds / 60);
    const secs = remainingSeconds % 60;
    timerText.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  }

  // Load questions from backend API
  async function loadQuestions() {
    if (questionContainer) {
      questionContainer.innerHTML = `
        <div style="padding: 60px; text-align: center; color: var(--color-text-muted);">
          <div class="spinner" style="display:inline-block; width:32px; height:32px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
          <p style="margin-top: 16px; font-size: 15px;">Loading verification test questions...</p>
        </div>
      `;
    }

    try {
      questions = await SkillMatch.api.get(`/skills/tests/${category}`);
      if (!questions || questions.length === 0) {
        throw new Error('No questions found for this category.');
      }
      renderCurrentStep();
      startTimer();
    } catch (err) {
      if (questionContainer) {
        questionContainer.innerHTML = `
          <div style="padding: 40px; text-align: center; color: var(--color-danger);">
            <p style="margin-bottom: 12px;">Failed to load skill test questions.</p>
            <a href="select_skill.html" class="btn btn--outline">Return to Skill Selection</a>
          </div>
        `;
      }
    }
  }

  // Render question step
  function renderCurrentStep() {
    if (!questionContainer || questions.length === 0) return;

    const q = questions[currentStep];
    const total = questions.length;
    const progressPercent = Math.round(((currentStep + 1) / total) * 100);

    if (progressBar) progressBar.style.width = `${progressPercent}%`;
    if (progressText) progressText.textContent = `Question ${currentStep + 1} of ${total}`;

    questionContainer.innerHTML = `
      <div class="question-step" style="display: block;">
        <h1 class="question__title">${escapeHtml(q.question)}</h1>
        
        <div class="options-group">
          ${q.options.map((opt, idx) => `
            <div class="option__wrapper">
              <input type="radio" id="opt_${idx}" name="current_question_ans" value="${idx}" class="option__input" ${userAnswers[q.id] === idx ? 'checked' : ''}>
              <label for="opt_${idx}" class="option__label">
                <div class="option__radio-circle"></div>
                <span>${escapeHtml(opt)}</span>
              </label>
            </div>
          `).join('')}
        </div>

        <div class="test-controls">
          ${currentStep > 0 ? `<button type="button" class="btn btn--outline" id="prevStepBtn">&larr; Previous</button>` : '<div></div>'}
          ${currentStep < total - 1 ? `<button type="button" class="btn btn--solid" id="nextStepBtn">Next Question &rarr;</button>` : `<button type="button" class="btn btn--solid" id="submitTestBtn" style="background-color: var(--color-success, #10b981);">Submit Test</button>`}
        </div>
      </div>
    `;

    // Attach listeners
    questionContainer.querySelectorAll('input[name="current_question_ans"]').forEach(radio => {
      radio.addEventListener('change', (e) => {
        userAnswers[q.id] = Number(e.target.value);
      });
    });

    document.getElementById('prevStepBtn')?.addEventListener('click', () => {
      if (currentStep > 0) {
        currentStep--;
        renderCurrentStep();
      }
    });

    document.getElementById('nextStepBtn')?.addEventListener('click', () => {
      if (userAnswers[q.id] === undefined) {
        SkillMatch.showToast('Please select an answer before proceeding.', 'warning');
        return;
      }
      if (currentStep < total - 1) {
        currentStep++;
        renderCurrentStep();
      }
    });

    document.getElementById('submitTestBtn')?.addEventListener('click', () => {
      if (userAnswers[q.id] === undefined) {
        SkillMatch.showToast('Please select an answer for the final question.', 'warning');
        return;
      }
      submitTest(false);
    });
  }

  // Submit test to backend
  async function submitTest(isTimeout = false) {
    if (timerInterval) clearInterval(timerInterval);

    const submitBtn = document.getElementById('submitTestBtn');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Grading...';
    }

    try {
      const payload = {
        category,
        answers: userAnswers,
        timeSpentSeconds: (15 * 60) - remainingSeconds,
        isTimeout
      };

      const result = await SkillMatch.api.post(`/skills/tests/${category}/submit`, payload);
      sessionStorage.setItem('skillmatch_latest_test_result', JSON.stringify(result));
      SkillMatch.showToast(`Test completed! Your score: ${result.score}%`, 'success');
      setTimeout(() => {
        window.location.href = `result_suggestion.html?category=${category}&score=${result.score}`;
      }, 700);
    } catch (err) {
      SkillMatch.showToast('Error submitting test: ' + err.message, 'error');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Test';
      }
    }
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

  loadQuestions();
});
