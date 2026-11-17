# SovereignStack Native TOML 1.1.0 Parser — Architecture Proposal

**Document type:** Deliverable 12 of 15
**Status:** PROPOSED — awaiting owner approval
**Inspection date:** 2026-11-17

---

## 1. Design Goals

The SStack native TOML 1.1.0 parser must achieve:

1. **Full TOML 1.1.0 conformance** — pass the official `toml-test` suite (1,060 test cases).
2. **Zero third-party runtime dependencies** — only PHP runtime + `ext-json` + `ext-mbstring`.
3. **Preserve approved CORE-10 contracts** — ConfigLoader facade, ConfigBuilder, EnvLoader, ConfigRepository, ConfigValidator, Environment enum, approved exception hierarchy, approved public Configuration API.
4. **Maintainability** — clear separation of lexing, parsing, and value-construction.
5. **Diagnostic quality** — error messages with file path, line, column, expected/actual token, contextual hints.
6. **Security** — enforce resource limits (input size, nesting depth, array/table sizes, UTF-8 validation).
7. **Failure atomicity** — failed parse leaves no accessible partial state.
8. **Testability** — internal components testable in isolation; official conformance suite runnable.

Non-goals (explicitly deferred):
- Streaming/incremental parsing (SStack config files are small).
- Error recovery (collect multiple errors per parse) — future enhancement.
- Public AST exposure — SStack builds a PHP array directly.
- Encoder/dumper — out of scope for CORE-10.

---

## 2. Component Diagram

```
                       ┌─────────────────────────────────────────┐
                       │              ConfigLoader               │
                       │   (public facade, single load() entry)   │
                       └──┬──────────────────────────────────────┘
                          │ delegates to
                          ▼
            ┌──────────────────────────────────┐
            │          ConfigBuilder            │
            │   loadFile($path) → TomlParser    │
            └──────────────────────────────────┘
                          │ invokes
                          ▼
   ┌──────────────────────────────────────────────────────────────┐
   │                       TomlParser                              │
   │   parse(string $toml, string $filePath): array<string, mixed>│
   │                                                                │
   │   ┌────────────────┐    ┌────────────────┐    ┌────────────┐ │
   │   │   TomlLexer    │───▶│  Token stream  │───▶│  Parser    │ │
   │   │                │    │                │    │  (recursive │ │
   │   │  UTF-8 check   │    │  Token[]       │    │  descent)   │ │
   │   │  char-by-char  │    │  with spans    │    │             │ │
   │   │  no backtrack  │    │                │    │  builds PHP │ │
   │   └────────────────┘    └────────────────┘    │  array      │ │
   │                                              └────────────┘ │
   │   ┌────────────────┐    ┌────────────────┐    ┌────────────┐ │
   │   │ TomlValue      │◀───│  TableBuilder  │◀───│ ParsingCtx │ │
   │   │ Decoder        │    │  (duplicate    │    │ (depth,    │ │
   │   │                │    │   key check,   │    │  path)     │ │
   │   │  strings       │    │   AoT descent) │    │            │ │
   │   │  ints/floats   │    │                │    │            │ │
   │   │  booleans      │    │                │    │            │ │
   │   │  datetimes     │    │                │    │            │ │
   │   │  arrays        │    │                │    │            │ │
   │   │  inline tables │    │                │    │            │ │
   │   └────────────────┘    └────────────────┘    └────────────┘ │
   │                                                                │
   │   ┌────────────────────────────────────────────────────────┐ │
   │   │              Resource Limits (security)                  │ │
   │   │   input_size ≤ 1MB   depth ≤ 100   array_elems ≤ 10000  │ │
   │   │   table_keys ≤ 10000  key_length ≤ 1024  string ≤ 64KB  │ │
   │   └────────────────────────────────────────────────────────┘ │
   │                                                                │
   │   ┌────────────────────────────────────────────────────────┐ │
   │   │              Error Model                                │ │
   │   │   TomlParseError (struct: message, span, hint, code)    │ │
   │   │   TomlParseErrorCode (enum: 16+ stable codes)           │ │
   │   │   → translated to ConfigurationFileException at boundary│ │
   │   └────────────────────────────────────────────────────────┘ │
   └──────────────────────────────────────────────────────────────┘
```

