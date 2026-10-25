# Jayahana Absensi — Master Blueprint V1.3.4

Status: **Design baseline approved; implementation and hosting staging not yet approved/deployed**  
Date: 2026-10-25  
Audience: product owner, ChatGPT, Codex, developers

## Product vision
A friendly, fast, trustworthy employee attendance and HRIS experience. Prioritize ease of use on Android and iPhone without sacrificing desktop capabilities or existing business rules. The design direction is **Modern Friendly Premium** — red/black Jayahana identity with spacious, light surfaces, soft corners and restrained motion. Do not copy third-party branded assets or proprietary layouts.

## Design tokens
- Primary `#B91C1C`; dark `#171717`; soft-red `#FFF1F2`; canvas `#F8FAFC`; success `#16A34A`; warning `#D97706`; error `#DC2626`.
- Font: Inter with system sans-serif fallback; readable contrast and text scalability.
- Spacing: 4/8/12/16/24/32px; card radius 12–20px; tap targets at least 44x44px (primary buttons minimum 48px tall).
- Motion 150–250ms where useful; honor reduced-motion preference. Avoid heavy mobile assets.
- Never signal success before server persistence is confirmed.

## Employee experience
- Primary mobile navigation: **Beranda, Riwayat, Pengajuan, Profil**.
- Above the fold: friendly greeting, current WIB time/date, today's status, and **one primary attendance action** (check-in or check-out as appropriate).
- Flow: choose action → location and camera permission/explanations → selfie + location check → confirmation → server submission → accurate feedback and refreshed history.
- GPS outside office radius is labeled for monitoring; **do not block submission solely for radius**. Field assignments may happen anywhere, with coordinates or location details when available.
- Keep empty, loading, failure, offline, denied-permission and slow-network states plain-language and recoverable.
- Mobile history uses compact cards; desktop can use tables. Keep forms concise and accessible.

## Admin/HR
Maintain staff management, schedules, attendance monitoring, requests, approvals, exports and auditability. Admin UI may adopt Filament only after dependency/security compatibility review; it is **not** a mandatory rewrite.

## Non-negotiable business rules
- Sunday attendance is recorded as holiday overtime and remains pending until authorized Admin/HR approval or rejection.
- Employee missed check-in/out corrections: within 3 calendar days, including weekends/holidays, until 23:59 WIB on day 3; after that only Admin/HR.
- Out-of-radius is monitoring, not blanket rejection. Field workers remain supported.
- Selfie evidence is compressed as appropriate and stored privately; never expose through unauthenticated public paths.
- Preserve HR authorization boundaries and important working features when redesigning.
- Planned Google Sheets sync is one-way and resilient; do not silently enable it or send personal records without separate implementation approval.

## Architecture
Keep the existing Laravel 12 codebase and business logic. Prefer reusable Blade/Tailwind/Alpine components; consider Livewire/Filament only where compatible. Keep employee UI separate from admin complexity. APIs/integrations must be modular. Do not assume a new database migration, rewrite or PWA capability is already deployed.

## Preview → approval → production
1. Audit code/Hostinger in read-only mode.
2. Implement on branch with documented tests and versioned commit.
3. Deploy **only after separately authorized staging setup** to an access-controlled preview subdomain (proposed `preview-absensi.jayahanagroups.my.id`) with independent DB, file storage and synthetic accounts; outbound notifications disabled.
4. Test business flows and responsive UI across desktop, Android and iPhone.
5. Owner reviews and approves the exact commit/version.
6. Before separate production authorization: verify backups, restore and rollback approach; plan migrations with impact assessment.
7. Deploy scoped approved commit, run smoke checks and verify data integrity. Roll back if needed.

## Quality gates
- Laravel and JavaScript tests; browser E2E tests where available.
- Attendance check-in/out, duplicate prevention, GPS and selfie, role access, requests, Sunday overtime, correction window and export regression checks.
- Mobile responsive, PWA where implemented, contrast, keyboard and touch accessibility.
- No operational secrets or personal data in public repo, test fixtures, screenshots or logs.

## Codex collaboration
Follow `AGENTS.md`, this document and domain rules. Every pull request summarizes impact, compatibility, tests, staging evidence and rollback notes. Document design changes in changelog. Approval of this blueprint authorizes documentation and planning only, not production modifications.
