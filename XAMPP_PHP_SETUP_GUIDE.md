# SkillMatch — XAMPP & PHP Backend Setup Guide

This guide walks you through migrating the SkillMatch frontend into your local XAMPP environment and building/extending the PHP + MySQL REST backend.

---

## 1. Moving the Project to XAMPP

1. Start **XAMPP Control Panel** and ensure both **Apache** and **MySQL** modules are started (showing green in the control panel).
2. Copy or move this entire project folder into your XAMPP web root:
   ```
   C:\xampp\htdocs\UIU_Web_4165
   ```
3. Test that the project is accessible through Apache by navigating to:
   ```
   http://localhost/UIU_Web_4165/
   ```

---

## 2. Importing the MySQL Database in phpMyAdmin

A pre-configured MySQL dump file [`database.sql`](file:///c:/Users/USER/Desktop/Web%20262/UIU_Web_4165/database.sql) has been created in the root directory.

1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Import** tab in the top navigation bar.
3. Click **Choose File** and select `C:\xampp\htdocs\UIU_Web_4165\database.sql`.
4. Click **Import** at the bottom of the page.
5. Verify that the database `skillmatch_db` is created with all 10 tables:
   - `users` (pre-seeded with Freelancers, Clients, and Admin)
   - `jobs` (pre-seeded with active marketplace projects)
   - `proposals`
   - `chat_threads` & `chat_messages`
   - `portfolio_items`
   - `skill_categories` & `skill_questions`
   - `test_results`
   - `deliverables`

---

## 3. How the Frontend Connects Automatically

In [`assets/js/api.js`](file:///c:/Users/USER/Desktop/Web%20262/UIU_Web_4165/assets/js/api.js), the API base URL now dynamically detects XAMPP:

```javascript
// Automatically resolves to: http://localhost/UIU_Web_4165/api
function resolveApiBaseUrl() {
  if (window.SKILLMATCH_API_BASE) return window.SKILLMATCH_API_BASE;
  const loc = window.location;
  if (loc && loc.origin && loc.origin.startsWith('http://localhost') && !loc.port.includes('8080')) {
    const match = loc.pathname.match(/^(\/[^\/]+)/);
    const projectRoot = match ? match[1] : '';
    return `${loc.origin}${projectRoot}/api`;
  }
  return 'http://localhost:8080/api';
}
```

- When running in XAMPP (`http://localhost/UIU_Web_4165/...`), `SkillMatch.api` automatically sends requests to `http://localhost/UIU_Web_4165/api/...`.
- If an endpoint is not yet implemented in PHP or MySQL is offline, the frontend's built-in **mock store fallback** will seamlessly fulfill the request with zero UI crashes.

---

## 4. Starter PHP Backend Structure (`api/`)

The starter backend has been scaffolded in [`api/`](file:///c:/Users/USER/Desktop/Web%20262/UIU_Web_4165/api/):

```
api/
├── .htaccess                 # Clean URL rewrite rules for Apache
├── config/
│   └── db.php                # PDO MySQL connection, CORS headers & helper functions
├── auth/
│   └── login.php             # POST /api/auth/login (authentication)
├── jobs/
│   └── index.php             # GET /api/jobs (filters) & POST /api/jobs (create)
└── freelancers/
    └── index.php             # GET /api/freelancers (verified talent list)
```

### Database Configuration (`api/config/db.php`)
Configured out-of-the-box for XAMPP:
- **Host**: `127.0.0.1`
- **Database**: `skillmatch_db`
- **User**: `root`
- **Password**: `""` (empty string)

---

## 5. Pattern for Building Remaining PHP Endpoints

Every endpoint can be built following this simple, standardized pattern:

```php
<?php
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Read operation
    $stmt = $pdo->query("SELECT * FROM your_table");
    sendResponse($stmt->fetchAll());
} elseif ($method === 'POST') {
    // Write operation
    $body = getRequestBody();
    $stmt = $pdo->prepare("INSERT INTO your_table (col1, col2) VALUES (?, ?)");
    $stmt->execute([$body['val1'], $body['val2']]);
    sendResponse(['id' => $pdo->lastInsertId(), 'message' => 'Created successfully'], 201);
} else {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}
```

### Complete Endpoint Reference Checklist

Refer to [`INSTRUCTIONS.md`](file:///c:/Users/USER/Desktop/Web%20262/UIU_Web_4165/INSTRUCTIONS.md) for the exact JSON payloads and response structures expected by the frontend.

| Method | Endpoint | Description | Recommended PHP File |
|---|---|---|---|
| `POST` | `/api/auth/login` | User/Admin authentication | `api/auth/login.php` (Created) |
| `GET` | `/api/jobs` | Marketplace listings (with filter params) | `api/jobs/index.php` (Created) |
| `POST` | `/api/jobs` | Post new project | `api/jobs/index.php` (Created) |
| `GET` | `/api/freelancers` | List verified freelancers | `api/freelancers/index.php` (Created) |
| `GET` | `/api/freelancers/{id}` | Public freelancer profile | `api/freelancers/profile.php` |
| `GET` | `/api/freelancers/me` | Logged-in freelancer profile | `api/freelancers/me.php` |
| `PUT` | `/api/freelancers/me` | Update freelancer profile | `api/freelancers/me.php` |
| `GET` | `/api/freelancers/me/proposals` | Freelancer proposal list | `api/proposals/index.php` |
| `DELETE`| `/api/proposals/{id}` | Withdraw proposal | `api/proposals/withdraw.php` |
| `GET` | `/api/chat/threads` | Client/Freelancer message threads | `api/chat/threads.php` |
| `GET` | `/api/chat/threads/{id}/messages` | Message thread history | `api/chat/messages.php` |
| `POST` | `/api/chat/threads/{id}/messages` | Send new chat message | `api/chat/messages.php` |
| `GET` | `/api/skills/categories` | Skill verification test categories | `api/skills/categories.php` |
| `GET` | `/api/skills/tests/{cat}` | Exam question bank | `api/skills/tests.php` |
| `POST` | `/api/skills/tests/{cat}/submit`| Submit exam and calculate grade | `api/skills/submit.php` |
| `GET` | `/api/admin/metrics` | Admin KPI analytics | `api/admin/metrics.php` |
| `GET` | `/api/admin/users` | Admin user list & search | `api/admin/users.php` |
| `POST` | `/api/admin/users/{id}/status` | Toggle user status (Active/Suspended) | `api/admin/status.php` |
