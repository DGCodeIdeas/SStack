# Transitive Dependency Analysis

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## SStack Dependency Policy

This analysis applies the binding SStack dependency policy:

- **ALLOWED:** PHP runtime and required PHP extensions.
- **ALLOWED:** PSR contract/interface packages (`psr/*`). These provide interfaces only — SStack implements them natively.
- **FORBIDDEN:** Third-party implementation libraries (Twig, Doctrine, Guzzle, AWS SDK, git-php, predis, nikic/php-parser, php-http/discovery). Framework functionality must be implemented natively within SovereignStack.
- **HISTORICAL:** DGLab source imports of forbidden packages. These are recorded as evidence of DGLab's current dependencies but do NOT authorize their introduction into SStack.

## Method

Direct Core-internal dependencies were identified by inspecting `use` statements in PHP source files for each component. Transitive dependencies were calculated by traversing the direct dependency graph. External package relationships were verified against the root `composer.json` `require` section at the baseline SHA.

## Direct Dependencies (Core internal only — component level)

Repository-level script imports (from `CLI/migrate.php` and `CLI/seed.php`) are excluded from this table. Those imports are recorded in `core-dependencies.csv` and classified in `conflicts.md` as script-level usage, not component-level architectural dependencies. See the CLI Script Classification Note in `conflicts.md`.

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
| CORE-13 (CLI Engine (Console)) | (none — not implemented; no Console Engine exists) |
| CORE-14 (Filesystem Abstraction) | (none) |
| CORE-15 (Cache Abstraction (PSR-6/16)) | (none — not implemented) |
| CORE-16 (Binary Encryption Envelope) | (none) |
| CORE-17 (Service Provider System) | (none — stub only) |
| CORE-18 (Core Kernel & Lifecycle) | CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10 |
| CORE-19 (Database Abstraction Layer) | (none) |
| CORE-20 (Developer CLI Toolchain (Forge)) | (none — not implemented) |

## Transitive Dependencies

### Principle

A transitive dependency exists only when a direct provider itself has further Core dependencies. If a direct provider has zero Core dependencies (as verified above), it introduces no additional transitive Core dependency.

### Analysis

| Component | Direct Deps | Transitive Deps (additional) | Depth | Explanation |
|---|---|---|---:|---|
| CORE-18 (Kernel) | CORE-02, 03, 04, 06, 08, 09, 10 | (none) | 1 | All 7 direct providers (Container, EventDispatcher, HttpMessage, Router, ErrorHandler, Logger, Config) have zero Core dependencies. No additional transitive Core dependency is introduced. Kernel's full dependency set equals its direct set. |
| All other components | (none) | (none) | 0 | No Core dependencies — nothing to traverse. |

### Conclusion

**No transitive Core dependencies exist beyond direct dependencies.** The Core dependency graph has maximum depth 1. Every direct provider is a leaf component with zero Core-internal dependencies.

**Only CORE-18 (Kernel) has direct Core-internal dependencies.** This is the sole component that imports sibling Core namespaces at the component level.

## External Package Verification

The root `composer.json` at the baseline SHA contains 17 `require` entries. Of these, 3 are PHP language/extensions (`php`, `ext-mbstring`, `ext-pcre`) and 14 are Composer packages. Each package was verified against actual source imports and classified under the SStack dependency policy.

### ALLOWED — PSR Contract Interface Packages

These packages provide interfaces only. SStack implements them natively. They are approved dependencies for SStack.

| Package | composer.json | Provides (interfaces only) | Used by Core Components |
|---|---|---|---|
| `psr/container: ^2.0` | YES | `Psr\Container\ContainerInterface` (PSR-11) | CORE-02, 03, 05, 18 |
| `psr/event-dispatcher: ^1.0` | YES | `Psr\EventDispatcher\EventDispatcherInterface`, `ListenerProviderInterface`, `StoppableEventInterface` (PSR-14) | CORE-03, 18 |
| `psr/http-factory: ^1.0` | YES | `Psr\Http\Message\ResponseFactoryInterface`, `ServerRequestFactoryInterface`, `StreamFactoryInterface` (PSR-17) | CORE-04 (implements factory interfaces in `ResponseFactory.php`, `ServerRequestFactory.php`, `StreamFactory.php`) |
| `psr/http-message: ^2.0` | YES | `Psr\Http\Message\*` interfaces — `RequestInterface`, `ResponseInterface`, `StreamInterface`, `UriInterface`, etc. (PSR-7) | CORE-04, 05, 06, 18 |
| `psr/http-server-handler: ^1.0` | YES | `Psr\Http\Server\RequestHandlerInterface` (PSR-15) | CORE-05 |
| `psr/http-server-middleware: ^1.0` | YES | `Psr\Http\Server\MiddlewareInterface` (PSR-15) | CORE-05 |
| `psr/log: ^3.0` | YES | `Psr\Log\LoggerInterface`, `LogLevel` (PSR-3) | CORE-03, 08, 09, 16, 19 |

