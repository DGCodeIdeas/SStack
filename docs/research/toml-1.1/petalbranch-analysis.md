# PetalBranch TOML — Technical Analysis

**Repository:** https://github.com/PetalBranch/toml
**Inspection date:** 2026-11-17
**HEAD commit:** d8553b064a73ed78e835d5372b2cde66b88837a2
**License:** Apache-2.0 (verified by reading LICENSE.txt header — lines 2–4 show "Apache License, Version 2.0, January 2004")
**Local clone:** `/home/z/my-project/research/toml-1.1/repos/petalbranch/`
**Authorisation:** READ-ONLY research reference. No code was copied. All snippets ≤10 lines.

**Evidence classification tags used throughout:** `SOURCE-VERIFIED` · `DOCUMENT-VERIFIED` · `INFERRED` · `UNVERIFIED`

---

## TL;DR — Headline Findings

| # | Claim (from README / composer.json) | Verdict | Evidence |
|---|-------------------------------------|---------|----------|
| 1 | "Zero-copy TOML parser" | **REFUTED** | Lexer builds every string via `$lexeme .= $char` (`Lexer.php:454,500,666,790`); `Token::$lexeme` is a value string (`Token.php:30`); 21 `substr()` call sites across `src/`; **zero** uses of `SplFixedArray`, `StringRef`, `StringView`, or pass-by-reference. |
| 2 | "O(1) Memory … 1KB or 100MB … memory approaches O(1)" | **REFUTED (mathematically impossible)** | `LazyTokenStream::$buffer` is a `private array $buffer = []` that accumulates **every** token (`LazyTokenStream.php:30,175`); AST retains all `Node` objects; source string retained by reference in both Lexer and Parser. The minimum bound for parsing is O(N), not O(1). |
| 3 | "Industrial-grade" | **PARTIALLY SUPPORTED** | PHPStan Level 9 (`phpstan.neon:2`) is real; but there is **no CI** (no `.github/workflows/`), only 7 commits from 1 contributor in a 3-day window (2026-03-20 → 2026-03-22), 8 months inactive, only 5 PHPUnit tests, and the toml-test runner committed is a Windows-only `.exe`. |
| 4 | "toml-test v2.1.0: Decoder 100%, Encoder 99.5%" | **DOCUMENT-VERIFIED but UNVERIFIED by independent execution** | The Windows `.exe` is bundled (`tests/toml-test-v2.1.0-windows-amd64.exe`); cannot be executed on Debian trixie; results are reported only in README. |
| 5 | "True Lossless Round-Trip" | **PARTIALLY REFUTED** | Datetime values are stored in the AST as **plain strings** (`Parser.php:663-669`) while the Dumper side requires `TomlSerializableInterface` instances (`ValueDumper.php:61-62`). Parse-then-Dump of a datetime will currently emit a quoted string. |
| 6 | TOML 1.1.0 support | **SOURCE-VERIFIED** | `\e` escape (`Lexer.php:549`), `\xHH` escape (`Lexer.php:552,925-941`), inline-table newlines (`Parser.php:374`), inline-table dotted keys (`Parser.php:388`). |
| 7 | PHP `^8.3` requirement | **OVER-CONSERVATIVE** | No `#[Override]`, no `ini_get_all` 8.3 features; `readonly class`/`readonly final class` is PHP 8.2; `never` return type and enums are PHP 8.1. Code would run on 8.2. |
| 8 | "Abandons inefficient `mb_str_split`" | **MISLEADING** | The codebase still calls `mb_strlen`, `mb_substr`, `mb_strwidth`, `mb_chr`, `mb_ord` extensively. The byte-cursor optimisation is real but the rhetorical contrast with `mb_str_split` is a strawman. |
| 9 | "Pixel-level error pointer" | **SOURCE-VERIFIED** | `ParseException.php:62-77` computes `mb_strwidth`-aware padding and pointer length, with full-width/emoji handling. Good feature, well implemented. |

---

## 1. Repository Identity and Maturity

### 1.1 Basic identity — DOCUMENT-VERIFIED

- **URL:** `https://github.com/PetalBranch/toml` (`composer.json:14`, `git remote -v`)
- **Default branch:** `main` (only branch besides `origin/HEAD -> origin/main`)
- **HEAD commit:** `d8553b0` "feat(core): 添加全局辅助函数支持" (commit message in Chinese)
- **Tags:** `v1.0.0`, `v1.1.0`, `v1.2.0` (3 releases — `git tag -l`)
- **License:** Apache-2.0 (`LICENSE.txt:2-4`, `composer.json:15`)

### 1.2 Maintenance activity — SOURCE-VERIFIED (via `git log`)

```
d8553b0 | 2026-03-22 | feat(core): 添加全局辅助函数支持
0ecfd7a | 2026-03-22 | feat(dumper): 添加等号对齐功能并优化注释渲染
2f26def | 2026-03-22 | docs(readme): ... feat(parser): 实现 TOML 解析器核心功能并重构代码结构
f85d7b9 | 2026-03-21 | feat(parser): 重构 TOML 日期时间解析并添加专用类型支持
405d4b3 | 2026-03-21 | fix(parser): 完善 TOML 解析器的错误处理和规范兼容性
8048871 | 2026-03-20 | feat(parser): 添加值节点尾随注释收集功能
df7f05f | 2026-03-20 | feat(parser): 添加 TOML 解析器核心组件
```

- **Total commits:** 7
- **Active development window:** 3 days (2026-03-20 → 2026-03-22)
- **Time since last commit at inspection:** ~8 months
- **Contributors:** 1 (`git shortlog -sn --all` returns `5 PetalBranch` + `2 Petalbranch` — same person, two spellings of the name)
- **Verdict:** This is a single-author, single-burst project with **no ongoing maintenance signal**. No commits, no tags, no PRs visible since 2026-03-22.

### 1.3 Stars / open issues — UNVERIFIED

GitHub REST API call returned `"API rate limit exceeded for 47.57.242.119"` — public metadata (stars, forks, open issues) could not be retrieved. **Marked UNVERIFIED per directive §2.D.**

### 1.4 PHP version requirement — DOCUMENT-VERIFIED + SOURCE-VERIFIED

- `composer.json:28` declares `"php": "^8.3"`.
- The code uses `readonly class` / `readonly final class` (a **PHP 8.2** feature), `never` return type (PHP 8.1), backed enums (PHP 8.1), `mixed` type (PHP 8.0).
- Grep for `#[Override]` (PHP 8.3 attribute) returned **no matches**.
- **Verdict:** The `^8.3` floor is **over-conservative** — the code would compile and run on PHP 8.2. This is a minor policy smell: tightening the floor without using 8.3-only features artificially limits adoption. For SStack purposes the codebase is portable to PHP 8.2+.

### 1.5 Runtime dependencies — DOCUMENT-VERIFIED + SOURCE-VERIFIED

`composer.json:27-31`:
```json
"require": {
    "php": "^8.3",
    "ext-json": "*",
    "ext-mbstring": "*"
}
```

- `ext-json`: used via `json_encode` in `ValueDumper.php:118`, `toml-test-decoder.php:34,119`.
- `ext-mbstring`: heavily used — `mb_strlen`, `mb_substr`, `mb_strwidth`, `mb_chr`, `mb_ord` across `Lexer.php`, `Parser.php`, `ParseException.php`, `NodeDumper.php`, `TomlKeyHelper.php` (verified by Grep).
- **No third-party runtime libraries.** This satisfies SStack's strict "no third-party implementation libraries at runtime" policy at the dependency level.

### 1.6 Dev dependencies — DOCUMENT-VERIFIED

`composer.json:32-35`:
```json
"require-dev": {
    "phpunit/phpunit": "^11.0 || ^12.0 || ^13.0",
    "phpstan/phpstan": "^2.1"
}
```

Dev-only. No leaked runtime dependency.

### 1.7 Static analysis — DOCUMENT-VERIFIED

`phpstan.neon` (entire file, 8 lines):
```yaml
parameters:
    level: 9
    paths:
        - src
    # 忽略一些不可避免的动态特性警告
    ignoreErrors:
```

- **Level 9 (max)** confirmed. Paths restricted to `src/` only (no `tests/`).
- `ignoreErrors:` is empty — no suppressed rules. Good.
- However: PHPStan is **not run in CI** (no `.github/workflows/` exists). The Level 9 badge is a one-shot maintainer claim, not an enforced invariant.

### 1.8 CI workflows — SOURCE-VERIFIED (absence verified)

- `find .github -type f` returned no files.
- `ls -la .github` returned "NO .github DIR".
- **Verdict:** There is **no continuous integration** configured. Every claim that depends on automated verification (PHPStan Level 9, toml-test pass rate) is only as trustworthy as the maintainer's manual run.

### 1.9 Test framework — DOCUMENT-VERIFIED

- `composer.json:62-65` exposes `composer test` → `vendor/bin/phpunit`.
- `tests/TomlTest.php` is a PHPUnit `TestCase` (verified — `tests/TomlTest.php:7,17`).
- Additional test scripts: `tests/toml-test-decoder.php`, `tests/toml-test-encoder.php` (compliance harness), `tests/round-trip.php`, `tests/align-test.php`, `tests/toml_edge_cases.php` (free-form PHP scripts, not PHPUnit).
- `tests/testnote.md` lists 4 manual test focus areas: lexer memory/perf, lossless writeback, equal-sign alignment, PHPStan.

---

## 2. Source Architecture

### 2.1 Tree overview — SOURCE-VERIFIED

49 PHP source files, ~4,682 LOC. The `src/` tree contains **15 directories** (not "10" as the brief suggested). Grouped by responsibility:

