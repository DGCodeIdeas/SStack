# Evidence and Source Register

**Document type:** Deliverable 14 of 15
**Inspection date:** 2026-11-17

This register records every source inspected during the research, with commit SHA / file path / line numbers where applicable.

---

## 1. TOML Specification Repository

| Item | Path / SHA | Lines | Notes |
|---|---|---|---|
| Repo URL | https://github.com/toml-lang/toml | — | Cloned to `/home/z/my-project/research/toml-1.1/repos/toml-spec/` |
| Default branch | `main` | — | |
| HEAD SHA | `beacf57367482eeb6dcd6e14f6a234b4f1c38bcf` | — | 2026-10-31 "Link to Wikipedia Base64 page…" |
| Tag 1.0.0 SHA | `8eae5e1c005bc5836098505f85a7aa06568999dd` | — | 2021-01-11 "Release v1.0.0" |
| Tag 1.1.0 SHA | `bcbbd1c1f03473ffe97b8bf26a0fc945efe2b4a1` | — | 2025-12-24 "remove redundant time precision requirements" |
| `toml.md` (1.0.0) | `git show 1.0.0:toml.md` | 1,010 lines | Saved to `/home/z/my-project/research/toml-1.1/specs/toml-1.0.0.md` |
| `toml.md` (1.1.0) | `git show 1.1.0:toml.md` | 1,045 lines | Saved to `/home/z/my-project/research/toml-1.1/specs/toml-1.1.0.md` |
| `toml.abnf` (1.0.0) | `git show 1.0.0:toml.abnf` | 243 lines | Saved to `/home/z/my-project/research/toml-1.1/specs/toml-1.0.0.abnf` |
| `toml.abnf` (1.1.0) | `git show 1.1.0:toml.abnf` | 247 lines | Saved to `/home/z/my-project/research/toml-1.1/specs/toml-1.1.0.abnf` |
| `CHANGELOG.md` | `toml-spec/CHANGELOG.md` | 5,770 bytes | Read in full |
| `LICENSE` | `toml-spec/LICENSE` | 1,074 bytes | MIT license |
| `README.md` | `toml-spec/README.md` | 3,054 bytes | Read in full |
| `.pre-commit-config.yaml` | `toml-spec/.pre-commit-config.yaml` | 442 bytes | Read |
| `.prettierrc.toml` | `toml-spec/.prettierrc.toml` | 21 bytes | Read |

---

## 2. TOML Test Suite Repository

| Item | Path / SHA | Lines / Count | Notes |
|---|---|---|---|
| Repo URL | https://github.com/toml-lang/toml-test | — | Cloned to `/home/z/my-project/research/toml-1.1/repos/toml-test/` |
| `README.md` | `toml-test/README.md` | ~50 lines (head) | Read in full |
| `runner.go` | `toml-test/runner.go` | ~50 lines (head) | Read in full; `const DefaultVersion = "1.0.0"` at line 38 |
| `tests/valid/` | directory listing | 535 files | `find tests/valid -type f \| wc -l` |
| `tests/invalid/` | directory listing | 525 files | `find tests/invalid -type f \| wc -l` |
| `tests/valid/spec-1.1.0/` | directory listing | common-N.toml + common-N.json pairs | 1.1.0-specific valid tests |
| `tests/invalid/spec-1.1.0/` | directory listing | 8 .toml files | 1.1.0-specific invalid tests |
| `LICENSE` | `toml-test/LICENSE` | not opened | MIT (per README) |

---

## 3. Devium Repository

| Item | Path / SHA | Lines / Size | Notes |
|---|---|---|---|
| Repo URL | https://github.com/vanodevium/toml | — | Cloned to `/home/z/my-project/research/toml-1.1/repos/devium/` |
| HEAD SHA | `59c82e8a1cb5294063c65baf86ad694106da9070` | — | v1.1.1 (2026-11-16) |
| License | MIT | `LICENSE.md` (1,090 bytes) | Read |
| `composer.json` | `devium/composer.json` | 1,694 bytes | Read in full |
| `README.md` | `devium/README.md` | 6,477 bytes | Read |
| `phpunit.xml` | `devium/phpunit.xml` | 592 bytes | Read |
| `rector.php` | `devium/rector.php` | 419 bytes | Read |
| `run-toml-test.sh` | `devium/run-toml-test.sh` | 566 bytes | Read |
| `src/` tree | full walk | 59 files (whole repo) | Inspected |
| `tests/` tree | full walk | 11 Pest cases | Counted |

