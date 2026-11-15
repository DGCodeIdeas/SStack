# Component Inventory — DGLab Core (K-S0b)

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`
**Date:** 2026-11-15

## Classification Criteria

| Status | Meaning |
|---|---|
| IMPLEMENTED — NOT VERIFIED | Source files exist; not yet checked against blueprint or tested in SStack |
| INCOMPLETE | Some source files exist but component is not fully functional |
| STUBBED | Placeholder interface/implementation only |
| MISSING | No implementation directory or files |
| DEFERRED | Implementation exists but reconstruction deferred by architectural decision |

## Inventory

| ID | Component | DGLab Source Files | DGLab Test Files | DGLab Test Methods | Status | SStack Notes |
|---|---|---:|---:|---:|---|---|
| CORE-01 | Polyrepo Orchestrator (Loom) | 7 | 0 | 0 | DEFERRED | Forbidden dep (CzProject/git-php). OD-001: native implementation required. Deferred to Stage 4. |
| CORE-02 | Reactive DI Container | 8 | 18 | 78 | IMPLEMENTED — NOT VERIFIED | PSR-only deps. Wave 1 candidate. |
| CORE-03 | PSR-14 Event Dispatcher | 7 | 7 | 42 | IMPLEMENTED — NOT VERIFIED | PSR-only deps. Wave 1 candidate. |
| CORE-04 | PSR-7 HTTP Message & Factory | 14 | 11 | 193 | IMPLEMENTED — NOT VERIFIED | PSR-only deps. OD-002: rename to `Core/Http/`. Wave 1 candidate. |
| CORE-05 | PSR-15 Middleware & Request Handler | 8 | 7 | 35 | IMPLEMENTED — NOT VERIFIED | PSR-only deps. OD-002: namespace `SovereignStack\Core\Middleware`. Wave 1 candidate. |
| CORE-06 | Attribute-Based Router | 13 | 7 | 48 | IMPLEMENTED — NOT VERIFIED | PSR-only deps. Wave 1 candidate. |
| CORE-07 | NeoSPX Lexer | 5 | 1 | 27 | DEFERRED | ADR-S02: NeoSPX deferred until HTTP Kernel stable. |
| CORE-08 | Global Error & Exception Handler | 5 | 2 | 30 | IMPLEMENTED — NOT VERIFIED | PSR-only deps. Wave 1 candidate. |
| CORE-09 | PSR-3 Logging Service | 9 | 8 | 79 | IMPLEMENTED — NOT VERIFIED | PSR-only deps. Wave 1 candidate. |
| CORE-10 | Configuration & Environment Loader | 10 | 8 | 67 | IMPLEMENTED — NOT VERIFIED | No external deps. Wave 1 candidate. |
| CORE-11 | NeoSPX Parser | 5 | 1 | 27 | DEFERRED | ADR-S02: NeoSPX deferred. Shares directory with CORE-07/12. |
| CORE-12 | NeoSPX Compiler | 5 | 1 | 27 | DEFERRED | ADR-S02: NeoSPX deferred. Shares directory with CORE-07/11. |
| CORE-13 | CLI Engine (Console) | 2 | 0 | 0 | MISSING | No Console Engine exists. Only 2 procedural scripts (migrate.php, seed.php). Blueprint describes full Console Engine. |
| CORE-14 | Filesystem Abstraction | 10 | 2 | 25 | IMPLEMENTED — NOT VERIFIED | No external deps. Wave 1 candidate. |
| CORE-15 | Cache Abstraction (PSR-6/16) | 0 | 0 | 0 | MISSING | OD-003: native implementation required. |
| CORE-16 | Binary Encryption Envelope | 8 | 4 | 21 | IMPLEMENTED — NOT VERIFIED | PSR-only deps. Wave 1 candidate. |
| CORE-17 | Service Provider System | 2 (stub) | 0 | 0 | STUBBED | OD-004: complete native implementation required. Stub in `Core/Kernel/Stub/`. |
| CORE-18 | Core Kernel & Lifecycle | 15 | 6 | 67 | IMPLEMENTED — NOT VERIFIED | Depends on CORE-02, 03, 04, 06, 08, 09, 10. Wave 2 candidate. |
| CORE-19 | Database Abstraction Layer | 13 | 6 | 66 | DEFERRED | Forbidden dep (Doctrine). Native implementation required. |
| CORE-20 | Developer CLI Toolchain (Forge) | 0 | 0 | 0 | MISSING | OD-005: native CLI-tier implementation required. |

## Summary

| Status | Count | Components |
|---|---:|---|
| IMPLEMENTED — NOT VERIFIED | 10 | CORE-02, 03, 04, 05, 06, 08, 09, 10, 14, 16, 18 |
| DEFERRED (architectural decision) | 5 | CORE-01, 07, 11, 12, 19 |
| MISSING | 3 | CORE-13, 15, 20 |
| STUBBED | 1 | CORE-17 |
| **Total** | **20** | |

Note: CORE-18 is implemented but depends on 7 Wave 1 components. It is listed as IMPLEMENTED — NOT VERIFIED because DGLab source exists, but SStack reconstruction requires Wave 1 components first.

Note: No component is marked "COMPLETE AND VERIFIED." All implemented components require verification against DGLab source, blueprints, and approved architecture before SStack reconstruction. A component must not be marked complete merely because its tests pass (per owner safeguard).