---

## 3. Proposed Components

### 3.1 `TomlLexer` (internal, final class)

**Responsibility:** Convert a TOML string into a token stream.

**Public contract (internal):**

```php
namespace SovereignStack\Core\Config\Toml\Internal;

final class TomlLexer
{
    /**
     * @param string $input Raw TOML input (must be valid UTF-8 — checked here).
     * @param string $filePath File path for error messages.
     * @throws ConfigurationFileException If input is not well-formed UTF-8
     *                                    or exceeds size limit.
     */
    public function __construct(string $input, string $filePath);

    /**
     * @return list<Token>
     */
    public function tokenize(): array;
}
```

**Internal behaviour:**
- Validates UTF-8 via `mb_check_encoding($input, 'UTF-8')` at construction.
- Validates input size ≤ 1 MB at construction.
- Character-by-character scanning with single-character lookahead (no backtracking).
- Tracks byte offset, line (1-indexed), column (1-indexed).
- Produces `Token` objects with `TokenType`, lexeme, and `SourceSpan`.

### 3.2 `Token`, `TokenType`, `SourceSpan` (internal, readonly structs)

```php
namespace SovereignStack\Core\Config\Toml\Internal;

enum TokenType: string
{
    case BareKey;
    case QuotedKey;
    case DotSep;           // .
    case KeyValSep;        // =
    case Comma;            // ,
    case InlineTableOpen;  // {
    case InlineTableClose; // }
    case ArrayOpen;        // [
    case ArrayClose;       // ]
    case TableHeaderStart; // [ (context distinguishes from ArrayOpen)
    case ArrayTableHeaderStart; // [[
    case BasicString;      // "..."
    case LiteralString;    // '...'
    case MultiLineBasicString; // """..."""
    case MultiLineLiteralString; // '''...'''
    case Integer;
    case Float;
    case Boolean;
    case OffsetDateTime;
    case LocalDateTime;
    case LocalDate;
    case LocalTime;
    case Newline;
    case Eof;
}

final readonly class SourceSpan
{
    public function __construct(
        public int $byteOffset,
        public int $line,
        public int $column,
        public int $length,
    ) {}
}

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $lexeme,
        public SourceSpan $span,
    ) {}
}
```

### 3.3 `TomlParser` (public, final class — preserves current API)

**Responsibility:** Consume a token stream and produce a nested PHP array.

**Public contract (unchanged from current):**

```php
namespace SovereignStack\Core\Config\Toml;

final class TomlParser
{
    /**
     * @param string $toml TOML input.
     * @param string $filePath File path for error messages.
     * @return array<string, mixed> Parsed configuration tree.
     * @throws ConfigurationFileException On any parse error.
     */
    public function parse(string $toml, string $filePath = '<string>'): array;
}
```

The public API is **unchanged** — `ConfigBuilder::loadFile()` continues to call `TomlParser::parse()` exactly as before. The refactoring is internal.

**Internal behaviour:**
- Constructs `TomlLexer` and calls `tokenize()`.
- Walks the token list with `ParsingContext` tracking depth, current table reference, and current key path.
- Dispatches to `TableBuilder` for table-header navigation and `TomlValueDecoder` for value parsing.

### 3.4 `ParsingContext` (internal)

```php
namespace SovereignStack\Core\Config\Toml\Internal;

final class ParsingContext
{
    public int $depth = 0;
    /** @var list<string> */
    public array $currentKeyPath = [];
}
```

Tracks the current parsing depth (for the 100-level limit) and the current key-path (for duplicate-key diagnostics).

### 3.5 `TableBuilder` (internal)

**Responsibility:** Navigate or create tables in the PHP array being constructed, with correct handling of:
- Standard tables (`[a]`)
- Nested tables (`[a.b.c]`)
- Quoted tables (`["a b"]`)
- Dotted tables (`[a.b.c]`)
- Array-of-tables (`[[items]]`)
- Nested array-of-tables (`[[fruits.varieties]]` after `[[fruits]]`)
- Implicit tables (created by dotted keys)

**Key invariant (fixes the current nested-AoT bug):**