**Per the Devium subagent analysis (file `deliverables/devium-analysis.md`, 1,118 lines):**

The subagent read every file in `src/` with line-level citations. Key findings:
- `TomlTokenizer` + `TomlInputIterator` lexer architecture.
- `TomlKeystore` uses `Ds\Set` with `.[N]` path scheme.
- `addTableNode` line 137 has the same prefix-conflict pattern that was fixed in `addArrayTableNode` on 2026-11-16 — latent bug.
- 1.1.0 features MISSING: trailing commas in inline tables, multi-line inline tables, `\e` escape, non-ASCII bare keys.
- Local datetime correctly stays naive (not coerced to `DateTimeImmutable`).

See `deliverables/devium-analysis.md` for the complete evidence register (per-file, per-line).

---

## 4. php-collective Repository

| Item | Path / SHA | Lines / Size | Notes |
|---|---|---|---|
| Repo URL | https://github.com/php-collective/toml | — | Cloned to `/home/z/my-project/research/toml-1.1/repos/php-collective/` |
| HEAD SHA | `9224aa0c80972d8e10315c7493deed2fd4eeae4a` | — | 2026-11-11 "Build the docs on pull requests (#38)" |
| License | MIT | `LICENSE` (1,071 bytes) | Read |
| `composer.json` | `php-collective/composer.json` | 1,527 bytes | Read in full |
| `README.md` | `php-collective/README.md` | 7,478 bytes | Read |
| `CONTRIBUTING.md` | `php-collective/CONTRIBUTING.md` | 3,709 bytes | Read |
| `benchmarks/` | directory listing | present | Read by subagent |
| `bin/` | directory listing | present | Read by subagent |
| `src/` tree | full walk | 190 files (whole repo) | Inspected |
| `.github/workflows/` | directory listing | present | Read by subagent |

**Per the php-collective subagent analysis (file `deliverables/php-collective-analysis.md`, 1,269 lines, ~78 KB):**

The subagent inspected 60+ source/test/doc files (~4,511 LoC in `src/` plus ~3,000 LoC in tests and docs). Key findings:
- 13 mutable AST node classes + 5 enums; "Original X" pattern.
- `ParseError` struct + `ParseErrorCode` enum (16 stable codes) + `ParseResult {Document?, errors[], value?}`.
- Two-strategy error recovery: `synchronize()` + `synchronizeInCollection()`.
- Local datetime/date/time preserved as **strings** (correctly timezone-neutral).
- `Parser.php:81` calls `iterator_to_array()` — pipeline NOT streaming.
- Single maintainer (Mark Scherer); 50 commits; 0.1.x version.

See `deliverables/php-collective-analysis.md` for the complete evidence register.

---

## 5. PetalBranch Repository

| Item | Path / SHA | Lines / Size | Notes |
|---|---|---|---|
| Repo URL | https://github.com/PetalBranch/toml | — | Cloned to `/home/z/my-project/research/toml-1.1/repos/petalbranch/` |
| HEAD SHA | `d8553b064a73ed78e835d5372b2cde66b88837a2` | — | 2026-03-22 "feat(core): 添加全局辅助函数支持" |
| License | Apache-2.0 | `LICENSE.txt` (11,354 bytes) | Read |
| `composer.json` | `petalbranch/composer.json` | 1,501 bytes | Read in full |
| `README.md` (Chinese) | `petalbranch/README.md` | 10,501 bytes | Read |
| `README-EN.md` (English) | `petalbranch/README-EN.md` | 11,646 bytes | Read |
| `phpstan.neon` | `petalbranch/phpstan.neon` | 121 bytes | Read (level 9) |
| `src/` tree | full walk | 10 directories, 64 files (whole repo) | Inspected |
| `tests/` | directory listing | 5 PHPUnit cases | Counted |

**Per the PetalBranch subagent analysis (file `deliverables/petalbranch-analysis.md`, 1,032 lines, 72 KB):**

