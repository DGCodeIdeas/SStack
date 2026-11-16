# Build Waves

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## SStack Dependency Policy

SStack may depend on:
- **ALLOWED:** PHP runtime and required PHP extensions.
- **ALLOWED:** PSR contract/interface packages (`psr/*`). These provide interfaces only, not implementations.
- **FORBIDDEN:** Third-party implementation libraries. Framework functionality must be implemented natively within SovereignStack.

Forbidden third-party implementation libraries include (non-exhaustive):
- Twig (template engine — superseded by ADR-S02 NeoSPX)
- Doctrine DBAL (database abstraction — SStack will implement natively)
- Guzzle (HTTP client)
- AWS SDK
- git-php / CzProject (Git wrapper)
- predis (Redis client)
- nikic/php-parser (PHP parser)
- php-http/discovery (HTTP factory discovery)

DGLab's `composer.json` contains these packages as historical dependencies. Their presence in DGLab does NOT authorize their introduction into SStack. DGLab source imports of these packages are recorded as HISTORICAL evidence in `core-dependencies.csv` — they document what DGLab does, not what SStack may do.

## Scheduling Rules

- A component may only be scheduled in a wave if all its Core dependencies are in earlier waves.
- A component may only be scheduled if its external dependencies are ALLOWED (PHP runtime, PHP extensions, or PSR contract packages).
- Components with FORBIDDEN third-party implementation dependencies must NOT be scheduled.
- NeoSPX components (CORE-07, CORE-11, CORE-12) are EXCLUDED from Stage 1 waves per K-S0b §7.6.
- If a dependency cycle is found, affected components are stopped.
- Missing or stub-only components are separated from implementation-ready components.
- Repository-level scripts (e.g., `CLI/migrate.php`, `CLI/seed.php`) are NOT component-level dependencies. Their imports are recorded in the dependency CSV and conflicts.md but do not determine a component's build-wave eligibility.

## Implementation-Ready Build Waves

### Wave 1 — Zero Core dependencies, external deps are ALLOWED (PSR contracts only)

| Component | Name | Core Dependencies | External Dependencies | Policy |
|---|---|---|---|---|
| CORE-02 | Reactive DI Container | (none) | psr/container | ALLOWED (PSR-11 interface) |
| CORE-03 | PSR-14 Event Dispatcher | (none) | psr/container, psr/event-dispatcher, psr/log | ALLOWED (PSR interfaces) |
| CORE-04 | PSR-7 HTTP Message & Factory | (none) | psr/http-factory, psr/http-message | ALLOWED (PSR-7 + PSR-17 interfaces) |
| CORE-05 | PSR-15 Middleware & Request Handler | (none) | psr/container, psr/http-message, psr/http-server-handler, psr/http-server-middleware | ALLOWED (PSR interfaces) |
| CORE-06 | Attribute-Based Router | (none) | psr/http-message | ALLOWED (PSR-7 interface) |
| CORE-08 | Global Error & Exception Handler | (none) | psr/log | ALLOWED (PSR-3 interface) |
| CORE-09 | PSR-3 Logging Service | (none) | psr/log | ALLOWED (PSR-3 interface) |
| CORE-10 | Configuration & Environment Loader | (none) | (none) | ALLOWED (PHP only) |
| CORE-14 | Filesystem Abstraction | (none) | (none) | ALLOWED (PHP only) |
| CORE-16 | Binary Encryption Envelope | (none) | psr/log | ALLOWED (PSR-3 interface) |

**Count: 10 components.** All external dependencies are PSR contract interfaces — no third-party implementation libraries.

### Wave 2 — Core dependencies satisfied by Wave 1, external deps are ALLOWED

| Component | Name | Core Dependencies | External Dependencies | Policy |
|---|---|---|---|---|
| CORE-18 | Core Kernel & Lifecycle | CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10 | psr/event-dispatcher, psr/http-message | ALLOWED (PSR interfaces) |

**Count: 1 component.** All external dependencies are PSR contract interfaces.

## Not Scheduled — FORBIDDEN Third-Party Implementation Dependencies

These components have source imports of third-party implementation libraries that are FORBIDDEN under the SStack dependency policy. They are HISTORICAL DGLab dependencies, not approved for SStack.

