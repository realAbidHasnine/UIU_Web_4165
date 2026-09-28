<?php
require_once __DIR__ . '/../api/config/db.php';

echo "--- Skill Categories ---\n";
$rows = $pdo->query("SELECT id, name, slug, question_count FROM skill_categories")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Name: {$r['name']} | Slug: '{$r['slug']}' | Count: {$r['question_count']}\n";
}

echo "\n--- Orphan Questions ---\n";
$orphans = $pdo->query("SELECT id, category, question FROM skill_questions WHERE category NOT IN (SELECT id FROM skill_categories)")->fetchAll(PDO::FETCH_ASSOC);
echo "Orphan question count: " . count($orphans) . "\n";
foreach ($orphans as $o) {
    echo "ID: {$o['id']} | Category: {$o['category']} | Q: {$o['question']}\n";
}

echo "\n--- Milestones Count ---\n";
echo "Milestones: " . $pdo->query("SELECT COUNT(*) FROM project_milestones")->fetchColumn() . "\n";
echo "Payments: " . $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn() . "\n";
echo "Disputes: " . $pdo->query("SELECT COUNT(*) FROM disputes")->fetchColumn() . "\n";
echo "Reviews: " . $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn() . "\n";
echo "Notifications: " . $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn() . "\n";
echo "Approvals: " . $pdo->query("SELECT COUNT(*) FROM approvals")->fetchColumn() . "\n";
