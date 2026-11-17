# Executive Summary — TOML 1.1.0 Research and CORE-10 Architecture Assessment

**Document type:** Deliverable 15 of 15 (Executive Summary)
**Directive:** SSTACK-CCR-002
**Inspection date:** 2026-11-17
**Execution mode:** READ-ONLY research (no implementation, no commits, no spec modifications)

---

## A. Overall Findings and Recommended Direction

### A.1 Bottom line

The owner should approve:
1. **Upgrading CORE-10 from TOML 1.0.0 to TOML 1.1.0** — strictly additive, low implementation cost, four substantive language changes plus six clarifying amendments.
2. **Recording OD-010, OD-011, OD-012** in the owner-decisions document — these are referenced by the directive but not yet recorded.
3. **The native parser architecture proposed in `sovereignstack-native-parser-architecture.md`** — a clean lexer/parser/TableBuilder separation with datetime value objects, resource limits, and a typed error code enum.
4. **Vendoring the official `toml-test` conformance suite** (~515 KB, MIT-licensed) into SStack's test directory.
5. **Pushing the locally-committed fixes `b312a95` + `1136065` to SStack PR #3** as an interim measure (these fix the 34 PHPStan errors and 3 TOML parser bugs in PR #3, but the full 1.1.0 migration is a separate larger effort).

### A.2 The actual SStack PR #3 is non-compliant with spec

This is the most significant finding of the research. The actual SStack PR #3 at `c377cb4` has:
- 34 PHPStan errors at the spec-required config (`level: max` + `bleedingEdge`).
- 3 real TOML parser defects (signed integers lose sign; invalid datetime throws wrong exception; nested array-of-tables creates duplicate parent entries).

The locally-committed fixes (`b312a95` + `1136065`) exist in the outer DGLab repo's main branch and have NOT been pushed to SStack PR #3. The evidence package at `/home/z/my-project/download/core10-evidence/` describes the locally-modified state, not the actual PR #3 state.

**The owner must decide whether to accept the local fixes and push them, or require a fresh remediation.**

### A.3 None of the three PHP implementations should be adopted

| Repo | Verdict | Reason |
|---|---|---|
| Devium | REJECT | Runtime deps `php-ds/php-ds` + `symfony/polyfill-mbstring` violate SStack policy. 1.1.0 claim is marketing — 4 of 5 features missing. Latent bug in `addTableNode` line 137. |
| php-collective | REJECT | Runtime dep `symfony/polyfill-mbstring` violates SStack policy (cosmetic but binding). AST is mutable (incompatible with SStack discipline). |
| PetalBranch | REJECT | Policy-compliant deps (only `ext-json` + `ext-mbstring`) but: no CI, 8 months inactive, false "zero-copy" claim (REFUTED by source), datetime round-trip bug. |

**SStack must retain its own native implementation.** All three repos provide borrowable architectural ideas (with attribution), not adoptable code.

### A.4 The most borrowable ideas come from php-collective

| Idea | Source | Utility |
|---|---|---|
| `ParseError` struct (message + span + hint + code) | php-collective | HIGH — clean error model for SStack |
| `ParseErrorCode` enum (16 stable codes) | php-collective | HIGH — typed error code namespace |
| Local datetime preserved as string (timezone-neutral) | php-collective | HIGH — confirms the right approach |
| Contextual hints (YAML `:` vs `=`, etc.) | php-collective | HIGH — improves error actionability |
| `\e` and `\xHH` escape implementations | PetalBranch | HIGH — reference for 1.1.0 escape extensions |
| PHPStan Level 9 discipline | PetalBranch | HIGH — confirms level-max is achievable |
| Correct naive `DateTimeImmutable` for local datetime | Devium | MEDIUM — confirms the alternative approach |

---

## B. TOML 1.0.0 vs 1.1.0 — Key Differences

TOML 1.1.0 was released 2025-12-18. The four substantive language changes:

