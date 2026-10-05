<?php

require_once __DIR__ . '/includes/auth.php';

/*
 * Posting wizard. All four steps post here and are told apart by wizard_step.
 * The project id is carried in the redirect chain so step 2 knows what step 1
 * wrote.
 *
 *   step 1  title / category / description / files  -> projects, project_attachments
 *   step 2  budget and skills                       -> projects, project_skills
 *   step 3  screening questions                     -> screening_questions, projects
 *   step 4  milestones, then publish                -> milestones, projects, notifications
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: post-project-details.php');
    exit;
}

$conn    = db();
$owner   = current_client_id();
$step    = max(1, min(4, (int) $_POST['wizard_step']));
$project = (int) $_POST['project_id'];
$errors  = [];

// Which page each step hands over to.
$next = [
    1 => 'post-project.php',
    2 => 'post-project-screening.php',
    3 => 'post-project-review.php',
];

$budgetTypes = ['fixed', 'hourly'];
$durations   = ['less-than-1-month', '1-3-months', '3-6-months'];

/*
 * Every step after the first edits rows that hang off the project id, so make
 * sure the project belongs to this client before anything is touched. Without
 * this, one client could wipe another client's skills, questions or milestones
 * just by posting the step with somebody else's project id.
 */
if ($project > 0) {
    $mine = $conn->prepare('SELECT 1 FROM projects WHERE id = ? AND client_id = ?');
    $mine->execute([$project, $owner]);

    if (!$mine->fetchColumn()) {
        flash('That project is not yours.');
        header('Location: my-projects.php');
        exit;
    }
}

function redirect_to($page, $project) {
    header('Location: ' . $page . '?project_id=' . $project);
    exit;
}

// Sends the user back to a step with the problems spelled out.
function redirect_with_errors($page, $project, $errors) {
    foreach ($errors as $message) {
        flash($message);
    }

    redirect_to($page, $project);
}

// Saves the brief files dropped on step 1, up to the project's own limit.
function save_files($conn, $project, $owner, $limit) {
    if (!isset($_FILES['attachments'])) {
        return [];
    }

    $files  = $_FILES['attachments'];
    $errors = [];

    if (count($files['name']) > $limit) {
        return ['You can attach at most ' . $limit . ' files.'];
    }

    $folder = dirname(__DIR__) . '/uploads/project-' . $project;

    if (!is_dir($folder)) {
        mkdir($folder, 0775, true);
    }

    $insert = $conn->prepare(
        'INSERT INTO project_attachments
            (project_id, uploaded_by, filename, file_size_bytes, mime_type, storage_path, position)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    foreach (array_keys($files['name']) as $i) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed for ' . $files['name'][$i] . '.';
            continue;
        }

        $name  = basename($files['name'][$i]);
        $safe  = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);

        move_uploaded_file($files['tmp_name'][$i], $folder . '/' . $safe);

        $insert->execute([
            $project,
            $owner,
            $name,
            $files['size'][$i],
            $files['type'][$i],
            'uploads/project-' . $project . '/' . $safe,
            $i + 1,
        ]);
    }

    return $errors;
}

