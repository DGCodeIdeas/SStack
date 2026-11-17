# php-collective TOML — Technical Analysis

**Repository:** https://github.com/php-collective/toml
**Inspection date:** 2026-11-17
**HEAD commit:** 9224aa0c80972d8e10315c7493deed2fd4eeae4a
**License:** MIT
**Evidence classification tags used throughout: SOURCE-VERIFIED / DOCUMENT-VERIFIED / INFERRED / UNVERIFIED**

> All claims cite file path + class/method + line numbers actually read during this investigation. Direct code quotes are ≤10 lines. README claims are only repeated after source verification.

---

## 1. Repository Identity and Maturity

| Item | Value | Evidence |
|------|-------|----------|
| URL | https://github.com/php-collective/toml | task brief |
| Default branch | `master` | SOURCE-VERIFIED — `git branch -a` shows `* master`, `remotes/origin/HEAD -> origin/master` |
| Latest commit | `9224aa0c…` "Build the docs on pull requests (#38)" | SOURCE-VERIFIED — `git log -1` |
| Pushed date | 2026-11-11 | SOURCE-VERIFIED — `git log -1 --format=%ad` |
| First commit | 2026-03-25 | SOURCE-VERIFIED — `git log --reverse` |
| Total commits | 50 | SOURCE-VERIFIED — `git log --oneline | wc -l` |
| Tags | `0.1.0` through `0.1.5` (6 tags) | SOURCE-VERIFIED — `git tag -l` |
| Latest tag | `0.1.5` (2026-05-31) | SOURCE-VERIFIED |
| Contributors | 1 (Mark Scherer, also as `mscherer`) | SOURCE-VERIFIED — `git shortlog -s -n` shows 34+16 commits by the same human |
| License | MIT, "Copyright (c) 2026 PHP Collective" | SOURCE-VERIFIED — `LICENSE:1-2` |
| PHP requirement | `>=8.2` | SOURCE-VERIFIED — `composer.json:21` |
| Runtime deps | `symfony/polyfill-mbstring: ^1.30` only | SOURCE-VERIFIED — `composer.json:22` |
| Dev deps | `phpunit/phpunit ^11.5||^12.5||^13.0`, `phpstan/phpstan ^2.0`, `php-collective/code-sniffer dev-master` | SOURCE-VERIFIED — `composer.json:25-27` |
| Suggested ext | `ext-ds` ("For improved performance with Set-based key tracking") | SOURCE-VERIFIED — `composer.json:30` |
| Test framework | PHPUnit (phpunit.xml present) | SOURCE-VERIFIED |
| Static analysis | PHPStan level 8 | SOURCE-VERIFIED — `phpstan.neon:1-2` |
| Code style | PHPCS with `PhpCollective` ruleset + Slevomat trailing comma rules | SOURCE-VERIFIED — `phpcs.xml:14-17` |
| CI matrix | PHP 8.2 and 8.4 on ubuntu-latest | SOURCE-VERIFIED — `.github/workflows/ci.yml:13-14` |
| CI gates | PHPUnit + PHPStan + PHPCS, plus pinned `toml-test` v2.2.0 corpus clone | SOURCE-VERIFIED — `.github/workflows/ci.yml:30-36` |
| Open issues | UNVERIFIED — GitHub REST API returned rate-limit error during investigation |

**Maintenance cadence:** SOURCE-VERIFIED — All 50 commits fall in three bursts: 25 March 2026 (initial dump, ~25 commits), 27-30 May 2026 (~22 commits including the 0.1.x releases), and 11 November 2026 (single docs PR). Eight months of history; effectively one active developer.

**Maturity assessment:** INFERRED — Project is young (8 months, single maintainer, 0.1.x versioning), but has aggressive conformance gating (toml-test v2.2.0 pinned in CI), PHPStan level 8, and a broad test suite (see §11). Production-grade engineering, but not battle-tested at scale.

---

## 2. Source Architecture (component diagram)

```
                      ┌──────────────┐
                      │   Input str  │
                      └──────┬───────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  Lexer  (src/Lexer/Lexer.php) │
              │  stateful, char-by-char       │
              │  Generator<Token>             │
              └──────────────┬───────────────┘
                             │ (materialized via iterator_to_array)
                             ▼
              ┌──────────────────────────────┐
              │  Parser (src/Parser/Parser.php)│
              │  recursive descent             │
              │  error-recovering              │
              └──────────────┬───────────────┘
                             │
                  Document (AST, src/Ast/Document.php)
                  + $parser->getErrors() : ParseError[]
                             │
                             ▼
              ┌──────────────────────────────┐
              │ Normalizer (src/Normalizer.php)│
              │ AST → array<string,mixed>      │
              │ semantic validation            │
              └──────────────┬───────────────┘
                             │
                array + $normalizer->getErrors()

  Public API (src/Toml.php):
    Toml::decode()       throw-first
    Toml::decodeFile()   throw-first
    Toml::tryParse()     → ParseResult { Document?, ParseError[], array? }
    Toml::parse()        → Document (with optional trivia)
    Toml::encode()       array → string
    Toml::encodeDocument() Document → string
    Toml::encodeFile() / encodeDocumentFile()

  Encoder (src/Encoder/Encoder.php):
    array → TOML       (canonical, options-driven)
    Document → TOML    (Normalized | SourceAware)
```

**SOURCE-VERIFIED — file inventory** (`src/` tree):
```
src/
├── Toml.php                       (facade, 142 lines)
├── TomlVersion.php                (enum V10/V11, 11 lines)
├── Normalizer.php                 (AST→array + semantic validation, 629 lines)
├── Lexer/
│   ├── Lexer.php                  (875 lines)
│   ├── Token.php                  (readonly struct, 21 lines)
│   ├── TokenType.php              (enum, 22 cases, 44 lines)
│   └── Span.php                   (readonly struct, 31 lines)
├── Parser/
│   ├── Parser.php                 (1346 lines)
│   ├── ParseError.php             (readonly + format(), 55 lines)
│   ├── ParseResult.php            (readonly aggregate, 48 lines)
│   └── ParseErrorCode.php         (string-backed enum, 16 codes, 101 lines)
├── Ast/
│   ├── Node.php                   (interface, 36 lines)
│   ├── AbstractNode.php           (base, 55 lines)
│   ├── Document.php               (root, 20 lines)
│   ├── Table.php                  (28 lines)
│   ├── KeyValue.php               (22 lines)
│   ├── Key.php                    (38 lines)
│   ├── KeyStyle.php               (enum, 12 lines)
│   ├── Trivia.php                 (17 lines)
│   ├── TriviaKind.php             (enum, 12 lines)
│   └── Value/
│       ├── Value.php              (interface: getValue())
│       ├── AbstractValue.php      (11 lines)
│       ├── BoolValue.php          (25 lines)
│       ├── IntegerValue.php       (28 lines)
│       ├── FloatValue.php         (30 lines)
│       ├── StringValue.php        (28 lines)
│       ├── StringStyle.php        (enum, 13 lines)
│       ├── IntegerBase.php        (enum, 13 lines)
│       ├── ArrayValue.php         (41 lines)
│       ├── InlineTable.php        (58 lines)
│       ├── OffsetDateTime.php     (26 lines)
│       ├── LocalDateTime.php      (25 lines)
│       ├── LocalDate.php          (25 lines)
│       └── LocalTime.php          (25 lines)
├── Encoder/
│   ├── Encoder.php                (1522 lines)
│   ├── EncoderOptions.php         (43 lines)
│   ├── ArrayStyle.php             (enum, 31 lines)
│   └── DocumentFormattingMode.php (enum, 11 lines)
├── Exception/
│   ├── TomlException.php          (10 lines)
│   ├── ParseException.php         (20 lines)
│   └── EncodeException.php        (9 lines)
├── Support/
│   └── TemporalValidator.php      (71 lines, static methods)
└── Value/                         (encoder-side wrappers)
    ├── TomlValue.php              (interface: toTomlLiteral())
    ├── TomlInteger.php            (32 lines)
    ├── LocalDate.php              (23 lines)
    ├── LocalTime.php              (23 lines)
    └── LocalDateTime.php          (23 lines)
```

**Total `src/` LoC:** ~4,511 across the four big files (Lexer 875, Parser 1346, Normalizer 629, Encoder 1522) plus ~600 LoC across the small types.

**Public API surface:** SOURCE-VERIFIED — `src/Toml.php:15-142` defines a `final class Toml` with 8 static methods: `decode`, `decodeFile`, `tryParse`, `parse`, `encode`, `encodeDocument`, `encodeFile`, `encodeDocumentFile`.

**Execution path** (input → PHP value):
1. `Toml::decode($input)` (`Toml.php:24-39`) constructs `new Parser(version:)`, calls `Parser::parse($input)`, then `new Normalizer()->normalize($doc)`, then merges `parser->getErrors() + normalizer->getErrors()` and throws the first as `ParseException` if any.
2. `Parser::parse($input)` (`Parser.php:77-87`) constructs `new Lexer($input, $version)`, calls `iterator_to_array($lexer->tokenize())` to materialize all tokens into `$this->tokens`, then dispatches into `parseDocument()`.
3. `parseDocument()` (`Parser.php:97-161`) loops tokens, dispatching to `parseTableHeader()` (`Parser.php:163-202`) on `[`, `parseKeyValue()` (`Parser.php:204-246`) on key-starting tokens, and recovering via `synchronize()` (`Parser.php:1285-1299`) on Invalid tokens.
4. `Normalizer::normalize($doc)` (`Normalizer.php:60-87`) walks `Document->items`, descending into tables via `openTable()` / `openArrayTable()` (`Normalizer.php:222-486`) and setting values via `setNestedValue()` (`Normalizer.php:500-620`), tracking duplicate-key / sealed-inline-table / static-array state.

---

## 3. Lexer Analysis

**Class:** `PhpCollective\Toml\Lexer\Lexer` — `src/Lexer/Lexer.php:12`, `final class`.

**Approach:** SOURCE-VERIFIED — stateful, char-by-char with explicit `$pos`, `$line`, `$column` tracking (`Lexer.php:14-18`). Generator-based via `tokenize(): Generator<Token>` (`Lexer.php:32`). NOT regex-driven for structural tokens; regex is reserved for validating numeric/datetime lexemes (`Lexer.php:608-613`, `Lexer.php:821-849`).