```
src/
├── Toml.php                    # Public facade (static methods: parse, parseFile, dump, dumpFile)
├── functions.php               # Global helpers: toml_decode(), toml_encode()
│
├── Parser/
│   ├── Lexer.php               # 1021 lines — char-by-char scanner with byte-cursor UTF-8 probe
│   └── Parser.php              # 1104 lines — recursive-descent table builder
│
├── Builder/
│   └── NodeBuilder.php         # Hydrator: PHP array → AST (for Dumper side)
│
├── Dumper/
│   ├── Dumper.php              # Facade: Builder + NodeDumper
│   ├── NodeDumper.php          # AST → TOML string (recursive)
│   ├── ValueDumper.php         # Scalar → TOML literal (with TomlSerializableInterface fast path)
│   └── TypeFormatter.php       # Pluggable formatter (priority-ordered)
│
├── Model/
│   ├── DumperConfig.php        # Mutable config bag (indent, newline, inlineTable, alignEquals)
│   ├── Entry.php               # (key, NodeInterface) pair
│   ├── KeyPath.php             # Immutable list of path segments
│   └── Node/
│       ├── Node.php            # Abstract base (position, raw, leadingComments, trailingComment)
│       ├── ValueNode.php       # Scalar leaf (TomlType + mixed value)
│       ├── ArrayNode.php       # Sequence (and [[table-array]] when declaredAsTableArray=true)
│       ├── TableNode.php       # Map (string → Entry)
│       └── InlineTableNode.php # Subclass of TableNode (isInline()=true)
│
├── Support/
│   ├── Token.php               # readonly DTO: type, lexeme, value, line/col, endLine/endCol
│   ├── LazyTokenStream.php     # Generator-backed stream; buffers ALL tokens on demand
│   ├── Position.php            # (line, col) pair
│   ├── ErrorContext.php        # (line, previousLine, nextLine) for diagnostics
│   ├── ThrowsErrorTrait.php    # Builds ErrorContext from $rawSource on throw
│   ├── TomlKeyHelper.php       # Bare-key detection + escapeString
│   ├── ArrayHelper.php         # isAssoc()
│   ├── TomlDate.php            # LOCAL_DATE typed value object
│   ├── TomlTime.php            # LOCAL_TIME typed value object
│   ├── TomlLocalDateTime.php   # LOCAL_DATETIME typed value object
│   └── TomlOffsetDateTime.php  # OFFSET_DATETIME typed value object
│
├── Type/
│   ├── TokenType.php           # 22 enum cases (lexer-side)
│   ├── TomlType.php            # 10 enum cases (AST-side)
│   └── ParseErrorType.php      # 13 enum cases
│
├── Exception/
│   ├── ParseException.php      # RuntimeException + typed properties + ASCII-art context
│   └── DumpException.php       # Dumper-side exception
│
└── Contract/                   # 13 interfaces, all in sub-namespaces
    ├── Lexer/      {LexerInterface, TokenInterface, TokenStreamInterface}
    ├── Parser/     {ParserInterface, NodeInterface, TableNodeInterface, ArrayNodeInterface}
    ├── Dumper/     {DumperInterface, NodeDumperInterface, ValueDumperInterface, TypeFormatterInterface, TomlSerializableInterface}
    ├── Type/       {TomlTemporalInterface}  (marker interface)
    └── Exception/  {TomlExceptionInterface, ParseExceptionInterface, DumpExceptionInterface}
```

### 2.2 Component diagram (text form) — INFERRED from reading the source

```
                  ┌─────────────────────────────────────────┐
                  │              Petalbranch\Toml\Toml       │  ← facade
                  │  parse() / parseFile() / dump() / dumpFile() │
                  └───────────┬─────────────────────┬───────┘
                              │                     │
                  ┌───────────▼─────────┐   ┌───────▼────────────┐
                  │  Parser\Parser      │   │  Dumper\Dumper     │  ← facade
                  │  + Parser\Lexer     │   │  (Builder + NodeDumper)│
                  └───────────┬─────────┘   └───────┬────────────┘
                              │                     │
                  ┌───────────▼─────────┐   ┌───────▼────────────┐
                  │ Support\LazyTokenStream │   │ Builder\NodeBuilder │  ← hydrator
                  │  (Generator → array buffer) │ └────────┬───────────┘
                  └───────────┬─────────┘            │
                              │                      │
                  ┌───────────▼─────────┐   ┌───────▼────────────┐
                  │  Model\Node\*       │   │  Dumper\NodeDumper  │
                  │  TableNode /        │◄──┤  + ValueDumper      │
                  │  ArrayNode /        │   │  + TypeFormatter    │
                  │  ValueNode /        │   └─────────────────────┘
                  │  InlineTableNode    │
                  └─────────────────────┘
```

### 2.3 Public API entry points — SOURCE-VERIFIED

- **`Petalbranch\Toml\Toml`** (`src/Toml.php`): static `parse(string): array`, `parseFile(string): array`, `dump(mixed, ?DumperConfig): string`, `dumpFile(string, mixed, ?DumperConfig): bool`, `enableDetailedErrors(bool)`.
- **`toml_decode()` / `toml_encode()`** global functions (`src/functions.php:17,32`) auto-loaded via composer `files` (`composer.json:40-42`). Designed to mimic PHP's `json_decode`/`json_encode`.
- **`Parser\Parser::parseToNode(string): TableNodeInterface`** — direct AST entry point (used by `round-trip.php`).
- **`Dumper\NodeDumper::dump(NodeInterface): string`** — AST-aware dumper, used for "lossless" round-trip.

---

## 3. Lexer Analysis

### 3.1 Approach — SOURCE-VERIFIED

Class: `Petalbranch\Toml\Parser\Lexer` (`src/Parser/Lexer.php`).

**Architecture:** Stateful char-by-char scanner returning a `Generator<Token>`. A byte-cursor (`$this->cursor`) walks `$this->source` (the full input string). Each iteration of `scan()` dispatches on the current character to one of `scanNewline`, `scanComment`, `scanSingleCharToken`, `scanString`, or `scanIdentifierOrNumber`. EOF yields a final `Token(TokenType::EOF, '', null, …)`.

**UTF-8 handling** uses an ord()-based length probe — `Lexer.php:163-185`:
```php
private function updateCurrentChar(): void
{
    if ($this->cursor >= $this->byteLength) { $this->currentChar = null; $this->currentCharLen = 0; return; }
    $byte = ord($this->source[$this->cursor]);
    if ($byte < self::UTF8_BYTE_128) { $this->currentCharLen = 1; }
    elseif ($byte < self::UTF8_2BYTE_THRESHOLD) { $this->currentCharLen = 2; }
    elseif ($byte < self::UTF8_3BYTE_THRESHOLD) { $this->currentCharLen = 3; }
    else { $this->currentCharLen = 4; }
    $this->currentChar = substr($this->source, $this->cursor, $this->currentCharLen);
}
```

This is a reasonable UTF-8 byte-length probe — but note the **`substr()` call on line 184**: every character of every multibyte string is copied into a fresh PHP string `$this->currentChar`. **This is a per-character copy, the opposite of zero-copy.**

### 3.2 Zero-copy verification (LEXER-SIDE) — REFUTED

Direct code evidence that every string is built by character-by-character concatenation:

- `scanLiteralString()` `Lexer.php:437-466`:
```php
$lexeme = "'";
$value = "";
while (!$this->isAtEnd() && $this->currentChar() !== "'") {
    ...
    $lexeme .= $char;
    $value .= $char;
    $this->advance();
}
```
- `scanBasicString()` `Lexer.php:485-524` — same pattern, `$lexeme .= $char;` `Lexer.php:500,509`.
- `scanMultilineLiteralString()` `Lexer.php:613-680` — same pattern at lines 644, 666, 667.
- `scanMultilineBasicString()` `Lexer.php:693-803` — same pattern at lines 724, 757, 774, 790, 791.
- `scanComment()` `Lexer.php:325-350` — `$comment .= $this->advance();` line 345.

**Token storage is also a value copy.** `Support\Token.php:17-38`:
```php
final readonly class Token implements TokenInterface
{
    public function __construct(
        private TokenType $type,
        private string    $lexeme,   // ← full string, not a slice view
        private mixed     $value,
        private int       $line,
        private int       $column,
        private int       $endLine,
        private int       $endColumn,
    ) {}
```

`$lexeme` is a `string` value property, not a `(int offset, int length)` view pair. PHP strings are copy-on-write, but each token still owns a separate buffer.

**No zero-copy idiom in the codebase.** Grep for `SplFixedArray | StringRef | StringView | RopeData | mb_str_split | SplStack | SplQueue | Fiber | memory_get_usage | SplDoublyLinkedList` returned **zero matches** across `src/`. Grep for `&$` (pass-by-reference) returned **zero matches**. Grep for `substr(` returned **21 occurrences across 6 files**.

### 3.3 Lookahead — SOURCE-VERIFIED

`peekChar(int $charOffset = 1): ?string` (`Lexer.php:224-241`) simulates forward cursor movement without mutating state, returning the target character. This is a cloned traversal (calls `getCharLength` per step), not a slice.

### 3.4 Regex usage — SOURCE-VERIFIED

`Lexer.php:50-63` defines a `private const array patterns` of 12 regexes for identifier/number/datetime classification. These are applied in `scanIdentifierOrNumber()` (`Lexer.php:855-914`) via `preg_match($regex, $subject, $matches)` in a sequential `foreach`. This is **NOT** a "byte cursor only" approach — the README claim "abandons inefficient `mb_str_split`, adopting low-level byte cursors and dynamic UTF-8 probing techniques" is partially true (for char-length detection) but **misleading** in implying regex is avoided.

Worse, `isAlphaNumericOrDash()` (`Lexer.php:396-400`) calls `preg_match('/^[a-zA-Z0-9_\-+]$/', $char)` on **every single character** of every bare key — a per-character regex invocation. A trivial byte-comparison lookup table would be both faster and idiomatic. This is a real performance smell.

