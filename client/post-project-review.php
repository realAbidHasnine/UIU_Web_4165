<?php require __DIR__ . '/includes/auth.php'; $flashHtml = take_flashes(); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post a Project - Step 4 - SkillMatch</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="../assets/css/global.css"></head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container">
<div class="steps">
<span>Project Details</span>
<span>/</span>
<span>Skills &amp; Budget</span>
<span>/</span>
<span>Screening</span>
<span>/</span>
<strong>Step 4 of 4 - Review</strong>
</div>
<?= $flashHtml ?>
<form class="form-card" action="save-project.php" method="POST">
<input type="hidden" name="wizard_step" value="4">
<input type="hidden" name="project_id" value="<?= (int) ($_GET['project_id'] ?? 0) ?>">
<div class="field">
<label>Posting summary</label>
<div id="summary-skills" style="margin-top:6px">
<span class="chip">React</span>
<span class="chip">TypeScript</span>
<span class="chip">UI Design</span>
</div>
<p id="summary-facts" style="color:var(--muted);font-size:13.5px;margin:10px 0 0">Fixed Price &middot; 2 screening questions &middot; cover letter + portfolio required</p>
</div>
<hr class="hr">
<div class="field">
<label for="m-0-title">Milestones — must total the budget</label>
<div id="milestones">
<div class="m-row" style="display:flex;gap:8px;margin-bottom:10px;align-items:center">
  <span style="font-size:13px;color:var(--muted);min-width:64px">Milestone 1</span> <input class="input" id="m-0-title" name="milestones[0][title]" placeholder="Milestone title"> <input class="input" name="milestones[0][amount]" type="number" min="0" step="0.01" placeholder="0.00" style="max-width:130px"> <button type="button" class="chip-x m-remove" aria-label="Remove milestone">&#10005;</button>
</div>
<div class="m-row" style="display:flex;gap:8px;margin-bottom:10px;align-items:center">
  <span style="font-size:13px;color:var(--muted);min-width:64px">Milestone 2</span> <input class="input" name="milestones[1][title]" placeholder="Milestone title"> <input class="input" name="milestones[1][amount]" type="number" min="0" step="0.01" placeholder="0.00" style="max-width:130px"> <button type="button" class="chip-x m-remove" aria-label="Remove milestone">&#10005;</button>
</div>
<div class="m-row" style="display:flex;gap:8px;margin-bottom:10px;align-items:center">
  <span style="font-size:13px;color:var(--muted);min-width:64px">Milestone 3</span> <input class="input" name="milestones[2][title]" placeholder="Milestone title"> <input class="input" name="milestones[2][amount]" type="number" min="0" step="0.01" placeholder="0.00" style="max-width:130px"> <button type="button" class="chip-x m-remove" aria-label="Remove milestone">&#10005;</button>
</div>
</div>
<button type="button" class="btn btn-outline" id="add-milestone">Add milestone</button>
</div>
<div class="card" style="padding:14px 18px;background:var(--bg-2)">
<div style="display:flex;justify-content:space-between;font-size:14px">
<span>Milestones total</span><strong id="total-out">$0.00</strong>
</div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-top:4px">
<span>Project budget</span><span id="budget-out">$4,500.00</span>
</div>
<p id="total-msg" style="font-size:13px;color:var(--green);font-weight:600;margin:8px 0 0">Totals match the budget. Each milestone locks in escrow on hire.</p>
</div>
<hr class="
div class="two-btns"> <a class="btn btn-outline" href="post-project-screening.php" style="border-color:var(--border);color:#374151">Back</a> <button class="btn btn-primary" type="submit">Publish project</button>
</div>
</form>
<script>
(function () {
  var wrap      = document.getElementById('milestones');
  var add       = document.getElementById('add-milestone');
  var totalOut  = document.getElementById('total-out');
  var msg       = document.getElementById('total-msg');
  var budgetOut = document.getElementById('budget-out');
  var budget = parseFloat(budgetOut.textContent.replace(/[^0-9.]/g, '')) || 0;
  function money(value) {
    var withCommas = value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return '$' + withCommas;
  }
  // Renumber the labels so they always read Milestone 1, 2, 3...
  function relabel() {
    var rows = wrap.querySelectorAll('.m-row');
    Array.prototype.forEach.call(rows, function (row, index) {
      row.querySelector('.m-row span').textContent = 'Milestone ' + (index + 1);
    });
  }
  function recalculate() {
    var inputs = wrap.querySelectorAll('input[name$="[amount]"]');
    var sum = 0;
    Array.prototype.forEach.call(inputs, function (input) {
      sum += parseFloat(input.value) || 0;
    });
    totalOut.textContent = money(sum);
    var balanced = Math.abs(sum - budget) < 0.005;
    if (balanced) {
      msg.textContent = 'Totals match the budget. Each milestone locks in escrow on hire.';
    } else {
      msg.textContent = 'Milestones must total ' + money(budget) + ' before you can publish.';
    }
    msg.style.color = balanced ? 'var(--green)' : 'var(--muted)';
  }
  function newRow(index) {
    var row = document.createElement('div');
    row.className = 'm-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:10px;align-items:center';
    row.innerHTML =
      '<span style="font-size:13px;color:var(--muted);min-width:64px"></span>' +
      '<input class="input" name="milestones[' + index + '][title]" placeholder="Milestone title">' +
      '<input class="input" name="milestones[' + index + '][amount]" type="number"' +
      ' min="0" step="0.01" placeholder="0.00" style="max-width:130px">' +
      '<button type="button" class="chip-x m-remove" aria-label="Remove milestone">&#10005;</button>';
    return row;
  }
  add.addEventListener('click', function () {
    var index = wrap.querySelectorAll('.m-row').length;
    var row = newRow(index);
    wrap.appendChild(row);
    relabel();
    row.querySelector('input').focus();
    recalculate();
  });
  wrap.addEventListener('click', function (event) {
    if (!event.target.closest('.m-remove')) {
      return;
    }
    var rows = wrap.querySelectorAll('.m-row');
    if (rows.length > 1) {
      event.target.closest('.m-row').remove();
      relabel();
    }
    recalculate();
  });
  wrap.addEventListener('input', recalculate);
  relabel();
  recalculate();
})();
</script></main></body></html>
