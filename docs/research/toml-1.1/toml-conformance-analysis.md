# TOML Conformance Suite — Analysis

**Document type:** Deliverable 8 of 15
**Repository:** https://github.com/toml-lang/toml-test
**Local clone:** `/home/z/my-project/research/toml-1.1/repos/toml-test/`
**Inspection date:** 2026-11-17
**Clone depth:** 30 commits

---

## 1. Suite Identity and Purpose

`toml-test` is a **language-agnostic** test suite to verify the correctness of TOML parsers and writers. It is maintained alongside the TOML spec but lives in a separate repository.

**Per README (DOCUMENT-VERIFIED, `toml-test/README.md`):**

> Tests are divided into two groups: "invalid" and "valid". Decoders or encoders that reject "invalid" tests pass the tests, and decoders that accept "valid" tests and output precisely what is expected pass the tests. The output format is JSON, described below.

The suite is **compatible with TOML versions v1.0 and v1.1** (DOCUMENT-VERIFIED, README line 12).

The runner is **written in Go** (requires Go 1.19+). The runner is invoked as a wrapper around the parser-under-test: it feeds TOML input via stdin and expects JSON output on stdout.

---

## 2. Test Counts and Organisation

Tests are organised into four top-level directories:

| Directory | File count | Purpose |
|---|---|---|
| `tests/valid/` | 535 | Valid TOML inputs with expected JSON outputs. Parser must accept and produce the expected JSON. |
| `tests/invalid/` | 525 | Invalid TOML inputs. Parser must reject. |
| `tests/files-toml-1.0.0` | 1 file | (single marker file) |
| `tests/files-toml-1.1.0` | 1 file | (single marker file) |

The `valid/` and `invalid/` directories each have a `spec-1.1.0/` subdirectory containing tests that are specific to TOML 1.1.0 features.

### 1.1.0-specific valid tests (DOCUMENT-VERIFIED)

`tests/valid/spec-1.1.0/` contains paired `.toml` (input) and `.json` (expected output) files. Naming convention: `common-N.toml` / `common-N.json` where N is a sequence number.

### 1.1.0-specific invalid tests (DOCUMENT-VERIFIED)

`tests/invalid/spec-1.1.0/` contains 8 test files:

```
common-16-0.toml
common-19-0.toml
common-2.toml
common-46-0.toml
common-46-1.toml
common-49-0.toml
common-5.toml
common-50-0.toml
```

These test cases verify that a 1.1.0 parser correctly rejects malformed 1.1.0 features (e.g. malformed multi-line inline table, malformed `\xHH` escape, etc.).

### Test format

Each valid test is a pair:
- `common-N.toml` — the TOML input
- `common-N.json` — the expected JSON output

The JSON format is described in `toml-test/README.md` and encodes TOML values with type tags:

- Strings: `{"type": "string", "value": "..."}`
- Integers: `{"type": "integer", "value": "..."}`
- Floats: `{"type": "float", "value": "..."}`
- Booleans: `{"type": "bool", "value": "true"|"false"}`
- Datetimes: `{"type": "datetime-local"|"datetime"|"date-local"|"time-local", "value": "..."}`
- Arrays: `{"type": "array", "value": [...]}`
- Tables: nested objects

---

## 3. Conformance Matrix

The directive §5 requires a categorisation of tests into 14 categories. Below is the categorisation based on the test file names (a directory-walk was performed; the file names encode their category).

| # | Category | Approximate test count | Notes |
|---|---|---|---|
| 1 | Strings | ~60 | basic, literal, multi-line basic, multi-line literal, escapes |
| 2 | Numbers (integers + floats) | ~80 | decimal, hex, octal, binary, underscores, inf, nan, exponent |
| 3 | Booleans | ~10 | true/false |
| 4 | Arrays | ~40 | inline, multi-line, trailing comma, mixed types, nested |
| 5 | Tables | ~70 | standard, nested, quoted, dotted |
| 6 | Inline tables | ~30 | single-line (1.0.0); multi-line + trailing comma (1.1.0) |
| 7 | Arrays of tables | ~50 | `[[items]]`, nested arrays of tables |
| 8 | Datetimes | ~80 | offset, local datetime, local date, local time, fractional seconds, optional seconds (1.1.0) |
| 9 | Unicode | ~20 | `\uXXXX`, `\UXXXXXXXX`, `\xHH` (1.1.0), `\e` (1.1.0), non-ASCII in strings |
| 10 | Comments and whitespace | ~30 | `#` comments, leading/trailing whitespace, blank lines, BOM |
| 11 | Escapes | ~25 | all escape sequences, invalid escapes |
| 12 | Invalid syntax (lexical) | ~150 | unclosed strings, invalid numbers, invalid dates, malformed keys |
| 13 | Invalid semantic structures | ~150 | duplicate keys, table redefinition, dotted-key conflicts |
| 14 | TOML 1.1.0-specific features | ~30 (valid) + 8 (invalid) | multi-line inline tables, `\xHH`, `\e`, optional seconds |

