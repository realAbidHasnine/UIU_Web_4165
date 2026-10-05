<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - SkillMatch</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="../assets/css/global.css"></head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container">
<section class="masthead" aria-label="Account overview">
<svg class="masthead-mark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4.5 13.5H11L9.5 22 19 10h-6.5L13 2z"/></svg>
<div class="masthead-top">
<div>
<h1 class="page-title">Welcome back, Abida</h1>
<p class="page-sub">Here is an overview of your current projects and freelancer activity.</p>
</div>
<a class="btn btn-inverse" href="post-project-details.php">+ Post a New Project</a>
</div>
<div class="perf-strip">
<div class="perf-cell">
<div class="perf-top">
<span>Active Projects</span>
<span class="ic">
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
</div>
<div class="perf-num">4</div>
</div>
<div class="perf-cell">
<div class="perf-top">
<span>Proposals Received</span>
<span class="ic">
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
</div>
<div class="perf-num">12</div>
</div>
<div class="perf-cell">
<div class="perf-top">
<span>Freelancers Hired</span>
<span class="ic">
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
</div>
<div class="perf-num">8</div>
</div>
<div class="perf-cell">
<div class="perf-top">
<span>Total Spent</span>
<span class="ic">
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg></span>
</div>
<div class="perf-num">$12,450</div>
</div>
</div>
</section>
<div class="section-head">
<h2>Recent Projects</h2>
<a href="my-projects.php" style="font-size:13.5px;font-weight:600">View All Projects</a>
</div>
<div class="card">
<div class="list-row">
<div>
<p class="row-title">E-commerce Redesign and Migration<span class="badge badge-open">Open</span>
</p>
<div class="row-meta">
<span>Posted 2 days ago</span>
<span>5 Proposals</span>
</div>
</div>
<div class="price">
<strong>Fixed Price</strong>$4,500 <a class="btn btn-outline" style="margin-left:10px" href="review-proposal.php">View</a>
</div>
</div>
<div class="list-row">
<div>
<p class="row-title">React Developer for SaaS<span class="badge badge-progress">In Progress</span>
</p>
<div class="row-meta">
<span>Started 1 week ago</span>
<span>1 Freelancer</span>
</div>
</div>
<div class="price">
<strong>Hourly</strong>$55/hr <span class="btn btn-outline" style="margin-left:10px" aria-disabled="true">View</span>
</div>
</div>
<div class="list-row">
<div>
<p class="row-title">Logo Refresh<span class="badge badge-done">Completed</span>
</p>
<div class="row-meta">
<span>Finished 3 weeks ago</span>
</div>
</div>
<div class="price">
<strong>Fixed Price</strong>$500 <span class="btn btn-outline" style="margin-left:10px" aria-disabled="true">View</span>
</div>
</div>
<div class="list-row">
<div>
<p class="row-title">API Integration<span class="badge badge-await">Awaiting Approval</span>
</p>
<div class="row-meta">
<span>Submitted today</span>
</div>
</div>
<div class="price">
<strong>Fixed Price</strong>$1,200 <span class="btn btn-outline" style="margin-left:10px" aria-disabled="true">View</span>
</div>
</div>
</div>
</main>
</body>
</html>
