# CORE-10 TOML 1.1.0 Migration Proposal

**Document type:** Deliverable 13 of 15
**Status:** PROPOSED — awaiting owner approval. The binding spec is NOT modified by this document.
**Inspection date:** 2026-11-17

---

## 1. Purpose

This document proposes the changes required to migrate the binding CORE-10 specification (`docs/architecture/core-10-reconstruction-spec.md` at SStack remote commit `d042db17e239567e4b840e73d5add50f355444ce`) from TOML 1.0.0 to TOML 1.1.0.

The proposal also:
- Incorporates the three owner decisions referenced by directive SSTACK-CCR-002 §9.1 (OD-010 secret-origin restrictions, OD-011 internal Environment detection, OD-012 mixed-type array support) — noting that these decisions are referenced by the directive but **not yet recorded** in the owner-decisions document on the SStack remote.
- Identifies every affected spec section.
- Includes a migration impact assessment.

The proposal does NOT modify the actual binding specification. It is submitted for owner review.

---

## 2. Outstanding Owner Decisions Referenced by the Directive

The directive SSTACK-CCR-002 §9.1 references three owner decisions that should be incorporated into the spec migration:

| Decision | Directive description | Current status |
|---|---|---|
| **OD-010** | Secret-origin restrictions | **NOT RECORDED** — `docs/architecture/owner-decisions-2026-11.md` on SStack remote (at `d042db17`) contains OD-001 through OD-009 only. No OD-010 entry exists. The directive treats OD-010 as approved. |
| **OD-011** | Internal Environment detection | **NOT RECORDED** — same situation. The directive description "Internal Environment detection under OD-011" implies that `Environment::detect()` should be `@internal` rather than public. |
| **OD-012** | Full TOML 1.0.0 mixed-type array support | **NOT RECORDED** — same situation. The directive description "Full TOML 1.0.0 mixed-type array support under OD-012" implies that mixed-type arrays are explicitly approved (resolving CCR-003 from the context restoration report). |

**Action required before spec migration can be authorised:**
1. The owner must formally record OD-010, OD-011, OD-012 in `docs/architecture/owner-decisions-2026-11.md`.
2. The spec migration must incorporate these decisions.

This proposal assumes the directive's descriptions are the binding direction for these three decisions. If the owner's actual recorded decisions differ, the proposal must be revised.

---

## 3. Affected Spec Sections

The binding spec at `d042db17` has 19 sections. The migration affects the following:

| Section | Subject | Affected? | Nature of change |
|---|---|---|---|
| §1 | Approved Owner Decisions | YES | Add B1 update note (1.0.0 → 1.1.0); reference OD-010, OD-011, OD-012 |
| §2 | DGLab Source Evidence | NO | Unchanged |
| §3 | Source-vs-Blueprint-vs-Approved Matrix | YES | Update "TOML parser scope" row to "Full TOML 1.1.0 conformance" |
| §4 | TOML 1.0.0 Parser Architecture | YES | **REWRITTEN** — update to TOML 1.1.0; add new escape sequences; add multi-line inline tables; add optional seconds; correct mixed-type array contradiction; add resource limits; reference native architecture proposal |
| §5 | File Discovery, Loading Order | NO | Unchanged |
| §6 | Configuration Merge Semantics | NO | Unchanged |
| §7 | Environment-Variable Naming | NO | Unchanged |
| §8 | Secret Metadata, Permitted Origins, Rejection, Redaction | YES | Add OD-010 secret-origin restrictions |
| §9 | Nested-Key Access, Missing-vs-Null | NO | Unchanged |
| §10 | Environment Detection and Invalid-Environment Handling | YES | Add OD-011 marking `Environment::detect()` as `@internal` |
| §11 | ConfigValidator Schema Contracts | NO | Unchanged |
| §12 | Exception Inheritance, Chaining, Diagnostic Safety | YES | Add new `TomlParseErrorCode` references; clarify `ConfigurationFileException` is the public boundary type |
| §13 | ConfigLoader Orchestration and Failure Atomicity | NO | Unchanged |
| §14 | Deferred Alternative: Multi-Step ConfigLoader API | NO | Unchanged |
| §15 | Configuration Repository Immutability and Secret Access Rules | YES | Update datetime handling note (value objects instead of `DateTimeImmutable`) |
| §16 | Updated Acceptance-Test Matrix | YES | Add conformance suite reference; expand test count to reflect 1.1.0 features |
| §17 | Remaining Open Questions | YES | Remove resolved items (N1, N2, N3 if resolved); add new questions if any |
| §18 | Completion Criteria | YES | Update item 4 (tests) to reference conformance suite; update item 5 (PHPStan) to "level max + bleedingEdge, zero errors, no suppressions" |
| §19 | Evidence Traceability Summary | YES | Add TOML 1.1.0 conformance row |