### 3.5 Escape sequences — SOURCE-VERIFIED

`scanEscapeSequence()` `Lexer.php:539-567` handles `\b \t \n \f \r \e \" \\` plus `\xHH` (`scanHexEscape` at `Lexer.php:925-941`) and `\uXXXX / \UXXXXXXXX` (`scanUnicodeEscape` at `Lexer.php:579-602`). Unicode scalar validation rejects surrogates `0xD800-0xDFFF` and code points above `0x10FFFF` (`Lexer.php:597`).

**TOML 1.1.0-specific support confirmed:** `\e` (ESC, U+001B) at `Lexer.php:549` and `\xHH` (basic hex escape) at `Lexer.php:552`. These are 1.1.0 additions; their presence is a positive spec-compliance signal.

### 3.6 Whitespace, comments, newlines — SOURCE-VERIFIED

- Whitespace `' '` / `"\t"` skipped silently in main loop (`Lexer.php:102-105`).
- Newlines: `scanNewline()` handles both `\n` and `\r\n`. **Bare `\r` is rejected** with `INVALID_CHAR` (`Lexer.php:287-298`) — spec-compliant per TOML 1.1.0.
- Comments: `scanComment()` consumes from `#` to next newline, **rejects control characters except Tab** (`Lexer.php:336-343`).
- Inside strings, bare `\r` is also rejected (`consumeStringNewline` `Lexer.php:950-974`).

### 3.7 Source positions — SOURCE-VERIFIED

`Token` carries `line, column, endLine, endColumn` (`Token.php:33-36`). Column is **character-based**, not byte-based: `Lexer.php:257` does `$this->column++` per advance, and `Lexer.php:905` does `$this->column += mb_strlen($matchedText, 'UTF-8');`. This is correct for human-facing diagnostics.

---

## 4. Parser Analysis

### 4.1 Approach — SOURCE-VERIFIED

Class: `Petalbranch\Toml\Parser\Parser` (`src/Parser/Parser.php`). Recursive-descent / state-machine hybrid driven by a `TokenStreamInterface` (constructed by `Lexer::tokenize()`).

Main loop `parseStream()` `Parser.php:139-182`:
1. `collectLeadingComments()` — slurps consecutive `COMMENT`/`NEWLINE` tokens.
2. Dispatch on current token type:
   - `LEFT_BRACKET` → `parseTableDefinition()` (`Parser.php:434-498`)
   - key token → `parseEntry()` (`Parser.php:194-228`)
   - else → `UNEXPECTED_TOKEN` error.

### 4.2 Table construction — SOURCE-VERIFIED

`resolveTableContext()` `Parser.php:766-899` walks a `KeyPath` segment by segment:
- Implicit-create intermediate `TableNode`s (`Parser.php:783-788`) marked `setImplicit(true)`.
- For `[table]` redefinition of an implicit table, **promote** the implicit table to explicit (`Parser.php:874-880`) — this is the correct TOML semantic for `[a.b.c]` after `a.b = {c = 1}` style definition.
- For `[[array.tables]]`, on first encounter creates an `ArrayNode(declaredAsTableArray=true)` and appends a fresh `TableNode` (`Parser.php:834-855`).

### 4.3 Array-of-tables sub-element traversal — SOURCE-VERIFIED

When walking an intermediate path and encountering an existing `ArrayNode` that is a table-array, the parser **enters the last element** (`Parser.php:791-802`):
```php
if ($node instanceof ArrayNode && $node->isTableArray()) {
    $elements = $node->getElements();
    if (empty($elements)) { $this->throwError("Internal Error: Table array '$segment' is empty", ...); }
    $current = end($elements);
}
```

This correctly handles the canonical `[[fruits]]` then `[[fruits.varieties]]` pattern from the TOML 1.1.0 spec example.

### 4.4 Inline tables — SOURCE-VERIFIED

`parseInlineTable()` `Parser.php:367-422`:
- **TOML 1.1.0 newline support confirmed:** `skipNewlinesAndComments()` is called at line 374 inside the inline-table loop. The comment on that line states "TOML 1.1.0 允许内联表换行" ("TOML 1.1.0 allows inline-table newlines"). Correct.
- **TOML 1.1.0 dotted-key support confirmed:** `Parser.php:387-389` — if the key path has > 1 segment, `insertDottedKey` is invoked. Comment: "TOML 1.1.0 允许内联表使用点号键".

### 4.5 Duplicate-key detection — SOURCE-VERIFIED

Multi-layered:
- `TableNode::set()` `TableNode.php:112-119` throws `DUPLICATE_KEY` if entry already exists (defensive).
- `parseInlineTable()` checks `has()` before `set()` for single-segment keys (`Parser.php:392-400`).
- `insertDottedKey()` `Parser.php:914-990` performs layered conflict detection: rejects redefinition of `[table]`-defined tables by dotted keys (`Parser.php:956-962`), and rejects dotted-key creation against an existing `[table]` definition.
- A `SplObjectStorage` (`Parser.php:53-54, 917-920, 937, 956`) tracks tables created via dotted keys, to distinguish them from `[table]`-defined tables. This is a clever and correct approach.

### 4.6 Integer / float parsing — SOURCE-VERIFIED

`parseInteger()` `Parser.php:583-592`:
- `0x` prefix → `hexdec()`
- `0o` prefix → `octdec()` (with `0o`→`0` substitution — a hack because PHP's `octdec` doesn't accept `0o`)
- `0b` prefix → `bindec()`
- Otherwise `(int)$value` after lexer strips underscores.

`parseFloat()` `Parser.php:603-614` handles `inf`, `+inf`, `-inf`, `nan`, `+nan`, `-nan`. The special values are returned as PHP's `INF` / `NAN` constants.

### 4.7 Control flow smell — INFERRED

`Parser.php:661-672` — broad `catch (Exception)` block swallows the underlying `Exception` and re-throws a generic `INVALID_CHAR`. The original stack trace and message are lost:
```php
try {
    return match ($type) {
        TomlType::LOCAL_DATE => (new TomlDate(new DateTimeImmutable($normalized)))->formatToml(),
        ...
    };
} catch (Exception) {
    $this->throwError("Invalid datetime format", ParseErrorType::INVALID_CHAR, $token->getLine(), $token->getColumn());
}
```
This is a minor observability bug — debugging datetime construction failures is harder than it should be. PHPStan Level 9 should also complain about the unused variable in `catch (Exception)` (no `$e` binding) — but does not, because there is no `$e` variable to complain about.

---

## 5. AST and Value Representation

### 5.1 Node hierarchy — SOURCE-VERIFIED

```
NodeInterface (Contract/Parser/NodeInterface.php)
  └── Node (abstract, Model/Node/Node.php)
       ├── ValueNode      (leaf: TomlType + mixed value)
       ├── TableNode      (entries: array<string, Entry>)
       │    └── InlineTableNode  (isInline()=true)
       └── ArrayNode      (elements: list<NodeInterface>; declaredAsTableArray bool)
```

Each `Node` carries:
- `?Position $position` — line/column of the original token
- `string $raw` — original source substring
- `?array $leadingComments` — list of comment strings preceding this node
- `?string $trailingComment` — single trailing comment after `=`/value

### 5.2 Value representation — SOURCE-VERIFIED + INFERRED (asymmetry flagged)

`ValueNode` stores `TomlType $type` and `mixed $value`. The `value` field is **NOT** always a typed object:

- **INTEGER/FLOAT/BOOLEAN/STRING**: PHP native types (`int`, `float`, `bool`, `string`).
- **DATETIME types** (`OFFSET_DATETIME`, `LOCAL_DATETIME`, `LOCAL_DATE`, `LOCAL_TIME`): the Parser stores a **formatted string**, NOT a `TomlDate` / `TomlLocalDateTime` / etc. instance. See `Parser.php:663-669`:
```php
return match ($type) {
    TomlType::LOCAL_DATE => (new TomlDate(new DateTimeImmutable($normalized)))->formatToml(),
    TomlType::LOCAL_TIME => ($this->createTomlTime($normalized))->formatToml(),
    TomlType::LOCAL_DATETIME => (new TomlLocalDateTime(new DateTimeImmutable($normalized)))->formatToml(),
    TomlType::OFFSET_DATETIME => (new TomlOffsetDateTime(new DateTimeImmutable($normalized)))->formatToml(),
    ...
};
```

The typed `Toml*` object is constructed and **immediately discarded** — only its `formatToml()` string output is kept. This is wasteful (constructs objects just to format them) and breaks symmetry with the Dumper side (see §6 below).

### 5.3 AST mutability — SOURCE-VERIFIED

`ValueNode::setValue(mixed, ?TomlType)` `ValueNode.php:86-107` infers type from PHP type:
- `is_int` → `INTEGER`
- `is_float` → `FLOAT`
- `is_bool` → `BOOLEAN`
- `is_string` → `STRING`
- default → keeps existing type

This is convenient for the "modify AST and re-dump" workflow but **does not handle datetime re-assignment** — a user replacing a datetime value with another datetime string would have its type silently coerced to `STRING` unless they pass `$type` explicitly. This is a documented footgun.

### 5.4 Entry preservation order — SOURCE-VERIFIED

`TableNode::$entries` is `array<string, Entry>` (`TableNode.php:28`). PHP arrays preserve insertion order, so source-order is preserved. `getEntries()` re-indexes via `array_values` (`TableNode.php:164`) for clean list output.

---

## 6. Datetime Handling — CRITICAL: timezone preservation audit

### 6.1 The four temporal types — SOURCE-VERIFIED