```
For each segment in a table-header path:
  if isLast AND isArray:
    append new element to current[segment] (AoT extension)
    descend into new element
  else if intermediate:
    if segment does not exist: create empty array
    if segment holds a list (AoT): descend into LAST element
    if segment holds a table (assoc): descend into it
    if segment holds a scalar: ERROR (table-structure conflict)
```

This is the algorithm that fixes the SStack PR #3 nested-AoT bug (already fixed in local commit `1136065`, here generalised and made explicit).

### 3.6 `TomlValueDecoder` (internal)

**Responsibility:** Convert a token's lexeme to its PHP value.

| Token type | Decoding |
|---|---|
| `BasicString` | Process escapes (`\n`, `\t`, `\r`, `\"`, `\\`, `\b`, `\f`, `\uXXXX`, `\UXXXXXXXX`, plus new in 1.1.0: `\e`, `\xHH`) |
| `LiteralString` | Verbatim (no escapes) |
| `MultiLineBasicString` | Trim first newline if present; process escapes; honour `\` line continuation |
| `MultiLineLiteralString` | Trim first newline if present; verbatim (no escape processing, no `\r` normalisation per 1.1.0 §842) |
| `Integer` | Strip underscores; decode hex/octal/binary/decimal; apply sign; range-check `PHP_INT_MIN..PHP_INT_MAX` |
| `Float` | Strip underscores; decode exponent; handle `inf`, `nan`, signed |
| `Boolean` | `true` / `false` |
| `OffsetDateTime` | Construct `OffsetDateTime` value object (preserves offset) |
| `LocalDateTime` | Construct `LocalDateTime` value object (NO timezone) |
| `LocalDate` | Construct `LocalDate` value object (NO timezone) |
| `LocalTime` | Construct `LocalTime` value object (NO timezone) |

### 3.7 DateTime value objects (NEW — preserves timezone-neutrality)

```php
namespace SovereignStack\Core\Config\Toml\Value;

final readonly class LocalDate
{
    public function __construct(
        public int $year,
        public int $month,
        public int $day,
    ) {}

    public function format(): string  // returns "YYYY-MM-DD"
    public function toDateTimeImmutable(): \DateTimeImmutable  // explicit opt-in
}

final readonly class LocalTime
{
    public function __construct(
        public int $hour,
        public int $minute,
        public int $second = 0,
        public ?int $microsecond = null,  // sub-millisecond precision per 1.1.0 #805
    ) {}

    public function format(): string  // returns "HH:MM:SS" or "HH:MM" or "HH:MM:SS.ffffff"
    public function toDateTimeImmutable(): \DateTimeImmutable
}

final readonly class LocalDateTime
{
    public function __construct(
        public LocalDate $date,
        public LocalTime $time,
    ) {}

    public function format(): string
    public function toDateTimeImmutable(): \DateTimeImmutable  // applies default TZ
}

final readonly class OffsetDateTime
{
    public function __construct(
        public LocalDate $date,
        public LocalTime $time,
        public string $offset,  // "Z", "+07:00", "-05:00"
    ) {}

    public function format(): string
    public function toDateTimeImmutable(): \DateTimeImmutable  // preserves offset
}
```

**Why value objects instead of `DateTimeImmutable`:**

PHP's `DateTimeImmutable` constructor applies the default timezone (`date.timezone` ini) when the input has no timezone. This means a TOML local datetime `2025-01-01T00:00:00` becomes a `DateTimeImmutable` with the server's timezone attached — **incorrect for a local datetime which by definition has no timezone**.

The value objects preserve the timezone-neutrality explicitly. Callers who need a `DateTimeImmutable` must call `toDateTimeImmutable()`, which documents the timezone-application behaviour.

This approach:
- Combines Devium's correctness (no silent timezone coercion) with php-collective's precision (string preservation is equivalent).
- Aligns with the TOML spec's distinction between local and offset datetimes.
- Is auditable: the value object's immutability makes it impossible to accidentally introduce a timezone.

**NOTE for the owner:** This is a NEW addition to the SStack public API. The current `ConfigRepository::get('app.datetime')` returns a `DateTimeImmutable` (per spec §4 normalisation). The refactored parser would return a value object instead. This is a **breaking change** for callers that depend on `DateTimeImmutable`. Owner decision required: see `core-10-toml-1.1-migration-proposal.md` §9.

### 3.8 `TomlParseError`, `TomlParseErrorCode` (internal)

Inspired by php-collective's `ParseError` struct pattern (with attribution).

```php
namespace SovereignStack\Core\Config\Toml\Internal;

