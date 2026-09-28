<?php
/**
 * Endpoint: GET    /api/admin/skill-categories
 * Endpoint: POST   /api/admin/skill-categories          body: { name, icon?, subcategories? }
 * Endpoint: PUT    /api/admin/skill-categories/{id}     body: { name?, icon?, subcategories?, isActive? }
 * Endpoint: DELETE /api/admin/skill-categories/{id}
 *
 * Powers Admin/html/skill-category-manager.html.
 * Categories are the anchors for the skill test question bank, so a category
 * that still owns questions cannot be deleted.
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Turn the stored subcategory list into a clean array.
 */
function categorySubcategories(?string $raw): array {
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return array_values(array_filter(array_map('trim', $decoded), fn($s) => $s !== ''));
    }
    return array_values(array_filter(array_map('trim', explode(',', $raw)), fn($s) => $s !== ''));
}

/**
 * Build the URL-safe slug used by /api/jobs?category= and /api/skills/tests/{category}.
 */
function categorySlug(string $name): string {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
    return trim((string)$slug, '_');
}

/**
 * Read + validate an optional list of subcategory names from the request body.
 */
function subcategoriesFromBody(array $body): ?array {
    if (!array_key_exists('subcategories', $body)) {
        return null;
    }
    $raw = $body['subcategories'];
    if (is_string($raw)) {
        $raw = $raw === '' ? [] : preg_split('/\s*,\s*/', $raw);
    }
    if (!is_array($raw)) {
        sendResponse(['message' => "Field 'subcategories' must be a list of names"], 400);
    }
    $out = [];
    foreach ($raw as $item) {
        $item = trim((string)$item);
        if ($item === '') {
            continue;
        }
        if (mb_strlen($item) > 100) {
            sendResponse(['message' => 'Subcategory names must be 100 characters or fewer'], 400);
        }
        $out[] = $item;
    }
    if (count($out) > 25) {
        sendResponse(['message' => 'A category may not have more than 25 subcategories'], 400);
    }
    return $out;
}

