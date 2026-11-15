# Dependency-Ordered Component Implementation Sequence

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`
**Date:** 2026-11-15

## Governing Rules

- OD-006: One tier at a time. A tier must pass completion criteria before the next begins.
- OD-007: One component at a time within a tier. No stubs or placeholders.
- ADR-S01: CLI depends on Core. Core depends on PHP/extensions/PSR only.
- ADR-S02: NeoSPX deferred until HTTP Kernel boots and handles requests reliably.
- SStack dependency policy: PSR contracts allowed; third-party implementations forbidden.

## Reconstruction Order (OD-009)

**OD-009 (approved 2026-11-15):** Core is reconstructed first. CLI is built against completed Core implementations.

This follows the dependency direction established in ADR-S01: CLI depends on Core (`CLI | Core` in the permitted dependencies table). CORE-13 (CLI Engine) blueprint specifies that Console commands are registered via the CORE-02 DI Container. Therefore, Core must be complete before CLI reconstruction begins.

The sequence below covers the Core tier first. CLI tier follows after all Core components are complete.

## Core Tier — Implementation Sequence

### Wave 1 — Leaf Components (Zero Core Dependencies, PSR-Only External Deps)

These components have no dependencies on other Core components. Their external dependencies are all PSR contract interfaces. They can be reconstructed in any order within the wave.

| Order | Component | Blueprint | DGLab Source | DGLab Tests | External Deps | Notes |
|---:|---|---|---:|---:|---|---|
| 1 | CORE-10 | Configuration & Environment Loader | 10 files | 67 methods | (none) | Simplest; no external deps at all |
| 2 | CORE-02 | Reactive DI Container | 8 files | 78 methods | psr/container | Foundational; many components depend on it |
| 3 | CORE-09 | PSR-3 Logging Service | 9 files | 79 methods | psr/log | Used by many components |
| 4 | CORE-08 | Global Error & Exception Handler | 5 files | 30 methods | psr/log | |
| 5 | CORE-03 | PSR-14 Event Dispatcher | 7 files | 42 methods | psr/container, psr/event-dispatcher, psr/log | |
| 6 | CORE-04 | PSR-7 HTTP Message & Factory | 14 files | 193 methods | psr/http-factory, psr/http-message | OD-002: rename to `Core/Http/` |
| 7 | CORE-05 | PSR-15 Middleware & Request Handler | 8 files | 35 methods | psr/container, psr/http-message, psr/http-server-handler, psr/http-server-middleware | OD-002: namespace `SovereignStack\Core\Middleware` |
| 8 | CORE-06 | Attribute-Based Router | 13 files | 48 methods | psr/http-message | |
| 9 | CORE-14 | Filesystem Abstraction | 10 files | 25 methods | (none) | |
| 10 | CORE-16 | Binary Encryption Envelope | 8 files | 21 methods | psr/log | |

**Count: 10 components.** Each must be fully reconstructed and validated before the next begins (OD-007).

### Wave 2 — Kernel (Depends on Wave 1)

| Order | Component | Blueprint | DGLab Source | DGLab Tests | Core Dependencies | Notes |
|---:|---|---|---:|---:|---|---|
| 11 | CORE-18 | Core Kernel & Lifecycle | 15 files | 67 methods | CORE-02, 03, 04, 06, 08, 09, 10 | Must wait for all Wave 1 components |

**Count: 1 component.** Kernel depends on 7 Wave 1 components. All must be complete before Kernel reconstruction begins.

### Post-Kernel — Deferred Components

These components are deferred by architectural decisions. They require the HTTP Kernel to be stable (ADR-S02) or require native replacements for forbidden dependencies.

| Component | Reason | Dependency | Notes |
|---|---|---|---|
| CORE-07 | ADR-S02: NeoSPX deferred | HTTP Kernel stable | Lexer |
| CORE-11 | ADR-S02: NeoSPX deferred | CORE-07 complete | Parser |
| CORE-12 | ADR-S02: NeoSPX deferred | CORE-11 complete | Compiler |
| CORE-15 | OD-003: Native cache | Detailed design approved | Missing in DGLab |
| CORE-17 | OD-004: Native service providers | Detailed design approved | Stub in DGLab |
| CORE-19 | Forbidden dep (Doctrine) | Native DB abstraction designed | 13 DGLab source files to reference |
| CORE-01 | OD-001 + forbidden dep (git-php) | Native Git ops + Loom design | Stage 4 |
| CORE-20 | OD-005: Native Forge | CLI tier complete + design | Stage 4+ |

## CLI Tier — After Core Completion

CLI reconstruction begins only after all Core components are complete and validated (OD-009).

| Component | Status | Notes |
|---|---|---|
| CORE-13 | MISSING — must be built from blueprint | Blueprint describes full Console Engine. DGLab has only 2 procedural scripts. Depends on CORE-02 (Container). |
| CORE-20 | MISSING — deferred (OD-005) | Forge; separate from initial Console slice. Depends on CORE-13 complete. |

## Completion Criteria Per Component (OD-007)

Each component must have ALL of the following before being marked complete:

1. Verified source and blueprint requirements (DGLab source inspected, blueprint read)
2. Agreed implementation scope and dependency contracts
3. Complete implementation of approved scope (no stubs, placeholders, or empty methods)
4. Meaningful unit tests covering behaviour and edge cases (not just class existence)
5. Integration tests where applicable
6. Static analysis and coding-standard validation (PHPStan level max)
7. Documentation of public contracts and behaviour
8. Completion review before next component begins

**Safeguard:** A component must not be marked complete merely because its tests pass. Implementation must be checked against original DGLab source, relevant blueprints, and approved architecture.