enum TomlParseErrorCode: string
{
    case UnclosedString = 'unclosed_string';
    case InvalidEscape = 'invalid_escape';
    case InvalidUnicodeEscape = 'invalid_unicode_escape';
    case InvalidInteger = 'invalid_integer';
    case InvalidFloat = 'invalid_float';
    case InvalidDateTime = 'invalid_datetime';
    case DuplicateKey = 'duplicate_key';
    case TableRedefinition = 'table_redefinition';
    case InvalidTableHeader = 'invalid_table_header';
    case UnexpectedToken = 'unexpected_token';
    case MissingKeyValSep = 'missing_keyval_sep';
    case InvalidBareKey = 'invalid_bare_key';
    case NestedTooDeep = 'nested_too_deep';
    case InputTooLarge = 'input_too_large';
    case ArrayTooLarge = 'array_too_large';
    case TableTooLarge = 'table_too_large';
    case InvalidUtf8 = 'invalid_utf8';
    case IntegerOverflow = 'integer_overflow';
    case TrailingData = 'trailing_data';
}

final readonly class TomlParseError
{
    public function __construct(
        public TomlParseErrorCode $code,
        public string $message,
        public SourceSpan $span,
        public ?string $hint = null,
    ) {}
}
```

**Translation to public exception:**

`TomlParser::parse()` catches `TomlParseError` instances (thrown internally) and translates them to `ConfigurationFileException::forParseError($filePath, $line, $message)` per spec §12. Third-party exception classes do NOT leak through the public API.

### 3.9 Resource Limits (enforced inside `TomlLexer` and `TomlParser`)

```php
namespace SovereignStack\Core\Config\Toml\Internal;

final class ResourceLimits
{
    public const MAX_INPUT_BYTES = 1024 * 1024;        // 1 MB
    public const MAX_NESTING_DEPTH = 100;
    public const MAX_ARRAY_ELEMENTS = 10_000;
    public const MAX_TABLE_KEYS = 10_000;
    public const MAX_KEY_LENGTH = 1024;
    public const MAX_STRING_LENGTH = 64 * 1024;        // 64 KB
}
```

Each limit is enforced at the appropriate point in the lexer/parser and throws `TomlParseError` with the relevant code (`InputTooLarge`, `NestedTooDeep`, etc.).

---

## 4. Public and Internal Contracts

### 4.1 Public API (unchanged except datetime value objects)

| Class | Visibility | Purpose |
|---|---|---|
| `TomlParser` | public | `parse(string $toml, string $filePath): array<string, mixed>` |
| `Toml\Value\LocalDate` | public | LocalDate value object |
| `Toml\Value\LocalTime` | public | LocalTime value object |
| `Toml\Value\LocalDateTime` | public | LocalDateTime value object |
| `Toml\Value\OffsetDateTime` | public | OffsetDateTime value object |

### 4.2 Internal API (not part of SStack public surface)

| Class | Visibility | Purpose |
|---|---|---|
| `Toml\Internal\TomlLexer` | internal | UTF-8 check + tokenize |
| `Toml\Internal\Token` | internal | readonly token struct |
| `Toml\Internal\TokenType` | internal | enum |
| `Toml\Internal\SourceSpan` | internal | readonly source position |
| `Toml\Internal\ParsingContext` | internal | depth + key path tracking |
| `Toml\Internal\TableBuilder` | internal | table navigation + AoT descent |
| `Toml\Internal\TomlValueDecoder` | internal | token → PHP value |
| `Toml\Internal\TomlParseError` | internal | readonly error struct |
| `Toml\Internal\TomlParseErrorCode` | internal | error code enum |
| `Toml\Internal\ResourceLimits` | internal | limit constants |

Internal classes are marked `@internal` in PHPDoc and are NOT exposed via the `ConfigInterface` or `ConfigLoader` public API. They live under the `Toml\Internal` namespace to make the boundary explicit.

---

## 5. Data-Flow Diagram

```
1. Application calls ConfigLoader::load([$configFile], $envFile, $overrides, $schema).