**Top-level dispatch** (`Lexer.php:48-67`):
```php
while ($this->pos < $this->length) {
    $char = $this->input[$this->pos];
    yield match ($char) {
        '[' => $this->single(TokenType::LeftBracket),
        '"' => $this->string(),
        "'" => $this->literalString(),
        ' ', "\t" => $this->whitespace(),
        "\r" => $this->skipCr(),
        default => $this->keyOrValue(),
    };
}
```

**UTF-8 / BOM:** Upfront `mb_check_encoding($this->input, 'UTF-8')` — emits a single `Invalid` token if it fails (`Lexer.php:35-40`). One optional leading UTF-8 BOM (`\u{FEFF}`, bytes `EF BB BF`) is skipped (`Lexer.php:44-46`).

**Whitespace / newlines / comments:**
- Whitespace = runs of `' '` or `"\t"` (`Lexer.php:148-160`).
- Newline = single `"\n"` (`Lexer.php:93-103`); column resets to 0, line increments.
- Lone CR (no LF) → `Invalid` token (`Lexer.php:105-118`).
- Comment = `#` to end-of-line, control characters (except `\t`) make it `Invalid` (`Lexer.php:120-146`).

**Escape sequences** (`Lexer.php:220-266`):
- `b`, `t`, `n`, `f`, `r`, `"`, `\\` always.
- `e` → `\x1B` (TOML 1.1 only — `null` in V10).
- `x` → 2-hex byte escape (TOML 1.1 only).
- `u` → 4-hex, `U` → 8-hex codepoint.
- Surrogates (`0xD800–0xDFFF`) and codepoints > `0x10FFFF` rejected via `Lexer.php:261-263`.
- `mb_chr($codepoint, 'UTF-8')` for emission (`Lexer.php:265`).

**Multi-line basic strings** (`Lexer.php:268-389`): 3-char `"""` opener, skips one immediate newline, supports up to 2 trailing extra quotes before closing `"""` (`Lexer.php:301-318`), supports line-ending backslash with whitespace/newline skip (`Lexer.php:330-360`). Bare CR without LF marked invalid (`Lexer.php:373-376`).

**Multi-line literal strings** (`Lexer.php:441-528`): mirror of basic, no escape processing.

**Number / datetime lookahead** (`Lexer.php:594-662`):
- A lookahead scan over `isDateTimeChar` chars decides between number, datetime, and time before sign-handling.
- `^\d{4}-\d{2}-\d{2}/` → `dateTime()`; `^\d{2}:\d{2}/` → `time()`.
- Datetime lexer stops at `' '` if not followed by a digit, and at `'.'` unless followed by digits after `:\d{2}` (`Lexer.php:676-695`).

**Integer overflow protection** (`Lexer.php:806-813`):
```php
// Plain integer; TOML integers are 64-bit signed, so reject out-of-range values
// instead of silently saturating to PHP_INT_MAX/PHP_INT_MIN via an (int) cast.
$parsed = filter_var($clean, FILTER_VALIDATE_INT);
if ($parsed === false) {
    return new Token(TokenType::Invalid, $value, null, $span);
}
```
Verified by `tests/Parser/IntegerRangeTest.php:39-63` which rejects `9223372036854775808`, `-9223372036854775809`, `0xFFFFFFFFFFFFFFFF`, etc.

**Hex/octal/binary classification** (`Lexer.php:770-799`): prefix `0x`/`0o`/`0b`, no sign allowed; `hexdec/octdec/bindec` returning a float (i.e. overflow) is rejected as `Invalid`.

**Float classification** (`Lexer.php:802-804`): `(float)$clean` where `$clean` has underscores stripped. The Lexer uses six separate regex patterns in `isValidNumberLiteral()` (`Lexer.php:821-849`) to validate integer/float literal shape before emitting a number token.

**Inf/NaN handling** (`Lexer.php:622-640`): `inf`, `+inf`, `-inf`, `nan` literals are parsed after optional sign into `INF` / `NAN` float tokens.

**Special-float ambiguity:** `nan` and `inf` are valid bare keys AND special float values. The lexer emits them as `BareKey` and the parser re-interprets them as floats in value position (`Parser.php:508-521`).

**Token model:** `Token` is a `final readonly class` with `public TokenType $type`, `public string $value`, `public mixed $parsed`, `public Span $span` (`Token.php:7-15`). The `parsed` field is the eagerly-evaluated PHP value (string, int, float, bool, `DateTimeImmutable`, etc.).

**Span / position tracking:** `Span` is a `final readonly class` with `int $start`, `int $end` (byte offsets), `int $line`, `int $column` (`Span.php:7-15`). The Lexer initializes `$line = 1`, `$column = 0` (`Lexer.php:16-18`) — so column is effectively **0-based** in the captured Span, despite docs claiming 1-based (`docs/reference/api.md:226`). Minor inconsistency. `Span::merge()` (`Span.php:22-30`) combines two spans for parent/child composition.

**Memory consideration:** SOURCE-VERIFIED — The Lexer is a generator (`tokenize()` yields lazily), but the Parser immediately materializes the whole stream via `iterator_to_array($lexer->tokenize())` (`Parser.php:81`). Therefore the actual decoding pipeline is **not streaming**; memory scales with input size. The "generator-based for memory efficiency" claim in `docs/reference/architecture.md:201` is **only true at the lexer boundary**, not end-to-end.

**Lookahead / backtracking:** Minimal and bounded. Examples:
- 2-char lookahead for `"""` / `'''` detection (`Lexer.php:168-172`, `398-402`).
- Multi-char lookahead for datetime detection via `isDateTimeChar` scan (`Lexer.php:600-605`).
- 3-char lookahead for `inf`/`nan` after sign (`Lexer.php:623-640`).
- No backtracking in the lexer; no regex catastrophic-backtracking risk because patterns are anchored and simple.

---

## 4. Parser Analysis

**Class:** `PhpCollective\Toml\Parser\Parser` — `src/Parser/Parser.php:34`, `final class`.

**Approach:** SOURCE-VERIFIED — recursive descent, single-pass, no backtracking. Tokens are pre-materialized into `$this->tokens` (`Parser.php:81`). Cursor `$this->pos` (`Parser.php:56`). Helpers `current()`, `previous()`, `advance()`, `check()`, `match()`, `expect()` follow the classic Crafting-Interpreters pattern (`Parser.php:834-894`).

**Context tracking** (`Parser.php:62-75`):
```php
private array $contextStack = [];
public function __construct(
    bool $preserveTrivia = false,
    private readonly TomlVersion $version = TomlVersion::V11,
) { ... }
```
The stack contains `'array'` or `'inline_table'` entries (`Parser.php:39-44`) and is pushed/popped by `parseArray()` (`Parser.php:622, 708`) and `parseInlineTable()` (`Parser.php:724, 808`) for error-recovery scoping.

**Document loop** (`Parser.php:97-161`): Dispatches on token type:
- `LeftBracket` → `parseTableHeader()` + `checkTableHeaderTerminator()`.
- Key-starter tokens (`BareKey`, `BasicString`, `LiteralString`, `Invalid`, `Integer`, `Float`, `Boolean`, datetime types) → `parseKeyValue()` + `checkKeyValueTerminator()`.
- `Newline` → advance.
- `Invalid` → emit error with `getInvalidTokenHint()` + `synchronize()`.
- Default → emit "Unexpected token" error + `synchronize()`.

**Key parsing** (`Parser.php:398-500`): Handles dotted keys (`a.b.c`) by looping, accepting bare keys, basic/literal quoted strings, decimal integers (TOML 1.1 bare-key rule — `Parser.php:429-438`), booleans (`Parser.php:449-453`), float-like dotted keys (`1.2` → two parts `["1","2"]`, `Parser.php:454-472`), and datetime-like bare keys (`2001-02-03`, `15:16:17` — `Parser.php:473-478`). Key construction:

```php
return new Key($parts, $styles, $span, $this->slice($span),
    null, null, $rawSeparators);
```
(`Parser.php:499`) — note `null` for `originalParts` / `originalStyles`; `Key::__construct` defaults them to the new values (`Key.php:30-31`).

**Value parsing** (`Parser.php:502-543`): A `match($token->type)` dispatch with separate `parseStringValue`, `parseIntegerValue`, `parseFloatValue`, `parseBoolValue`, `parseOffsetDateTime`, `parseLocalDateTime`, `parseLocalDate`, `parseLocalTime`, `parseArray`, `parseInlineTable`. Special-case: `BareKey` token in value position is interpreted as `nan`/`inf` float (`Parser.php:508-521`).

**Array parsing** (`Parser.php:620-720`): Standard loop with `parseValue()`, comma separator, optional trailing comma, collection-local trivia collection. On value-parse failure, calls `synchronizeInCollection()` to recover at next comma or closing `]` (`Parser.php:647-660`).

**Inline table parsing** (`Parser.php:722-830`): Mirrors array parsing but uses `parseInlineTableKeyValue()` (`Parser.php:253-296`) — a variant of `parseKeyValue()` that does NOT call `synchronize()` on error, letting the parent `parseInlineTable()` handle recovery.

**TOML 1.0 vs 1.1 enforcement** (`Parser.php:810-818`):
```php
if ($this->version === TomlVersion::V10) {
    if ($hasInlineTableLayoutNewline) {
        $this->error('Multiline inline tables require TOML 1.1', $span);
    }
    if ($hasTrailingComma) {
        $this->error('Inline table trailing commas require TOML 1.1', $span);
    }
}
```
Single enforcement point at the end of `parseInlineTable()`. Equivalent enforcement for `\x`/`\e` escapes is in the lexer (`Lexer.php:239-240`); for optional seconds in local times, in `TemporalValidator` (`Support/TemporalValidator.php:58-60`).

**Table construction & array-of-tables after array-of-tables** (critical test from directive §3.4):

`[[fruits]]` then `[[fruits.varieties]]` is handled by the Normalizer, not the Parser. The Parser simply emits two `Table` AST nodes with `isArrayTable = true` and `key.parts = ['fruits']` / `['fruits','varieties']` respectively. The Normalizer's `openArrayTable()` (`Normalizer.php:367-486`) does the heavy lifting:

```php
$leafKey = array_pop($path);     // pop 'varieties' from ['fruits','varieties']
// ...iterate $path = ['fruits']:
elseif (array_is_list($current[$key])) {     // fruits is a list of tables
    $lastEntry = array_key_last($current[$key]);
    $internalPrefix[] = '#' . (string)$lastEntry;
    $current = &$current[$key][$lastEntry];   // descend into LAST fruits entry
}
// ...then at leaf:
$current[$leafKey][] = [];                     // append new varieties entry
```
(`Normalizer.php:426-441, 469-478`). Verified by `tests/Normalizer/SemanticValidationTest.php:134-170` (`testArrayOfTablesWithSubtables`) which exercises exactly that pattern with two `[[fruits]]` and three `[[fruits.varieties]]` headers.

**Duplicate key detection:** Performed in `Normalizer::setNestedValue()` (`Normalizer.php:558-578`) and at table-open (`Normalizer.php:303-351`). The Normalizer tracks four key states in `$definedTables`: `'implicit'`, `'explicit'`, `'array'`, `'dotted'` — and rejects transitions that violate TOML's table redefinition rules. See `tests/Normalizer/SemanticValidationTest.php:15-90` for the test matrix.

**Terminator checks:** `checkTableHeaderTerminator()` (`Parser.php:997-1024`) requires newline/EOF/comment after `[name]`; `checkKeyValueTerminator()` (`Parser.php:1030-1063`) requires the same after `key = value`. Both peek ahead through whitespace without consuming.

**Error recovery** (see §8 for details): Two strategies — top-level `synchronize()` skips to newline or `[`; `synchronizeInCollection()` is depth-aware, tracking nested `[`/`{` to stop at the correct closing bracket or comma.

**Lookahead / backtracking in parser:** Only the `checkTableHeaderTerminator()` / `checkKeyValueTerminator()` lookahead-through-whitespace patterns (`Parser.php:1007-1020`, `1041-1058`). No full backtracking.

---

## 5. AST Architecture (DETAILED — the headline feature)

### 5.1 Node taxonomy

**SOURCE-VERIFIED — 13 AST node classes + 5 enums** (see file tree in §2):

**Structural nodes** (`src/Ast/`):
| Class | File | Lines | Role |
|-------|------|-------|------|
| `Node` (interface) | `Ast/Node.php` | 9-36 | `getSpan()`, `getLeadingTrivia()`, `getTrailingTrivia()`, `setLeadingTrivia()`, `setTrailingTrivia()` |
| `AbstractNode` | `Ast/AbstractNode.php` | 9-55 | Stores `$span`, `$leadingTrivia`, `$trailingTrivia`; concrete accessors |
| `Document` (root) | `Ast/Document.php` | 9-20 | `public array $items` of `Table|KeyValue` |
| `Table` | `Ast/Table.php` | 9-28 | `public Key $key`, `public bool $isArrayTable`, `public array $items`, `public string $rawHeader`, `public ?bool $originalIsArrayTable`, `public string $rawHeaderPrefix`, `public string $rawHeaderSuffix` |
| `KeyValue` | `Ast/KeyValue.php` | 10-22 | `public Key $key`, `public Value $value`, `public string $raw`, `public string $rawPrefix`, `public string $rawAssignmentSeparator` |
| `Key` | `Ast/Key.php` | 9-38 | `public array $parts`, `public array $styles`, `public string $raw`, `public ?array $originalParts`, `public ?array $originalStyles`, `public array $rawSeparators`, `toString()` |
| `Trivia` | `Ast/Trivia.php` | 9-17 | `public TriviaKind $kind`, `public string $value`, `public Span $span` |

**Enums** (`src/Ast/`):
| Enum | Cases | File |
|------|-------|------|
| `KeyStyle` | `Bare`, `Basic`, `Literal` | `Ast/KeyStyle.php:7-12` |
| `TriviaKind` | `Whitespace`, `Comment`, `Newline` | `Ast/TriviaKind.php:7-12` |

**Value nodes** (`src/Ast/Value/`):
| Class | File | Lines | getValue() returns | Style/base tag |
|-------|------|-------|--------------------|----------------|
| `Value` (interface) | `Value.php` | 9-12 | `mixed` | — |
| `AbstractValue` | `AbstractValue.php` | 9-11 | — | — |
| `BoolValue` | `BoolValue.php` | 9-25 | `bool` | — + `originalValue` |
| `IntegerValue` | `IntegerValue.php` | 9-28 | `int` | `IntegerBase $base` + `originalValue` + `originalBase` |
| `FloatValue` | `FloatValue.php` | 9-30 | `float` | `isSpecial()` method + `originalValue` |
| `StringValue` | `StringValue.php` | 9-28 | `string` | `StringStyle $style` + `originalValue` + `originalStyle` |
| `ArrayValue` | `ArrayValue.php` | 9-41 | `array<mixed>` | `openingTrivia`, `closingTrivia`, `hasTrailingComma`, `originalItemCount`, `raw`, `singleLineStyle` |
| `InlineTable` | `InlineTable.php` | 9-58 | `array<string,mixed>` | `openingTrivia`, `closingTrivia`, `hasTrailingComma`, `originalItemCount`, `raw`, `singleLineStyle` |
| `OffsetDateTime` | `OffsetDateTime.php` | 10-26 | `DateTimeImmutable` | `string $raw` + `originalComparable` |
| `LocalDateTime` | `LocalDateTime.php` | 9-25 | `string` | `string $raw` + `originalValue` |
| `LocalDate` | `LocalDate.php` | 9-25 | `string` | `string $raw` + `originalValue` |
| `LocalTime` | `LocalTime.php` | 9-25 | `string` | `string $raw` + `originalValue` |

**Value enums**:
| Enum | Cases |
|------|-------|
| `StringStyle` | `Basic`, `Literal`, `MultiLineBasic`, `MultiLineLiteral` |
| `IntegerBase` | `Decimal`, `Hexadecimal`, `Octal`, `Binary` |

### 5.2 The "Original X" pattern (KEY DESIGN)

Every value and key node carries BOTH the live (potentially-edited) value AND nullable `original*` fields populated lazily from the live value at construction:

```php
// KeyValue.php:12-21 — note rawPrefix + rawAssignmentSeparator
public function __construct(
    public Key $key,
    public Value $value,
    Span $span,
    public string $raw = '',
    public string $rawPrefix = '',
    public string $rawAssignmentSeparator = '',
) { parent::__construct($span); }
```
```php
// BoolValue.php:11-19
public function __construct(
    public bool $value,
    Span $span,
    public string $raw = '',
    public ?bool $originalValue = null,
) {
    parent::__construct($span);
    $this->originalValue ??= $value;
}
```

This is the substrate that enables source-aware re-encoding in `Encoder::encodeAstItem()` (`Encoder.php:259-290`): the encoder checks `isReusable*()` predicates and emits `item->raw` for unchanged nodes, falls back to canonical formatting for changed nodes.

### 5.3 AST visitor pattern

**SOURCE-VERIFIED — There is NO visitor pattern.** No `accept()` methods, no `Visitor` interface. AST traversal is via direct `foreach ($document->items as $item)` loops with `instanceof Table` / `instanceof KeyValue` checks. Confirmed by `docs/guide/ast.md:198-213` "Walking the AST" section, which shows raw iteration.

### 5.4 How AST is exposed to callers

Three entry points (all SOURCE-VERIFIED):
1. `Toml::parse($input, $preserveTrivia = false, $version = V11): Document` — `Toml.php:78-86`. Returns the AST directly. Always returns a `Document`, even on parse errors.
2. `Toml::tryParse($input, $version = V11): ParseResult` — `Toml.php:62-73`. Returns `ParseResult { Document|null, ParseError[], array|null }`. `getDocument()` is non-null even when invalid (`tests/PublicApi/ContractsTest.php:18-26`).
3. `Toml::decode()` and `Toml::decodeFile()` — `Toml.php:24-56`. Throw on first error; AST never exposed.

### 5.5 AST mutability

**SOURCE-VERIFIED — The AST is MUTABLE.** Every concrete node declares `public array $items` (`Document.php:14`, `Table.php:14`) or `public` properties (`KeyValue.php:13-19`, `Key.php:21-28`, `BoolValue.php:12-17`, etc.). `setLeadingTrivia()` / `setTrailingTrivia()` mutate state (`AbstractNode.php:42-54`). The README example confirms: `$document->items[0]->value = new StringValue('new value', …)` (`README.md:134`).

The `original*` fields are nullable but assignable (public). Therefore the AST cannot be safely shared across concurrent readers if any writer holds a reference — relevant for SStack if it wants a single shared AST.

### 5.6 Trivia model

**SOURCE-VERIFIED — Trivia is attached per-node, opt-in.** When `Toml::parse($input, true)` is called, the Parser collects `Whitespace`/`Comment`/`Newline` tokens into leading/trailing trivia arrays on each `Table`/`KeyValue` (`Parser.php:103, 120-123, 137-140`), and into `openingTrivia`/`closingTrivia` on each `ArrayValue`/`InlineTable` (`Parser.php:628, 668, 693, 696, 730, 789, 796`).

Trivia structure (`Ast/Trivia.php:11-16`):
```php
public function __construct(
    public TriviaKind $kind,    // Whitespace | Comment | Newline
    public string $value,       // raw text e.g. "# my comment\n"
    public Span $span,
) {}
```

Additionally, `ArrayValue` and `InlineTable` carry a `?array $singleLineStyle` shape (`ArrayValue.php:19`, `InlineTable.php:19`) recording per-collection delimiter spacing — populated by `Parser::inferSingleLineArrayStyle()` / `inferSingleLineInlineTableStyle()` (`Parser.php:1182-1258`). This is the local-formatting-preservation substrate.

### 5.7 Architectural separation assessment (per §2.C)

**The architectural separation IS useful for SovereignStack.** Specifically:

1. **Lexer / Parser / Normalizer pipeline split** — Three classes, three responsibilities, testable in isolation. The Lexer produces an Invalid token instead of throwing, the Parser consumes Invalid tokens and emits `ParseError` records, the Normalizer produces a value + additional semantic errors. **SStack-relevant: high.** A native SStack parser can adopt this split.

2. **AST as a separate, addressable output** — `Toml::parse()` returns a `Document` independent of the final PHP array. The Normalizer is a separate pass. **SStack-relevant: very high** — CORE-10 Configuration needs structured config access without forcing materialization to a PHP array.

3. **Original-value/source-aware separation** — Each node carries both new value and `original*` metadata. This is a clever way to support round-trip editing without immutability. **SStack-relevant: medium** — SStack's stricter contract might prefer immutability plus a separate "raw source" record per node.

4. **Error model decoupled from exception flow** — `ParseResult` with `ParseError[]` is a clean data-oriented design, distinct from `ParseException` (the throwing variant). **SStack-relevant: very high** — see §8.

