/**
 * SkillMatch — Dynamic Freelancers Directory
 * Connects to PHP REST Endpoints:
 * - GET /api/freelancers (supports search, categories, experience, sort)
 */

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('freelancersGrid') || 
                    document.querySelector('.freelancers-grid') || 
                    document.querySelector('.freelancer-list') || 
                    document.getElementById('freelancerList');

  const searchInput = document.querySelector('input[name="freelancer_query"]') || 
                      document.getElementById('searchFreelancers') || 
                      document.querySelector('input[type="search"]') || 
                      document.getElementById('keyword');

  const searchForm = document.getElementById('freelancerSearchForm');
  const filterForm = document.getElementById('freelancerFilterForm');
  const sortSelect = document.querySelector('select[name="sort_by"]') || 
                     document.getElementById('sortSelect') || 
                     document.getElementById('sort');

  // Pre-fill from URL query parameters (e.g. from Hero Search on index.html)
  const urlParams = new URLSearchParams(window.location.search);
  const initialSearch = urlParams.get('search') || urlParams.get('skill_query') || urlParams.get('q') || '';
  if (searchInput && initialSearch) {
    searchInput.value = initialSearch;
  }

  const initialCat = urlParams.get('categories') || urlParams.get('category') || '';
  if (initialCat) {
    const cats = initialCat.split(',').map(c => c.trim().toLowerCase());
    document.querySelectorAll('input[name="categories[]"]').forEach(cb => {
      if (cats.includes(cb.value.toLowerCase())) {
        cb.checked = true;
      }
    });
  }

  let allFreelancers = [];

  async function loadFreelancers() {
    if (!container) return;

    container.innerHTML = `
      <div style="grid-column: 1 / -1; padding: 48px; text-align: center; color: var(--color-text-muted);">
        <div class="spinner" style="display:inline-block; width:32px; height:32px; border:3px solid rgba(37,99,235,0.2); border-top-color:var(--color-primary); border-radius:50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 12px; font-size: 14px;">Loading verified talent network...</p>
      </div>
    `;

    const params = {};

    const query = searchInput ? searchInput.value.trim() : '';
    if (query) {
      params.search = query;
    }

    const checkedCats = Array.from(document.querySelectorAll('input[name="categories[]"]:checked'))
      .map(cb => cb.value)
      .filter(Boolean);
    if (checkedCats.length > 0) {
      params.categories = checkedCats.join(',');
    }

    const checkedExp = document.querySelector('input[name="experience"]:checked');
    if (checkedExp && checkedExp.value && checkedExp.value !== 'all') {
      params.experience = checkedExp.value;
    }

    const sortVal = sortSelect ? sortSelect.value : '';
    if (sortVal) {
      // Map sort labels to API sort keys
      if (sortVal === 'highest_rated' || sortVal === 'rating') params.sort = 'rating';
      else if (sortVal === 'most_reviews' || sortVal === 'skill_score') params.sort = 'skill_score';
      else if (sortVal === 'rate_low_high' || sortVal === 'price_asc') params.sort = 'price_asc';
      else if (sortVal === 'rate_high_low' || sortVal === 'price_desc') params.sort = 'price_desc';
      else if (sortVal === 'newest') params.sort = 'newest';
      else params.sort = sortVal;
    }

    try {
      allFreelancers = await SkillMatch.api.get('/freelancers', params);
      renderFreelancers(allFreelancers);
    } catch (err) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 32px; text-align: center; color: var(--color-danger);">
          Failed to load freelancers. Please verify XAMPP is running.
        </div>
      `;
    }
  }

  function renderFreelancers(list) {
    if (!container) return;

    if (!list || list.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 48px; text-align: center; background: white; border-radius: 12px; border: 1px solid var(--color-border);">
          <div style="font-size: 32px; margin-bottom: 8px;">🔍</div>
          <h3 style="font-size: 16px; font-weight: 600; color: var(--color-text-main); margin-bottom: 6px;">No freelancers found</h3>
          <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 12px;">Try adjusting your search terms or clearing some filters.</p>
          <button type="button" class="btn btn--outline" id="clearFiltersBtn">Reset Filters</button>
        </div>
      `;
      document.getElementById('clearFiltersBtn')?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (filterForm) filterForm.reset();
        loadFreelancers();
      });
      return;
    }

    container.innerHTML = list.map(f => `
      <article class="freelancer-card" style="background: white; border: 1px solid var(--color-border); border-radius: 12px; padding: 24px; margin-bottom: 20px; transition: all 0.2s ease;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
          <div style="display: flex; gap: 16px; align-items: center;">
            <div class="avatar" style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-primary, #2563eb); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">
              ${getInitials(f.name)}
            </div>
            <div>
              <h3 style="font-size: 18px; font-weight: 600; margin: 0 0 4px 0; color: var(--color-text-main);">
                ${escapeHtml(f.name)}
                ${f.score >= 90 ? `<span style="margin-left: 8px; font-size: 12px; padding: 2px 8px; border-radius: 12px; background: #dbeafe; color: #1e40af;">★ Verified ${f.score}%</span>` : ''}
              </h3>
              <p style="font-size: 14px; color: var(--color-text-muted); margin: 0;">${escapeHtml(f.title || 'Independent Professional')}</p>
            </div>
          </div>
          <div style="text-align: right;">
            <span style="font-size: 20px; font-weight: 700; color: var(--color-text-main);">$${f.hourlyRate}</span>
            <span style="font-size: 12px; color: var(--color-text-muted);">/ hr</span>
          </div>
        </div>

        <p style="font-size: 14px; line-height: 1.5; color: var(--color-text-main); margin: 16px 0;">
          ${escapeHtml(f.bio || 'Verified talent with proven expertise on SkillMatch.')}
        </p>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--color-border); padding-top: 14px; margin-top: 14px; flex-wrap: wrap; gap: 10px;">
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            ${(f.skills || []).map(s => `<span style="font-size: 12px; padding: 3px 10px; border-radius: 6px; background: #f1f5f9; color: #475569;">${escapeHtml(s)}</span>`).join('')}
          </div>
          <a href="freelancer_public_profile.html?id=${encodeURIComponent(f.id)}" class="btn btn--outline" style="text-decoration: none; font-size: 13px; padding: 6px 14px;">View Profile</a>
        </div>
      </article>
    `).join('');
  }

  // Live search input with debouncing
  let debounceTimer = null;
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(loadFreelancers, 250);
    });
  }

  if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      loadFreelancers();
    });
  }

  if (filterForm) {
    filterForm.addEventListener('change', () => loadFreelancers());
    filterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      loadFreelancers();
    });
    filterForm.addEventListener('reset', () => {
      setTimeout(loadFreelancers, 10);
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', () => loadFreelancers());
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
