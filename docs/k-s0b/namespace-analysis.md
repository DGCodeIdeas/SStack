# Namespace Analysis

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## Approved Namespace Targets (K-S0b §7.7)

| Component | Approved Namespace | Approved Directory |
|---|---|---|
| HTTP Messages | `SovereignStack\Core\Http` | (not specified) |
| Middleware | `SovereignStack\Core\Middleware` | (not specified) |

## Actual DGLab Namespaces and Directories

| Component | Namespace (PSR-4) | Directory | Match? |
|---|---|---|---|
| SovereignStack\Core\Config\ | Core/Config/ | YES |
| SovereignStack\Core\Container\ | Core/Container/ | YES |
| SovereignStack\Core\Crypto\ | Core/Crypto/ | YES |
| SovereignStack\Core\Database\ | Core/Database/ | YES |
| SovereignStack\Core\ErrorHandler\ | Core/ErrorHandler/ | YES |
| SovereignStack\Core\EventDispatcher\ | Core/EventDispatcher/ | YES |
| SovereignStack\Core\Filesystem\ | Core/Filesystem/ | YES |
| SovereignStack\Core\Http\ | Core/HttpMessage/ + Core/Middleware/ | PARTIAL |
| SovereignStack\Core\Kernel\ | Core/Kernel/ | YES |
| SovereignStack\Core\Logger\ | Core/Logger/ | YES |
| SovereignStack\Core\NeoSpx\ | Core/NeoSpx/ | YES |
| SovereignStack\Core\Router\ | Core/Router/ | YES |
| SovereignStack\Core\View\ | Core/View/ | YES |

## Namespace Conflicts

### Http namespace split across two directories

`SovereignStack\Core\Http\` is mapped to TWO directories:
- `Core/HttpMessage/` (25 PHP files)
- `Core/Middleware/` (15 PHP files)

This means classes in `Core/HttpMessage/` and `Core/Middleware/` share the same PSR-4 namespace prefix.
The approved namespace target separates them:
- HTTP Messages: `SovereignStack\Core\Http`
- Middleware: `SovereignStack\Core\Middleware`

SStack reconstruction should separate these into distinct namespace prefixes.

## All Core Namespaces

| Blueprint | Component | Namespace | Directory |
|---|---|---|---|
| CORE-01 | Polyrepo Orchestrator (Loom) | `SovereignStack\Orchestrator\` | `orchestrator/src` |
| CORE-02 | Reactive DI Container | `SovereignStack\Core\Container\` | `Core/Container` |
| CORE-03 | PSR-14 Event Dispatcher | `SovereignStack\Core\EventDispatcher\` | `Core/EventDispatcher` |
| CORE-04 | PSR-7 HTTP Message & Factory | `SovereignStack\Core\HttpMessage\` | `Core/HttpMessage` |
| CORE-05 | PSR-15 Middleware & Request Handler | `SovereignStack\Core\Middleware\` | `Core/Middleware` |
| CORE-06 | Attribute-Based Router | `SovereignStack\Core\Router\` | `Core/Router` |
| CORE-07 | NeoSPX Lexer | `SovereignStack\Core\NeoSpx\` | `Core/NeoSpx` |
| CORE-08 | Global Error & Exception Handler | `SovereignStack\Core\ErrorHandler\` | `Core/ErrorHandler` |
| CORE-09 | PSR-3 Logging Service | `SovereignStack\Core\Logger\` | `Core/Logger` |
| CORE-10 | Configuration & Environment Loader | `SovereignStack\Core\Config\` | `Core/Config` |
| CORE-11 | NeoSPX Parser | `SovereignStack\Core\NeoSpx\` | `Core/NeoSpx` |
| CORE-12 | NeoSPX Compiler | `SovereignStack\Core\NeoSpx\` | `Core/NeoSpx` |
| CORE-13 | CLI Engine (Console) | (no namespace — procedural scripts) | `CLI` |
| CORE-14 | Filesystem Abstraction | `SovereignStack\Core\Filesystem\` | `Core/Filesystem` |
| CORE-15 | Cache Abstraction (PSR-6/16) | (not implemented) | (none) |
| CORE-16 | Binary Encryption Envelope | `SovereignStack\Core\Crypto\` | `Core/Crypto` |
| CORE-17 | Service Provider System | (not implemented) | (none) |
| CORE-18 | Core Kernel & Lifecycle | `SovereignStack\Core\Kernel\` | `Core/Kernel` |
| CORE-19 | Database Abstraction Layer | `SovereignStack\Core\Database\` | `Core/Database` |
| CORE-20 | Developer CLI Toolchain (Forge) | (not implemented) | (none) |
