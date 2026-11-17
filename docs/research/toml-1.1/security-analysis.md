# Security Analysis

**Document type:** Deliverable 10 of 15
**Inspection date:** 2026-11-17

---

## 1. Threat Model

A TOML parser is invoked at application boot time on configuration files that are typically trusted (committed to the repository or deployed by the operator). However, the threat model must consider:

1. **Compromised config file.** An attacker who can write to a config file (e.g. via a CI/CD supply-chain attack, a misconfigured deployment, or a local-file-inclusion vulnerability in another component) can craft a malicious TOML file to attack the parser.

2. **Adversarial input via dynamic config.** Some applications load TOML files from user-uploaded sources (e.g. plugin manifests). These are adversarial inputs.

3. **Resource exhaustion.** A config file that triggers excessive CPU or memory consumption during parse can DoS the application at boot.

4. **Information disclosure.** Error messages that include file contents, secret values, or internal state can leak information to an attacker who can trigger parse errors.

5. **Parser state corruption.** A parser that leaves partially-constructed state accessible after a failed parse can leak data to subsequent code.

The SStack parser must be safe against all five threats.

---

## 2. Attack Vectors and Mitigations

### 2.1 Excessive nesting

**Attack:** A TOML document with deeply nested tables/arrays can cause stack overflow in a recursive-descent parser.

```
[a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z.a.b.c.d.e.f...]
```

**Mitigation:** Enforce a maximum nesting depth. Recommended limit: **100 levels**. The parser tracks current depth and throws `ConfigurationFileException` if exceeded.

**Status in inspected implementations:**

| Repo | Nesting limit | Status |
|---|---|---|
| SStack (current) | None | VULNERABILITY — no depth limit |
| Devium | None | VULNERABILITY |
| php-collective | None (INFERRED) | VULNERABILITY |
| PetalBranch | None (INFERRED) | VULNERABILITY |

