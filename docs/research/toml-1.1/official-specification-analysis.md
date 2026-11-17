# Official TOML Specification Repository — Analysis

**Document type:** Deliverable 2 of 15
**Repository:** https://github.com/toml-lang/toml
**Local clone:** `/home/z/my-project/research/toml-1.1/repos/toml-spec/`
**HEAD inspected:** `beacf57367482eeb6dcd6e14f6a234b4f1c38bcf` (2026-10-31)
**Tags inspected:** `1.0.0` (`8eae5e1c`), `1.1.0` (`bcbbd1c1`)
**Inspection date:** 2026-11-17

---

## 1. Repository Identity and Maturity

| Property | Value | Source |
|---|---|---|
| URL | https://github.com/toml-lang/toml | DOCUMENT-VERIFIED |
| Default branch | `main` | SOURCE-VERIFIED (git) |
| HEAD SHA | `beacf57367482eeb6dcd6e14f6a234b4f1c38bcf` | SOURCE-VERIFIED |
| HEAD commit date | 2026-10-31 | SOURCE-VERIFIED |
| HEAD subject | "Link to Wikipedia Base64 page, instead of some random ad-supported site" | SOURCE-VERIFIED |
| License | MIT (read LICENSE file, 1074 bytes) | SOURCE-VERIFIED |
| Maintenance status | Active — multiple commits in 2026; PRs #993, #1087, #1103, #1106, #1095 referenced in CHANGELOG "unreleased" section | SOURCE-VERIFIED |
| Stars / forks | Not inspected via API (rate-limited; not material to research) | UNVERIFIED |
| Open issues | Not inspected via API (rate-limited) | UNVERIFIED |

The repo contains **15 tracked files** at HEAD:

```
.gitattributes
.pre-commit-config.yaml
.prettierrc.toml
CHANGELOG.md
LICENSE
README.md
docs/README.md         (single subfile; the docs/ subdir is sparse)
logos/                 (PNG/SVG logos — not inspected)
scripts/               (build scripts — not material to spec)
toml.abnf              (247 lines — the ABNF grammar at HEAD)
toml.md                (1045 lines — the spec markdown at HEAD)
```

The repository is purely a **specification document**, not an implementation. There is no PHP/Go/Rust/Python code here. The single substantive artefact is `toml.md` (the human-readable spec) plus `toml.abnf` (the machine-readable grammar).

---

## 2. Source Architecture

The repo does not contain executable source. Its "architecture" is the document structure of `toml.md`. The spec is organised into the following sections (in order, verified by reading `toml-1.1.0.md`):

1. **Preliminaries** — case-sensitivity, whitespace definition, newline definition, UTF-8 requirement (clarified in 1.1.0).
2. **Comment** — `#` to end of line; control characters restricted; comments must not affect parser output (1.1.0 clarification).
3. **Key/Value Pair** — `key = value` structure.
4. **Keys** — bare keys (`[A-Za-z0-9_-]+`), quoted keys (`"..."` or `'...'`), dotted keys (`a.b.c`).
5. **String** — basic, multi-line basic, literal, multi-line literal. Escape sequences table (extended in 1.1.0).
6. **Integer** — decimal, hex (`0x`), octal (`0o`), binary (`0b`), underscores, sign.
7. **Float** — `intpart.fracpart`, exponent, `inf`, `nan`, sign.
8. **Boolean** — `true`, `false`.
9. **Offset Date-Time** — `YYYY-MM-DDTHH:MM:SSZ` or with numeric offset.
10. **Local Date-Time** — `YYYY-MM-DDTHH:MM:SS`.
11. **Local Date** — `YYYY-MM-DD`.
12. **Local Time** — `HH:MM:SS`.
13. **Array** — `[1, 2, 3]`, multi-line, trailing comma, mixed types allowed.
14. **Table** — `[table]`, nested, quoted, dotted.
15. **Inline Table** — `{key = value, ...}` (extended in 1.1.0).
16. **Array of Tables** — `[[items]]`.
17. **Filename Extension** — `.toml`.
18. **MIME Type** — `application/toml`.
19. **ABNF Grammar** — the formal grammar.

---

## 3. The ABNF Grammar

The ABNF file `toml.abnf` is the normative grammar reference. Key observations from inspecting both versions:

### 3.1 File size and structure

| Version | Lines | Bytes |
|---|---|---|
| 1.0.0 | 243 | 7,420 |
| 1.1.0 | 247 | 7,560 |

The ABNF is organised into clearly-labelled sections (Comment, Key-Value, String, Integer, Float, Boolean, Date-Time, Array, Table, Inline Table, Array Table). Comments in the ABNF use `;;`.

### 3.2 Notable grammar rules (1.1.0 version)

**Bare key:** `unquoted-key = 1*( ALPHA / DIGIT / %x2D / %x5F )` — letters, digits, hyphen, underscore. Non-empty. Non-ASCII bare keys are NOT in the ABNF (the 1.1.0 unreleased clarifications do not change this).

**Quoted key:** `quoted-key = basic-string / literal-string` — a quoted key is any valid basic or literal string (including empty, though discouraged).

**Dotted key:** `dotted-key = simple-key 1*( dot-sep simple-key )` — at least one dot-separated segment.

**String escape sequences (1.1.0):**

```
escape-seq-char =  %x22         ; "    quotation mark  U+0022
escape-seq-char =/ %x5C         ; \    reverse solidus U+005C
escape-seq-char =/ %x62         ; b    backspace       U+0008
escape-seq-char =/ %x65         ; e    escape          U+001B    [NEW IN 1.1.0]
escape-seq-char =/ %x66         ; f    form feed       U+000C
escape-seq-char =/ %x6E         ; n    line feed       U+000A
escape-seq-char =/ %x72         ; r    carriage return U+000D
escape-seq-char =/ %x74         ; t    tab             U+0009
escape-seq-char =/ %x78 2HEXDIG ; xHH                  U+00HH    [NEW IN 1.1.0]
escape-seq-char =/ %x75 4HEXDIG ; uHHHH                U+HHHH
escape-seq-char =/ %x55 8HEXDIG ; UHHHHHHHH            U+HHHHHHHH
```

**Inline table (1.1.0):**

```
inline-table = inline-table-open [ inline-table-keyvals ] ws-comment-newline inline-table-close
inline-table-open  = %x7B  ; {
inline-table-close = %x7D  ; }
inline-table-sep   = %x2C  ; , Comma
inline-table-keyvals =  ws-comment-newline keyval ws-comment-newline inline-table-sep inline-table-keyvals
inline-table-keyvals =/ ws-comment-newline keyval ws-comment-newline [ inline-table-sep ]
```

The `ws-comment-newline` rule (whitespace + comments + newlines) is reused inside inline tables, allowing them to span multiple lines and contain comments. A trailing comma is explicitly permitted by the second alternative (`[ inline-table-sep ]`).

**Partial time (1.1.0):**

```
partial-time = time-hour ":" time-minute [ ":" time-second [ time-secfrac ] ]
```

Seconds are now optional (the inner group `[ ":" time-second [ time-secfrac ] ]` is optional as a whole).

### 3.3 Things the ABNF does NOT cover (must be enforced in supporting text)

The ABNF preamble explicitly states:

> Although a TOML document must be valid UTF-8, this grammar refers to the Unicode Code Points you get after you decode the UTF-8 input.
>
> All valid TOML documents will match this description, however certain invalid documents would need to be rejected as per the semantics described in the supporting text description.

This means the ABNF alone is NOT a complete specification. A conformant parser must also enforce the semantic rules in `toml.md`. Examples of semantic rules NOT in the ABNF:

- Duplicate keys within a table are invalid.
- A table cannot redefine a sub-table created by a dotted key.
- A dotted key cannot redefine a table created by an explicit header.
- An array-of-tables cannot be defined after a non-array table with the same name.
- The `0x` prefix must be followed by at least one hex digit.
- The `0o` prefix must be followed by at least one octal digit (0–7).
- The `0b` prefix must be followed by at least one binary digit (0–1).
- Floats must have a digit before and after the decimal point (`1.0`, not `.0` or `1.`).
- Floats with exponents must have digits in the exponent (`1e10`, not `1e`).

These semantic rules must be implemented in the parser logic, not the lexer.

---

## 4. The Official Conformance Test Suite (`toml-test`)

The TOML specification repo does NOT contain the conformance tests. They live in a separate repository: https://github.com/toml-lang/toml-test.

