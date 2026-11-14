# Build Waves

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## Scheduling Rules

- A component may only be scheduled in a wave if all its Core dependencies are in earlier waves.
- NeoSPX components (CORE-07, CORE-11, CORE-12) are EXCLUDED from Stage 1 waves.
- Components requiring Twig, Doctrine, AWS SDK, Guzzle, or git-php are noted but not blocked from scheduling.
- If a dependency cycle is found, affected components are stopped.

## Build Waves

### Wave 1

| Component | Name | Core Dependencies | External Dependencies |
|---|---|---|---|
| CORE-01 | Polyrepo Orchestrator (Loom) | (none) | CzProject, psr/http-message |
| CORE-02 | Reactive DI Container | (none) | psr/container |
| CORE-03 | PSR-14 Event Dispatcher | (none) | psr/container, psr/event-dispatcher, psr/log |
| CORE-04 | PSR-7 HTTP Message & Factory | (none) | psr/http-message |
| CORE-05 | PSR-15 Middleware & Request Handler | (none) | psr/container, psr/http-message, psr/http-server-handler, psr/http-server-middleware |
| CORE-06 | Attribute-Based Router | (none) | psr/http-message |
| CORE-08 | Global Error & Exception Handler | (none) | psr/log |
| CORE-09 | PSR-3 Logging Service | (none) | psr/log |
| CORE-10 | Configuration & Environment Loader | (none) | (none) |
| CORE-14 | Filesystem Abstraction | (none) | (none) |
| CORE-15 | Cache Abstraction (PSR-6/16) | (none) | (none) |
| CORE-16 | Binary Encryption Envelope | (none) | psr/log |
| CORE-17 | Service Provider System | (none) | (none) |
| CORE-19 | Database Abstraction Layer | (none) | Doctrine, psr/log |
| CORE-20 | Developer CLI Toolchain (Forge) | (none) | (none) |

### Wave 2

| Component | Name | Core Dependencies | External Dependencies |
|---|---|---|---|
| CORE-13 | CLI Engine (Console) | CORE-02, CORE-10, CORE-19 | Doctrine |
| CORE-18 | Core Kernel & Lifecycle | CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10 | psr/event-dispatcher, psr/http-message |

## NeoSPX Exclusion

CORE-07 (Lexer), CORE-11 (Parser), and CORE-12 (Compiler) are excluded from Stage 1 build waves per K-S0b §7.6.
They are inventoried and analyzed but not scheduled for implementation.