| Type | Class | File | PHP backing | Output format |
|------|-------|------|-------------|---------------|
| `LOCAL_DATE` | `TomlDate` | `Support/TomlDate.php` | `DateTimeImmutable` (time zeroed at `setTime(0,0)`, `TomlDate.php:57`) | `Y-m-d` (line 84) |
| `LOCAL_TIME` | `TomlTime` | `Support/TomlTime.php` | `int $hour, $minute, $second, $microsecond` (no `DateTime`) | `HH:MM:SS[.ffffff]` (line 52) |
| `LOCAL_DATETIME` | `TomlLocalDateTime` | `Support/TomlLocalDateTime.php` | `DateTimeImmutable` (no TZ forced) | `Y-m-d\TH:i:s[.ffffff]` (line 124) |
| `OFFSET_DATETIME` | `TomlOffsetDateTime` | `Support/TomlOffsetDateTime.php` | `DateTimeImmutable` (TZ required) | `Y-m-d\TH:i:s[.ffffff](Z\|±HH:MM)` (line 149) |

### 6.2 Timezone-neutrality — **PARTIALLY COMPROMISED** (semantic issue)

`TomlLocalDateTime` wraps a `DateTimeImmutable` (`TomlLocalDateTime.php:71,82`). The constructor (`TomlLocalDateTime.php:80-95`) accepts a `DateTimeImmutable` **without enforcing a UTC or floating timezone**. When the Parser constructs it via `new DateTimeImmutable($normalized)` (`Parser.php:666`) with a string like `"2023-12-25T14:30:45"` (no TZ suffix), PHP attaches the **system default timezone** (`date.timezone` ini setting, or UTC if unset).

Consequences:
1. **Output is correct:** `formatToml()` (`TomlLocalDateTime.php:117-125`) emits only `Y-m-d\TH:i:s` with no TZ suffix, so the dumped TOML is still a valid `local-date-time`.
2. **Internal semantics are subtly wrong:** if a user calls `getDateTime()` (`TomlLocalDateTime.php:104-107`) and does arithmetic on it (e.g., `$dt->modify('+1 hour')`), the result is in the system TZ, not in a "floating" TZ. Two `TomlLocalDateTime` instances parsed on servers with different `date.timezone` settings may compare unequal in absolute-time terms even though they represent the same wall-clock.
3. **Round-trip across TZ changes is unsafe:** if a process parses with `date.timezone=Asia/Tokyo`, serialises the `TomlLocalDateTime` object somehow (e.g., via `var_export` + cache), and a different process with `date.timezone=UTC` unserialises it, the wall-clock shifts by 9 hours.

