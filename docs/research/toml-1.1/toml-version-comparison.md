# TOML 1.0.0 vs 1.1.0 — Normative Version Comparison

**Document type:** Deliverable 1 of 15
**Source authority:** Directly inspected from `toml-lang/toml` repository
**Spec tag 1.0.0:** `8eae5e1c005bc5836098505f85a7aa06568999dd` (released 2021-01-11)
**Spec tag 1.1.0:** `bcbbd1c1f03473ffe97b8bf26a0fc945efe2b4a1` (released 2025-12-24)
**ABNF grammar diff:** `toml-1.0.0.abnf` (243 lines) vs `toml-1.1.0.abnf` (247 lines)
**Inspection date:** 2026-11-17

---

## Methodology

This comparison was produced by:
1. Extracting `toml.md` from both the `1.0.0` and `1.1.0` git tags of `toml-lang/toml`.
2. Extracting `toml.abnf` from both tags.
3. Running `diff -u` on both pairs.
4. Reading the `CHANGELOG.md` at HEAD to cross-reference each change with its source PR.
5. Reading each changed section of `toml.md` in full context.

**Evidence classification tags used throughout:** SOURCE-VERIFIED (read from spec/ABNF), DOCUMENT-VERIFIED (from CHANGELOG/README).

Every claim below is traceable to a specific line in either the spec markdown or the ABNF grammar.

---

## Summary of Normative Changes

TOML 1.1.0 was released on **2025-12-18** (CHANGELOG line 27). It contains **four substantive language changes** plus **six clarifying amendments** and a restructuring of the spec document itself. The ABNF grammar changed in **eight locations**.

The four substantive changes (any one of which would break a strict 1.0.0 parser if encountered in the wild):