**7 PSR packages — all verified as interface-only contracts.** All are used by Core components. All are ALLOWED under the SStack dependency policy.

### HISTORICAL — Forbidden Third-Party Implementation Libraries

These packages are third-party implementations (not PSR contracts). They are HISTORICAL DGLab dependencies. Their presence in DGLab's `composer.json` does NOT authorize their introduction into SStack. SStack must implement equivalent functionality natively.

| Package | composer.json | Provides (implementations) | Used by Core Components | SStack Action |
|---|---|---|---|---|
| `doctrine/dbal: ^4.5` | YES | `Doctrine\DBAL\Connection`, `Doctrine\DBAL\DriverManager` (database abstraction) | CORE-19 (component-level); CORE-13 (script-level via `CLI/migrate.php`) | SStack must implement database abstraction natively |
| `twig/twig: ^3.30` | YES | `Twig\Environment`, `Twig\Loader\FilesystemLoader` (template engine) | `Core/View/` (`TwigDriver.php`) — no dedicated blueprint in CORE-01 to CORE-20 | Superseded by ADR-S02 NeoSPX native rendering. See CONFLICT-006. |
| `czproject/git-php: ^4.6` | YES | `CzProject\Git\GitRepository` (Git wrapper) | CORE-01 (orchestrator) | SStack must implement Git operations natively or defer Loom |
| `predis/predis: ^3.7` | YES | `Predis\*` classes (Redis client) | NOT imported by any Core component (used by Hub/Identity only) | Not a Core dependency; Hub-tier concern |
| `guzzlehttp/guzzle: ^8.3` | YES | `GuzzleHttp\*` classes (HTTP client) | NOT imported by any Core component | Not a Core dependency |
| `nikic/php-parser: ^5.9` | YES | `PhpParser\*` classes (PHP parser) | NOT imported by any Core component | Not a Core dependency |
| `php-http/discovery: ^1.20` | YES | `Http\Discovery\*` classes (HTTP factory discovery) | NOT imported by any Core component | Not a Core dependency; not a PSR package |

**7 third-party implementation packages — all classified as HISTORICAL/FORBIDDEN.** None are approved for SStack. 3 are imported by Core components (Doctrine, Twig, CzProject). 4 are not imported by any Core component (Predis, Guzzle, nikic/php-parser, php-http/discovery).

### Verification notes

- 14 Composer packages verified total (excluding `php`, `ext-mbstring`, `ext-pcre`).
- 7 are PSR contract interface packages (ALLOWED).
- 7 are third-party implementation packages (HISTORICAL/FORBIDDEN).
- `php-http/discovery` is NOT a PSR package despite being HTTP-related. It is a third-party implementation from the PHP-HTTP project. It is NOT imported by any Core component.
- `predis/predis`, `guzzlehttp/guzzle`, `nikic/php-parser`, and `php-http/discovery` are in DGLab's `composer.json` but are NOT imported by any Core component. They are Hub/Bridge/Spokes tier dependencies.
- `twig/twig` is imported only by `Core/View/` (`TwigDriver.php`). `Core/View/` does not have a dedicated blueprint in CORE-01 to CORE-20. ADR-S02 (approved and frozen) removes Twig from SStack — see `conflicts.md` CONFLICT-006 (classified as historical/migration constraint).
- `doctrine/dbal` is imported by `Core/Database/` (CORE-19, component-level) and by `CLI/migrate.php` (script-level, not a CORE-13 component dependency). This is a FORBIDDEN dependency for SStack.

## Dependency Graph (adjacency list — component level only)

```
CORE-18 → CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10
```

All other components have zero outgoing Core-internal edges at the component level.

## Cycle Detection

No cycles detected. The Core dependency graph is acyclic. No component depends on itself transitively. Maximum graph depth is 1.

## Execution Provenance

- **Executor:** Z.ai (Kilo was unavailable in the execution environment)
- **Method:** Read-only analysis via `git show origin/main:<path>` against verified baseline SHA
- **External package verification:** Root `composer.json` inspected at baseline SHA; PSR package interfaces verified against actual source usage
- **No tests executed**
- **No repositories modified**