The subagent inspected all source files with line-level citations. Key findings:
- "Zero-copy" claim **REFUTED**: Lexer builds strings via `$lexeme .= $char` at `Lexer.php:454,500,666,790` (12 call sites).
- "O(1) memory" claim **REFUTED**: mathematically impossible.
- TOML 1.1.0 support **VERIFIED**: `\e` escape at `Lexer.php:549`, `\xHH` escape at `Lexer.php:552`, inline-table newlines at `Parser.php:374`, inline-table dotted keys at `Parser.php:388`.
- Round-trip bug: parser stores datetime as strings (`Parser.php:663-669` discards typed objects).
- No CI (no `.github/workflows/`).
- PHPStan Level 9 (real, `phpstan.neon:2`).
- 7 commits, 1 contributor, 8 months inactive.

See `deliverables/petalbranch-analysis.md` for the complete evidence register.

---

## 6. SStack Repository (for comparison baseline)

| Item | Path / SHA | Lines / Size | Notes |
|---|---|---|---|
| Repo URL | https://github.com/DGCodeIdeas/SStack | — | Fetched as read-only remote `sstack-remote` into `/home/z/my-project/.git` |
| SStack main HEAD | `bce2f4364a31074f19357745d8e695be5e2fec0a` | — | Initial commit |
| SStack PR #3 / `feature/core-10-config` HEAD | `c377cb4bd94d377fa91b5d27b163d2dd338765d7` | — | CORE-10 implementation (6 commits) |
| SStack PR #2 / `docs/k-s0b-core-dependency-analysis` HEAD | `d042db17e239567e4b840e73d5add50f355444ce` | — | Spec + K-S0b docs (10 commits) |
| PR #4 / `docs/sstack-ccr-001-context-restoration-report` HEAD | `1af1b1031c26a674a1eb14faff459b1ff8c0c285` | — | Context restoration report (this agent's prior push) |
| `feature/stage-1-2-cli-core` HEAD | `c8389466d3e6ea8592b8d25e993c10223a83ccdf` | — | CLI + Core reconstruction (1 commit) |

### SStack PR #3 implementation files inspected

| File | Path | Lines | Notes |
|---|---|---|---|
| `phpstan.neon` | PR #3 root | 6 lines | Level 8, no bleedingEdge (SOURCE-VERIFIED non-compliance) |
| `composer.json` | PR #3 root | 31 lines | PHP >=8.4, ext-json, ext-mbstring, phpstan ^2.1, phpunit ^11.0 |
| `.github/workflows/ci.yml` | PR #3 .github/workflows/ | ~30 lines | Runs PHPStan at level 8 (insufficient) |
| `src/Core/Config/Toml/TomlParser.php` | PR #3 | 918 lines | Three real bugs verified (signed int, datetime exception, nested AoT) |
| `src/Core/Config/ConfigRepository.php` | PR #3 | ~155 lines | `all()` redacts, `allRaw()` unredacted (matches spec §8) |
| `src/Core/Config/ConfigInterface.php` | PR #3 | ~94 lines | SECRET_PATTERN verbatim from DGLab |
| `src/Core/Config/ConfigLoader.php` | PR #3 | ~110 lines | Single `load()` method per B3 |
| `src/Core/Config/ConfigBuilder.php` | PR #3 | ~145 lines | TOML loader via `TomlParser::parse()` |
| `src/Core/Config/EnvLoader.php` | PR #3 | ~150 lines | Env-wins-over-file preserved |
| `src/Core/Config/ConfigValidator.php` | PR #3 | ~170 lines | Required-key + type + constraint + required-secret validation |
| `src/Core/Config/Environment.php` | PR #3 | ~48 lines | `Environment::detect()` is `public` (CCR-002 — would become `@internal` per OD-011) |
| `src/Core/Config/Exception/*.php` | PR #3 (5 files) | ~100 lines total | Approved hierarchy per D7 |

### SStack specification files inspected