---

## 4. Specific Changes per Section

### 4.1 Section 1 — Approved Owner Decisions

**Add to the "Blocking Items Resolved (B1-B3)" table:**

> **B1 update (approved 2026-11-17 per directive SSTACK-CCR-002):** TOML version upgraded from 1.0.0 to 1.1.0. Full TOML 1.1.0 support; native implementation; comprehensive conformance tests via vendored `toml-test` fixtures; actionable invalid-syntax errors with source spans.

**Add new sub-section "Additional Owner Decisions (OD-010 – OD-012, approved 2026-11-17)":**

| # | Decision |
|---|---|
| OD-010 | Secret-origin restrictions: secrets may only originate from environment variables (.env file or real environment). Secrets in TOML config files are rejected at validation time. (Resolves CCR-001 from context restoration report.) |
| OD-011 | Internal Environment detection: `Environment::detect()` is marked `@internal`. It is not part of the public API surface. Tests must drive environment detection through `ConfigLoader::load()` rather than calling `Environment::detect()` directly. (Resolves CCR-002.) |
| OD-012 | Full TOML 1.0.0/1.1.0 mixed-type array support: the parser accepts mixed-type arrays per upstream TOML 1.0.0/1.1.0 semantics. The previous spec §4 rejected-conditions entry "Array with mixed types" is removed. (Resolves CCR-003.) |

**NOTE:** These three decisions must be separately recorded in `docs/architecture/owner-decisions-2026-11.md` by the owner. This proposal does NOT modify that document.

### 4.2 Section 3 — Source-vs-Blueprint-vs-Approved Resolution Matrix

**Update the "TOML parser scope" row:**

| Area | DGLab Source | DGLab Blueprint | SStack Approved | Evidence |
|---|---|---|---|---|
| TOML parser scope | N/A | N/A | **Full TOML 1.1.0 conformance** (B1 updated 2026-11-17) + resource limits (security) + value-object datetime representation | Owner decision (SSTACK-CCR-002); `toml-version-comparison.md`; `sovereignstack-native-parser-architecture.md` |

### 4.3 Section 4 — TOML Parser Architecture (REWRITTEN)

The current §4 specifies TOML 1.0.0 conformance. The rewritten section specifies TOML 1.1.0 conformance and references the native architecture proposal.

**Replace the "Approved decision" header:**

> **Approved decision (D3 + B1, updated 2026-11-17):** Full TOML 1.1.0 support. Native SovereignStack implementation. No third-party parser. Conformance verified via the official `toml-test` suite (vendored fixtures, ~1,060 test cases).

**Replace the "Complete syntax coverage" subsection:**

