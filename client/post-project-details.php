<?php require __DIR__ . '/includes/auth.php'; $flashHtml = take_flashes(); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post a Project - Step 1 - SkillMatch</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="../assets/css/global.css"></head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container">
<div class="steps">
<strong>Step 1 of 4 - Project Details</strong>
<span>/</span>
<span>Skills &amp; Budget</span>
<span>/</span>
<span>Screening</span>
<span>/</span>
<span>Review</span>
</div>
<?= $flashHtml ?>
<form class="form-card" action="save-project.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="wizard_step" value="1">
<input type="hidden" name="project_id" value="<?= (int) ($_GET['project_id'] ?? 0) ?>">
<div class="field">
<label for="title">Project title</label><input class="input" id="title" name="title" placeholder="e.g. E-commerce Redesign and Migration" required>
</div>
<div class="field">
<label for="category_id">Category</label><select class="select" id="category_id" name="category_id" required>
<option value="">Select a category</option><option value="1">Web Development</option><option value="2">UI/UX Design</option><option value="3">Mobile App Dev</option><option value="4">Data Science</option><option value="5">Content Writing</option><option value="6">Digital Marketing</option></select>
</div>
<div class="field">
<label for="description">Description</label><textarea class="textarea" id="description" name="description" placeholder="Describe the work, deliverables and timeline..." required></textarea>
</div>
<div class="field">
<label for="attachments">Attachments</label><input type="file" id="attachments" name="attachments[]" multiple>
<p style="color:var(--muted);font-size:12.5px;margin:8px 0 0">Figma files, briefs or references — up to 5 files.</p>
</div>
<hr class="
div class="two-btns"> <a class="btn btn-outline" href="client-dashboard.php" style="border-color:var(--border);color:#374151">Back</a> <button class="btn btn-primary" type="submit">Continue</button>
</div>
</form></main></body></html>