| File | Path | Lines | Notes |
|---|---|---|---|
| `docs/architecture/core-10-reconstruction-spec.md` | SStack `docs/k-s0b-core-dependency-analysis` at `d042db17` | 894 lines | Saved to `/tmp/actual-spec.md`; compared to local copy at `/home/z/my-project/scripts/core10-v3-spec.md` (894 lines; identical except lines 834 + 860 provenance figures: binding 62/37 vs local 54/45) |
| `docs/architecture/owner-decisions-2026-11.md` | same branch | 101 lines | OD-001 through OD-009 only; **NO OD-010/011/012** recorded |
| `docs/architecture/reconstruction-roadmap.md` | same branch | 61 lines | Stage 0 complete; Stage 1 "NOT STARTED" (stale — CORE-10 PR exists) |
| `docs/architecture/implementation-sequence.md` | same branch | 89 lines | Wave 1: 10 components starting with CORE-10 |
| `docs/architecture/component-inventory.md` | same branch | 53 lines | 11 implemented, 5 deferred, 3 missing, 1 stubbed |
| `docs/k-s0b/conflicts.md` | same branch | 49 lines | 6 CONFLICT entries, all resolved |
| `docs/k-s0b/core-dependencies.csv` | same branch | 135 lines (134 data + header) | Core dependency graph |
| `docs/k-s0b/dag-verification.md` | same branch | not counted | 0 cycles |
| `docs/k-s0b/baseline-verification.md` | same branch | not counted | Baseline SHA verification |
| `docs/k-s0b/blueprint-inventory.md` | same branch | not counted | 20 blueprints verified |
| `docs/k-s0b/build-waves.md` | same branch | not counted | Build wave structure |
| `docs/k-s0b/namespace-analysis.md` | same branch | not counted | Namespace plan |
| `docs/k-s0b/test-coverage.md` | same branch | not counted | Test coverage analysis |
| `docs/k-s0b/transitive-dependencies.md` | same branch | not counted | Transitive deps |

---

## 7. SStack Local Working Tree (for comparison)

| Item | Path | Notes |
|---|---|---|
| Locally-modified `TomlParser.php` | `/home/z/my-project/scripts/sstack-core10/src/Core/Config/Toml/TomlParser.php` (951 lines) | Contains fixes from local commits `b312a95` + `1136065`; NOT pushed to SStack PR #3 |
| Locally-modified `phpstan.neon` | `/home/z/my-project/scripts/sstack-core10/phpstan.neon` (8 lines) | level max + bleedingEdge (spec-compliant) |
| Conformance harness | `/home/z/my-project/scripts/sstack-core10/conformance/toml-1.0.0-conformance.php` (187 lines) | 57/57 conformance checks against the local parser |
| Evidence package | `/home/z/my-project/download/core10-evidence/` (10 files, ~80 KB) | Contains outputs from local verification runs |

### Outer DGLab repo state

| Property | Value |
|---|---|
| Path | `/home/z/my-project/` |
| Remote | `origin → https://github.com/DGCodeIdeas/DGLab.git` |
| HEAD | `c70e604253921f79196602804f8dd8debde371b1` (UUID commit message) |
| Locally-committed SStack fixes in DGLab main | `b312a9550661af62c9a659acc14097be665ee53d` + `11360656315cf5857142bfc4ae06f7b2660a4599` |
| These commits in SStack remote? | **NO** — local to outer DGLab repo only |

---

## 8. DGLab Baseline (Historical Reference)

| Property | Value |
|---|---|
| Baseline SHA | `543329ed84a2148308e0cc455ac7e2fe27cf35f4` |
| Date | 2026-11-12 13:02:20 +0100 |
| Commit message | "fix(release-validation): reconcile package naming with loom repos:generate (#379)" |
| Files at baseline under `Core/Config/` | 18 PHP files (10 source + 5 test + 3 fixtures) |

### DGLab source blobs retrieved for fidelity comparison

| File | Blob SHA | Retrieved via |
|---|---|---|
| `Core/Config/ConfigInterface.php` | `8cd76a20607ce8f089b422bbbf18c23bde4abe3c` | `git cat-file -p` |
| `Core/Config/ConfigRepository.php` | `982d5577ab53e54ae9d3a5472b97ff5c38d6145f` | `git cat-file -p` |
| `Core/Config/ConfigBuilder.php` | `6f67a6201894a3498c497765db32c487b9771c3d` | `git cat-file -p` |
| `Core/Config/EnvLoader.php` | `53ed3847ec632f74c055a0533ed7f308f795f6db` | `git cat-file -p` |

All four files read in full for the source-fidelity comparison.

---

## 9. K-S0b Reported Figures — Verification Results

