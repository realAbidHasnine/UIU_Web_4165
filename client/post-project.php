<?php require __DIR__ . '/includes/auth.php'; $flashHtml = take_flashes(); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post a Project - Step 2 - SkillMatch</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="../assets/css/global.css"></head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container">
<div class="steps">
<span>Project Details</span>
<span>/</span>
<strong>Step 2 of 4 - Skills &amp; Budget</strong>
<span>/</span>
<span>Screening</span>
<span>/</span>
<span>Review</span>
</div>
<?= $flashHtml ?>
<form class="form-card" action="save-project.php" method="POST">
<input type="hidden" name="wizard_step" value="2">
<input type="hidden" name="project_id" value="<?= (int) ($_GET['project_id'] ?? 0) ?>">
<div class="field">
<label for="required_skills">Required Skills</label>
<div class="inline">
<input class="input" id="required_skills" name="required_skills" placeholder="e.g. React, UI Design">
<button type="button" class="btn btn-outline" id="add-skill">Add</button>
</div>
<div id="skill-chips" style="margin-top:10px">
  <span class="chip">React
<button type="button" class="chip-x" aria-label="Remove React">&#10005;</button></span>
  <span class="chip">TypeScript
<button type="button" class="chip-x" aria-label="Remove TypeScript">&#10005;</button></span>
  <span class="chip">UI Design
<button type="button" class="chip-x" aria-label="Remove UI Design">&#10005;</button></span>
</div>
<input type="hidden" name="skills" id="skills-value" value="React,TypeScript,UI Design">
</div>
<hr class="hr">
<div class="field">
<label>Budget Type</label>
<div class="inline" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
<label class="radio-card" style="margin:0"><input type="radio" name="budget_type" value="fixed" checked>
<span><strong>Fixed Price</strong><span>One agreed total for the whole project.</span></span></label>
<label class="radio-card" style="margin:0"><input type="radio" name="budget_type" value="hourly">
<span><strong>Hourly Rate</strong><span>Pay per hour as work is delivered.</span></span></label>
</div>
</div>
<div class="field">
<label for="budget_min">Budget</label>
<div class="inline">
<input class="input" id="budget_min" name="budget_min" type="number" min="0" step="0.01" placeholder="$  Min" required>
<span>-</span><input class="input" name="budget_max" type="number" min="0" step="0.01" placeholder="$  Max">
</div>
</div>
<div class="field">
<label for="estimated_duration">Estimated Duration</label><select class="select" id="estimated_duration" name="estimated_duration"><option value="">Select duration</option><option value="less-than-1-month">Less than 1 month</option><option value="1-3-months">1-3 months</option><option value="3-6-months">3-6 months</option></select>
</div>
<hr class="hr">
<div class="two-btns">
<a class="btn btn-outline" href="post-project-details.php" style="border-color:var(--border);color:#374151">Back</a>
<button class="btn btn-primary" type="submit">Continue</button>
</div>
</form>
<script>
(function () {
  var chips  = document.getElementById('skill-chips');
  var hidden = document.getElementById('skills-value');
  var input  = document.getElementById('required_skills');
  var add    = document.getElementById('add-skill');
  // Keep the hidden field in step with whatever chips are on screen.
  function sync() {
    var names = Array.prototype.map.call(
      chips.querySelectorAll('.chip'),
      function (chip) {
        return chip.dataset.skill;
      }
    );
    hidden.value = names.join(',');
  }
  function addSkill() {
    var name = input.value.trim();
    if (!name) {
      return;
    }
    var existing = chips.querySelector('.chip[data-skill="' + name + '"]');
    if (existing) {
      input.value = '';
      return;
    }
    var chip = document.createElement('span');
    chip.className = 'chip';
    chip.dataset.skill = name;
    chip.appendChild(document.createTextNode(name));
    var remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'chip-x';
    remove.innerHTML = '&#10005;';
    remove.setAttribute('aria-label', 'Remove ' + name);
    chip.appendChild(remove);
    chips.appendChild(chip);
    input.value = '';
    sync();
  }
  add.addEventListener('click', addSkill);
  input.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      addSkill();
    }
  });
  chips.addEventListener('click', function (event) {
    var button = event.target.closest('.chip-x');
    if (button) {
      button.closest('.chip').remove();
      sync();
    }
  });
  sync();
})();
</script>
</main>
</body></html>