5. **Encoder as separate concern** — `Encoder::encode()` (array→TOML) and `Encoder::encodeDocument()` (Document→TOML) are independent of the decoder. `encodeDocument()` can call `Normalizer` to validate the AST before encoding (`Encoder.php:58-63`). **SStack-relevant: high.**

---

## 6. Value Representation

**SOURCE-VERIFIED — Final PHP value types after `Toml::decode()`:**

| TOML type | AST node | `getValue()` | Final decode value |
|-----------|----------|--------------|--------------------|
| Basic/Literal string | `StringValue` | `string` | `string` |
| Multiline basic/literal | `StringValue` | `string` | `string` |
| Integer | `IntegerValue` | `int` | `int` |
| Float | `FloatValue` | `float` | `float` |
| `inf`, `-inf`, `nan` | `FloatValue` | `float` (`INF`/`-INF`/`NAN`) | `float` |
| Boolean | `BoolValue` | `bool` | `bool` |
| Offset datetime | `OffsetDateTime` | `DateTimeImmutable` | `DateTimeImmutable` |
| Local datetime | `LocalDateTime` | `string` | `string` |
| Local date | `LocalDate` | `string` | `string` |
| Local time | `LocalTime` | `string` | `string` |
| Array | `ArrayValue` | `array<mixed>` (via `array_map($v->getValue())` — `ArrayValue.php:39`) | `array` |
| Inline table | `InlineTable` | `array<string,mixed>` (with dotted-key fan-out — `InlineTable.php:36-57`) | `array` |
| Table / array-of-tables | `Table` | n/a (handled by Normalizer) | `array` / `list<array>` |

**Scalar → AST → value:** Strings are unescaped in the lexer (`Lexer.php:178-214`) so the AST `StringValue` carries the parsed value, not the source literal. The raw source is preserved in `StringValue->raw` and `->originalValue` for round-trip.

**Encoder-side value wrappers** (`src/Value/`):
- `TomlValue` interface: `toTomlLiteral(): string` (`Value/TomlValue.php:9`).
- `TomlInteger(int $value, IntegerBase $base)` — encodes as `0x..`/`0o..`/`0b..` when base is non-decimal and value ≥ 0 (`Value/TomlInteger.php:17-30`).
- `LocalDate`, `LocalTime`, `LocalDateTime` readonly wrappers that validate the literal at construction via `TemporalValidator` and expose `toTomlLiteral()` (`Value/LocalDate.php:10-23` etc.).

These wrappers are the API for telling the encoder "this string is actually a TOML local-time literal, not a quoted string" — there is no automatic coercion of plain strings to local temporals (`docs/reference/support-matrix.md:85`).

---

## 7. Datetime Handling (CRITICAL — verify timezone preservation)

### 7.1 Timezone-neutrality verification

**SOURCE-VERIFIED — Local datetime / date / time remain STRINGS, never `DateTimeImmutable`.** This is intentional and explicitly documented:

```php
// Ast/Value/LocalDateTime.php:11-23
final class LocalDateTime extends AbstractValue
{
    public function __construct(
        public string $value,    // <-- STRING, not DateTimeImmutable
        Span $span,
        public string $raw = '',
        public ?string $originalValue = null,
    ) {
        parent::__construct($span);
        $this->originalValue ??= $value;
    }
    public function getValue(): string { return $this->value; }
}
```

The Lexer preserves the raw string for all three local temporal types (`Lexer.php:718, 725, 743`) and only constructs a `DateTimeImmutable` for `OffsetDateTime` (`Lexer.php:708-710`).

`docs/reference/limitations.md:78-87` confirms the design rationale:
> Local datetime types (`LocalDateTime`, `LocalDate`, `LocalTime`) are returned as strings, not `DateTimeImmutable` … This is because local dates have no timezone information, making `DateTimeImmutable` semantically incorrect.

**Verdict for §2.C directive:** The design **preserves timezone-neutrality correctly.** A local datetime does not silently inherit PHP's default timezone (which would be the bug if `new DateTimeImmutable('1979-05-27T07:32:00')` were used — it would attach the server's TZ). SStack should adopt this design.

### 7.2 Validation

`Support/TemporalValidator.php` (71 lines, all static):
- `isValidLocalDate($value)`: regex + `checkdate()` to reject Feb 30 (`TemporalValidator.php:47-54`).
- `isValidLocalTime($value, $version)`: regex with optional seconds in V11, required seconds in V10; hour ≤ 23, minute ≤ 59, second ≤ 59 (`TemporalValidator.php:56-70`).
- `isValidLocalDateTime($value, $version)`: regex + delegation to date+time validators (`TemporalValidator.php:35-45`).
- `isValidOffsetDateTime($value, $version)`: regex + `new DateTimeImmutable($normalized)` attempt-catch + date+time validation (`TemporalValidator.php:13-33`).

### 7.3 Precision limitation

**DOCUMENT-VERIFIED — Nanosecond fractional seconds are truncated to microseconds** (`docs/reference/limitations.md:21-32`):
> PHP's `DateTimeImmutable` supports microsecond precision (6 digits). Nanosecond values (9 digits) are truncated.

This affects only `OffsetDateTime` because local types are preserved as strings (full precision retained). INFERRED — A local time like `07:32:00.123456789` would be preserved as the string `"07:32:00.123456789"` through the parser, but on encoding through `Value\LocalTime` it would be re-validated and the literal preserved.

### 7.4 Offset datetime normalization in the AST

`OffsetDateTime` carries a `?string $originalComparable` initialized to `$value->format('Y-m-d\TH:i:s.uP')` (`Ast/Value/OffsetDateTime.php:13-20`). This is a normalized string form used for stable comparison when the raw form differs (e.g. `Z` vs `+00:00`).

---

## 8. Error Handling and "Collected Parse Errors" (DETAILED)

### 8.1 Exception hierarchy

```
Exception
  └── TomlException            (src/Exception/TomlException.php, 10 lines)
        ├── ParseException     (src/Exception/ParseException.php, 20 lines)
        └── EncodeException   (src/Exception/EncodeException.php, 9 lines)
```

**SOURCE-VERIFIED** — `TomlException extends Exception` (`TomlException.php:9`); `ParseException extends TomlException` (`ParseException.php:10`); `EncodeException extends TomlException` (`EncodeException.php:7`). The exception hierarchy is shallow and clean.

### 8.2 ParseException structure

```php
// src/Exception/ParseException.php:10-20
class ParseException extends TomlException
{
    public function __construct(
        string $message,
        public readonly ?Span $span = null,
        public readonly ?string $hint = null,
        public readonly ?ParseErrorCode $errorCode = null,
    ) {
        parent::__construct($message);
    }
}
```

**Concern:** `parent::__construct($message)` does NOT pass a `$previous` exception — so previous-exception chaining is unsupported. SStack should consider passing `$previous` for wrapping lower-level exceptions. SOURCE-VERIFIED limitation.

### 8.3 ParseError (the data record)

```php
// src/Parser/ParseError.php:9-24
final readonly class ParseError
{
    public ParseErrorCode $code;
    public function __construct(
        public string $message,
        public Span $span,
        public ?string $hint = null,
        ?ParseErrorCode $code = null,
    ) {
        $this->code = $code ?? ParseErrorCode::fromMessage($message);
    }
    public function format(string $input): string { /* see §9 */ }
}
```

Stable `code` is derived from `message` if not explicitly provided. The `format()` method produces CLI-style context (see §9).

### 8.4 ParseResult (the aggregate)

```php
// src/Parser/ParseResult.php:9-48
final readonly class ParseResult
{
    public function __construct(
        private ?Document $document,
        private array $errors,
        private ?array $value = null,
    ) {}
    public function isValid(): bool      { return $this->errors === []; }
    public function getDocument(): ?Document { return $this->document; }
    public function getErrors(): array   { return $this->errors; }
    public function getValue(): ?array   { return $this->value; }
}
```

**Critical contract:** `getDocument()` returns the AST **even when invalid** (verified by `tests/PublicApi/ContractsTest.php:18-26`). `getValue()` returns `null` when invalid (`Toml.php:70`). This makes `tryParse()` ideal for IDE/linter workflows.

### 8.5 ParseErrorCode enum

**SOURCE-VERIFIED — 16 codes in 4 categories** (`src/Parser/ParseErrorCode.php:13-43`):
- Generic fallback: `SyntaxError`
- Syntax (parser): `ExpectedKey`, `ExpectedValue`, `ExpectedToken`, `UnexpectedToken`, `UnterminatedStatement`, `UnsupportedVersion`
- Lexical: `InvalidToken`, `InvalidEncoding`, `UnterminatedString`, `InvalidNumber`, `IntegerOutOfRange`, `InvalidDateTime`
- Semantic (normalizer): `DuplicateKey`, `DuplicateTable`, `KeyRedefinition`, `TableRedefinition`, `ArrayTableConflict`, `ExtendStaticArray`, `ExtendInlineTable`, `DottedKeyConflict`

Two classifiers (`ParseErrorCode.php:50-100`):
- `fromMessage(string $message): self` — pattern-matches message stems like `str_starts_with($message, 'Duplicate key')` → `DuplicateKey`.
- `fromInvalidLexeme(string $lexeme): self` — inspects the rejected lexeme: a leading `"`/`'` without matching terminator → `UnterminatedString`, otherwise a decimal-integer pattern → `IntegerOutOfRange`, a date pattern → `InvalidDateTime`, etc.

Tests: `tests/Parser/ParseErrorCodeTest.php` (82 lines) covers all stable codes including overflow, unterminated string, invalid escape, invalid datetime, duplicate key/table, key redefinition, extend-inline-table, strict-version error, fallback.

### 8.6 How errors are COLLECTED in one parse

**Two error sources, merged in `Toml` facade:**

```php
// src/Toml.php:30-36 (decode — throws first) and 62-73 (tryParse — collects)
$parser = new Parser(version: $version);
$doc = $parser->parse($input);
$normalizer = new Normalizer();
$value = $normalizer->normalize($doc);
$errors = [...$parser->getErrors(), ...$normalizer->getErrors()];
```

Parser errors are appended to `$this->errors[]` via `Parser::error()` (`Parser.php:1276-1279`). Normalizer errors are appended via `$this->errors[] = new ParseError(...)` at each semantic-violation site (`Normalizer.php:240-243, 263, 273, 283, 304, 320, 325, 336, 347, 385, 408, 417, 428, 449, 455, 464, 472, 530, 538, 560, 566, 575, 591, 605`).

