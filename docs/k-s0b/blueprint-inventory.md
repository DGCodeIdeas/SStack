# Blueprint Inventory — DGLab Core

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

**Total blueprints:** 20

| ID | Title | Component Directory | Dir Exists | PHP Source Files |
|---|---|---|---|---:|
| CORE-01 | Polyrepo Orchestrator ("Loom") | orchestrator/src | YES | 7 |
| CORE-02 | Reactive DI Container | Core/Container | YES | 8 |
| CORE-03 | PSR-14 Event Dispatcher | Core/EventDispatcher | YES | 7 |
| CORE-04 | PSR-7 HTTP Message & Factory | Core/HttpMessage | YES | 14 |
| CORE-05 | PSR-15 Middleware & Request Handler | Core/Middleware | YES | 8 |
| CORE-06 | Attribute-Based Router | Core/Router | YES | 13 |
| CORE-07 | NeoSPX Lexer | Core/NeoSpx | YES | 5 |
| CORE-08 | Global Error & Exception Handler | Core/ErrorHandler | YES | 5 |
| CORE-09 | PSR-3 Logging Service (Structured Logging Engine) | Core/Logger | YES | 9 |
| CORE-10 | Configuration & Environment Loader | Core/Config | YES | 10 |
| CORE-11 | NeoSPX Parser | Core/NeoSpx | YES | 5 |
| CORE-12 | NeoSPX Compiler | Core/NeoSpx | YES | 5 |
| CORE-13 | CLI Engine (Console) | CLI | YES | 2 |
| CORE-14 | Filesystem Abstraction | Core/Filesystem | YES | 10 |
| CORE-15 | Cache Abstraction (PSR-6/16) | (none) | NO | 0 |
| CORE-16 | Binary Encryption Envelope | Core/Crypto | YES | 8 |
| CORE-17 | Service Provider System | (none) | NO | 0 |
| CORE-18 | Core Kernel & Lifecycle | Core/Kernel | YES | 15 |
| CORE-19 | Database Abstraction Layer | Core/Database | YES | 13 |
| CORE-20 | Developer CLI Toolchain ("Sovereign Forge") | (none) | NO | 0 |

## Missing Components

The following blueprints have no implementation directory or empty directories:

- **CORE-15** (Cache Abstraction (PSR-6/16)): No implementation found
- **CORE-17** (Service Provider System): No implementation found
- **CORE-20** (Developer CLI Toolchain ("Sovereign Forge")): No implementation found

## Supplementary Architecture Files

| File | Purpose |
|---|---|
| CORE-BUILD-ORDER.md | Generated build order (derived from verified DAG) |
| CORE-CAPABILITY-DAG.md | Capability delivery DAG |
| CORE-DECLARED-DAG.md | Declared architecture DAG (blueprint Upward/Downward) |
| CORE-VERIFIED-DAG.md | Verified implementation DAG (Composer + source imports) |