The 1.1.0 spec unreleased clarifications (#1087) say: "tables typically cannot be nested infinitely." This is a hint that parsers should enforce a limit.

### 2.2 Excessive input size

**Attack:** A multi-GB TOML file can exhaust memory.

**Mitigation:** Enforce a maximum input size. Recommended limit: **1 MB**. SStack config files are typically < 10 KB; 1 MB is a generous upper bound.

**Status in inspected implementations:** None of the four implementations enforces an input-size limit. SStack should add this check at the lexer entry point.

### 2.3 Extremely large arrays

**Attack:** `[1, 2, 3, ..., 100000000]` can exhaust memory.

**Mitigation:** Enforce a maximum array element count. Recommended limit: **10,000 elements** per array.

### 2.4 Deep recursive structures

Same as 2.1. The PHP default stack limit is ~256 frames (depends on SAPI). A parser that recurses for each nested table/array can hit this limit before the explicit depth limit triggers.

**Mitigation:** The parser should use iterative algorithms (loops with explicit stacks) rather than recursive descent for deeply-nested structures. Alternatively, the explicit depth limit (100) is well below PHP's stack limit.

### 2.5 Invalid UTF-8

**Attack:** Malformed UTF-8 byte sequences can be used to:
- Encode path-traversal characters (`/` as overlong `\xC0\xAF`).
- Bypass string-content filters that assume well-formed UTF-8.
- Trigger bugs in downstream code that assumes well-formed UTF-8.

**Mitigation:** Per TOML 1.1.0 spec §Preliminaries, the parser MUST reject ill-formed UTF-8 byte sequences (preferred) or replace them with U+FFFD. SStack should choose **reject**.

Implementation: `mb_check_encoding($input, 'UTF-8')` returns false if the input is not well-formed UTF-8. Call this once at the lexer entry point.

**Status in inspected implementations:**

| Repo | UTF-8 validation | Status |
|---|---|---|
| SStack (current) | None | VULNERABILITY — accepts malformed UTF-8 silently |
| Devium | Lone `\r` not normalised; surrogate codepoints not rejected | VULNERABILITY |
| php-collective | mbstring polyfill used; explicit validation UNVERIFIED | UNVERIFIED |
| PetalBranch | Uses `mb_*` functions; explicit validation UNVERIFIED | UNVERIFIED |

### 2.6 Malformed escape sequences

**Attack:** A string like `"\xZZ"` (invalid hex) or `"\u001"` (too few digits) should be rejected. A parser that silently accepts these can produce unexpected string values.

**Mitigation:** The lexer's escape-sequence decoder must validate the hex digits and throw `ConfigurationFileException` for invalid escapes.

**Status:** SStack's current parser correctly rejects invalid escapes (TEST-VERIFIED in the conformance harness). No vulnerability here.

### 2.7 Numeric overflow

**Attack:** An integer like `99999999999999999999` (larger than PHP_INT_MAX) silently coerces to float in PHP. A float like `1e999` produces `INF`.

**Mitigation:** Per TOML 1.1.0 spec clarification #1058, parsers are free to support any int or float size. SStack should:
- For integers: check `<= PHP_INT_MAX` and `>= PHP_INT_MIN`. Throw `ConfigurationFileException` for out-of-range integers.
- For floats: PHP's float coercion is IEEE 754 compliant; `INF` is a legitimate float value (the TOML spec allows `inf` literally). No special handling needed.

**Status:** SStack's current parser does NOT check integer range. A 20-digit integer would be silently coerced to float. This is a minor vulnerability (data integrity, not security).

### 2.8 Invalid date/time values

**Attack:** A datetime like `1979-13-99` (month 13, day 99) should be rejected. PHP's `new DateTimeImmutable("1979-13-99")` throws `DateMalformedStringException` (PHP 8.3+).

**Mitigation:** Catch the exception and re-throw as `ConfigurationFileException` per spec §12. SStack's locally-modified parser (commit `1136065`) does this correctly. The actual PR #3 does NOT (the bug was TEST-VERIFIED in the context restoration report).

### 2.9 Memory exhaustion

Combined mitigation from 2.2 + 2.3: input-size limit (1 MB) + array-element limit (10,000) + depth limit (100) bounds peak memory to approximately:
- Input: 1 MB
- Token stream: ~10 MB (100× input overhead for token objects)
- AST: ~10 MB (similar overhead)
- Final value: ~1 MB (similar to input)
- Total peak: ~22 MB

This is well within typical PHP memory limits (default 128 MB).

### 2.10 CPU exhaustion

**Attack:** A TOML file with pathological structure (e.g. deeply nested arrays with millions of elements) can cause the parser to consume excessive CPU.

**Mitigation:** The input-size limit (1 MB) bounds the total parse work. A 1 MB TOML file parses in < 500 ms with a character-by-character parser. No separate CPU limit is needed.

A specific concern is **backtracking** in the lexer. If the lexer tries multiple alternatives for a token (e.g. "is this a date or a number?") and backtracks on failure, pathological input could cause exponential parse time. SStack's parser should use **no-backtracking** lexical analysis (peek 1 character to decide token type, then commit).

### 2.11 Error-message information disclosure

**Attack:** An error message that includes the file path, line content, or secret values can leak information.

**Mitigation:** Per spec §12, error messages MUST NOT include:
- Secret values (passwords, tokens, keys).
- `$_ENV` contents.
- Raw config file contents.
- Other env values (for `InvalidEnvironmentException`).

Error messages MAY include:
- File path (needed for debugging).
- Line number and column.
- Expected/actual token types.
- Key names (dot-notation paths).
- Expected/actual types for validation errors.

**Status:** SStack's current exception hierarchy follows this contract (SOURCE-VERIFIED in `ConfigurationException.php` and subclasses). No vulnerability.

### 2.12 Parser state corruption

**Attack:** If a parse fails midway and the parser's internal state (e.g. a half-built PHP array) is accessible to subsequent code, an attacker could trigger a parse failure and then read the partial state.

**Mitigation:** Per spec §13 failure atomicity: "If ANY step fails... NO ConfigRepository is constructed... The merged data is discarded... The appropriate exception is thrown with diagnostic context."

SStack's `ConfigLoader::load()` correctly discards the merged data on failure — the data lives in a local variable that goes out of scope when the exception propagates. No vulnerability.

The one documented side-effect: `EnvLoader::load()` populates `$_ENV` as it parses, and this cannot be rolled back. Spec §13 explicitly accepts this: matches DGLab source behaviour.

### 2.13 Duplicate-key ambiguity

**Attack:** A TOML file with duplicate keys (`key = 1\nkey = 2`) is ambiguous. If the parser silently takes the last value, an attacker who can append to a config file can override earlier values.

**Mitigation:** Per TOML spec, duplicate keys within the same table MUST be rejected. SStack's parser does this (SOURCE-VERIFIED in `parseKeyValue()` line 156).

A more subtle case: a table header `[a]` followed by `a.b = 1` (redefining `a` as a sub-table). SStack's parser does NOT explicitly check this case — it would produce a PHP runtime error when trying to use a string as an array. This is a robustness issue, not a security vulnerability.

---

## 3. Recommended Parser Protections for SStack

| # | Protection | Limit | Implementation point |
|---|---|---|---|
| 1 | Input size limit | 1 MB (reject larger) | `TomlParser::parse()` entry point |
| 2 | Nesting depth limit | 100 levels | `TomlParser` depth-tracking field |
| 3 | Array element count limit | 10,000 per array | `parseArray()` counter |
| 4 | Table key count limit | 10,000 per table | `parseTable()` counter |
| 5 | Key length limit | 1,024 characters | `parseKey()` length check |
| 6 | String length limit | 64 KB per string | `parseString()` length check |
| 7 | UTF-8 well-formedness check | reject malformed | `TomlLexer` entry point via `mb_check_encoding` |
| 8 | Integer range check | `PHP_INT_MIN <= v <= PHP_INT_MAX` | `parseInteger()` range check |
| 9 | No-backtracking lexer | single-character lookahead, commit on first match | `TomlLexer` design |
| 10 | Failure atomicity | discard merged data on any error | `ConfigLoader::load()` local variable scoping |
| 11 | Error message sanitisation | no secrets, no file contents, no `$_ENV` | `ConfigurationException` and subclasses |
| 12 | Explicit table-conflict check | reject `[a]` followed by `a.b = 1` | `parseTableHeader()` + `parseKeyValue()` coordination |

All 12 protections should be implemented in the refactored TOML 1.1.0 parser.

---

## 4. Interaction with CORE-10 Failure Atomicity

Spec §13 requires that a failed parse must NOT leave partially-constructed configuration state accessible. The parser achieves this via:

1. `ConfigLoader::load()` creates a local `$merged` variable.
2. Each step (loadFile, envFile, build, detect, validate) either succeeds and updates `$merged`, or throws and the local goes out of scope.
3. `ConfigRepository` is constructed only after all steps succeed.
4. The exception propagates with diagnostic context but without the merged data.

The 12 protections above integrate cleanly with this contract:
- Each limit check throws `ConfigurationFileException` on violation.
- The exception propagates up through `ConfigBuilder::loadFile()` → `ConfigLoader::load()`.
- The local `$merged` is discarded.
- No `ConfigRepository` is constructed.

**No conflict with failure atomicity.**

---

## 5. Security Posture Summary

| Threat | SStack current (PR #3) | SStack locally-modified | Recommended (refactored 1.1.0) |
|---|---|---|---|
| Excessive nesting | Not mitigated | Not mitigated | Depth limit 100 |
| Excessive input size | Not mitigated | Not mitigated | Size limit 1 MB |
| Large arrays | Not mitigated | Not mitigated | Element limit 10,000 |
| Deep recursion | Not mitigated | Not mitigated | Iterative algorithms OR depth limit |
| Invalid UTF-8 | Not mitigated | Not mitigated | `mb_check_encoding` reject |
| Malformed escapes | Mitigated | Mitigated | Mitigated (preserved) |
| Numeric overflow | Not mitigated | Not mitigated | Range check |
| Invalid datetime | **NOT mitigated (PR #3 bug)** | Mitigated (commit 1136065) | Mitigated (preserved) |
| Memory exhaustion | Not mitigated | Not mitigated | Combined limits bound peak to ~22 MB |
| CPU exhaustion | Not mitigated | Not mitigated | No-backtracking lexer + size limit |
| Error-message disclosure | Mitigated | Mitigated | Mitigated (preserved) |
| Parser state corruption | Mitigated (failure atomicity) | Mitigated | Mitigated (preserved) |
| Duplicate-key ambiguity | Partially mitigated | Partially mitigated | Full table-conflict check |

**Summary:** The current SStack parser mitigates 4 of 13 identified threats. The refactored 1.1.0 parser should mitigate all 13.

---

## 6. Cross-References

- Native architecture proposal (resource-limit implementation): `sovereignstack-native-parser-architecture.md`
- Performance analysis (memory targets): `performance-analysis.md`
- Existing SStack exception hierarchy: `core-10-toml-1.1-migration-proposal.md` (preserved)
- Per-implementation security details: `devium-analysis.md`, `php-collective-analysis.md`, `petalbranch-analysis.md`
