# SunPlex.live — Session Log

> Running history of work on this project. **Read this first** at the start of each session to
> restore context, then append a new entry at the end of the session.

---

## Project snapshot
- **Goal:** Rebuild WordPress `sunplex.live` as a standalone PHP + MySQL IPTV platform using the
  FWD Ultimate Video Player **JS** engine.
- **Stack:** PHP 8.2, MariaDB (XAMPP), FWD UVP v11.0 JS engine, dark IPTV grid theme.
- **Scope:** Core + Subscriptions, with a site-wide subscriptions on/off toggle. No payment gateway.
- **Repo:** https://github.com/kawsershourov/IPTv-Streaming-Services (public; `origin/main`).
- **Plan:** see [`plan.md`](plan.md).

## How to run
1. XAMPP: start Apache + MySQL.
2. DB: create `sunplex` (utf8mb4), import `sql/schema.sql` then `sql/seed.sql`.
3. Copy `app/config.sample.php` → `app/config.php` (XAMPP defaults already set).
4. Browse http://localhost/SunPlex.live/ — admin at `/admin/` (`admin@sunplex.live` / `admin123`).

## Build phases — status (all complete)
- [x] 1. Repo init (git, .gitignore/.gitattributes, README, plan.md, session.md, config sample)
- [x] 2. Database (schema.sql + seed.sql) — verified import on MariaDB 10.4
- [x] 3. App core (bootstrap, db PDO, helpers, auth, access, models, front-end shell)
- [x] 4. Player assets — **full official UVP v11.0 package** copied into `/player`
- [x] 5. Auth pages (register/login/logout/forgot+reset-password/account)
- [x] 6. Home + category pages + `site.css` dark grid
- [x] 7. Watch page — UVP embed (verified v11.0 markup) + access gating + channel rail
- [x] 8. Subscriptions — plans/subscribe + `subscriptions_enabled` toggle + gating
- [x] 9. Admin panel — dashboard + CRUD (categories/channels/users/plans/settings)
- [x] 10. Polish — root `.htaccess` hardening, README/docs, session log

## Key implementation facts (so future sessions don't re-derive)
- **Config:** `app/config.php` (git-ignored) returns an array; access via `config('db.host')` etc.
- **DB access:** `db()` PDO singleton + `db_one/db_all/db_run`. Models are thin static classes
  in `app/models/`.
- **Auth:** `current_user()` (cached; reset with `auth_reset_cache()` after login/logout),
  `require_login()`, `require_admin()`, CSRF via `csrf_field()`/`csrf_verify()`.
- **Access:** `subscriptions_enabled()` + `guest_access_enabled()` gates;
  `can_watch($channel,$user)` and `watch_block_reason()` in `app/access.php`. Premium needs a PAID
  active sub (price>0). Guest access ON = visitors watch without login (free channels when subs on,
  everything when subs off); premium prompts login. Both toggles live in admin Settings.
- **UVP embed (verified, in `watch.php`):** global `FWDUVPlayer` constructor; playlists come
  from HTML markup — `#playlists > div[data-source=<videosId>][data-thumbnail-path]`, then
  `#<videosId> > a[data-thumb-source][data-video-source][data-is-live]` with a child carrying
  `data-video-short-description` (title). HLS auto-detected from `.m3u8`. Required props:
  `instanceName, parentId, playlistsId, mainFolderPath(=/player/content), skinPath`.
  Player playlist only includes channels the user may watch (no premium URL leak).
- **`/player`** is the pristine official package — to update, replace the folder with a newer
  official UVP release.

## Verification done this session
- SQL import OK (1 admin, 3 plans, 6 categories, 15 channels).
- Register→login→account, admin login, all admin pages 200, CRUD reflected on front-end.
- Watch gating: anon→302, free plays, premium→upsell (no URL leak); subscribe→access granted.
- Subscriptions toggle: off → plans redirect, premium opens freely, plan UI hidden.
- Player assets (FWDUVP.js, css, hls.js) serve 200.

---

## Sessions