A clone of `toml-test` is at `/home/z/my-project/research/toml-1.1/repos/toml-test/`. See `toml-conformance-analysis.md` for the full analysis. Summary:

- 535 valid test cases (TOML input + expected JSON output).
- 525 invalid test cases (TOML input that must be rejected).
- Tests are organised into `tests/valid/` and `tests/invalid/`, with 1.0.0-shared and 1.1.0-specific subdirectories.
- The runner is written in Go (requires Go 1.19+).
- The runner embeds tests via `go:embed` and invokes the parser-under-test as a subprocess that reads TOML from stdin and writes JSON to stdout.

---

## 5. Historical Change Rationale

The CHANGELOG.md records the historical reasoning behind each change. The four 1.1.0 substantive changes are each tied to a PR with a publicly-visible discussion thread:

- **#904 (multi-line inline tables)** — long-requested ergonomic improvement; aligned inline-table syntax with array syntax.
- **#796 (`\xHH` escape)** — useful for embedding binary data (byte values 0–255) without resorting to `\u00HH` (4 digits) or `\U000000HH` (8 digits).
- **#790 (`\e` escape)** — useful for encoding ANSI escape sequences (`\e[31m` for red text) without `\u001b`.
- **#894 (optional seconds)** — aligns with ISO 8601 practice where seconds are optional in some contexts.

The clarifying amendments (#929, #950, #859, #842, #805, #1058) address ambiguities raised by implementers in PR discussions and issue threads.

---

## 6. Key Insights for SovereignStack

1. **The ABNF is necessary but not sufficient.** A conformant parser must implement both the ABNF grammar and the semantic rules in `toml.md`. The SStack parser must not rely on the ABNF alone.

2. **The spec is the binding reference, not third-party summaries.** Several existing PHP TOML implementations misread the spec in places (see `devium-analysis.md`, `petalbranch-analysis.md`). SStack's parser must be validated against the actual `toml.md` text and `toml.abnf` grammar, not against another implementation's behaviour.

3. **The spec is mature and stable.** The 1.1.0 release is the first new spec version in nearly 5 years (1.0.0 was January 2021; 1.1.0 was December 2025). The rate of change is low, which means a parser implemented against 1.1.0 today is unlikely to need significant changes for several years.

4. **The `toml-test` suite is the authoritative conformance check.** A parser is not "TOML 1.1.0 conformant" unless it passes the official test suite. Selected unit tests passing is not sufficient evidence.

5. **The spec deliberately does not mandate performance.** There are no performance requirements in `toml.md`. Performance is an implementation quality, not a conformance criterion. SStack is free to set its own performance targets.

6. **The spec deliberately does not mandate error-message format.** The spec says invalid documents "must be rejected" but does not specify the exception type or message format. SStack is free to design its own exception hierarchy (per the approved D7 exception contract).

---

## 7. Evidence Register

| File inspected | Path | Lines | Commit/tag |
|---|---|---|---|
| README.md | `toml-spec/README.md` | 3,054 bytes | HEAD |
| CHANGELOG.md | `toml-spec/CHANGELOG.md` | 5,770 bytes | HEAD |
| LICENSE | `toml-spec/LICENSE` | 1,074 bytes | HEAD |
| toml.md (HEAD = 1.1.0 + unreleased) | `toml-spec/toml.md` | 1,045 lines | HEAD |
| toml.md (1.0.0) | git show 1.0.0:toml.md | 1,010 lines | tag 1.0.0 |
| toml.md (1.1.0) | git show 1.1.0:toml.md | 1,045 lines | tag 1.1.0 |
| toml.abnf (HEAD) | `toml-spec/toml.abnf` | 247 lines | HEAD |
| toml.abnf (1.0.0) | git show 1.0.0:toml.abnf | 243 lines | tag 1.0.0 |
| toml.abnf (1.1.0) | git show 1.1.0:toml.abnf | 247 lines | tag 1.1.0 |
| .pre-commit-config.yaml | `toml-spec/.pre-commit-config.yaml` | 442 bytes | HEAD |
| .prettierrc.toml | `toml-spec/.prettierrc.toml` | 21 bytes | HEAD |

Full version-difference matrix: see `toml-version-comparison.md`.
Conformance suite analysis: see `toml-conformance-analysis.md`.