| # | Change | Complexity | Adopt? |
|---|---|---|---|
| 1 | Multi-line inline tables + trailing comma (#904) | Medium | YES |
| 2 | `\xHH` escape for codepoints ≤ U+00FF (#796) | Low | YES |
| 3 | `\e` escape for ESC character U+001B (#790) | Low | YES |
| 4 | Optional seconds in time/datetime (#894) | Low | YES |

The six clarifying amendments:
- Comments never affect parser output (#950) — no parser change required.
- UTF-8 well-formedness rejection preferred (#929) — security improvement, low complexity.
- Dotted key table creation clarified (#859) — verify existing behaviour.
- Multi-line literal `\r` preservation (#842) — verify existing behaviour.
- Sub-millisecond precision allowed (#805) — verify existing behaviour.
- Parsers free to support any int/float size (#1058) — add explicit range check.

**Backward compatibility:** Strictly additive. No existing valid 1.0.0 document becomes invalid or changes meaning under 1.1.0.

Full comparison: `toml-version-comparison.md`.

---

## C. Repository Findings (Summary)

### C.1 Devium (https://github.com/vanodevium/toml)

- **Marketing vs reality:** Claims "compatible with 1.0.0 and 1.1.0" but `run-toml-test.sh` invokes `toml-test -toml=1.0` — only tests against 1.0.0.
- **Missing 1.1.0 features:** trailing commas in inline tables, multi-line inline tables, `\e` escape, non-ASCII bare keys.
- **Latent bug:** `addTableNode` line 137 has the same prefix-conflict pattern fixed in `addArrayTableNode` on 2026-11-16.
- **Dependencies:** `php-ds/php-ds` + `symfony/polyfill-mbstring` — VIOLATE SStack policy.
- **Datetime handling:** Correctly naive `DateTimeImmutable` (no timezone coercion).
- **Test coverage:** 11 Pest cases only; PHPStan at level 0.

Full analysis: `devium-analysis.md` (1,118 lines).

### C.2 php-collective (https://github.com/php-collective/toml)

- **Headline feature:** "AST access and collected parse errors" — both verified as real.
- **AST architecture:** 13 mutable node classes + 5 enums; "Original X" pattern carries both live value and original source for re-encoding. AST is fully mutable (incompatible with SStack immutability discipline).
- **Error model:** `ParseError` readonly struct + `ParseErrorCode` enum (16 stable codes) + `ParseResult {Document?, errors[], value?}`. Two-strategy recovery: `synchronize()` + `synchronizeInCollection()`. Excellent contextual hints.
- **Datetime:** Local datetime/date/time preserved as **strings** — correctly timezone-neutral.
- **Pipeline:** Generator-based lexer BUT parser materialises via `iterator_to_array` at `Parser.php:81` — effectively NOT streaming.
- **Maturity:** 8 months old, single maintainer (Mark Scherer), 0.1.x, 50 commits.
- **Dependencies:** Only `symfony/polyfill-mbstring` (policy violation but cosmetic).

Full analysis: `php-collective-analysis.md` (1,269 lines, ~78 KB).

### C.3 PetalBranch (https://github.com/PetalBranch/toml)

- **"Zero-copy" claim — REFUTED:** Lexer builds every string via `$lexeme .= $char` per character (`Lexer.php:454,500,666,790` — 12 call sites). `Token::$lexeme` is a value `string` property, not a slice reference. 21 `substr()` calls. Zero uses of `SplFixedArray`, `StringRef`, etc.
- **"O(1) memory" claim — REFUTED:** `LazyTokenStream::$buffer` accumulates ALL tokens monotonically. Mathematically impossible for any parser that returns a value.
- **TOML 1.1.0 support — VERIFIED:** `\e` escape, `\xHH` escape, inline-table newlines, inline-table dotted keys all present in source.
- **Round-trip bug:** Parser stores datetime values as strings (`Parser.php:663-669` discards typed objects), while Dumper requires typed objects — parse→dump of datetime emits a quoted string.
- **Maturity:** PHPStan Level 9 (real); no CI; only 7 commits from 1 contributor in a 3-day burst; 8 months inactive; only 5 PHPUnit tests.
- **Dependencies:** `ext-json` + `ext-mbstring` only — POLICY COMPLIANT.

Full analysis: `petalbranch-analysis.md` (1,032 lines, 72 KB).

---

## D. Architecture Comparison (Summary)

| Property | SStack (current PR #3) | Devium | php-collective | PetalBranch |
|---|---|---|---|---|
| Lexer separation | No (inline) | Yes | Yes (generator) | Yes |
| Token model | Implicit | Yes (`Ds\Vector`) | Yes (with span) | Yes (`Token` with `string $lexeme`) |
| AST | No (builds PHP array) | Yes | Yes (13 classes) | Yes |
| Source position | Line only | Line + column | Line + column + byte | Line + column |
| Error recovery | None | None | **Two-strategy** | None |
| Streaming | No | No | Generator but materialised | No |
| Datetime for local | `DateTimeImmutable` (timezone-coerced — BUG) | `DateTimeImmutable` naive (correct) | String (correct) | String (lost type — round-trip bug) |

Full comparison: `lexer-parser-architecture-comparison.md`.

---

## E. Conformance and Testing

- **Official `toml-test` suite:** 535 valid + 525 invalid = 1,060 test cases. Maintained at https://github.com/toml-lang/toml-test (Go-based runner, MIT-licensed).
- **1.1.0-specific tests:** `tests/valid/spec-1.1.0/` (paired .toml + .json) + `tests/invalid/spec-1.1.0/` (8 invalid cases).
- **Current SStack coverage:** 99 acceptance tests cover ~10% of the official suite.
- **Recommended:** Vendor the 1,060 test fixtures into SStack's `tests/Toml/Conformance/` directory with MIT attribution. Write a single `ConformanceTest.php` PHPUnit class that runs all 1,060 cases. Pin to a specific `toml-test` release (e.g. v2.2.0).

Full analysis: `toml-conformance-analysis.md`.

---

## F. Performance and Security

### F.1 Performance

No fresh benchmark numbers are reported (would require installing third-party TOML libraries, which falls outside read-only research scope). Architectural analysis confirms:

- SStack's current single-pass architecture is the most memory-efficient.
- A refactored lexer+parser architecture will use more memory (token + AST allocation) but improve diagnostics.
- Performance cost is negligible for SStack's typical config-file sizes (KB range).
- PetalBranch's "zero-copy" and "O(1) memory" claims are **false**.

Recommended non-binding parse-time targets: < 1ms for 1 KB input; < 50ms for 100 KB; < 500ms for 1 MB.

Full analysis: `performance-analysis.md`.

### F.2 Security

13 attack vectors identified. The current SStack parser mitigates 4 of 13. The recommended refactored parser mitigates all 13 via:

- Input size limit: 1 MB
- Nesting depth limit: 100 levels
- Array element limit: 10,000 per array
- Table key limit: 10,000 per table
- Key length limit: 1,024 chars
- String length limit: 64 KB
- UTF-8 well-formedness rejection
- Integer range check (PHP_INT_MIN..PHP_INT_MAX)
- No-backtracking lexer
- Failure atomicity (preserved)
- Error-message sanitisation (preserved)
- Explicit table-conflict check (new)

All limits integrate cleanly with the existing failure-atomicity contract.

Full analysis: `security-analysis.md`.

---

## G. SovereignStack Native Parser Architecture (Summary)

The proposed architecture (`sovereignstack-native-parser-architecture.md`):

- **Separate lexer from parser** (SStack currently has them inline).
- **Token carries source span** (line + column + byte offset).
- **No public AST** (SStack does not need to expose AST).
- **Typed `TomlParseErrorCode` enum** (inspired by php-collective, with attribution).
- **Value objects for datetimes** (`LocalDate`, `LocalTime`, `LocalDateTime`, `OffsetDateTime`) — fixes the silent timezone-coercion bug.
- **No error recovery in v1** (single-error throw; recovery deferred).
- **Resource limits enforced** (12 protections per security analysis).

The public `TomlParser::parse()` API is **unchanged** — the refactoring is internal. The only public-API addition is the four datetime value objects under `SovereignStack\Core\Config\Toml\Value\`.

This is a **breaking change** for callers using `DateTimeImmutable` from `ConfigRepository::get()` for datetime keys. The value objects provide `toDateTimeImmutable()` for callers who need the PHP type.

Full proposal: `sovereignstack-native-parser-architecture.md`.

---

## H. CORE-10 Migration Proposal (Summary)

The proposed spec migration (`core-10-toml-1.1-migration-proposal.md`) affects 11 of the 19 spec sections:

- §1 — Update B1 to reference TOML 1.1.0; add OD-010/011/012.
- §3 — Update TOML parser scope row.
- §4 — REWRITTEN for TOML 1.1.0; remove mixed-type array contradiction; add resource limits; add datetime value objects; add conformance testing.
- §8 — Add OD-010 secret-origin restrictions.
- §10 — Mark `Environment::detect()` as `@internal` per OD-011.
- §12 — Add `TomlParseErrorCode` references.
- §15 — Update datetime handling note.
- §16 — Add conformance suite; update test count to 1,159.
- §17 — Resolve N1, N2, N3; add N5 (error recovery question).
- §18 — Update item 4 (tests) + item 5 (PHPStan).
- §19 — Add TOML 1.1.0 conformance row.

**Breaking changes:** datetime value objects, `@internal` `detect()`, OD-010 secret-origin restrictions, resource limits. All have documented mitigations.

**Effort estimate:** ~3-4 weeks of focused implementation (after spec migration approved).

Full proposal: `core-10-toml-1.1-migration-proposal.md`.

---

## I. Outstanding Owner Decisions

| # | Decision | Why required |
|---|---|---|
| 1 | Approve TOML 1.0.0 → 1.1.0 upgrade | All downstream work depends on this |
| 2 | Record OD-010 (secret-origin), OD-011 (internal detect), OD-012 (mixed-type arrays) in owner-decisions document | Referenced by directive but NOT YET recorded |
| 3 | Approve native parser architecture proposal | Implementation cannot start without this |
| 4 | Approve datetime value-object representation (breaking change) | Affects public API |
| 5 | Approve resource limits (1 MB, 100 depth, etc.) | Security binding |
| 6 | Approve vendoring toml-test fixtures (~515 KB) | Adds files to SStack repo |
| 7 | Approve migration impact assessment | Acceptance of breaking changes |
| 8 | Decide on locally-committed fixes (`b312a95` + `1136065`) | Accept and push to PR #3, OR require fresh remediation |
| 9 | Confirm or refine K-S0b figures (orchestrator 8 / CLI 2) | Minor provenance clarification |
| 10 | ADR-S01 / ADR-S02 document creation | Authorised by directive but sequenced AFTER TOML research (which is now complete) |

---

## J. Evidence Register

All inspected sources are catalogued in `evidence-and-source-register.md` with:
- Repository URL + commit SHA + tag
- File path + line numbers
- Inspection method (read / executed / API call)
- Evidence classification (SOURCE-VERIFIED / DOCUMENT-VERIFIED / TEST-VERIFIED / AGENT-REPORTED / INFERRED / UNVERIFIED)

15 deliverable files produced at `/home/z/my-project/research/toml-1.1/deliverables/`:

1. `toml-version-comparison.md`
2. `official-specification-analysis.md`
3. `devium-analysis.md` (1,118 lines)
4. `php-collective-analysis.md` (1,269 lines)
5. `petalbranch-analysis.md` (1,032 lines)
6. `repository-comparison.md`
7. `lexer-parser-architecture-comparison.md`
8. `toml-conformance-analysis.md`
9. `performance-analysis.md`
10. `security-analysis.md`
11. `dependency-and-licensing-analysis.md`
12. `sovereignstack-native-parser-architecture.md`
13. `core-10-toml-1.1-migration-proposal.md`
14. `evidence-and-source-register.md`
15. `executive-summary.md` (this document)

---

## K. Implementation Readiness Assessment

**Question per directive §15.K:** State whether the architecture and specification are sufficiently detailed for implementation.

**Answer:** YES, conditional on owner approval of items 1–7 in section I above.

The architecture proposal (`sovereignstack-native-parser-architecture.md`) provides:
- Complete component diagram.
- Class responsibilities and public/internal contracts.
- Data-flow diagram.
- Error-flow diagram.
- Detailed parsing algorithm (lexer + parser + table-builder).
- Datetime representation design.
- Resource-limit strategy.
- Implementation order (15 steps).

The migration proposal (`core-10-toml-1.1-migration-proposal.md`) provides:
- Every affected spec section identified.
- Specific text changes per section.
- Migration impact assessment.
- Risk assessment with mitigations.
- Effort estimate.

A development team can implement the resulting native parser without inventing missing contracts or algorithms, **provided** the owner:
1. Approves the architecture proposal.
2. Approves the migration proposal.
3. Records OD-010/011/012 in the owner-decisions document.
4. Authorises implementation (separate from this research authorisation).

**Implementation has NOT been started.** This research is READ-ONLY. No source code was modified, no commits were created, no branches were pushed, no specifications were modified.

---

## Stop Condition

Per directive §16, this investigation is complete. The research package is presented for owner review.

**Awaiting owner review and explicit next instruction.**

No implementation work will begin. No binding specifications will be modified. No commits will be created. No PRs will be opened (other than the previously-pushed PR #4 for the context restoration report, which is a separate documentation action).

The owner retains final architectural authority.
