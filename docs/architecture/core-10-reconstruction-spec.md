# CORE-10 Reconstruction Specification v2

**Component:** Configuration & Environment Loader
**Blueprint:** Architecture/Core/CORE-10.md (DGLab historical reference)
**DGLab baseline:** 543329ed84a2148308e0cc455ac7e2fe27cf35f4
**Date:** 2026-11-15
**Status:** Specification v2 — incorporates 8 approved owner decisions. Implementation NOT authorized.

---

## 1. Approved Owner Decisions for CORE-10

| # | Decision | Source |
|---|---|---|
| D1 | Architecture: ConfigLoader facade backed by ConfigBuilder and EnvLoader | Owner decision |
| D2 | Merge precedence: Configuration files, then environment variables, then explicit application overrides | Owner decision |
| D3 | Format: Native TOML parser; TOML is the exclusive configuration-file format | Owner decision |
| D4 | Public API: get(), getOrFail(), has(), all(), allRaw(), getEnvironment() | Owner decision |
| D5 | Environment: Native backed enum with Development, Staging and Production; unknown values produce an error | Owner decision |
| D6 | Validation: Native ConfigValidator supporting required keys, expected types, configured constraints, required secret availability | Owner decision |
| D7 | Exceptions: ConfigurationException, MissingConfigurationException, InvalidConfigurationException, InvalidEnvironmentException, ConfigurationFileException under SovereignStack\Core\Config\Exception | Owner decision |
| D8 | ConfigKey attribute: Deferred to CORE-17; CORE-10 functions independently without it | Owner decision |

---

## 2. DGLab Source Evidence

### Source files (10 PHP, 820 lines)

| File | Lines | DGLab Role | SStack Status |
|---|---:|---|---|
| Core/Config/ConfigInterface.php | 87 | Frozen interface | REPLACED (new API per D4) |
| Core/Config/ConfigBuilderInterface.php | 51 | Immutable builder contract | PRESERVED (backs facade per D1) |
| Core/Config/ConfigRepository.php | 142 | Immutable config store + secret redaction | PRESERVED |
| Core/Config/ConfigBuilder.php | 184 | Merges PHP files + $_ENV + overrides | ADAPTED (TOML replaces PHP per D3) |
| Core/Config/EnvLoaderInterface.php | 32 | .env loading contract | PRESERVED |
| Core/Config/EnvLoader.php | 162 | Parses .env with ${VAR} interpolation | PRESERVED |
| Core/Config/Exception/InvalidConfigFileException.php | 24 | Config file errors | RENAMED to ConfigurationFileException (D7) |
| Core/Config/Exception/InvalidEnvFileException.php | 16 | .env file errors | MERGED into ConfigurationFileException (D7) |
| Core/Config/Exception/MissingConfigurationException.php | 20 | Absent required key | PRESERVED (name retained D7) |
| Core/Config/Exception/UndefinedInterpolationException.php | 22 | Undefined ${VAR} | REVIEW (may merge into InvalidConfigurationException) |

### Test files (5 classes, ~67 methods)

| File | Methods | SStack Status |
|---|---:|---|
| tests/Unit/ConfigRepositoryTest.php | 23 | ADAPTED (add getEnvironment tests) |
| tests/Unit/ConfigBuilderTest.php | 16 | ADAPTED (replace PHP file tests with TOML) |
| tests/Unit/EnvLoaderTest.php | 17 | PRESERVED |
| tests/Performance/ConfigBenchTest.php | 4 | PRESERVED |
| tests/Security/ConfigSecurityTest.php | 7 | PRESERVED |

PHPStan: level max with bleedingEdge.neon. SStack must match.

---

## 3. Source-vs-Blueprint-vs-Approved Resolution Matrix

