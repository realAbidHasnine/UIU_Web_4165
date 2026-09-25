/**
 * SkillMatch — Dynamic Work Deliverable Uploader
 * Connects to Spring Boot REST Endpoints:
 * - POST /api/projects/{id}/deliverables
 */

document.addEventListener('DOMContentLoaded', () => {
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileUpload');
  const fileList = document.getElementById('fileList');
  const form = document.getElementById('uploadWorkForm');

  let selectedFiles = [];

  // Mobile Navbar Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navbarMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navbarMenu) {
    mobileToggle.addEventListener('click', () => {
      navbarMenu.classList.toggle('navbar__center--mobile-open');
    });
  }

  // Handle files
  function handleFiles(files) {
    Array.from(files).forEach(file => {
      if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
        selectedFiles.push(file);
      }
    });
    renderFileList();
  }

  function renderFileList() {
    if (!fileList) return;

    if (selectedFiles.length === 0) {
      fileList.innerHTML = `
        <div style="padding: 16px; text-align: center; color: var(--color-text-muted); font-size: 13px;">
          No files selected yet. Drop files above or click to browse.
        </div>
      `;
      return;
    }

    fileList.innerHTML = selectedFiles.map((file, idx) => `
      <div class="file-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--color-border); border-radius: var(--radius-md); margin-bottom: 8px;">
        <div class="file-item__info" style="display: flex; align-items: center; gap: 12px;">
          <svg class="file-item__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <div>
            <div class="file-item__name" style="font-weight: 500; font-size: 14px;">${escapeHtml(file.name)}</div>
            <div class="file-item__size" style="font-size: 12px; color: var(--color-text-muted);">${formatBytes(file.size)}</div>
          </div>
        </div>
        <button type="button" class="btn-remove-file" data-idx="${idx}" style="background: none; border: none; color: var(--color-danger, #ef4444); cursor: pointer; font-size: 18px;" title="Remove file">&times;</button>
      </div>
    `).join('');

    fileList.querySelectorAll('.btn-remove-file').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = Number(btn.getAttribute('data-idx'));
        selectedFiles.splice(idx, 1);
        renderFileList();
      });
    });
  }

  // Drag and drop listeners
  if (dropZone) {
    ['dragenter', 'dragover'].forEach(eventName => {
      dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--color-primary)';
        dropZone.style.backgroundColor = 'rgba(37, 99, 235, 0.04)';
      });
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--color-border)';
        dropZone.style.backgroundColor = 'transparent';
      });
    });

    dropZone.addEventListener('drop', (e) => {
      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        handleFiles(e.dataTransfer.files);
      }
    });
  }

  if (fileInput) {
    fileInput.addEventListener('change', (e) => {
      if (e.target.files) {
        handleFiles(e.target.files);
      }
    });
  }

  // Form submission
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (selectedFiles.length === 0) {
        SkillMatch.showToast('Please attach at least one deliverable file or archive.', 'warning');
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Uploading Deliverables...';

      const notes = document.getElementById('deliveryNotes')?.value || '';
      const repoUrl = document.getElementById('repoUrl')?.value || '';

      const payload = {
        projectId: 'proj-101',
        files: selectedFiles.map(f => ({ name: f.name, size: f.size, type: f.type })),
        notes,
        repoUrl,
        submittedAt: new Date().toISOString()
      };

      try {
        await SkillMatch.api.post('/projects/proj-101/deliverables', payload);
        SkillMatch.showToast('Milestone deliverable submitted successfully!', 'success');
        setTimeout(() => {
          window.location.href = 'index.html';
        }, 1000);
      } catch (err) {
        SkillMatch.showToast('Error uploading work: ' + err.message, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
      }
    });
  }

  function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
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

  renderFileList();
});