For SStack, the correct pattern is to **always use a floating TZ** (`new DateTimeZone('UTC')` is wrong; PHP doesn't have a true floating TZ in `DateTimeImmutable`) or to store the raw wall-clock components separately and never expose a `DateTimeImmutable` from a `LOCAL_*` type. PetalBranch's design is a partial implementation of this — it exposes the components (`year`, `month`, `day`, `hour`, `minute`, `second`, `nanosecond`) as public readonly properties (`TomlLocalDateTime.php:27-69`), but also exposes the `DateTimeImmutable` getter.

### 6.3 TomlOffsetDateTime — SOURCE-VERIFIED

Constructor (`TomlOffsetDateTime.php:96-114`) requires a TZ-aware `DateTimeImmutable`; the docblock says so but the code does **not enforce it** — passing a TZ-less `DateTimeImmutable` would silently use the system default. There is no runtime `throw new InvalidArgumentException` despite the docblock claiming one.

`formatToml()` (`TomlOffsetDateTime.php:136-150`): if offset is 0, emits `Z`; otherwise `P` format (`±HH:MM`). Correct per TOML spec.

### 6.4 TomlDate — SOURCE-VERIFIED

`TomlDate.php:54-61` constructor zeroes the time via `$date->setTime(0, 0)` — good. `formatToml()` returns `Y-m-d`. The internal `DateTimeImmutable` will still have whatever TZ was attached at construction time, but since output is date-only, this is invisible. No bug observed.

### 6.5 TomlTime — SOURCE-VERIFIED

`TomlTime.php:32-39` stores `int $hour, $minute, $second, $microsecond` — no `DateTimeImmutable` involvement at all. This is the cleanest of the four temporal types. `formatToml()` `TomlTime.php:50-60` produces `HH:MM:SS[.ffffff]` with trailing-zero stripping.

### 6.6 Microsecond vs nanosecond — INFERRED (terminology issue)

All `Toml*` classes expose a `public int $nanosecond` property (e.g., `TomlLocalDateTime.php:69`) but actually compute it as `$microsecond * 1000` (`TomlLocalDateTime.php:94`, `TomlOffsetDateTime.php:110`). PHP's `DateTimeImmutable::format('u')` returns microseconds, not nanoseconds. So the property is named `nanosecond` but only ever holds multiples of 1000 — it is effectively microsecond-resolution. This is a documentation/precision mismatch.

### 6.7 Parse→Dump roundtrip asymmetry — CRITICAL ROUNDTRIP BUG (SOURCE-VERIFIED)

This is the most important finding for the SStack reference analysis. Trace:

1. **Parse side:** `Parser::parseValue()` (`Parser.php:286-308`) calls `parseDatetime()` for temporal tokens, which **returns a string** (the result of `->formatToml()`). `ValueNode` is constructed with `type=LOCAL_DATETIME` but `value="2023-12-25T14:30:45"` (string).

2. **Dump side from AST:** `NodeDumper::dumpInlineNode()` calls `$this->valueDumper->dump($node->getValue())` (`NodeDumper.php:235`). `getValue()` returns the string. `ValueDumper::dump()` (`ValueDumper.php:59-80`) checks `instanceof TomlSerializableInterface` (false for a string), then iterates registered `TypeFormatter`s, then falls through to `match(true)`:
```php
return match (true) {
    is_string($value) => $this->dumpString($value),   // ← QUOTES THE STRING
    is_bool($value) => $value ? 'true' : 'false',
    is_float($value) => $this->dumpFloat($value),
    is_int($value), $value instanceof Stringable => (string)$value,
    ...
};
```
   So a `ValueNode(type=LOCAL_DATETIME, value="2023-12-25T14:30:45")` is dumped as `"2023-12-25T14:30:45"` — a **quoted TOML string**, not an unquoted datetime. **This is invalid TOML for the original type.**

3. **Why the encoder test "passes":** `tests/toml-test-encoder.php` (`toml-test-encoder.php:53-56`) explicitly **wraps** incoming JSON-tagged values in `TomlOffsetDateTime`, `TomlLocalDateTime`, etc. instances before calling `Dumper::dump()`. So the encoder test goes `typed-object → Builder → ValueNode(value=typed object) → NodeDumper → ValueDumper → TomlSerializableInterface::toToml()` (unquoted). It **never** exercises the parse→AST→dump path.

4. **The README's round-trip demo** (`README-EN.md:104-134`) shows parsing, modifying `port` (an int) and `password` (a string) values, then re-dumping. It does **NOT** modify a datetime value, so the bug is not surfaced.

**Verdict:** The "True Lossless Round-Trip" claim (`README-EN.md:34-37`) is **qualified/refuted for datetime values**: a round-trip that touches a datetime via AST modification will not preserve datetime typing unless the user manually reconstructs `Toml*` instances.

---

## 7. Error Handling

### 7.1 Exception hierarchy — SOURCE-VERIFIED

```
Throwable
└── RuntimeException
    └── ParseException  (implements ParseExceptionInterface, extends RuntimeException)
        └── public readonly ParseErrorType $type
        └── public readonly int $lineNumber, $columnNumber
        └── public readonly ?ErrorContext $context
        └── public readonly int $length
        └── public static bool $detailedErrorOutput = true   ← MUTABLE GLOBAL
```

`DumpException` exists separately for dumper-side errors.

### 7.2 Diagnostics — SOURCE-VERIFIED, GENUINELY GOOD

`ParseException::__construct()` (`ParseException.php:36-86`) builds a multi-line message:

```
[<ErrorType>] <message> (Line: L, Column: C)

  L-1 | <previous line>
> L   | <error line>
            ^^^^
  L+1 | <next line>
```

Pointer width is `mb_strwidth`-aware (`ParseException.php:66-77`) so emoji and full-width CJK characters receive the correct visual width. The `^` count = `max(1, mb_strwidth($errorText, 'UTF-8'))`.

This is a genuinely nice feature. The README's "pixel-level pointer" rhetoric is overblown (terminal characters are not pixels), but the underlying implementation is sound.

### 7.3 Error recovery — SOURCE-VERIFIED (none)

`ThrowsErrorTrait::throwError()` is declared `: never` (`ThrowsErrorTrait.php:36`). All parse errors immediately abort. There is **no error-recovery mode** (e.g., skip-to-next-newline-and-continue) for batch validation use cases. The README's "TOML Linter / Validation Tool" suggestion (`README-EN.md:89`) is therefore aspirational — a real linter needs multi-error collection.

### 7.4 Global mutable state smell — SOURCE-VERIFIED

`ParseException::$detailedErrorOutput` (`ParseException.php:23`) is a `public static bool` mutated by `Toml::enableDetailedErrors(false)` (`Toml.php:129-132`). This is **process-global mutable state**. In an async PHP runtime (Swoole, RoadRunner, FrankenPHP) this is unsafe — different requests sharing a worker process will race on the flag. SStack should reject this pattern in any borrowed ideas.

### 7.5 Error type taxonomy — SOURCE-VERIFIED

`ParseErrorType` enum (`Type/ParseErrorType.php:14-54`) has 13 cases: `DUPLICATE_KEY`, `INVALID_TYPE`, `INVALID_TABLE`, `INVALID_ARRAY`, `INVALID_CHAR`, `UNEXPECTED_EOF`, `INTERNAL_ERROR`, `UNEXPECTED_TOKEN`, `CONFLICTING_KEY`, `INVALID_TABLE_DEFINITION`, `UNKNOWN_SCALAR_TYPE`, `FILE_NOT_FOUND`, `FILE_READ_FAILED`. Reasonable coverage, but `INVALID_TABLE` and `INVALID_ARRAY` are **declared but never thrown** — Grep across `src/` confirms no `throwError(..., ParseErrorType::INVALID_TABLE, ...)` or `INVALID_ARRAY` call site exists. Dead enum cases.

---

## 8. Zero-Copy Claim Verification — DETAILED (THE KEY DELIVERABLE)

### 8.1 What "zero-copy" should mean

In systems programming, "zero-copy parsing" denotes a design where the parser produces tokens / AST nodes that **reference slices of the original input buffer** rather than allocating new string storage. Concretely, a zero-copy lexer in PHP would:

- Store each `Token`'s lexeme as an `(int $offset, int $length)` pair, NOT as a `string $lexeme`.
- Provide `getLexeme(): string` that lazily does `substr($this->source, $this->offset, $this->length)` — at most one allocation per access, and only when actually needed.
- Use `SplFixedArray` (constant-time indexing, no rehash) instead of `array` for the token stream — or, better, **not buffer the full token stream at all** (true generator consumption).
- Avoid `substr()` in hot paths — use `str_starts_with`, `str_contains`, byte comparisons, or `substr_compare` against the original buffer.
- Pass strings by reference (`&$`) where mutation is intended, or never copy.

### 8.2 What PetalBranch actually does — SOURCE-VERIFIED (refuting evidence)

| Test | Expected if zero-copy | Observed | Verdict |
|------|----------------------|----------|---------|
| Token storage | `(offset, length)` view | `private string $lexeme` value property (`Token.php:30`) | **REFUTES** |
| Per-char scan | No `substr()` in hot path | `substr($this->source, $this->cursor, $this->currentCharLen)` per character (`Lexer.php:184`) | **REFUTES** |
| String building | Slice into source buffer | `$lexeme .= $char; $value .= $char;` per character (`Lexer.php:454,455,500,512,666,667,724,725,757,774,790,791`) | **REFUTES** (12 call sites) |
| Token stream | Generator consumed directly, no buffering | `private array $buffer = []` accumulates all tokens (`LazyTokenStream.php:30,175`) | **REFUTES** |
| `SplFixedArray` use | Present for token storage | Grep returns **zero** matches across `src/` | **REFUTES** |
| Pass-by-reference (`&$`) | Used for slice mutation | Grep returns **zero** matches across `src/` | **REFUTES** |
| `substr()` call sites | Avoided entirely | 21 occurrences across 6 files (`Lexer.php:8, Parser.php:7, NodeDumper.php:2, ValueDumper.php:1, TypeFormatter.php:1, ParseException.php:2`) | **REFUTES** |
| Identifier pre-scan | Use `strpos`/`substr_compare` on buffer | Builds `$subject .= substr($this->source, $lookaheadCursor, $len)` per char (`Lexer.php:875`) | **REFUTES** |
| Comment collection | Slice into source | `$comment .= $this->advance()` (`Lexer.php:345`) — character concatenation | **REFUTES** |

### 8.3 What is real

The **byte-cursor UTF-8 length probe** (`Lexer.php:163-185`, `getCharLength()` at `Lexer.php:990-1003`) is genuinely faster than the `mb_str_split`-based approach used by some naive PHP TOML parsers. Using `ord($this->source[$position])` and comparing against UTF-8 leading-byte thresholds is a real and commendable optimisation.

But this is a **micro-optimisation at the character-iteration layer**, not zero-copy parsing. The README conflates "we don't call `mb_str_split`" with "we don't copy strings", which is a category error.

### 8.4 Memory bound — SOURCE-VERIFIED (refuting O(1) claim)

For an N-byte input file:
- `$this->source` (Lexer): N bytes (retained for full parse — needed for `peekChar` and error context).
- `$this->rawSource` (Parser, via `ThrowsErrorTrait`): N bytes (retained for error diagnostics).
- `LazyTokenStream::$buffer`: O(T) where T = number of tokens. For typical TOML, T is roughly proportional to N (one token per few bytes).
- AST (`TableNode::$entries`, `ArrayNode::$elements`, `ValueNode::$value`): O(N) — every byte of every string literal ends up as a PHP string in the AST.

**The minimum theoretical memory is O(N)** (you must hold at least the input and the parsed structure). The README's "memory approaches O(1) whether parsing 1KB or 100MB" is **mathematically impossible**.

The reasonable interpretation is that the author means "memory scales sub-linearly with file size beyond the input itself" — but even that is **false here**, because the AST duplicates every string value (`$lexeme` AND `$value` per Token, AND `$value` per ValueNode).

### 8.5 Verdict on the "zero-copy" claim

**REFUTED.** The phrase "zero-copy" in `composer.json:3` and "Zero-Copy Lexer" in `README-EN.md:31` and `README.md:28` is **marketing language unsupported by the source code**. The implementation is a straightforward copy-based recursive-descent parser with a byte-cursor UTF-8 probe — a fine and idiomatic PHP implementation, but not zero-copy by any reasonable definition of the term.

For SStack's research purposes: do not borrow the "zero-copy" framing. The byte-cursor UTF-8 probe (`Lexer.php:163-185`) is the only genuinely transferable optimisation idea, and even that is well-known folklore.

---

## 9. TOML 1.1.0 Support — Verified Feature-by-Feature

The README badges "TOML v1.1.0". Feature-level verification:

| 1.1.0 feature | Status | Source evidence |
|---------------|--------|-----------------|
| `\e` escape sequence (U+001B) | **VERIFIED** | `Lexer.php:549` — `'e' => "\e"` with comment "Toml v1.1.0 新增" |
| `\xHH` hex escape (basic) | **VERIFIED** | `Lexer.php:552` — `'x' => $this->scanHexEscape()`; `scanHexEscape()` at `Lexer.php:925-941` |
| Inline tables may contain newlines | **VERIFIED** | `Parser.php:374` — `skipNewlinesAndComments()` called inside inline-table loop |
| Dotted keys inside inline tables | **VERIFIED** | `Parser.php:387-389` — calls `insertDottedKey` for multi-segment keys |
| Hex / octal / binary integers (`0x`, `0o`, `0b`) | **VERIFIED** (also 1.0.0) | `Lexer.php:58-60` patterns; `Parser.php:586-588` decoding |
| Arbitrary underscores in integers/floats | **VERIFIED** (also 1.0.0) | Patterns at `Lexer.php:57,58,59,60,61` use `_?` |
| `inf`, `nan`, `+inf`, `-inf`, `+nan`, `-nan` floats | **VERIFIED** (also 1.0.0) | `Lexer.php:52` pattern; `Parser.php:608-610` mapping |
| RFC 3339 / ISO 8601 datetimes with `Z`/offset | **VERIFIED** | `Lexer.php:53` pattern; `TomlOffsetDateTime.php:147` `Z` output |
| Leap-second seconds value `60` | **VERIFIED** | `Parser.php:653` — `$ss > 60` check allows `60` |
| Bare `\r` rejected | **VERIFIED** (also 1.0.0) | `Lexer.php:287-298`, `Lexer.php:958-966` |
| Control chars in comments rejected (except Tab) | **VERIFIED** | `Lexer.php:336-343` |
| Control chars in strings rejected (except Tab) | **VERIFIED** | `Lexer.php:450-452, 495-497, 662-664, 786-788` |

**Verdict:** TOML 1.1.0-specific features (`\e`, `\xHH`, inline-table newlines, inline-table dotted keys) are **genuinely implemented**. The 1.1.0 badge is justified on feature grounds.

### 9.1 One spec deviation observed

`Parser.php:543-554` — when the Lexer over-eagerly classifies a sequence like `1.23` as a FLOAT token but the context is a key path, the Parser splits it back into segments `["1", "23"]`. This is correct TOML behaviour (bare keys may contain digits and dots are path separators). However, the implementation uses `explode('.', $val)` (`Parser.php:544`) — if a key segment contained a quoted `.` (e.g., `"a.b"`), the float-detection regex would not have triggered, so this path is safe. INFERRED: no bug here, but the heuristic is fragile.

---

## 10. Test Coverage and Independent Execution Results

### 10.1 Test organisation — SOURCE-VERIFIED

`tests/` directory contents:

| File | Type | Purpose |
|------|------|---------|
| `TomlTest.php` | PHPUnit `TestCase` | 5 unit tests (parse, parseFile, parseFileNotFound, dump, dumpWithConfig, dumpFile) |
| `toml-test-decoder.php` | Free-form PHP script | toml-test compliance decoder harness (reads STDIN, emits JSON) |
| `toml-test-encoder.php` | Free-form PHP script | toml-test compliance encoder harness (reads tagged JSON, emits TOML) |
| `toml-test-v2.1.0-windows-amd64.exe` | Windows binary | The toml-test runner itself — Windows x86-64 only |
| `round-trip.php` | Free-form PHP script | Demo of parse→modify AST→dump |
| `align-test.php` | Free-form PHP script | Demo of equal-sign alignment |
| `toml_edge_cases.php` | Free-form PHP script | 12 invalid-TOML cases, asserts each throws |
| `testnote.md` | Markdown notes | 4 focus areas: lexer memory/perf, lossless writeback, alignment, PHPStan |

### 10.2 PHPUnit coverage — SOURCE-VERIFIED (thin)

`TomlTest.php` is **208 lines** covering 5 test methods:
1. `testParse` — basic TOML with table, dates array. Asserts only outer structure (line 76: "这里为了门面测试的简洁性，只检查最外层结构").
2. `testParseFile` — file I/O roundtrip with 2 keys.
3. `testParseFileNotFoundThrowsException` — error path.
4. `testDump` — basic dump with 3 assertions via regex.
5. `testDumpWithConfig` — inline table config.
6. `testDumpFile` — file write.

**Coverage gaps:**
- No test for datetime round-trip via AST.
- No test for the `0o`/`0x`/`0b` integer forms.
- No test for `\e` or `\xHH` escapes.
- No test for inline-table newlines (1.1.0 feature).
- No test for inline-table dotted keys (1.1.0 feature).
- No test for `inf`/`nan` float handling.
- No test for the equal-sign alignment feature (only a free-form script).
- No test for multi-line basic/literal strings.
- No test for the error diagnostic output format.
- No performance / memory benchmark.

### 10.3 Independent execution — UNVERIFIED (blocked by environment)

The directive §2.D requires: "Independently verify performance and compatibility claims."

**Blocker:** The sandboxed environment at `/home/z/...` does **not** have PHP installed:
- `/home/z/php-local/usr/bin/php8.4` (mentioned in the task brief) **does not exist**.
- `which php php8.3 php8.4 composer` returned nothing.
- `apt-get install php8.4-cli` requires root / password.
- The toml-test runner is `toml-test-v2.1.0-windows-amd64.exe` — a Windows binary that cannot run on Debian trixie.

Per directive §2.D, all performance and toml-test compatibility claims are therefore classified **UNVERIFIED** in this report. The README's "Decoder 100% pass, Encoder 99.5% pass" (`README-EN.md:185-187`) is **DOCUMENT-VERIFIED** (the README states it) but **NOT independently verified** (the test runner cannot be executed here).

**Recommendation to SStack:** If the SStack team wishes to verify the toml-test pass rate, they should:
1. Acquire a platform-appropriate `toml-test` binary from `https://github.com/toml-lang/toml-test/releases`.
2. Install PHP 8.3+ on a Linux or macOS host.
3. Run `cd tests && toml-test test -toml 1.1 -decoder "php toml-test-decoder.php" -encoder "php toml-test-encoder.php"`.
4. Compare actual pass counts against the README's 214/213 numbers.

---

## 11. Performance Claims vs Evidence

### 11.1 Claims inventoried — DOCUMENT-VERIFIED

| Claim | Source | Strength of claim |
|-------|--------|-------------------|
| "O(1) Memory Extremely Fast Parsing" | README-EN.md:31, README.md:28 | Absolute |
| "Whether parsing a 1KB or 100MB configuration file, memory usage approaches O(1)" | README-EN.md:33 | Absolute |
| "low-level byte cursors and dynamic UTF-8 probing techniques" | README-EN.md:32-33 | Descriptive |
| "Memory Usage (Lexer): O(1) extremely fast byte cursor" | README-EN.md:208 (comparison table) | Absolute |
| "Common Implementations: O(N) full array split" | README-EN.md:208 | Comparative |

### 11.2 Evidence — SOURCE-VERIFIED

| Claim | Evidence | Verdict |
|-------|----------|---------|
| O(1) memory for lexer | `LazyTokenStream::$buffer` grows to hold all tokens (`LazyTokenStream.php:30,175`); source retained (`Lexer.php:75-76`); tokens hold full string lexemes (`Token.php:30`) | **REFUTED** |
| "100MB file uses O(1) memory" | Theoretical minimum is O(N) since input must be in memory | **MATHEMATICALLY IMPOSSIBLE** |
| "low-level byte cursor" | `ord($this->source[$this->cursor])` is used for UTF-8 length probe (`Lexer.php:172,994`) | **PARTIALLY TRUE** (probe only) |
| "dynamic UTF-8 probing techniques" | The byte-length probe is real (`Lexer.php:163-185,990-1003`) | **TRUE** (but standard UTF-8 folklore, not novel) |
| Avoids `mb_str_split` | No `mb_str_split` calls in `src/` (Grep returns no matches) | **TRUE** |
| Uses `mb_*` functions | `mb_strlen`, `mb_substr`, `mb_strwidth`, `mb_chr`, `mb_ord` called extensively | **TRUE** (so the rhetorical contrast is misleading) |
| Per-character regex `preg_match('/^[a-zA-Z0-9_\-+]$/', $char)` | `Lexer.php:399` | **PERFORMANCE SMELL** (should be a byte lookup table) |

### 11.3 No benchmark — SOURCE-VERIFIED (absence)

- No `bench.php`, no `benchmark/` directory, no `phpbench` config.
- The README's `## ⚙️ Performance & Production Environment Suggestions` section (`README-EN.md:312-328`) recommends **caching the parsed array to a PHP file via `var_export`** — effectively acknowledging that the parser is slow enough to need caching. This is honest but contradicts the "extremely fast parsing" framing.

### 11.4 Verdict

All performance claims are **UNVERIFIED** by independent benchmark, and the O(1) memory claim is **REFUTED** by code structure. The byte-cursor UTF-8 probe is a genuine micro-optimisation but does not amount to "zero-copy" or "O(1) memory".

---

## 12. Memory Allocation Strategies

### 12.1 Per-character string concatenation — SOURCE-VERIFIED

The dominant allocation pattern is `$lexeme .= $char;` in 12 call sites (see §3.2). PHP's string concatenation uses a doubling-buffer strategy internally, so the amortised cost is O(L) per string of length L — but it still allocates a fresh buffer per string, and the buffer is then handed off to the `Token` constructor as a value.

### 12.2 Token stream buffering — SOURCE-VERIFIED

`LazyTokenStream::ensureBufferHas(int $targetIndex)` `LazyTokenStream.php:172-193`:
```php
private function ensureBufferHas(int $targetIndex): void
{
    while (!isset($this->buffer[$targetIndex]) && $this->generator->valid()) {
        $this->buffer[] = $this->generator->current();
        $this->generator->next();
    }
    ...
}
```

The buffer is a `private array $buffer` (`LazyTokenStream.php:30`) that grows monotonically as the parser requests further tokens. `peek(int $offset)` (`LazyTokenStream.php:90-102`) triggers `ensureBufferHas($this->cursor + $offset)` — so lookahead forces the buffer to grow.

This is **not** lazy in the practical sense: by the time the parser reaches EOF, the buffer contains every token. The "lazy" framing is technically accurate (tokens are produced on demand) but functionally irrelevant — there is no scenario in which the buffer does not eventually hold all tokens.

### 12.3 AST retention — SOURCE-VERIFIED

The AST retains:
- Every `Token`'s `$lexeme` AND `$value` (often duplicated strings — for a literal string `'foo'`, lexeme = `'foo'` AND value = `foo`).
- Every `Node`'s `$raw` (often yet another copy of the lexeme).
- Every `ValueNode`'s `$value` (for strings, another copy of the lexer's `$value`).
- Every `Entry` (key + node reference).
- Leading and trailing comments as separate strings per node.