Add coverage for the four 1.1.0 substantive changes:
1. Multi-line inline tables + trailing comma (#904)
2. `\xHH` escape (#796)
3. `\e` escape (#790)
4. Optional seconds in time/datetime (#894)

Plus the clarifying amendments:
5. Comments never affect parser output (#950)
6. UTF-8 well-formedness rejection (#929)
7. Dotted key table creation (#859)
8. Multi-line literal `\r` preservation (#842)
9. Sub-millisecond precision (#805)
10. Integer/float size freedom with explicit range check (#1058)

**Remove the "Array with mixed types" rejected-condition entry** (per OD-012):

The current §4 lists "Array with mixed types (TOML 1.0.0 requires homogeneous arrays)" as a rejected condition. This is INCORRECT per the upstream TOML 1.0.0/1.1.0 spec (mixed-type arrays are permitted). Per OD-012, this entry is removed.

**Add "Resource Limits" subsection:**

> Per `security-analysis.md` §3, the parser enforces:
> - Maximum input size: 1 MB
> - Maximum nesting depth: 100 levels
> - Maximum array elements: 10,000 per array
> - Maximum table keys: 10,000 per table
> - Maximum key length: 1,024 characters
> - Maximum string length: 64 KB
> - UTF-8 well-formedness: reject malformed
> - Integer range: PHP_INT_MIN to PHP_INT_MAX
>
> Violations throw `ConfigurationFileException` with the relevant diagnostic.

**Add "Datetime Value Objects" subsection:**

> Per `sovereignstack-native-parser-architecture.md` §3.7, the parser returns value objects for datetime values:
> - `Toml\Value\LocalDate` — year/month/day, no timezone
> - `Toml\Value\LocalTime` — hour/minute/second/microsecond, no timezone
> - `Toml\Value\LocalDateTime` — composition, no timezone
> - `Toml\Value\OffsetDateTime` — with offset string
>
> Callers needing `DateTimeImmutable` call `toDateTimeImmutable()` (documented timezone-application behaviour).

**Add "Conformance Testing" subsection:**

> Per `toml-conformance-analysis.md`, the parser is verified against the official `toml-test` suite (version pinned). Test fixtures are vendored into `tests/Toml/Conformance/` with MIT-license attribution. A single `ConformanceTest.php` PHPUnit class runs all 1,060 test cases.

### 4.4 Section 8 — Secret Metadata (OD-010)

**Add new subsection "OD-010 — Secret Origin Restrictions":**

> Per owner decision OD-010 (approved 2026-11-17), secrets may originate ONLY from:
> 1. Environment variables (`.env` file or real `$_ENV`)
> 2. Explicit programmatic overrides (typically for testing)
>
> Secrets present in TOML config files are REJECTED at validation time. The `ConfigValidator` checks each `required_secrets` entry against the origin of its value:
> - If the value originated from a TOML config file: throw `InvalidConfigurationException::forSecretOriginViolation($key)`.
> - If the value originated from `$_ENV` or an explicit override: accept.
>
> Origin tracking is performed by `ConfigBuilder`, which records the source of each value during the merge process.

**NOTE:** Origin tracking is a NEW feature not in the current implementation. It requires `ConfigBuilder` to tag each value with its origin (TOML file / env / override). This is a non-trivial implementation change. The proposal flags it as a follow-up to the TOML 1.1.0 migration.

### 4.5 Section 10 — Environment Detection (OD-011)

**Update the Environment enum code block:**

```php
namespace SovereignStack\Core\Config;

enum Environment: string
{
    case Development = 'development';
    case Staging = 'staging';
    case Production = 'production';

    /**
     * @internal Per owner decision OD-011, this method is not part of the
     *           public API surface. Tests must drive environment detection
     *           through ConfigLoader::load().
     */
    internal static function detect(?string $rawValue): self;
}
```

PHP 8.4 supports `internal` visibility on methods (introduced in PHP 8.4 RC). This makes `detect()` callable only from within the `SovereignStack\Core\Config` namespace, NOT from test code or external callers.

**Update the test matrix:**

Remove test #25 ("testGetEnvironmentThrowsForUnrecognisedAppEnv") which calls `Environment::detect('unknown-env')` directly. Replace with a test that drives detection through `ConfigLoader::load(['config.toml'], null, [], null)` where `config.toml` contains `app.env = "unknown-env"`, asserting that `InvalidEnvironmentException` is thrown.

### 4.6 Section 12 — Exception Inheritance

**Add to the "Approved hierarchy" diagram:**

```
SovereignStack\Core\Config\Exception\ConfigurationException (extends \RuntimeException)
├── MissingConfigurationException
├── InvalidConfigurationException
├── InvalidEnvironmentException
└── ConfigurationFileException
```

(Unchanged externally.)

**Add note about internal parse-error type:**

> Internally, the TOML parser uses `Toml\Internal\TomlParseError` (a readonly struct) and `Toml\Internal\TomlParseErrorCode` (a backed enum with stable codes). These are translated to `ConfigurationFileException` at the `TomlParser::parse()` boundary. Third-party/internal exception types do NOT leak through the public API.

### 4.7 Section 15 — Configuration Repository Immutability and Secret Access Rules

**Update the "Immutability" subsection:**

Add note about datetime value objects:

> Datetime values returned by `ConfigRepository::get()` are immutable value objects (`LocalDate`, `LocalTime`, `LocalDateTime`, `OffsetDateTime`). They are constructed once during parse and never mutated. Callers receive the same object reference for the same key within a single repository instance.

### 4.8 Section 16 — Updated Acceptance-Test Matrix

**Add a "Conformance Suite" section:**

> In addition to the 99 acceptance tests below, the parser must pass the vendored `toml-test` conformance suite (~1,060 test cases). See `tests/Toml/Conformance/ConformanceTest.php`.

**Update the test count summary:**

> **Total: 99 acceptance tests (62 source-derived, 37 new SStack) + 1,060 official conformance tests = 1,159 tests.**

(The provenance figures 62/37 are from the actual binding spec at `d042db17`, not the local copy's 54/45.)

### 4.9 Section 17 — Remaining Open Questions

**Resolve N1, N2, N3:**

| # | Original question | Resolution |
|---|---|---|
| N1 | UndefinedInterpolationException retained as distinct or merged? | RESOLVED — merged into `InvalidConfigurationException` per D7 (already in v3 spec). |
| N2 | Environment enum helper methods (displayErrors, enableOpcache)? | RESOLVED per OD-011 — `detect()` is `@internal`; other helpers remain deferred. |
| N3 | SECRET_PATTERN updates for SStack-specific keys? | RESOLVED — pattern preserved verbatim from DGLab source; no SStack-specific additions approved. |

**Add N5 (new):**

| # | Question | Status |
|---|---|---|
| N5 | Should the parser support error recovery (collecting multiple errors per parse)? | Deferred — single-error throw for v1; recovery is a future enhancement. |

### 4.10 Section 18 — Completion Criteria

**Update item 4 (Tests):**

> 4. **Tests:** 99 acceptance tests pass (62 source-derived, 37 new SStack) PLUS 1,060 official `toml-test` conformance tests pass (vendored fixtures).

**Update item 5 (Static analysis):**

> 5. **Static analysis:** PHPStan level max with bleedingEdge.neon — zero errors, no suppressions, no baseline, no rule weakening.

### 4.11 Section 19 — Evidence Traceability Summary

**Add new rows:**

| Requirement | TOML 1.1.0 conformance |
|---|---|
| TOML version | 1.1.0 (upgraded from 1.0.0 per directive SSTACK-CCR-002) |
| Conformance evidence | `toml-test` suite (1,060 cases, vendored) |
| Resource limits | `security-analysis.md` §3 |
| Datetime representation | Value objects (`sovereignstack-native-parser-architecture.md` §3.7) |
| OD-010 (secret origins) | Recorded in `owner-decisions-2026-11.md` (pending) |
| OD-011 (internal detect) | Recorded in `owner-decisions-2026-11.md` (pending) |
| OD-012 (mixed-type arrays) | Recorded in `owner-decisions-2026-11.md` (pending) |

---

## 5. Migration Impact Assessment

### 5.1 What does NOT change

- ConfigLoader facade API (single `load()` method).
- ConfigBuilder, EnvLoader, ConfigRepository, ConfigValidator public APIs.
- Environment enum cases (Development/Staging/Production).
- Exception hierarchy (ConfigurationException + 4 subclasses).
- Approved public Configuration API (6 methods).
- Merge precedence (files → env → overrides).
- Failure atomicity (no partial state on failure).
- PHP 8.4 + ext-json + ext-mbstring requirements.
- Zero third-party runtime dependencies.

### 5.2 What changes

| Change | Impact | Backward-compatible? |
|---|---|---|
| TOML version 1.0.0 → 1.1.0 | Parser accepts more documents (1.1.0 features); no existing valid 1.0.0 doc becomes invalid | YES (strictly additive) |
| Mixed-type arrays accepted | Previous spec said reject; now accept per OD-012 and upstream TOML | YES (more permissive) |
| Datetime return type: `DateTimeImmutable` → value objects | Callers using `->format()` on a get() result for a datetime key will break | **NO — breaking change** |
| `Environment::detect()` becomes `@internal` | Tests calling `Environment::detect()` directly will fail to compile (PHP 8.4 internal visibility) | **NO — breaking change for tests** |
| OD-010 secret-origin restrictions | TOML config files containing secret values are rejected at validation | **NO — breaking change for users who put secrets in TOML** |
| Resource limits enforced | Config files exceeding 1 MB / 100 nesting / 10,000 elements are rejected | **NO — breaking change for users with very large configs** (unlikely in practice) |
| Internal parser refactored into Lexer/Parser/TableBuilder/etc. | No public API change; internal restructure | YES (internal only) |
| `toml-test` conformance suite vendored | ~515 KB added to repo | YES (additive) |
| PHPStan config: level max + bleedingEdge (already required by spec §18.5 but not enforced in PR #3) | 34 errors in current PR #3 must be fixed | NO for the 34 errors (requires test/source fixes) |

### 5.3 Risk assessment

| Risk | Mitigation |
|---|---|
| Breaking change for callers using `DateTimeImmutable` from `get()` | Provide a `toDateTimeImmutable()` method on each value object; document migration path; provide a deprecation period if needed |
| OD-010 rejects secrets in TOML files | Document the requirement clearly; provide a migration tool that scans existing TOML configs for secret keys and reports violations |
| `Environment::detect()` internal visibility breaks tests | Rewrite affected tests to use `ConfigLoader::load()` |
| Resource limits reject very large configs | Limits are generous (1 MB / 100 depth); unlikely to affect real-world configs |
| PHPStan level max + bleedingEdge requires 34 fixes in PR #3 | Fixes already exist in local commits `b312a95` + `1136065` (pending owner decision on whether to push to SStack) |
| TOML 1.1.0 implementation requires new lexer/parser architecture | Native architecture proposal (`sovereignstack-native-parser-architecture.md`) provides complete design; implementation order in §12 |

### 5.4 Effort estimate (PROPOSED, non-binding)

| Phase | Estimated effort | Notes |
|---|---|---|
| Record OD-010/011/012 in owner-decisions document | Small (owner action, not implementation) | Requires owner approval |
| Update spec to v1.1 (this proposal) | Small (1-2 days) | Documentation only |
| Implement native parser per architecture proposal | Medium-large (5-10 days) | New lexer, parser, value objects, table builder |
| Vendor toml-test fixtures + write ConformanceTest | Small (1 day) | Mechanical |
| Pass full conformance suite | Medium (2-5 days) | Debugging failures against 1,060 tests |
| Update existing 99 acceptance tests for new datetime value objects | Small (1 day) | Mechanical |
| Fix 34 PHPStan errors in PR #3 (if accepting local fixes) | Small (already done locally) | Push local commits |
| Implement OD-010 secret-origin tracking in ConfigBuilder | Medium (3-5 days) | New feature |
| Total | ~3-4 weeks of focused implementation | Sequenced per `sovereignstack-native-parser-architecture.md` §12 |

---

## 6. What This Proposal Does NOT Do

- Does NOT modify the binding spec (only proposes changes).
- Does NOT record OD-010/011/012 in the owner-decisions document (only describes what they should say).
- Does NOT authorise implementation (implementation requires separate owner authorisation after spec migration is approved).
- Does NOT push any commits to SStack.
- Does NOT start CLI or Loom reconstruction.
- Does NOT declare CORE-10 complete.

---

## 7. Owner Decisions Required

| Decision | Description |
|---|---|
| 1 | Approve TOML version upgrade from 1.0.0 to 1.1.0. |
| 2 | Approve OD-010 (secret-origin restrictions), OD-011 (internal `Environment::detect()`), OD-012 (mixed-type array support) — and authorise recording them in the owner-decisions document. |
| 3 | Approve the native parser architecture proposal (`sovereignstack-native-parser-architecture.md`). |
| 4 | Approve the datetime value-object representation (breaking change for callers using `DateTimeImmutable`). |
| 5 | Approve the resource limits (1 MB input, 100 depth, 10,000 elements, etc.). |
| 6 | Approve vendoring the `toml-test` fixtures (~515 KB) with MIT attribution. |
| 7 | Approve the migration impact assessment (acceptance of breaking changes). |
| 8 | After approval: authorise implementation per `sovereignstack-native-parser-architecture.md` §12. |

---

## 8. Cross-References

- TOML 1.0.0 → 1.1.0 spec comparison: `toml-version-comparison.md`
- Native architecture proposal: `sovereignstack-native-parser-architecture.md`
- Conformance suite strategy: `toml-conformance-analysis.md`
- Security resource limits: `security-analysis.md`
- Repository analyses: `devium-analysis.md`, `php-collective-analysis.md`, `petalbranch-analysis.md`
- Context restoration report (CCR-001 through CCR-010): `SSTACK-CCR-001-CONTEXT-RESTORATION-REPORT.md` (in `/home/z/my-project/download/`)
