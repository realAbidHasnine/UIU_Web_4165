<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Raise a Dispute - SkillMatch</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="../assets/css/global.css"></head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container" style="max-width:720px">
<p style="font-size:12px;letter-spacing:1px;color:var(--muted)">DISPUTE &middot; PYTHON DATA SCRAPING SCRIPT</p>
<h1 class="page-title">Raise a dispute</h1>
<p class="page-sub">Filing freezes the<strong style="color:var(--text)">$450.00</strong>in escrow — nothing moves until the case is resolved.</p>
<div class="form-card" style="max-width:none">
<div class="field">
<label>Reason</label>
<label class="radio-card"><input type="radio" name="reason" checked>
<span><strong>Work quality</strong><span>Deliverable does not meet the agreed requirements.</span></span></label>
<label class="radio-card"><input type="radio" name="reason">
<span><strong>Missed deadline</strong><span>Milestone arrived later than agreed with no notice.</span></span></label>
<label class="radio-card"><input type="radio" name="reason">
<span><strong>Communication breakdown</strong><span>Freelancer is unresponsive to feedback and messages.</span></span></label>
<label class="radio-card"><input type="radio" name="reason">
<span><strong>Other</strong><span>Explain below.</span></span></label>
</div>
<div class="field">
<label>What happened</label><textarea class="textarea" placeholder="Describe the issue with the delivered work..."></textarea>
</div>
<div class="field">
<label>Desired outcome</label>
<label class="radio-card"><input type="radio" name="outcome" checked>
<span><strong>Request a revision</strong><span>Freelancer reworks and resubmits. Escrow stays locked.</span></span></label>
<label class="radio-card"><input type="radio" name="outcome">
<span><strong>Partial refund</strong><span>You propose an amount; the rest releases on agreement.</span></span></label>
<div class="inline">
<span style="font-size:14px">$</span><input class="input" inputmode="decimal" placeholder="0.00">
</div>
<label class="radio-card" style="margin-top:10px"><input type="radio" name="outcome">
<span><strong>Full refund</strong><span>The full $450.00 returns to your balance.</span></span></label>
</div>
<hr class="hr">
<div class="two-btns">
<a class="btn btn-outline" href="work-approval.php" style="border-color:var(--border);color:#374151">Cancel</a>
<a class="btn btn-primary" href="dispute-status.php">File dispute</a>
</div>
</div>
</main>
</body></html>
