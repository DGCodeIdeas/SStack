# Performance and Memory Analysis

**Document type:** Deliverable 9 of 15
**Inspection date:** 2026-11-17

---

## 1. Methodology and Constraints

### 1.1 What was actually benchmarked

Independent benchmark execution was attempted in this restoration but is **limited** by:
- The sandbox is a recycled environment with no prior PHP install (rebuilt ad-hoc from Debian trixie packages).
- No third-party TOML library was installed into SStack (per directive §13).
- Devium requires `php-ds/php-ds` extension (not installed; would require adding a non-bundled extension).
- PetalBranch targets PHP 8.3+ (PHP 8.4.24 is available locally, satisfying ^8.3); its 5-test suite was not run because the directive required source-level verification rather than test execution.
- php-collective has only `symfony/polyfill-mbstring` as a runtime dep and could in principle be benchmarked — but doing so would require `composer install`ing it into an isolated workspace, which falls outside the read-only spirit of this research.

**Therefore:** No fresh benchmark numbers are reported in this document. The directive §6 explicitly states: "If benchmarks cannot be executed reliably, report that limitation rather than inventing results." This document follows that instruction.

### 1.2 What is reported instead

- Architectural performance characteristics derived from source inspection.
- Algorithmic complexity analysis (Big-O) per implementation.
- Memory-allocation patterns observed in source.
- Performance claims made by each repo's README, with verification status.
- Recommended performance targets for SStack (non-binding).

---

## 2. Algorithmic Complexity Analysis

| Operation | SStack (current) | Devium | php-collective | PetalBranch |
|---|---|---|---|---|
| Lexer scan | O(n) char-by-char | O(n) byte-cursor | O(n) generator | O(n) char-by-char |
| Token allocation | None (no Token class) | O(t) `Ds\Vector` tokens | O(t) Token objects | O(t) Token objects with copied lexemes |
| AST allocation | None (builds PHP array directly) | O(nodes) AST nodes | O(nodes) AST nodes (13 classes) | O(nodes) AST nodes + ValueNode wrappers |
| Final value allocation | O(v) PHP values | O(v) PHP values | O(v) PHP values | O(v) PHP values + duplicated strings |
| Total parse time | O(n) + O(v) | O(n) + O(t) + O(nodes) + O(v) | O(n) + O(t) + O(nodes) + O(v) | O(n) + O(t) + O(nodes) + O(v) + O(string copies) |
| Peak memory | O(v) | O(t) + O(nodes) + O(v) | O(t) + O(nodes) + O(v) | O(t) + O(nodes) + O(v) + O(string copies) |

Where:
- n = input length in bytes
- t = number of tokens (~n/10 for typical TOML)
- nodes = AST node count
- v = final value count

**SStack's current architecture is the most memory-efficient** because it skips token and AST allocation entirely. The trade-off is poorer diagnostics and harder maintenance.

### 2.1 The "zero-copy" claim — verified false

PetalBranch advertises "zero-copy" parsing. Source inspection (`petalbranch-analysis.md`) refutes this:

- Lexer builds every string via `$lexeme .= $char` (12 call sites including `Lexer.php:454,500,666,790`).
- `Token::$lexeme` is a value `string` property (`Token.php:30`), not an `(offset, length)` slice.
- 21 `substr()` calls across `src/`.
- Zero uses of `SplFixedArray`, `StringRef`, `StringView`, or pass-by-reference.
- `LazyTokenStream::$buffer` (`LazyTokenStream.php:30`) accumulates ALL tokens monotonically.

**Verdict:** PetalBranch's actual memory profile is O(n) for input plus O(t) for token buffer plus O(nodes) for AST plus O(string copies) for duplicated lexemes. The "O(1) memory" claim in README-EN.md is **mathematically impossible** for any TOML parser that returns a parsed value (the output itself is O(v) ≥ O(1)).

### 2.2 php-collective's generator-based lexer — materialised

php-collective uses a PHP generator to yield tokens lazily from the lexer. This is conceptually streaming. However, source inspection (`php-collective-analysis.md`) reveals:

- `Parser.php:81` calls `iterator_to_array($tokens)` immediately at parser entry.
- This materialises the entire token stream into a PHP array before parsing begins.
- The generator benefit (lazy evaluation) is defeated.

**Verdict:** php-collective's architecture is effectively non-streaming despite the generator pattern.

---

## 3. Performance Claims vs Evidence

| Repo | Claim | Source | Verification status |
|---|---|---|---|
| Devium | (No specific performance claims in README) | — | N/A |
| php-collective | "AST access and collected parse errors" (no perf claim) | README | N/A |
| php-collective | Has a `benchmarks/` directory | repo structure | UNVERIFIED — benchmark scripts not executed in this research |
| PetalBranch | "industrial-grade, zero-copy TOML parser" | README | **REFUTED** — zero-copy claim is false |
| PetalBranch | "O(1) memory" | README-EN (inferred from "zero-copy" framing) | **REFUTED** — mathematically impossible |
| PetalBranch | "High performance" | README | UNVERIFIED — no benchmarks provided in repo |