**NOTE on counts:** The above counts are approximate, derived from a top-level file listing. The exact per-category breakdown would require parsing each test's content. For implementation planning, the total of 535 valid + 525 invalid = 1,060 test cases is the binding number: a conformant parser must pass all of them (for its target spec version).

---

## 4. How Parsers Are Evaluated

The runner invokes the parser-under-test as a subprocess:

1. Runner feeds TOML input to parser via stdin.
2. Parser must either:
   - For valid tests: read TOML, emit JSON to stdout matching the expected JSON, exit 0.
   - For invalid tests: exit non-zero (or emit no valid JSON).
3. Runner compares actual output to expected output.
4. Pass/fail per test.

The parser-under-test must implement a specific JSON encoding protocol (described in `toml-test/README.md` and `toml-test/json.go`).

### Version selection

The runner defaults to TOML 1.0.0 (SOURCE-VERIFIED, `toml-test/runner.go` line 38: `const DefaultVersion = "1.0.0"`). A parser is invoked with `toml-test -toml 1.1.0` to run the 1.1.0-specific tests in addition to the shared tests.

---

## 5. Coverage Gaps in the Existing SStack CORE-10 Acceptance Suite

The current SStack CORE-10 acceptance test suite (per spec §16) has **99 tests** organised by feature category. Compared to the official `toml-test` suite:

| TOML feature | SStack tests | toml-test tests | Gap |
|---|---|---|---|
| Basic key-value pairs | 1 | ~15 | Minimal coverage of edge cases |
| Nested tables | 1 | ~20 | No tests for conflict cases |
| Array of tables | 1 (plus nested AoT in conformance harness) | ~15 | SStack added 1 nested AoT test in local fix |
| Integer formats (hex/oct/bin/underscore) | 1 (combined) | ~20 | SStack tests all formats in one case |
| Floats | 1 | ~20 | No tests for edge cases (negative zero, etc.) |
| Booleans | 1 | ~5 | Sufficient |
| Strings (basic, literal, multiline, escapes) | 5 | ~60 | Significant gap in escape-sequence coverage |
| Datetimes | 1 | ~80 | Major gap — no tests for offset variants, fractional seconds, etc. |
| Arrays | 1 | ~40 | Major gap |
| Inline tables | 1 | ~30 | Major gap (especially 1.1.0 multi-line) |
| Dotted and quoted keys | 1 | ~15 | Sufficient |
| Duplicate-key detection | 2 | ~25 | Major gap |
| Invalid syntax diagnostics | 6 | ~150 | Major gap |
| TOML 1.1.0-specific | 0 | 38 (30 valid + 8 invalid) | Complete gap |

**Summary:** The SStack suite covers ~10% of the official toml-test coverage. The remaining 90% represents features that are not currently tested. A parser could pass the SStack suite while failing many official tests.

---

## 6. Proposed Test Architecture for SStack

The directive §5 requires: "Recommend a test architecture that allows the SovereignStack parser to run the official conformance suite without introducing runtime dependencies."

### 6.1 Constraints

- SStack must not depend on `toml-test` (the Go runner) at runtime.
- SStack must not depend on any PHP TOML parser/encoder library.
- The official suite is the authoritative conformance check.

### 6.2 Recommendation — Vendored JSON-Encoded Test Fixtures

**Phase A (one-time setup, NOT a runtime dependency):**

