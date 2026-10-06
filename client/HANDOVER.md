# Client portal — handover

Everything here is the client side of SkillMatch: the database, the four page
files that write to it, and the two pages that read from it.

## Setup from a fresh clone

1. Start MySQL. On this machine XAMPP ships **MariaDB 10.4** and has no
   `my.ini`, so start it directly if the XAMPP panel will not do it:

   ```
   mysqld --datadir=C:\xampp\mysql\data --port=3306 --bind-address=127.0.0.1
   ```

2. Import the database, in this order:

   ```
   mysql -u root < db/schema.sql
   mysql -u root < db/seed.sql
   ```

   `schema.sql` drops and recreates the `skillmatch` database. In phpMyAdmin
   that is Import for both files, schema first.

3. Serve the project. It is not inside `htdocs`, so use PHP's own server from
   the project root:

   ```
   php -S 127.0.0.1:8000 -t C:\path\to\web
   ```

   Then open `http://127.0.0.1:8000/client/`.

4. `uploads/` is created on demand by the first file upload. It is gitignored.

Database credentials are the XAMPP defaults (`root`, no password) and live in
`client/includes/db.php`.

## What is live versus still static

**Reads from the database** — only two pages:

- `my-projects.php` — project list, filter tabs, timelines
- `partials/header.php` — notifications, profile menu, nav active state

Everything else renders hardcoded mockup content. It looks like real data but
is not, and editing those files will not change the database.

**Writes to the database** — four handlers:

| Handler | Does |
|---|---|
| `save-project.php` | the whole posting wizard, all 4 steps |
| `send-message.php` | chat message, inbox preview, notification |
| `create-account.php` | registers a client account |
| `mark-notifications-read.php` | clears the notification badge |

## There is no login yet

`client/includes/auth.php` has:

```php
define('CURRENT_USER_ID', 1);
```

Every page acts as the seeded client, Abida Hasan (id 1). `create-account.php`
creates real accounts, but they cannot sign in. To add auth, change that one
constant to read `$_SESSION` instead, populate the session in a login handler,
and the rest of the portal follows unchanged.

## Layout

```
client/
  includes/
    db.php           PDO connection, one shared instance
    auth.php         current user, money formatting, flash messages
  partials/
    header.php       nav + notifications + profile menu (included by 16 pages)
  save-project.php   posting wizard
  send-message.php   chat
  create-account.php registration
  mark-notifications-read.php
  <page>.php         17 display pages, mostly static
db/
  schema.sql         23 tables, 3 triggers
  seed.sql           sample data
  README.md          column-to-page map, business rules, conventions
```

## Rules the database enforces

Three triggers carry the business rules, so they hold even if the PHP is wrong:

- **Escrow fee is 3%** — computed on insert from
  `platform_settings.escrow_fee_rate`. `total_charged = amount + escrow_fee`.
- **Filing a dispute freezes the money** — sets `response_due_at` to
  `filed_at + 3 days`, flips the milestone to `disputed`, moves the escrow row
  to `frozen`.
- **Released escrow needs a release date.**

Two CHECK constraints: ratings must be 1–5, and a `partial_refund` outcome must
carry an amount.

Milestone totals are **not** enforced by the database. The wizard checks in
both the browser and `save-project.php`, and publishing is blocked on a
mismatch, but if you add another path that writes milestones, re-check the sum
yourself.

## Conventions worth keeping

- Money is `DECIMAL(12,2)`. Never `FLOAT`.
- Display IDs are `VARCHAR` + `UNIQUE`, generated in PHP: receipts
  `SM-YYYY-NNNN`, disputes `DIS-YYYY-NNNN`, certificates `SM-CERT-NNNN`.
- Order is explicit everywhere it matters: `milestones.position`,
  `screening_questions.position`, `project_skills.position`.
- Every write goes through prepared statements.
- Every output value goes through `e()` in `auth.php`.
- Ownership is checked with `WHERE id = ? AND client_id = ?`, or up front in
  `save-project.php`. Do not add a write that skips this.

## Two things that will trip you up

**Sessions must start before any output.** `take_flashes()` calls
`session_start()`, so it has to run before the first byte of HTML. Every page
that shows flash messages does it on line 1, before `<!DOCTYPE html>`. If you
add a page that needs flashes, put the `require` at the very top.

**The current user is hardcoded, so there is no real access control.** The
ownership checks are written and tested, but they all resolve to user 1. They
only become meaningful once real auth is in.

## Known gaps

- No login or sessions.
- No skill tests or scoring — `freelancer_skill_scores` holds results, but
  nothing produces them.
- Only chat thread 1 is reachable; the thread list is still static markup.
- Dispute escalation is a status value with nothing behind it.
- `raise-dispute.html`, `rate-freelancer.html` and `work-approval.html` have
  no handler yet, so their buttons do nothing.
- Admin and freelancer portals are untouched.

## All seed data is fictional

The names came from the mockups. Ratings, job counts, GitHub handles and the
`$12,450` dashboard total are invented. Replace as real data arrives.