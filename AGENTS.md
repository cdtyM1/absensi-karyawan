# Codex Working Rules — Jayahana Absensi

Scope: this repository, including `backend/`. Read `docs/BLUEPRINT.md` before editing UI, behavior, schemas or deployment configuration.

## Default workflow (Level B, semi-autonomous)
1. Inspect relevant code, tests, and documentation first.
2. Propose a focused implementation plan; implement approved, scoped changes on a feature branch.
3. Preserve existing attendance behavior, data integrity, access permissions, and mobile UX.
4. Add or update automated tests; report commands run and failures honestly.
5. Update documentation and changelog for behavior changes.
6. Prepare changes for review. Never equate preview approval with permission to deploy production.

## Allowed without repeated permission
- Read-only repository and environment audit, excluding secrets and employee personal data.
- Editing scoped source, documentation, and tests in a non-production branch or isolated staging workspace.
- Synthetic-data testing and building assets in an isolated environment.

## Requires explicit authorization
- Deploy or change production code/configuration; production migrations and scheduler activation.
- Any operational database mutation, destructive action, changing DNS, email routing, or production credentials.
- Changing attendance policies, access models, employee records, or handling real selfie/GPS data.
- Merging into protected main or changing remote deployment configuration unless individually authorized.

## Safety and testing
- Never print, commit or paste `.env`, passwords, tokens, employee/selfie/GPS records.
- Stage must have a separate database, storage, test accounts, and disabled outbound notifications.
- Verify backup restore and rollback path before an approved production release.
- Retain Sunday overtime approval, 3-calendar-day corrections, off-radius monitoring (not automatic rejection), field worker support, private selfie storage, and server-verified success feedback.
- Check responsive UI and accessibility; do not introduce insecure public storage or bypass HR approvals.
- If an action affects real data or has unclear blast radius, stop and request scope-specific approval.

## Repository note
The root contains a browser demo; Laravel backend code is in `backend/`. Inspect the deployment environment separately; do not assume GitHub main equals the production version.
