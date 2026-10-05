# SkillMatch — Client Portal Database

Schema and seed data for the client portal, built from the 21 pages in
`client/*.html`.

## Run

```
schema.sql   23 tables, 3 triggers
seed.sql     dummy data matching the mockups
```

phpMyAdmin: Import both, schema first (it drops and recreates `skillmatch`).

```
mysql -u root -p < db/schema.sql
mysql -u root -p < db/seed.sql
```

Verified on MariaDB 10.4 (what XAMPP ships). Collation is `utf8mb4_unicode_ci`
so it also runs on MySQL 8.

Seed passwords are placeholders. Before any real login:

```php
password_hash('your-password', PASSWORD_DEFAULT)
```

## Tables

**Identity** — `users`, `client_profiles`, `skill_categories`, `platform_settings`

**Posting to hire** — `projects`, `project_skills`, `screening_questions`,
`milestones`, `proposals`, `proposal_screening_answers`

**Money and conflict** — `project_attachments`, `milestone_submissions`,
`milestone_feedback`, `escrow_payments`, `disputes`, `dispute_events`, `reviews`

**Comms** — `conversations`, `messages`, `notifications`

**Freelancer stubs** — `freelancers`, `freelancer_skill_scores`,
`freelancer_portfolio_items`

The last group is read-only from the client side. `browse-freelancers.php` and
`freelancer-profile.php` need those rows; the freelancer owner writes them.

## Page to table

| Page | Tables |
|---|---|
| `client-dashboard.php` | `projects`, `proposals`, `escrow_payments` |
| `client-profile.php` | `client_profiles` + same aggregates |
| `my-projects.php` | `projects`, `milestones`, `proposals`, `client_profiles` — **reads from the database** |
| `post-project-details.php` | `projects`, `skill_categories`, `project_attachments` |
| `post-project.php` | `projects`, `project_skills` |
| `post-project-screening.php` | `screening_questions`, `projects` |
| `post-project-review.php` | `milestones`, `projects` |
| `review-proposal.php` | `proposals`, `freelancer_skill_scores` |
| `browse-freelancers.php` | `freelancers`, `skill_categories` |
| `freelancer-profile.php` | `freelancers`, `freelancer_skill_scores`, `freelancer_portfolio_items`, `reviews` |
| `client-chat.php` | `conversations`, `messages` |
| `work-approval.php` | `milestone_submissions`, `milestone_feedback`, `milestones`, `escrow_payments` |
| `payment-released.php` | `escrow_payments`, `milestones` |
| `billing-payments.php` | `escrow_payments`, `milestones`, `projects` |
| `rate-freelancer.php` | `reviews` |
| `raise-dispute.php` | `disputes` |
| `dispute-status.php` | `disputes`, `dispute_events` |
| `create-client-account.php` | `client_profiles`, `users` |
| header dropdown (all pages) | `notifications` |

## Running the PHP

Client pages are now `.php`, so they need a server. The project sits outside
`htdocs`, so use PHP's built-in server:

```
php -S 127.0.0.1:8000 -t C:\Users\Sami\Desktop\web
```

Then open `http://127.0.0.1:8000/client/`. Requires PHP 8+ with `pdo_mysql`
(both present in XAMPP). Connection details are in `client/includes/db.php` —
XAMPP defaults to `root` with no password.

**There is no login yet.** `client/includes/auth.php` hardcodes
`CURRENT_USER_ID = 1` (Abida), so every page acts as that client. Add session
auth in that one file and the rest follows.

Uploaded files land in `uploads/` at the project root, gitignored.

## Posting wizard

All four steps POST to `client/save-project.php`, discriminated by a
`wizard_step` field. `projects.wizard_step` (1–4, nullable) records how far a
draft has got; `status = 'draft'` marks it unpublished. Drafts show a **Resume**
button on the projects list.