2. ConfigLoader creates ConfigBuilder, calls ConfigBuilder::loadFile($path) for each file.

3. ConfigBuilder::loadFile($path):
   a. Checks file exists + readable. Throws ConfigurationFileException on failure.
   b. Reads file contents via file_get_contents.
   c. Creates TomlParser, calls TomlParser::parse($contents, $path).

4. TomlParser::parse():
   a. Creates TomlLexer($toml, $filePath).
      - Lexer validates UTF-8 (mb_check_encoding).
      - Lexer validates input size ≤ 1 MB.
      - Throws ConfigurationFileException on UTF-8 or size failure.
   b. Lexer.tokenize() → list<Token>.
   c. Creates ParsingContext, TableBuilder, TomlValueDecoder.
   d. Walks tokens:
      - On table header: TableBuilder.navigateOrCreate(path, isArray).
        Enforces depth limit (≤100).
      - On key-value: parse key path, expect '=', decode value via TomlValueDecoder.
        Enforces duplicate-key check.
      - On error: throw TomlParseError internally → caught → translated to ConfigurationFileException.
   e. Returns nested PHP array.

5. ConfigBuilder merges parsed array via mergeRecursive() into accumulated data.

6. After all files loaded + EnvLoader run + overrides applied:
   ConfigBuilder::build() returns merged array.

7. ConfigLoader detects environment, validates, constructs ConfigRepository.

8. ConfigRepository returned as ConfigInterface.
```

---

## 6. Error-Flow Diagram

```
[Lexical error]                    → TomlParseError(code, msg, span, hint)
                                       │
                                       ▼
                                   TomlParser::parse() catches
                                       │
                                       ▼
                                   ConfigurationFileException::forParseError(
                                       $filePath, $span->line, "$code: $msg"
                                   )
                                       │
                                       ▼
                                   ConfigBuilder::loadFile() propagates
                                       │
                                       ▼
                                   ConfigLoader::load() propagates
                                       │
                                       ▼
                                   Application receives ConfigurationFileException.
                                   No ConfigRepository constructed.
                                   $merged data discarded (local variable out of scope).

[Semantic error: duplicate key]    → TomlParseError(DuplicateKey, ...)
                                       │
                                       ▼ (same flow as lexical error)

[Resource limit: input too large]  → TomlParseError(InputTooLarge, ...)
                                       │
                                       ▼ (same flow)

[Resource limit: nested too deep]  → TomlParseError(NestedTooDeep, ...)
                                       │
                                       ▼ (same flow)

[UTF-8 validation failure]         → ConfigurationFileException::forParseError
                                       (thrown directly by TomlLexer constructor)
                                       │
                                       ▼ (same flow)

[Integer overflow]                 → TomlParseError(IntegerOverflow, ...)
                                       │
                                       ▼ (same flow)

[EnvLoader failure]                → ConfigurationFileException::forMalformedLine
                                       (existing behaviour, unchanged)
                                       │
                                       ▼ (same flow as above from ConfigLoader::load())

[Environment detection failure]    → InvalidEnvironmentException
                                       (existing behaviour, unchanged)

[Validation failure]               → MissingConfigurationException OR
                                   InvalidConfigurationException
                                       (existing behaviour, unchanged)
```

---

## 7. Parsing Algorithm

### 7.1 Lexer algorithm

```
input: string $toml, string $filePath
output: list<Token>

1. Validate UTF-8: if !mb_check_encoding($toml, 'UTF-8'):
     throw ConfigurationFileException::forParseError($filePath, 1, 'input is not valid UTF-8')

2. Validate size: if strlen($toml) > 1_048_576:
     throw ConfigurationFileException::forParseError($filePath, 1, 'input exceeds 1 MB limit')

3. Initialize:
     position = 0
     line = 1
     column = 1
     tokens = []