### 8.7 Error recovery mechanisms

**Top-level `synchronize()`** (`Parser.php:1285-1299`):
```php
private function synchronize(): void
{
    while (!$this->isAtEnd()) {
        if ($this->check(TokenType::Newline)) {
            $this->advance();
            return;
        }
        if ($this->check(TokenType::LeftBracket)) {
            return;        // stop at table-header start, do not consume
        }
        $this->advance();
    }
}
```
Skip to newline or next `[`. This is line-oriented recovery.

**In-collection `synchronizeInCollection(): bool`** (`Parser.php:1307-1345`):
```php
private function synchronizeInCollection(): bool
{
    $depth = 0;
    while (!$this->isAtEnd()) {
        $token = $this->current();
        if ($token->is(TokenType::LeftBracket, TokenType::LeftBrace)) {
            $depth++;
            $this->advance();
            continue;
        }
        if ($token->is(TokenType::RightBracket, TokenType::RightBrace)) {
            if ($depth > 0) { $depth--; $this->advance(); continue; }
            return false;        // at our closing — stop without consuming
        }
        if ($depth === 0 && $token->is(TokenType::Comma)) {
            $this->advance();
            return true;         // found comma — can continue parsing
        }
        $this->advance();
    }
    return false;
}
```
Depth-aware recovery inside arrays/inline tables. Returns `true` if a comma was found (continue parsing next element), `false` if a closing bracket was hit.

**Newline-gap break-out** (`Parser.php:676-686` for arrays, `773-783` for inline tables): If the gap between two values contains a newline AND the next token isn't a comma, the parser breaks out to the document parser instead of swallowing following top-level keys. This prevents `a = [1\nb = 2` from absorbing `b = 2` into the array (verified by `tests/Parser/ErrorRecoveryTest.php:302-315`).

**Missing-comma in-line recovery** (`Parser.php:684-686, 781-783`): On a same-line gap without comma, emit `Expected , or ] in array` once and `continue` — the loop re-enters and parses the next value. Verified by `tests/Parser/ErrorRecoveryTest.php:284-300` (`testMissingCommaInArrayReportsAndRecovers` — three values, two errors, three items recovered).

**Stated limitation:** `docs/reference/support-matrix.md:107`: "Multiple error collection — Partial — Recovery exists, but is line-oriented and not conformance-grade." Honest self-assessment.

### 8.8 Contextual hints

**SOURCE-VERIFIED — Parser generates UX hints:**

| Method | File:Line | Trigger | Hint |
|--------|-----------|---------|------|
| `getEqualsHint()` | `Parser.php:908-921` | YAML `:` instead of `=` | "TOML uses `=` for key-value pairs, not `:`" |
| `getExpectedValueHint()` | `Parser.php:923-937` | YAML bool (`yes`/`no`/`on`/`off`) | "TOML booleans are `true` or `false`" |
| `getExpectedValueHint()` | `Parser.php:932-934` | Unquoted string | "Strings must be quoted. Did you mean `\"…\"`?" |
| `getInvalidTokenHint()` | `Parser.php:939-952` | Semver-like `1.0.0` | "This looks like a version string. Strings must be quoted" |
| `getUnexpectedTokenHint()` | `Parser.php:954-962` | `=` without key | "A key is required before `=`" |
| `getExpectedKeyHint()` | `Parser.php:964-977` | Consecutive dots | "Empty key segment. Keys cannot have consecutive dots" |

These hints significantly improve the configuration-author experience — **highly SStack-relevant**.

### 8.9 Error categories (where each is detected)

| Category | Detected in | Examples |
|----------|-------------|----------|
| Lexical | Lexer → `TokenType::Invalid` token | Unterminated string, bad escape, malformed number, bad datetime, integer overflow, control char in string/comment |
| Syntax | Parser | Missing `=`, missing `]`/`}`, unexpected token, missing comma |
| Semantic | Normalizer | Duplicate key/table, key/table redefinition, inline-table extension, static-array extension, dotted-key conflict |
| Version | Parser + Lexer | `\x`/`\e` in V10, multiline inline table in V10, trailing comma in inline table in V10, optional seconds in V10 |

### 8.10 Source location on every error

Every `ParseError` carries a `Span` (`ParseError.php:19`) — line + column + byte start/end. The `Parser::error()` call site passes `$token->span` or `$this->current()->span` (`Parser.php:151, 155, 225, 229, 264, 277, 280, 651, 684, 781`). The Normalizer passes the span of the offending `KeyValue` or `Table` (`Normalizer.php:243, 263, 273, 283, 304, 320, …`).

---

## 9. Source Location Tracking

### 9.1 Span model

`Span` (`src/Lexer/Span.php:7-15`) is a `final readonly class`:
```php
public function __construct(
    public int $start,    // 0-based byte offset
    public int $end,      // end byte offset
    public int $line,     // 1-based line number
    public int $column,   // 0-based in practice (see §3)
) {}
```

`Span::length(): int` returns `end - start` (`Span.php:17-20`).
`Span::merge(Span $other): self` returns a new span covering both, picking the earlier column (`Span.php:22-30`).

### 9.2 How positions are tracked through the pipeline

- **Lexer** increments `$pos`, `$line`, `$column` per character (`Lexer.php:87-91`, `93-103`). Captured into every Token's Span at construction.
- **Parser** slices Spans via `$this->slice($span)` (`Parser.php:1159-1162`) to preserve raw source text on each AST node. `Span::merge()` combines token spans into node spans (`Parser.php:191, 236, 706, 806`).
- **AST nodes** all expose `getSpan(): Span` via `AbstractNode` (`AbstractNode.php:21-28`).
- **Errors** carry the offending token's or node's Span (`Parser.php:151`).

### 9.3 Formatted error output

`ParseError::format(string $input): string` (`ParseError.php:26-54`):
```php
public function format(string $input): string
{
    $lines = explode("\n", $input);
    $lineNum = $this->span->line;
    $col = $this->span->column;
    $output = "Parse error: {$this->message}\n\n";
    $start = max(0, $lineNum - 2);
    $end = min(count($lines), $lineNum + 1);
    $numWidth = strlen((string)$end);
    for ($i = $start; $i < $end; $i++) {
        $prefix = str_pad((string)($i + 1), $numWidth, ' ', STR_PAD_LEFT);
        $output .= "  {$prefix} | {$lines[$i]}\n";
        if ($i === $lineNum - 1) {
            $output .= '  ' . str_repeat(' ', $numWidth) . ' | '
                . str_repeat(' ', $col) . "^\n";
        }
    }
    if ($this->hint !== null) {
        $output .= "\nHint: {$this->hint}\n";
    }
    return $output;
}
```

Output format (from `docs/guide/error-handling.md:115-125` and `README.md:151-159`):
```
Parse error: unterminated string

  3 | name = "value
    |        ^
  4 | other = 123

Hint: Did you forget to close the string with "?
```

**Caret placement:** Uses `$col` spaces then `^`. Because `$col` is 0-based at first-character capture, the caret aligns with the column visually.

### 9.4 Column base inconsistency

**SOURCE-VERIFIED issue:** `docs/reference/api.md:226` claims `$span->column` is "1-based column number". However, `Lexer.php:18` initializes `$column = 0`, and `Lexer.php:80-84` captures `$col = $this->column` BEFORE calling `$this->advance()`. Therefore a token starting at the first character of a line has `column = 0`. The `format()` method pads with `str_repeat(' ', $col)` spaces which compensates visually. Minor inconsistency between docs and source. SStack should pick one and document it clearly.

---

## 10. TOML 1.1.0 Support (verified)

### 10.1 Versioning

`src/TomlVersion.php:7-11`:
```php
enum TomlVersion: string
{
    case V10 = '1.0';
    case V11 = '1.1';
}
```

Default is `V11` everywhere (`Toml.php:24, 48, 62, 81`; `Lexer.php:24`; `Parser.php:73`; `EncoderOptions.php:17`).

### 10.2 TOML 1.1-only features verified by source

| Feature | Source location | Verified by test |
|---------|-----------------|-------------------|
| `\e` escape (ESC) | `Lexer.php:239` (returns `null` in V10) | `tests/PublicApi/VersioningTest.php:29-35` |
| `\xHH` byte escape | `Lexer.php:240` (returns `null` in V10) | `tests/PublicApi/VersioningTest.php:19-27` |
| Local time without seconds | `TemporalValidator.php:58-60` (V10 uses `:\d{2}:\d{2}`, V11 uses `:\d{2}(?::\d{2})?`) | `tests/PublicApi/VersioningTest.php:37-43` |
| Local datetime without seconds | `TemporalValidator.php:35-39` | `tests/PublicApi/VersioningTest.php:97-104` |
| Offset datetime without seconds | `TemporalValidator.php:15-18` | `tests/Conformance/LiteralsAndDateTimeTest.php:43-49` |
| Multiline inline tables | `Parser.php:810-813` (rejected in V10) | `tests/PublicApi/VersioningTest.php:53-59` |
| Inline table trailing commas | `Parser.php:815-817` (rejected in V10) | `tests/PublicApi/VersioningTest.php:45-51` |
| Boolean as bare key | `Parser.php:449-453` | `tests/Conformance/SupportedSyntaxTest.php` (via fixtures) |
| Float-like dotted key (`1.2 = "a.b"`) | `Parser.php:454-472` | `tests/Conformance/SpecEdgeCaseTest.php:13-23` |
| Date/time-like bare key | `Parser.php:473-478` | (parser path coverage) |

### 10.3 toml-test conformance claims

**DOCUMENT-VERIFIED (not SOURCE-VERIFIED for actual pass rate)** — `docs/reference/support-matrix.md:122-134` claims:

| Mode | Valid tests | Invalid tests | Compliance |
|------|-------------|---------------|-----------|
| TOML 1.1 | 213 / 213 | 467 / 467 | 100% |
| TOML 1.0 | 204 / 204 | 474 / 474 | 100% |

CI gates on pinned `toml-test` v2.2.0 corpus (`.github/workflows/ci.yml:32-33`). The `bin/toml-decoder` (`bin/toml-decoder:1-366`) and `bin/toml-encoder` (`bin/toml-encoder:1-119`) implement the tagged-JSON protocol. Tests in `tests/Conformance/TomlTestDecoderTest.php:11-191` and `tests/Conformance/TomlTestEncoderTest.php` exercise the harness but **skip when `/tmp/toml-test/tests` is absent** (`tests/Conformance/TomlTestDecoderTest.php:16-18`). SOURCE-VERIFIED — the conformance tests do not run in this sandbox because the corpus was not cloned locally.