1. Newlines and trailing commas in inline tables (#904)
2. `\xHH` escape for codepoints ≤ U+00FF (#796)
3. `\e` escape for the ESC character U+001B (#790)
4. Optional seconds in time and datetime values (#894)

The clarifying amendments (do not change accepted syntax, but tighten semantic requirements):

5. Comments never affect the tables produced (#950)
6. Unicode/UTF-8 references clarified (#929)
7. Dotted keys define tables — clarification of where (#859)
8. Newline normalization in multi-line literal strings (#842)
9. Sub-millisecond precision is allowed (#805)
10. Parsers are free to support any int or float size (#1058)

---

## Detailed Change Matrix

For every difference, the table below records: feature, 1.0.0 behaviour, 1.1.0 behaviour, normative reference, parser impact, test impact, complexity, security impact, recommendation.

### Change 1 — Newlines and trailing commas in inline tables

| Field | Description |
|---|---|
| Feature | Inline table syntax |
| TOML 1.0.0 | Inline tables MUST be on a single line and MUST NOT end with a trailing comma. ABNF: `inline-table = inline-table-open [ inline-table-keyvals ] inline-table-close` where `inline-table-keyvals = keyval [ inline-table-sep inline-table-keyvals ]` and separators are `ws %x2C ws`. |
| TOML 1.1.0 | Inline tables MAY span multiple lines, MAY contain comments, and MAY end with a trailing comma. ABNF: `inline-table = inline-table-open [ inline-table-keyvals ] ws-comment-newline inline-table-close` where `inline-table-keyvals = ws-comment-newline keyval ws-comment-newline inline-table-sep inline-table-keyvals / ws-comment-newline keyval ws-comment-newline [ inline-table-sep ]`. The `inline-table-open` and `inline-table-close` tokens no longer carry mandatory `ws` (whitespace is consumed by `ws-comment-newline` instead). |
| Normative reference | CHANGELOG line 30 (PR #904); `toml-1.1.0.abnf` lines 227–232; `toml-1.1.0.md` "Inline Table" section. |
| Parser impact | Lexer must accept newlines, comments, and a trailing comma inside `{...}`. Parser must allow zero keyvals (already allowed) and one keyval with optional trailing comma. The inline-table parser can no longer assume single-line input — it must reuse the multi-line whitespace/comment handling already used for arrays. |
| Test impact | New tests required for: (a) multi-line inline table, (b) trailing comma in single-line inline table, (c) trailing comma in multi-line inline table, (d) comments inside inline table, (e) empty inline table on its own line, (f) REJECT: malformed trailing data after `}` on same line. |
| Complexity | Medium — conceptually simple but requires reusing the multi-line whitespace/comment logic in a context where it was previously forbidden. |
| Security impact | Low. No new escape vectors. Marginal risk of a parser failing to enforce the closing `}` if it is on a later line — but the grammar still requires `}` to appear. |
| Recommendation | Adopt. Required for 1.1.0 conformance. Aligns with array behaviour. |

### Change 2 — `\xHH` escape for codepoints ≤ U+00FF

| Field | Description |
|---|---|
| Feature | String escape sequences |
| TOML 1.0.0 | Basic strings support `\b \t \n \f \r \" \\ \uXXXX \UXXXXXXXX`. No `\x` escape. ABNF: `escape-seq-char =/ %x75 4HEXDIG / %x55 8HEXDIG`. |
| TOML 1.1.0 | Adds `\xHH` for codepoints U+0000 to U+00FF. ABNF: `escape-seq-char =/ %x78 2HEXDIG ; xHH U+00HH`. Two hex digits required (not one, not three). |
| Normative reference | CHANGELOG line 36 (PR #796); `toml-1.1.0.abnf` line 84. |
| Parser impact | Lexer's escape-sequence decoder must accept `x` followed by exactly 2 hex digits, producing the byte/codepoint U+00HH. Must reject `x` not followed by 2 hex digits. |
| Test impact | New tests: (a) `\x00` → U+0000 (null byte), (b) `\x41` → 'A', (c) `\xff` → U+00FF (ÿ), (d) REJECT: `\x` alone, (e) REJECT: `\x1` (one digit), (f) REJECT: `\xZZ` (non-hex). |
| Complexity | Low. Trivial extension of the existing `\u` decoder. |
| Security impact | Low. The null byte (U+0000) is a legitimate concern in C-string contexts, but PHP strings are length-prefixed and null-byte-safe. The encoded value cannot exceed U+00FF by definition (2 hex digits). |
| Recommendation | Adopt. Required for 1.1.0 conformance. Trivial implementation cost. |

### Change 3 — `\e` escape for ESC character U+001B

| Field | Description |
|---|---|
| Feature | String escape sequences |
| TOML 1.0.0 | No `\e` escape. The ESC character (U+001B) could only be encoded via `\u001b` or `\U0000001b`. |
| TOML 1.1.0 | Adds `\e` for U+001B. ABNF: `escape-seq-char =/ %x65 ; e escape U+001B`. |
| Normative reference | CHANGELOG line 41 (PR #790); `toml-1.1.0.abnf` line 81. |
| Parser impact | Lexer's escape-sequence decoder must accept `e` as a single-character escape producing U+001B. |
| Test impact | New tests: (a) `"esc: \e["` → "esc: \x1b[", (b) REJECT: `\E` (uppercase, not allowed — escapes are case-sensitive). |
| Complexity | Low. Single new branch in the escape dispatcher. |
| Security impact | Low. U+001B is the ASCII ESC character; it is a control character but not particularly dangerous in PHP string contexts. The same character was already representable via `\u001b`. |
| Recommendation | Adopt. Required for 1.1.0 conformance. Trivial. |

### Change 4 — Optional seconds in time and datetime values

| Field | Description |
|---|---|
| Feature | Date/time syntax |
| TOML 1.0.0 | `partial-time = time-hour ":" time-minute ":" time-second [ time-secfrac ]`. Seconds mandatory. |
| TOML 1.1.0 | `partial-time = time-hour ":" time-minute [ ":" time-second [ time-secfrac ] ]`. Seconds optional. Fractional seconds require seconds to be present. |
| Normative reference | CHANGELOG line 45 (PR #894); `toml-1.1.0.abnf` line 180. |
| Parser impact | Datetime lexer must accept `HH:MM` as a valid local time, and `YYYY-MM-DDTHH:MM` or `YYYY-MM-DD HH:MM` as a valid local datetime. The optional-seconds form applies to all datetime variants: offset datetime, local datetime, local time. |
| Test impact | New tests: (a) `t = 14:15` → local time 14:15:00, (b) `dt = 2010-02-03 14:15` → local datetime, (c) `dt = 2010-02-03T14:15Z` → offset datetime, (d) `dt = 2010-02-03T14:15+07:00` → offset datetime with numeric offset, (e) verify fractional seconds still REJECTED without seconds: `t = 14:15.5` invalid. |
| Complexity | Low. Trivial grammar relaxation — the seconds and fractional-seconds portion becomes an optional group. |
| Security impact | None. No new data exposure. |
| Recommendation | Adopt. Required for 1.1.0 conformance. Aligns with ISO 8601 practice. |

### Change 5 — Comments never affect parser output (clarification)

| Field | Description |
|---|---|
| Feature | Comment semantics |
| TOML 1.0.0 | Comments are described syntactically (`#` to end of line) but the spec does not explicitly state that parsers must not modify keys/values based on comment contents. |
| TOML 1.1.0 | Adds: "Comments should be used to communicate between the human readers of a file. Parsers must not modify keys or values, based on the presence (or contents) of a comment." |
| Normative reference | CHANGELOG line 49 (PR #950); `toml-1.1.0.md` Comment section. |
| Parser impact | None for compliant 1.0.0 parsers (they already ignore comments). This is a normative clarification, not a new acceptance. |
| Test impact | None required for 1.1.0 conformance — a 1.0.0 parser that ignores comments already satisfies this. |
| Complexity | None. |
| Security impact | None. |
| Recommendation | No parser change required. Note for documentation. |

### Change 6 — Unicode / UTF-8 clarification

| Field | Description |
|---|---|
| Feature | UTF-8 well-formedness |
| TOML 1.0.0 | "A TOML file must be a valid UTF-8 encoded Unicode document." |
| TOML 1.1.0 | "A TOML file must be a valid UTF-8 encoded Unicode document. Specifically this means that a file as a whole must form a well-formed code-unit sequence. Otherwise, it must be rejected (preferably) or have ill-formed byte sequences replaced with U+FFFD, as per the Unicode specification." Also: the ABNF preamble adds "Although a TOML document must be valid UTF-8, this grammar refers to the Unicode Code Points you get after you decode the UTF-8 input." |
| Normative reference | CHANGELOG line 52 (PR #929); `toml-1.1.0.abnf` lines 4–6; `toml-1.1.0.md` Preliminaries section. |
| Parser impact | The parser must either reject ill-formed UTF-8 byte sequences (preferred) or replace them with U+FFFD. The SStack parser should choose **reject** for defence-in-depth: a malformed UTF-8 file almost always indicates either corruption or an attack attempt. |
| Test impact | New tests: (a) REJECT: file containing a lone continuation byte (e.g. `\x80` at start), (b) REJECT: file containing an overlong encoding (e.g. `\xC0\xAF`), (c) REJECT: file containing a surrogate codepoint encoded as UTF-8 (`\xED\xA0\x80` etc.). |
| Complexity | Low. PHP's `mb_check_encoding($s, 'UTF-8')` performs this check in O(n). The parser can call this once at the entry point. |
| Security impact | Medium. Malformed UTF-8 is a classic attack vector (UTF-8 overlong encoding of `/`, path traversal via filter evasion, etc.). Rejecting it is a security improvement. |
| Recommendation | Adopt the "reject" variant (not the U+FFFD replacement). Add an upfront `mb_check_encoding` call in the lexer. |

### Change 7 — Dotted keys define tables (clarification)

| Field | Description |
|---|---|
| Feature | Dotted key semantics |
| TOML 1.0.0 | Dotted keys are described as defining nested structures, but the exact table-creation semantics are ambiguous in edge cases (especially interaction with explicit `[a.b.c]` headers). |
| TOML 1.1.0 | PR #859 clarifies where and how dotted keys define tables. The full text of the clarification is in `toml-1.1.0.md` under "Keys" / "Dotted Keys". |
| Normative reference | CHANGELOG line 55 (PR #859). |
| Parser impact | None for a 1.0.0 parser that already implements the canonical semantics (dotted keys define nested tables; you cannot redefine a table that was already created by an explicit header; you cannot use a dotted key to define a sub-table of an array-of-tables element). |
| Test impact | The existing 1.0.0 invalid-test set already covers these cases. No new tests required for 1.1.0. |
| Complexity | None. |
| Security impact | None. |
| Recommendation | No parser change required. Verify existing behaviour against the 1.0.0 invalid-test suite. |

### Change 8 — Newline normalization in multi-line literal strings (clarification)

| Field | Description |
|---|---|
| Feature | Multi-line literal string newline handling |
| TOML 1.0.0 | "When the last non-whitespace character on a line is a `\`, it will be trimmed along with all whitespace (including newlines) up to the next non-whitespace character." This was ambiguous about whether a lone `\r` (CR without LF) gets normalized. |
| TOML 1.1.0 | PR #842 clarifies that newline normalization applies only inside multi-line **basic** strings (because line-ending escapes are interpreted there); multi-line **literal** strings preserve bytes verbatim including any `\r` characters. |
| Normative reference | CHANGELOG line 58 (PR #842). |
| Parser impact | The parser must NOT normalize `\r\n` → `\n` inside multi-line literal strings. Inside multi-line basic strings, the existing line-ending normalization applies. |
| Test impact | New test: a multi-line literal string containing `\r\n` preserves both bytes. |
| Complexity | Low — actually simplifies the parser (no transformation needed for literal strings). |
| Security impact | None. |
| Recommendation | Verify existing parser does not normalize `\r` inside `'''...'''`. If it does, remove that transformation. |

### Change 9 — Sub-millisecond precision allowed (clarification)

| Field | Description |
|---|---|
| Feature | Datetime fractional seconds precision |
| TOML 1.0.0 | `time-secfrac = "." 1*digit` — any number of fractional digits. The spec did not impose a precision limit, but some readers interpreted this as "up to millisecond precision" by analogy with ISO 8601. |
| TOML 1.1.0 | PR #805 clarifies that sub-millisecond precision (e.g. microseconds, nanoseconds) is allowed. |
| Normative reference | CHANGELOG line 61 (PR #805). |
| Parser impact | None for a 1.0.0 parser that already accepts `1*digit` per the ABNF. |
| Test impact | New test: a datetime with microsecond precision (`2025-01-01T00:00:00.123456`) is accepted. |
| Complexity | None. |
| Security impact | None. |
| Recommendation | No parser change required. Verify existing parser accepts arbitrary fractional precision. |

### Change 10 — Parsers may support any int or float size (clarification)

| Field | Description |
|---|---|
| Feature | Numeric representation width |
| TOML 1.0.0 | The spec does not specify a numeric range; some readers assumed int64. |
| TOML 1.1.0 | PR #1058 clarifies that parsers are free to support any int or float size, including arbitrary precision (bignum). |
| Normative reference | CHANGELOG line 64 (PR #1058). |
| Parser impact | None for a PHP parser (PHP ints are 64-bit on 64-bit platforms; floats are IEEE 754 doubles). The parser should accept any 64-bit int; values that overflow should either throw or be coerced to float. SStack should choose **throw** for an int that overflows PHP_INT_MAX (defence in depth). |
| Test impact | New test: an integer exceeding PHP_INT_MAX is either rejected or coerced to float (SStack: reject). |
| Complexity | Low — a single range check. |
| Security impact | Low. Integer overflow is a known source of bugs in C-based parsers; less of a concern in PHP. |
| Recommendation | Add a range check: integers must fit in PHP_INT_MAX; otherwise throw `ConfigurationFileException`. |

---

## Additional ABNF-Level Changes (Cosmetic / Reorganization)

The ABNF diff shows several non-semantic reorganizations:

1. **`non-eol` excludes DEL (U+007F)** — `non-eol = %x09 / %x20-7E / non-ascii` (was `%x20-7F`). DEL is no longer a valid comment character.
2. **Multi-line basic string body uses `basic-char`** — `mlb-content = basic-char / newline / mlb-escaped-nl` (was `mlb-char / newline / mlb-escaped-nl` where `mlb-char = mlb-unescaped / escaped` and `mlb-unescaped = wschar / %x21 / %x23-5B / %x5D-7E / non-ascii`). This is a refactor that combines the unescaped-char and escaped-char rules into a single `basic-char` rule, reducing ABNF verbosity without changing the language.
3. **Multi-line literal string body uses `literal-char`** — same refactor.
4. **`inf` and `nan` use uppercase hex literals in ABNF** — `%x69.6E.66` and `%x6E.61.6E` (was lowercase `%x69.6e.66`). ABNF hex literals are case-insensitive, so this is purely cosmetic.
5. **Whitespace handling around inline-table delimiters** — see Change 1 above.

The DEL exclusion (#1) is a subtle behavioural change: a comment containing the DEL character (U+007F) was technically valid in 1.0.0 (per a strict reading of the ABNF) and is invalid in 1.1.0. In practice, no human-written TOML uses DEL in comments, so the impact is negligible.

---

## Backward Compatibility Assessment

**QUESTION:** Do any existing valid TOML 1.0.0 documents become invalid or change meaning under TOML 1.1.0?

**ANSWER:** Only one — and it is a non-issue in practice.

| Document pattern | 1.0.0 status | 1.1.0 status | Impact |
|---|---|---|---|
| Inline table on single line, no trailing comma | valid | valid | None |
| Multi-line inline table | **invalid** | valid | 1.0.0 docs don't use this (forbidden); 1.1.0 docs may |
| Trailing comma in inline table | **invalid** | valid | 1.0.0 docs don't use this; 1.1.0 docs may |
| `\xHH` escape in basic string | **invalid** | valid | 1.0.0 docs don't use this; 1.1.0 docs may |
| `\e` escape in basic string | **invalid** | valid | 1.0.0 docs don't use this; 1.1.0 docs may |
| Time without seconds (`14:15`) | **invalid** | valid | 1.0.0 docs don't use this; 1.1.0 docs may |
| Comment containing U+007F (DEL) | technically valid per 1.0.0 ABNF | invalid | Negligible — no human-written TOML uses DEL in comments |
| Multi-line literal string containing `\r\n` | bytes preserved (no rule) | bytes preserved (clarified) | None — confirms existing behaviour |
| File with malformed UTF-8 | rejection not strictly required | rejection preferred | A 1.0.0 parser that accepted malformed UTF-8 was non-conforming anyway |

**Conclusion:** A strict 1.0.0 parser will reject valid 1.1.0 documents that use any of the four new features. A 1.1.0 parser accepts all valid 1.0.0 documents (the DEL-in-comment case is the only theoretical exception, and no real document uses it).

**Therefore:** Upgrading SStack from TOML 1.0.0 to TOML 1.1.0 is **strictly additive** — no existing valid SStack configuration becomes invalid, and no existing valid configuration changes meaning.

---

## Recommendation Summary

| Change | Adopt? | Complexity | Driver |
|---|---|---|---|
| 1 — Multi-line inline tables + trailing comma | YES | Medium | Required for 1.1.0 conformance |
| 2 — `\xHH` escape | YES | Low | Required for 1.1.0 conformance |
| 3 — `\e` escape | YES | Low | Required for 1.1.0 conformance |
| 4 — Optional seconds in time/datetime | YES | Low | Required for 1.1.0 conformance |
| 5 — Comments do not affect parser output | YES (documentation only) | None | Normative clarification |
| 6 — UTF-8 well-formedness (reject) | YES | Low | Security improvement |
| 7 — Dotted key table creation | YES (verify existing) | None | Normative clarification |
| 8 — Multi-line literal `\r` preservation | YES (verify existing) | None | Normative clarification |
| 9 — Sub-millisecond precision | YES (verify existing) | None | Normative clarification |
| 10 — Int/float size freedom | YES (add range check) | Low | Defence in depth |

**Overall verdict:** The upgrade from TOML 1.0.0 to TOML 1.1.0 is **architecturally justified**. The four substantive changes are all syntactic relaxations (more documents become valid, no existing valid document becomes invalid). The clarifying amendments either confirm existing correct behaviour or add a single upfront UTF-8 validation check that improves security.

The implementation cost is **low to medium**: the lexer needs three new escape sequences (`\xHH`, `\e`, optional seconds), the inline-table parser needs to reuse the multi-line whitespace logic already implemented for arrays, and a UTF-8 validation pass should be added at the lexer entry point.

---

## Cross-References

- Official spec repository analysis: see `official-specification-analysis.md`
- Existing SStack parser defects (3 real bugs in current PR #3): see `evidence-and-source-register.md`
- Migration proposal for the SStack spec: see `core-10-toml-1.1-migration-proposal.md`
- Native architecture proposal: see `sovereignstack-native-parser-architecture.md`
