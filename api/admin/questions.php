<?php
/**
 * Endpoint: GET    /api/admin/skills/{category}/questions
 * Endpoint: POST   /api/admin/skills/{category}/questions
 * Endpoint: PUT    /api/admin/skills/{category}/questions/{id}
 * Endpoint: DELETE /api/admin/skills/{category}/questions/{id}
 *
 * Powers Admin/html/question-bank-manager.html.
 *
 * skill_questions.category stores the category key — the skill_categories.id
 * value such as "web" — so questions stay attached even if a category is
 * renamed. The key is accepted as an id, a slug or a name for convenience.
 */

require_once __DIR__ . '/../config/db.php';

const QUESTION_TYPES     = ['Multiple Choice', 'Coding'];
const QUESTION_DIFFICULTY = ['Easy', 'Medium', 'Hard'];

/**
 * Resolve the {category} path segment to a skill_categories row.
 * Sends 404 and exits when nothing matches.
 */
function resolveQuestionCategory(PDO $pdo, string $key): array {
    $stmt = $pdo->prepare("SELECT id, name, slug, is_active
                           FROM skill_categories
                           WHERE id = ? OR slug = ? OR name = ?
                           LIMIT 1");
    $stmt->execute([$key, $key, $key]);
    $category = $stmt->fetch();
    if (!$category) {
        sendResponse(['message' => 'Skill category not found: ' . $key], 404);
    }
    return $category;
}

/**
 * Validate the shared question fields. Returns the values to persist.
 *
 * @param bool $partial True on PUT, where absent fields keep their old value.
 */
function validateQuestionFields(PDO $pdo, array $body, array $current = null, bool $partial = false): array {
    $out = [
        'question'      => $current['question'] ?? '',
        'qtype'         => $current['qtype'] ?? 'Multiple Choice',
        'difficulty'    => $current['difficulty'] ?? 'Medium',
        'options'       => [],
        'correct_index' => (int)($current['correct_index'] ?? 0),
    ];

    if ($current !== null) {
        $out['options'] = json_decode((string)$current['options'], true);
        if (!is_array($out['options'])) {
            $out['options'] = [];
        }
    }

    if (array_key_exists('question', $body) || !$partial) {
        $out['question'] = requireField($body, 'question', 1000, 'Question text');
    }

    if (array_key_exists('qtype', $body)) {
        $out['qtype'] = requireOneOf((string)$body['qtype'], QUESTION_TYPES, 'question type', 'raw');
    } elseif ($partial) {
        // keep current
    } else {
        $out['qtype'] = 'Multiple Choice';
    }

    if (array_key_exists('difficulty', $body)) {
        $out['difficulty'] = requireOneOf((string)$body['difficulty'], QUESTION_DIFFICULTY, 'difficulty', 'raw');
    } elseif (!$partial) {
        $out['difficulty'] = 'Medium';
    }

    if (array_key_exists('options', $body)) {
        $raw = $body['options'];
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : preg_split('/\s*,\s*/', $raw);
        }
        if (!is_array($raw)) {
            sendResponse(['message' => "Field 'options' must be a list of answer choices"], 400);
        }
        $opts = [];
        foreach ($raw as $opt) {
            $opt = trim((string)$opt);
            if ($opt === '') {
                continue;
            }
            if (mb_strlen($opt) > 500) {
                sendResponse(['message' => 'Each answer choice must be 500 characters or fewer'], 400);
            }
            $opts[] = $opt;
        }
        $out['options'] = $opts;
    }

    // Multiple choice needs a real option list and a valid correctIndex.
    if ($out['qtype'] === 'Multiple Choice') {
        if (count($out['options']) < 2) {
            sendResponse(['message' => 'A multiple choice question needs at least 2 answer choices'], 400);
        }
        if (array_key_exists('correctIndex', $body)) {
            $idx = $body['correctIndex'];
            if (!is_numeric($idx) || (float)$idx != floor((float)$idx)) {
                sendResponse(['message' => "Field 'correctIndex' must be a whole number"], 400);
            }
            $out['correct_index'] = (int)$idx;
        } elseif (!$partial) {
            sendResponse(['message' => "Field 'correctIndex' is required for multiple choice"], 400);
        }
        if ($out['correct_index'] < 0 || $out['correct_index'] >= count($out['options'])) {
            sendResponse([
                'message' => "Field 'correctIndex' must be between 0 and " . (count($out['options']) - 1),
            ], 400);
        }
    } else {
        // Coding questions are graded manually, so there is no correct option.
        $out['correct_index'] = 0;
    }

    return $out;
}

/**
 * Serialise a question row for the admin UI.
 */