### 10.4 Verdict

**SOURCE-VERIFIED + DOCUMENT-VERIFIED:** The implementation supports all TOML 1.1 syntax features that are testable by `toml-test` v2.2.0, and CI gates on that corpus. The 100% pass-rate claim is DOCUMENT-VERIFIED only (could not re-run toml-test in this sandbox).

---

## 11. Test Coverage and Organization

### 11.1 Test framework and config

- **PHPUnit** 11.5+ / 12.5+ / 13.0 (`composer.json:25`). Config: `phpunit.xml` (24 lines) — single suite scanning `tests/`, source coverage config for `src/`.
- **PHPStan** level 8 (`phpstan.neon:1-2`) on `src/` and `tests/`.
- **PHPCS** with `PhpCollective` ruleset + `SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration` + `RequireTrailingCommaInClosureUse` (`phpcs.xml:14-17`).
- No coverage threshold enforced in phpunit.xml.

### 11.2 Test directories (SOURCE-VERIFIED)

```
tests/
├── Lexer/        LexerTest, SpanTest
├── Parser/       ParserTest (140 lines), ErrorRecoveryTest (358 lines),
│                 ParseErrorCodeTest (82 lines), IntegerRangeTest (64 lines),
│                 TriviaPreservationTest (102 lines)
├── Encoder/      EncoderTest, EncoderThresholdsTest
├── Value/        TemporalValueTest (38 lines), TomlIntegerTest
├── Normalizer/   SemanticValidationTest (172 lines)
├── Conformance/  FixtureCorpusTest (114 lines), TomlTestDecoderTest (191 lines),
│                 TomlTestEncoderTest, SupportedSyntaxTest (160 lines),
│                 SpecEdgeCaseTest (59 lines), LiteralsAndDateTimeTest (66 lines),
│                 CollectionsAndCommentsTest (44 lines)
├── Integration/  RoundTripTest (146 lines), DocumentRoundTripTest,
│                 AstEditingFallbackTest, EditingFixtureTest
├── PublicApi/    VersioningTest (137 lines), ContractsTest (106 lines)
├── NormalizerTest.php
└── TomlTest.php
```

### 11.3 Test fixtures

```
tests/fixtures/
├── Conformance/
│   ├── valid/       8 fixtures (time-without-seconds, multiline-inline-table, etc.)
│   ├── invalid/     6 fixtures (control-char-string, bad-float-trailing-dot, bad-unicode-escape, etc.)
│   ├── semantic/    7 fixtures (duplicate-key, duplicate-table, scalar-table-collision,
│   │                 array-table-dotted-extension, etc.)
│   └── roundtrip/   4 fixtures exercising source-aware byte-equal re-encoding
└── Editing/         17 input/expected pairs for AST editing scenarios
                     (inline-table-dotted-key-edit, array-table-header-edit,
                      nested-multiline-array-remove, single-line-inline-insert,
                      literal-key-edit, multiline-literal-string-replace, etc.)
```

### 11.4 Notable test design

- `tests/Parser/ErrorRecoveryTest.php` (358 lines) — 18 test cases verifying in-collection recovery, multi-error collection across lines (`testRecoveryCollectsMultipleErrorsAcrossLines:226-239` checks 3 errors on lines 1, 2, 4), continuation across table headers, unterminated-array break-out.
- `tests/Parser/ParseErrorCodeTest.php` — pins every error code via `#[DataProvider]`.
- `tests/Conformance/FixtureCorpusTest.php` — data-driven valid/invalid/semantic/roundtrip fixture testing.
- `tests/Integration/EditingFixtureTest.php` (not read in full) — 17 fixture pairs exercising source-aware editing.
- `tests/PublicApi/ContractsTest.php` — pins the public contract: tryParse returns Document even when invalid, exception hierarchy, encodeDocument fallback behavior.

### 11.5 Coverage assessment

INFERRED — Coverage is broad (syntax, conformance, semantic validation, round-trip editing, public API contracts, error recovery, integer range, trivia preservation, version modes). No coverage percentage reported in repo. The conformance suite is gated by an external corpus clone in CI.

---

## 12. Performance Characteristics

### 12.1 Benchmark harness

`benchmarks/compare-libraries.php` (295 lines):
- Spins up a throwaway Composer project in a temp dir (`benchmarks/compare-libraries.php:80-81`).
- Installs `php-collective/toml` (path repo), `petalbranch/toml`, `devium/toml`, `internal/toml`, `yosymfony/toml` (`benchmarks/compare-libraries.php:61-78`).
- Three cases: `decode-baseline` (3000 iter), `decode-modern` (2500 iter), `encode-baseline` (2500 iter) (`benchmarks/compare-libraries.php:103-132`).
- 5 rounds per case, takes the median (`benchmarks/compare-libraries.php:134-158`).
- Outputs a Markdown report.

### 12.2 Reported numbers

**DOCUMENT-VERIFIED** (`docs/reference/benchmarks.md:18-46`, dated PHP 8.4):

| Case | Library | Ops/s |
|------|---------|------:|
| Decode baseline | internal | 1031 |
| Decode baseline | petalbranch | 1029 |
| Decode baseline | **php-collective** | **1019** |
| Decode baseline | yosymfony | 777 |
| Decode baseline | devium | 435 |
| Decode modern (TOML 1.1) | internal | 1418 |
| Decode modern | petalbranch | 954 |
| Decode modern | **php-collective** | **848** |
| Decode modern | devium | 505 |
| Decode modern | yosymfony | — (does not parse TOML 1.1) |
| Encode baseline | **php-collective** | **17504** |
| Encode baseline | devium | 15448 |
| Encode baseline | internal | 5832 |
| Encode baseline | petalbranch | 5653 |
| Encode baseline | yosymfony | — (no array-to-TOML encode) |

### 12.3 Trade-off explanation

`docs/reference/benchmarks.md:54-55`:
> That gap is the cost of what the decoder produces: a full AST with trivia, collected diagnostics, and strict validation — work the fastest decoders skip. For analysis, tooling, and source-aware editing this is a deliberate trade; for raw decode-only throughput a leaner library wins.

### 12.4 Memory characteristics

- **Lexer:** Generator-based tokenize — lazily produces tokens (`Lexer.php:32`).
- **Parser:** Materializes the entire token stream via `iterator_to_array($lexer->tokenize())` (`Parser.php:81`). Therefore the pipeline is **NOT streaming** end-to-end. SOURCE-VERIFIED.
- **Normalizer:** Single pass over AST, no allocation beyond the result array and `$definedTables`/`$definedKeys`/`$sealedInlineTables`/`$staticArrays` maps.
- **pathId via json_encode:** `Normalizer::pathId()` uses `json_encode($segments)` for hash-map keys (`Normalizer.php:625-628`). This is wasteful at scale (JSON encoding on every key insertion) — SStack could use a simpler separator-based join.

### 12.5 Memory consumption claims

No explicit memory-consumption numbers in `docs/reference/benchmarks.md` or `docs/reference/architecture.md`. The only memory-related claim is "Generator-based lexer for memory efficiency" (`docs/reference/architecture.md:201`), which is partially undermined by `iterator_to_array` at parser entry.

---

## 13. Dependency Audit

### 13.1 Runtime dependencies

| Dependency | Version | Used for | File:line |
|-----------|---------|----------|-----------|
| `symfony/polyfill-mbstring` | `^1.30` | `mb_check_encoding`, `mb_chr`, `mb_strlen` | `Lexer.php:35, 265`; `Encoder.php:201` |

**SOURCE-VERIFIED — only one runtime dependency.** Matches the directive's "verify `symfony/polyfill-mbstring` is the only one" expectation.

### 13.2 Suggested extensions

`composer.json:30`:
```
"suggest": { "ext-ds": "For improved performance with Set-based key tracking" }
```

**UNVERIFIED — No actual usage of `ext-ds` was found in `src/`.** A `Grep` for `Ds\\Set`, `Ds\\Map`, `Ds\\Vector` returned zero matches in `src/`. The Normalizer uses plain PHP arrays with `json_encode` keys for path tracking (`Normalizer.php:625-628`). The suggestion appears aspirational.

### 13.3 Dev dependencies

| Dependency | Version |
|-----------|---------|
| `phpunit/phpunit` | `^11.5 || ^12.5 || ^13.0` |
| `phpstan/phpstan` | `^2.0` |
| `php-collective/code-sniffer` | `dev-master` |

### 13.4 Optional runtime deps from bin/

`bin/toml-validate:38, 121` optionally uses `justinrainbow/json-schema` (`Validator` class) for schema validation, gated by `class_exists(Validator::class)`. **Not in composer.json** — the user must install separately.

### 13.5 Audit verdict for SStack

SOURCE-VERIFIED — Runtime dep footprint is minimal: one mbstring polyfill. SStack's "NO third-party implementation libraries at runtime" policy is **not directly satisfiable** by adopting php-collective as a dependency (the polyfill is a third-party library), but the **ideas** in php-collective are transferable to a native SStack parser that uses PHP's built-in `mbstring` extension directly (which is bundled with PHP by default in modern builds, though not always enabled — SStack should require `ext-mbstring` directly instead of a polyfill).

---

## 14. Security Posture

### 14.1 Attack surface

- **Input source:** Pure local string parsing. No network input, no eval, no `unserialize`, no shell exec in `src/`.
- **File I/O:** `Toml::decodeFile()` uses `@file_get_contents($path)` with silenced errors and checks `false` (`Toml.php:50-53`). `Toml::encodeFile()` uses `@file_put_contents($path, $toml)` (`Toml.php:124, 138`). Both throw `ParseException` / `EncodeException` on failure.
- **External process execution:** `benchmarks/compare-libraries.php:204-229` uses `proc_open` for benchmark subprocesses — but this is in `benchmarks/`, not `src/`, and not shipped as runtime code.
- **Schema validation:** `bin/toml-validate:111-127` uses `json_decode` and `Validator` on schema files — gated by `class_exists`, error-tolerant.

### 14.2 Robustness features

