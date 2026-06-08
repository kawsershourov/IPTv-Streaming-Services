# SunPlex.live — Session Log

> Running history of work on this project. **Read this first** at the start of each session to
> restore context, then append a new entry at the end of the session.

---

## Project snapshot
- **Goal:** Rebuild WordPress `sunplex.live` as a standalone PHP + MySQL IPTV platform using the
  FWD Ultimate Video Player JS engine.
- **Stack:** PHP 8.2, MySQL (XAMPP), FWD UVP v11.0 JS engine, dark IPTV grid theme.
- **Scope:** Core + Subscriptions (no payment gateway yet).
- **Plan:** see [`plan.md`](plan.md).

## Build phases — status
- [x] 1. Repo init (git, .gitignore, README, plan.md, session.md, config.sample.php)
- [ ] 2. Database (schema.sql + seed.sql)
- [ ] 3. App core (config, db, auth, access, helpers, models, includes)
- [ ] 4. Player assets (extract UVP engine → /player)
- [ ] 5. Auth pages (register/login/logout/forgot-password/account)
- [ ] 6. Home + category (grid + site.css)
- [ ] 7. Watch page (UVP embed + access gating + channel rail)
- [ ] 8. Subscriptions (plans, account, premium gating)
- [ ] 9. Admin panel (categories, channels, users, plans, settings)
- [ ] 10. Seed real data + polish + docs

---

## Sessions

### 2026-06-08 — Session 1
**Done**
- Analyzed the live site `sunplex.live`: WordPress (`spcorelive` theme) + FWD UVP plugin, IPTV
  service (live sports/news/entertainment/Bangladeshi channels) with user accounts.
- Inspected the on-disk UVP v11.0 plugin; confirmed the JS engine `window.FWDUVPlayer`
  (`js/FWDUVP-unminified.js:19679`) embeds without WordPress and supports HLS `.m3u8`.
- Wrote the build plan and got it approved.
- **Phase 1 complete:** initialized git, added `.gitignore`, `README.md`, `plan.md`,
  `session.md`, and `app/config.sample.php`.

**Current state**
- Repo initialized; base docs in place. No DB or app code yet.

**Next steps**
- Phase 2: write `sql/schema.sql` + `sql/seed.sql`.

**Open questions / notes**
- Stream URLs in the seed are placeholders — operator supplies licensed sources via admin.
- Verify exact UVP v11.0 player prop names against the unminified engine when wiring `watch.php`.
