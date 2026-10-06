<?php require __DIR__ . '/includes/auth.php'; $flashHtml = take_flashes(); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messages - SkillMatch</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="../assets/css/global.css"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;700;800&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&display=swap" rel="stylesheet"></head>
<body class="dossier-ground">
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container dossier-shell" style="max-width:1280px">
<?= $flashHtml ?>
<div class="file-rail">
<div style="position:relative;z-index:1">
<div class="case-id">Messages · 3 open files · Replies stay attached to the project</div>
<h1>Dashboard redesign — Sabbir Hossain</h1>
<div class="case-sub">Case file SM-2026-0847 · Freelancer · Online now</div>
</div>
</div>
<div class="docket">
<aside class="docket-side" aria-label="Conversations">
<div style="padding:8px 8px 16px">
<input class="search" id="message_search" name="message_search" aria-label="Search messages" style="width:100%;min-width:0" placeholder="Search messages...">
</div>
<button type="button" class="thread active unread">
<span class="avatar-lg" style="background:#dcefe4;color:#065f46;font-size:14px">SH</span>
<span style="flex:1;min-width:0"><span class="tname">Sabbir Hossain
<span class="flag" aria-label="Unread"></span></span>
<span class="tmeta">Freelancer · 10:42 AM</span><span class="tsnip">Mainly regarding the timeline charts...<span class="count">2</span>
</span></span></button>
<button type="button" class="thread">
<span class="avatar-lg" style="font-size:14px">A</span>
<span style="flex:1;min-width:0"><span class="tname">Abdullah
<span class="flag" aria-hidden="true"></span></span>
<span class="tmeta">Freelancer · Yesterday</span><span class="tsnip">Thanks for the feedback. I have updated the...</span></span></button>
<button type="button" class="thread">
<span class="avatar-lg" style="background:#e8dcc3;color:#7c4a03;font-size:14px">SJ</span>
<span style="flex:1;min-width:0"><span class="tname">Sumaiya Jahan
<span class="flag" aria-hidden="true"></span></span>
<span class="tmeta">Freelancer · Oct 24</span><span class="tsnip">Project completed and final files attached.</span></span></button>
</aside>
<section style="display:flex;flex-direction:column;min-width:0" aria-label="Transcript">
<div class="project-rail">
<span class="avatar-lg" style="background:#dcefe4;color:#065f46">M</span>
<div style="flex:1;min-width:0">
<strong>Sabbir Hossain</strong> <span class="stamp ledger" style="font-size:11px;padding:4px 8px 3px">Freelancer</span>
<div style="font-size:12px;color:var(--ink-soft)">Online · Replies in this file</div>
</div>
<a href="work-approval.php" style="font-size:13.5px;font-weight:700">View Project</a>
</div>
<div class="transcript">
<div class="date-rule">Today · Dashboard redesign</div>
<div class="slip">Hi there! I have reviewed the brief for the dashboard redesign. I have a few quick questions about the data visualization requirements.<span class="when">10:30 AM</span>
</div>
<div class="slip out">Great, thanks for taking a look. What specific aspects of the data visualization need clarification?<span class="when">10:35 AM</span>
</div>
<div class="slip">Mainly regarding the timeline charts. Do you prefer a unified view or separate widgets for each metric? I will send over the updated wireframes showing both options shortly.<span class="when">10:42 AM</span>
</div>
</div>
<div class="composer">
<span style="align-self:center;display:inline-flex;color:var(--ink-soft)" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.4 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.65 5.66l-9.2 9.19a2 2 0 0 1-2.82-2.83l8.49-8.48"/></svg></span>
<form action="send-message.php" method="POST" style="display:flex;gap:8px;flex:1">
  <input type="hidden" name="conversation_id" value="<?= (int) ($_GET['c'] ?? 1) ?>"> <input class="input" id="chat_message" name="chat_message" aria-label="Type a message" placeholder="Type a message..." style="border-radius:8px" maxlength="2000" required> <button class="btn-file solid" type="submit">Send</button>
</form>
</div>
</section>
</main>
<script src="../assets/js/api.js"></script>
<script src="../assets/js/client-chat.js"></script>
</body></html>
