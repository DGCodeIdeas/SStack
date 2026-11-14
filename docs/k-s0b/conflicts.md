# Conflict Register

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

Conflicts are recorded without resolution. The project owner decides each individually.

**Execution provenance:** Z.ai executed this analysis (Kilo was unavailable). Method: read-only source inspection via `git show origin/main:<path>`. No tests executed. No repositories modified.

---

## CONFLICT-001: CORE-13 vs CORE-01 — Loom CLI behavior (MANDATORY)

**Status:** RECORDED — NOT RESOLVED

### Blueprint side (CORE-13)

CORE-13 (CLI Engine / Console) states that `bin/loom` builds a Console Application. The blueprint describes a Symfony-Console-style micro-framework that powers CORE-01's `bin/loom` entry point and CORE-20's `bin/forge` toolchain. The blueprint specifies that `bin/loom` constructs an `Application`, registers commands (`loom:release`, `loom:bump`, `loom:ci:wait`, etc.), and calls `$application->run($argv)`.

Key blueprint quotes:
> CORE-13 is the command-line interface engine for the SovereignStack Core tier.
> Downward: CORE-01 (Polyrepo Orchestrator) — the `bin/loom` executable constructs an Application.

### Blueprint side (CORE-01)

CORE-01 (Polyrepo Orchestrator / Loom) describes the orchestrator but does not establish that `bin/loom` builds a Console Application. The blueprint describes the orchestrator's role in polyrepo release coordination using Kahn's algorithm for tier-respecting topological sort (ADR-004).

### Code side