| Feature | File:line | Benefit |
|---------|-----------|---------|
| UTF-8 validation upfront | `Lexer.php:35-40` | Rejects malformed UTF-8 before parser confusion |
| BOM skip (single, leading only) | `Lexer.php:44-46` | Matches toml-test expectations |
| Control char rejection in strings/comments | `Lexer.php:130, 208, 380, 430, 519` | Rejects control chars except `\t` |
| Surrogate codepoint rejection | `Lexer.php:261-263` | Rejects `\uD800`–`\uDFFF` |
| Codepoint range check | `Lexer.php:261` | Rejects > `0x10FFFF` |
| Integer overflow rejection | `Lexer.php:806-813` | Prevents silent saturation to `PHP_INT_MAX` (security-relevant for IDs, sizes) |
| Hex/octal/binary overflow rejection | `Lexer.php:774-775, 784-785, 794-795` | Same for non-decimal radices |
| Hex escape limited to 2 chars | `Lexer.php:240, 247-266` | `\xHH` only, no arbitrary byte injection |

### 14.3 Recursion / DoS considerations

- **Deeply nested tables / arrays / inline tables:** No explicit recursion depth limit. `docs/reference/limitations.md:177-184` acknowledges deep nesting may cause stack issues.
- **Multi-line strings with line-continuation backslashes:** `Lexer.php:331-360` — bounded by input length.
- **`synchronizeInCollection` depth tracking** (`Parser.php:1309-1320`) prevents recovery from getting confused at the wrong nesting level, but unbalanced nesting still consumes stack via `parseArray` / `parseInlineTable` recursion.
- **No ReDoS risk** in lexer regexes — patterns are anchored (`^…$`) and simple.

### 14.4 Verdict

INFERRED — Security posture is solid for a parser library: no eval/unserialize/shell, defensive I/O, robust input validation. The main DoS vector is unbounded recursion depth on adversarial input — SStack should consider an explicit recursion-depth guard if CORE-10 will parse untrusted TOML.

---

## 15. Architectural Ideas Useful for SStack

Per directive §2.C, this section focuses on AST design and error representation.

### 15.1 AST design ideas (HIGHLY RELEVANT)

1. **Three-tier pipeline split (Lexer → Parser → Normalizer)** — Clean separation of concerns; each tier independently testable; AST is an explicit intermediate representation. **Recommend SStack adopt this.**

2. **Node interface with `getSpan()` + trivia accessors** — Every node carries source location; trivial to expose editor diagnostics. **Recommend SStack adopt this**, but make trivia immutable (use `withLeadingTrivia()` return-new pattern instead of `setLeadingTrivia()`).

3. **"Original X" pattern for round-trip preservation** — Each node carries both the live value AND nullable `original*` fields populated at construction. This enables source-aware re-encoding without requiring immutability. **Recommend SStack adopt the concept but make it immutable**: store original source spans + raw text in a separate `SourceInfo` record attached to each node, never mutated.

4. **Collection-local `singleLineStyle` shape recording** — `ArrayValue` / `InlineTable` carry a `?array $singleLineStyle` shape with `opening`/`beforeComma`/`afterComma`/`closing`/`trailingComma` fields (`ArrayValue.php:19`, `Parser.php:1182-1218`). This is a clever encoding of per-collection layout for round-trip. **Recommend SStack adopt** if CORE-10 needs round-trip editing.

5. **Mutable public-property AST — AVOID for SStack.** php-collective's AST is fully mutable (`public array $items` on Document/Table; `public` properties on every value node). This works for php-collective's editor use-case but breaks concurrent-access safety and reasoning about identity. **Recommend SStack make AST nodes `final readonly` with `with*()` methods returning new instances.**

6. **No visitor pattern — RECONSIDER.** php-collective has no `Visitor` interface; AST traversal is direct `foreach` + `instanceof`. This is fine for small ASTs but limits extensibility (no double-dispatch). **Recommend SStack add an optional `NodeVisitor` interface** with `visitDocument`, `visitTable`, `visitKeyValue`, `visitValue` methods — useful for CORE-10 validation pipelines.

7. **Value node taxonomy** — Distinguish `OffsetDateTime` (DateTimeImmutable value) from `LocalDateTime`/`LocalDate`/`LocalTime` (string values). **Strongly recommend SStack adopt this exact taxonomy** for timezone-neutrality (see §7).

### 15.2 Error model ideas (HIGHLY RELEVANT)

1. **`ParseError` as readonly struct** — `{message, span, hint, code}`. Clean, serializable, no exception overhead. **Recommend SStack adopt verbatim.**

2. **`ParseErrorCode` enum with `fromMessage()` / `fromInvalidLexeme()` classifiers** — Stable machine-readable codes retroactively derived from message stems. The classifier approach lets you add stable codes without refactoring every error site. **Recommend SStack adopt**, but consider passing the code explicitly at every call site (rather than relying on string-prefix matching) for stronger typing.

3. **`ParseResult { Document|null, ParseError[], array|null }`** — Tooling-friendly aggregate. `getDocument()` returns AST even on error → enables partial-structure display. **Recommend SStack adopt verbatim.**

4. **Two-tier error strategy: throw-first (`decode`) vs collect-all (`tryParse`)** — Same pipeline, two consumption modes. **Recommend SStack adopt.**

5. **Contextual hints** — YAML `:` vs TOML `=`, version-string quoters, YAML booleans, unquoted-string quoters (§8.8). **Strongly recommend SStack adopt** — dramatically improves configuration-author UX.

6. **`format()` method on ParseError** — CLI-style context lines + caret + hint. **Recommend SStack adopt** for CORE-10 tooling.

7. **Error recovery strategies** — Top-level `synchronize()` to newline / `[`; in-collection `synchronizeInCollection()` with depth tracking; newline-gap break-out to avoid absorbing following keys; missing-comma in-line recovery. **Recommend SStack adopt** — well-tested patterns. The `tests/Parser/ErrorRecoveryTest.php` is a goldmine of recovery test cases.

8. **Previous-exception chaining — currently MISSING.** `ParseException::__construct` calls `parent::__construct($message)` without `$previous` (`ParseException.php:18`). **Recommend SStack add `$previous` parameter** for wrapping lower-level exceptions (file I/O, etc.).

### 15.3 Lexer/Parser ideas

1. **Upfront UTF-8 validation + BOM skip** — Robust, cheap. **Recommend SStack adopt.**

2. **`filter_var($clean, FILTER_VALIDATE_INT)` for integer parsing** — Prevents silent saturation. **Recommend SStack adopt.**

3. **Special-float ambiguity handling** — Lex `nan`/`inf` as `BareKey`, re-interpret as float in value position (`Parser.php:508-521`). **Recommend SStack adopt** — cleaner than lexer context-sensitivity.

4. **Generator-based lexer + iterator_to_array at parser entry** — php-collective's pipeline is NOT actually streaming. **Recommend SStack decide explicitly:** either consume tokens lazily (true streaming, harder) or materialize (simpler, current behavior). Don't claim streaming unless it's true end-to-end.

5. **Lookahead-only, no backtracking** — Bounded performance, predictable. **Recommend SStack adopt.**

6. **Inline-table TOML 1.0/1.1 enforcement at single site** (`Parser.php:810-818`) — Clean version gating. **Recommend SStack adopt.**

### 15.4 Normalizer ideas

1. **Table "kind" state machine** (`implicit` / `explicit` / `array` / `dotted`) — Elegant duplicate-detection model. **Recommend SStack adopt.**

2. **`sealedInlineTables` + `staticArrays` immutability tracking** — Clean enforcement of TOML's "inline tables cannot be extended" / "static arrays cannot be extended" rules. **Recommend SStack adopt.**

3. **`array_key_last` descent for array-of-tables** — Correctly handles `[[fruits.varieties]]` after `[[fruits]]` by descending into the last fruits entry. **Recommend SStack adopt.**

4. **`json_encode` for path IDs — AVOID.** Use a separator-based join (`implode("\0", $segments)`) — faster, simpler, no JSON encoding overhead.

### 15.5 Datetime design (CRITICAL for SStack)

1. **Local datetime / date / time as STRINGS, never DateTimeImmutable** — Preserves timezone-neutrality. **STRONGLY recommend SStack adopt.** php-collective explicitly documents this as the correct design (`docs/reference/limitations.md:78-87`).

2. **Offset datetime as DateTimeImmutable** — Timezone is explicit in the literal, so PHP's DateTimeImmutable is semantically correct. **Recommend SStack adopt.**

3. **Encoder-side wrapper types (`Value\LocalDate`, `Value\LocalTime`, `Value\LocalDateTime`, `Value\TomlInteger`)** — Lets the encoder distinguish "this string is a local-time literal" from "this string is a quoted string". **Recommend SStack adopt.**

4. **TemporalValidator with regex + checkdate + DateTimeImmutable parse-attempt** — Robust validation. **Recommend SStack adopt.**

---

## 16. Limitations and Concerns

### 16.1 Architectural limitations

1. **AST is fully mutable** (`public array $items`, `public` properties on every node) — Concurrent-access unsafe; identity reasoning is hard. (SOURCE-VERIFIED §5.5)
2. **No visitor pattern** — Extension requires touching every consumer. (SOURCE-VERIFIED §5.3)
3. **`iterator_to_array` materializes token stream** — Pipeline is NOT streaming despite generator-based lexer. (SOURCE-VERIFIED §3, §12.4)
4. **`json_encode` for path IDs** — Wasteful at scale. (SOURCE-VERIFIED §15.4)
5. **`ext-ds` suggested but unused** — Aspirational suggestion only. (SOURCE-VERIFIED §13.2)
6. **No `$previous` exception chaining** — Limits wrapping lower-level exceptions. (SOURCE-VERIFIED §8.2)

### 16.2 Functional limitations

1. **Integers limited to PHP `int` (64-bit)** — No GMP fallback for arbitrary precision. (`docs/reference/limitations.md:6-19`)
2. **Microsecond precision only** for `OffsetDateTime` fractional seconds — Nanoseconds truncated. (`docs/reference/limitations.md:21-32`)
3. **No streaming/incremental parsing** for very large files. (`docs/reference/limitations.md:188-191`)
4. **Error recovery is line-oriented, not conformance-grade** — Self-documented. (`docs/reference/support-matrix.md:107`)
5. **Empty PHP array encodes as `[]`, not `[table]`** — Use `stdClass` to force table output. (`docs/reference/limitations.md:143-164`)
6. **Null values rejected by encoder** unless `skipNulls: true`. (`docs/reference/limitations.md:106-128`)