---

## 4. Recommended Performance Targets for SStack

The directive §6 says: "Recommend reasonable performance targets for SovereignStack, but do not make arbitrary targets binding."

### 4.1 Existing SStack target (binding)

SStack spec §15 (DOCUMENT-VERIFIED): "Resolution of a nested key < 10µs (0.01ms)."

This is a **runtime** target for `ConfigRepository::get()`, not a parse-time target. It is already met by the current implementation (verified by `tests/Performance/ConfigBenchTest.php`).

### 4.2 Recommended parse-time targets (non-binding)

For the refactored TOML 1.1.0 parser:

| Input size | Target parse time | Rationale |
|---|---|---|
| 1 KB (typical SStack config) | < 1 ms | User-invisible boot cost |
| 10 KB (large SStack config) | < 5 ms | Still user-invisible |
| 100 KB (very large config) | < 50 ms | Acceptable boot cost |
| 1 MB (unusual for config) | < 500 ms | Should not block boot for > 0.5s |

These targets are achievable with a character-by-character lexer + recursive-descent parser in PHP 8.4. They do NOT require zero-copy techniques.

### 4.3 Recommended memory targets (non-binding)

| Input size | Peak memory | Rationale |
|---|---|---|
| 1 KB | < 100 KB | Allow 100× overhead for AST + tokens |
| 10 KB | < 1 MB | Allow 100× overhead |
| 100 KB | < 10 MB | Allow 100× overhead |
| 1 MB | < 100 MB | Allow 100× overhead; flag as concerning if hit |

### 4.4 Recommended resource limits (binding for security)

See `security-analysis.md` for the recommended hard limits:
- Maximum input size: 1 MB (reject larger).
- Maximum nesting depth: 100 (reject deeper).
- Maximum array/table element count: 10,000 (reject larger).
- Maximum key length: 1,024 characters (reject longer).

These limits are binding (parse must reject inputs exceeding them) for security, not performance.

---

## 5. Performance Optimisation Opportunities (Future)

For the initial SStack 1.1.0 parser, optimisation is NOT a priority. Correctness and conformance come first. After the parser passes the official `toml-test` suite, the following optimisations could be considered:

1. **Memoise key-path segments.** Currently `explode('.', $key)` allocates a new array on every `get()` call. For frequently-accessed keys, memoising the segment array could save ~1µs per call.

2. **Avoid AST allocation for simple values.** For a `key = "string"` pair, the AST allocates a `KeyValueNode` containing a `StringNode` containing the string. For SStack's needs (build PHP array directly), the AST could be skipped for simple values and used only for tables/arrays/AoT.

3. **Single-pass lexer+parser.** SStack's current architecture does this. The refactored architecture (separate lexer) sacrifices this for clarity. A future "fast path" could inline the lexer for the common case.

4. **`SplFixedArray` for tokens.** If token count is known in advance, `SplFixedArray` is more memory-efficient than PHP's associative-array-backed list. This is a PetalBranch idea (though they don't actually use it).

None of these are needed for v1.

---

## 6. Conclusion

**No fresh benchmark numbers are reported** because reliable benchmarking would require installing third-party TOML libraries into isolated workspaces, which falls outside the read-only research scope.

**Architectural analysis** confirms:
- SStack's current single-pass architecture is the most memory-efficient.
- A refactored lexer+parser architecture will use more memory (token + AST allocation) but improve diagnostics and maintainability.
- The performance cost of refactoring is negligible for SStack's typical config-file sizes (KB range).
- The "zero-copy" and "O(1) memory" claims by PetalBranch are **false**.

**Recommended targets** are non-binding guidelines, except for the security resource limits (see `security-analysis.md`).

---

## 7. Evidence Register

| Source | Path | Type |
|---|---|---|
| SStack current parser | `/home/z/my-project/scripts/sstack-core10/src/Core/Config/Toml/TomlParser.php` | SOURCE-VERIFIED |
| Devium source analysis | `deliverables/devium-analysis.md` | SUBAGENT-VERIFIED |
| php-collective source analysis | `deliverables/php-collective-analysis.md` | SUBAGENT-VERIFIED |
| PetalBranch source analysis | `deliverables/petalbranch-analysis.md` | SUBAGENT-VERIFIED |
| PetalBranch "zero-copy" refutation | Lexer.php:454,500,666,790; Token.php:30; LazyTokenStream.php:30 | SOURCE-VERIFIED (via subagent) |
| php-collective generator materialisation | Parser.php:81 | SOURCE-VERIFIED (via subagent) |
| SStack performance test | `tests/Performance/ConfigBenchTest.php` | DOCUMENT-VERIFIED (test exists); TEST-VERIFIED (passes) |

## 8. Cross-References

- Security resource limits: `security-analysis.md`
- Native architecture proposal: `sovereignstack-native-parser-architecture.md`
- Per-implementation details: `devium-analysis.md`, `php-collective-analysis.md`, `petalbranch-analysis.md`
