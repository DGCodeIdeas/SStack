# Conflict Register

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

Conflicts are recorded with their approved resolutions. Owner decisions are referenced, not rewritten.

**Execution provenance:** Z.ai executed this analysis (Kilo was unavailable). Method: read-only source inspection via `git show origin/main:<path>`. No tests executed. No repositories modified.

## SStack Dependency Policy

- **ALLOWED:** PHP runtime, PHP extensions, and PSR contract/interface packages (`psr/*`).
- **FORBIDDEN:** Third-party implementation libraries. SStack implements framework functionality natively.
- **HISTORICAL:** DGLab source imports of forbidden packages — recorded as evidence, not authorization.

---

## CONFLICT-001: CORE-13 vs CORE-01 — Loom CLI behavior
**Status:** RESOLVED by OD-001

Both independent and Console-integrated entry points are first-class. `bin/loom` provides an independent entry point; Console Application may expose Loom via commands. Loom orchestration must not depend on CLI. ADR-S01 tier boundaries preserved. See `docs/architecture/owner-decisions-2026-11.md` OD-001.

## CONFLICT-002: Namespace — Http vs HttpMessage
**Status:** RESOLVED by OD-002

HTTP messages: `Core/Http/`, namespace `SovereignStack\Core\Http`. Middleware: `Core/Middleware/`, namespace `SovereignStack\Core\Middleware`. See OD-002.

## CONFLICT-003: CORE-15 (Cache) — Missing implementation
**Status:** RESOLVED by OD-003

Implement Cache as a native SovereignStack Core capability. No third-party cache libraries. See OD-003.

## CONFLICT-004: CORE-17 (Service Providers) — Stub only
**Status:** RESOLVED by OD-004

Implement a complete native Service Provider system. Existing Kernel stubs to be inspected and evolved or replaced. See OD-004.

## CONFLICT-005: CORE-20 (Forge) — Missing implementation
**Status:** RESOLVED by OD-005

Implement Forge as a native CLI-tier development tool. Separate from initial Console slice. See OD-005.

## CONFLICT-006: Twig dependency in Core/View
**Status:** RESOLVED by ADR-S02 (frozen)

Twig removed from SStack. NeoSPX native rendering engine replaces it. Historical DGLab dependency classified as migration constraint. `Core/View/` has no dedicated blueprint in CORE-01 to CORE-20.

## CLI Script Classification Note

`CLI/migrate.php` and `CLI/seed.php` are repository-level procedural scripts, not a CORE-13 Console Engine component. Their imports of Container, Config, and Database are script-level usage, not component-level architectural dependencies. CORE-13's blueprint describes a Console Engine but no implementation exists.
