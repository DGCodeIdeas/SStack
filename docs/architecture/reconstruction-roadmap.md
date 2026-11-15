# Reconstruction Roadmap — Reconciled

**Date:** 2026-11-15
**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## Approved Decisions Applied

- ADR-S01 (frozen): CLI tier architecture; CLI and Hub independent
- ADR-S02 (frozen): Native rendering; Twig removed; NeoSPX deferred
- OD-001: Loom architecture (dual entry points)
- OD-002: Http/Middleware directory and namespace structure
- OD-003: Native Cache
- OD-004: Native Service Providers
- OD-005: Native Forge
- OD-006: One tier at a time
- OD-007: One component at a time
- OD-008: Conflict governance

## Reconstruction Stages

| Stage | Description | Status | Authorization |
|---|---|---|---|
| Stage 0 | Inspect DGLab, verify baseline, prepare analysis | COMPLETE (K-S0b) | Approved |
| Stage 1 | Reconstruct Core tier | NOT STARTED | Required |
| Stage 2 | Reconstruct CLI tier | NOT STARTED | Required (after Stage 1) |
| Stage 3 | Reconstruct independent tiers (Hub, Bridge, Spokes) | NOT STARTED | Required (after Stage 2) |
| Stage 4 | Reconstruct Loom as CLI + orchestrator (OD-001) | NOT STARTED | Required (after Stage 3) |
| Stage 5 | Integrate and validate complete framework | NOT STARTED | Required (after Stage 4) |

## Reconstruction Order

**OD-009 (approved 2026-11-15):** Core is reconstructed first. CLI is built against completed Core implementations. This follows the dependency direction established in ADR-S01 (CLI depends on Core).

The binding order is:
1. Core — reconstruct and validate all components in dependency order
2. CLI — build against completed Core implementations
3. Independent tiers — follow the approved dependency graph
4. Loom — follow OD-001 and ADR-S01
5. Integration — validate the complete framework

## Dependency Policy (Binding)

SStack may use:
- PHP runtime and justified PHP extensions
- PSR contract/interface packages where actually required
- SovereignStack-native implementations

SStack must NOT depend on third-party implementation libraries. Historical DGLab dependencies are evidence, not authorization.

## Component Status Summary

| Status | Count | Components |
|---|---:|---|
| Implemented (not verified) | 11 | CORE-02, 03, 04, 05, 06, 08, 09, 10, 14, 16, 18 |
| Deferred (architectural decision) | 5 | CORE-01, 07, 11, 12, 19 |
| Missing | 3 | CORE-13, 15, 20 |
| Stubbed | 1 | CORE-17 |

See `docs/architecture/component-inventory.md` for full details.
See `docs/architecture/implementation-sequence.md` for dependency-ordered sequence.

