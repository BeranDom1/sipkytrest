# Copilot Instructions for Šipky Třešť Liga App

## Project Overview
Šipky Třešť is a dart league management system with responsive UI (Black-Yellow theme), PWA support, and role-based access control. Two variants: `liga-app` (main) and `liga-app-clean` (reference implementation).

## Core Architecture

**Database Layer:**
- MySQLi connection in [db.php](db.php); tables: `rocniky` (seasons), `ligy` (leagues), `uzivatele` (users), `turnaje` (tournaments)
- Liga reference: `cislo` (0–5) maps to `id` via `_safe_liga_id()` helper (see [common.php](liga-app/common.php))

**Request Flow:**
1. [header.php](liga-app/header.php) initializes session, validates user, loads season/league
2. Page-specific logic queries DB, builds data arrays
3. [footer.php](liga-app/footer.php) closes output
4. Static assets in [assets/](liga-app/assets/) (theme.final.css, theme.js)

**Security:**
- CSRF tokens required for all POST requests; validate via [csrf.php](liga-app/security/csrf.php) + [guard-post.php](liga-app/security/guard-post.php)
- Auth via [login_action.php](liga-app/login_action.php); roles: `user`, `stat_editor`, `admin`
- Admin pages protected by [admin/_auth.php](liga-app/admin/_auth.php)
- Always use `htmlspecialchars()` for output; prepared statements for SQL

## Key Conventions

**URLs & Sessions:**
- `BASE_URL` set in header (hardcoded `/liga-app` or auto-detected in clean variant)
- Active season: `$_SESSION['rocnik_id']` (auto-initializes to latest)
- Active league: pass `?cislo=N` (0–5) or `?liga=ID` (handles both)

**Query Parameters:**
- Liga selection: use `?cislo=0|1|2|3|4|5` for human-readable URLs
- Fallback to `?liga=<id>` for direct ID; `_safe_liga_id()` normalizes both

**Forms & CSRF:**
```php
// In header: csrf protected via $_SESSION['csrf']
// Output token: <?= csrf_input() ?> or htmlspecialchars($_SESSION['csrf'] ?? '')
// Validate POST: csrf_validate_or_die() or csrf_check($_POST['csrf'] ?? '')
```

**Output Escaping:**
```php
// Always: htmlspecialchars($var), htmlspecialchars($var, ENT_QUOTES, 'UTF-8') for attributes
// Helper function iv($val) likely exists; verify in context before use
```

## Development Notes

- **Two Codebases:** `liga-app` is production; `liga-app-clean` shows best practices (auto BASE_URL detection, cleaner DB auth)
- **liga-app vs clean:** Merge improvements from clean into main when deploying
- **Database:** Hosts live data on `md418.wedos.net` (see [db.php](liga-app/db.php); credentials hardcoded)
- **UI Components:** Sidebar responsive design via [theme.final.css](liga-app/assets/theme.final.css); mobile hamburger button
- **Fullcalendar:** Used in [rezervace.php](liga-app/rezervace.php) via CDN (v6.1.15)

## Common Tasks

**Add a new page:**
1. Create `page.php` in liga-app/
2. Start with `<?php require __DIR__.'/header.php'; ?>`
3. Query DB using prepared statements with `$conn->prepare()`
4. Every form needs `csrf_input()` and validate via csrf check
5. End with `<?php require __DIR__.'/footer.php'; ?>`

**Modify admin features:**
- Use [admin/_auth.php](liga-app/admin/_auth.php) to verify `$role === 'admin'`
- User management in [admin/create_user.php](liga-app/admin/create_user.php)
- Password hashing: `password_hash($pwd, PASSWORD_DEFAULT)` + `password_verify()`

**Debug Liga/Season issues:**
- `_active_rocnik_id($conn)` → autoselect latest season
- `_safe_liga_id()` → resolve cislo/liga param conflicts
- Check [liga.php](liga-app/liga.php) for full league display logic
