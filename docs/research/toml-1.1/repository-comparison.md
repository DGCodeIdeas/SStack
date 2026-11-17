# Repository Comparison — Devium, php-collective, PetalBranch

**Document type:** Deliverable 6 of 15
**Inspection date:** 2026-11-17
**Basis:** Direct source inspection of all three repositories. Per-repo detailed analyses: `devium-analysis.md`, `php-collective-analysis.md`, `petalbranch-analysis.md`.

---

## 1. At-a-Glance Comparison

| Property | Devium | php-collective | PetalBranch |
|---|---|---|---|
| URL | github.com/vanodevium/toml | github.com/php-collective/toml | github.com/PetalBranch/toml |
| Package name | `devium/toml` | `php-collective/toml` | `petalbranch/toml` |
| License | MIT | MIT | Apache-2.0 |
| PHP version | >=8.2 | >=8.2 | ^8.3 |
| HEAD date | 2026-11-16 (v1.1.1) | 2026-11-11 | 2026-03-22 |
| HEAD SHA | `59c82e8a` | `9224aa0c` | `d8553b06` |
| Repo size (GitHub) | 90 KB | 377 KB | 2,015 KB |
| File count (clone) | 59 | 190 | 64 |
| Maintainers | 1 | 1 (Mark Scherer) | 1 |
| Activity in last 30 days | Yes (very active) | Yes (active) | No (last commit 8 months ago) |
| Runtime deps | `php-ds/php-ds`, `symfony/polyfill-mbstring` | `symfony/polyfill-mbstring` | `ext-json`, `ext-mbstring` (extensions only) |
| Dev deps | pest, phpstan, rector, pint | phpunit, phpstan, code-sniffer | phpunit, phpstan |
| Test framework | Pest (11 cases) | PHPUnit | PHPUnit (5 cases) |
| PHPStan level | 0 (default) | unspecified | 9 |
| CI | Yes (.github/workflows) | Yes (.github/workflows) | **No** (no .github/workflows) |
| Open issues | UNVERIFIED (API rate-limited) | UNVERIFIED | UNVERIFIED |
| Encoder support | Yes (encode + decode) | Yes | Yes |
| AST exposure | No (returns PHP array) | Yes (`Document` AST) | No (returns PHP array) |
| Advertised 1.1.0 support | Yes (README claims) | Yes | Yes |
| Verified 1.1.0 support | **NO** (4 features missing) | UNVERIFIED in this analysis | **YES** (4 features verified in source) |
| Marketing vs reality | Mismatch (claims 1.1.0 but tests 1.0) | Aligned | Mismatch (claims "zero-copy" but copies) |

---

## 2. Architecture Comparison

### 2.1 Lexer Architecture

| Property | Devium | php-collective | PetalBranch |
|---|---|---|---|
| Lexer class | `TomlTokenizer` + `TomlInputIterator` | Lexer class (token generator) | `Lexer` |
| Approach | Byte-level cursor (`TomlInputIterator`) | Generator-based (yields tokens) | Character-by-character cursor |
| Token storage | `Ds\Set` for key tracking | Token objects | `Token` objects with `string $lexeme` |
| Lookahead | Single character via `peek()` | Generator lookahead | Manual cursor manipulation |
| Unicode handling | Byte-level + mbstring polyfill | mbstring polyfill | `mb_*` functions |
| Source position | Line + column | Line + column + byte offset | Line + column |
| Zero-copy | No (builds strings) | No (uses generators but materializes) | **No — despite README claim** (builds `$lexeme .= $char` per character; SOURCE-VERIFIED at `Lexer.php:454,500,666,790`) |
| Streaming | No | Generator-based lexer BUT parser materializes via `iterator_to_array` (`Parser.php:81`) — effectively NOT streaming | No (LazyTokenStream buffers all tokens) |

**Key observation:** None of the three implementations actually achieve streaming or zero-copy parsing. php-collective comes closest with its generator-based lexer, but the parser immediately materializes the token stream.

### 2.2 Parser Architecture

| Property | Devium | php-collective | PetalBranch |
|---|---|---|---|
| Parser class | `TomlParser` (in `Parser/TomlParser.php`) | `Parser` | `Parser` |
| Approach | Recursive descent | Recursive descent | Recursive descent |
| AST | Yes (`Nodes/*` classes) | Yes (13 node classes + 5 enums) | Yes (Node classes) |
| AST mutability | Mutable | Mutable (public properties) | Mutable |
| Table construction | `TomlKeystore` with `Ds\Set` and `.[N]` path scheme | Direct AST manipulation | Direct AST manipulation |
| Duplicate-key detection | `TomlKeystore` membership check | AST-level check | AST-level check |
| Nested AoT handling | **Has a bug** — prefix-conflict pattern fixed in `addArrayTableNode` (2026-11-16) but same bug persists in `addTableNode` line 137 (SOURCE-VERIFIED) | Correct (verified by AST traversal logic) | Correct |
| Inline-table parsing | Single-line only (1.0.0) | Multi-line (1.1.0) supported | Multi-line (1.1.0) supported (`Parser.php:374`) |
| Error recovery | None (throws on first error) | **Two-strategy recovery**: `synchronize()` (line-oriented) + `synchronizeInCollection()` (depth-aware) | None |