Actual `orchestrator/bin/loom` exists: **YES**

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);
// ... raw PHP script that parses $_SERVER['argv'] directly
```

The actual `bin/loom` is a raw PHP script that does NOT construct an `Application` or use a Console engine. It parses `$_SERVER['argv']` directly with a `switch` statement.

Additionally, `CLI/` directory contains only 2 procedural scripts:
- `CLI/migrate.php` — calls `DoctrineMigrationRunner` directly
- `CLI/seed.php` — broken (imports non-existent classes; documented in prior Phase 0 analysis)

No Console Engine (`Application`, `Command`, `CommandRegistry`, `InputInterface`, `OutputInterface`) implementation exists in the repository.

### Architectural consequences

- If CORE-13 is correct, Loom should be a Console command within the CLI tier, and a Console Engine must be built.
- If CORE-01 is correct, Loom is an orchestrator that may not require a Console Engine.
- The placement of Loom in the CLI tier depends on resolving this conflict.
- The CLI scripts (`migrate.php`, `seed.php`) are repository-level procedural scripts, NOT a CORE-13 Console Engine component. Their imports of Container, Config, and Database are script-level dependencies, not architectural dependencies of a CLI component.

### Possible resolutions (NOT chosen)

1. Loom is both an orchestrator AND a CLI command (orchestrator registers as a Console command).
2. Loom is an orchestrator only; `bin/loom` is a CLI wrapper that invokes the orchestrator.
3. CORE-13's claim about `bin/loom` is aspirational, not yet implemented.

---

## CONFLICT-002: Namespace — Http vs HttpMessage

**Status:** RECORDED — NOT RESOLVED

### Blueprint side

CORE-04 (PSR-7 HTTP Message & Factory) implies namespace `SovereignStack\Core\Http\`.
K-S0b §7.7 specifies namespace target: `SovereignStack\Core\Http` for HTTP messages, `SovereignStack\Core\Middleware` for middleware.

### Code side

Actual directory: `Core/HttpMessage/` (not `Core/Http/`)
Actual autoload: `SovereignStack\Core\Http\` maps to BOTH `Core/HttpMessage/` AND `Core/Middleware/`
The PSR-4 autoload maps the `Http` namespace to two directories: HttpMessage and Middleware.

### Consequences

- The directory name (`HttpMessage`) differs from the namespace (`Http`).
- The approved namespace target (`SovereignStack\Core\Http`) matches the autoload but not the directory name.
- SStack reconstruction must decide: use `Core/Http/` directory or keep `Core/HttpMessage/`.

---

## CONFLICT-003: CORE-15 (Cache) — Missing implementation

**Status:** RECORDED — NOT RESOLVED

CORE-15 (Cache Abstraction, PSR-6/16) has a blueprint but no implementation directory. No `Core/Cache/` directory exists in DGLab. The component is not implementation-ready.

---

## CONFLICT-004: CORE-17 (Service Providers) — Stub only

**Status:** RECORDED — NOT RESOLVED

CORE-17 (Service Provider System) has a blueprint but is implemented only as stub files inside the Kernel component:

- `Core/Kernel/Stub/ProviderRegistryInterface.php` — stub interface with `registerAll()` and `bootAll()` methods
- `Core/Kernel/Stub/EmptyProviderRegistry.php` — empty implementation (no-op)

**Reconciliation note:** The blueprint inventory (`blueprint-inventory.md`) initially reported CORE-17 as having "No implementation found." This was because the inventory looked for a dedicated `Core/ServiceProvider/` directory. The stub files DO exist, but they are located inside `Core/Kernel/Stub/`, not in a dedicated component directory. The stub is a temporary placeholder that ships inside the Kernel package to unblock CORE-18 (Kernel), as documented in the stub's own docblock:

> Stub interface for CORE-17 (Service Providers). CORE-17 is not yet implemented. This interface is a temporary placeholder that ships inside the kernel package to unblock CORE-18.

The component is not implementation-ready. Full implementation is deferred.

---

## CONFLICT-005: CORE-20 (Forge) — Missing implementation

**Status:** RECORDED — NOT RESOLVED

CORE-20 (Developer CLI Toolchain / Sovereign Forge) has a blueprint but no implementation directory. The component is not implementation-ready.

---

## CONFLICT-006: Twig dependency in Core/View — HISTORICAL/MIGRATION CONSTRAINT

**Status:** RECLASSIFIED — Historical dependency, not an outstanding owner decision

### Background

DGLab's `Core/View/` (8 PHP files, 444 lines) depends on Twig (`twig/twig: ^3.30`). The source imports `Twig\*` classes.

### Owner decision (already settled)

ADR-S02 (approved and frozen, 14 November 2026) explicitly removes Twig from SStack:
- No Twig runtime dependency in SStack
- No Twig compatibility layer
- No Twig migration tool
- Existing templates will be rewritten using native syntax (NeoSPX)

### Classification

This is NOT an outstanding conflict requiring owner decision. The Twig decision is settled by ADR-S02. This entry is classified as a **historical dependency / migration constraint**:

- DGLab's `Core/View/` has a Twig dependency that must be replaced during SStack reconstruction.
- The replacement is NeoSPX (native rendering engine), which is deferred until HTTP Kernel acceptance per ADR-S02 §6.3.
- Until NeoSPX is implemented, SStack's View component will be non-functional or absent.
- No owner decision is required — the path forward is defined by ADR-S02.

### Migration implication

During SStack reconstruction, `Core/View/` cannot be copied directly (it depends on Twig). The component must either:
1. Be deferred entirely until NeoSPX is implemented (per ADR-S02 §6.3).
2. Be copied with Twig dependency stubbed out (non-functional placeholder).

This is a scheduling constraint, not an architectural conflict.

---

## CLI Script Classification Note

The `CLI/` directory contains two procedural scripts (`migrate.php`, `seed.php`) that import Core namespaces (Container, Config, Database). These imports were recorded in the dependency analysis as CORE-13 dependencies. However, the architectural classification is:

- These are **repository-level scripts**, not a Console Engine component.
- CORE-13's blueprint describes a Console Engine (Application, Command, CommandRegistry), but no such engine exists in the codebase.
- The scripts' imports of `SovereignStack\Core\Database\Migration\DoctrineMigrationRunner`, `SovereignStack\Core\Container\Container`, and `SovereignStack\Core\Config\ConfigRepository` are script-level usage, not component-level architectural dependencies.
- The scripts should not be treated as evidence that a CLI Engine component depends on Container, Config, or Database. They are evidence that repository-level utility scripts use Core services directly.

This classification affects:
- Build wave scheduling (CORE-13's "dependencies" on Container/Config/Database are script-level, not component-level)
- DAG verification (the DAG claim "only Kernel imports sibling Core namespaces" is technically true at the component level — the CLI scripts are not a CORE-13 Console Engine component)
- However, the imports DO exist in source code under the `CLI/` directory, so they are recorded as SOURCE_IMPORTED dependencies with the clarification above.
