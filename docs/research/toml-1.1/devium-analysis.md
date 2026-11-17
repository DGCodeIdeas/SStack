# Devium TOML — Technical Analysis

**Repository:** https://github.com/vanodevium/toml
**Inspection date:** 2026-11-17
**HEAD commit:** `59c82e8a1cb5294063c65baf86ad694106da9070` (v1.1.1)
**License:** MIT (`LICENSE.md`, lines 1–22, SOURCE-VERIFIED)
**Evidence classification:** predominantly **SOURCE-VERIFIED**. Every architectural claim below is backed by a quoted line range from the local clone at `/home/z/my-project/research/toml-1.1/repos/devium/`. Where a claim could not be confirmed (e.g. GitHub issue counts), it is tagged **UNVERIFIED** with the reason. Per directive SSTACK-CCR-002 §3, "SOURCE-VERIFIED" means I opened the file and read the lines; "DOCUMENT-VERIFIED" means I read project metadata (composer.json, README, CI YAML); "INFERRED" means a reasonable interpretation from observed code; "UNVERIFIED" means I could not confirm.

> Authorisation boundary: READ-ONLY. No Devium file was modified. No code is reproduced beyond ≤10-line analytical snippets. SStack may **borrow ideas**, not code.

---

## 1. Repository Identity and Maturity

| Field | Value | Evidence |
|---|---|---|
| URL | `https://github.com/vanodevium/toml` | `git remote -v` output, DOCUMENT-VERIFIED |
| Default branch | `main` | `git symbolic-ref --short HEAD` → `main`, SOURCE-VERIFIED |
| Latest release | `v1.1.1` (tag) | `git tag` lists `v1.0.1 … v1.1.1`, SOURCE-VERIFIED |
| HEAD commit | `59c82e8 …` | `git log -1`, SOURCE-VERIFIED |
| HEAD date | 2026-11-16 21:47:12 +0200 | `git log --pretty=%ci`, SOURCE-VERIFIED |
| License | MIT | `LICENSE.md` lines 1–22, SOURCE-VERIFIED |
| Author | Vano Devium `<vano@devium.me>` / commit author `<webdevium@gmail.com>` | `composer.json` lines 8–12; `git shortlog -sne`, SOURCE-VERIFIED |
| PHP minimum | `>=8.2` | `composer.json` line 15, SOURCE-VERIFIED |
| CI matrix | PHP 8.2, 8.3, 8.4, 8.5 | `.github/workflows/ci.yaml` line 12, SOURCE-VERIFIED |
| Total commits | 50 (single author) | `git shortlog -sne --all`, SOURCE-VERIFIED |
| First commit | 2024-08-10 | `git log` tail, SOURCE-VERIFIED |

### 1.1 Composer runtime dependencies (SOURCE-VERIFIED, `composer.json` lines 14–19)

```json
"require": {
    "php": ">=8.2",
    "ext-json": "*",
    "php-ds/php-ds": "^1.5",
    "symfony/polyfill-mbstring": "^1.30"
}
```

Both `php-ds/php-ds` and `symfony/polyfill-mbstring` ARE required at runtime. This is a **hard constraint** that directly violates SStack's "no third-party runtime libraries" policy if Devium were used as a dependency (which is explicitly out of scope here — research only). The polyfills are suggested as optional extensions (`ext-ds`, `ext-mbstring`) at lines 35–38; in production they exist to back-fill when the C extensions are absent.

**Runtime dependency surface used by the source:**
- `Ds\Set` — used in `src/TomlKeystore.php` lines 11, 17–24, 28–32 (`new Set`, `->add`, `->contains`, `->reversed`, `->filter`, `->isEmpty`, `->count`, `->last`, array access). The keystore depends on `Ds\Set` for ordered-dedup semantics.
- `mb_chr` — used in `src/TomlTokenizer.php` line 297 (`mb_chr(intval($codePoint, 16))`) for `\u`/`\U` escape decoding.
- `mbstring` is otherwise only needed for `mb_chr`; the rest of the codec is byte-oriented.

### 1.2 Dev dependencies (SOURCE-VERIFIED, `composer.json` lines 20–26)

```
laravel/pint ^1.17.3      — code style
pestphp/pest ^3.8.6       — test framework (Pest v3, on top of PHPUnit)
phpstan/phpstan ^2.2.5    — static analysis
rector/rector ^2.5.3      — refactoring automation
symfony/var-dumper ^6.4|^7.1.4 — dump() in dev
```

**No `phpstan.neon` / `phpstan.dist.neon` exists** (only `.editorconfig`, `phpunit.xml`, `rector.php` at repo root — `ls -a` confirmed). Composer script `test:types` (line 58) is `phpstan analyse --ansi src` with no `--level` flag → defaults to PHPStan **level 0**, the weakest. No baseline file either. **Static analysis rigour is low.**

### 1.3 Test framework (SOURCE-VERIFIED)

- Pest 3.x on top of PHPUnit 10.x — `phpunit.xml` lines 1–18 reference the PHPUnit 10.3 schema.
- `tests/Pest.php` lines 1–44: minimal; defines one custom expectation `toBeOne()` and a placeholder helper `something()`.
- `tests/TestCase.php` lines 1–12: empty abstract subclass of `PHPUnit\Framework\TestCase`.
- Three test files in `tests/Unit/`: `TomlDecodeTest.php` (223 lines, 5 `it(...)` cases), `TomlEncodeTest.php` (186 lines, 4 `it(...)` cases), `TomlErrorTest.php` (67 lines, 2 `it(...)` cases). Total ≈ 476 lines, 11 Pest `it()` blocks.

### 1.4 CI workflows (SOURCE-VERIFIED)

`.github/workflows/ci.yaml` (22 lines): runs `composer install` then `composer test` on push to `main`. Matrix: PHP 8.2 / 8.3 / 8.4 / 8.5. `composer test` (lines 60–65) is the chain `test:refacto` (rector --dry-run) → `test:lint` (pint --test) → `test:types` (phpstan analyse src) → `test:unit` (pest). No coverage upload, no mutation testing, no security scanning, no dependabot config in repo.

`.github/workflows/availability.yaml` (25 lines): scheduled cron `0,30 * * * *` and on-push; verifies that `composer require devium/toml` still resolves from Packagist across the same PHP matrix. Pure availability smoke-test.

### 1.5 Maintenance activity (SOURCE-VERIFIED)

- 2024-08-10 to 2025-01-11: initial v1.0.x development (≈25 commits).
- 2025-04-22, 2025-10-16: v1.0.5 / v1.0.6 maintenance.
- 2026-04-04: three commits bumping to v1.1.0 (README, composer, CI, run-toml-test.sh).
- 2026-11-16 (one day before this inspection): four commits in ~30 minutes:
  - `24c139e` 21:17 — semantic fix in `TomlKeystore.php` (prefix-conflict bug for `[[items]]` vs `[[itemsExtended]]`) plus new test cases.
  - `a55e6ba` 21:38 — rector cosmetic refactor across 41 files (no logic change).
  - `4c44e0c` 21:42 — composer version bump.
  - `59c82e8` 21:47 — README badge bump (v1.1.1 release).

The 2026-11-16 burst released v1.1.0 and v1.1.1 within 30 minutes after a real bug was discovered and patched — indicating **active maintenance** but also **release-before-stabilise cadence** (no human reviewer between fix and tag).

### 1.6 Known open issues (UNVERIFIED)

`curl -sS -H "Accept: application/vnd.github+json" https://api.github.com/repos/vanodevium/toml/issues?state=open` returned:
```
{"message":"API rate limit exceeded for 8.212.10.159. …"}
```
after two attempts from two different egress IPs. The local clone does not include issue data. **Open issue count and content could not be verified at inspection time.** Recommend SStack re-run this query with an authenticated token before relying on it. The README does not list any known issues or limitations beyond the seven toml-test skip rules (see §8).

---

## 2. Source Architecture (with component diagram in text form)

### 2.1 Source tree (SOURCE-VERIFIED, `ls -R src/`)

```
src/
├── Toml.php                     # public façade (26 lines)
├── TomlDecoder.php              # decode orchestrator (54 lines)
├── TomlEncoder.php              # encode orchestrator (235 lines)
├── TomlTokenizer.php            # lexer (373 lines)
├── TomlInputIterator.php        # byte cursor (65 lines)
├── TomlToken.php                # token value object (54 lines)
├── TomlParser.php               # recursive-descent parser (594 lines)
├── TomlKeystore.php             # duplicate-key tracker (258 lines)
├── TomlNormalizer.php           # AST → PHP value transformer (147 lines)
├── TomlObject.php               # ArrayObject wrapper (15 lines)
├── TomlTag.php                  # toml-test type tagging (139 lines)
├── TomlError.php                # exception with code-block caret (67 lines)
├── TomlUtils.php                # char-class predicates (47 lines)
├── TomlInternalDateTime.php     # datetime base class (76 lines)
├── TomlDateTime.php             # offset datetime (extends DateTimeImmutable) (33 lines)
├── TomlLocalDate.php            # YYYY-MM-DD value object (39 lines)
├── TomlLocalDateTime.php        # local datetime value object (59 lines)
├── TomlLocalTime.php            # HH:MM:SS(.fff) value object (58 lines)
├── TomlDateTimeInterface.php    # marker interface (7 lines)
├── helpers.php                  # toml_encode/toml_decode globals (26 lines)
└── Nodes/                       # AST node classes (16 files)
    ├── Node.php                 # marker interface
    ├── ValuableNode.php         # marker interface for value nodes
    ├── NumericNode.php          # marker interface for int/float
    ├── TomlDateTimeNode.php     # marker interface for datetime nodes
    ├── RootTableNode.php
    ├── TableNode.php
    ├── ArrayTableNode.php       # extends TableNode
    ├── KeyNode.php
    ├── KeyValuePairNode.php
    ├── StringNode.php
    ├── BareNode.php
    ├── IntegerNode.php
    ├── FloatNode.php
    ├── BooleanNode.php
    ├── ArrayNode.php
    ├── InlineTableNode.php
    ├── OffsetDateTimeNode.php
    ├── LocalDateTimeNode.php
    ├── LocalDateNode.php
    └── LocalTimeNode.php
```

Total source: 2 698 lines across 41 files (`wc -l src/**/*.php`).

### 2.2 Component diagram (text form)

