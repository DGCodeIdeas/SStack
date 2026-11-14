# Transitive Dependency Analysis

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## Method

Direct Core-internal dependencies were identified by inspecting `use` statements in PHP source files for each component. Transitive dependencies were calculated by traversing the direct dependency graph. External package relationships were verified against the root `composer.json` `require` section at the baseline SHA.

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
| CORE-13 (CLI Engine (Console)) | CORE-02, CORE-10, CORE-19 |
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
| CORE-13 (CLI) | CORE-02, 10, 19 | (none) | 1 | All 3 direct providers (Container, Config, Database) have zero Core dependencies. No additional transitive Core dependency is introduced. Note: these imports are from repository-level scripts (migrate.php, seed.php), not a Console Engine component — see conflicts.md for classification. |
| All other components | (none) | (none) | 0 | No Core dependencies — nothing to traverse. |

### Conclusion

**No transitive Core dependencies exist beyond direct dependencies.** The Core dependency graph has maximum depth 1. Every direct provider is a leaf component with zero Core-internal dependencies.

## External Package Verification

The following external package relationships were verified against actual `composer.json` `require` entries at the baseline SHA:

| Package | composer.json require | Verified interface/package | Components using it |
|---|---|---|---|
| `psr/container: ^2.0` | YES | `Psr\Container\ContainerInterface` (PSR-11) | CORE-02, 03, 05, 18 (via Kernel) |
| `psr/event-dispatcher: ^1.0` | YES | `Psr\EventDispatcher\EventDispatcherInterface`, `ListenerProviderInterface` (PSR-14) | CORE-03, 18 (via Kernel) |
| `psr/http-message: ^2.0` | YES | `Psr\Http\Message\*` interfaces (PSR-7) | CORE-04, 05, 06, 18 (via Kernel) |
| `psr/http-server-handler: ^1.0` | YES | `Psr\Http\Server\RequestHandlerInterface` (PSR-15) | CORE-05 |
| `psr/http-server-middleware: ^1.0` | YES | `Psr\Http\Server\MiddlewareInterface` (PSR-15) | CORE-05 |
| `psr/log: ^3.0` | YES | `Psr\Log\LoggerInterface` (PSR-3) | CORE-03, 08, 09, 16, 19 |
| `doctrine/dbal: ^4.5` | YES | `Doctrine\DBAL\Connection`, `Doctrine\DBAL\DriverManager` | CORE-19, 13 (via migrate.php) |
| `twig/twig: ^3.30` | YES | `Twig\*` classes | CORE-16 (View) — historical dep, ADR-S02 removes from SStack |
| `czproject/git-php: ^4.6` | YES | `CzProject\Git\GitRepository` | CORE-01 (orchestrator) |
| `predis/predis: ^3.7` | YES | `Predis\*` classes | Not imported by any Core component (used by Hub/Identity) |
| `guzzlehttp/guzzle: ^8.3` | YES | `GuzzleHttp\*` classes | Not imported by any Core component |
| `nikic/php-parser: ^5.9` | YES | `PhpParser\*` classes | Not imported by any Core component |
| `php-http/discovery: ^1.20` | YES | `Http\Discovery\*` classes | Not imported by any Core component |

### Verification notes

- `predis/predis`, `guzzlehttp/guzzle`, `nikic/php-parser`, and `php-http/discovery` are in the root `composer.json` `require` but are NOT imported by any Core component. They are dependencies of Hub, Bridge, or Spokes tier components, not Core.
- `twig/twig` is imported only by `Core/View/` (CORE-16). ADR-S02 (approved and frozen) removes Twig from SStack — see conflicts.md CONFLICT-006 (reclassified as historical/migration constraint).
- `doctrine/dbal` is imported by `Core/Database/` (CORE-19) and transitively by `CLI/migrate.php` (repository-level script). This is a forbidden dependency for Stage 1 build waves per K-S0b §7.5.

## Dependency Graph (adjacency list)

```
CORE-13 → CORE-02, CORE-10, CORE-19
CORE-18 → CORE-02, CORE-03, CORE-04, CORE-06, CORE-08, CORE-09, CORE-10
```

All other components have zero outgoing Core-internal edges.

## Cycle Detection

No cycles detected. The Core dependency graph is acyclic. No component depends on itself transitively. Maximum graph depth is 1.

## Execution Provenance

- **Executor:** Z.ai (Kilo was unavailable in the execution environment)
- **Method:** Read-only analysis via `git show origin/main:<path>` against verified baseline SHA
- **External package verification:** Root `composer.json` inspected at baseline SHA
- **No tests executed**
- **No repositories modified**