For a TOML file with many string values, the AST retains roughly **3-4× the source size** in PHP string buffers.

### 12.4 Generator use — SOURCE-VERIFIED

The Lexer's `scan()` method (`Lexer.php:96-148`) is declared `: Generator`. The Generator is consumed by `LazyTokenStream`. PHP Generators themselves are memory-efficient (they don't materialise a full array), but the buffer defeats this benefit.

A genuinely memory-efficient design would either:
- Consume the Generator directly in the Parser without buffering (requires single-token lookahead, which TOML mostly allows), or
- Use a ring buffer of size `max_lookahead` (TOML needs at most ~3 tokens of lookahead).

PetalBranch does neither.

---

## 13. Dependency Audit

### 13.1 Runtime — DOCUMENT-VERIFIED + SOURCE-VERIFIED

| Dependency | Type | Required by | SStack-OK? |
|------------|------|-------------|------------|
| `php ^8.3` | Language | All | Yes (SStack targets PHP 8.3+) |
| `ext-json *` | Bundled ext | `ValueDumper.php:118`, `toml-test-decoder.php` | Yes (always present) |
| `ext-mbstring *` | Bundled ext | Lexer, Parser, NodeDumper, ParseException, TomlKeyHelper | Yes (always present) |

No third-party runtime libraries. **Dependency policy: CLEAN.**

### 13.2 Dev — DOCUMENT-VERIFIED

| Dependency | Type | Purpose |
|------------|------|---------|
| `phpunit/phpunit ^11.0 \|\| ^12.0 \|\| ^13.0` | Dev | Unit testing |
| `phpstan/phpstan ^2.1` | Dev | Static analysis |

No leaked dev dependencies into runtime. `composer.json:50-54` `archive.exclude` strips `/tests`, `/phpstan.neon`, `/.gitignore` from dist tarballs — good hygiene.

### 13.3 Composer plugins — DOCUMENT-VERIFIED

`composer.json:58-60`:
```json
"allow-plugins": {
    "pestphp/pest-plugin": true
}
```

This is a residual allow-list entry for Pest (a PHPUnit-compatible DSL), but Pest is **not** declared in `require-dev`. Dead config — no observable impact, but indicates the project was scaffolded from a Pest template and never cleaned up.

---

## 14. Security Posture

### 14.1 Input validation — SOURCE-VERIFIED (good)

The Lexer and Parser are strict about rejecting invalid input:
- Control characters in strings/comments rejected (`Lexer.php:336,450,495,662,786`).
- Bare `\r` rejected (`Lexer.php:287-298,958-966`).
- Unicode scalar validation rejects surrogates and out-of-range code points (`Lexer.php:597`).
- Date/time validity checked via `checkdate()` and hour/min/sec range checks (`Parser.php:640,653`).
- Duplicate keys rejected at multiple layers (see §4.5).
- Type-mismatch rejections throughout `resolveTableContext` and `insertDottedKey`.

### 14.2 ReDoS / regex safety — INFERRED