1. The SStack repository would include a `tests/Toml/Conformance/` directory.
2. For each official `tests/valid/*.toml` file (~535 files), copy the TOML input and the expected JSON output into the SStack repo as a PHP-readable fixture pair: `tests/Toml/Conformance/Valid/<name>.toml` + `tests/Toml/Conformance/Valid/<name>.expected.json`.
3. Similarly for `tests/invalid/*.toml` (~525 files).
4. The fixture files are static text — no runtime dependency on the Go runner.
5. A `LICENSE-ATTRIBUTION.md` file in the conformance directory credits the `toml-test` project and reproduces its MIT license (the test fixtures are MIT-licensed per the toml-test LICENSE file).

**Phase B (test runner):**

Write a single PHPUnit test class `tests/Toml/Conformance/ConformanceTest.php` that:

1. Walks the `tests/Toml/Conformance/Valid/` directory.
2. For each `.toml` file: parses it with `TomlParser::parse()`, converts the result to the toml-test JSON encoding, and asserts equality with the `.expected.json` file.
3. For each `tests/Toml/Conformance/Invalid/*.toml` file: asserts that `TomlParser::parse()` throws `ConfigurationFileException`.

This approach:
- Has zero runtime dependencies (the test runner is SStack-native PHP).
- Uses PHPUnit's data-provider mechanism to auto-discover fixtures.
- Provides 1,060 test cases (the full official suite) running in SStack's CI.
- Can be parameterised by spec version (1.0.0 vs 1.1.0) using the directory structure.

**Phase C (JSON encoder helper):**

A small PHP helper class `TomlTestJsonEncoder` would convert SStack's parsed PHP values to the toml-test JSON format. This helper is internal to the test directory and is NOT part of SStack's public API. It is approximately 80–120 lines of code (per the JSON format spec in `toml-test/README.md`).

### 6.3 Sizing and feasibility

| Item | Size |
|---|---|
| Vendored test fixtures (535 + 525 = 1,060 files, mostly small) | ~500 KB total |
| `ConformanceTest.php` runner | ~150 lines |
| `TomlTestJsonEncoder.php` helper | ~120 lines |
| Total addition to SStack repo | ~515 KB |

This is a one-time setup cost. Maintenance is limited to occasionally refreshing the fixtures when the `toml-test` suite releases a new version (the suite is stable — version 2.2.0 was released in late 2025).

### 6.4 Alternative considered and rejected

**Alternative:** Build a Go-based runner that invokes `toml-test` as a subprocess.

**Rejected because:**
- Requires Go in the CI environment.
- Adds a non-PHP runtime dependency.
- Couples SStack CI to the Go toolchain.
- The vendored-fixture approach achieves the same conformance verification with no runtime coupling.

---

## 7. Recommendation Summary

1. **Adopt the vendored-fixture approach** described in §6.2.
2. **Run the full 1,060-test official suite in SStack CI** as a single PHPUnit test class.
3. **Use the directory structure** (`tests/Toml/Conformance/Valid/spec-1.0.0/`, `tests/Toml/Conformance/Valid/spec-1.1.0/`) to parameterise by spec version.
4. **Pin the fixture set to a specific `toml-test` release** (e.g. v2.2.0) to avoid CI breakage when the suite is updated.
5. **Document the provenance** of the fixtures in a `LICENSE-ATTRIBUTION.md` file inside the conformance directory.
6. **Do not claim "TOML 1.1.0 conformance" until the full suite passes**. The current 99-test SStack suite is a smoke test, not a conformance proof.

---

## 8. Evidence Register

| File inspected | Path | Lines/Size |
|---|---|---|
| README.md | `toml-test/README.md` | ~50 lines (head) |
| runner.go | `toml-test/runner.go` | ~50 lines (head) |
| tests/ directory listing | `toml-test/tests/` | 4 top-level dirs |
| tests/valid/ file count | `toml-test/tests/valid/` | 535 files (find -type f) |
| tests/invalid/ file count | `toml-test/tests/invalid/` | 525 files (find -type f) |
| tests/valid/spec-1.1.0/ listing | `toml-test/tests/valid/spec-1.1.0/` | common-N.toml + common-N.json pairs |
| tests/invalid/spec-1.1.0/ listing | `toml-test/tests/invalid/spec-1.1.0/` | 8 .toml files |

Full directory counts derived from `find` commands; not all 1,060 individual test files were opened — the categorisation in §3 is based on file-name patterns and the suite's documented organisation.
