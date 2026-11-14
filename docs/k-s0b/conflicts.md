# Conflict Register

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

Conflicts are recorded without resolution. The project owner decides each individually.

## CONFLICT-001: CORE-13 vs CORE-01 — Loom CLI behavior (MANDATORY)

**Status:** RECORDED — NOT RESOLVED

### Blueprint side (CORE-13)

CORE-13 (CLI Engine / Console) states that `bin/loom` builds a Console Application.

> - **Finding 2** — re-anchors CORE-13 to its verified identity per `01_MASTER_INDEX.md` §2: **CLI Engine (Console)**, namespace `SovereignStack\Core\Console`. The approved file's vague "Sovereign CLI Framework" label is replaced with the canonical name. The component is a Symfony-Console-like micro-framework (built in-repo, minimal, zero third-party runtime dependency) that powers CORE-01's `bin/loom` entry point and CORE-20's `forge` toolchain.
> CORE-13 is the **command-line interface engine** for the SovereignStack Core tier. It provides a Symfony-Console-style micro-framework — an `Application` entry point, an abstract `Command` base class, `InputInterface` / `OutputInterface` abstractions, an `InputArgument` / `InputOption` definition model, a `CommandRegistry` for lookup, and a `#[AsCommand]` PHP 8.4 attribute for declarative auto-registration via the CORE-02 DI Container. The component exists because the SovereignStack has **two** first-party CLI entry points that share the same needs: CORE-01 ships `bin/loom` (the polyrepo orchestrator) and CORE-20 will ship `bin/forge` (the developer toolchain). Both need argument parsing, `--help` generation, exit-code propagation, and structured command discovery. Duplicating that machinery across two packages would produce format drift; pulling in `symfony/console` would import ~30 transitive classes and a templating subsystem the SovereignStack does not otherwise use. CORE-13 is the minimal, in-repo alternative.
> - **Downward:** CORE-01 (Polyrepo Orchestrator) — the `bin/loom` executable constructs an `Application`, registers `loom:release`, `loom:bump`, `loom:ci:wait`, etc., and calls `$application->run($argv)`. CORE-20 (Sovereign Forge) — the `bin/forge` executable uses `autoRegister()` to discover every `Forge\Command\*` class marked `#[AsCommand]`. CORE-17 (Service Providers) — a `ConsoleServiceProvider` will register the `Application` instance as a singleton in CORE-02 and bind `InputInterface` / `OutputInterface` to the per-request instances. Future Hub-tier management commands (e.g., HUB-01 `flags:list`, HUB-02 `cache:flush`, HUB-06 `audit:replay`) will extend `SovereignStack\Core\Console\Command` once their packages land.
> participant Bin as bin/loom<br/>(PHP entrypoint)
> Shell->>Bin: exec bin/loom release --env=prod
> **Downward (consumed by):** The `bin/loom` entry point (CORE-01) constructs an `Application`, registers `loom:release`, `loom:bump:minor|major|patch`, `loom:ci:wait`, `loom:dep:graph`, `loom:tag` as concrete `Command` subclasses, and calls `$application->run($_SERVER['argv'])`. CORE-20 (Forge) does the same with `forge:make:hub`, `forge:make:spoke`, `forge:make:bridge`, `forge:test:bootstrap`, but uses `autoRegister(__DIR__ . '/../src/Command', 'SovereignStack\\Forge\\Command')` so new commands are picked up by adding a class with the `#[AsCommand]` attribute — no bootstrap edits. A future `ConsoleServiceProvider` (CORE-17) will bind `Application::class` as a singleton in CORE-02 and bind `InputInterface` / `OutputInterface` to per-request instances so non-CLI entry points can resolve the same Application via the container.
> **Typical bin/loom bootstrap (illustrative):**
> $application = new Application(name: 'loom', version: '1.0.0');
> | Dispatch overhead per trivial command (echo + return 0) | Harness: PHPUnit `--group performance` invoking `Application::run(['loom', 'noop'])` 1 000 times in a tight loop, `microtime(true)` wall-clock before/after, mean computed. Baseline: GitHub Actions `ubuntu-latest`, PHP 8.4.3, opcache enabled, no Xdebug, `ini_set('opcache.enable_cli', '1')`. Load model: single process, no concurrency, 1 trivial command, no I/O. Assert mean dispatch overhead ≤ 0.5 ms — **provisional, unverified** until first measurement lands in CI. |
> | Boot-to-exit latency for `bin/loom --version` | Harness: 1 000 invocations of `php bin/loom --version` as a subprocess via `proc_open`, wall-clock via `microtime(true)`. Baseline: GitHub Actions `ubuntu-latest`, PHP 8.4.3, opcache enabled. Load model: cold PHP process per invocation (no persistent interpreter). Assert mean ≤ 50 ms — **provisional, unverified**. |
> - **Help text test:** `Application::run(['loom', '--help'])` produces output containing the string `"Usage:"` and the names of all registered non-hidden commands. `Application::run(['loom', 'release', '--help'])` produces output containing `"Arguments:"` and `"Options:"` reflecting the command's declared signature.
> - **Unknown-command suggestion test:** invoking `Application::run(['loom', 'relase'])` (typo of `release`) writes a "Did you mean one of these?" suggestion containing `release` to stderr and returns exit code 1.
> 5. Refactor `bin/loom` to construct an `Application` and register its commands. Backwards-compatible: existing CLI behaviour is preserved; the new engine adds `--help` and `list` for free.
> **Rollback procedure:** Remove `packages/core/console/` from the vendor tree. Revert `bin/loom` and `bin/forge` to raw PHP scripts that parse `$_SERVER['argv']` directly. CORE-01 and CORE-20 fall back to the previous behaviour: no `--help`, no `list`, no auto-registration. The orchestrator's existing commands continue to work because their `execute()` logic is independent of the engine. No data migration is required (CORE-13 is stateless — no database, no files written outside the test fixtures).
> **Minor** (initial release: 1.0.0). The package is new — it adds a CLI engine to the Core tier that did not previously exist as a reusable component. It does not break any existing public API (CORE-01's `bin/loom` continues to work; the engine is additive). The first stable release is `1.0.0` once CI passes: 100% branch coverage on `Application::run()` and `CommandRegistry::lookup()`, phpstan level 8 clean, the full argument-parsing matrix green, and the no-shell-exec security test green. Subsequent minor releases may add: an interactive `Question` helper (CORE-13.1), a `Table` renderer (CORE-13.2), a `ProgressBar` (CORE-13.3) — all behind new `OutputInterface` extensions and never breaking the core contracts.

### Blueprint side (CORE-01)

CORE-01 (Polyrepo Orchestrator / Loom) describes the orchestrator but does not establish that `bin/loom` builds a Console Application.


### Code side

Actual `orchestrator/bin/loom` exists: YES

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The Loom — CORE-01 Polyrepo Orchestrator CLI
 *
 * Central entry point for orchestrating the Sovereign Stack polyrepo lifecycle.
 */

// Determine autoload path — support both installed and development modes
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

$autoloaded = false;
foreach ($autoloadPaths as $path) {
    if (\file_exists($path)) {
        require $path;
        $autol
```

### Architectural consequences

- If CORE-13 is correct, Loom should be a Console command within the CLI tier.
- If CORE-01 is correct, Loom is an orchestrator that may not be a CLI command.
- The placement of Loom in the CLI tier depends on resolving this conflict.

### Possible resolutions (NOT chosen)

1. Loom is both an orchestrator AND a CLI command (orchestrator registers as a Console command).
2. Loom is an orchestrator only; `bin/loom` is a CLI wrapper that invokes the orchestrator.
3. CORE-13's claim about `bin/loom` is aspirational, not yet implemented.


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


## CONFLICT-003: CORE-15 (Cache) — Missing implementation

**Status:** RECORDED — NOT RESOLVED

CORE-15 (Cache Abstraction, PSR-6/16) has a blueprint but no implementation directory.
No `Core/Cache/` directory exists in DGLab.


## CONFLICT-004: CORE-17 (Service Providers) — Stub only

**Status:** RECORDED — NOT RESOLVED

CORE-17 (Service Provider System) has a blueprint but is implemented only as a stub:
- `Core/Kernel/Stub/ProviderRegistryInterface.php` — stub interface
- `Core/Kernel/Stub/EmptyProviderRegistry.php` — empty implementation
The stub ships inside the Kernel package to unblock CORE-18.


## CONFLICT-005: CORE-20 (Forge) — Missing implementation

**Status:** RECORDED — NOT RESOLVED

CORE-20 (Developer CLI Toolchain / Sovereign Forge) has a blueprint but no implementation directory.


## CONFLICT-006: Twig dependency in Core/View

**Status:** RECORDED — NOT RESOLVED

ADR-S02 (approved and frozen) removes Twig from SStack.
DGLab's `Core/View/` depends on Twig (`twig/twig: ^3.30`).
SStack reconstruction must replace Core/View with native rendering (NeoSPX) per ADR-S02.
NeoSPX implementation is deferred until HTTP Kernel acceptance per ADR-S02 §6.3.