4. Loop until position == strlen($toml):
     skipWhitespaceAndComments()  // updates line, column, position
     if position == strlen($toml): break

     char = $toml[position]
     span_start = (position, line, column)

     switch char:
       case '[':
         if next char is '[': emit ArrayTableHeaderStart; consume 2 chars
         else: emit TableHeaderStart; consume 1 char
       case '"':
         if next 2 chars are '""': scan multi-line basic string; emit MultiLineBasicString
         else: scan basic string; emit BasicString
       case "'":
         if next 2 chars are "''": scan multi-line literal string; emit MultiLineLiteralString
         else: scan literal string; emit LiteralString
       case '=': emit KeyValSep
       case '.': emit DotSep
       case ',': emit Comma
       case '{': emit InlineTableOpen
       case '}': emit InlineTableClose
       case ']': emit ArrayClose
       case '\n': emit Newline; line++; column = 1
       case '0'..'9', '+', '-':
         // Could be integer, float, or datetime
         scan ahead to disambiguate (look for '-', ':', 'T', ' ', 'e', '.')
         emit Integer | Float | OffsetDateTime | LocalDateTime | LocalDate | LocalTime
       case 'i': scan 'inf'; emit Float
       case 'n': scan 'nan'; emit Float
       case 't': scan 'true'; emit Boolean
       case 'f': scan 'false'; emit Boolean
       case 'A'..'Z', 'a'..'z', '_', '-':
         scan bare key; emit BareKey
       default:
         throw TomlParseError(UnexpectedToken, "unexpected character '{char}'", span)

5. Emit Token(Eof, span)
6. Return tokens
```

**Critical property:** The lexer uses single-character lookahead and commits on the first character. There is NO backtracking. This bounds parse time to O(n).

### 7.2 Parser algorithm

```
input: list<Token>, string $filePath
output: array<string, mixed>

1. Initialize:
     root = []
     currentTable = &root  // reference
     ctx = new ParsingContext(depth=0, keyPath=[])

2. Loop over tokens:
     token = next()

     switch token.type:
       case TableHeaderStart:
         expect ']'  // (parse key path inside [ ])
         keyPath = parseKeyPath()
         expect ']'
         ctx.depth = keyPath length
         if ctx.depth > 100: throw TomlParseError(NestedTooDeep, ...)
         currentTable = TableBuilder.navigateOrCreate(root, keyPath, isArray=false, ctx)

       case ArrayTableHeaderStart:
         expect ']]'  // (parse key path inside [[ ]])
         keyPath = parseKeyPath()
         expect ']]'
         ctx.depth = keyPath length
         if ctx.depth > 100: throw TomlParseError(NestedTooDeep, ...)
         currentTable = TableBuilder.navigateOrCreate(root, keyPath, isArray=true, ctx)

       case BareKey, QuotedKey:
         // Key-value pair
         keyPath = parseKeyPath()  // may be dotted
         expect KeyValSep (=)
         value = TomlValueDecoder.decode(next(), ctx)
         setNested(currentTable, keyPath, value)  // with duplicate-key check
         // expect newline or EOF
         skipNewlines()

       case Newline:
         continue  // skip blank lines

       case Eof:
         break

       default:
         throw TomlParseError(UnexpectedToken, "expected table header or key, got {token}", token.span)

3. Return root
```

### 7.3 Table-construction algorithm (TableBuilder.navigateOrCreate)

```
input: array &$root, list<string> $keyPath, bool $isArray, ParsingContext $ctx
output: array& (reference to the table to fill)