### 2026-06-08 — Session 3 (player settings in admin)
**Done:** New **Admin → Player** page (`admin/player.php`) manages all UVP options: skin, bg color,
max width/height, vector icons, autoplay, volume, playlist position/width, show-by-default, playlists
dropdown, search, and every controller button. Central helper `app/player.php` →
`uvp_base_config()` + `uvp_player_script()`; `index.php`/`watch.php` now build the player from it
(no hardcoded props). Player fields removed from Settings (skin/size moved to Player). 23 `player_*`
settings added to seed + DB. Verified: admin save instantly changes the live player config.
Also added a **Colors** section (HEX theming): `useHEXColorsForSkin` + button/time/playlist/
thumbnail/search/dropdown-selector/preloader colors (prop names verified in the engine; see
`player_color_map()` in `app/player.php`). 15 color settings added to seed + DB.
Later extended the button toggles to cover **every** control-bar icon, grouped left/right/playlist:
added playlist-button, controller prev/next, subtitles, quality(HD), audio tracks, chromecast,
360/VR (props verified in engine). `showPlaylistButtonAndPlaylist` moved into base config (no longer
hardcoded in index/watch). 25 toggles total on the Player page.
Fixes: home `showPlaylistsByDefault` now follows the `player_show_playlist_by_default` toggle
(so admins can stop the playlist popping open on reload); volume hover slider fixed by setting
`volumeScrubberHeight:80` (engine default was 10) + `controllerHeight:50` + `showButtonsToolTips:yes`
in `uvp_base_config()`. Then split the playlist-on-load into two independent toggles (engine props
are separate: `showPlaylistByDefault`=right-side list vs `showPlaylistsByDefault`=popup window):
`player_show_playlist_by_default` (right list, default ON) and `player_show_playlists_popup`
(selector popup, default OFF) — so the right list always shows but no popup animates on reload.
Home list overhaul: default to a single **All Channels** list (no category dropdown:
`player_show_playlists_button`/`player_use_playlists_select_box` default OFF; index.php only renders
per-category playlists when the dropdown is on). Channel cards: admin `player_thumb_width/height`
(default 40, ~half) -> thumbnailWidth/Height; locked/premium item color
`player_thumb_disabled_bg` -> thumbnailDisabledBackgroundColor (engine default was red #FF0000);
active item = `player_thumb_hover_bg` (relabelled). Channel name: wrapped in `.sp-chname` span with
injected `<style>` from `player_channel_name_size` + `player_channel_name_align` (default center 13px).
Bottom playlist bar = the existing Search + loop/shuffle/next-prev toggles.
Player extracted to reusable `app/includes/channel_player.php` (+ `player_head_assets()` in
app/player.php); home uses all channels, **category.php now shows the same player layout scoped to
one category's channels** (replaced the old grid). Playlist header name fixed via `data-playlist-name`.
Duplicate stream URLs: append a unique `#uvp<id>` fragment so both channels reload/play.
Site logo: `site_logo` setting + upload in admin Settings (`upload_image()` helper) shown in the
header (falls back to the SunPlex text).

### 2026-06-08 — Session 2 (home = live-TV player)
**Done:** Replaced the home grid with a **full UVP player** (the sunplex.live live-TV layout):
big video + right-side vertical playlist + "All Channels"/per-category dropdown + search.
Gating-safe via per-item `data-redirect-url` — watchable channels carry the real stream, locked
ones (🔒) carry only a redirect to `watch.php` (login/upsell); autoplay starts on the first
watchable channel. Files: `index.php` (rewritten), `header.php` (`$bodyClass`), `site.css`
(`.home-player`). Verified: 0 locked items contain a stream URL. Category grids still live at
`category.php`.

### 2026-06-08 — Session 1
**Done:** Analyzed live `sunplex.live` (WordPress + UVP plugin IPTV). Planned and built the entire
platform Phases 1–10. Created the public GitHub repo `IPTv-Streaming-Services` and pushed every
phase commit. Fixed a current_user() caching bug affecting admin login. Implemented the
user-requested site-wide subscriptions on/off toggle. Kept the full official UVP package in `/player`.

**Current state:** Feature-complete MVP running on XAMPP and pushed to GitHub `origin/main`.

**Next steps (open follow-ups):**
- Replace placeholder seed stream URLs with real **licensed** streams via the admin panel.
- Optional: payment gateway (bKash/Stripe) on top of the plans model.
- Optional: SMTP for real password-reset emails; tokenized/secured stream URLs; HTTPS.
- Optional: import existing WordPress users/channels.

**Notes:** Default admin password is `admin123` — change it after first login. `app/config.php`,
`/uploads/*`, the raw plugin folder and the UVP zip are git-ignored.