The Lexer's 12 regexes (`Lexer.php:50-63`) are anchored with `^` and use negative lookahead `(?![A-Za-z0-9_-])` to terminate. They do not contain nested quantifiers or unbounded alternations that would suggest ReDoS vulnerability. However, the regexes are applied in sequence to every identifier-like token (`Lexer.php:884-890`), so worst-case parsing of a long bare-key token runs 12 regex matches.

### 14.3 Resource exhaustion — SOURCE-VERIFIED (no protection)

There is **no input size limit**. A 1GB TOML file will be loaded into `$this->source` (memory pressure), lexed into a token buffer (more memory), and parsed into an AST (yet more memory). The Parser does not enforce any maximum depth, so a malicious deeply-nested inline table `{a={b={c={...}}}}` could cause stack overflow.

### 14.4 `eval` / dynamic code — SOURCE-VERIFIED (none)

Grep for `eval(`, `create_function`, `assert(` with string argument, `preg_replace` with `/e` modifier returned no matches. The codebase does not dynamically evaluate untrusted input.

### 14.5 File I/O — SOURCE-VERIFIED

`Parser::parseFile()` (`Parser.php:98-108`) uses `is_file`, `is_readable`, `file_get_contents` — standard, no path traversal risk (the caller controls the path). `Toml::dumpFile()` (`Toml.php:107-117`) uses `file_put_contents` — standard.

### 14.6 `Throwable` swallowed — SOURCE-VERIFIED (smell)

`Parser.php:670`: `catch (Exception)` (no variable binding) discards the original exception. This is observability-relevant but not security-relevant.

---

## 15. Architectural Ideas Useful for SStack

Per directive SSTACK-CCR-002 §3, this section identifies concepts SStack could **borrow** (without copying code) for the CORE-10 native TOML parser.

### 15.1 Worth borrowing

1. **Byte-cursor UTF-8 length probe** (`Lexer.php:163-185,990-1003`). The `ord()`-based detection of 1/2/3/4-byte UTF-8 sequences is fast and idiomatic. SStack should implement this in its native lexer.

2. **`ParseErrorType` enum + structured `ParseException`** (`Type/ParseErrorType.php`, `Exception/ParseException.php`). A typed error enum with line/column/context/length fields is a good diagnostic contract. SStack should adopt a similar error taxonomy.

3. **`mb_strwidth`-aware pointer in error messages** (`ParseException.php:62-77`). For terminals that display CJK / emoji at double width, computing the `^` pointer width via `mb_strwidth` (not `mb_strlen`) is the correct approach.

4. **`SplObjectStorage` to track dotted-key-created tables** (`Parser.php:53,917-920,937,956`). This is a clever way to distinguish "table created by `a.b = 1`" from "table created by `[a.b]`" without per-node flags. SStack could use this if it implements dotted-key semantics.

5. **TomlKeyHelper bare-key detection** (`TomlKeyHelper.php:27-36`). The `preg_match('/^[A-Za-z0-9_-]+$/', $key)` test for whether a key can be emitted unquoted is simple and correct.

6. **Pluggable `TypeFormatterInterface`** (`Contract/Dumper/TypeFormatterInterface.php`, `Dumper/ValueDumper.php:36-47`). The priority-ordered formatter chain is a clean extension point for custom value types.

7. **`TomlSerializableInterface`** (`Contract/Dumper/TomlSerializableInterface.php`). Marker interface for "this object knows how to render itself as a TOML value" — clean integration point for userland value objects.

### 15.2 Worth NOT borrowing

1. **The "zero-copy" framing.** Do not market SStack's parser as zero-copy unless it actually avoids string copies via slice views. PetalBranch's marketing overreach here is a cautionary tale.

2. **Global mutable `ParseException::$detailedErrorOutput`** (`ParseException.php:23`). SStack should use a per-parse config object, not process-global state.

3. **Discarding typed datetime objects after `formatToml()`** (`Parser.php:663-669`). SStack should retain typed `LocalDateTime` / `OffsetDateTime` / `Date` / `Time` value objects in the AST so that AST→dump round-trip is type-preserving.

4. **Per-character `preg_match` for character class tests** (`Lexer.php:399`). Use a byte lookup table or `ctype_*` instead.

5. **Token stream that buffers all tokens** (`LazyTokenStream.php:30`). If SStack wants low memory, it should consume the Generator directly in the Parser with bounded lookahead.

6. **Rhetorical comparison table that strawmans competitors** (`README-EN.md:204-212`). The "Common Implementations: ❌ O(N) full array split" line is unsubstantiated — SStack's documentation should compare against measured data, not caricature.

### 15.3 SStack native parser sketch (informed by this audit)

For CORE-10, the audit suggests this design:
- **Lexer:** Byte-cursor scanner with `ord()`-based UTF-8 length probe. Tokens store `(int offset, int length)` views into the source buffer, materialising strings only when the Parser requests `getLexeme()`. Token stream consumed directly by Parser (Generator), bounded ring buffer of ~4 tokens for lookahead.
- **Parser:** Recursive descent. AST retains typed value objects (`SStack\Toml\LocalDate`, `LocalTime`, `LocalDateTime`, `OffsetDateTime`) — never plain strings for temporal types.
- **`LocalDateTime`:** Stores `(year, month, day, hour, minute, second, nanosecond)` as integers, with no `DateTimeImmutable` backing. Exposes arithmetic via dedicated methods, not via `DateTimeImmutable::modify`.
- **Errors:** `ParseErrorType` enum + `ParseException` with `line`, `column`, `length`, `previousLine`, `line`, `nextLine`, and `mb_strwidth`-aware pointer.
- **No global state:** Parse config is a value object passed per parse call.
- **Tests:** PHPUnit suite with datetime round-trip coverage, toml-test runner as CI job (Linux binary), micro-benchmark in repo.

---

## 16. Limitations and Concerns

### 16.1 Project maturity — LOW

- 7 commits, 1 contributor, 3-day development window, 8 months inactive.
- 3 semver tags (`v1.0.0`, `v1.1.0`, `v1.2.0`) but no associated GitHub Releases visible (UNVERIFIED — API rate-limited).
- No CI, no changelog, no contribution guide, no security policy.

### 16.2 Documentation accuracy — MIXED

- TOML 1.1.0 feature support: **accurate** (verified in code).
- PHPStan Level 9: **accurate** (verified in `phpstan.neon`).
- License Apache-2.0: **accurate**.
- Zero-copy / O(1) memory: **inaccurate** (refuted by code).
- Lossless round-trip: **partially inaccurate** (datetime round-trip is broken).
- toml-test pass rate: **unverifiable** in this environment.

### 16.3 Datetime round-trip bug — HIGH SEVERITY for SStack reference use

If SStack borrows the AST design, the parse-side discarding of typed datetime objects (`Parser.php:663-669`) is a pattern to **avoid**. The Dumper's reliance on `TomlSerializableInterface` (`ValueDumper.php:61-62`) without a fallback for typed-but-stringified values is the matching half of this bug.

### 16.4 Global mutable state — MEDIUM SEVERITY for async runtimes

`ParseException::$detailedErrorOutput` (`ParseException.php:23`) and the `Toml::enableDetailedErrors()` static method (`Toml.php:129-132`) mutate process-global state. Unsafe under Swoole / RoadRunner / FrankenPHP worker sharing. SStack must avoid this pattern.

### 16.5 No CI — MEDIUM SEVERITY for trust

The PHPStan Level 9 and toml-test compliance claims are **one-shot maintainer assertions**, not enforced invariants. Any future commit could regress either without detection.

### 16.6 Windows-only test runner — LOW SEVERITY but inconvenient

`tests/toml-test-v2.1.0-windows-amd64.exe` cannot be run on Linux / macOS without Wine. This effectively means the maintainer developed on Windows and the broader community cannot trivially re-verify compliance.

### 16.7 License compatibility — OK

Apache-2.0 is compatible with SStack's licensing needs. No copyleft concern.

### 16.8 Stability of API — INFERRED (low commitment)

No `@internal` or `@experimental` markers in the source. No semver compatibility contract documented. The `Contract/` namespace suggests API stability intent, but with only 7 commits and 8 months of inactivity, there is no track record to evaluate.

---

## 17. Evidence Register

Every file inspected with line numbers used as evidence in this report.

### 17.1 Project metadata files

| File | Lines read | Purpose |
|------|-----------|---------|
| `composer.json` | 1-67 | Package metadata, deps, autoload |
| `phpstan.neon` | 1-8 | Static analysis config (level 9) |
| `LICENSE.txt` | 1-20 (of 202) | Apache-2.0 header verification |
| `README-EN.md` | 1-339 | English marketing claims |
| `README.md` | 1-80 (of 330) | Chinese marketing claims (cross-check) |
| `.gitattributes` | 1-4 | Dist archive excludes |
| `.gitignore` | 1-4 | `/vendor/`, `composer.lock`, `.idea/` |
| `tests/testnote.md` | 1-4 | Manual test focus notes |

### 17.2 Source files (full read unless noted)