current = &$root
for i, segment in keyPath:
  isLast = (i == count(keyPath) - 1)

  if isLast and isArray:
    # Array-of-tables: append new element
    if !isset(current[segment]) or !is_array(current[segment]):
      current[segment] = []
    if !is_list(current[segment]):
      throw TomlParseError(TableRedefinition, "key '{segment}' is not an array-of-tables")
    current[segment][] = []
    current = &current[segment][last]

  elseif isLast:
    # Standard table: must not already exist as a table
    if isset(current[segment]):
      if is_array(current[segment]) and !is_list(current[segment]):
        # Table already exists explicitly — redefinition
        # (TOML allows redefining a sub-table only via dotted keys, not [header])
        throw TomlParseError(TableRedefinition, "table '{segment}' already defined")
      if is_list(current[segment]):
        # Array-of-tables — cannot redefine as standard table
        throw TomlParseError(TableRedefinition, "key '{segment}' is an array-of-tables, cannot redefine as table")
      else:
        # Scalar value exists — cannot redefine as table
        throw TomlParseError(TableRedefinition, "key '{segment}' has scalar value, cannot redefine as table")
    current[segment] = []
    current = &current[segment]

  else:
    # Intermediate segment
    if !isset(current[segment]):
      current[segment] = []
      current = &current[segment]
    elseif is_array(current[segment]):
      if is_list(current[segment]) and !empty(current[segment]):
        # Array-of-tables: descend into LAST element
        current = &current[segment][last]
      else:
        # Regular table: descend into it
        current = &current[segment]
    else:
      # Scalar exists at intermediate path — conflict
      throw TomlParseError(TableRedefinition, "key '{segment}' has scalar value, cannot descend")

return current
```

---

## 8. Datetime Representation

### 8.1 The problem

PHP's `DateTimeImmutable` constructor applies the `date.timezone` ini setting when the input string has no timezone. This means:

```php
$dt = new \DateTimeImmutable('2025-01-01T00:00:00');
echo $dt->format('c');  // "2025-01-01T00:00:00+00:00" (or server's default TZ)
```

The TOML local datetime `2025-01-01T00:00:00` has NO timezone. PHP silently attaches one. This is incorrect for TOML semantics.

### 8.2 The solution

Use value objects that explicitly carry the absence of timezone:

- `LocalDate` — year/month/day, no timezone.
- `LocalTime` — hour/minute/second/microsecond, no timezone.
- `LocalDateTime` — composition of LocalDate + LocalTime, no timezone.
- `OffsetDateTime` — LocalDate + LocalTime + offset string, preserves original offset.

Callers who need a `DateTimeImmutable` call `toDateTimeImmutable()`:
- For `LocalDate`/`LocalTime`/`LocalDateTime`: the method applies the default timezone (documented behaviour).
- For `OffsetDateTime`: the method preserves the original offset.

### 8.3 Comparison with reference implementations

| Approach | Used by | SStack assessment |
|---|---|---|
| `DateTimeImmutable` for all datetimes (current SStack) | SStack PR #3 | BUG — silent timezone coercion for local datetimes |
| `DateTimeImmutable` correctly naive | Devium | WORKS — but requires careful construction |
| String preservation through AST | php-collective | WORKS — but loses type safety |
| String in AST (lost type info) | PetalBranch | BUG — round-trip failure |
| **Value objects** (proposed for SStack) | none of the three | BEST — explicit, type-safe, no silent coercion |

---

## 9. Resource-Limit Strategy

| Limit | Value | Enforced where | Error code |
|---|---|---|---|
| Input size | 1 MB | `TomlLexer` constructor | `InputTooLarge` |
| Nesting depth | 100 levels | `ParsingContext` depth tracking | `NestedTooDeep` |
| Array elements | 10,000 per array | `TomlValueDecoder::decodeArray()` counter | `ArrayTooLarge` |
| Table keys | 10,000 per table | `TableBuilder` counter | `TableTooLarge` |
| Key length | 1,024 chars | `TomlLexer::parseKey()` | `InvalidBareKey` (or `KeyTooLong` — could add new code) |
| String length | 64 KB per string | `TomlLexer::scanString()` | `UnclosedString` (or new `StringTooLong` code) |
| Integer range | `PHP_INT_MIN..PHP_INT_MAX` | `TomlValueDecoder::decodeInteger()` | `IntegerOverflow` |

All limits are **non-configurable constants** in `ResourceLimits`. They are NOT runtime-tunable. This prevents an attacker from tricking the application into relaxing a limit.

---

## 10. Integration with CORE-10

The parser integrates with the existing CORE-10 architecture WITHOUT changing any public API except datetime value objects:

| Existing component | Change required |
|---|---|
| `ConfigLoader::load()` | NONE — still calls `ConfigBuilder::loadFile()` |
| `ConfigBuilder::loadFile()` | NONE — still calls `(new TomlParser())->parse($contents, $path)` |
| `ConfigBuilder::build()` | NONE |
| `ConfigRepository` | NONE — receives PHP array as before |
| `ConfigValidator` | NONE — validates array as before |
| `Environment::detect()` | NONE — reads string from merged config as before |
| `EnvLoader` | NONE — unrelated to TOML parsing |
| Approved exception hierarchy | NONE — `ConfigurationFileException` remains the public exception type |
| Approved public Configuration API | NONE — `get()`/`getOrFail()`/`has()`/`all()`/`allRaw()`/`getEnvironment()` unchanged |

**Only addition:** four datetime value objects (`LocalDate`, `LocalTime`, `LocalDateTime`, `OffsetDateTime`) under `SovereignStack\Core\Config\Toml\Value\`. These are returned by `ConfigRepository::get('some.date.key')` instead of `DateTimeImmutable`.

**Breaking change for callers:** any code that does `$repo->get('app.datetime')->format('Y-m-d')` will break because the return is now a `LocalDateTime` value object, not a `DateTimeImmutable`. The value object has a `format()` method, but the format string syntax may differ.

**Mitigation:** the value objects implement `__toString()` returning the canonical TOML format, and provide a `toDateTimeImmutable()` method for callers who need the PHP type. Migration is mechanical: `->format('Y-m-d')` → `->format()` (canonical) or `->toDateTimeImmutable()->format('Y-m-d')`.

**Owner decision required:** see `core-10-toml-1.1-migration-proposal.md` §9.

---

## 11. Testability

The internal classes (`TomlLexer`, `TomlParser` internal, `TableBuilder`, `TomlValueDecoder`) can each be unit-tested in isolation. The public `TomlParser::parse()` is the integration entry point.

**Recommended test structure:**

```
tests/Toml/
  Unit/
    TomlLexerTest.php           — tokenization tests
    TomlValueDecoderTest.php    — value decoding tests
    TableBuilderTest.php        — table navigation tests
    ResourceLimitsTest.php      — limit enforcement tests
  Integration/
    TomlParserTest.php          — end-to-end parse tests (existing)
  Conformance/
    ConformanceTest.php         — runs vendored toml-test fixtures
    Valid/                      — vendored valid TOML fixtures (~535)
    Invalid/                    — vendored invalid TOML fixtures (~525)
    LICENSE-ATTRIBUTION.md      — toml-test MIT license attribution
