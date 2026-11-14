# Build Waves

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## Scheduling Rules

- A component may only be scheduled in a wave if all its Core dependencies are in earlier waves.
- NeoSPX components (CORE-07, CORE-11, CORE-12) are EXCLUDED from Stage 1 waves per K-S0b §7.6.
- Components requiring Twig, Doctrine, AWS SDK, Guzzle, or git-php **must not be scheduled**.
- If a dependency cycle is found, affected components are stopped.
- Missing or stub-only components are separated from implementation-ready components.

## Implementation-Ready Build Waves

### Wave 1 — Zero Core dependencies, zero forbidden external dependencies

| Component | Name | Core Dependencies | External Dependencies |
|---|---|---|---|
| CORE-02 | Reactive DI Container | (none) | psr/container |
| CORE-03 | PSR-14 Event Dispatcher | (none) | psr/container, psr/event-dispatcher, psr/log |
| CORE-04 | PSR-7 HTTP Message & Factory | (none) | psr/http-message |
| CORE-05 | PSR-15 Middleware & Request Handler | (none) | psr/container, psr/http-message, psr/http-server-handler, psr/http-server-middleware |
| CORE-06 | Attribute-Based Router | (none) | psr/http-message |
| CORE-08 | Global Error & Exception Handler | (none) | psr/log |
| CORE-09 | PSR-3 Logging Service | (none) | psr/log |
| CORE-10 | Configuration & Environment Loader | (none) | (none) |
| CORE-14 | Filesystem Abstraction | (none) | (none) |
| CORE-16 | Binary Encryption Envelope | (none) | psr/log |

**Count: 10 components.**

### Wave 2 — Core dependencies satisfied by Wave 1, zero forbidden external dependencies

| Component | Name | Core Dependencies | External Dependencies |
|---|---|---|---|
| CORE-18 | Core Kernel & Lifecycle | CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10 | psr/event-dispatcher, psr/http-message |

**Count: 1 component.**

## Not Scheduled — Forbidden External Dependencies

These components have source imports of forbidden packages (Doctrine, git-php) and must not be scheduled in Stage 1 build waves per K-S0b §7.5.

| Component | Name | Forbidden Dependency | Source Evidence |
|---|---|---|---|
| CORE-01 | Polyrepo Orchestrator (Loom) | CzProject (czproject/git-php) | `orchestrator/src/RepoManager.php` imports `CzProject\Git\GitRepository` |
| CORE-13 | CLI Engine (Console) | Doctrine | `CLI/migrate.php` imports `SovereignStack\Core\Database\Migration\DoctrineMigrationRunner` (which uses Doctrine DBAL) |
| CORE-19 | Database Abstraction Layer | Doctrine | `Core/Database/Migration/DoctrineMigrationRunner.php` imports `Doctrine\DBAL\Connection`, `Doctrine\DBAL\DriverManager` |

**Note on CORE-13:** The Doctrine dependency in CORE-13 is via `CLI/migrate.php` and `CLI/seed.php`, which are repository-level procedural scripts — not a Console Engine component. CORE-13's blueprint describes a CLI Engine (Console Application), but no Console Engine implementation exists. The scripts' imports of Container, Config, and Database are repository-level dependencies, not architectural dependencies of a CLI component. See CONFLICT-001 for the Loom CLI behavior conflict and the CLI classification issue in `conflicts.md`.

**Note on CORE-01:** CzProject (git-php) is used by the orchestrator's `RepoManager` for Git repository operations. This is an operational dependency of the polyrepo orchestrator, not a framework runtime dependency.

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
| CORE-15 | Cache Abstraction (PSR-6/16) | MISSING — no `Core/Cache/` directory exists | Blueprint only; zero implementation files |
| CORE-17 | Service Provider System | STUB — stub interface and empty implementation exist inside Kernel | `Core/Kernel/Stub/ProviderRegistryInterface.php` (interface), `Core/Kernel/Stub/EmptyProviderRegistry.php` (empty implementation). Not a dedicated component directory. |
| CORE-20 | Developer CLI Toolchain (Forge) | MISSING — no implementation directory exists | Blueprint only; zero implementation files |

## Summary

| Category | Count | Components |
|---|---:|---|
| Wave 1 (implementation-ready, zero deps) | 10 | CORE-02, 03, 04, 05, 06, 08, 09, 10, 14, 16 |
| Wave 2 (Core deps satisfied) | 1 | CORE-18 |
| Not scheduled (forbidden deps) | 3 | CORE-01, 13, 19 |
| NeoSPX excluded (§7.6) | 3 | CORE-07, 11, 12 |
| Missing/stub | 3 | CORE-15, 17, 20 |
| **Total** | **20** | |

## Execution Provenance

- **Executor:** Z.ai (Kilo was unavailable in the execution environment)
- **Method:** Read-only analysis via `git show origin/main:<path>` against verified baseline SHA
- **No tests executed:** This is source inspection only
- **No repositories modified**
