# Transitive Dependency Analysis

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## Direct Dependencies (Core internal only)

| Component | Direct Core Dependencies |
|---|---|
| CORE-01 (Polyrepo Orchestrator (Loom)) | (none) |
| CORE-02 (Reactive DI Container) | (none) |
| CORE-03 (PSR-14 Event Dispatcher) | (none) |
| CORE-04 (PSR-7 HTTP Message & Factory) | (none) |
| CORE-05 (PSR-15 Middleware & Request Handler) | (none) |
| CORE-06 (Attribute-Based Router) | (none) |
| CORE-07 (NeoSPX Lexer) | (none) |
| CORE-08 (Global Error & Exception Handler) | (none) |
| CORE-09 (PSR-3 Logging Service) | (none) |
| CORE-10 (Configuration & Environment Loader) | (none) |
| CORE-11 (NeoSPX Parser) | (none) |
| CORE-12 (NeoSPX Compiler) | (none) |
| CORE-13 (CLI Engine (Console)) | CORE-02 (Reactive DI Container), CORE-10 (Configuration & Environment Loader), CORE-19 (Database Abstraction Layer) |
| CORE-14 (Filesystem Abstraction) | (none) |
| CORE-15 (Cache Abstraction (PSR-6/16)) | (none) |
| CORE-16 (Binary Encryption Envelope) | (none) |
| CORE-17 (Service Provider System) | (none) |
| CORE-18 (Core Kernel & Lifecycle) | CORE-02 (Reactive DI Container), CORE-03 (PSR-14 Event Dispatcher), CORE-04 (PSR-7 HTTP Message & Factory), CORE-06 (Attribute-Based Router), CORE-08 (Global Error & Exception Handler), CORE-09 (PSR-3 Logging Service), CORE-10 (Configuration & Environment Loader) |
| CORE-19 (Database Abstraction Layer) | (none) |
| CORE-20 (Developer CLI Toolchain (Forge)) | (none) |

## Transitive Dependencies

| Component | Direct Deps | Transitive Deps | Depth | All Deps (direct + transitive) |
|---|---|---|---:|---|
| CORE-01 (Polyrepo Orchestrator (Loom)) | (none) | (none) | 0 | (none) |
| CORE-02 (Reactive DI Container) | (none) | (none) | 0 | (none) |
| CORE-03 (PSR-14 Event Dispatcher) | (none) | (none) | 0 | (none) |
| CORE-04 (PSR-7 HTTP Message & Factory) | (none) | (none) | 0 | (none) |
| CORE-05 (PSR-15 Middleware & Request Handler) | (none) | (none) | 0 | (none) |
| CORE-06 (Attribute-Based Router) | (none) | (none) | 0 | (none) |
| CORE-07 (NeoSPX Lexer) | (none) | (none) | 0 | (none) |
| CORE-08 (Global Error & Exception Handler) | (none) | (none) | 0 | (none) |
| CORE-09 (PSR-3 Logging Service) | (none) | (none) | 0 | (none) |
| CORE-10 (Configuration & Environment Loader) | (none) | (none) | 0 | (none) |
| CORE-11 (NeoSPX Parser) | (none) | (none) | 0 | (none) |
| CORE-12 (NeoSPX Compiler) | (none) | (none) | 0 | (none) |
| CORE-13 (CLI Engine (Console)) | CORE-02, CORE-10, CORE-19 | (none) | 1 | CORE-02, CORE-10, CORE-19 |
| CORE-14 (Filesystem Abstraction) | (none) | (none) | 0 | (none) |
| CORE-15 (Cache Abstraction (PSR-6/16)) | (none) | (none) | 0 | (none) |
| CORE-16 (Binary Encryption Envelope) | (none) | (none) | 0 | (none) |
| CORE-17 (Service Provider System) | (none) | (none) | 0 | (none) |
| CORE-18 (Core Kernel & Lifecycle) | CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10 | (none) | 1 | CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10 |
| CORE-19 (Database Abstraction Layer) | (none) | (none) | 0 | (none) |
| CORE-20 (Developer CLI Toolchain (Forge)) | (none) | (none) | 0 | (none) |

## Dependency Graph (adjacency list)

  CORE-13 → CORE-02, CORE-10, CORE-19
  CORE-18 → CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10

## Cycle Detection

  No cycles detected. The Core dependency graph is acyclic.