| Area | DGLab Source | DGLab Blueprint | SStack Approved | Resolution |
|---|---|---|---|---|
| Architecture | ConfigBuilder + EnvLoader (separate) | Single ConfigLoader | ConfigLoader facade over ConfigBuilder + EnvLoader (D1) | Owner D1 |
| Config format | PHP files (require) | JSON files | TOML native parser (D3) | Owner D3 — NEW, no historical TOML |
| Merge precedence | Files then $_ENV then overrides | Secret-aware (env wins secrets) | Config files then env then overrides (D2) | Owner D2 — source model retained |
| Environment detection | Not implemented | Environment enum (Dev/Staging/Production, default Production) | Backed enum; unknown = error (D5) | Owner D5 — NEW, stricter than blueprint |
| Schema validation | Not implemented | ConfigValidator class | Native ConfigValidator (D6) | Owner D6 — NEW |
| Public API | get/getOrFail/has/all/allRaw | get/has/all/getEnvironment | Union of both (D4) | Owner D4 |
| Exception naming | 4 separate exceptions | ConfigNotFoundException + ConfigValidationException | 5-class hierarchy (D7) | Owner D7 |
| ConfigKey attribute | Not implemented | #[ConfigKey] for CORE-17 | Deferred to CORE-17 (D8) | Owner D8 |
| Secret redaction | SECRET_PATTERN + redact() in ConfigRepository | redact() in ConfigLoader | Preserved from source | Source evidence |
| ${VAR} interpolation | EnvLoader: ${VAR} against $_ENV | ConfigLoader: escape sequences | EnvLoader behaviour preserved | Source evidence |
| $_SERVER population | $_ENV only (thread safety) | $_ENV AND $_SERVER | $_ENV only | Source evidence |
| Immutability | ConfigRepository readonly; ConfigBuilder frozen after build | ConfigRepository immutable | Source behaviour preserved | Source evidence |
| Null vs absent | get() returns default for absent; null distinguishable | get() throws if absent | Source behaviour: get() returns default; getOrFail() throws | Source evidence |

---

## 4. TOML Specification and Conformance

### Approved decision (D3)

TOML is the exclusive config-file format. Native parser required — no third-party TOML library.

### TOML version