| Component | Name | Forbidden Dependency | Source Evidence | SStack Action |
|---|---|---|---|---|
| CORE-01 | Polyrepo Orchestrator (Loom) | CzProject (czproject/git-php) | `orchestrator/src/RepoManager.php` imports `CzProject\Git\GitRepository` | SStack must implement Git operations natively or defer Loom |
| CORE-19 | Database Abstraction Layer | Doctrine (doctrine/dbal) | `Core/Database/Migration/DoctrineMigrationRunner.php` imports `Doctrine\DBAL\Connection`, `Doctrine\DBAL\DriverManager` | SStack must implement database abstraction natively |

**Note:** CORE-01's CzProject dependency is an operational dependency of the polyrepo orchestrator. CORE-19's Doctrine dependency is a framework-level database abstraction dependency. Both require native SStack implementations to be scheduled.

## Not Implemented — No Console Engine Component Exists

CORE-13 (CLI Engine / Console) has a blueprint describing a Console Engine (Application, Command, CommandRegistry, InputInterface, OutputInterface) but **no Console Engine implementation exists** in the repository. The `CLI/` directory contains only two repository-level procedural scripts:

- `CLI/migrate.php` — calls `DoctrineMigrationRunner` directly (HISTORICAL: imports Container, Config, Database — script-level, not component-level)
- `CLI/seed.php` — broken (imports non-existent classes; documented in prior Phase 0 analysis)

These scripts are **repository-level utilities**, not a CORE-13 Console Engine component. Their imports of Container, Config, and Database are script-level usage and do NOT constitute CORE-13 component-level dependencies. See `conflicts.md` CLI Script Classification Note for details.

CORE-13 is classified as **not implemented** — it cannot be scheduled in build waves because the component does not exist. The script-level Doctrine import in `CLI/migrate.php` does not make CORE-13 a forbidden-dependency component; the script is not the component.

CONFLICT-001 (CORE-13 vs CORE-01 Loom CLI behavior) remains unresolved.

## NeoSPX Exclusion (per K-S0b §7.6)

| Component | Name | Status |
|---|---|---|
| CORE-07 | NeoSPX Lexer | Excluded from Stage 1 — inventoried and analyzed but not scheduled |
| CORE-11 | NeoSPX Parser | Excluded from Stage 1 — inventoried and analyzed but not scheduled |
| CORE-12 | NeoSPX Compiler | Excluded from Stage 1 — inventoried and analyzed but not scheduled |

NeoSPX implementation is deferred until the HTTP Kernel boots and handles requests reliably, per ADR-S02 §6.3.

## Missing or Stub-Only Components

These components have blueprints but no implementation directory or only stub files. They are not implementation-ready and cannot be scheduled.

| Component | Name | Implementation Status | Evidence |
|---|---|---|---|
| CORE-13 | CLI Engine (Console) | NOT IMPLEMENTED — no Console Engine exists; only repository-level scripts in `CLI/` | Blueprint describes Application/Command/CommandRegistry; no such classes exist in source |
| CORE-15 | Cache Abstraction (PSR-6/16) | MISSING — no `Core/Cache/` directory exists | Blueprint only; zero implementation files |
| CORE-17 | Service Provider System | STUB — stub interface and empty implementation exist inside Kernel | `Core/Kernel/Stub/ProviderRegistryInterface.php` (interface), `Core/Kernel/Stub/EmptyProviderRegistry.php` (empty implementation). Not a dedicated component directory. |
| CORE-20 | Developer CLI Toolchain (Forge) | MISSING — no implementation directory exists | Blueprint only; zero implementation files |

## Summary

| Category | Count | Components |
|---|---:|---|
| Wave 1 (implementation-ready, ALLOWED deps only) | 10 | CORE-02, 03, 04, 05, 06, 08, 09, 10, 14, 16 |
| Wave 2 (Core deps satisfied, ALLOWED deps only) | 1 | CORE-18 |
| Not scheduled (FORBIDDEN third-party impl deps) | 2 | CORE-01, 19 |
| NeoSPX excluded (§7.6) | 3 | CORE-07, 11, 12 |
| Not implemented / missing / stub | 4 | CORE-13, 15, 17, 20 |
| **Total** | **20** | |

## Execution Provenance

- **Executor:** Z.ai (Kilo was unavailable in the execution environment)
- **Method:** Read-only analysis via `git show origin/main:<path>` against verified baseline SHA
- **No tests executed:** This is source inspection only
- **No repositories modified**
