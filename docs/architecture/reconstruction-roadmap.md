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
| Stage 1 | Reconstruct CLI tier | NOT STARTED | Required |
| Stage 2 | Reconstruct Core tier | NOT STARTED | Required |
| Stage 3 | Reconstruct independent tiers (Hub, Bridge, Spokes) | NOT STARTED | Required (after Stage 2) |
| Stage 4 | Reconstruct Loom as CLI + orchestrator (OD-001) | NOT STARTED | Required (after Stage 3) |
| Stage 5 | Integrate and validate complete framework | NOT STARTED | Required (after Stage 4) |

## Open Item Requiring Owner Decision

### CLI vs Core Reconstruction Order

The roadmap specifies CLI first (Stage 1), then Core (Stage 2). However, ADR-S01 establishes that CLI depends on Core. CORE-13 (Console Engine) blueprint specifies registration via CORE-02 (DI Container).

**Options (per OD-008, not resolved):**
- A: Core first, then CLI (follows dependency direction)
- B: CLI with minimal self-contained Container, refactor later
- C: Parallel reconstruction (violates OD-006)

**Owner must decide before Stage 1 or Stage 2 can be authorized.**

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