TOML 1.0.0 (https://toml.io/en/v1.0.0). Latest stable version.

### Conformance requirements

The native parser MUST support: key-value pairs (string, integer, float, boolean, datetime, array, inline table), tables and nested tables, array of tables, bare/quoted/dotted keys, multi-line basic and literal strings, comments, all integer formats (decimal, hex, octal, binary, underscores), float formats (standard, exponent, inf, nan), boolean, datetime variants.

The parser MUST reject: invalid syntax with ConfigurationFileException (file path, line number, error description), duplicate keys with ConfigurationFileException.

### Historical evidence

DGLab does NOT implement TOML. DGLab uses PHP config files. TOML is a NEW SStack requirement per D3.

---

## 5. File Discovery, Loading Order, Merge Behaviour

### Loading order (D2)

1. TOML config files (lowest precedence) — loaded via ConfigBuilder::loadFile()
2. Environment variables — .env loaded via EnvLoader; $_ENV merged (string values, underscores to dots, lowercased)
3. Explicit overrides (highest precedence) — via ConfigBuilder::withOverride()

### Merge semantics

Recursive merge: when both sides have arrays at same key, merge recursively. Otherwise higher-precedence wins. Matches DGLab source ConfigBuilder::mergeRecursive().

### $_ENV to config key mapping (source-preserved)

APP_URL to app.url, DB_HOST to db.host. Conversion: strtolower(str_replace('_', '.', $envKey)). Only string values merged; non-string and empty skipped.

### .env precedence (source-preserved)

Existing $_ENV keys NEVER overwritten by .env file. Environment wins over .env file.

### Historical evidence

ConfigBuilder merge: verified in ConfigBuilder.php lines 73-89. EnvLoader precedence: verified in EnvLoader.php lines 34-51.

---

## 6. Secret Metadata, Redaction, Validation

### Secret pattern (source-preserved)

Pattern: /password|passwd|secret|token|authorization|cookie|private_key|api_key|secret_key|\bkey\b/i

Source: ConfigInterface.php line 31 (frozen constant).

### Redaction behaviour (source-preserved)

- all(): recursive copy with secrets replaced by '***REDACTED***'. Safe to log.
- allRaw(): unredacted data. Trusted callers only. Never log.
- get(): returns RAW value including secrets.

Source: ConfigRepository.php lines 82-90, 106-125.

### Required secret validation (D6 — NEW)

ConfigValidator MUST check required secret keys are present and non-empty. NEW SStack feature — no historical implementation.

---

## 7. Environment-Variable Mapping and Parsing Rules

### .env parsing (source-preserved — all behaviour from EnvLoader.php)

1. Lines starting with # are comments — skipped
2. Blank lines skipped
3. export KEY=VALUE and KEY=VALUE both accepted
4. Keys must match /^[A-Z_][A-Z0-9_]*$/i
5. Single-quoted: literal (no interpolation)
6. Double-quoted: ${VAR} interpolation against $_ENV
7. Bare: trimmed, ${VAR} interpolation applies
8. Existing $_ENV keys NEVER overwritten
9. Writes to $_ENV only — never putenv() or getenv()
10. Undefined ${VAR} left as literal
11. Malformed lines throw (becomes ConfigurationFileException per D7)

### Environment detection (D5 — NEW)

Backed enum: Development, Staging, Production. Detection from APP_ENV in merged config.

- development/dev/local -> Development
- staging/stage -> Staging
- production/prod -> Production
- absent -> InvalidEnvironmentException (D5: unknown must error)
- unrecognised -> InvalidEnvironmentException

Historical discrepancy: Blueprint defaulted absent to Production. SStack requires error. This is a behavioural change.

---

## 8. Nested-Key Access, Missing-vs-Null, Default Semantics

### Dot-notation (source-preserved)

Keys use dot-notation: db.connections.primary.host. Split by dot, traverse nested array.

### Key validation (source-preserved, exception reclassified per D7)

- Empty key: throws InvalidConfigurationException (was \InvalidArgumentException)
- Consecutive dots: throws InvalidConfigurationException
- Leading dot: throws InvalidConfigurationException

Source: ConfigRepository.php segments() method.

### Missing vs null (source-preserved)

- get('missing') without default: returns null (absent)
- get('existing.null_key') where value is null: returns null (present, value is null)
- has('missing'): false; has('existing.null_key'): true
- getOrFail('missing'): throws MissingConfigurationException

Source: ConfigRepositoryTest testNullValueIsDistinguishableFromAbsentKey.

### Default semantics (source-preserved)

get(key, default): absent key returns default. Present key with null value returns null (NOT default).

---

## 9. ConfigValidator Contracts (D6 — NEW)

### Interface

ConfigValidator validates config against a schema before ConfigRepository construction.

### Schema supports

- Required keys: must be present; absence throws MissingConfigurationException
- Expected types: each key's type; mismatch throws InvalidConfigurationException
- Configured constraints: custom rules (length, range, enum); violation throws InvalidConfigurationException
- Required secrets: must be present and non-empty; absence/empty throws MissingConfigurationException

### Lifecycle

1. ConfigLoader loads and merges all sources
2. ConfigValidator::validate() called before ConfigRepository construction
3. Failure: exception thrown, Kernel boot fails
4. Success: ConfigRepository constructed and frozen
5. Validation ONCE at boot — not per get() call

### Historical evidence

No ConfigValidator exists in DGLab. Entirely NEW per D6.

---

## 10. Exception Hierarchy (D7)

### Approved hierarchy

ConfigurationException (base, extends \RuntimeException)
- MissingConfigurationException
- InvalidConfigurationException
- InvalidEnvironmentException
- ConfigurationFileException

Namespace: SovereignStack\Core\Config\Exception

### Exception contracts

| Exception | Thrown When | Context |
|---|---|---|
| ConfigurationException | Base for all config errors | Message |
| MissingConfigurationException | Required key absent or required secret missing | Key name |
| InvalidConfigurationException | Type mismatch, constraint violation, malformed key | Key, expected type, actual |
| InvalidEnvironmentException | APP_ENV absent or unrecognised | Raw value if any |
| ConfigurationFileException | TOML/.env parse error, file missing | File path, line number |

### Chaining

All exceptions support $previous chaining. Lower-level exceptions (parse errors) passed as $previous when re-thrown.

### Safe diagnostics

Messages MUST NOT include secret values. MAY include key names, file paths, line numbers.

### Historical mapping

| DGLab Exception | SStack Exception | Evidence |
|---|---|---|
| MissingConfigurationException | MissingConfigurationException (retained) | Source: Exception/MissingConfigurationException.php |
| InvalidConfigFileException | ConfigurationFileException | Source: Exception/InvalidConfigFileException.php — renamed |
| InvalidEnvFileException | ConfigurationFileException | Source: Exception/InvalidEnvFileException.php — merged |
| UndefinedInterpolationException | InvalidConfigurationException | Source: Exception/UndefinedInterpolationException.php — merged |
| \InvalidArgumentException (key validation) | InvalidConfigurationException | Source: ConfigRepository segments() — reclassified |
| \UnexpectedValueException (.env malformed) | ConfigurationFileException | Source: EnvLoader — reclassified |
| \LogicException (builder frozen) | InvalidConfigurationException | Source: ConfigBuilder — reclassified |
| (none) | ConfigurationException (base) | NEW per D7 |
| (none) | InvalidEnvironmentException | NEW per D7 |

---

## 11. Updated Acceptance-Test Matrix

### ConfigRepository (25 tests)

| # | Test | Source |
|---|---|---|
| 1-22 | (22 tests from DGLab source — get/getOrFail/has/all/allRaw/redact/null-vs-absent/key-validation) | DGLab source |
| 23 | GetEnvironment returns detected environment | NEW (D5) |
| 24 | GetEnvironment throws for absent APP_ENV | NEW (D5) |
| 25 | GetEnvironment throws for unrecognised APP_ENV | NEW (D5) |

### ConfigBuilder (18 tests)

| # | Test | Source |
|---|---|---|
| 26-27 | Load TOML file merges | NEW (D3) — replaces PHP file tests |
| 28 | Load file throws when missing | DGLab source (exception reclassified) |
| 29-30 | Load file throws on malformed/duplicate TOML | NEW (D3) |
| 31-42 | Override/env/freeze tests (12 from DGLab source) | DGLab source |
| 43 | TOML preserves native types | NEW (D3) |

### EnvLoader (17 tests — all from DGLab source, preserved)

| # | Test | Source |
|---|---|---|
| 44-60 | All 17 EnvLoader tests | DGLab source |

### ConfigValidator (8 tests — all NEW)

| # | Test | Source |
|---|---|---|
| 61 | Valid config passes | NEW (D6) |
| 62 | Missing required key throws | NEW (D6) |
| 63 | Type mismatch throws | NEW (D6) |
| 64 | Constraint violation throws | NEW (D6) |
| 65 | Missing required secret throws | NEW (D6) |
| 66 | Empty required secret throws | NEW (D6) |
| 67 | All requirements satisfied passes | NEW (D6) |
| 68 | Validator does not mutate config | NEW (D6) |

### Security (7 tests — from DGLab source)

| # | Test | Source |
|---|---|---|
| 69-75 | All 7 security tests | DGLab source |

### Performance (4 tests — from DGLab source)

| # | Test | Source |
|---|---|---|
| 76-79 | Resolution/has/miss/build-time benchmarks | DGLab source |

### TOML Parser (10 tests — all NEW)

| # | Test | Source |
|---|---|---|
| 80 | Parse basic key-value pairs | NEW (D3) |
| 81 | Parse nested tables | NEW (D3) |
| 82 | Parse array of tables | NEW (D3) |
| 83 | Parse all value types | NEW (D3) |
| 84 | Parse multi-line strings | NEW (D3) |
| 85 | Parse comments | NEW (D3) |
| 86 | Reject malformed TOML | NEW (D3) |
| 87 | Reject duplicate keys | NEW (D3) |
| 88 | Parse dotted keys | NEW (D3) |
| 89 | Parse quoted keys | NEW (D3) |

**Total: 89 acceptance tests** (54 from DGLab source, 35 new SStack requirements)

---

## 12. Remaining Open Questions and Blockers

### Blocking (must resolve before implementation)

| # | Question |
|---|---|
| B1 | TOML parser scope: Full TOML 1.0.0 parser or start with common subset? |
| B2 | ConfigValidator schema format: Array spec, TOML schema, fluent builder, or attributes? |
| B3 | ConfigLoader facade API: Single load() call or individual step methods? |

### Non-blocking (can resolve during implementation)

| # | Question |
|---|---|
| N1 | UndefinedInterpolationException retained or merged into InvalidConfigurationException? |
| N2 | Environment enum helper methods (displayErrors, enableOpcache)? |
| N3 | SECRET_PATTERN updates for SStack-specific keys? |
| N4 | TOML parser support for rarely-used features (hex/octal/binary integers)? |

### Corrected blocking-item count

v1 listed 8 open items (3 marked blocking). v2 has 3 blocking (B1-B3) and 4 non-blocking (N1-N4). All 8 original v1 items resolved by owner decisions D1-D8.

---

## 13. Completion Criteria (per OD-007)

1. Verified source and blueprint requirements (this document)
2. Agreed scope: zero external deps, zero Core-internal deps, PHP 8.4 + ext-json
3. Complete implementation (no stubs, no placeholders)
4. 89 unit/integration/security/performance tests pass
5. PHPStan level max with bleedingEdge — zero errors
6. Documentation of public contracts
7. Completion review against DGLab source, blueprint, approved architecture

**NOT complete if tests pass but implementation diverges from approved spec without owner approval.**

---

*End of CORE-10 Reconstruction Specification v2. Submitted for owner approval. Implementation NOT authorized.*