try {
    requireRole($pdo, ['ADMIN']);
    $method = $_SERVER['REQUEST_METHOD'];

    // ------------------------------------------------------------------ list
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, name, slug, icon, subcategories, is_active, question_count, difficulty
                             FROM skill_categories
                             ORDER BY is_active DESC, name ASC");
        $items = [];
        foreach ($stmt->fetchAll() as $r) {
            $items[] = [
                'id'            => $r['id'],
                'name'          => $r['name'],
                'slug'          => $r['slug'],
                'icon'          => $r['icon'],
                'subcategories' => categorySubcategories($r['subcategories']),
                'isActive'      => (bool)$r['is_active'],
                'questionCount' => (int)$r['question_count'],
                'difficulty'    => $r['difficulty'],
            ];
        }
        sendResponse(['categories' => $items, 'total' => count($items)], 200);
    }

    // ---------------------------------------------------------------- create
    if ($method === 'POST') {
        $body = getRequestBody();
        $name = requireField($body, 'name', 100, 'Category name');
        $icon = trim((string)($body['icon'] ?? 'code')) ?: 'code';
        if (mb_strlen($icon) > 20) {
            sendResponse(['message' => 'Field icon must be 20 characters or fewer'], 400);
        }
        $subs = subcategoriesFromBody($body) ?? [];

        $dupe = $pdo->prepare("SELECT id FROM skill_categories WHERE name = ? LIMIT 1");
        $dupe->execute([$name]);
        if ($dupe->fetch()) {
            sendResponse(['message' => 'A category with that name already exists'], 409);
        }

        $slug = categorySlug($name);
        $slugDupe = $pdo->prepare("SELECT id FROM skill_categories WHERE slug = ? LIMIT 1");
        $slugDupe->execute([$slug]);
        if ($slugDupe->fetch()) {
            sendResponse(['message' => 'A category with that slug already exists'], 409);
        }

        $id = newId('cat');
        $ins = $pdo->prepare("INSERT INTO skill_categories (id, name, slug, icon, subcategories, is_active, question_count)
                              VALUES (?, ?, ?, ?, ?, 1, 0)");
        $ins->execute([$id, $name, $slug, $icon, json_encode($subs, JSON_UNESCAPED_UNICODE)]);

        sendResponse([
            'message'       => 'Category created',
            'id'            => $id,
            'name'          => $name,
            'slug'          => $slug,
            'icon'          => $icon,
            'subcategories' => $subs,
            'isActive'      => true,
        ], 201);
    }

    // ---------------------------------------------------------------- update
    if ($method === 'PUT') {
        $categoryId = routeParam('id');
        if ($categoryId === '') {
            sendResponse(['message' => 'Category ID is required'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM skill_categories WHERE id = ? LIMIT 1");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        if (!$category) {
            sendResponse(['message' => 'Category not found'], 404);
        }

        $body  = getRequestBody();
        $name  = $category['name'];
        $icon  = $category['icon'];
        $slug  = $category['slug'];
        $isActive = (bool)$category['is_active'];
        $subs  = categorySubcategories($category['subcategories']);

        if (array_key_exists('name', $body)) {
            $name = requireField($body, 'name', 100, 'Category name');
            $slug = categorySlug($name);
            $clash = $pdo->prepare("SELECT id FROM skill_categories WHERE slug = ? AND id <> ? LIMIT 1");
            $clash->execute([$slug, $categoryId]);
            if ($clash->fetch()) {
                sendResponse(['message' => 'A category with that slug already exists'], 409);
            }
        }
        if (array_key_exists('icon', $body)) {
            $icon = trim((string)$body['icon']) ?: 'code';
            if (mb_strlen($icon) > 20) {
                sendResponse(['message' => 'Field icon must be 20 characters or fewer'], 400);
            }
        }
        if (array_key_exists('isActive', $body)) {
            $raw = $body['isActive'];
            if (!is_bool($raw) && !in_array($raw, [0, 1, '0', '1', 'true', 'false'], true)) {
                sendResponse(['message' => "Field 'isActive' must be a boolean"], 400);
            }
            $isActive = in_array($raw, [true, 1, '1', 'true'], true);
        }
        $newSubs = subcategoriesFromBody($body);
        if ($newSubs !== null) {
            $subs = $newSubs;
        }

        $upd = $pdo->prepare("UPDATE skill_categories
                              SET name = ?, slug = ?, icon = ?, subcategories = ?, is_active = ?
                              WHERE id = ?");
        $upd->execute([$name, $slug, $icon, json_encode($subs, JSON_UNESCAPED_UNICODE), $isActive ? 1 : 0, $categoryId]);

        sendResponse([
            'message'       => 'Category updated',
            'id'            => $categoryId,
            'name'          => $name,
            'slug'          => $slug,
            'icon'          => $icon,
            'subcategories' => $subs,
            'isActive'      => $isActive,
        ], 200);
    }

    // ---------------------------------------------------------------- delete
    if ($method === 'DELETE') {
        $categoryId = routeParam('id');
        if ($categoryId === '') {
            sendResponse(['message' => 'Category ID is required'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM skill_categories WHERE id = ? LIMIT 1");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        if (!$category) {
            sendResponse(['message' => 'Category not found'], 404);
        }

        $inUse = $pdo->prepare("SELECT COUNT(*) FROM skill_questions WHERE category = ? OR category = ?");
        $inUse->execute([$category['id'], (string)$category['slug']]);
        $questionCount = (int)$inUse->fetchColumn();
        if ($questionCount > 0) {
            sendResponse([
                'message' => 'This category still has ' . $questionCount . ' question(s). ' .
                             'Deactivate it instead of deleting it.',
            ], 400);
        }

        $del = $pdo->prepare("DELETE FROM skill_categories WHERE id = ?");
        $del->execute([$categoryId]);

        sendResponse(['message' => 'Category deleted', 'id' => $categoryId], 200);
    }

    sendResponse(['message' => 'Method Not Allowed'], 405);

} catch (PDOException $e) {
    sendResponse(['message' => 'Category error: ' . $e->getMessage()], 500);
}