| File | LOC | Key lines cited |
|------|-----|-----------------|
| `src/Toml.php` | 135 | 44-48 (parse), 90-94 (dump), 129-132 (enableDetailedErrors global state) |
| `src/functions.php` | 37 | 17 (toml_decode), 32 (toml_encode) |
| `src/Parser/Lexer.php` | 1021 | 50-63 (regex patterns), 73-89 (tokenize), 96-148 (scan generator), 163-185 (updateCurrentChar + substr), 224-241 (peekChar), 252-263 (advance), 278-314 (scanNewline), 325-350 (scanComment), 358-400 (isSingleCharToken, isAlphaNumericOrDash with per-char preg_match), 410-525 (scanString, scanLiteralString, scanBasicString with `$lexeme .= $char`), 539-567 (scanEscapeSequence with `\e` and `\xHH`), 579-602 (scanUnicodeEscape), 613-680 (scanMultilineLiteralString), 693-803 (scanMultilineBasicString), 821-832 (isControlChar), 855-914 (scanIdentifierOrNumber with sequential regex), 925-941 (scanHexEscape), 950-974 (consumeStringNewline), 990-1003 (getCharLength UTF-8 probe), 1015-1020 (advanceN) |
| `src/Parser/Parser.php` | 1104 | 43-67 (class + ctor), 82-124 (parse / parseFile / parseToNode), 139-182 (parseStream main loop), 194-228 (parseEntry), 242-309 (parseValue + type mapping), 320-356 (parseArray), 367-422 (parseInlineTable with 1.1.0 newline + dotted-key support), 434-498 (parseTableDefinition), 509-572 (parseKeyPath), 583-592 (parseInteger), 603-614 (parseFloat), 630-672 (parseDatetime — **returns strings, discards typed objects**, lines 663-669), 688-701 (isKeyToken), 711-728 (collectLeadingComments), 737-750 (collectTrailingComment), 766-899 (resolveTableContext — table-array traversal at 791-802), 914-990 (insertDottedKey with SplObjectStorage), 1000-1010 (skipNewlinesAndComments), 1022-1040 (createTomlTime), 1055-1074 (normalizeDatetimeString), 1083-1104 (expectToken) |
| `src/Support/Token.php` | 109 | 17-38 (`final readonly class Token`, `private string $lexeme` at line 30) |
| `src/Support/LazyTokenStream.php` | 193 | 23-53 (class + ctor), 60-81 (current/next), 90-102 (peek), 113-147 (match/expect), 156-159 (isEOF), 172-193 (ensureBufferHas — **buffer grows monotonically**, line 30 `private array $buffer = []`) |
| `src/Support/Position.php` | 30 | 15-28 (class) |
| `src/Support/ErrorContext.php` | 32 | 15-30 (`final readonly class`) |
| `src/Support/ThrowsErrorTrait.php` | 53 | 16-21 (`private string $rawSource`), 36-53 (throwError with `: never` return type) |
| `src/Support/TomlKeyHelper.php` | 62 | 27-36 (format bare-key detection), 50-61 (escapeString) |
| `src/Support/ArrayHelper.php` | 32 | 25-31 (isAssoc) |
| `src/Support/TomlDate.php` | 114 | 20 (readonly final class), 54-61 (ctor zeroes time), 82-85 (formatToml) |
| `src/Support/TomlTime.php` | 88 | 19 (readonly final class), 32-39 (ctor with int components), 50-60 (formatToml) |
| `src/Support/TomlLocalDateTime.php` | 154 | 20 (readonly final class), 27-69 (public int properties incl. `$nanosecond`), 80-95 (ctor — **no TZ enforcement**), 104-107 (getDateTime exposes DateTimeImmutable), 117-125 (formatToml — no TZ suffix) |
| `src/Support/TomlOffsetDateTime.php` | 178 | 22 (readonly final class), 73-78 ($offset), 96-114 (ctor — **no TZ enforcement despite docblock**), 136-150 (formatToml with `Z`/`P`) |
| `src/Model/Node/Node.php` | 118 | 16-32 (abstract base), 40-98 (getters/setters) |
| `src/Model/Node/ValueNode.php` | 122 | 16-51 (class + ctor), 59-73 (getType/getValue), 86-107 (setValue with type inference — **datetime re-assignment footgun**) |
| `src/Model/Node/TableNode.php` | 189 | 20-28 ($entries), 55-60 (getValue), 81-102 (getPath), 112-119 (set with DUPLICATE_KEY guard), 149-153 (isInline=false), 162-165 (getEntries), 173-188 (isImplicit/setImplicit) |
| `src/Model/Node/ArrayNode.php` | 197 | 20-30 ($elements, $declaredAsTableArray), 41-51 (ctor), 59-93 (add/get/set/remove), 117-131 (getElements/isTableArray), 161-180 (getType/getValue), 190-196 (isAllTables) |
| `src/Model/Node/InlineTableNode.php` | 24 | 13 (extends TableNode), 20-23 (isInline=true) |
| `src/Model/KeyPath.php` | 104 | 15-26 (final readonly class), 38-42 (fromString), 53-56 (append), 65-102 (__toString, isBareKey, escapeString) |
| `src/Model/Entry.php` | 30 | (read; not cited in detail) |
| `src/Model/DumperConfig.php` | 69 | 15-67 (mutable config bag: indent, newline, inlineTable, inlineTableMaxDepth, inlineTableMaxItems, alignEquals) |
| `src/Builder/NodeBuilder.php` | 147 | 30 (readonly class), 39-43 (ctor), 55-74 (build), 88-119 (transformToNode), 132-146 (detectTomlType — **only recognises Toml* instances, not raw DateTime**) |
| `src/Dumper/Dumper.php` | 63 | 19-45 (dump = Builder + NodeDumper), 47-62 (dumpFile) |
| `src/Dumper/NodeDumper.php` | 272 | 23-39 (class + ctor), 51-57 (dump), 71-205 (dumpTable with alignment + 4-phase rendering), 216-236 (dumpInlineNode — **calls valueDumper->dump($node->getValue()) without type context**, line 235), 245-271 (renderComments) |
| `src/Dumper/ValueDumper.php` | 128 | 22-47 (class + addFormatter), 59-80 (dump — **TomlSerializableInterface fast path at 61-62, falls through to is_string at 73**), 91-94 (dumpString), 106-127 (dumpFloat with json_encode) |
| `src/Dumper/TypeFormatter.php` | 106 | 20-36 (canHandle), 47-73 (format), 84-90 (escapeString), 101-104 (priority=10) |
| `src/Exception/ParseException.php` | 119 | 20-23 (**public static bool $detailedErrorOutput** global state), 36-86 (ctor with ASCII-art context + mb_strwidth pointer), 91-118 (getters) |
| `src/Exception/DumpException.php` | (read; not cited in detail) | |
| `src/Type/TokenType.php` | 122 | 14-122 (enum with 22 cases) |
| `src/Type/TomlType.php` | 90 | 14-90 (enum with 10 cases) |
| `src/Type/ParseErrorType.php` | 54 | 14-54 (enum with 13 cases — **INVALID_TABLE and INVALID_ARRAY declared but never thrown**) |
| `src/Contract/Lexer/*.php` | (3 files, read) | LexerInterface, TokenInterface, TokenStreamInterface |
| `src/Contract/Parser/*.php` | (4 files, read) | ParserInterface, NodeInterface, TableNodeInterface, ArrayNodeInterface |
| `src/Contract/Dumper/*.php` | (5 files, read) | DumperInterface, NodeDumperInterface, ValueDumperInterface, TypeFormatterInterface, TomlSerializableInterface |
| `src/Contract/Type/TomlTemporalInterface.php` | 17 | 15-17 (empty marker interface) |
| `src/Contract/Exception/*.php` | (3 files, read) | TomlExceptionInterface, ParseExceptionInterface, DumpExceptionInterface |

### 17.3 Test files

| File | LOC | Key lines cited |
|------|-----|-----------------|
| `tests/TomlTest.php` | 208 | 17 (TestCase), 26-33 (setUp), 40-52 (tearDown), 61-86 (testParse — **only outer structure asserted**), 95-111 (testParseFile), 120-128 (testParseFileNotFound), 137-154 (testDump), 162-185 (testDumpWithConfig), 193-207 (testDumpFile) |
| `tests/toml-test-decoder.php` | 139 | 16-50 (main flow), 53-138 (convertNodeToTomlTestFormat — **datetime output uses raw string at line 129**) |
| `tests/toml-test-encoder.php` | 90 | 12-31 (main flow), 36-78 (translateTaggedJson — **wraps datetimes in Toml* instances at lines 53-56**) |
| `tests/round-trip.php` | 47 | 14-24 (sample TOML), 27-47 (parse → modify port/password → dump — **does not exercise datetime**) |
| `tests/toml_edge_cases.php` | 154 | 9-129 (12 invalid TOML cases), 132-153 (run loop) |
| `tests/align-test.php` | (not read in detail) | Equal-sign alignment demo |
| `tests/toml-test-v2.1.0-windows-amd64.exe` | (binary) | Windows x86-64 executable, cannot run on Linux |

### 17.4 Git metadata (via `git log`, `git tag`, `git shortlog`)

- 7 commits, 1 contributor (PetalBranch / Petalbranch — two spellings).
- 3 tags: v1.0.0, v1.1.0, v1.2.0.
- HEAD: `d8553b064a73ed78e835d5372b2cde66b88837a2`.
- No `.github/workflows/` directory.

### 17.5 Grep-based evidence (counts and absences)

- `SplFixedArray | StringRef | StringView | RopeData | mb_str_split | SplStack | SplQueue | Fiber | SplDoublyLinkedList` → **0 matches** in `src/`.
- `&$` (pass-by-reference) → **0 matches** in `src/`.
- `substr(` → **21 matches** across 6 files in `src/`.
- `readonly class | readonly final class | #\[Override] | never | enum` → 11 matches (readonly classes: ErrorContext, Token, TomlDate, TomlTime, TomlOffsetDateTime, TomlLocalDateTime, NodeBuilder, KeyPath; never: ThrowsErrorTrait:33,36; enums: TokenType, ParseErrorType, TomlType). **No `#[Override]` attribute** (the only PHP 8.3-specific feature).
- `1\.1\.0` references → 13 matches (all in `src/Type/TomlType.php` docblocks and `src/Parser/Parser.php`/`src/Parser/Lexer.php` comments).

---

## End of Analysis

**Report prepared:** 2026-11-17
**Authorisation:** READ-ONLY research per SSTACK-CCR-002 §3. No source code modified. No commits pushed. No third-party libraries installed as dependencies.
**Distribution:** SStack CORE-10 research archive. Not for redistribution outside SStack.
