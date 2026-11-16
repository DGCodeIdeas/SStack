# SovereignStack Owner Decision Register

**Document:** `docs/architecture/owner-decisions-2026-11.md`
**Status:** Approved by owner
**Decision date:** 2026-11-15

## Purpose

Records architecture decisions made by the SovereignStack owner while reconciling the DGLab reconstruction findings (K-S0b). These resolve the five outstanding conflicts identified during K-S0b analysis. They supplement, but do not replace or modify, the frozen ADR-S01 and ADR-S02.

## Owner Decisions

### OD-001 — Loom Architecture
**Resolves:** CONFLICT-001

Both independent and Console-integrated entry points are first-class:
- `bin/loom` provides an independent Loom entry point.
- Console Application may expose Loom functionality through commands.
- Both entry points delegate to shared Loom orchestration services.
- Loom orchestration must not depend on the Console Application or CLI tier.
- Integration must preserve the tier boundaries established in ADR-S01.

Exact classes, interfaces, and bootstrap behaviour require further source inspection and detailed design.

### OD-002 — HTTP Directory and Namespace Structure
**Resolves:** CONFLICT-002

- HTTP message implementations: `Core/Http/`, namespace `SovereignStack\Core\Http`
- Middleware implementations: `Core/Middleware/`, namespace `SovereignStack\Core\Middleware`
- PSR-4 mappings must reflect this structure.
- Individual class placement based on verified existing responsibilities.

### OD-003 — Native Cache Component
**Resolves:** CONFLICT-003

Implement Cache as a native SovereignStack Core capability. No third-party cache implementation libraries. Scope, interfaces, storage drivers, expiration, and APIs require detailed design. PSR compatibility adapters must be justified by actual requirement.

### OD-004 — Native Service Provider System
**Resolves:** CONFLICT-004

Implement a complete native Service Provider system in Core. No third-party framework. Provider lifecycle, registration, ordering, validation, and failure handling require detailed design. Existing Kernel stubs must be inspected and deliberately evolved or replaced.

### OD-005 — Native Forge
**Resolves:** CONFLICT-005

Implement Forge as a native SovereignStack development and scaffolding tool within the CLI tier. Separate from the initial Console-only CLI slice. Must respect ADR-S01 tier boundaries. No third-party scaffolding or template engine.

### OD-006 — One Tier at a Time
Reconstruction proceeds sequentially, one tier at a time. A tier must pass its agreed completion and validation criteria before reconstruction advances to the next tier.

### OD-007 — One Component at a Time
Within a tier, components must be reconstructed sequentially. A component must be fully implemented and validated before work begins on the next. No stubs, placeholders, empty methods, or fake implementations. Each component requires: verified source/blueprint requirements, agreed scope, complete implementation, meaningful tests, static analysis, documentation, and completion review.

### OD-009 — Core Before CLI
**Resolves:** CLI vs Core reconstruction order

Core is reconstructed first. CLI is built against completed Core implementations.

Binding reconstruction order:
1. Core — reconstruct and validate all components in dependency order.
2. CLI — build against completed Core implementations.
3. Independent tiers — follow the approved dependency graph.
4. Loom — follow OD-001 and ADR-S01.
5. Integration — validate the complete framework.

This follows the dependency direction established in ADR-S01 (CLI depends on Core).

### OD-008 — Conflict Governance
When source code, blueprints, tests, or samples disagree: record documented requirement, record actual source behaviour, record test evidence, explain discrepancy, present options to owner. Do not resolve without explicit owner approval.

## Decision 008 - CORE-10 Configuration and Environment Loader

Eight sub-decisions approved 2026-11-15 for CORE-10 reconstruction:

1. Architecture: ConfigLoader facade backed by ConfigBuilder and EnvLoader.
2. Merge precedence: Configuration files, then environment variables, then explicit application overrides.
3. Format: Native TOML parser; TOML is the exclusive configuration-file format.
4. Public API: get(), getOrFail(), has(), all(), allRaw(), getEnvironment().
5. Environment: Native backed enum with Development, Staging and Production cases. Unknown values must produce an error.
6. Validation: Native ConfigValidator supporting required keys, expected types, configured constraints and required secret availability.
7. Exceptions: ConfigurationException, MissingConfigurationException, InvalidConfigurationException, InvalidEnvironmentException and ConfigurationFileException under SovereignStack\Core\Config\Exception.
8. ConfigKey attribute: Deferred to CORE-17. CORE-10 must function independently without it.

## Dependency Policy
SStack may use: PHP runtime and justified extensions; PSR contract/interface packages where actually required; SovereignStack-native implementations. SStack must not depend on third-party implementation libraries. Historical DGLab dependencies are evidence, not authorization.

## Implementation Authorization
These decisions establish architectural direction. They do not independently authorize implementation. Before implementation begins: roadmap must be reconciled, component inventory reviewed, detailed scope approved, and owner must explicitly authorize the next stage.

ADR-S01 and ADR-S02 remain frozen and binding.