### 16.3 Maturity / project concerns

1. **Single maintainer** — Mark Scherer is the only contributor. (SOURCE-VERIFIED §1)
2. **8 months of history** — First commit 2026-03-25, latest 2026-11-11. (SOURCE-VERIFIED §1)
3. **0.1.x versioning** — Pre-1.0 stability; public API may change. (SOURCE-VERIFIED §1)
4. **Open issues count UNVERIFIED** — GitHub API rate-limited during investigation. (UNVERIFIED §1)

### 16.4 Documentation vs source inconsistencies

1. **Span `column` claimed 1-based but actually 0-based** in source. (`docs/reference/api.md:226` vs `Lexer.php:14-18, 80-84`; §9.4)
2. **"Generator-based for memory efficiency"** claim is partially undermined by `iterator_to_array` at parser entry. (`docs/reference/architecture.md:201` vs `Parser.php:81`; §3, §12.4)
3. **`ext-ds` suggestion** in composer.json but no usage in src/. (`composer.json:30`; §13.2)

### 16.5 SStack-specific concerns

1. **MIT license allows copying, but SStack's policy forbids it** — Must re-implement ideas, not copy code.
2. **Runtime dep on `symfony/polyfill-mbstring`** — SStack could require `ext-mbstring` directly instead.
3. **Mutable AST is incompatible with SStack's stricter contract** — Must redesign as immutable.
4. **No `$previous` exception chaining** — SStack should add this for wrapping file I/O errors.

---

## 17. Evidence Register

Every file inspected with line numbers / sections actually read:

### Source files (read in full)
| File | Lines read | Notes |
|------|-----------|-------|
| `composer.json` | 1-59 | Full read |
| `LICENSE` | 1-22 | Full read |
| `README.md` | 1-208 | Full read |
| `.github/workflows/ci.yml` | 1-59 | Full read |
| `.github/workflows/deploy-docs.yml` | 1-70 | Full read |
| `phpunit.xml` | 1-25 | Full read |
| `phpstan.neon` | 1-7 | Full read |
| `phpcs.xml` | 1-26 | Full read |
| `src/Toml.php` | 1-142 | Full read |
| `src/TomlVersion.php` | 1-11 | Full read |
| `src/Normalizer.php` | 1-629 | Full read |
| `src/Lexer/Lexer.php` | 1-875 | Full read (3 chunks: 1-250, 251-550, 551-875) |
| `src/Lexer/Token.php` | 1-21 | Full read |
| `src/Lexer/TokenType.php` | 1-44 | Full read |
| `src/Lexer/Span.php` | 1-31 | Full read |
| `src/Parser/Parser.php` | 1-1346 | Full read (3 chunks: 1-300, 301-650, 651-1050, 1051-1347) |
| `src/Parser/ParseError.php` | 1-55 | Full read |
| `src/Parser/ParseResult.php` | 1-48 | Full read |
| `src/Parser/ParseErrorCode.php` | 1-101 | Full read |
| `src/Ast/Node.php` | 1-36 | Full read |
| `src/Ast/AbstractNode.php` | 1-55 | Full read |
| `src/Ast/Document.php` | 1-20 | Full read |
| `src/Ast/Table.php` | 1-28 | Full read |
| `src/Ast/KeyValue.php` | 1-22 | Full read |
| `src/Ast/Key.php` | 1-38 | Full read |
| `src/Ast/KeyStyle.php` | 1-12 | Full read |
| `src/Ast/Trivia.php` | 1-17 | Full read |
| `src/Ast/TriviaKind.php` | 1-12 | Full read |
| `src/Ast/Value/Value.php` | 1-12 | Full read |
| `src/Ast/Value/AbstractValue.php` | 1-11 | Full read |
| `src/Ast/Value/BoolValue.php` | 1-25 | Full read |
| `src/Ast/Value/IntegerValue.php` | 1-28 | Full read |
| `src/Ast/Value/FloatValue.php` | 1-30 | Full read |
| `src/Ast/Value/StringValue.php` | 1-28 | Full read |
| `src/Ast/Value/StringStyle.php` | 1-13 | Full read |
| `src/Ast/Value/IntegerBase.php` | 1-13 | Full read |
| `src/Ast/Value/ArrayValue.php` | 1-41 | Full read |
| `src/Ast/Value/InlineTable.php` | 1-58 | Full read |
| `src/Ast/Value/OffsetDateTime.php` | 1-26 | Full read |
| `src/Ast/Value/LocalDateTime.php` | 1-25 | Full read |
| `src/Ast/Value/LocalDate.php` | 1-25 | Full read |
| `src/Ast/Value/LocalTime.php` | 1-25 | Full read |
| `src/Encoder/Encoder.php` | 1-400 | Partial read (key methods: encode, encodeDocument, encodeTable, encodeValue, encodeAstItem, encodeAstKeyValue, encodeAstTableHeader, encodeAstKey, encodeAstValue) |
| `src/Encoder/EncoderOptions.php` | 1-43 | Full read |
| `src/Encoder/DocumentFormattingMode.php` | 1-11 | Full read |
| `src/Encoder/ArrayStyle.php` | 1-31 | Full read |
| `src/Exception/TomlException.php` | 1-12 | Full read |
| `src/Exception/ParseException.php` | 1-20 | Full read |
| `src/Exception/EncodeException.php` | 1-9 | Full read |
| `src/Support/TemporalValidator.php` | 1-71 | Full read |
| `src/Value/TomlValue.php` | 1-10 | Full read |
| `src/Value/TomlInteger.php` | 1-32 | Full read |
| `src/Value/LocalDate.php` | 1-23 | Full read |
| `src/Value/LocalTime.php` | 1-23 | Full read |
| `src/Value/LocalDateTime.php` | 1-23 | Full read |

### Bin scripts (read in full)
| File | Lines read |
|------|-----------|
| `bin/toml-validate` | 1-208 |
| `bin/toml-decoder` | 1-366 |
| `bin/toml-encoder` | 1-119 |

### Benchmark script (read in full)
| File | Lines read |
|------|-----------|
| `benchmarks/compare-libraries.php` | 1-295 |

### Test files (read in full or substantial)
| File | Lines read |
|------|-----------|
| `tests/Parser/ParserTest.php` | 1-100 |
| `tests/Parser/ErrorRecoveryTest.php` | 1-358 (full) |
| `tests/Parser/ParseErrorCodeTest.php` | 1-82 (full) |
| `tests/Parser/IntegerRangeTest.php` | 1-64 (full) |
| `tests/Parser/TriviaPreservationTest.php` | 1-102 (full) |
| `tests/Conformance/FixtureCorpusTest.php` | 1-114 (full) |
| `tests/Conformance/TomlTestDecoderTest.php` | 1-191 (full) |
| `tests/Conformance/SupportedSyntaxTest.php` | 1-160 (full) |
| `tests/Conformance/SpecEdgeCaseTest.php` | 1-59 (full) |
| `tests/Conformance/LiteralsAndDateTimeTest.php` | 1-66 (full) |
| `tests/Conformance/CollectionsAndCommentsTest.php` | 1-44 (full) |
| `tests/Normalizer/SemanticValidationTest.php` | 1-172 (full) |
| `tests/PublicApi/VersioningTest.php` | 1-137 (full) |
| `tests/PublicApi/ContractsTest.php` | 1-106 (full) |
| `tests/Value/TemporalValueTest.php` | 1-38 (full) |
| `tests/Integration/RoundTripTest.php` | 1-120 (partial) |

### Test fixtures (read)
| File | Notes |
|------|-------|
| `tests/fixtures/Conformance/valid/time-without-seconds.toml` | Single line `opens_at = 07:32` |
| `tests/fixtures/Conformance/valid/multiline-inline-table.toml` | 3 lines |
| `tests/fixtures/Conformance/invalid/bad-unicode-escape.toml` | `value = "\uD800"` |
| `tests/fixtures/Conformance/invalid/bad-float-trailing-dot.toml` | `value = 1.` |
| `tests/fixtures/Conformance/semantic/array-table-dotted-extension.toml` | `[[a.b]]\n\n[a]\nb.y = 2` |
| `tests/fixtures/Conformance/semantic/inline-table-extension.toml` | `point = { x = 1 }\n\n[point]\ny = 2` |
| `tests/fixtures/Conformance/semantic/scalar-table-collision.toml` | `name = "value"\n\n[name]\nchild = 1` |
| `tests/fixtures/Conformance/semantic/dotted-key-explicit-table.toml` | `[a]\nb.c = 1\n\n[a.b]\nd = 2` |
| `tests/fixtures/Conformance/semantic/explicit-table-dotted-extension.toml` | `[a.b.c]\nz = 9\n\n[a]\nb.c.t = 8` |
| `tests/fixtures/Conformance/valid/temporal-and-inline.toml` | 3 lines |

### Docs files (read in full)
| File | Lines read |
|------|-----------|
| `docs/reference/architecture.md` | 1-239 (full) |
| `docs/reference/api.md` | 1-629 (full) |
| `docs/reference/benchmarks.md` | 1-63 (full) |
| `docs/reference/compatibility.md` | 1-55 (full) |
| `docs/reference/support-matrix.md` | 1-162 (full) |
| `docs/reference/limitations.md` | 1-192 (full) |
| `docs/guide/ast.md` | 1-299 (full) |
| `docs/guide/error-handling.md` | 1-215 (full) |

### Git operations performed (read-only)
- `git log --oneline -30` (50 commits total viewed)
- `git log -1 --format='%H%n%an%n%ad%n%s'`
- `git branch -a`
- `git tag -l`
- `git for-each-ref --sort=-creatordate --format '...' refs/tags`
- `git log --since='2026-01-01' --pretty='%ad %h %s' --date=short`
- `git log --reverse --pretty='%ad %h %s' --date=short | head -5`
- `git shortlog -s -n --all`
- `git ls-files | wc -l` (190 files)

### GitHub API queries attempted
- `curl https://api.github.com/repos/php-collective/toml/issues?state=open` → **rate-limited**, no data returned. Open-issues count is UNVERIFIED.

---

## End of analysis

**Inspection completed:** 2026-11-17
**Files inspected:** 60+ source/test/doc files
**Lines of source code read:** ~4,511 in `src/` + ~2,000 in tests + ~1,000 in docs
**Authorisation boundary:** READ-ONLY investigation maintained. No files modified in the php-collective repo. No commits created. No pushes. Analysis document written only to the deliverables directory as authorised.