try {
    // ---- step 1: the basics ---------------------------------------------
    if ($step === 1) {
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category    = (int) $_POST['category_id'];

        if ($title === '') {
            $errors[] = 'Give the project a title.';
        }

        if ($description === '') {
            $errors[] = 'Describe the work so freelancers can respond.';
        }

        $check = $conn->prepare('SELECT id FROM skill_categories WHERE id = ?');
        $check->execute([$category]);

        if (!$check->fetch()) {
            $errors[] = 'Choose a category.';
        }

        if ($errors) {
            flash($errors[0]);
            header('Location: post-project-details.php');
            exit;
        }

        $conn->beginTransaction();

        $insert = $conn->prepare(
            "INSERT INTO projects
                (client_id, category_id, title, description,
                 budget_type, status, wizard_step, attachment_limit)
             VALUES (?, ?, ?, ?, 'fixed', 'draft', 1, 5)"
        );
        $insert->execute([$owner, $category, $title, $description]);

        $project = (int) $conn->lastInsertId();
        $errors  = save_files($conn, $project, $owner, 5);

        $conn->commit();

        redirect_to($next[1], $project);
    }

    // ---- step 2: skills and budget --------------------------------------
    if ($step === 2) {
        $budgetType = in_array($_POST['budget_type'], $budgetTypes)
            ? $_POST['budget_type']
            : '';

        $min      = parse_money($_POST['budget_min']);
        $max      = parse_money($_POST['budget_max']);
        $duration = in_array($_POST['estimated_duration'], $durations)
            ? $_POST['estimated_duration']
            : null;

        if ($budgetType === '') {
            $errors[] = 'Choose a budget type.';
        } elseif ($budgetType === 'fixed' && $max <= 0) {
            $errors[] = 'Enter the fixed project budget.';
        } elseif ($budgetType === 'hourly' && ($min <= 0 || $max <= 0)) {
            $errors[] = 'Enter an hourly rate range.';
        } elseif ($min > $max && $max > 0) {
            $errors[] = 'The maximum cannot be lower than the minimum.';
        }

        // The chip control hands us one comma separated string.
        $skills = array_filter(array_map('trim', explode(',', $_POST['skills'])));
        $skills = array_slice(array_values(array_unique($skills)), 0, 12);

        if ($errors) {
            redirect_with_errors($next[1], $project, $errors);
        }

        $conn->beginTransaction();

        $update = $conn->prepare(
            'UPDATE projects
                SET budget_type = ?, budget_min = ?, budget_max = ?,
                    estimated_duration = ?, wizard_step = 2
              WHERE id = ? AND client_id = ?'
        );
        $update->execute([
            $budgetType,
            $budgetType === 'fixed' ? $max : $min,
            $max,
            $duration,
            $project,
            $owner,
        ]);

        $conn->prepare('DELETE FROM project_skills WHERE project_id = ?')
            ->execute([$project]);

        $addSkill = $conn->prepare(
            'INSERT INTO project_skills (project_id, skill_name, position)
             VALUES (?, ?, ?)'
        );

        foreach ($skills as $i => $skill) {
            $addSkill->execute([$project, $skill, $i + 1]);
        }

        $conn->commit();

        redirect_to($next[2], $project);
    }

    // ---- step 3: what applicants must answer ----------------------------
    if ($step === 3) {
        $questions = array_filter(array_map('trim', $_POST['screening_questions']));
        $questions = array_slice(array_values($questions), 0, 10);

        if (!$questions) {
            redirect_with_errors($next[2], $project,
                ['Add at least one screening question.']);
        }

        // Unchecked boxes are not submitted at all, so isset is the test.
        $cover   = isset($_POST['cover_letter_required']) ? 1 : 0;
        $portfolio = isset($_POST['portfolio_links_required']) ? 1 : 0;
        $test    = isset($_POST['verified_skill_test_required']) ? 1 : 0;

        $conn->beginTransaction();

        $conn->prepare('DELETE FROM screening_questions WHERE project_id = ?')
            ->execute([$project]);

        $addQuestion = $conn->prepare(
            'INSERT INTO screening_questions (project_id, position, question_text)
             VALUES (?, ?, ?)'
        );

        foreach ($questions as $i => $question) {
            $addQuestion->execute([$project, $i + 1, $question]);
        }

        $conn->prepare(
            'UPDATE projects
                SET cover_letter_required = ?, portfolio_links_required = ?,
                    verified_skill_test_required = ?, wizard_step = 3
              WHERE id = ? AND client_id = ?'
        )->execute([$cover, $portfolio, $test, $project, $owner]);

        $conn->commit();

        redirect_to($next[3], $project);
    }

    // ---- step 4: milestones, then go live -------------------------------
    $rows = [];

    foreach ($_POST['milestones'] as $entry) {
        $title  = trim($entry['title']);
        $amount = parse_money($entry['amount']);

        if ($title === '' && $amount <= 0) {
            continue;
        }

        if ($title === '') {
            redirect_with_errors($next[3], $project,
                ['Every milestone needs a title.']);
        }

        if ($amount <= 0) {
            redirect_with_errors($next[3], $project,
                ['Milestone "' . $title . '" needs an amount above zero.']);
        }

        $rows[] = [$title, round($amount, 2)];
    }

    if (!$rows) {
        redirect_with_errors($next[3], $project,
            ['Add at least one milestone.']);
    }

    $st = $conn->prepare(
        'SELECT title, budget_type, budget_max
           FROM projects WHERE id = ? AND client_id = ?'
    );
    $st->execute([$project, $owner]);
    $row = $st->fetch();

    if (!$row) {
        redirect_with_errors($next[3], $project,
            ['That project no longer exists.']);
    }

    // The browser checks this too, but nothing sent from a browser counts.
    if ($row['budget_type'] === 'fixed') {
        $total = round(array_sum(array_column($rows, 1)), 2);
        $limit = round((float) $row['budget_max'], 2);

        if (abs($total - $limit) >= 0.005) {
            redirect_with_errors($next[3], $project, [
                'Milestones total ' . money($total) . ' but the budget is '
                . money($limit) . '.',
            ]);
        }
    }

    $conn->beginTransaction();

    $conn->prepare('DELETE FROM milestones WHERE project_id = ?')
        ->execute([$project]);

    $add = $conn->prepare(
        "INSERT INTO milestones (project_id, position, title, amount, status)
         VALUES (?, ?, ?, ?, 'posted')"
    );

    foreach ($rows as $i => $item) {
        $add->execute([$project, $i + 1, $item[0], $item[1]]);
    }

    $conn->prepare(
        "UPDATE projects
            SET status = 'open', wizard_step = 4, published_at = NOW()
          WHERE id = ? AND client_id = ?"
    )->execute([$project, $owner]);

    $conn->prepare(
        "INSERT INTO notifications (user_id, type, title, body, related_type, related_id)
         VALUES (?, 'project_published', 'Project published', ?, 'project', ?)"
    )->execute([$owner, $row['title'], $project]);

    $conn->commit();

    flash('Project published. It is now visible in Browse.', 'success');
    header('Location: my-projects.php');
    exit;
} catch (PDOException $e) {
    // Land them back on whichever step they were submitting.
    $back = [
        1 => 'post-project-details.php',
        2 => $next[1],
        3 => $next[2],
        4 => $next[3],
    ];

    redirect_with_errors($back[$step], $project,
        ['Could not save the project. Please try again.']);
}