function formatQuestion(array $r, string $categoryKey): array {
    $options = json_decode((string)$r['options'], true);
    if (!is_array($options)) {
        $options = [];
    }
    return [
        'id'           => (int)$r['id'],
        'category'     => $r['category'],
        'categoryKey'  => $categoryKey,
        'question'     => $r['question'],
        'qtype'        => $r['qtype'],
        'difficulty'   => $r['difficulty'],
        'options'      => array_values($options),
        'correctIndex' => (int)$r['correct_index'],
    ];
}

try {
    requireRole($pdo, ['ADMIN']);
    $method = $_SERVER['REQUEST_METHOD'];

    $categoryKey = routeParam('category');
    if ($categoryKey === '') {
        sendResponse(['message' => 'Skill category is required'], 400);
    }
    $category = resolveQuestionCategory($pdo, $categoryKey);
    $categoryKey = (string)$category['id'];

    // ------------------------------------------------------------------ list
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT id, category, question, options, correct_index, qtype, difficulty
                               FROM skill_questions
                               WHERE category = ?
                               ORDER BY id ASC");
        $stmt->execute([$categoryKey]);

        $items = [];
        foreach ($stmt->fetchAll() as $r) {
            $items[] = formatQuestion($r, $categoryKey);
        }

        sendResponse([
            'questions' => $items,
            'category'  => [
                'id'       => $category['id'],
                'name'     => $category['name'],
                'slug'     => $category['slug'],
                'isActive' => (bool)$category['is_active'],
            ],
        ], 200);
    }

    // ---------------------------------------------------------------- create
    if ($method === 'POST') {
        $body   = getRequestBody();
        $fields = validateQuestionFields($pdo, $body);

        $ins = $pdo->prepare("INSERT INTO skill_questions (category, question, options, correct_index, qtype, difficulty)
                              VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([
            $categoryKey,
            $fields['question'],
            json_encode($fields['options'], JSON_UNESCAPED_UNICODE),
            $fields['correct_index'],
            $fields['qtype'],
            $fields['difficulty'],
        ]);
        $id = (int)$pdo->lastInsertId();

        $count = $pdo->prepare("UPDATE skill_categories SET question_count = (SELECT COUNT(*) FROM skill_questions WHERE category = ?) WHERE id = ?");
        $count->execute([$categoryKey, $categoryKey]);

        sendResponse([
            'message'  => 'Question added',
            'id'       => $id,
            'question' => $fields['question'],
            'qtype'    => $fields['qtype'],
            'difficulty' => $fields['difficulty'],
        ], 201);
    }

    // ------------------------------------------------- update / delete by id
    $questionId = routeParam('questionId');
    if ($questionId === '' || !ctype_digit($questionId)) {
        sendResponse(['message' => 'Question ID is required'], 400);
    }
    $questionId = (int)$questionId;

    $stmt = $pdo->prepare("SELECT * FROM skill_questions WHERE id = ? AND category = ? LIMIT 1");
    $stmt->execute([$questionId, $categoryKey]);
    $question = $stmt->fetch();
    if (!$question) {
        sendResponse(['message' => 'Question not found in this category'], 404);
    }

    if ($method === 'PUT') {
        $body   = getRequestBody();
        $fields = validateQuestionFields($pdo, $body, $question, true);

        $upd = $pdo->prepare("UPDATE skill_questions
                              SET question = ?, options = ?, correct_index = ?, qtype = ?, difficulty = ?
                              WHERE id = ? AND category = ?");
        $upd->execute([
            $fields['question'],
            json_encode($fields['options'], JSON_UNESCAPED_UNICODE),
            $fields['correct_index'],
            $fields['qtype'],
            $fields['difficulty'],
            $questionId,
            $categoryKey,
        ]);

        sendResponse([
            'message' => 'Question updated',
            'id'      => $questionId,
            'question' => $fields['question'],
            'qtype'   => $fields['qtype'],
            'difficulty' => $fields['difficulty'],
            'options' => $fields['options'],
            'correctIndex' => $fields['correct_index'],
        ], 200);
    }

    if ($method === 'DELETE') {
        $del = $pdo->prepare("DELETE FROM skill_questions WHERE id = ? AND category = ?");
        $del->execute([$questionId, $categoryKey]);

        $count = $pdo->prepare("UPDATE skill_categories SET question_count = (SELECT COUNT(*) FROM skill_questions WHERE category = ?) WHERE id = ?");
        $count->execute([$categoryKey, $categoryKey]);

        sendResponse(['message' => 'Question deleted', 'id' => $questionId], 200);
    }

    sendResponse(['message' => 'Method Not Allowed'], 405);

} catch (PDOException $e) {
    sendResponse(['message' => 'Question bank error: ' . $e->getMessage()], 500);
}