```
                ┌─────────────────────────────────────────────────┐
   user code ──▶│ Toml::decode / Toml::encode                     │ ← public API
                │ (src/Toml.php)                                   │
                └───┬───────────────────────────────────┬─────────┘
                    ▼                                   ▼
        ┌──────────────────────┐         ┌──────────────────────────┐
        │ TomlDecoder          │         │ TomlEncoder              │
        │  src/TomlDecoder.php │         │  src/TomlEncoder.php     │
        └───┬──────────────────┘         └──────────────────────────┘
            │
            ▼
   ┌────────────────────┐        ┌──────────────────────┐
   │ TomlParser         │───────▶│ TomlKeystore         │ ← duplicate-key tracker
   │  src/TomlParser.php│        │  src/TomlKeystore.php│
   └───┬────────────────┘        └──────────────────────┘
       │ owns
       ▼
   ┌──────────────────────┐
   │ TomlTokenizer        │───────▶ TomlInputIterator (byte cursor)
   │  src/TomlTokenizer   │         src/TomlInputIterator.php
   └───┬──────────────────┘
       │ emits
       ▼
   ┌──────────────────────┐
   │ TomlToken            │ ← immutable value object
   │  src/TomlToken.php   │
   └──────────────────────┘

   Parser produces AST (Nodes\*):
       RootTableNode
        ├── KeyValuePairNode (key: KeyNode, value: ValuableNode)
        ├── TableNode / ArrayTableNode
        │     └── KeyValuePairNode*
        └── ...

   AST → TomlNormalizer::normalize() → TomlObject (ArrayObject subclass)
   TomlObject → toArray()/toObject() → array | stdClass (final user output)
```

### 2.3 Execution path: input string → tokens → parse → PHP value (SOURCE-VERIFIED)

For `toml_decode($input, asArray: false)`:

1. **Entry**: `src/helpers.php` line 23 `toml_decode()` → `src/Toml.php` line 22 `Toml::decode()` → `src/TomlDecoder.php` line 16 `TomlDecoder::decode($input, $asArray, $asFloat)`.
2. **Parser construction**: `TomlDecoder.php` line 18 `new TomlParser($input, $asFloat)`. Inside `TomlParser::__construct()` (`TomlParser.php` lines 42–48): constructs `TomlTokenizer($input)`, a `TomlKeystore`, a `RootTableNode`, and aliases `$this->tableNode = $this->rootTableNode`.
3. **Tokenizer construction**: `TomlTokenizer::__construct()` (`TomlTokenizer.php` lines 38–41) wraps `$this->validateInput($input)` in a `TomlInputIterator`. `validateInput()` (lines 46–53) rejects inputs matching `/("""\n?.*\\\\ )|(\\\\ .*\n?""")/` — i.e. it pre-scans for the forbidden `\` + space pattern outside multiline basic strings.
4. **Main parse loop**: `TomlParser::parse()` (`TomlParser.php` lines 53–82) iterates calling `expression()` (lines 87–97). For each top-level node:
   - Whitespace + comment + newline are consumed (`takeWhitespace`, `takeComment`, `assert(NEWLINE | EOF)`) at lines 62–64.
   - `TomlKeystore::addNode($node)` (line 65) registers the node for duplicate-key tracking.
   - If the node is a `TableNode`/`ArrayTableNode`, it becomes the new "current table" (`$this->tableNode = $node`) and is appended to root (lines 66–68). Otherwise the node (a key/value pair) is appended to the current table (line 70).
5. **Token consumption**: `TomlParser::value()` (lines 191–203) calls `$this->tokenizer->next()` once and dispatches on token type. The lexer is **on-demand**: `TomlTokenizer::next()` (`TomlTokenizer.php` lines 100–122) is invoked by the parser as needed, not in a pre-pass. `peek()` (lines 83–95) saves and restores `iterator->pos` to implement lookahead — i.e. **backtracking is by cursor save/restore**, not by token buffering.
6. **AST → value**: `TomlDecoder::decode()` (line 21 / 24) calls `TomlNormalizer::normalize($parser->parse())`. `TomlNormalizer::normalize()` (`TomlNormalizer.php` lines 30–78) walks the AST recursively:
   - `KeyValuePairNode` → `objectify(keys, value)` builds a nested `TomlObject` chain (lines 133–146).
   - `TableNode` → merges children under the key path.
   - `ArrayTableNode` → wraps merged children in a single-element array `[…]` (line 59), so consecutive `[[T]]` headers merge into the same array via the `merge()` reduce (lines 91–119).
7. **Final conversion**: `TomlDecoder::toObject()` (lines 44–53) walks the merged `TomlObject` tree and emits `stdClass` for objects or `array` for array-tables, preserving `DateTimeInterface` and `TomlInternalDateTime` instances as leaves (lines 29–35).

### 2.4 Encoder path (SOURCE-VERIFIED, `src/TomlEncoder.php`)

`Toml::encode($data)` (line 14) → `TomlEncoder::encode()` (lines 18–29) → `stringifyTable()` (lines 74–103) which separates "preamble" (top-level key/value pairs) from "tables" (sub-objects and array-tables) and writes them in spec-compliant order: top-level scalars first, then `[table]` / `[[array-table]]` blocks. `stringifyValue()` (lines 139–190) dispatches on `extendedTypeOf()` (lines 50–69) which recognises `'date'`, `'array'`, `'object'`, `'integer'`, `'double'`, `'boolean'`, `'string'`. Strings are encoded with `json_encode($s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)` then `\\u007f` is re-substituted for DEL (line 107). Floats are appended `.0` if no fractional dot appears (lines 154–156). `nan`, `inf`, `-inf` are written literally (lines 144–152).

---

## 3. Lexer Analysis

### 3.1 Lexer location (SOURCE-VERIFIED)

- **Class**: `Devium\Toml\TomlTokenizer` (final, `@internal`), `src/TomlTokenizer.php` lines 8–373.
- **Backing cursor**: `Devium\Toml\TomlInputIterator`, `src/TomlInputIterator.php` lines 10–65. The iterator exposes a public mutable `int $pos` (line 14) initialised to `-1` (i.e. before the first byte).

### 3.2 Approach: stateless character-by-character scanner, on-demand (SOURCE-VERIFIED)

There is no regex-driven lexer and no pre-tokenisation pass. `TomlTokenizer::next()` (`TomlTokenizer.php` lines 100–122) fetches one byte from `$this->iterator->next()` and dispatches:

```php
$char = $this->iterator->next();
$start = $this->iterator->pos;
if ($this->isPunctuatorOrNewline($char)) {
    return TomlToken::fromArray([
        'type' => self::PUNCTUATOR_OR_NEWLINE_TOKENS[$char],
        'value' => $char,
    ]);
}
if ($this->isBare($char)) {
    return $this->scanBare($start);
}
return match ($char) {
    ' ', "\t" => $this->scanWhitespace($start),
    '#' => $this->scanComment($start),
    "'" => $this->scanLiteralString(),
    '"' => $this->scanBasicString(),
    TomlInputIterator::EOF => TomlToken::fromArray(['type' => TomlToken::EOF]),
    default => throw new TomlError('unexpected character: '.$char),
};
```
— `src/TomlTokenizer.php` lines 102–122.

The match is on a single byte. `isBare()` (lines 129–136) only accepts `A–Z`, `a–z`, `0–9`, `-`, `_`. **Non-ASCII bare keys (TOML 1.1.0 §6) are NOT recognised by `isBare()`** — they would fall through to the `default` arm and raise `TomlError('unexpected character: …')`. This is consistent with TOML 1.0 rules, not 1.1.

### 3.3 Character consumption and lookahead (SOURCE-VERIFIED)

- **Cursor**: `TomlInputIterator::next()` (`TomlInputIterator.php` lines 44–59) increments `$this->pos` then reads `$this->input[$this->pos]`. PHP strings are byte arrays, so this is **byte-level access**, not codepoint-level. Multi-byte UTF-8 characters are split across multiple `next()` calls.
- **Lookahead / peek**: `TomlInputIterator::peek()` (lines 35–42) saves `$pos`, calls `next()`, restores `$pos`. `TomlTokenizer::peek()` (lines 83–95) does the same at the token level: it calls `$this->next()` and then restores `$this->iterator->pos`. So **peek does full backtracking via cursor save/restore**, which means the cost of `peek()` is one full token scan plus one rewind.
- **CRLF normalisation**: `TomlInputIterator::next()` lines 52–56 — if the byte is `\r` and the next byte is `\n`, it consumes both and returns `"\n"`. Lone `\r` is returned as-is (NOT normalised to `\n`), which is a potential spec deviation — TOML 1.0/1.1 require normalisation of bare `\r` to `\n` at the lexer level (ABNF `non-eol = %x09 / %x20-7E / %x80-10FFFF` excludes `\r`).
- **EOF sentinel**: `TomlInputIterator::EOF = '-1'` (line 12) — a 2-character **string** used as a sentinel, which is an odd choice given that `next()`'s return type is `string`. The string `'-1'` cannot appear in real input unless someone literally types the two characters `-1` adjacent, but the comparison `===` against `self::EOF` makes it safe.

### 3.4 Token boundaries & multi-character scanners (SOURCE-VERIFIED)

| Token type | Scanner | File:lines | Notes |
|---|---|---|---|
| Punctuator / newline | inline table lookup | `TomlTokenizer.php` 10–21, 104–109 | 10 single-byte tokens |
| Bare | `scanBare()` | lines 138–145 | consumes consecutive `isBare()` chars |
| Whitespace | `scanWhitespace()` | lines 165–172 | only `' '` and `"\t"` |
| Comment | `scanComment()` | lines 179–194 | consumes until control char (except tab) or EOF |
| Literal string | `scanLiteralString()` → `scanString("'")` | lines 214–329 | handles `'''` multiline |
| Basic string | `scanBasicString()` → `scanString('"')` | lines 222–329, 348–351 | handles `"""` multiline, escapes, `\u`/`\U` |
| EOF | inline | line 119 | sentinel |

### 3.5 Escape sequence handling (SOURCE-VERIFIED, `TomlTokenizer.php` lines 23–31, 280–314)

The escape map covers exactly the seven TOML 1.0 escapes:
```php
protected const ESCAPES = [
    'b' => "\b", 't' => "\t", 'n' => "\n", 'f' => "\f",
    'r' => "\r", '"' => '"', '\\' => '\\',
];
```
— `TomlTokenizer.php` lines 23–31.

**`\e` (TOML 1.1.0 escape for U+001B ESC) is NOT in the map.** A `\e` inside a basic string hits the `default` arm at line 314 → `throw new TomlError('unexpected character: e')`. → **TOML 1.1.0 `\e` escape unsupported.**

`\u` and `\U` are handled at lines 287–303: 4 hex digits for `\u`, 8 for `\U`; each digit validated by `TomlUtils::isUnicode()` (`TomlUtils.php` lines 22–25 — accepts `0-9 A-F a-f`). The accumulated hex is `mb_chr(intval($codePoint, 16))` (line 297), and the result is rejected if it exceeds `"\u{10ffff}"` (line 342). **Surrogates (`D800–DFFF`) are NOT explicitly rejected** — `mb_chr(0xD800)` would produce a replacement char or invalid sequence depending on mbstring encoding settings. The toml-test skip list includes `invalid/encoding/bad-codepoint`, so the maintainer is aware this case is not handled rigorously.

### 3.6 Line-ending backslash in multiline basic strings (SOURCE-VERIFIED, lines 305–313)

```php
if ($isMultiline && ($this->isWhitespace($char) || $char === "\n")) {
    for (; ;) {
        if (! $this->iterator->take(' ', "\t", "\n")) {
            break;
        }
    }
    continue 3;
}
```
Consumes whitespace+newlines after a backslash in multiline basic strings. This is the TOML 1.0 "line-ending backslash" rule and is correctly implemented.

The pre-flight check in `validateInput()` (lines 46–53) rejects `\` followed by a space that is NOT inside a `"""…"""` block:
```php
if (preg_match('/("""\n?.*\\\\ )|(\\\\ .*\n?""")/', $input)) {
    throw new TomlError('unexpected \\<space> escaping');
}
```
This is a coarse input-wide guard; it cannot distinguish `\" "` inside a literal string from a real `\<space>` in a basic string. The regex is also single-line by default, so it will not see `\<space>` spanning lines outside of multiline strings. Edge cases here are likely.

### 3.7 Whitespace, comments, newlines (SOURCE-VERIFIED)

- Whitespace token = `' '` + `"\t"` only (lines 174–177). TOML spec whitespace is exactly space + tab, so this is correct.
- Comments: `#` until any control char except tab, or EOF (lines 179–209). TOML 1.1 clarifies that comments may contain Unicode and must end at a newline; this implementation accepts Unicode in comments because it scans byte-by-byte and only stops on control chars. The skip list includes `invalid/encoding/bad-utf8-in-comment`, confirming that **invalid UTF-8 inside comments is NOT detected** (the lexer happily accumulates the bad bytes).
- Newlines: the `\n` byte maps to `TomlToken::NEWLINE` via `PUNCTUATOR_OR_NEWLINE_TOKENS["\n"]` (line 11). CRLF is normalised to `\n` by the iterator (lines 52–56). **Lone `\r` is NOT normalised** — it would fall through to `default` in `next()` (line 120) → `TomlError('unexpected character: \r')`. This is a deviation from the TOML ABNF, which treats bare `\r` as a newline.

### 3.8 Unicode handling (SOURCE-VERIFIED)

- Outside strings: raw bytes are accumulated into token values via `returnScan()` (lines 147–153), which uses `TomlUtils::stringSlice()` (a `substr` wrapper, `TomlUtils.php` lines 37–46). Multi-byte characters survive at the byte level.
- Inside literal strings (`'…'`): bytes appended verbatim (line 276). Works for any UTF-8 content.
- Inside basic strings (`"…"`): bytes appended verbatim (line 316) except for escapes. `\u` / `\U` produce UTF-8 via `mb_chr` (line 297).
- The `valid/key/quoted-unicode` toml-test case is **skipped** (`run-toml-test.sh` line 7), suggesting the maintainer has not been able to make round-tripping of Unicode in quoted keys match the toml-test expected output (most likely a `json_encode` flag issue in `toml-test-decode.php`, which omits `JSON_UNESCAPED_UNICODE`).

### 3.9 Invalid character handling (SOURCE-VERIFIED)

- Any byte that is not a punctuator, not `isBare()`, not whitespace/comment/string-quote, and not EOF → `TomlError('unexpected character: '.$char)` at `TomlTokenizer.php` line 120.
- Inside strings: any control character other than tab (`\x00–\x1F` excluding `\t`, plus `\x7F`) → `TomlError('unexpected EOF or control character: '.$char)` at line 272.

### 3.10 Source positions: line / column tracking (SOURCE-VERIFIED)

**The lexer does NOT track line or column natively.** `TomlToken` (lines 40–53) carries only `type`, `value`, `isMultiline` — no position field. `TomlInputIterator` exposes only a raw byte `$pos` (line 14). Position-to-line/column translation happens lazily inside `TomlError::__construct()` (`TomlError.php` lines 28–43): it splits the input string up to `$position` on `/\r\n|\n|\r/` and counts the resulting pieces. The caret column is `strlen()` of the last partial line — i.e. **byte column, not character column** (so for inputs with multi-byte UTF-8, the caret will be visually misaligned in the error message).

---

## 4. Parser Analysis

### 4.1 Parser location (SOURCE-VERIFIED)

**Class**: `Devium\Toml\TomlParser` (final, `@internal`), `src/TomlParser.php` lines 29–594.

### 4.2 Approach: hand-written recursive descent, no Pratt, no state machine (SOURCE-VERIFIED)

The parser is a single class with a `$tableNode` "current table" pointer (line 37) and a `$keystore` (line 33) for duplicate detection. There is no separate Pratt table for expressions — TOML has no operator precedence, so this is appropriate. The grammar entry point is `expression()` (lines 87–97):

```php
protected function expression(): KeyValuePairNode|TableNode|ArrayTableNode|null
{
    $this->takeCommentsAndNewlines();
    $token = $this->tokenizer->peek();
    return match ($token->type) {
        TomlToken::LEFT_SQUARE_BRACKET => $this->table(),
        TomlToken::EOF => null,
        default => $this->keyValuePair(),
    };
}
```
— `TomlParser.php` lines 87–97.

`table()` (lines 123–137) handles both `[a.b.c]` and `[[a.b.c]]` by optionally consuming a second `[`. `key()` (lines 142–173) handles dotted keys: it accepts `BARE` or `STRING` tokens, separated by `PERIOD`, with optional whitespace around each segment. `keyValuePair()` (lines 178–186) is `key() assert(EQUALS) take(WHITESPACE) value()`.

### 4.3 Table construction algorithm (SOURCE-VERIFIED)

- `[header]` → `new TableNode($key, [])` (line 136).
- `[[header]]` → `new ArrayTableNode($key, [])` (line 136). `ArrayTableNode` is an empty subclass of `TableNode` (`src/Nodes/ArrayTableNode.php` lines 10–10).
- The parser stores the produced node in `$this->tableNode` (lines 66–68), so subsequent `keyValuePair()` results attach to the most recently opened table via `$this->tableNode->addElement($node)` (line 70).

### 4.4 Inline tables (SOURCE-VERIFIED, `TomlParser.php` lines 571–593)

```php
protected function inlineTable(): InlineTableNode
{
    $this->tokenizer->take(TomlToken::WHITESPACE);
    $inlineTableNode = new InlineTableNode([]);
    if ($this->tokenizer->take(TomlToken::RIGHT_CURLY_BRACKET)) {
        return $inlineTableNode;
    }
    $keystore = new TomlKeystore;
    for (; ;) {
        $keyValue = $this->keyValuePair();
        $keystore->addNode($keyValue);
        $inlineTableNode->addElement($keyValue);
        $this->tokenizer->take(TomlToken::WHITESPACE);
        if ($this->tokenizer->take(TomlToken::RIGHT_CURLY_BRACKET)) {
            break;
        }
        $this->tokenizer->assert(TomlToken::COMMA);
    }
    return $inlineTableNode;
}
```

Observations:
- An inline table gets **its own keystore instance** (line 580) so duplicate keys are detected within the inline table only, not against the outer document.
- After `}`, the loop breaks. After `,`, it loops back to `keyValuePair()` which immediately calls `key()` → `tokenizer->next()` and expects BARE or STRING. **No `take(WHITESPACE)` or NEWLINE before the next key** — so:
  - `{a = 1, b = 2}` works (single space is consumed by `key()` itself at line 147).
  - `{a = 1,}` → after the comma, `key()` calls `next()`, gets `}`, falls into `default` → throws `TomlError('unexpected token type: RIGHT_CURLY_BRACKET')`. → **Trailing comma in inline tables is NOT supported (TOML 1.1.0 §5.7 feature unsupported).**
  - `{a = 1,\n b = 2}` → after comma, `key()` calls `next()`, gets NEWLINE → `default` → throws. → **Multi-line inline tables (TOML 1.1.0 §5.7 feature) are NOT supported.**

### 4.5 Arrays (SOURCE-VERIFIED, `TomlParser.php` lines 541–566)

```php
protected function array(): ArrayNode
{
    $arrayNode = new ArrayNode([]);
    for (; ;) {
        $this->takeCommentsAndNewlines();
        if ($this->tokenizer->peek()->type === TomlToken::RIGHT_SQUARE_BRACKET) break;
        $value = $this->value();
        $arrayNode->addElement($value);
        $this->takeCommentsAndNewlines();
        if (! $this->tokenizer->take(TomlToken::COMMA)) {
            $this->takeCommentsAndNewlines();
            break;
        }
    }
    $this->tokenizer->assert(TomlToken::RIGHT_SQUARE_BRACKET);
    return $arrayNode;
}
```

After `take(COMMA)`, the loop calls `takeCommentsAndNewlines()` (line 546) before re-testing for `]`. So `[1, 2,]` works (trailing comma supported in arrays — both TOML 1.0 and 1.1 permit this). Multi-line arrays with comments inside are also supported.

### 4.6 Arrays-of-tables and nested structures (SOURCE-VERIFIED)

`[[fruits]]` followed by `[[fruits.varieties]]` is handled by `TomlKeystore::addArrayTableNode()` (`TomlKeystore.php` lines 214–257). The algorithm:

1. Compute the header: `$header = $this->makeKey($arrayTableNode->key)` (line 216), e.g. `"fruits"`.
2. Walk `$this->arrayTables` **reversed** (line 227). For each existing array-table entry:
   - If its header equals `$header`, increment `$index` (lines 231–234) — this counts previous occurrences of the same header.
   - Else if `$header` starts with `$arrayTableHeader.'.'`, the new array-table is a sub-table of the existing one: set `$key = $arrayTable . substr($header, strlen($arrayTableHeader))` (line 238) and break. This is how `fruits.varieties` becomes `fruits.[0].varieties`.
3. Append `".[$index]"` to the key (line 254) and add to both `arrayTables` and `tables` sets (lines 255–256).

So the keystore uses a string-tuple scheme where array-table indices are encoded as `.[0]`, `.[1]`, … embedded in the key path. For the canonical example from the TOML spec:

```
[[fruits]]               → fruits.[0]
name = "apple"            → fruits.[0].name (key, attached to last table)
[fruits.physical]         → fruits.[0].physical (regular sub-table)
[[fruits.varieties]]      → fruits.[0].varieties.[0]
[[fruits.varieties]]      → fruits.[0].varieties.[1]
[[fruits]]                → fruits.[1]
[[fruits.varieties]]      → fruits.[1].varieties.[0]
```

This scheme correctly handles the **"duplicate parent entry" bug** the directive specifically asks about: `[[fruits.varieties]]` after `[[fruits]]` is keyed as `fruits.[0].varieties.[0]` — it does NOT create a second `fruits` entry. The test `can decode TOML` (`tests/Unit/TomlDecodeTest.php` lines 102–124) exercises exactly this canonical pattern.

### 4.7 Duplicate key detection (SOURCE-VERIFIED)

`TomlKeystore` maintains **four `Ds\Set` instances** (lines 17–24):
- `$keys` — fully-qualified key paths for key/value pairs.
- `$tables` — fully-qualified paths for `[header]` tables (and array-table entries with `.[N]` suffix).
- `$arrayTables` — paths with `.[N]` suffix for array-table *headers* (without further sub-table expansion).
- `$implicitTables` — paths created implicitly by dotted keys or by parent segments of a `[a.b.c]` header.

`addKeyValuePairNode()` (lines 57–89) walks each component of the dotted key, builds the prefix path, and rejects duplication against `$keys`, `$tables`, and `$tablesContainsZeroIndex` (which checks for `"$key.[0]"` to prevent adding `fruits` as a key when `fruits` is already an array-table).

`addTableNode()` (lines 129–180) and `addArrayTableNode()` (lines 214–257) similarly reject duplication. `addArrayTableNode()` also rejects the case where a new `[[T]]` would create an array-table at a path that was already used as a regular table (lines 244–248).

### 4.8 Implicit vs explicit table semantics (SOURCE-VERIFIED)

Implicit tables are recorded in `$implicitTables` by `addKeyValuePairNode()` (line 78) for each intermediate component of a dotted key (`a.b.c = 1` implicitly defines `a` and `a.b`). The check at line 83–85 then prevents an *explicit* `[a.b]` from being added later for the same path — but a subsequent *explicit* table `[a.b.c]` is permitted because the explicit table's path is `a.b.c` (which has never been an implicit table; only `a` and `a.b` have).

This matches the TOML spec rule: "you cannot add keys to a table once it has been used as an implicit parent" — but adding a deeper explicit table is allowed.

### 4.9 The `[[fruits.varieties]]` after `[[fruits]]` bug specifically (SOURCE-VERIFIED)

Tracing the canonical example with the keystore code:

| Step | Operation | Keystore state (`arrayTables` ∪ `tables` ∪ `keys`) |
|---|---|---|
| 1 | `[[fruits]]` | `arrayTables={fruits.[0]}`, `tables={fruits.[0]}` |
| 2 | `name="apple"` (inside `[[fruits]]`) | `keys={fruits.[0].name}` |
| 3 | `[fruits.physical]` | `tables={fruits.[0], fruits.[0].physical}` |
| 4 | `[[fruits.varieties]]` | `arrayTables={fruits.[0], fruits.[0].varieties.[0]}`, `tables+=fruits.[0].varieties.[0]` |
| 5 | `[[fruits.varieties]]` | `arrayTables+=fruits.[0].varieties.[1]` (index incremented in step 4 of `addArrayTableNode`'s reversed scan) |
| 6 | `[[fruits]]` (second) | `arrayTables+=fruits.[1]` (index=1 because the reversed scan found `fruits.[0]` and matched `$arrayTableHeader === $header`) |
| 7 | `[[fruits.varieties]]` (banana's) | Reversed scan finds `fruits.[1]` (header `fruits`), so `$key = "fruits.[1].varieties.[0]"` |

→ Correct: banana's `varieties` goes to `fruits.[1].varieties.[0]`, not `fruits.[0].varieties.[2]`. **The bug does not occur.** The test `can decode TOML` at `tests/Unit/TomlDecodeTest.php` line 102 exercises this exact case and asserts equality against the expected JSON.

### 4.10 Recent related fix — the prefix bug (SOURCE-VERIFIED, git history)

Commit `24c139e` (2026-11-16 21:17) fixed a related prefix-collision bug in `TomlKeystore::addArrayTableNode()`:

```diff
-            if (str_starts_with($header, $arrayTableHeader)) {
+            if (str_starts_with($header, $arrayTableHeader.'.')) {
```
and
```diff
-        if ($index === 0 && ! $this->tables->filter(
-            static fn ($table) => str_starts_with((string) $table, $header))->isEmpty()
+        if ($index === 0 && ! $this->tables->filter(
+            static fn ($table) => str_starts_with((string) $table, $header.'.'))->isEmpty()
```

Before the fix, `[[items]]` followed by `[[itemsExtended]]` would incorrectly treat `itemsExtended` as a sub-array-table of `items` (because `str_starts_with("itemsExtended", "items")` is true). The new test `can decode array tables whose names share a prefix` (`tests/Unit/TomlDecodeTest.php` lines 130–162) was added in the same commit to lock in the fix.

**Latent concern (INFERRED):** The same prefix bug **still exists in `addTableNode()`** at `TomlKeystore.php` line 137:
```php
if (str_starts_with($header, $this->makeHeaderFromArrayTable($arrayTableItem))) {
```
There is no trailing `.` here. So `[itemsExtendedMoreSub.metadata]` after `[[itemsExtendedMore]]` would match `itemsExtendedMore` as a prefix and be incorrectly routed as a sub-table of `itemsExtendedMore.[0]`. I could not find a test exercising this case → likely a latent bug. **Recommend SStack's native implementation use a more rigorous path-prefix match (e.g. split-on-`.` and compare element-wise) rather than `str_starts_with`.**

### 4.11 Numbers, dates, times dispatch (SOURCE-VERIFIED, `TomlParser.php` lines 191–223)

`value()` dispatches on the first token type. A `BARE` token (which may contain digits, letters, `-`, `_`) is routed through `booleanOrNumberOrDateOrDateTimeOrTime()` (lines 208–223), which:
1. Returns `BooleanNode` for `true` / `false` (line 210–212).
2. Routes to `dateOrDateTime()` if the value contains `-` after position 0 and is not a scientific-notation float (line 214).
3. Routes to `time()` if the next token is `COLON` (line 218).
4. Otherwise routes to `number()` (line 222).

This dispatch is heuristic — it does not use regex anchoring — and relies on token boundaries established by the lexer. The downside is that ambiguous literals like `12:34:56` (a local time) require two-token lookahead (BARE then COLON), which is done at line 218.

### 4.12 Integer / float internals (SOURCE-VERIFIED, `TomlParser.php` lines 350–526)

`number()` (lines 350–371) dispatches to:
- `integer($value, 16)` for `0x…`
- `integer($value, 8)` for `0o…`
- `integer($value, 2)` for `0b…`
- `float($value)` if the value contains `e` or the next token is `PERIOD`
- `integer($value, 10)` otherwise

`parseInteger()` (lines 388–460) is a byte-by-byte validator that handles signs, leading-zero rules, underscores (must be between digits), and radix-specific digit checks via `digitalChecks()` (lines 465–474). The final integer is `intval(str_replace('0o', '0', $cleaned), 0)` (lines 450–452) — `intval(..., 0)` auto-detects `0x`, `0`, `0b` prefixes, which is why `0o` is rewritten to `0` first (PHP's `intval` does not understand `0o`).

**Floats default to string** (line 505: `new FloatNode($this->asFloat ? (float) $float : $float)`). The `$asFloat` flag from `TomlDecoder::decode()` propagates through `TomlParser::__construct()` (`TomlParser.php` line 42). The README documents this at lines 206–216 — floats are returned as strings by default to preserve precision. **SStack should consider this design** if it needs to round-trip high-precision floats.

`inf` / `nan` / `+inf` / `-inf` / `+nan` / `-nan` are mapped to PHP `INF` / `-INF` / `NAN` (lines 352–356).

---

## 5. AST and Value Representation

### 5.1 AST node hierarchy (SOURCE-VERIFIED, `src/Nodes/`)

```
interface Node                              ← marker
├── interface ValuableNode extends Node     ← values that can appear on RHS of '='
├── interface NumericNode                   ← int/float marker (NOT a Node)
├── interface TomlDateTimeNode              ← datetime marker (NOT a Node)
└── classes:
    RootTableNode        (elements: KeyValuePair|Table|ArrayTable[])
    TableNode            (key: KeyNode, elements: KeyValuePairNode[])
      └── ArrayTableNode (no extra fields; subclass for [[...]] semantics)
    KeyNode              (keys: BareNode[]|StringNode[])
    KeyValuePairNode     (key: KeyNode, value: ValuableNode)
    StringNode           (string value)
    BareNode             (string value) — used for bare keys AND unquoted barewords
    IntegerNode          (int value)
    FloatNode            (float|string value)
    BooleanNode          (bool value)
    ArrayNode            (elements: ValuableNode[])
    InlineTableNode      (elements: KeyValuePairNode[])
    OffsetDateTimeNode   (TomlDateTime value)
    LocalDateTimeNode    (TomlLocalDateTime value)
    LocalDateNode        (TomlLocalDate value)
    LocalTimeNode        (TomlLocalTime value)
```

The AST is a **plain object tree** with no visitor pattern, no source-position annotations, and no parent pointers. `TomlNormalizer` walks it once via `normalize()` and produces `TomlObject` / array / scalar leaves.

### 5.2 String representation (SOURCE-VERIFIED)

- Strings → `StringNode` with a PHP `string` value (raw bytes) — `src/Nodes/StringNode.php` line 12.
- Bare keys/unquoted tokens → `BareNode` with a PHP `string` value — `src/Nodes/BareNode.php` line 12.
- No distinction between literal and basic strings at the AST level — both produce `StringNode`. The lexer's `TomlToken` carries an `isMultiline` flag (`TomlToken.php` line 43) but the AST drops it.

### 5.3 Integers (SOURCE-VERIFIED)

- `IntegerNode` wraps a PHP `int` — `src/Nodes/IntegerNode.php` line 12. On 64-bit PHP, this limits values to `PHP_INT_MAX` (9.2 × 10^18). TOML allows arbitrary-precision integers; the toml-test skip list includes `encoder/float/max-int` (`run-toml-test.sh` line 15), suggesting this limit has caused conformance failures.

### 5.4 Floats (SOURCE-VERIFIED)

- `FloatNode` wraps `float|string` — `src/Nodes/FloatNode.php` line 12. Default mode (no `$asFloat`) returns the original numeric text as a string to preserve precision (see §4.12). With `$asFloat=true`, returns a PHP `float` (IEEE 754 double).

### 5.5 Booleans (SOURCE-VERIFIED)

- `BooleanNode` wraps a PHP `bool` — `src/Nodes/BooleanNode.php` line 12.

### 5.6 Arrays, tables, inline tables (SOURCE-VERIFIED)

- Arrays → `ArrayNode` with a list of `ValuableNode` (`src/Nodes/ArrayNode.php`).
- Tables → `TableNode` with a `KeyNode` and a list of `KeyValuePairNode`.
- Array-tables → `ArrayTableNode extends TableNode` (no extra fields, just type marker).
- Inline tables → `InlineTableNode` with a list of `KeyValuePairNode` (separate from `TableNode`).

### 5.7 Datetime value objects (SOURCE-VERIFIED) — CRITICAL CHECK ON TIMEZONE

| TOML type | Devium class | Base class | Timezone behaviour |
|---|---|---|---|
| Offset date-time | `TomlDateTime` | `DateTimeImmutable` | **Timezone-aware.** Preserves offset. |
| Local date-time | `TomlLocalDateTime` | `TomlInternalDateTime` (abstract, NOT a `DateTime`) | **No timezone** — 7 readonly int properties. |
| Local date | `TomlLocalDate` | `TomlInternalDateTime` | **No timezone** — 3 readonly int properties. |
| Local time | `TomlLocalTime` | `TomlInternalDateTime` | **No timezone, no date** — 4 readonly int properties. |

`TomlDateTime` (`src/TomlDateTime.php` lines 9–33) extends `DateTimeImmutable` and implements `Stringable, TomlDateTimeInterface`. Its constructor (lines 16–27) validates the input against a regex (line 11) and checks February-day validity via `TomlInternalDateTime::isValidFebruary()` (line 22) before delegating to `parent::__construct($dateTimeString)` (line 26). PHP's `DateTimeImmutable` constructor preserves the supplied offset (e.g. `1979-05-27T00:32:00-07:00` becomes a `DateTimeImmutable` with timezone `-07:00`).

**The critical question from the directive — "Does a local datetime become a timezone-aware PHP DateTime?"** → **NO.** `TomlLocalDateTime` does NOT extend `DateTimeImmutable`; it extends `TomlInternalDateTime` (which is `abstract class … implements Stringable, TomlDateTimeInterface` — `src/TomlInternalDateTime.php` lines 12–13). It holds only `int $year, $month, $day, $hour, $minute, $second, $millisecond` (`src/TomlLocalDateTime.php` lines 7–15). So a TOML local datetime stays a **naive** value object with no implicit UTC conversion. This is correct per the TOML spec (local datetimes have no timezone).

The encoder (`TomlEncoder::stringifyValue()` lines 169–179) checks for `DateTimeInterface` and converts via `TomlInternalDateTime::toTOMLString($value)` (lines 14–24) which:
1. Takes microseconds via `$dateTime->format('u')` and truncates to 3 digits (line 16).
2. **Converts to UTC** via `->setTimezone(new DateTimeZone('UTC'))` (line 22) before formatting.
3. Formats as `Y-m-d\TH:i:s.<ms>p` with the `~` placeholder substituted by the millisecond value (lines 18–23).

So a `TomlDateTime("1979-05-27T00:32:00-07:00")` will serialise back to `1979-05-27T07:32:00.000-07:00`? No — `p` returns the offset like `+00:00`, and after `setTimezone(UTC)` it becomes `+00:00`. So it round-trips to UTC. This is **lossy with respect to the original offset** — a `1979-05-27T00:32:00-07:00` decoded then re-encoded becomes `1979-05-27T07:32:00.000+00:00`. The test `can encode DateTimeInterface` (`tests/Unit/TomlEncodeTest.php` lines 120–137) explicitly verifies this: a `-07:00` input is encoded as `Z` (= `+00:00`). This is a **design decision**, not a bug, but it means the encoder is not symmetric for offset datetimes — the original offset is lost on encode.

### 5.8 Millisecond truncation (SOURCE-VERIFIED)

`TomlLocalTime::__construct()` (`src/TomlLocalTime.php` lines 9–16) truncates milliseconds to 3 digits via `substr((string) $millisecond, 0, 3)` — i.e. **microsecond precision is lost**. A TOML local time `00:32:00.999999` becomes `00:32:00.999`. The README example at line 138–146 confirms this behaviour (`local-date-time-2 = 1979-05-27T00:32:00.999999` → `millisecond: 999`). **This is spec-compliant** (TOML allows up to microsecond precision in the ABNF, but truncating to milliseconds is acceptable).

### 5.9 `TomlObject` wrapper (SOURCE-VERIFIED, `src/TomlObject.php` lines 9–15)

```php
class TomlObject extends ArrayObject
{
    public function __construct(object|array $array = [])
    {
        parent::__construct($array, ArrayObject::ARRAY_AS_PROPS);
    }
}
```

A thin `ArrayObject` subclass with `ARRAY_AS_PROPS` so that `$obj->key` and `$obj['key']` are interchangeable. This is what `TomlNormalizer::merge()` reduces into (lines 91–118) and what `TomlDecoder::toObject()` finally converts to `stdClass` or `array`.

---

## 6. Error Handling

### 6.1 Exception hierarchy (SOURCE-VERIFIED)

There is **exactly one exception class**: `Devium\Toml\TomlError` (`src/TomlError.php` lines 7–67), a `final class TomlError extends Exception`. No subclasses, no error code enum, no `TomlSyntaxError` / `TomlSemanticError` split. The class is `final` (line 7) so users cannot extend it.

### 6.2 Constructed error message format (SOURCE-VERIFIED, `TomlError.php` lines 17–36)

```php
public function __construct(
    string $message = '',
    string $toml = '',
    int $position = -1,
) {
    if ($position < 0) {
        parent::__construct($message);
        return;
    }
    [$line, $column] = $this->getLineColFromPosition($toml, $position);
    $codeBlock = $this->makeCodeBlock($toml, $line, $column);
    $this->tomlLine = $line;
    $this->tomlColumn = $column;
    $this->tomlCodeBlock = $codeBlock;
    parent::__construct("Invalid TOML document: $message\n\n$codeBlock");
}
```

Three pieces of metadata are exposed as public properties (`tomlLine`, `tomlColumn`, `tomlCodeBlock` — lines 11, 13, 15). The `Exception::$code` (integer) is never set; only the message is. There is **no `previous` exception chaining** — the constructor signature omits `Throwable $previous = null` entirely, so callers cannot pass one.

### 6.3 Line/column calculation (SOURCE-VERIFIED, `TomlError.php` lines 38–43)

```php
protected function getLineColFromPosition($string, $position): array
{
    $lines = preg_split(self::RE, TomlUtils::stringSlice($string, 0, $position));
    return [count($lines), strlen((string) array_pop($lines))];
}
```

`RE = '/\r\n|\n|\r/'` (line 9). So lines are split on any of CRLF, LF, or CR. Column is `strlen()` of the last partial line — i.e. **byte column**, not visual column. For inputs with multi-byte UTF-8, the caret in `makeCodeBlock()` will be misaligned visually.

### 6.4 Code-block rendering (SOURCE-VERIFIED, `TomlError.php` lines 45–66)

`makeCodeBlock()` prints the line before, the line itself (with a line number prefix), and the line after, with a caret on the offending line. The test `TomlErrorTest.php` lines 7–39 confirms the exact expected output:

```
Invalid TOML document: unexpected non-numeric value

5:  [owner]
6:  name = Tom Preston-Werner
             ^
7:  dob = 1979-05-31T07:32:00-08:00
```

### 6.5 Error categories and detection points (SOURCE-VERIFIED)

| Category | Examples | Detection site (file:line) |
|---|---|---|
| Lexical | Unknown byte, control char in string, bad escape, bad `\u`/`\U` | `TomlTokenizer.php` 120, 272, 293, 299, 314, 48 |
| Syntax / assertion | Expected token mismatch, unexpected token type | `TomlTokenizer.php` 61, 368; `TomlParser.php` 158, 166, 182, 201 |
| Semantic / duplicate keys | `key duplication` | `TomlKeystore.php` 74, 84, 155, 171, 176, 221, 247, 251 |
| Numeric | Leading zero, bad underscore, bad formatting | `TomlParser.php` 393, 400, 407, 411, 415, 424, 440, 447, 487, 491, 509 |
| Datetime | Invalid February day, bad datetime format | `TomlDateTime.php` 19, 23; `TomlLocalDate.php` 19, 25, 29; `TomlLocalTime.php` 24, 34; `TomlLocalDateTime.php` 25 |
| Encoder | Null in array, unknown type | `TomlEncoder.php` 25, 171, 189, 228 |

### 6.6 Expected-token information (SOURCE-VERIFIED, partially)

When the parser hits a wrong token via `TomlTokenizer::expect($type)` (lines 364–372), the error message is `unexpected token type: {$token->type}` — it reports **what was found**, not **what was expected**. Similarly `assert(...)` (lines 58–63) gives the unhelpful literal string `'unexpected assertion'`. This is a clear weakness for debugging.

### 6.7 Error recovery (SOURCE-VERIFIED: NONE)

There is no error recovery. The first `TomlError` thrown anywhere in the parse loop propagates up to `TomlParser::parse()`'s try/catch (lines 55–79), which wraps it in a new `TomlError` enriched with position info, then re-throws. Parsing stops at the first error — there is no "best-effort multi-error" mode.

### 6.8 Position enrichment pattern (SOURCE-VERIFIED, `TomlParser.php` lines 55–79)

```php
try {
    for (; ;) {
        $node = $this->expression();
        if (! $node) break;
        $this->tokenizer->take(TomlToken::WHITESPACE);
        $this->tokenizer->take(TomlToken::COMMENT);
        $this->tokenizer->assert(TomlToken::NEWLINE, TomlToken::EOF);
        $this->keystore->addNode($node);
        if (in_array($node::class, [TableNode::class, ArrayTableNode::class])) {
            $this->tableNode = $node;
            $this->rootTableNode->addElement($node);
        } else {
            $this->tableNode->addElement($node);
        }
    }
} catch (TomlError $error) {
    throw new TomlError(
        $error->getMessage(),
        $this->tokenizer->getInput(),
        $this->tokenizer->getPosition(),
    );
}
```

Note that the re-thrown `TomlError` uses `$this->tokenizer->getPosition()` (which is `iterator->pos` at the moment of the error). This position is the cursor AFTER the failing `next()` call in most cases — so the caret points at or one-past the offending byte. Acceptable for human debugging, but **byte-precise source mapping is not guaranteed**.

---

## 7. TOML 1.1.0 Support (verified, not claimed)

The README (`README.md` lines 12–13) claims compatibility with both v1.0.0 and v1.1.0. The composer.json description (line 4) repeats the claim. **This claim is only partially supported by the source code.** Below is a feature-by-feature audit of TOML 1.1.0-specific changes (per the official diff at toml.io/en/v1.1.0).

| TOML 1.1.0 feature | Source evidence | Status |
|---|---|---|
| Trailing comma in **arrays** (already permitted in 1.0) | `TomlParser::array()` lines 541–566, esp. 556–560 | ✅ Supported |
| Trailing comma in **inline tables** (NEW in 1.1) | `TomlParser::inlineTable()` lines 571–593 — after comma, loops back to `keyValuePair()` with no `}` peek | ❌ **NOT supported** |
| Multi-line inline tables (NEW in 1.1) | `inlineTable()` only `take(WHITESPACE)` once (line 573), never `take(NEWLINE)`; `keyValuePair()` will hit NEWLINE → `unexpected token type: NEWLINE` | ❌ **NOT supported** |
| `\e` escape for U+001B (NEW in 1.1) | `TomlTokenizer::ESCAPES` lines 23–31 — only `b,t,n,f,r,",\\` | ❌ **NOT supported** |
| Non-ASCII bare keys (NEW in 1.1) | `TomlTokenizer::isBare()` lines 129–136 — only `A-Z,a-z,0-9,-,_` | ❌ **NOT supported** |
| Newline in inline table values (NEW in 1.1) | see multi-line inline table row above | ❌ **NOT supported** |
| Hex, octal, binary integers with `_` separators (also 1.0) | `TomlParser::parseInteger()` lines 388–460 | ✅ Supported |
| Underscores in floats (also 1.0) | `TomlParser::float()` lines 479–526 | ✅ Supported |
| RFC 3339 / ISO 8601 datetimes (also 1.0) | `TomlDateTime::REGEX` line 11 | ✅ Supported |
| Local date/time/datetime (also 1.0) | `TomlLocalDate`, `TomlLocalTime`, `TomlLocalDateTime` | ✅ Supported |
| Time-precision microseconds (also 1.0) | `TomlLocalTime` truncates to ms (line 15) | ⚠️ Truncated to ms (deviation from full µs, but spec-permitted) |
| Control chars in comments (1.1 clarifies) | `TomlTokenizer::scanComment()` lines 179–194 — stops on control char except tab | ✅ Matches 1.1 |
| `\r` normalisation (1.0+1.1 require `\r` → `\n`) | `TomlInputIterator::next()` lines 52–56 — only CRLF collapsed; bare `\r` falls through to default → error | ❌ **Deviation** |

### 7.1 Smoking gun: `run-toml-test.sh` tests against `toml-test -toml=1.0` (SOURCE-VERIFIED)

`run-toml-test.sh` line 20:
```bash
toml-test test -toml=1.0 "${skip_decode[@]}" "${skip_encode[@]}" -decoder='php ./toml-test-decode.php' -encoder='php ./toml-test-encode.php' || e=1
```

The official conformance suite is invoked with **`-toml=1.0`**, not `-toml=1.1`. The v1.1.0 commit `9456675` (2026-04-04) is the one that introduced both the 1.1.0 README claim and this `-toml=1.0` invocation (verified by `git show 9456675 -- run-toml-test.sh`). **The library claims 1.1 compatibility but is conformance-tested only against 1.0.** This is the central finding for §7.

### 7.2 Skipped toml-test cases (SOURCE-VERIFIED, `run-toml-test.sh` lines 6–17)

```
skip_decode:
  valid/key/quoted-unicode         → Unicode in quoted keys fails
  invalid/encoding/bad-utf8-in-comment → bad UTF-8 in comments undetected
  invalid/encoding/bad-codepoint   → bad \u/\U codepoints insufficiently rejected

skip_encode:
  encoder/key/quoted-unicode       → encoder cannot round-trip Unicode keys
  encoder/spec-1.0.0/float-1       → float encoding edge case (1.0 conformance)
  encoder/float/max-int            → integer precision limit (PHP_INT_MAX)
  encoder/float/long               → long float serialisation
```

Seven skips total. None of the new TOML 1.1 features (trailing commas in inline tables, multi-line inline tables, `\e`, non-ASCII bare keys) appear in the skip list because **the toml-test 1.0 suite does not test them**. So we have no evidence either way from the conformance suite about whether Devium handles them; the source inspection above is the only evidence, and it is negative.

### 7.3 Conclusion for §7

**The Devium library is a TOML 1.0 implementation with a 1.1.0 marketing label.** The 2026-04-04 v1.1.0 release consists of a README edit and a `composer.json` version bump (commit `9456675`); no TOML-1.1-specific lexing or parsing code was added. The 2026-11-16 burst (commits `24c139e`, `a55e6ba`, `4c44e0c`, `59c82e8`) added one bug fix and rector cosmetic refactors. SStack should treat this library as a **1.0 reference**, not a 1.1 reference.

---

## 8. Test Coverage

### 8.1 Test inventory (SOURCE-VERIFIED)

| File | Lines | `it()` cases | Subject |
|---|---|---|---|
| `tests/Unit/TomlDecodeTest.php` | 223 | 5 | decode of canonical example, prefix-collision array-tables, datetime formats, floats, π as float |
| `tests/Unit/TomlEncodeTest.php` | 186 | 4 | encode of canonical example, `DateTimeInterface` encoding, null skipping, null-in-array rejection |
| `tests/Unit/TomlErrorTest.php` | 67 | 2 | error message with caret for bad value, error message for array-order violation |
| `tests/Pest.php` | 44 | — | Pest bootstrap, custom `toBeOne()` expectation |
| `tests/TestCase.php` | 13 | — | empty abstract subclass of `PHPUnit\Framework\TestCase` |

**Total: 11 `it()` test cases.** This is a very thin unit-test suite.

### 8.2 What is tested (SOURCE-VERIFIED)

- The canonical TOML example from `toml.io` (decode + encode round-trip).
- Nested `[[fruits]]` → `[fruits.physical]` → `[[fruits.varieties]]` × 2 → `[[fruits]]` × 2.
- Prefix-colliding array-table names (`items` / `itemsExtended` / `itemsExtendedMore`).
- Round-trip of `DateTimeImmutable` and `DateTime` through encode.
- Null-value skipping in tables.
- Two error-message snapshots.

### 8.3 What is NOT tested (INFERRED from absence)

- No unit tests for: integer radix formats (`0x…`, `0o…`, `0b…`), integer underscores, leading-zero rejection, sign handling on integers.
- No unit tests for: float scientific notation (`1e10`, `1.5e-3`), float underscores, `inf`/`nan` literal parsing.
- No unit tests for: escape sequences in basic strings (`\b`, `\t`, `\n`, `\f`, `\r`, `\"`, `\\`, `\uXXXX`, `\UXXXXXXXX`).
- No unit tests for: multiline literal `'''…'''` and basic `"""…"""` strings, including line-ending backslash.
- No unit tests for: local date, local time, local datetime formats individually.
- No unit tests for: comment handling, whitespace edge cases, CRLF normalisation, lone `\r`.
- No unit tests for: error positions for lexer errors (only parser/keystore errors are snapshotted).
- No unit tests for: encoder for arrays of scalars, nested inline tables, array-of-inline-tables.
- No unit tests for: `TomlTag` (the test-suite tagging helper used by `toml-test-decode.php`/`toml-test-encode.php`).

The official `toml-test` conformance suite (via `run-toml-test.sh`) is the de-facto safety net for the cases the unit tests miss, but as noted in §7 it runs against TOML 1.0 only and skips 7 cases.

### 8.4 `run-toml-test.sh` deep read (SOURCE-VERIFIED, lines 1–22)

The script:
1. Requires `toml-test` v2 binary from `github.com/toml-lang/toml-test/v2/cmd/toml-test@latest` (line 4 comment).
2. Defines seven skip rules (lines 6–17, transcribed in §7.2).
3. Invokes a **single combined run**: `toml-test test -toml=1.0 … -decoder=… -encoder=…`. Both decoder and encoder are exercised in one pass.
4. The decoder/encoder are PHP scripts (`toml-test-decode.php`, `toml-test-encode.php`) that read TOML from `php://stdin`, decode/encode, and emit tagged JSON. The tagging logic is in `src/TomlTag.php`:
   - `tagObject()` (lines 15–78) wraps PHP values in `{type: "integer|float|string|bool|datetime|date-local|time-local|datetime-local", value: …}` objects that `toml-test` understands.
   - `untagObject()` (lines 84–138) reverses the tagging for the encode side.

### 8.5 Manual execution of the suite (UNVERIFIED)

I did not run the tests because:
- No `php` binary on the sandbox (`php --version` → command not found).
- No `vendor/` directory present (`composer install` was not run as part of the clone).
- No Go toolchain to build the `toml-test` binary.

The CI workflow (`.github/workflows/ci.yaml`) does `composer install` then `composer test` on Ubuntu with `shivammathur/setup-php@2` for PHP 8.2–8.5. I will assume the suite passes on CI for the supported matrix; this is consistent with the README badge claim. **Status: UNVERIFIED locally; DOCUMENT-VERIFIED via CI badge.**

---

## 9. Performance Characteristics

### 9.1 Benchmark files (SOURCE-VERIFIED: none)

No `benchmarks/`, `bench/`, or `*.bench.php` files exist in the repository. `composer.json` has no `bench` script. There are no `phpbench` dependencies. **No performance data is published.**

### 9.2 Algorithmic complexity observations (INFERRED from source)

- **Lexer peek cost**: `TomlTokenizer::peek()` (lines 83–95) does a full `next()` scan plus a cursor rewind. The parser calls `peek()` at decision points (e.g. `TomlParser.php` lines 90, 218, 230, 232, 236, 272, 274, 283, 368, 548). Each `peek()` effectively scans one token twice. For a document of *n* tokens, this roughly doubles lexer work.
- **Comment scanning**: `scanComment()` (lines 179–194) walks byte-by-byte until control char or EOF. For a file with a large trailing comment, this is O(comment-length) per scan — fine.
- **String scanning**: `scanString()` (lines 222–329) walks byte-by-byte. `"""…"""` multiline strings cost O(length).
- **Duplicate-key set**: `TomlKeystore` uses `Ds\Set`, which is backed by a hash table — `contains()` and `add()` are O(1) average. The reversed scan in `addArrayTableNode()` (lines 227–242) is O(number of previous array-table headers) per `[[...]]`. For a document with many array-tables, this is O(A²) in the worst case (where A = number of array-table headers). For typical configs this is negligible.
- **Normalizer merge**: `TomlNormalizer::merge()` (lines 91–119) is `array_reduce` over the children of each table. For nested tables this is O(total key/value pairs × depth) — acceptable.
- **Object wrapping**: `TomlObject extends ArrayObject`. Each merge creates new `TomlObject` instances, which involves PHP object allocation overhead. For very large documents, this may dominate.

### 9.3 Memory observations (INFERRED)

- The full input string is held in `$this->iterator->input` for the lifetime of the parse (necessary because `returnScan()` slices it on each token emission).
- All tokens are emitted as immutable `TomlToken` objects (`TomlToken::fromArray()` line 46–53), each a small allocation. For a 10 000-token document, that's 10 000 object allocations during parse.
- The AST is built as a tree of `*Node` objects, then discarded after `TomlNormalizer::normalize()` runs. Peak memory is AST + normalised `TomlObject` tree side-by-side.

No memory benchmarks to validate. **SStack should benchmark if adopting any of these patterns.**

---

## 10. Dependency Audit

### 10.1 Runtime dependencies (SOURCE-VERIFIED, `composer.json` lines 14–19)

| Dependency | Version | Used by | Replaceable? |
|---|---|---|---|
| `php` | `>=8.2` | whole library | — |
| `ext-json` | `*` | `TomlEncoder::formatString()` (uses `json_encode`), `TomlTag` (JSON for toml-test) | Bundled with PHP since 8.0 |
| `php-ds/php-ds` | `^1.5` | `TomlKeystore` (4× `Ds\Set`) | Could be replaced with `array_keys(array_flip(...))` patterns or a thin `Set` class. Optional `ext-ds` provides the C implementation. |
| `symfony/polyfill-mbstring` | `^1.30` | `TomlTokenizer::scanString()` line 297 (`mb_chr`) | Could be replaced with a hand-written `utf8_encode(intval($codePoint, 16))` for BMP, or with `iconv("UTF-32BE", "UTF-8", pack("N", $codePoint))` for full range. Optional `ext-mbstring` provides the C implementation. |

### 10.2 Dev dependencies (SOURCE-VERIFIED, `composer.json` lines 20–26)

| Dependency | Version | Purpose |
|---|---|---|
| `laravel/pint` | `^1.17.3` | Code-style enforcement (Pint wraps PHP-CS-Fixer). |
| `pestphp/pest` | `^3.8.6` | Test framework. Pest 3 requires PHP 8.2+. |
| `phpstan/phpstan` | `^2.2.5` | Static analysis. **Run at default level 0** (no `phpstan.neon`, no `--level` in composer script `test:types`). |
| `rector/rector` | `^2.5.3` | Automated refactoring (`rector.php` lines 1–20 configures `codeQuality` + `php81` sets — note `php81` set applied to a `>=8.2` codebase). |
| `symfony/var-dumper` | `^6.4|^7.1.4` | Provides `dump()` for ad-hoc debugging. |

### 10.3 SStack dependency-policy implications

For SStack's "NO third-party implementation libraries at runtime" rule:
- `php-ds/php-ds` and `symfony/polyfill-mbstring` are both **polyfills** — they exist to provide functionality that the PHP runtime may or may not have via extensions. They are technically third-party code (MIT licensed, distributed via Composer). Under a strict reading of SStack's policy, they are forbidden.
- The Devium source itself could be reduced to **zero runtime dependencies** by:
  1. Replacing the four `Ds\Set` instances in `TomlKeystore` with a `SplObjectStorage`/`array_flip`/native `Set` (PHP 8.x does not have a native `Set`, so this is the most invasive change).
  2. Replacing the single `mb_chr` call with `mb_chr` equivalent using `iconv` (also an extension) or pure PHP UTF-8 encoding.

SStack's native implementation should avoid both dependencies from the start.

---

## 11. Security Posture

### 11.1 Input validation (SOURCE-VERIFIED)

- **Pre-flight regex**: `TomlTokenizer::validateInput()` (lines 46–53) scans the input for `\` followed by space in a `"""`-anchored context. The regex is single-line (`s` flag not set), so multi-line `\<space>` patterns outside the multiline-string context are not detected. This is a **defensive guard**, not a security control.
- **No size limits**: `TomlInputIterator` accepts arbitrarily large strings. No length cap, no depth cap for nested arrays/inline tables. A deeply nested inline table `{a={b={c=…}}}` could trigger PHP's default stack limit (`xdebug.max_nesting_level` or `memory_limit`), but the library does not enforce its own caps.
- **No resource limits**: comments are scanned byte-by-byte to EOF (`scanComment()` lines 179–194) without bounds checking — a 100 MB comment would be loaded fully.
- **No `unserialize`**: the library does not call `unserialize()`, `eval()`, or `include()` on user input. Safe from object-injection attacks.
- **Regex DoS**: several regexes are anchored and bounded (`/^\d{4}-\d{2}-\d{2}$/`, `/^[a-z0-9-_]+$/i`, etc.). The `TomlDateTime::REGEX` (line 11) is more complex but uses fixed-width quantifiers. No obvious catastrophic backtracking patterns. The `validateInput` regex (line 48) uses `.*` but is anchored and used once at construction. Low ReDoS risk.

### 11.2 Exception side effects (SOURCE-VERIFIED)

`TomlError::__construct()` (lines 17–36) embeds a snippet of the input string in the error message (lines 28–35). If the input contains secrets (e.g. API keys in a TOML config), the error message would leak them into logs. **SStack should NOT include input snippets in production error messages** — gate this behind a debug flag.

### 11.3 String handling and Unicode (SOURCE-VERIFIED)

- Byte-level iteration means invalid UTF-8 bytes are processed as bytes, not as malformed codepoints. The skip list (`bad-utf8-in-comment`, `bad-codepoint`) indicates the library does not actively reject invalid UTF-8 in all positions.
- `\u` / `\U` escapes use `mb_chr(intval($codePoint, 16))`. **Surrogate codepoints (`D800–DFFF`) are not explicitly rejected** — `mb_chr` may emit a replacement char or invalid sequence depending on mbstring's internal encoding.

### 11.4 No CVE history (UNVERIFIED)

I could not query the GitHub Security Advisory API due to rate limiting (see §1.6). No security-related commits are visible in `git log --oneline` (all 50 commits are version-tagged feature releases). **SStack should run `composer audit` against the dep set before any experimental install.**

---

## 12. Architectural Ideas Useful for SStack

These are **ideas** for SStack's native TOML parser, derived from observing what works and what doesn't in Devium. **No code is copied.** SStack's native implementation will be written from scratch.

### 12.1 Ideas worth adopting

1. **Three-stage pipeline: Lexer → Parser → Normalizer.** Separating the AST-construction phase from the value-materialisation phase (Devium's `TomlNormalizer`) makes the code easier to test and lets the same AST feed different output modes (array vs object, typed vs untyped). SStack could mirror this layering.

2. **On-demand lexer.** Devium's lexer is invoked by the parser as needed (`peek()` / `next()`), not pre-tokenised. This avoids materialising a full token array for large inputs. SStack could go further and avoid the `peek()` backtrack-by-cursor cost by introducing a 1-token lookahead buffer.

3. **AST as plain immutable value objects.** Each `*Node` has `readonly` properties and no behaviour beyond `addElement`/`keys()` accessors. This is testable, serialisable, and trivially walkable. SStack's AST could follow the same pattern with stronger typing (use PHP 8.2 `readonly` classes).

4. **Marker interfaces for value categories.** `ValuableNode`, `NumericNode`, `TomlDateTimeNode` let `TomlNormalizer` dispatch on `instanceof` or `$node::class`. SStack could use PHP 8.1+ enums for token types and node kinds, getting exhaustiveness checking in `match` expressions.

5. **Distinct value objects for local date / time / datetime.** Not collapsing local datetimes into `DateTimeImmutable` is correct per the TOML spec and avoids the implicit-UTC pitfall. SStack should replicate this separation.

6. **Path-based keystore for duplicate detection.** Encoding array-table indices as `.[N]` segments in a string key is a simple way to track duplicates without building a full nested table tree during parse. SStack could improve on this by using a typed `Path` value object (list of segments, each annotated with "regular" vs "array-table-index") rather than a string — this would eliminate the `str_starts_with` prefix bugs Devium has.

7. **Pre-flight input validation regex.** Devium's `validateInput()` rejects `\<space>` outside multiline strings up-front. SStack could do a similar cheap scan for byte patterns that are unambiguously illegal (e.g. NUL bytes outside strings) before the main parse loop.

8. **Error message with caret.** Showing a 3-line window with a caret under the offending column is excellent UX. SStack should adopt this and improve it to (a) use character columns not byte columns, (b) report expected-token information, (c) chain `previous` exceptions.

9. **Test-suite tagging helper.** `TomlTag::tagObject()` / `untagObject()` is the bridge between PHP values and `toml-test`'s JSON-tagged protocol. SStack should write its own equivalent to run the official conformance suite against its native parser.

10. **Float-as-string default.** Returning floats as strings by default preserves precision across round-trips. SStack could adopt the same `$asFloat` flag rather than silently coercing to IEEE 754 double.

### 12.2 Anti-patterns to avoid

1. **Byte-level cursor with PHP string indexing.** `TomlInputIterator::next()` reads `$this->input[$this->pos]`, which splits multi-byte UTF-8 characters. SStack should use `mb_substr` / a codepoint-aware iterator, OR explicitly decide to be byte-level and validate UTF-8 up-front (reject invalid bytes).

2. **`str_starts_with` for path prefix matching in the keystore.** This is the root cause of the `items` vs `itemsExtended` prefix bug Devium fixed on 2026-11-16, and the same bug still exists in `addTableNode()` for the `[itemsExtendedMoreSub.metadata]` after `[[itemsExtendedMore]]` case (see §4.10). SStack should split keys into segments and compare segment-by-segment, never as raw string prefixes.

3. **`final` exception class with no subclasses and no `previous` chaining.** Devium's `TomlError` cannot be subclassed and loses causation context. SStack should define a `TomlException` interface with concrete `TomlLexicalError`, `TomlSyntaxError`, `TomlSemanticError` subclasses, all chainable via `previous`.

4. **Assert messages without expected-token info.** `'unexpected assertion'` and `'unexpected token type: …'` are unhelpful. SStack should produce messages like `expected BARE or STRING, got NEWLINE at line 6 column 12`.

5. **Position info computed from byte offset with `strlen` for column.** Misaligns the caret for multi-byte inputs. SStack should track line and column natively in the iterator.

6. **Lone `\r` not normalised.** Devium's iterator only collapses CRLF, leaving bare `\r` to hit the lexer's `default` arm. SStack should normalise all newlines per the TOML ABNF.

7. **Static analysis at level 0.** SStack should configure PHPStan at level ≥6 (ideally `max`) with a baseline file, and consider adding Rector-verified refactors only after static analysis passes.

8. **`@internal` on every class.** Devium marks everything `@internal` (lexer, parser, AST, normalizer, even the public-facing `TomlDecoder` and `TomlEncoder`). This is fine for a library that exposes only `Toml` and the helper functions, but it means consumers cannot extend or override anything. SStack's native parser is itself internal, so this is moot, but it's worth noting that the only true public API is two static methods and two functions.

9. **Mixing `BareNode` for both keys and unquoted bareword tokens.** `BareNode` is used for bare keys in `KeyNode` (parser line 152) and is also a `Node` interface implementation. This conflates two concepts. SStack should have separate `BareKey` and `BarewordToken` types.

10. **Cosmetic refactors shipped as version bumps.** The 2026-11-16 v1.1.0 → v1.1.1 burst mixed a real bug fix with rector refactors and tagged two releases in 30 minutes. SStack's release process should separate semantic fixes from cosmetic changes.

---

## 13. Limitations and Concerns

### 13.1 Conformance limitations (SOURCE-VERIFIED via §7)

- **TOML 1.1.0 claim is unsubstantiated.** The library implements 1.0 and is conformance-tested only against 1.0.
- 7 toml-test cases are skipped; of those, 3 are decoder-side (`quoted-unicode`, `bad-utf8-in-comment`, `bad-codepoint`) and 4 are encoder-side.
- `\e` escape, non-ASCII bare keys, multi-line inline tables, trailing comma in inline tables — all 1.1 features — are unsupported.

### 13.2 Correctness concerns (partly SOURCE-VERIFIED, partly INFERRED)

- **Latent prefix bug in `addTableNode()`** (`TomlKeystore.php` line 137) — same pattern that was fixed in `addArrayTableNode()` on 2026-11-16 but not in `addTableNode()`. A regular `[itemsExtendedMoreSub.metadata]` table after an `[[itemsExtendedMore]]` array-table would be mis-routed. No test exercises this. **CONFIDENCE: HIGH (code-reading), UNVERIFIED (no reproduction).**
- **Lone `\r` not normalised** — `TomlInputIterator::next()` lines 52–56 only collapse CRLF. A bare-CR file would raise `unexpected character: \r`.
- **Surrogate codepoints not rejected** — `\uD800` would be passed to `mb_chr`, behaviour depends on mbstring config.
- **Microsecond precision truncated to milliseconds** for local times and local datetimes — spec-permitted but lossy.
- **Encoder loses original timezone offset** for offset datetimes — converts everything to UTC on encode (see §5.7).

### 13.3 Robustness concerns (SOURCE-VERIFIED)

- No depth limit on nested arrays/inline tables.
- No size limit on input.
- No timeout mechanism.
- Exception messages include input snippets (potential secret leakage in logs).

### 13.4 Maturity concerns (SOURCE-VERIFIED)

- 50 commits, single author, 2-year history.
- 11 unit-test cases — very thin.
- Static analysis at level 0.
- Two releases in 30 minutes on 2026-11-16 indicate a reactive (not stabilised) release process.
- No CHANGELOG, no security policy file, no contributing guide beyond a one-line README note.

### 13.5 Maintenance concerns (SOURCE-VERIFIED via git log + DOCUMENT-VERIFIED via README)

- Author is responsive (the 2026-11-16 burst shows same-day bug fix + release).
- Author maintains the `toml-test` integration, which is the strongest signal of spec-correctness intent.
- The library has been downloaded enough to merit a Packagist badge (README line 9), suggesting real-world usage.
- No funded maintenance model beyond `github: vanodevium` sponsorship (`.github/FUNDING.yml`).

---

## 14. Evidence Register (every file inspected with line numbers)

### 14.1 Project metadata and configuration

| File | Lines read | Notes |
|---|---|---|
| `composer.json` | 1–67 | Name, version, description, deps, autoload, scripts. |
| `LICENSE.md` | 1–22 | MIT, copyright Vano Devium. |
| `README.md` | 1–253 | Features, usage, datetime classes, float-as-string, null handling, toml-test skips. |
| `phpunit.xml` | 1–18 | PHPUnit 10.3 schema, testsuite `./tests`, source `./src`. |
| `rector.php` | 1–20 | `codeQuality` + `php81` sets on `src/` and `tests/`. |
| `.editorconfig` | 1–12 | 4-space indent, LF, UTF-8. |
| `.gitignore` | 1–3 | Ignores `.idea`, `vendor`, `composer.lock`. |
| `.github/FUNDING.yml` | 1–3 | GitHub Sponsors: `vanodevium`. |
| `.github/CODE_OF_CONDUCT.md` | 1–20 | Contributor Covenant. |
| `.github/workflows/ci.yaml` | 1–22 | PHP matrix 8.2/8.3/8.4/8.5, `composer install` + `composer test`. |
| `.github/workflows/availability.yaml` | 1–25 | Cron `0,30 * * * *`, `composer require devium/toml`. |
| `run-toml-test.sh` | 1–22 | `toml-test -toml=1.0` with 7 skip rules. |
| `toml-test-decode.php` | 1–15 | Reads stdin, `toml_decode`, `TomlTag::tagObject`, `json_encode`. |
| `toml-test-encode.php` | 1–15 | Reads stdin JSON, `TomlTag::untagObject`, `toml_encode`. |

### 14.2 Source files (src/)

| File | Lines read | Key contents |
|---|---|---|
| `src/Toml.php` | 1–26 | Public façade: `Toml::encode`, `Toml::decode`. |
| `src/helpers.php` | 1–26 | `toml_encode`, `toml_decode` globals. |
| `src/TomlDecoder.php` | 1–54 | Decode orchestrator; `toArray`, `toObject` conversion. |
| `src/TomlEncoder.php` | 1–235 | Encode logic; `stringifyTable`, `stringifyValue`, `formatString`, `stringifyInlineTable`, `stringifyArray`, `isArrayOfTables`. |
| `src/TomlTokenizer.php` | 1–373 | Lexer; `validateInput`, `assert`, `take`, `peek`, `next`, `scanBare`, `scanWhitespace`, `scanComment`, `scanString`, `scanLiteralString`, `scanBasicString`, `sequence`, `expect`. |
| `src/TomlInputIterator.php` | 1–65 | Byte cursor; `take`, `peek`, `next`, `isEOF`. EOF sentinel `'-1'`. |
| `src/TomlToken.php` | 1–54 | Token VO; 13 type constants; `fromArray`. |
| `src/TomlParser.php` | 1–594 | Recursive-descent parser; `expression`, `table`, `key`, `keyValuePair`, `value`, `booleanOrNumberOrDateOrDateTimeOrTime`, `dateOrDateTime`, `time`, `number`, `integer`, `parseInteger`, `digitalChecks`, `float`, `plus`, `array`, `inlineTable`, `parseDate`. |
| `src/TomlKeystore.php` | 1–258 | Duplicate-key tracker; `addNode`, `addKeyValuePairNode`, `addTableNode`, `addArrayTableNode`, four `Ds\Set`s. |
| `src/TomlNormalizer.php` | 1–147 | AST → PHP value transformer; `normalize`, `mapNormalize`, `merge`, `objectify`. |
| `src/TomlObject.php` | 1–15 | `ArrayObject` subclass with `ARRAY_AS_PROPS`. |
| `src/TomlTag.php` | 1–139 | toml-test type tagging; `tagObject`, `untagObject`. |
| `src/TomlError.php` | 1–67 | Single exception class; `getLineColFromPosition`, `makeCodeBlock`. |
| `src/TomlUtils.php` | 1–47 | Char-class predicates; `stringSlice`. |
| `src/TomlInternalDateTime.php` | 1–76 | Abstract datetime base; `toTOMLString`, `isValidFebruary`, `isLeap`, range validators. |
| `src/TomlDateTime.php` | 1–33 | Offset datetime; extends `DateTimeImmutable`. REGEX line 11. |
| `src/TomlLocalDate.php` | 1–39 | Local date VO; `fromString`. |
| `src/TomlLocalDateTime.php` | 1–59 | Local datetime VO; `fromString`. |
| `src/TomlLocalTime.php` | 1–58 | Local time VO; `fromString`. |
| `src/TomlDateTimeInterface.php` | 1–7 | Empty marker interface. |
| `src/Nodes/Node.php` | 1–10 | Marker interface. |
| `src/Nodes/ValuableNode.php` | 1–10 | Marker interface for value nodes. |
| `src/Nodes/NumericNode.php` | 1–10 | Marker interface for int/float. |
| `src/Nodes/TomlDateTimeNode.php` | 1–10 | Marker interface for datetime nodes. |
| `src/Nodes/RootTableNode.php` | 1–28 | Root container; `addElement`, `elements`. |
| `src/Nodes/TableNode.php` | 1–29 | Regular table; `key`, `addElement`, `elements`. |
| `src/Nodes/ArrayTableNode.php` | 1–10 | Empty subclass of `TableNode`. |
| `src/Nodes/KeyNode.php` | 1–26 | Dotted key; `addKey`, `keys`. |
| `src/Nodes/KeyValuePairNode.php` | 1–16 | Pair of `KeyNode` + `ValuableNode`. |
| `src/Nodes/StringNode.php` | 1–13 | `string $value`. |
| `src/Nodes/BareNode.php` | 1–13 | `string $value`. |
| `src/Nodes/IntegerNode.php` | 1–13 | `int $value`. |
| `src/Nodes/FloatNode.php` | 1–13 | `float|string $value`. |
| `src/Nodes/BooleanNode.php` | 1–13 | `bool $value`. |
| `src/Nodes/ArrayNode.php` | 1–26 | `addElement`, `elements`. |
| `src/Nodes/InlineTableNode.php` | 1–26 | `addElement`, `elements`. |
| `src/Nodes/OffsetDateTimeNode.php` | 1–15 | Wraps `TomlDateTime`. |
| `src/Nodes/LocalDateTimeNode.php` | 1–15 | Wraps `TomlLocalDateTime`. |
| `src/Nodes/LocalDateNode.php` | 1–15 | Wraps `TomlLocalDate`. |
| `src/Nodes/LocalTimeNode.php` | 1–15 | Wraps `TomlLocalTime`. |

### 14.3 Test files

| File | Lines read | Key contents |
|---|---|---|
| `tests/Pest.php` | 1–44 | Pest bootstrap; `toBeOne` expectation; `something()` placeholder. |
| `tests/TestCase.php` | 1–12 | Empty abstract subclass of `PHPUnit\Framework\TestCase`. |
| `tests/Unit/TomlDecodeTest.php` | 1–223 | 5 cases: canonical decode, prefix-collision array-tables, datetime decode, float decode, π as float. |
| `tests/Unit/TomlEncodeTest.php` | 1–186 | 4 cases: canonical encode, `DateTimeInterface` encode, null skip, null-in-array rejection. |
| `tests/Unit/TomlErrorTest.php` | 1–67 | 2 cases: caret-on-bad-value error, array-order violation error. |

### 14.4 Git history

| Command | Output summary |
|---|---|
| `git remote -v` | `origin https://github.com/vanodevium/toml.git` |
| `git symbolic-ref --short HEAD` | `main` |
| `git tag` | v1.0.1, v1.0.2, v1.0.3, v1.0.4, v1.0.5, v1.0.6, v1.1.0, v1.1.1 |
| `git shortlog -sne --all` | 50 commits, single author `Vano Devium <webdevium@gmail.com>` |
| `git log --pretty=%ci` HEAD | 2026-11-16 21:47:12 +0200 |
| `git log --pretty=%ci` tail | 2024-08-10 12:30:51 +0300 |
| `git show --stat HEAD` | v1.1.1 — README + composer.json version bumps only |
| `git show 24c139e` | v1.1.0 — prefix-bug fix in `TomlKeystore.php` lines 234–247 + 2 new tests |
| `git show a55e6ba` | v1.1.0 — rector cosmetic refactor across 41 files |
| `git show 9456675` | v1.1.0 — README 1.1.0 claim + composer.json version + `run-toml-test.sh -toml=1.0` |
| `git show 08fa11e` | v1.0.6 — composer.json version bump |

### 14.5 GitHub API queries

| Query | Result |
|---|---|
| `GET /repos/vanodevium/toml/issues?state=open` | **UNVERIFIED** — HTTP 403 "API rate limit exceeded" (two attempts, two egress IPs). |
| `GET /repos/vanodevium/toml` | **UNVERIFIED** — same rate-limit failure. |

### 14.6 Local execution attempts

| Command | Result |
|---|---|
| `php --version` | command not found (no PHP on sandbox) |
| `ls vendor/` | directory does not exist (`composer install` not run) |
| `composer test` | not executed (no PHP, no Composer) |

The full conformance suite and unit tests were **not executed locally**. All claims about test outcomes are inferred from reading the test source and the CI workflow definition; they are tagged DOCUMENT-VERIFIED at best.

---

**End of analysis.**