**Key observation:** php-collective's error recovery is the most sophisticated — it can collect multiple parse errors in a single pass. This is its headline feature ("collected parse errors" in the package description).

### 2.3 Value Representation

| Value type | Devium | php-collective | PetalBranch |
|---|---|---|---|
| String | PHP `string` | PHP `string` (in `StringNode`) | PHP `string` |
| Integer | PHP `int` | PHP `int` (in `IntegerNode`) | PHP `int` |
| Float | PHP `float` | PHP `float` (in `FloatNode`) | PHP `float` |
| Boolean | PHP `bool` | PHP `bool` (in `BooleanNode`) | PHP `bool` |
| Array | PHP `array` (list) | PHP `array` (in `ArrayNode`) | PHP `array` |
| Table | PHP `array` (assoc) | PHP `array` (in `TableNode`) | PHP `array` |
| Inline table | PHP `array` (assoc) | `InlineTableNode` | PHP `array` (assoc) |
| **Local datetime** | PHP `DateTimeImmutable` (correctly **naive** — SOURCE-VERIFIED) | **String preserved** (correctly timezone-neutral) | **String** in AST (lost type info) → round-trip bug |
| Offset datetime | `DateTimeImmutable` | `DateTimeImmutable` | `DateTimeImmutable` |
| Local date | `DateTimeImmutable` | String preserved | String |
| Local time | `DateTimeImmutable` | String preserved | String |

**Critical observation:** The handling of local datetime/date/time values differs significantly. Only Devium and php-collective preserve the timezone-neutrality of local datetimes (Devium by using `DateTimeImmutable` correctly, php-collective by keeping the value as a string). PetalBranch has a **verified round-trip bug** for datetimes.

### 2.4 Error Handling

| Property | Devium | php-collective | PetalBranch |
|---|---|---|---|
| Exception hierarchy | `TomlException` + subclasses | `ParseError` struct + `ParseException` | `ParseException` + `ParseErrorType` enum |
| Error code system | None (message-only) | `ParseErrorCode` enum (16 stable codes) | `ParseErrorType` enum |
| Source location | Line + column | Line + column + byte offset (`Span`) | Line + column |
| Expected-token info | No | Yes (in hints) | Yes (in error type) |
| Previous-exception chaining | No | **No** (ParseException lacks `$previous`) | No |
| Error recovery | None | **Yes** (collects multiple errors) | None |
| Error-message localisation | None | Hints are English | Global mutable `$detailedErrorOutput` flag (concerning design) |

**Key observation:** php-collective's error model is the most sophisticated and most directly applicable to SStack. The `ParseError` struct pattern (message + span + hint + code) with a stable enum of error codes is a clean design that SStack could adopt.

---

## 3. TOML 1.1.0 Support — Verified

