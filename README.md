# SStack — SovereignStack Framework

**SovereignStack** is the clean, production-oriented reconstruction of the DGLab framework.

DGLab remains intact as the experimental laboratory and historical archive. SStack will become the authoritative SovereignStack framework implementation.

## Architecture

SStack is organized into explicit architectural tiers:

- **CLI** — Command-line interface, command registration, input/output abstractions
- **Core** — Foundational framework contracts and services
  - Container (PSR-11 DI)
  - Config (environment + file-based configuration)
  - Kernel (lifecycle management)
  - EventDispatcher (PSR-14)
  - Router (PSR-15 compatible)
  - HttpMessage (PSR-7 implementations)
  - Middleware (PSR-15 pipeline)
  - ErrorHandler
  - Logger (PSR-3)
  - Database (DBAL abstraction)
  - Crypto
  - Filesystem
  - View (Twig rendering)

## Namespace

All code uses the `SovereignStack\SStack` namespace.

## Dependencies

- PHP 8.4+
- PSR-3, PSR-7, PSR-11, PSR-14, PSR-15
- Doctrine DBAL 4.x
- Twig 3.x

## License

Apache-2.0

## Status

This is a clean reconstruction, not a fork. DGLab source is referenced for provenance but not copied directly.
