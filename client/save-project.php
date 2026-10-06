<?php

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: post-project-details.php');
    exit;
}

start_session();
$conn  = db();
$owner = current_client_id();
$step  = max(1, min(4, (int) ($_POST['wizard_step'] ?? 1)));

if (!isset($_SESSION['wizard_project'])) {
    $_SESSION['wizard_project'] = [];
}

try {
    // Step 1: Basic details
    if ($step === 1) {
        $title       = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $category    = (int) ($_POST['category_id'] ?? 1);

        if ($title === '') {
            flash('Give the project a title.');
            header('Location: post-project-details.php');
            exit;
        }

        if ($description === '') {
            flash('Describe the work so freelancers can respond.');
            header('Location: post-project-details.php');
            exit;
        }

        $_SESSION['wizard_project']['title']       = $title;
        $_SESSION['wizard_project']['description'] = $description;
        $_SESSION['wizard_project']['category_id'] = $category;

        header('Location: post-project.php');
        exit;
    }

    // Step 2: Skills and Budget
    if ($step === 2) {
        $budgetMin = parse_money($_POST['budget_min'] ?? '0');
        $budgetMax = parse_money($_POST['budget_max'] ?? '0');
        $skills    = trim((string) ($_POST['skills'] ?? ''));
        $duration  = trim((string) ($_POST['estimated_duration'] ?? ''));

        $budget = $budgetMax > 0 ? $budgetMax : ($budgetMin > 0 ? $budgetMin : 500);

        $_SESSION['wizard_project']['budget']     = $budget;
        $_SESSION['wizard_project']['budget_min'] = $budgetMin;
        $_SESSION['wizard_project']['budget_max'] = $budgetMax;
        $_SESSION['wizard_project']['skills']     = $skills;
        $_SESSION['wizard_project']['duration']   = $duration;

        header('Location: post-project-screening.php');
        exit;
    }

    // Step 3: Screening questions
    if ($step === 3) {
        $questions = array_filter(array_map('trim', $_POST['questions'] ?? []));
        $_SESSION['wizard_project']['questions'] = $questions;

        header('Location: post-project-review.php');
        exit;
    }

    // Step 4: Milestones and Publish
    if ($step === 4) {
        $rows = [];
        if (!empty($_POST['milestones']) && is_array($_POST['milestones'])) {
            foreach ($_POST['milestones'] as $entry) {
                $title  = trim((string) ($entry['title'] ?? ''));
                $amount = parse_money($entry['amount'] ?? '0');
                if ($title !== '' && $amount > 0) {
                    $rows[] = [$title, round($amount, 2)];
                }
            }
        }

        if (empty($rows)) {
            $budget = $_SESSION['wizard_project']['budget'] ?? 500;
            $rows[] = ['Initial Delivery', $budget];
        }

        $title       = $_SESSION['wizard_project']['title'] ?? 'New Project';
        $description = $_SESSION['wizard_project']['description'] ?? 'Project description';
        $categoryId  = $_SESSION['wizard_project']['category_id'] ?? 1;
        $budget      = $_SESSION['wizard_project']['budget'] ?? 1000;

        $jobId = 'job-' . rand(100, 9999);

        $conn->beginTransaction();

        $stmt = $conn->prepare(
            "INSERT INTO jobs (id, client_id, category_id, title, description, budget, status)
             VALUES (?, ?, ?, ?, ?, ?, 'Open')"
        );
        $stmt->execute([
            $jobId,
            $owner,
            $categoryId,
            $title,
            $description,
            (string) $budget
        ]);

        $mStmt = $conn->prepare(
            "INSERT INTO milestones (job_id, title, amount, status)
             VALUES (?, ?, ?, 'Pending')"
        );

        foreach ($rows as $item) {
            $mStmt->execute([$jobId, $item[0], $item[1]]);
        }

        // Add notification for the client
        try {
            $conn->prepare(
                "INSERT INTO notifications (user_id, type, title, body, link)
                 VALUES (?, 'project', 'Project published', ?, ?)"
            )->execute([
                $owner,
                'Your project "' . $title . '" has been published successfully.',
                'my-projects.php'
            ]);
        } catch (\Throwable $e) {}

        $conn->commit();

        unset($_SESSION['wizard_project']);

        flash('Project published successfully! It is now live for freelancers.', 'success');
        header('Location: my-projects.php');
        exit;
    }
} catch (\Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    flash('Could not save the project: ' . $e->getMessage());
    header('Location: post-project-details.php');
    exit;
}