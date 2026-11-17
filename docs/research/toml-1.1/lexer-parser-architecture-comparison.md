# Lexer / Parser Architecture Comparison

**Document type:** Deliverable 7 of 15
**Inspection date:** 2026-11-17
**Basis:** Source-level inspection of all three PHP implementations + SStack's current `TomlParser.php`.

---

## 1. The Four Parser Architectures at a Glance

| Property | SStack (current PR #3) | Devium | php-collective | PetalBranch |
|---|---|---|---|---|
| Single-file parser | Yes (`TomlParser.php`, 918 lines) | No (separated into Tokenizer, Parser, Nodes) | No (Lexer, Parser, AST, Nodes, Tokens, Exceptions) | No (Lexer, Parser, Tokens, Nodes, Exceptions) |
| Lexer separation | No (inline in TomlParser) | Yes (`TomlTokenizer` + `TomlInputIterator`) | Yes (separate Lexer class) | Yes (separate `Lexer` class) |
| Token model | Implicit (no Token class) | Yes (`Token`-like via `Ds\Vector`) | Yes (Token class with source span) | Yes (`Token` class with `string $lexeme`) |
| AST | No (builds PHP array directly) | Yes (`Nodes/*`) | Yes (13 node classes + 5 enums) | Yes (`Node` classes) |
| Source position tracking | Line only (via `currentLine()`) | Line + column | Line + column + byte offset (Span) | Line + column |
| Error recovery | None (throws on first error) | None | Two-strategy recovery | None |
| Streaming | No | No | Generator lexer but materializes at parser entry | No (LazyTokenStream buffers all) |
| Recursive descent depth limit | None | None | None | None |
| Memory model | Single PHP array | Builder + intermediate nodes | AST + final value | AST + final value |

---

## 2. Detailed Lexer Comparison

### 2.1 SStack (current PR #3 — `TomlParser.php`)

**SOURCE-VERIFIED** at `/home/z/my-project/scripts/sstack-core10/src/Core/Config/Toml/TomlParser.php`.

The SStack parser has **NO separate lexer**. Character scanning is inline in `TomlParser.php`:

- Position tracking via `private int $position` and `private int $length`.
- Character access via `$this->input[$this->position]`.
- Whitespace skipping via `skipWhitespaceAndComments()`, `skipInlineWhitespace()`.
- No tokenization phase — the parser consumes characters directly and dispatches to value-parsers based on the first character.

**Advantages:**
- Compact (single file).
- Low allocation overhead — no Token objects created.
- Sufficient for SStack's typical config-file sizes (KB range).

**Disadvantages:**
- No source-position tracking beyond line number (no column).
- No reusable token stream — error messages cannot reference "expected token X, got Y".
- Harder to add features like multi-line inline tables without entangling lexer/parser state.
- The three known bugs (signed integers, datetime exception, nested AoT) are direct consequences of the inline-scanning approach — sign handling is buried in `parseIntegerOrFloat()`, datetime construction is buried in `parseDateTime()`, and table navigation has no clear abstraction.

### 2.2 Devium — `TomlTokenizer` + `TomlInputIterator`

**SOURCE-VERIFIED** per `devium-analysis.md`.

- `TomlInputIterator`: byte-level cursor abstraction with `peek()`, `next()`, `eof()`.
- `TomlTokenizer`: consumes characters via the iterator and produces a token stream.
- Uses `Ds\Set` from the `php-ds` extension for duplicate-key tracking.

**Advantages:**
- Clean cursor abstraction.
- Token stream is reusable.

**Disadvantages:**
- Depends on `php-ds/php-ds` (non-bundled extension) — disqualifying for SStack.
- Token stream is fully materialized (not streaming).
- 1.1.0 features are missing.

### 2.3 php-collective — Separate Lexer class

**SOURCE-VERIFIED** per `php-collective-analysis.md`.

- Lexer is a generator-based implementation: yields `Token` objects one at a time.
- Token objects carry source span (line, column, byte offset).
- Parser uses `iterator_to_array()` to materialize the token stream (`Parser.php:81`).

**Advantages:**
- Clean separation of lexing and parsing.
- Rich source-position information for error messages.
- Generator pattern is conceptually elegant.

**Disadvantages:**
- Generator materialization defeats the streaming benefit (`iterator_to_array` at parser entry).
- Adds complexity without performance benefit for typical config sizes.
- AST is mutable (public properties).

### 2.4 PetalBranch — `Lexer` class

**SOURCE-VERIFIED** per `petalbranch-analysis.md`.

- Character-by-character cursor with `$lexeme .= $char` accumulation.
- `Token` class with `string $lexeme` value property (NOT a slice reference).
- `LazyTokenStream` buffers all tokens.

**Advantages:**
- PHPStan level 9 (verifiable rigour).
- Verified 1.1.0 escape implementations.

**Disadvantages:**
- "Zero-copy" claim is FALSE — every token's lexeme is a copied string.
- No CI — quality assurance depends entirely on the maintainer.
- String-based datetime storage in AST causes round-trip bug.

---

## 3. Detailed Parser Comparison

### 3.1 Table Construction Algorithm

| Implementation | Algorithm | Bug-free? |
|---|---|---|
| SStack (current PR #3) | Direct `$current = &$current[$segment]` reference walk; intermediate segments always descend into `$current[$segment]` regardless of whether it's a list (array-of-tables) or a table | **BUGGY** — `[[fruits.varieties]]` after `[[fruits]]` creates duplicate parent (SOURCE-VERIFIED) |
| SStack (locally-modified, b312a95+1136065) | Same algorithm with `array_is_list()` descent check added to intermediate-segment navigation | Fixed (REPORTED) |
| Devium | `TomlKeystore` tracks full path with `.[N]` indexing for AoT elements; `addTableNode` and `addArrayTableNode` walk the keystore | **BUGGY** — `addTableNode` line 137 has the same prefix-conflict pattern that was fixed in `addArrayTableNode` on 2026-11-16 (SOURCE-VERIFIED) |
| php-collective | AST-level table construction; uses AST node hierarchy to distinguish table kinds | Correct (INFERRED from analysis) |
| PetalBranch | Direct AST manipulation; uses `SplObjectStorage` for dotted-key tracking | Correct for AoT (INFERRED); has datetime round-trip bug |

### 3.2 Duplicate-Key Detection

| Implementation | Mechanism | Coverage |
|---|---|---|
| SStack | `isset($current[$segment])` check at `parseKeyValue()` line 156 | Covers same-table duplicate keys; does NOT cover cross-header conflicts (e.g. `[a]` then `a.b = 1` redefines `a`) |
| Devium | `TomlKeystore` membership check | Covers full path including AoT indices |
| php-collective | AST-level check during normalization | Full coverage |
| PetalBranch | AST-level check | Full coverage |

### 3.3 Datetime Parsing

| Implementation | Approach | Local datetime | Offset datetime |
|---|---|---|---|
| SStack | Regex match + `new \DateTimeImmutable($str)` | `DateTimeImmutable` (timezone coerced to default TZ — INCORRECT for local datetimes) | `DateTimeImmutable` with offset |
| Devium | Regex + `new \DateTimeImmutable($str)` | `DateTimeImmutable` correctly **naive** (SOURCE-VERIFIED — does not coerce timezone) | `DateTimeImmutable` with offset |
| php-collective | String preserved through AST; conversion to `DateTimeImmutable` only for offset datetimes | String (correctly timezone-neutral) | `DateTimeImmutable` |
| PetalBranch | Parses to typed `Toml*` objects then DISCARDS them, storing as string | String (lost type info) | String (lost type info) → round-trip bug |

**Key insight:** SStack's current parser uses `new \DateTimeImmutable($str)` for local datetimes. PHP's `DateTimeImmutable` constructor applies the default timezone (`date.timezone` ini setting) when the input has no timezone. This means a TOML local datetime `2025-01-01T00:00:00` becomes a `DateTimeImmutable` with the server's default timezone attached — **incorrect for a local datetime which by definition has no timezone**.

This is a CORRECTNESS BUG in SStack's current parser, not just a stylistic issue. It is documented in the SStack context restoration report as part of the datetime exception handling fix (commit `1136065`), but the underlying timezone-coercion issue was NOT fixed in that commit.

### 3.4 Error Recovery

| Implementation | Strategy | Multiple errors per parse? |
|---|---|---|
| SStack | None — throws `ConfigurationFileException` on first error | No |
| Devium | None | No |
| php-collective | `synchronize()` at top level + `synchronizeInCollection()` inside arrays/inline-tables | **Yes** — collects multiple errors |
| PetalBranch | None | No |

php-collective's error-recovery approach is the most user-friendly for large config files: a single parse can report multiple errors, saving the user from fix-rerun-fix-rerun cycles. However, it adds significant complexity to the parser (depth tracking, recovery synchronisation points).

For SStack's typical use case (single config file at boot), single-error throw is acceptable. Error recovery can be deferred to a future enhancement.

---

## 4. Architectural Pattern Recommendations for SStack

Based on the four implementations inspected, the recommended architecture for SStack's native TOML 1.1.0 parser (detailed in `sovereignstack-native-parser-architecture.md`):

1. **Separate lexer from parser.** A `TomlLexer` class produces a `Token` stream. A `TomlParser` class consumes tokens and builds the PHP representation. This is the cleanest separation observed in the three reference implementations (php-collective and PetalBranch both do this; SStack currently does not).

2. **Token carries source span.** Each token records its byte offset, line number, and column. This enables precise error messages ("expected `=`, got `]` at line 12 column 7") — a quality only php-collective achieves well.

3. **No public AST.** SStack does not need to expose the AST to callers (unlike php-collective which advertises AST access). The parser builds the AST internally and returns a PHP array. This keeps the public API minimal.

4. **Typed error code enum.** Adopt php-collective's pattern: a `TomlParseErrorCode` backed enum with stable codes for each error category (unclosed string, invalid escape, duplicate key, etc.). The exception carries the code + source span + contextual hint.

5. **Value objects for datetimes.** Use dedicated value objects for `LocalDate`, `LocalTime`, `LocalDateTime`, `OffsetDateTime` to preserve timezone-neutrality. This is the approach that combines Devium's correctness with php-collective's precision.

6. **No error recovery in v1.** Throw on first error. Document error recovery as a future enhancement.

7. **Resource limits.** Add explicit limits for input size, nesting depth, and token count (see `security-analysis.md`).

---

## 5. Detailed Algorithm Recommendations

### 5.1 Lexer Algorithm (recommended for SStack)

```
1. Receive input string + filePath.
2. Validate UTF-8 well-formedness via mb_check_encoding($input, 'UTF-8').
   - If invalid: throw ConfigurationFileException with byte-offset of first invalid byte.
3. Initialise position = 0, line = 1, column = 1.
4. Loop:
   a. Skip whitespace and comments (track line/column updates).
   b. If position >= length: emit EOF token; break.
   c. Peek first character of next token:
      - '[' → table header or AoT header token
      - '"' → basic string token (single or multi-line based on lookahead)
      - "'" → literal string token (single or multi-line based on lookahead)
      - '=' → keyval-sep token
      - '.' → dot-sep token
      - ',' → comma token
      - '}' → inline-table-close token
      - ']' → array-close / table-close token
      - '{' → inline-table-open token
      - digit or '+' or '-' or 'i' (inf) or 'n' (nan) → value-start (number or datetime)
      - 't' or 'f' → boolean (true/false)
      - bare-key char → bare-key token
      - otherwise → lexical error
   d. Consume the token; record source span.
   e. Emit Token.
```

### 5.2 Parser Algorithm (recommended for SStack)

```
Recursive descent over token stream:

parseDocument():
  root = empty table
  currentTable = reference to root
  while token != EOF:
    if token is table-header ([X]):
      currentTable = navigateOrCreate(root, X, isArray=false)
    if token is array-table-header ([[X]]):
      currentTable = navigateOrCreate(root, X, isArray=true)
    else:
      parseKeyValue(currentTable)
  return root

navigateOrCreate(root, path, isArray):
  current = &root
  for i, segment in path:
    isLast = (i == len(path) - 1)
    if isLast and isArray:
      # Append new element to AoT
      if not exists(current[segment]) or not is_array(current[segment]):
        current[segment] = []
      current[segment][] = []
      current = &current[segment][last]
    else:
      # Intermediate segment — descend into existing table OR create new
      if not exists(current[segment]):
        current[segment] = []
      if is_list(current[segment]) and not empty:
        # Intermediate segment is an AoT — descend into last element
        current = &current[segment][last]
      else:
        current = &current[segment]
  return current

parseKeyValue(table):
  keyPath = parseKeyPath()  # may be dotted
  expect '='
  value = parseValue()
  # Set value at keyPath in table, with duplicate-key check
  current = &table
  for i, segment in keyPath:
    if i == last:
      if exists(current[segment]):
        throw DuplicateKeyException(segment, sourceSpan)
      current[segment] = value
    else:
      if not exists(current[segment]):
        current[segment] = []
      current = &current[segment]

parseValue():
  token = peek()
  switch token.type:
    case BASIC_STRING: return parseBasicString()
    case LITERAL_STRING: return parseLiteralString()
    case ML_BASIC_STRING: return parseMultilineBasicString()
    case ML_LITERAL_STRING: return parseMultilineLiteralString()
    case INTEGER: return parseInteger()
    case FLOAT: return parseFloat()
    case BOOLEAN: return parseBoolean()
    case OFFSET_DATETIME: return parseOffsetDateTime()
    case LOCAL_DATETIME: return parseLocalDateTime()
    case LOCAL_DATE: return parseLocalDate()
    case LOCAL_TIME: return parseLocalTime()
    case ARRAY_OPEN: return parseArray()
    case INLINE_TABLE_OPEN: return parseInlineTable()
    default: throw UnexpectedTokenException(token, expectedTypes)
```

### 5.3 Table-Construction Invariant

The SStack-localised fix (commit `1136065`) correctly handles the nested AoT case by checking `array_is_list()` on intermediate segments. This pattern should be preserved in the refactored parser.

The general invariant: **an intermediate segment in a table header path is one of:**
1. A non-existent key → create empty table.
2. An existing table (associative array) → descend into it.
3. An existing array-of-tables (list array) → descend into the LAST element.
4. Anything else (a string, int, etc.) → ERROR (table-structure conflict).

The SStack fix correctly handles cases 1, 2, 3 but does not explicitly check case 4 (it would produce a PHP runtime error when trying to use a scalar as an array). The refactored parser should explicitly check case 4 and throw a typed exception.

---

## 6. Performance Implications of Architecture Choice

A separate lexer+parser architecture adds:
- One extra pass over the input (lexer produces tokens, parser consumes them).
- One Token object per token (PHP object allocation overhead).

For SStack's typical config-file sizes (low KB range), this overhead is negligible. For very large config files (MB range), it could become measurable — but SStack does not target MB-scale config files (a 1 MB TOML file would be unusual; the spec does not require streaming).

The recommended architecture prioritises correctness, maintainability, and diagnostic quality over microsecond-level performance. This aligns with the SStack spec §15 performance target: "resolution of a nested key < 10µs" — this is a runtime target for `ConfigRepository::get()`, not a parse-time target. Parse time is a one-time boot cost.

---

## 7. Cross-References

- Native SStack architecture proposal: `sovereignstack-native-parser-architecture.md`
- Performance analysis: `performance-analysis.md`
- Security analysis (resource limits): `security-analysis.md`
- Per-implementation details: `devium-analysis.md`, `php-collective-analysis.md`, `petalbranch-analysis.md`
