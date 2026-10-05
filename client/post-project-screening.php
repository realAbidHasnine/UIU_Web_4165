<?php require __DIR__ . '/includes/auth.php'; $flashHtml = take_flashes(); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post a Project - Step 3 - SkillMatch</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="../assets/css/global.css"></head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container">
<div class="steps">
<span>Project Details</span>
<span>/</span>
<span>Skills &amp; Budget</span>
<span>/</span>
<strong>Step 3 of 4 - Screening</strong>
<span>/</span>
<span>Review</span>
</div>
<?= $flashHtml ?>
<form class="form-card" action="save-project.php" method="POST">
<input type="hidden" name="wizard_step" value="3">
<input type="hidden" name="project_id" value="<?= (int) ($_GET['project_id'] ?? 0) ?>">
<div class="field">
<label for="question-0">Screening questions</label>
<p style="color:var(--muted);font-size:13.5px;margin:0 0 10px">Applicants must answer these before proposing. Fewer, sharper questions get better proposals.</p>
<div id="questions">
<div class="q-row" style="display:flex;gap:8px;margin-bottom:10px">
  <input class="input" name="screening_questions[]" placeholder="e.g. Describe a similar project you shipped in the last year."> <button type="button" class="chip-x q-remove" aria-label="Remove question">&#10005;</button>
</div>
<div class="q-row" style="display:flex;gap:8px;margin-bottom:10px">
  <input class="input" name="screening_questions[]" placeholder="e.g. What would you deliver in the first two weeks?"> <button type="button" class="chip-x q-remove" aria-label="Remove question">&#10005;</button>
</div>
</div>
<button type="button" class="btn btn-outline" id="add-question">Add question</button>
</div>
<hr class="hr">
<div class="field">
<label>Proposal requirements</label>
<label class="check"><input type="checkbox" name="cover_letter_required" value="1" checked> Cover letter required</label>
<label class="check"><input type="checkbox" name="portfolio_links_required" value="1" checked> Portfolio links required</label>
<label class="check"><input type="checkbox" name="verified_skill_test_required" value="1"> Verified skill test required</label>
</div>
<hr class="
div class="two-btns"> <a class="btn btn-outline" href="post-project.php" style="border-color:var(--border);color:#374151">Back</a> <button class="btn btn-primary" type="submit">Continue</button>
</div>
</form>
<script>
(function () {
  var wrap = document.getElementById('questions');
  var add  = document.getElementById('add-question');
  var placeholder = 'e.g. Describe a similar project you shipped in the last year.';
  function newRow() {
    var row = document.createElement('div');
    row.className = 'q-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:10px';
    row.innerHTML =
      '<input class="input" name="screening_questions[]" placeholder="' + placeholder + '">' +
      '<button type="button" class="chip-x q-remove" aria-label="Remove question">&#10005;</button>';
    return row;
  }
  add.addEventListener('click', function () {
    var row = newRow();
    wrap.appendChild(row);
    row.querySelector('input').focus();
  });
  wrap.addEventListener('click', function (event) {
    if (!event.target.closest('.q-remove')) {
      return;
    }
    var rows = wrap.querySelectorAll('.q-row');
    // Always leave one row on screen, just empty it.
    if (rows.length > 1) {
      event.target.closest('.q-row').remove();
    } else {
      wrap.querySelector('input').value = '';
    }
  });
})();
</script></main></body></html>