```

See `toml-conformance-analysis.md` §6 for the vendored-fixture strategy.

---

## 12. Implementation Order

If the owner approves this architecture, the recommended implementation order is:

1. **Create internal namespace structure** (`src/Core/Config/Toml/Internal/`).
2. **Implement `SourceSpan`, `Token`, `TokenType`** (read-only structs).
3. **Implement `TomlParseErrorCode` enum** and `TomlParseError` struct.
4. **Implement `ResourceLimits` constants.**
5. **Implement `TomlLexer`** with UTF-8 + size validation, character-by-character scanning, no backtracking.
6. **Implement `TomlValueDecoder`** for all value types.
7. **Implement datetime value objects** (`LocalDate`, `LocalTime`, `LocalDateTime`, `OffsetDateTime`).
8. **Implement `TableBuilder`** with the nested-AoT descent algorithm.
9. **Implement `ParsingContext`** with depth tracking.
10. **Refactor `TomlParser::parse()`** to use the new components.
11. **Vendor the `toml-test` fixtures** into `tests/Toml/Conformance/`.
12. **Write `ConformanceTest.php` runner.**
13. **Write `TomlTestJsonEncoder.php` helper.**
14. **Run the full conformance suite; fix failures.**
15. **Update SStack CORE-10 spec to reflect TOML 1.1.0 and the new datetime value objects.**

Each step is independently testable. No step requires spec modification (except step 15, which is the migration proposal).

---

## 13. Cross-References

- TOML 1.0.0 → 1.1.0 spec changes: `toml-version-comparison.md`
- Conformance suite strategy: `toml-conformance-analysis.md`
- Security resource limits: `security-analysis.md`
- Performance targets: `performance-analysis.md`
- CORE-10 spec migration proposal: `core-10-toml-1.1-migration-proposal.md`
- Existing approved contracts (preserved): `core-10-toml-1.1-migration-proposal.md` §3
