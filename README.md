# SunPlex.live — IPTV Streaming Platform

A standalone **PHP + MySQL** rebuild of `sunplex.live` (formerly WordPress) that uses the
**FWD Ultimate Video Player (UVP) v11.0 JS engine** for live HLS playback, with a custom
backend for channels, categories, users, and subscription-based access.

> Replaces the old WordPress (`spcorelive` theme) + UVP plugin setup with a lightweight,
> self-contained app that runs directly under XAMPP.

**Repository:** https://github.com/kawsershourov/IPTv-Streaming-Services

---

## Features

- Live HLS (`.m3u8`) playback via the official FWD UVP v11.0 JS engine (`/player`)
- Category rows + channel grid (sunplex.live-style dark theme)
- User accounts: register, login, logout, password reset
- Subscription plans with premium/expiry gating — and a **site-wide on/off toggle**
  (off = every signed-in member watches everything; no plan UI shown)
- Admin panel: dashboard + CRUD for categories, channels (with logo upload),
  users (role/status, grant plan), plans, and settings

---

## Tech stack

- **Backend:** PHP 8.2 (PDO), MySQL / MariaDB
- **Front-end:** HTML/CSS/JS, dark IPTV grid theme
- **Player:** FWD Ultimate Video Player JS engine (`window.FWDUVPlayer`), HLS `.m3u8`
- **Server:** Apache (XAMPP) — served from `htdocs/SunPlex.live`

---

## Local setup (XAMPP)

1. **Start** Apache + MySQL from the XAMPP control panel.
2. **Create the database** and import schema + seed:
   - Open phpMyAdmin → create database `sunplex` (utf8mb4).
   - Import `sql/schema.sql`, then `sql/seed.sql`.
   - _Or_ from the CLI:
     ```
     c:\xampp\mysql\bin\mysql -u root sunplex < sql/schema.sql
     c:\xampp\mysql\bin\mysql -u root sunplex < sql/seed.sql
     ```
3. **Configure** the app: copy `app/config.sample.php` → `app/config.php` and set DB credentials
   (XAMPP default: user `root`, empty password).
4. **Browse:** http://localhost/SunPlex.live/

### Default admin login (from seed)
- URL: http://localhost/SunPlex.live/admin/
- Email: `admin@sunplex.live`
- Password: `admin123` — **change immediately after first login.**

---

## Project layout

```
index.php / category.php / watch.php / login.php / register.php / account.php   front-end
/app        backend internals (db, auth, access, helpers, models, includes) — not web-served
/admin      admin panel (categories, channels, users, plans, settings)
/player     FWD UVP JS engine (extracted from the plugin)
/assets     site CSS/JS/images
/sql        schema.sql + seed.sql
/uploads    channel logos uploaded via admin (git-ignored)
```

---

## Content & licensing note

This is **platform software**. Stream URLs are entered by the operator via the admin panel and are
seeded only with **placeholders**. The operator is responsible for supplying **legally licensed**
stream sources for any third-party channels.

---

## Status & history

See [`plan.md`](plan.md) for the build plan and [`session.md`](session.md) for the running
session log / current state.