| Step | Page | Fields |
|---|---|---|
| 1 | `post-project-details.php` | `title`, `category_id`, `description`, `attachments[]` (multipart, max 5) |
| 2 | `post-project.php` | `budget_type` (fixed\|hourly), `budget_min`, `budget_max`, `estimated_duration`, `skills` |
| 3 | `post-project-screening.php` | `screening_questions[]`, `cover_letter_required`, `portfolio_links_required`, `verified_skill_test_required` |
| 4 | `post-project-review.php` | `milestones[i][title]`, `milestones[i][amount]` |

Handler notes:

- Checkboxes send nothing when unchecked — read them with `isset()`.
- `skills` arrives comma-separated from the chip control; it is split before
  writing `project_skills`.
- Step 4 re-checks the milestone total **server-side**. The browser does the
  same thing, but nothing sent from a browser can be trusted.
- Errors redirect back with a flash message. `render_flashes()` **must** be
  called before any HTML output, or `session_start()` fails.

## Other handlers

- `send-message.php` — inserts the message, refreshes
  `conversations.last_message_at`/preview, notifies the other participant. The
  composer posts a hidden `conversation_id`; only thread 1 is reachable until
  the chat list is DB-driven.
- `create-account.php` — validates unique email and an 8-character minimum,
  bcrypts the password, writes `users` + `client_profiles`, accepts a JPG/PNG/
  WEBP avatar under 2 MB.

## What the database enforces

Three triggers and two CHECK constraints carry the business rules:

- **Escrow fee is 3%** — computed on insert from
  `platform_settings.escrow_fee_rate`, so the caller cannot pass a wrong total.
  `total_charged = amount + escrow_fee`.
- **Filing a dispute freezes the money** — sets `response_due_at` to
  `filed_at + 3 days`, flips the milestone to `disputed`, moves the escrow row
  to `frozen`.
- **Released escrow needs a release date**, and unread notifications cannot
  carry a read timestamp.
- **Ratings are 1–5**, and a `partial_refund` outcome must have an amount.

Budget totals are not enforced — validate with a query when you publish a
project:

```sql
SELECT p.budget_max, SUM(m.amount) FROM projects p
  JOIN milestones m ON m.project_id = p.id
 WHERE p.id = ? AND p.budget_type = 'fixed'
 GROUP BY p.budget_max;
```

## Conventions

- Money is `DECIMAL(12,2)`, never `FLOAT`.
- Display IDs are `VARCHAR UNIQUE`, generated in PHP: receipts
  `SM-YYYY-NNNN`, disputes `DIS-YYYY-NNNN`, certificates `SM-CERT-NNNN`.
- `ON DELETE CASCADE` from `projects` and `milestones`, but `RESTRICT` on
  `users` — you cannot delete an account with money or disputes attached.
- Ordering is explicit: `milestones.position`, `screening_questions.position`,
  `project_skills.position`, `dispute_events.created_at`.

## Mockup conflicts resolved

The HTML contradicted itself in five places. Seed takes one position:

1. **Proposal counts** — `34` in `my-projects.html`, `12` in
   `review-proposal.php`, `5` on the dashboard. All for the same project. Now
   a live `COUNT(*)`.
2. **`SM-2026-0847`** was a receipt, a case file, and a chat thread. Scoped to
   receipts only.
3. **"Proposal Approved"** notification was written in freelancer voice inside
   the client panel. Rewritten as `proposal_accepted` from the client's side.
4. **Badge classes are not status keys** — `badge-await` rendered both "In
   Progress" and "Awaiting Approval". Status text and CSS class are now
   separate; PHP picks the class from the enum.
5. **Project titles** drifted ("Redesign and Migration" vs "& Migration").
   Seeded with the `&`.

All data is dummy. Freelancer names came from the mockups; ratings, job counts
and GitHub handles are filler.

## Not modelled

- Skill tests and scoring — the freelancer owner's side. The client pages only
  read the results, via `freelancer_skill_scores`.
- Dispute escalation mechanics — `status = 'escalated'` exists, no mediator
  table behind it.
- Admin reports.

The wizard's four steps are wired and submit correctly, but nothing consumes
them yet — `client/post-project.php` still has to be written. Until then the
wizard survives page-to-page only within a single browser session.