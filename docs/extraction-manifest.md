# SStack Extraction Manifest

## Source baseline

- **Repository:** https://github.com/DGCodeIdeas/DGLab
- **SHA:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`
- **Verified:** 2026-11-13

## Tier inventory (DGLab)

| Tier | Components | PHP files | Lines | Namespace |
|---|---|---|---|---|
| Core/Config | ConfigBuilder, ConfigRepository, EnvLoader | 18 | 1782 | `SovereignStack\Core\Config` |
| Core/Container | Container, ContainerBuilder, PSR-11 | 26 | 2325 | `SovereignStack\Core\Container` |
| Core/Crypto | PasswordHasher, ULID | 12 | 773 | `SovereignStack\Core\Crypto` |
| Core/Database | Connection, DoctrineMigrationRunner | 19 | 2075 | `SovereignStack\Core\Database` |
| Core/ErrorHandler | ErrorHandler, Renderer | 7 | 1103 | `SovereignStack\Core\ErrorHandler` |
| Core/EventDispatcher | Event, Dispatcher, Provider | 14 | 1481 | `SovereignStack\Core\EventDispatcher` |
| Core/Filesystem | Filesystem abstractions | 12 | 979 | `SovereignStack\Core\Filesystem` |
| Core/HttpMessage | PSR-7 messages | 25 | 3955 | `SovereignStack\Core\Http` |
| Core/Kernel | Kernel, Bootstrapper, Events | 21 | 3944 | `SovereignStack\Core\Kernel` |
| Core/Logger | PSR-3 logger | 17 | 2278 | `SovereignStack\Core\Logger` |
| Core/Middleware | PSR-15 middleware | 15 | 1203 | `SovereignStack\Core\Http` |
| Core/NeoSpx | Lexer | 6 | 1430 | `SovereignStack\Core\NeoSpx` |
| Core/Router | Router, RouteCompiler | 20 | 1367 | `SovereignStack\Core\Router` |
| Core/View | Twig rendering | 8 | 444 | `SovereignStack\Core\View` |
| CLI | migrate.php, seed.php | 2 | 336 | (no namespace) |
| orchestrator | Loom | 8 | — | `SovereignStack\Orchestrator` |

## Cross-component dependencies (verified)

Kernel is the hub component that depends on: Config, Container, ErrorHandler, EventDispatcher, Http, Logger, Router.

All other Core components are independent of each other (no cross-component imports).

## External dependencies

- PSR-3 (Log), PSR-7 (HTTP Message), PSR-11 (Container), PSR-14 (EventDispatcher), PSR-15 (HTTP Server Middleware)
- Doctrine DBAL 4.x
- Twig 3.x
- Predis (Redis client — NOT carried into SStack)

## SStack reconstruction dispositions

| DGLab component | SStack status | Approach |
|---|---|---|
| Core/Container | DONE | Clean reconstruction with PSR-11 conformance |
| Core/Config | DONE | Clean reconstruction with dot-notation access |
| Core/Kernel | DONE | Clean reconstruction with lifecycle states |
| Core/EventDispatcher | DONE | PSR-14 clean implementation |
| Core/Router | DONE | Clean route matching with param extraction |
| Core/HttpMessage | DONE | PSR-7 clean implementations |
| Core/Middleware | DONE | PSR-15 pipeline |
| Core/ErrorHandler | DONE | Structured error handling |
| Core/Logger | DONE | PSR-3 structured logger |
| Core/Database | DEFERRED | Stage 3 (depends on DBAL integration testing) |
| Core/Crypto | DEFERRED | Stage 3 |
| Core/Filesystem | DEFERRED | Stage 3 |
| Core/View | DEFERRED | Stage 3 (requires Twig integration) |
| Core/NeoSpx | OMITTED | Experimental; not needed for framework |
| CLI | DONE | Clean CLI with command registration |
| orchestrator → Loom | DEFERRED | Stage 4 (rename + refactor) |

## Omissions (intentional)

- `Products/LMS/Bootstrap.php` — placeholder stub
- `Core/NeoSpx` — experimental lexer
- `app/Application.php` — DGLab-specific application layer
- `Hub/Identity` — will be reconstructed as a standalone module in SStack
- `Bridge/Vanguard` — DGLab-specific boundary contract
- `Spokes/` — business modules deferred to later stages
