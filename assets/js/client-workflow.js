/**
 * SkillMatch — Client Workflow Controller
 * Wires client frontend pages to live PHP REST API:
 * - billing-payments.html
 * - work-approval.html
 * - payment-released.html
 * - rate-freelancer.html
 * - raise-dispute.html
 * - dispute-status.html
 * - notification-dropdown-panel.html & header bell dropdown
 * - create-client-account.html / post-project file upload
 */

document.addEventListener('DOMContentLoaded', () => {
  const path = window.location.pathname;

  // Global: sync profile header
  syncUserHeader();

  // Global: notification bell
  initNotifications();

  // Page-specific routing
  if (path.includes('billing-payments')) {
    initBilling();
  } else if (path.includes('work-approval')) {
    initWorkApproval();
  } else if (path.includes('payment-released')) {
    initPaymentReleased();
  } else if (path.includes('rate-freelancer')) {
    initRateFreelancer();
  } else if (path.includes('raise-dispute')) {
    initRaiseDispute();
  } else if (path.includes('dispute-status')) {
    initDisputeStatus();
  } else if (path.includes('create-client-account') || path.includes('post-project-details')) {
    initUploads();
  }

  // -------------------------------------------------------------------------
  // Header User & Auth Sync
  // -------------------------------------------------------------------------
  function syncUserHeader() {
    const user = window.SkillMatch?.auth?.getUser();
    if (!user) return;

    const nameEls = document.querySelectorAll('.profile-name');
    nameEls.forEach(el => {
      el.innerHTML = `${escapeHtml(user.name || 'Client')} <span class="profile-role">${escapeHtml(user.role || 'CLIENT')}</span>`;
    });

    const emailEls = document.querySelectorAll('.profile-email');
    emailEls.forEach(el => {
      el.textContent = user.email || '';
    });

    const logoutBtns = document.querySelectorAll('a.logout, a.logout-btn');
    logoutBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.SkillMatch?.auth?.logout();
      });
    });
  }

  // -------------------------------------------------------------------------
  // Notifications Dropdown & Panel
  // -------------------------------------------------------------------------
  async function initNotifications() {
    const notifWrap = document.querySelector('.notif-wrap') || document.querySelector('.notif-panel');
    if (!notifWrap) return;

    try {
      const res = await SkillMatch.api.get('/notifications');
      const items = res.notifications || [];
      const unreadCount = res.unreadCount || 0;

      // Update badge if summary bell exists
      const summary = document.querySelector('.notif-wrap summary');
      if (summary && unreadCount > 0) {
        let badge = summary.querySelector('.notif-badge');
        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'notif-badge';
          badge.style.cssText = 'position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border-radius:50%;width:10px;height:10px;display:inline-block;';
          summary.style.position = 'relative';
          summary.appendChild(badge);
        }
      }

      // Populate list
      const panel = document.querySelector('.notif-panel');
      if (panel) {
        const head = panel.querySelector('.notif-head');
        const markAllBtn = panel.querySelector('.notif-link');

        if (markAllBtn) {
          markAllBtn.style.cursor = 'pointer';
          markAllBtn.addEventListener('click', async () => {
            try {
              await SkillMatch.api.post('/notifications/read-all', {});
              panel.querySelectorAll('.notif-row.unread').forEach(r => r.classList.remove('unread'));
              const badge = summary?.querySelector('.notif-badge');
              if (badge) badge.remove();
              SkillMatch.showToast('All notifications marked as read', 'info');
            } catch (e) {
              SkillMatch.showToast('Could not mark notifications as read', 'error');
            }
          });
        }

        // Remove static rows between head and foot
        const existingRows = panel.querySelectorAll('.notif-row');
        existingRows.forEach(r => r.remove());

        const foot = panel.querySelector('.notif-foot');

        if (items.length === 0) {
          const empty = document.createElement('div');
          empty.style.cssText = 'padding: 24px; text-align: center; color: var(--muted); font-size: 13.5px;';
          empty.textContent = 'No notifications yet';
          if (foot) panel.insertBefore(empty, foot);
          else panel.appendChild(empty);
          return;
        }

        items.forEach(n => {
          const row = document.createElement('div');
          row.className = `notif-row ${n.isRead ? '' : 'unread'}`;
          row.style.cursor = n.link ? 'pointer' : 'default';

          let iconColor = 'blue';
          if (n.type === 'payment' || n.type === 'milestone') iconColor = 'green';
          else if (n.type === 'dispute') iconColor = 'brown';

          row.innerHTML = `
            <span class="notif-dot ${n.isRead ? 'placeholder' : ''}" aria-hidden="true"></span>
            <span class="notif-icon ${iconColor}" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              </svg>
            </span>
            <div class="notif-text">
              <div class="notif-title">${escapeHtml(n.title)}</div>
              <div class="notif-desc">${escapeHtml(n.body)}</div>
            </div>
            <span class="notif-time">${escapeHtml(n.time || '')}</span>
          `;

          if (n.link) {
            row.addEventListener('click', () => {
              window.location.href = n.link;
            });
          }

          if (foot) panel.insertBefore(row, foot);
          else panel.appendChild(row);
        });
      }
    } catch (e) {
      // Quiet fail if guest or not logged in
    }
  }

  // -------------------------------------------------------------------------
  // 1. Billing & Payments (billing-payments.html)
  // -------------------------------------------------------------------------
  async function initBilling() {
    const main = document.querySelector('main.container');
    if (!main) return;

    // Export CSV button
    const exportBtn = main.querySelector('.btn-outline');
    if (exportBtn) {
      exportBtn.style.cursor = 'pointer';
      exportBtn.removeAttribute('aria-disabled');
      exportBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
          const user = SkillMatch.auth.getUser();
          const token = user?.token;
          const exportUrl = `${SkillMatch.config.BASE_URL}/clients/me/billing/export`;
          
          const resp = await fetch(exportUrl, {
            headers: token ? { 'Authorization': `Bearer ${token}` } : {}
          });
          if (!resp.ok) throw new Error('Export failed');
          
          const blob = await resp.blob();
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = `skillmatch-billing-${Date.now()}.csv`;
          document.body.appendChild(link);
          link.click();
          link.remove();
          SkillMatch.showToast('Billing ledger exported successfully', 'success');
        } catch (err) {
          SkillMatch.showToast('Failed to export CSV: ' + err.message, 'error');
        }
      });
    }

    try {
      const data = await SkillMatch.api.get('/clients/me/billing');
      const summary = data.summary || {};
      const months = data.months || [];

      // Update stat cards
      const statNums = main.querySelectorAll('.stats .stat .stat-num');
      if (statNums.length >= 3) {
        statNums[0].textContent = '$' + Number(summary.totalSpent || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        statNums[1].textContent = '$' + Number(summary.heldInEscrow || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        statNums[2].textContent = '$' + Number(summary.releasedThisMonth || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }

      // Remove existing static section-head and cards
      const dynamicSections = main.querySelectorAll('.section-head, .card:not(.stats)');
      dynamicSections.forEach(s => s.remove());

      const footerNote = main.querySelector('p[style*="Showing recent activity"]');

      if (months.length === 0) {
        const emptyCard = document.createElement('div');
        emptyCard.className = 'card';
        emptyCard.style.padding = '32px';
        emptyCard.style.textAlign = 'center';
        emptyCard.style.color = 'var(--muted)';
        emptyCard.innerHTML = '<p>No billing or escrow transactions found yet.</p>';
        if (footerNote) main.insertBefore(emptyCard, footerNote);
        else main.appendChild(emptyCard);
        return;
      }

      months.forEach(m => {
        const head = document.createElement('div');
        head.className = 'section-head';
        head.innerHTML = `
          <h2>${escapeHtml(m.label)}</h2>
          <span style="font-size:13.5px;color:var(--muted)">Released $${Number(m.releasedTotal || 0).toFixed(2)} &bull; Held $${Number(m.heldTotal || 0).toFixed(2)}</span>
        `;
        if (footerNote) main.insertBefore(head, footerNote);
        else main.appendChild(head);

        const card = document.createElement('div');
        card.className = 'card';
        card.style.marginBottom = '20px';

        (m.entries || []).forEach(entry => {
          const row = document.createElement('div');
          row.className = 'list-row';
          const isReleased = entry.status === 'Released';
          const badgeClass = isReleased ? 'badge-done' : (entry.status === 'Refunded' ? 'badge-open' : 'badge-progress');

          row.innerHTML = `
            <div>
              <p class="row-title">${escapeHtml(entry.jobTitle)} <span class="badge ${badgeClass}">${escapeHtml(entry.status)}</span></p>
              <div class="row-meta">
                <span>${escapeHtml(entry.milestoneLabel)}</span>
                <span>${escapeHtml(entry.freelancerName)}</span>
                <span>${escapeHtml(entry.date || '')}</span>
                ${entry.receiptId ? `<span>Receipt ${escapeHtml(entry.receiptId)}</span>` : '<span>Releases on approval</span>'}
              </div>
            </div>
            <div class="price">
              <strong>$${Number(entry.total || 0).toFixed(2)}</strong>
              $${Number(entry.amount || 0).toFixed(2)} + $${Number(entry.escrowFee || 0).toFixed(2)} fee
            </div>
          `;
          card.appendChild(row);
        });

        if (footerNote) main.insertBefore(card, footerNote);
        else main.appendChild(card);
      });
    } catch (err) {
      SkillMatch.showToast('Could not load billing ledger: ' + err.message, 'error');
    }
  }

  // -------------------------------------------------------------------------
  // 2. Work Approval (work-approval.html)
  // -------------------------------------------------------------------------
  async function initWorkApproval() {
    const urlParams = new URLSearchParams(window.location.search);
    let milestoneId = urlParams.get('milestoneId') || urlParams.get('id');

    let activeMilestone = null;

    try {
      const milestones = await SkillMatch.api.get('/clients/me/milestones');
      if (!Array.isArray(milestones) || milestones.length === 0) {
        SkillMatch.showToast('No milestones found for your projects.', 'info');
        return;
      }

      if (milestoneId) {
        activeMilestone = milestones.find(m => m.id === milestoneId);
      }
      if (!activeMilestone) {
        // Find first Submitted milestone, or first milestone
        activeMilestone = milestones.find(m => m.status === 'Submitted') || milestones[0];
        milestoneId = activeMilestone.id;
      }

      renderWorkApproval(activeMilestone);
    } catch (err) {
      SkillMatch.showToast('Failed to load milestone: ' + err.message, 'error');
    }

    function renderWorkApproval(m) {
      // Header subtitles
      const pageSub = document.querySelector('.page-sub');
      if (pageSub) {
        pageSub.innerHTML = `Review deliverables for <strong style="color:var(--text)">${escapeHtml(m.jobTitle)}</strong> (${escapeHtml(m.label)}) submitted by ${escapeHtml(m.freelancerName)}.`;
      }

      // Summary amounts
      const aside = document.querySelector('aside.card');
      if (aside) {
        const strongs = aside.querySelectorAll('strong');
        if (strongs.length >= 3) {
          strongs[0].textContent = `$${Number(m.amount).toFixed(2)}`;
          strongs[1].textContent = `$${Number(m.escrowFee).toFixed(2)}`;
          strongs[2].textContent = `$${Number(m.total).toFixed(2)}`;
        }
      }

      // Deliverables card
      const delivCard = document.querySelector('.card:first-child');
      if (delivCard && m.deliverables && m.deliverables.length > 0) {
        const d = m.deliverables[0];
        const filesContainer = delivCard.querySelector('div[style*="display:flex"]')?.parentElement;
        if (d.notes) {
          let noteEl = delivCard.querySelector('.deliverable-notes');
          if (!noteEl) {
            noteEl = document.createElement('p');
            noteEl.className = 'deliverable-notes';
            noteEl.style.cssText = 'background:#f8fafc;padding:12px;border-radius:6px;font-size:13.5px;margin-top:12px;border:1px solid var(--border-light);';
            delivCard.appendChild(noteEl);
          }
          noteEl.innerHTML = `<strong>Freelancer Notes:</strong> ${escapeHtml(d.notes)}`;
        }
      }

      // Feedback input
      const feedbackTextarea = document.querySelector('textarea[name="feedback"]');

      // Wire Approve button
      const approveBtn = document.querySelector('a.btn-green');
      if (approveBtn) {
        approveBtn.removeAttribute('href');
        approveBtn.style.cursor = 'pointer';
        approveBtn.addEventListener('click', async (e) => {
          e.preventDefault();
          approveBtn.style.pointerEvents = 'none';
          approveBtn.textContent = 'Releasing payment...';

          try {
            const note = feedbackTextarea ? feedbackTextarea.value.trim() : '';
            await SkillMatch.api.post(`/projects/${encodeURIComponent(m.id)}/approve`, { note });
            SkillMatch.showToast('Payment released successfully!', 'success');
            setTimeout(() => {
              window.location.href = `payment-released.html?milestoneId=${encodeURIComponent(m.id)}`;
            }, 500);
          } catch (err) {
            SkillMatch.showToast('Approval failed: ' + err.message, 'error');
            approveBtn.style.pointerEvents = 'auto';
            approveBtn.textContent = '✓ Approve & Release Payment';
          }
        });
      }

      // Wire Request Changes button
      const revisionBtn = document.querySelector('a.btn-orange-outline');
      if (revisionBtn) {
        revisionBtn.removeAttribute('href');
        revisionBtn.style.cursor = 'pointer';
        revisionBtn.addEventListener('click', async (e) => {
          e.preventDefault();
          const note = feedbackTextarea ? feedbackTextarea.value.trim() : '';
          if (!note || note.length < 5) {
            SkillMatch.showToast('Please enter at least 5 characters of feedback explaining the changes needed.', 'error');
            feedbackTextarea?.focus();
            return;
          }

          revisionBtn.style.pointerEvents = 'none';
          revisionBtn.textContent = 'Submitting request...';

          try {
            await SkillMatch.api.post(`/projects/${encodeURIComponent(m.id)}/revision`, { note });
            SkillMatch.showToast('Changes requested. Milestone sent back to freelancer.', 'info');
            setTimeout(() => {
              window.location.href = 'my-projects.html';
            }, 1000);
          } catch (err) {
            SkillMatch.showToast('Request failed: ' + err.message, 'error');
            revisionBtn.style.pointerEvents = 'auto';
            revisionBtn.textContent = 'Request Changes';
          }
        });
      }

      // Wire Raise Dispute link
      const disputeBtn = document.querySelector('a.btn-outline[href*="raise-dispute"]');
      if (disputeBtn) {
        disputeBtn.href = `raise-dispute.html?milestoneId=${encodeURIComponent(m.id)}`;
      }
    }
  }

  // -------------------------------------------------------------------------
  // 3. Payment Released (payment-released.html)
  // -------------------------------------------------------------------------
  async function initPaymentReleased() {
    const urlParams = new URLSearchParams(window.location.search);
    const milestoneId = urlParams.get('milestoneId') || urlParams.get('id') || 'ms-18-1';

    try {
      const milestones = await SkillMatch.api.get('/clients/me/milestones');
      const m = milestones.find(item => item.id === milestoneId) || milestones[0];
      if (m) {
        const amountEl = document.querySelector('.release-amount');
        if (amountEl) {
          amountEl.textContent = `$${Number(m.total || m.amount).toFixed(2)}`;
        }

        const pageSub = document.querySelector('.page-sub');
        if (pageSub) {
          pageSub.innerHTML = `Your payment of <strong class="release-amount" style="color:var(--text)">$${Number(m.total || m.amount).toFixed(2)}</strong> has been sent to ${escapeHtml(m.freelancerName)} for the <em>${escapeHtml(m.jobTitle)}</em> milestone.`;
        }

        const avatar = document.querySelector('.card .avatar');
        if (avatar && m.freelancerName) {
          avatar.alt = m.freelancerName;
        }

        const nameStrong = document.querySelector('.card strong[style*="font-size:18px"]');
        if (nameStrong && m.freelancerName) {
          nameStrong.textContent = m.freelancerName;
        }

        const reviewBtn = document.querySelector('a[href*="rate-freelancer"]');
        if (reviewBtn) {
          reviewBtn.href = `rate-freelancer.html?milestoneId=${encodeURIComponent(m.id)}`;
        }
      }
    } catch (e) {
      // Keep UI usable with default markup
    }
  }

  // -------------------------------------------------------------------------
  // 4. Rate Freelancer (rate-freelancer.html)
  // -------------------------------------------------------------------------
  async function initRateFreelancer() {
    const urlParams = new URLSearchParams(window.location.search);
    const milestoneId = urlParams.get('milestoneId') || urlParams.get('id') || 'ms-18-1';

    // Interactive ratings state
    let ratings = {
      communication: 5,
      quality: 5,
      timeliness: 5
    };

    const modal = document.querySelector('.modal');
    if (!modal) return;

    // Load milestone context if available
    try {
      const milestones = await SkillMatch.api.get('/clients/me/milestones');
      const m = milestones.find(item => item.id === milestoneId);
      if (m) {
        const title = modal.querySelector('h2');
        if (title) title.textContent = `Rate ${m.freelancerName}`;
        const avatar = modal.querySelector('.avatar-lg');
        if (avatar && m.freelancerName) avatar.textContent = m.freelancerName.charAt(0).toUpperCase();
      }
    } catch (e) {}

    // Make stars interactive
    const ratingRows = modal.querySelectorAll('div[style*="justify-content:space-between"]');
    const dimKeys = ['communication', 'quality', 'timeliness'];

    ratingRows.forEach((row, idx) => {
      const dim = dimKeys[idx];
      if (!dim) return;

      const starContainer = row.querySelector('span:last-child');
      if (starContainer) {
        renderDimensionStars(starContainer, dim);
      }
    });

    function renderDimensionStars(container, dim) {
      container.innerHTML = '';
      container.style.cursor = 'pointer';
      container.style.userSelect = 'none';

      for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.textContent = '★';
        star.style.fontSize = '18px';
        star.style.color = i <= ratings[dim] ? 'var(--star, #f59e0b)' : '#e2e8f0';
        star.style.margin = '0 1px';

        star.addEventListener('click', () => {
          ratings[dim] = i;
          renderDimensionStars(container, dim);
          updateOverall();
        });
        container.appendChild(star);
      }
    }

    function updateOverall() {
      const avg = Math.round((ratings.communication + ratings.quality + ratings.timeliness) / 3);
      const overallStars = modal.querySelector('.stars');
      if (overallStars) {
        overallStars.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
          const s = document.createElement('span');
          s.textContent = '★';
          s.style.color = i <= avg ? 'var(--star, #f59e0b)' : '#e2e8f0';
          overallStars.appendChild(s);
        }
      }
    }

    const submitBtn = modal.querySelector('a.btn-primary');
    const feedbackTextarea = modal.querySelector('textarea[name="review_feedback"]');

    if (submitBtn) {
      submitBtn.removeAttribute('href');
      submitBtn.style.cursor = 'pointer';
      submitBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        submitBtn.style.pointerEvents = 'none';
        submitBtn.textContent = 'Submitting review...';

        try {
          const comment = feedbackTextarea ? feedbackTextarea.value.trim() : '';
          await SkillMatch.api.post('/reviews', {
            milestoneId,
            communication: ratings.communication,
            quality: ratings.quality,
            timeliness: ratings.timeliness,
            comment
          });

          SkillMatch.showToast('Thank you! Your review has been submitted.', 'success');
          setTimeout(() => {
            window.location.href = 'client-dashboard.html';
          }, 800);
        } catch (err) {
          SkillMatch.showToast('Could not submit review: ' + err.message, 'error');
          submitBtn.style.pointerEvents = 'auto';
          submitBtn.textContent = 'Submit Review';
        }
      });
    }
  }

  // -------------------------------------------------------------------------
  // 5. Raise Dispute (raise-dispute.html)
  // -------------------------------------------------------------------------
  async function initRaiseDispute() {
    const urlParams = new URLSearchParams(window.location.search);
    const milestoneId = urlParams.get('milestoneId') || urlParams.get('id') || 'ms-18-2';

    let activeMilestone = null;
    try {
      const milestones = await SkillMatch.api.get('/clients/me/milestones');
      activeMilestone = milestones.find(m => m.id === milestoneId) || milestones[0];
      if (activeMilestone) {
        const eyebrow = document.querySelector('p[style*="letter-spacing"]');
        if (eyebrow) {
          eyebrow.textContent = `DISPUTE · ${activeMilestone.jobTitle.toUpperCase()}`;
        }
        const pageSub = document.querySelector('.page-sub');
        if (pageSub) {
          pageSub.innerHTML = `Filing freezes the <strong style="color:var(--text)">$${Number(activeMilestone.amount).toFixed(2)}</strong> in escrow — nothing moves until the case is resolved.`;
        }
      }
    } catch (e) {}

    const formCard = document.querySelector('.form-card');
    if (!formCard) return;

    const fileDisputeBtn = formCard.querySelector('a.btn-primary[href*="dispute-status"]');
    const cancelBtn = formCard.querySelector('a.btn-outline');
    if (cancelBtn) {
      cancelBtn.href = `work-approval.html?milestoneId=${encodeURIComponent(milestoneId)}`;
    }

    if (fileDisputeBtn) {
      fileDisputeBtn.removeAttribute('href');
      fileDisputeBtn.style.cursor = 'pointer';
      fileDisputeBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        // Selected reason
        const reasonInput = formCard.querySelector('input[name="reason"]:checked');
        const reason = reasonInput?.closest('label')?.querySelector('strong')?.textContent.trim() || 'Work quality';

        // Description
        const descInput = formCard.querySelector('textarea');
        const description = descInput ? descInput.value.trim() : '';
        if (!description || description.length < 10) {
          SkillMatch.showToast('Please describe the issue in at least 10 characters.', 'error');
          descInput?.focus();
          return;
        }

        // Desired outcome
        const outcomeInput = formCard.querySelector('input[name="outcome"]:checked');
        const desiredOutcome = outcomeInput?.closest('label')?.querySelector('strong')?.textContent.trim() || 'Request a revision';

        // Partial refund
        const partialInput = formCard.querySelector('input.input[placeholder="0.00"]');
        const partialRefundAmount = partialInput && partialInput.value ? Number(partialInput.value) : undefined;

        if (desiredOutcome === 'Partial refund' && (!partialRefundAmount || partialRefundAmount <= 0)) {
          SkillMatch.showToast('Please enter a valid partial refund amount.', 'error');
          partialInput?.focus();
          return;
        }

        fileDisputeBtn.style.pointerEvents = 'none';
        fileDisputeBtn.textContent = 'Filing dispute...';

        try {
          const res = await SkillMatch.api.post('/disputes', {
            milestoneId: activeMilestone?.id || milestoneId,
            reason,
            description,
            desiredOutcome,
            partialRefundAmount
          });

          SkillMatch.showToast('Dispute filed. Escrow is locked.', 'info');
          const dispId = res.disputeId || res.id || 'DIS-2026-0001';
          setTimeout(() => {
            window.location.href = `dispute-status.html?id=${encodeURIComponent(dispId)}`;
          }, 800);
        } catch (err) {
          SkillMatch.showToast('Failed to file dispute: ' + err.message, 'error');
          fileDisputeBtn.style.pointerEvents = 'auto';
          fileDisputeBtn.textContent = 'File dispute';
        }
      });
    }
  }

  // -------------------------------------------------------------------------
  // 6. Dispute Status (dispute-status.html)
  // -------------------------------------------------------------------------
  async function initDisputeStatus() {
    const urlParams = new URLSearchParams(window.location.search);
    const disputeId = urlParams.get('id') || urlParams.get('disputeId');

    try {
      const disputes = await SkillMatch.api.get('/disputes');
      if (!Array.isArray(disputes) || disputes.length === 0) return;

      const d = (disputeId ? disputes.find(x => x.id === disputeId) : null) || disputes[0];
      if (!d) return;

      const eyebrow = document.querySelector('p[style*="letter-spacing"]');
      if (eyebrow) {
        eyebrow.textContent = `DISPUTE ${d.id} · ${d.jobTitle.toUpperCase()}`;
      }

      const h1 = document.querySelector('.page-title');
      if (h1) {
        h1.textContent = `${d.milestoneLabel} under review`;
      }

      const badge = document.querySelector('.badge');
      if (badge) {
        badge.textContent = d.status;
        badge.className = `badge ${d.status === 'Resolved' ? 'badge-done' : 'badge-progress'}`;
      }

      const pageSub = document.querySelector('.page-sub');
      if (pageSub) {
        pageSub.innerHTML = `Filed for <strong>${escapeHtml(d.reason)}</strong>. The <strong style="color:var(--text)">$${Number(d.milestoneAmount).toFixed(2)}</strong> stays locked in escrow until resolution.`;
      }

      // Counterparty response card
      const respCard = document.querySelectorAll('.card')[1];
      if (respCard) {
        if (d.freelancerResponse) {
          respCard.innerHTML = `
            <h3 style="margin:0 0 4px">Freelancer response</h3>
            <p style="color:var(--text);font-size:14px;margin:8px 0">${escapeHtml(d.freelancerResponse)}</p>
            <div style="font-size:12px;color:var(--muted)">Responded ${escapeHtml(d.respondedAt || '')}</div>
          `;
        }
      }

      // Return to review button
      const reviewBtn = document.querySelector('a.btn-primary[href*="work-approval"]');
      if (reviewBtn) {
        reviewBtn.href = `work-approval.html?milestoneId=${encodeURIComponent(d.milestoneId)}`;
      }
    } catch (e) {
      // Keep static display
    }
  }

  // -------------------------------------------------------------------------
  // 7. File & Photo Uploads (create-client-account.html & post-project-details.html)
  // -------------------------------------------------------------------------
  function initUploads() {
    // Client profile photo upload preview
    const photoUploadBox = document.querySelector('.photo-upload');
    if (photoUploadBox) {
      photoUploadBox.style.cursor = 'pointer';
      let fileInput = document.getElementById('avatarFileInput');
      if (!fileInput) {
        fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.id = 'avatarFileInput';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);
      }

      photoUploadBox.addEventListener('click', () => {
        fileInput.click();
      });

      fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = (event) => {
            photoUploadBox.innerHTML = `
              <img src="${event.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Uploaded Avatar">
            `;
            SkillMatch.showToast('Profile photo ready for account creation', 'info');
          };
          reader.readAsDataURL(file);
        }
      });
    }

    // Attachments upload in post-project-details.html
    const attachBtn = document.querySelector('.field span.btn-outline');
    if (attachBtn && attachBtn.textContent.includes('Attach files')) {
      attachBtn.style.cursor = 'pointer';
      attachBtn.removeAttribute('aria-disabled');

      let attachInput = document.getElementById('projectAttachInput');
      if (!attachInput) {
        attachInput = document.createElement('input');
        attachInput.type = 'file';
        attachInput.id = 'projectAttachInput';
        attachInput.multiple = true;
        attachInput.style.display = 'none';
        document.body.appendChild(attachInput);
      }

      const fileListDiv = document.createElement('div');
      fileListDiv.id = 'attachedFilesList';
      fileListDiv.style.cssText = 'display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;';
      attachBtn.parentElement.appendChild(fileListDiv);

      attachBtn.addEventListener('click', () => {
        attachInput.click();
      });

      attachInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files || []);
        fileListDiv.innerHTML = files.map(f => `
          <span style="font-size:12px;background:#e0e7ff;color:#1e40af;padding:4px 10px;border-radius:12px;display:inline-flex;align-items:center;gap:6px;">
            📄 ${escapeHtml(f.name)} (${(f.size / 1024).toFixed(0)} KB)
          </span>
        `).join('');

        if (files.length > 0) {
          SkillMatch.showToast(`Attached ${files.length} file(s)`, 'success');
        }
      });
    }
  }

  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