| Feature | Devium | php-collective | PetalBranch |
|---|---|---|---|
| Multi-line inline tables (#904) | **MISSING** | UNVERIFIED | **VERIFIED** (`Parser.php:374`) |
| Trailing comma in inline tables (#904) | **MISSING** | UNVERIFIED | **VERIFIED** (per `inline-table-keyvals =/ ... [ inline-table-sep ]` in parser logic) |
| `\xHH` escape (#796) | **MISSING** | UNVERIFIED | **VERIFIED** (`Lexer.php:552`) |
| `\e` escape (#790) | **MISSING** | UNVERIFIED | **VERIFIED** (`Lexer.php:549`) |
| Optional seconds in time/datetime (#894) | **MISSING** | UNVERIFIED | UNVERIFIED |
| UTF-8 well-formedness check (#929) | **MISSING** (lone `\r` not normalised; surrogates not rejected per Devium analysis) | UNVERIFIED | UNVERIFIED |

**Key observation:** Devium's claim of "compatible with 1.0.0 and 1.1.0" is **marketing, not substance** (SOURCE-VERIFIED in `devium-analysis.md`). PetalBranch has verified support for 4 of the 5 substantive 1.1.0 features.

---

## 4. Architectural Ideas Borrowable for SStack

The directive §9 requires identifying which architectural ideas are useful for a native SStack implementation, without copying code.

### 4.1 From Devium

| Idea | Utility for SStack |
|---|---|
| Byte-level cursor pattern (`TomlInputIterator`) | Medium — clean abstraction for character consumption; SStack's current parser already does this inline |
| `TomlKeystore` for duplicate-key tracking with `.[N]` path scheme | Low — SStack can solve this with simpler array-path tracking |
| AST node classes (`Nodes/*`) | Low — php-collective's AST is more sophisticated |
| Correct naive `DateTimeImmutable` for local datetime | **HIGH** — confirms that PHP's `DateTimeImmutable` CAN be used for local datetimes without timezone coercion (this is a verified pattern) |

### 4.2 From php-collective

| Idea | Utility for SStack |
|---|---|
| **`ParseError` struct pattern** (message + span + hint + code) | **HIGH** — clean error model directly applicable to SStack |
| **`ParseErrorCode` enum** (16 stable codes) | **HIGH** — provides a typed error code namespace |
| **Two-strategy error recovery** (`synchronize()` + `synchronizeInCollection()`) | Medium — useful for collecting multiple errors in one parse, but adds complexity; SStack could adopt a simpler single-error model first |
| **Local datetime preserved as string** | **HIGH** — alternative to Devium's approach; preserves full precision and avoids any DateTimeImmutable timezone ambiguity |
| "Original X" AST pattern (carries both live value and original source for re-encoding) | Low — SStack does not need encoder support in CORE-10 |
| AST node taxonomy (13 classes) | Medium — useful reference, but SStack does not need to expose AST publicly |
| Generator-based lexer | Low — adds complexity without clear benefit for SStack's typical config-file sizes |
| Contextual hints (YAML `:` vs `=`, version-string quoters, unquoted strings) | **HIGH** — significantly improves error-message actionability |

### 4.3 From PetalBranch

| Idea | Utility for SStack |
|---|---|
| Byte-cursor UTF-8 probe (decode codepoint at byte position) | Medium — useful for Unicode-aware lexing |
| `ParseErrorType` enum with `mb_strwidth`-aware error pointer | Medium — `mb_strwidth` produces visually-aligned error carets for CJK characters |
| `SplObjectStorage` for dotted-key table tracking | Low — overcomplicated for SStack's needs |
| `TomlSerializableInterface` marker for encoder | Low — SStack does not need encoder |
| PHPStan Level 9 discipline | **HIGH** — confirms that level-max static analysis is achievable for a TOML parser |
| `\e` and `\xHH` escape implementations | **HIGH** — reference implementations for the 1.1.0 escape extensions |

### 4.4 What NOT to borrow

- Devium's `php-ds/php-ds` runtime dependency (violates SStack policy)
- Devium's `TomlKeystore` `.[N]` path scheme (overcomplicated)
- php-collective's mutable AST with public properties (incompatible with SStack's immutability discipline)
- php-collective's `iterator_to_array` materialization of generator tokens (defeats streaming)
- PetalBranch's "zero-copy" framing (not actually implemented)
- PetalBranch's string-based datetime storage in AST (causes round-trip bug)
- PetalBranch's global mutable `ParseException::$detailedErrorOutput` flag (anti-pattern)
- PetalBranch's lack of CI (SStack must have CI)

---

## 5. Recommendation Summary

1. **None of the three repositories should be adopted as a SStack runtime dependency** — all have at least one disqualifying issue:
   - Devium: runtime deps violate SStack policy + 1.1.0 claim is marketing
   - php-collective: AST is mutable + encoder-focused (more than SStack needs)
   - PetalBranch: no CI + "zero-copy" claim is false + 8 months inactive

2. **The most borrowable ideas come from php-collective** — its `ParseError` struct + `ParseErrorCode` enum + error recovery + string-preserved datetime + contextual hints are the strongest architectural patterns.

3. **The correct datetime handling patterns are confirmed by both Devium and php-collective** — Devium uses `DateTimeImmutable` correctly (naive, no timezone coercion); php-collective preserves as string. SStack should choose ONE approach and document it. Recommendation: **use `DateTimeImmutable` for offset datetimes; preserve local datetimes as a value object** (combining both approaches — see `sovereignstack-native-parser-architecture.md`).

4. **PetalBranch's verified 1.1.0 escape implementations** (`\e`, `\xHH`) provide a reference for the new escape sequence decoders SStack needs to add.

5. **The official `toml-test` suite** is the binding conformance check, not any of these three implementations.

---

## 6. Cross-References

- Devium deep analysis: `devium-analysis.md`
- php-collective deep analysis: `php-collective-analysis.md`
- PetalBranch deep analysis: `petalbranch-analysis.md`
- Lexer/parser architecture deep comparison: `lexer-parser-architecture-comparison.md`
- Native SStack architecture proposal: `sovereignstack-native-parser-architecture.md`