| Reported | Actual | Source |
|---|---|---|
| Core blueprints: 20 | 20 | `git ls-tree -r 543329ed --name-only \| grep 'Architecture/Core/CORE-[0-9]+' \| wc -l` |
| Core component directories: 14 | 14 | `git ls-tree -r 543329ed --name-only \| grep -E '^Core/[^/]+/' \| awk -F/ '{print $2}' \| sort -u \| wc -l` |
| Orchestrator PHP files: 8 | 17 total (8 source in `orchestrator/src/` + 9 test in `orchestrator/tests/`) | `git ls-tree -r 543329ed --name-only \| grep -E 'orchestrator/.*\.php$' \| wc -l` |
| CLI PHP files: 2 | 9 total (all in `archive/Legacy/cli/`; 0 in non-archived `cli/`) | `git ls-tree -r 543329ed --name-only \| grep -E 'cli/.*\.php$' \| wc -l` |
| Dependency CSV rows: 134 | 134 data rows + 1 header = 135 total | `wc -l docs/k-s0b/core-dependencies.csv` |
| Architectural conflicts: 6 | 6 | `grep -c '^## CONFLICT-' docs/k-s0b/conflicts.md` |
| Dependency cycles: 0 | 0 (no "cycle" mentions in dag-verification.md) | `grep -i cycle docs/k-s0b/dag-verification.md` |

**Discrepancy notes:**
- "Orchestrator PHP files: 8" matches `orchestrator/src/*.php` count (8 files: CIMonitor, CiGate, DependencyGraph, Manifest, MonorepoPackage, RepoManager, VersionBumpEngine = 7 + 1 = 8 if including `ci/run.php`). K-S0b appears to count source files only.
- "CLI PHP files: 2" cannot be reconciled with the baseline — all 9 CLI files are in `archive/Legacy/cli/` and the active `cli/` count is 0. The "2" figure likely refers to a different definition or K-S0b used a different baseline path.

---

## 10. Independent Verification Executed

| Verification | Method | Result |
|---|---|---|
| SStack PR #3 PHPStan at level 8 | `vendor/bin/phpstan analyse` in fresh checkout `/tmp/pr3-verify` | PASS — 0 errors |
| SStack PR #3 PHPStan at spec-required level max + bleedingEdge | Override config applied to PR #3 code | FAIL — 34 errors |
| SStack PR #3 PHPUnit | `vendor/bin/phpunit --no-coverage` in `/tmp/pr3-verify` | PASS — 99 tests, 131 assertions |
| SStack PR #3 composer validate | `composer validate --strict` | PASS |
| SStack PR #3 composer audit | `composer audit` | PASS — no advisories |
| SStack PR #3 TomlParser bug 1 (signed int) | Inline PHP test: `$p->parse("neg = -17")["neg"]` | Returns 17 (BUG) |
| SStack PR #3 TomlParser bug 2 (datetime exception) | Inline PHP test: `$p->parse("dt = 1979-13-99")` | Throws `DateMalformedStringException` (BUG) |
| SStack PR #3 TomlParser bug 3 (nested AoT) | Inline PHP test: parse `[[fruits]]\n...\n[[fruits.varieties]]\n...` | Returns 2 fruits (BUG) |
| SStack spec 1.0.0 vs 1.1.0 ABNF diff | `diff -u toml-1.0.0.abnf toml-1.1.0.abnf` | 8 grammar changes identified |
| SStack spec 1.0.0 vs 1.1.0 markdown diff | `diff -u toml-1.0.0.md toml-1.1.0.md` | Full spec comparison done |
| K-S0b figures | Direct counting at DGLab baseline `543329ed` | 5 of 7 figures match exactly; 2 need definition clarification |
| ADR-S01 / ADR-S02 existence | `find` across all SStack branches + DGLab repo | NOT FOUND as files (only referenced by title in 7 documents) |

---

## 11. Cross-References

- Context restoration report (SSTACK-CCR-001): `/home/z/my-project/download/SSTACK-CCR-001-CONTEXT-RESTORATION-REPORT.md`
- Per-repo analyses: `devium-analysis.md`, `php-collective-analysis.md`, `petalbranch-analysis.md`
- Comparison deliverables: `repository-comparison.md`, `lexer-parser-architecture-comparison.md`
- Foundational deliverables: `toml-version-comparison.md`, `official-specification-analysis.md`, `toml-conformance-analysis.md`
- Architecture proposal: `sovereignstack-native-parser-architecture.md`
- Migration proposal: `core-10-toml-1.1-migration-proposal.md`
- Performance: `performance-analysis.md`
- Security: `security-analysis.md`
- Dependencies: `dependency-and-licensing-analysis.md`
- Executive summary: `executive-summary.md`
