# CORE-10 Reconstruction Specification v3

**Component:** Configuration & Environment Loader
**Blueprint:** Architecture/Core/CORE-10.md (DGLab historical reference)
**DGLab baseline:** 543329ed84a2148308e0cc455ac7e2fe27cf35f4
**Date:** 2026-11-15
**Status:** Specification v3 — all blocking items resolved. Implementation NOT authorized.

---

## 1. Approved Owner Decisions

### CORE-10 Sub-Decisions (1-8, approved 2026-11-15)

| # | Decision |
|---|---|
| D1 | ConfigLoader facade backed by ConfigBuilder and EnvLoader |
| D2 | Merge precedence: config files → environment variables → explicit overrides |
| D3 | Native TOML parser; TOML is the exclusive configuration-file format |
| D4 | Public API: get(), getOrFail(), has(), all(), allRaw(), getEnvironment() |
| D5 | Environment backed enum (Development/Staging/Production); unknown = error |
| D6 | Native ConfigValidator: required keys, types, constraints, required secrets |
| D7 | Exception hierarchy: ConfigurationException + 4 subclasses |
| D8 | ConfigKey attribute deferred to CORE-17 |

### Blocking Items Resolved (B1-B3, approved 2026-11-15)

| # | Decision |
|---|---|
| B1 | Full TOML 1.0.0 support; native implementation; comprehensive conformance tests |
| B2 | PHP array-based validation schemas; required keys, types, constraints, required secrets |
| B3 | Single public ConfigLoader::load() method orchestrates complete lifecycle |

### Deferred Alternative (recorded, not approved)

Multi-step ConfigLoader API (discoverFiles/loadFiles/loadEnvironment/merge/validate/build) documented in Section 14 as a future consideration. NOT part of CORE-10's approved public API.

---

## 2. DGLab Source Evidence

### Source inventory (10 PHP files, 820 lines; 5 test files, ~67 methods)

| File | Lines | DGLab Role | SStack Status | Evidence |
|---|---:|---|---|---|
| Core/Config/ConfigInterface.php | 87 | Frozen interface: get/getOrFail/has/all/allRaw + SECRET_PATTERN | REPLACED by D4 API (adds getEnvironment) | Source: verified at baseline |
| Core/Config/ConfigBuilderInterface.php | 51 | Immutable builder: loadFile/withOverride/build | PRESERVED (backs facade per D1) | Source: verified |
| Core/Config/ConfigRepository.php | 142 | Immutable store; dot-notation; secret redact() | PRESERVED + add getEnvironment() | Source: verified |
| Core/Config/ConfigBuilder.php | 184 | Merges PHP files + $_ENV + overrides; freezes | ADAPTED: TOML loader replaces PHP require (D3) | Source: verified |
| Core/Config/EnvLoaderInterface.php | 32 | .env loading contract | PRESERVED | Source: verified |
| Core/Config/EnvLoader.php | 162 | Parses .env; ${VAR} interpolation; env-wins | PRESERVED | Source: verified |
| Exception/InvalidConfigFileException.php | 24 | Config file errors | RENAMED → ConfigurationFileException (D7) | Source: verified |
| Exception/InvalidEnvFileException.php | 16 | .env file errors | MERGED → ConfigurationFileException (D7) | Source: verified |
| Exception/MissingConfigurationException.php | 20 | Absent required key | PRESERVED (name retained D7) | Source: verified |
| Exception/UndefinedInterpolationException.php | 22 | Undefined ${VAR} | MERGED → InvalidConfigurationException (D7) | Source: verified |

### Test inventory (verified at baseline)

| File | Methods | SStack Status |
|---|---:|---|
| tests/Unit/ConfigRepositoryTest.php | 23 | ADAPTED (add getEnvironment tests; exception types reclassified) |
| tests/Unit/ConfigBuilderTest.php | 16 | ADAPTED (replace PHP file tests with TOML tests per D3) |
| tests/Unit/EnvLoaderTest.php | 17 | PRESERVED |
| tests/Performance/ConfigBenchTest.php | 4 | PRESERVED |
| tests/Security/ConfigSecurityTest.php | 7 | PRESERVED |

### PHPStan: level max with bleedingEdge.neon. SStack must match.

---

## 3. Source-vs-Blueprint-vs-Approved Resolution Matrix

| Area | DGLab Source (historical) | DGLab Blueprint (historical) | SStack Approved | Evidence |
|---|---|---|---|---|
| Architecture | ConfigBuilder + EnvLoader (separate, no facade) | Single ConfigLoader class | ConfigLoader facade over ConfigBuilder + EnvLoader (D1+B3) | Owner decision |
| Config format | PHP files (require returns array) | JSON files | TOML 1.0.0 native parser (D3+B1) | Owner decision; NEW — no historical TOML |
| Merge precedence | Files → $_ENV → overrides (simple) | Secret-aware (env wins secrets; JSON wins non-secrets) | Config files → env vars → explicit overrides (D2) | Owner decision; source model retained |
| Public API | get/getOrFail/has/all/allRaw | get/has/all/getEnvironment | Union: all 6 methods (D4) | Owner decision |
| Environment detection | NOT IMPLEMENTED | Environment enum (Dev/Staging/Production; default Production for absent) | Backed enum; unknown/absent = error (D5) | Owner decision; NEW; stricter than blueprint |
| Schema validation | NOT IMPLEMENTED | ConfigValidator class | Native ConfigValidator with PHP array schemas (D6+B2) | Owner decision; NEW |
| Exception naming | 4 separate exceptions | ConfigNotFoundException + ConfigValidationException | 5-class hierarchy (D7) | Owner decision |
| ConfigKey attribute | NOT IMPLEMENTED | #[ConfigKey] for CORE-17 | Deferred to CORE-17 (D8) | Owner decision |
| Secret redaction | SECRET_PATTERN + redact() in ConfigRepository | redact() in ConfigLoader | Preserved from source | Source evidence: ConfigRepository.php |
| ${VAR} interpolation | EnvLoader: ${VAR} against $_ENV; undefined left literal | ConfigLoader: escape sequences | EnvLoader behaviour preserved | Source evidence: EnvLoader.php |
| $_SERVER population | $_ENV only (thread safety) | $_ENV AND $_SERVER | $_ENV only | Source evidence: EnvLoader.php |
| Immutability | ConfigRepository readonly array; ConfigBuilder frozen after build | ConfigRepository immutable | Source behaviour preserved | Source evidence: ConfigRepository + ConfigBuilder |
| Null vs absent | get() returns default for absent; null distinguishable from absent | get() throws if absent and no default | Source behaviour: get() returns default; getOrFail() throws | Source evidence: ConfigRepositoryTest |
| ConfigLoader facade API | N/A (no facade exists) | N/A (blueprint's ConfigLoader is monolithic) | Single load() method orchestrates lifecycle (B3) | Owner decision; NEW |
| TOML parser scope | N/A | N/A | Full TOML 1.0.0 conformance (B1) | Owner decision; NEW |

---

## 4. TOML 1.0.0 Parser Architecture

### Approved decision (D3 + B1)

Full TOML 1.0.0 support. Native SovereignStack implementation. No third-party parser.

### TOML specification reference

TOML 1.0.0 — https://toml.io/en/v1.0.0 (released January 2021)

### Parser architecture

The TOML parser is a new SStack component within CORE-10. It consists of:

| Class | Responsibility |
|---|---|
| TomlParser (final) | Entry point: parse(string $toml): array. Tokenizes, parses, validates, returns nested PHP array. |
| TomlTokenizer (final) | Converts TOML string into token stream. Handles all lexical rules per TOML 1.0.0. |
| TomlLexer (internal) | Defines token types and lexical patterns. Not public. |

### Complete syntax coverage (TOML 1.0.0)

The parser MUST support ALL of the following per TOML 1.0.0 specification:

**Comments:**
- `#` to end of line — skipped

**Keys:**
- Bare keys: `[A-Za-z0-9_-]+`
- Quoted keys: `"basic"` or `'literal'`
- Dotted keys: `a.b.c = value` (creates nested structure)

**Values:**
- Strings: basic (`"..."` with escapes), literal (`'...'` no escapes), multi-line basic (`"""..."""`), multi-line literal (`'''...'''`)
- Integers: decimal, hex (`0x`), octal (`0o`), binary (`0b`), with underscores
- Floats: standard (`3.14`), exponent (`5e+22`), special (`inf`, `nan`, `+inf`, `-inf`)
- Booleans: `true`, `false`
- Offset date-time: `1979-05-27T07:32:00Z`, `1979-05-27T07:32:00-07:00`
- Local date-time: `1979-05-27T07:32:00`
- Local date: `1979-05-27`
- Local time: `07:32:00`
- Arrays: `[1, 2, 3]` (can span multiple lines, trailing comma allowed)
- Inline tables: `{key = value, key2 = value2}` (single line, no trailing comma)

**Tables:**
- Standard: `[table]`
- Nested: `[parent.child]`
- Quoted: `["table name"]`
- Dotted: `[a.b.c]`

**Array of tables:**
- `[[products]]` — creates array entries

**String escapes (basic strings):**
- `\n`, `\t`, `\r`, `\"`, `\\`, `\b`, `\f`, `\uXXXX`, `\UXXXXXXXX`

### Parsing errors and diagnostics

The parser MUST reject invalid TOML with `ConfigurationFileException` containing:
- File path (when loaded via file)
- Line number (1-indexed)
- Column number (where determinable)
- Error description (human-readable, no secret values)

**Rejected conditions:**
- Invalid syntax (malformed keys, unclosed strings, invalid values)
- Duplicate keys within same table
- Trailing data after inline table
- Multi-line basic string with invalid escape sequences
- Invalid date/time format
- Array with mixed types (TOML 1.0.0 requires homogeneous arrays)

### Normalization

- TOML tables → nested PHP arrays
- TOML keys are case-sensitive (preserved as-is)
- TOML integers → PHP int
- TOML floats → PHP float
- TOML booleans → PHP bool
- TOML strings → PHP string
- TOML datetimes → PHP DateTimeImmutable
- TOML arrays → PHP array (sequential)
- TOML inline tables → PHP array (associative)

### Historical evidence

DGLab does NOT implement TOML. DGLab uses PHP config files (`require`). TOML is entirely NEW per D3+B1. No historical TOML behaviour exists.

---

## 5. File Discovery, Loading Order, Duplicate Handling

### File discovery

Configuration files are NOT auto-discovered. The application (or ConfigLoader facade) explicitly passes file paths to ConfigBuilder::loadFile().

This matches DGLab source behaviour: ConfigBuilder::loadFile(string $path) takes explicit paths. No directory scanning or glob patterns.

Source evidence: ConfigBuilder.php line 40.

### Loading order (D2)

```
1. TOML config files (lowest precedence)
   - Multiple files loaded in call order
   - Each file merges recursively into the accumulated config
2. Environment variables (.env + existing $_ENV)
   - .env loaded via EnvLoader::load()
   - $_ENV string values merged with key conversion (APP_URL → app.url)
   - Existing $_ENV keys NOT overwritten by .env file (env wins over file)
3. Explicit application overrides (highest precedence)
   - Applied via ConfigBuilder::withOverride()
```

### Duplicate key handling

- **Within a single TOML file:** Duplicate keys are rejected by the TOML parser (ConfigurationFileException per TOML 1.0.0 spec).
- **Across multiple config files:** Later files override earlier files at the key level. Recursive merge: if both sides have arrays at the same key, they merge recursively. Otherwise, the later value wins.
- **Config file vs environment:** Environment variables override config file values (per D2 precedence).
- **Config file/env vs explicit override:** Explicit overrides always win (highest precedence).

### Historical evidence

- ConfigBuilder recursive merge: Source ConfigBuilder.php mergeRecursive() method (lines 137-156).
- EnvLoader env-wins-over-file: Source EnvLoader.php lines 40-42.
- Override precedence: Source ConfigBuilder.php build() method (lines 61-89).

---

## 6. Configuration Merge Semantics

### Recursive merge (from DGLab source — preserved)

When merging two config arrays:
1. For each key in the right-hand array:
   - If both left and right have arrays at this key → merge recursively
   - Otherwise → right value replaces left value

This allows a local.toml to override individual nested keys without redefining the entire tree.

Source evidence: ConfigBuilder.php mergeRecursive() method.

### $_ENV to config key mapping (from DGLab source — preserved)

Conversion: `strtolower(str_replace('_', '.', $envKey))`

Examples:
- `APP_URL=https://example.com` → `app.url = "https://example.com"`
- `DB_HOST=127.0.0.1` → `db.host = "127.0.0.1"`
- `DATABASE_NAME=mydb` → `database.name = "mydb"`

Only string values are merged. Non-string and empty-string values are skipped.

Source evidence: ConfigBuilder.php build() method lines 73-84.

### Type preservation

- TOML values preserve native types (int, float, bool, string, DateTimeImmutable, array)
- $_ENV values are always strings (environment variables are inherently strings)
- Explicit overrides can be any type

---

## 7. Environment-Variable Naming, Mapping, Type Conversion

### .env file parsing rules (from DGLab source — preserved)

All rules verified from EnvLoader.php source at baseline:

1. Lines starting with `#` (after optional whitespace) → comments, skipped
2. Blank lines → skipped
3. `export KEY=VALUE` and `KEY=VALUE` → both accepted (export prefix stripped)
4. Keys MUST match `/^[A-Z_][A-Z0-9_]*$/i`
5. Single-quoted values → literal (NO interpolation)
6. Double-quoted values → `${VAR}` interpolation against $_ENV applies
7. Bare values → trimmed; `${VAR}` interpolation applies
8. Existing $_ENV keys → NEVER overwritten (environment wins over .env file)
9. Writes to $_ENV only → NEVER calls putenv() or getenv() (thread safety per ADR-017)
10. Undefined `${VAR}` references → left as literal `${VAR}`
11. Malformed lines (missing `=`, invalid key) → throw (becomes ConfigurationFileException per D7)

Source evidence: EnvLoader.php (all rules verified against source code and tests).

### $_ENV merge rules (from DGLab source — preserved)

- Only string values from $_ENV are merged
- Non-string values → skipped
- Empty-string values → skipped
- Key conversion: lowercase + underscores to dots (APP_URL → app.url)

Source evidence: ConfigBuilder.php build() method.

### Historical evidence

All environment-variable behaviour is from DGLab source. No changes approved by owner decisions. The only change is exception reclassification (D7): malformed .env lines now throw ConfigurationFileException instead of \UnexpectedValueException.

---

## 8. Secret Metadata, Permitted Origins, Rejection, Redaction

### Secret pattern (from DGLab source — preserved, frozen)

```php
public const SECRET_PATTERN = '/password|passwd|secret|token|authorization|cookie|private_key|api_key|secret_key|\bkey\b/i';
```

Source: ConfigInterface.php line 31. Frozen per SDLC-AGRD §2.1.

### Permitted origins for secrets

Secrets may originate from:
1. **Environment variables** (.env file or real environment) — highest precedence for secret keys
2. **TOML config files** — lower precedence (CI should flag secrets in committed TOML)
3. **Explicit overrides** — programmatic, typically for testing

There are no origin restrictions in the current approved design — secrets from any source are accepted. A future enhancement could restrict secrets to environment-only origin, but this is NOT approved.

### Redaction behaviour (from DGLab source — preserved)

| Method | Behaviour | Safe to log? |
|---|---|---|
| `all()` | Recursive copy; all values whose keys match SECRET_PATTERN → `'***REDACTED***'` | YES |
| `allRaw()` | Returns unredacted data array | NO — trusted callers only |
| `get()` | Returns RAW value (including secrets) | Caller responsibility — must redact before logging |
| `getOrFail()` | Returns RAW value | Same as get() |
| `has()` | Returns bool (no value exposed) | YES |

Source evidence: ConfigRepository.php all() (lines 82-84), allRaw() (lines 87-90), redact() (lines 106-125).

### Required secret validation (D6 — NEW)

ConfigValidator MUST support checking that required secret keys are:
- Present in the merged configuration
- Non-empty (value is not null, not empty string, not empty array)

Absence or empty value → throws MissingConfigurationException.

This is a NEW SStack feature — no ConfigValidator exists in DGLab.

---

## 9. Nested-Key Access, Missing-vs-Null, Default Semantics

### Dot-notation traversal (from DGLab source — preserved)

Keys use dot-notation: `db.connections.primary.host`. Implementation splits by `.` and traverses the nested array.

Source evidence: ConfigRepository.php segments() method (lines 127-142).

### Key validation (from DGLab source; exception reclassified per D7)

| Condition | DGLab Source | SStack (D7) |
|---|---|---|
| Empty key (`''`) | \InvalidArgumentException | InvalidConfigurationException |
| Consecutive dots (`'app..name'`) | \InvalidArgumentException | InvalidConfigurationException |
| Leading dot (`'.app'`) | \InvalidArgumentException | InvalidConfigurationException |

Source evidence: ConfigRepository.php segments() method. Tests: ConfigRepositoryTest testEmptyKeyThrows, testConsecutiveDotsThrow, testLeadingDotKeyThrowsForEmptySegment.

### Missing vs null (from DGLab source — preserved)

| Scenario | get() | has() | getOrFail() |
|---|---|---|---|
| Key absent | Returns default (null if no default) | false | Throws MissingConfigurationException |
| Key present, value is null | Returns null | true | Returns null |
| Key present, value is non-null | Returns value | true | Returns value |

Source evidence: ConfigRepositoryTest testNullValueIsDistinguishableFromAbsentKey.

### Default-value semantics (from DGLab source — preserved)

- `get(key, default)`: When key is absent → returns `$default`. When key is present with null value → returns `null` (NOT the default).
- `get(key)`: When key is absent → returns `null` (default is null).

This means: a present key with a null value is distinguishable from an absent key, because the default is only returned for absent keys.

Source evidence: ConfigRepository.php get() method (lines 35-47).

---

## 10. Environment Detection and Invalid-Environment Handling (D5 — NEW)

### Environment enum

```php
enum Environment: string {
    case Development = 'development';
    case Staging = 'staging';
    case Production = 'production';
}
```

### Detection rules

The environment is detected from the merged configuration's `app.env` key (set from `APP_ENV` via the $_ENV mapping: `APP_ENV` → `app.env`).

| APP_ENV value | Environment | Notes |
|---|---|---|
| `development`, `dev`, `local` | `Development` | Case-insensitive |
| `staging`, `stage` | `Staging` | Case-insensitive |
| `production`, `prod` | `Production` | Case-insensitive |
| Absent (APP_ENV not set) | `InvalidEnvironmentException` | D5: unknown MUST error |
| Unrecognised value | `InvalidEnvironmentException` | D5: unknown MUST error |

### Historical discrepancy

DGLab's blueprint defaulted absent/unrecognised APP_ENV to `Production` (fail-safe). SStack's approved decision D5 requires an error for unknown or absent values. This is a **behavioural change** from the blueprint — SStack is stricter.

DGLab source does NOT implement environment detection at all. This is entirely NEW.

### Detection timing

Environment detection occurs during ConfigLoader::load() — after merge, before ConfigRepository construction. If detection fails, the load fails with InvalidEnvironmentException.

---

## 11. ConfigValidator Schema Contracts and Validation Lifecycle (D6 + B2 — NEW)

### Schema format (B2)

PHP array-based validation schemas. No TOML schema, no fluent builder, no attributes.

### Schema structure

```php
$schema = [
    'required' => [
        'app.name' => 'string',
        'app.env' => 'string',
        'database.host' => 'string',
        'database.port' => 'int',
    ],
    'optional' => [
        'app.debug' => 'bool',
        'cache.ttl' => 'int',
    ],
    'constraints' => [
        'database.port' => fn($v) => $v >= 1 && $v <= 65535,
        'app.name' => fn($v) => mb_strlen($v) >= 1 && mb_strlen($v) <= 255,
    ],
    'required_secrets' => [
        'database.password',
        'app.key',
    ],
];
```

### Schema sections

| Section | Purpose | Failure Exception |
|---|---|---|
| `required` | Keys that MUST be present with expected type | MissingConfigurationException (absent) / InvalidConfigurationException (type mismatch) |
| `optional` | Keys that MAY be present; if present, type must match | InvalidConfigurationException (type mismatch only) |
| `constraints` | Custom validation closures; receive value, return bool | InvalidConfigurationException (constraint violation) |
| `required_secrets` | Secret keys that MUST be present and non-empty | MissingConfigurationException (absent or empty) |

### Supported types

| Type string | PHP type check |
|---|---|
| `'string'` | `is_string($value)` |
| `'int'` | `is_int($value)` |
| `'float'` | `is_float($value)` (note: is_int returns false for float; int values are NOT accepted for float type) |
| `'bool'` | `is_bool($value)` |
| `'array'` | `is_array($value)` |
| `'datetime'` | `$value instanceof \DateTimeImmutable` |

### Custom validation extension

Constraints are PHP closures: `fn(mixed $value): bool`. Return `true` = valid, `false` = violation.

Future extension point: a `ConstraintInterface` could replace closures for reusable constraints. NOT approved for CORE-10 v1 — closures only.

### Validation lifecycle

1. ConfigLoader::load() merges all sources
2. Environment detection occurs (D5)
3. ConfigValidator::validate($mergedData, $schema) is called
4. If validation fails → appropriate exception thrown → Kernel boot fails
5. If validation passes → ConfigRepository constructed with merged data + detected Environment
6. Validation occurs ONCE at boot time — never on individual get() calls

### Failure atomicity

If validation fails, NO ConfigRepository is constructed. The merged data is discarded. The caller receives the exception with no partial state.

### Historical evidence

No ConfigValidator exists in DGLab. Entirely NEW per D6+B2.

---

## 12. Exception Inheritance, Chaining, Diagnostic Safety (D7)

### Approved hierarchy

```
SovereignStack\Core\Config\Exception\ConfigurationException (extends \RuntimeException)
├── MissingConfigurationException
├── InvalidConfigurationException
├── InvalidEnvironmentException
└── ConfigurationFileException
```

### Exception contracts

| Exception | Thrown When | Error Context Included | NEVER Includes |
|---|---|---|---|
| `ConfigurationException` | Base class for all config exceptions | Message | Secret values |
| `MissingConfigurationException` | Required key absent (getOrFail) or required secret missing/empty (validator) | Key name (dot-notation path) | Secret values |
| `InvalidConfigurationException` | Type mismatch, constraint violation, malformed key (empty/consecutive dots), builder frozen | Key name, expected type, actual type | Secret values |
| `InvalidEnvironmentException` | APP_ENV absent or unrecognised | Raw APP_ENV value (if present) | Other env values |
| `ConfigurationFileException` | TOML parse error, .env parse error, file missing/unreadable | File path, line number (where determinable), error description | Secret values, file contents |

### Previous-exception chaining

All exceptions extend \RuntimeException which supports `$previous`. When a lower-level exception is caught and re-thrown:

| Lower-level | Re-thrown As | $previous |
|---|---|---|
| TOML parse error (internal) | ConfigurationFileException | Original internal exception |
| \UnexpectedValueException (.env malformed) | ConfigurationFileException | Original UnexpectedValueException |
| \InvalidArgumentException (key validation) | InvalidConfigurationException | Original InvalidArgumentException |
| \LogicException (builder frozen) | InvalidConfigurationException | Original LogicException |
| Validation failure (internal) | MissingConfigurationException or InvalidConfigurationException | null (validation-generated, no lower-level exception) |

### Safe diagnostics

- Exception messages MUST NOT include secret values (passwords, tokens, keys)
- Exception messages MAY include: key names, file paths, line numbers, expected/actual types
- Exception messages MUST NOT include: $_ENV contents, raw config file contents, secret values
- File path disclosure in ConfigurationFileException is acceptable (needed for debugging)

### Historical mapping

| DGLab Exception | SStack Exception | Evidence |
|---|---|---|
| MissingConfigurationException | MissingConfigurationException (retained) | Source: Exception/MissingConfigurationException.php |
| InvalidConfigFileException | ConfigurationFileException | Source: renamed per D7 |
| InvalidEnvFileException | ConfigurationFileException | Source: merged per D7 |
| UndefinedInterpolationException | InvalidConfigurationException | Source: merged per D7 |
| \InvalidArgumentException (key validation) | InvalidConfigurationException | Source: reclassified per D7 |
| \UnexpectedValueException (.env malformed) | ConfigurationFileException | Source: reclassified per D7 |
| \LogicException (builder frozen) | InvalidConfigurationException | Source: reclassified per D7 |
| (none) | ConfigurationException (base) | NEW per D7 |
| (none) | InvalidEnvironmentException | NEW per D7 |

---

## 13. ConfigLoader Orchestration and Failure Atomicity (D1 + B3)

### ConfigLoader facade API (B3)

Single public method:

```php
final class ConfigLoader
{
    /**
     * Orchestrates the complete configuration lifecycle:
     * 1. Load TOML config files (via ConfigBuilder)
     * 2. Load .env file (via EnvLoader)
     * 3. Merge $_ENV into config (via ConfigBuilder)
     * 4. Apply explicit overrides (via ConfigBuilder)
     * 5. Detect environment (from merged config)
     * 6. Validate against schema (via ConfigValidator)
     * 7. Construct frozen ConfigRepository
     *
     * @param string[] $configFiles TOML file paths in load order.
     * @param string|null $envFile .env file path (null = skip .env loading).
     * @param array<string, mixed> $overrides Explicit overrides (dot-notation keys).
     * @param array|null $schema Validation schema (null = skip validation).
     *
     * @throws ConfigurationFileException If any file is missing or unparseable.
     * @throws InvalidEnvironmentException If APP_ENV is absent or unrecognised.
     * @throws MissingConfigurationException If a required key or secret is absent.
     * @throws InvalidConfigurationException If a type mismatch or constraint violation occurs.
     */
    public function load(
        array $configFiles = [],
        ?string $envFile = null,
        array $overrides = [],
        ?array $schema = null,
    ): ConfigInterface;
}
```

### Lifecycle ordering (D2)

```
load() execution order:
  1. ConfigBuilder created
  2. For each $configFiles path (in order): ConfigBuilder::loadFile($path)
     - TOML parsed by TomlParser
     - Merged recursively into accumulated config
  3. If $envFile !== null: EnvLoader::load($envFile)
     - .env parsed into $_ENV (env-wins-over-file)
  4. ConfigBuilder::build() called
     - Merges: TOML config → $_ENV → explicit overrides
     - Returns frozen merged array
  5. Environment detected from merged config (app.env key)
     - InvalidEnvironmentException if absent or unrecognised
  6. If $schema !== null: ConfigValidator::validate($merged, $schema)
     - MissingConfigurationException / InvalidConfigurationException on failure
  7. ConfigRepository constructed with merged data + Environment
  8. ConfigInterface returned (frozen, immutable)
```

### Failure atomicity

If ANY step fails (TOML parse error, .env parse error, environment detection failure, validation failure):
- NO ConfigRepository is constructed
- The merged data is discarded (not exposed to caller)
- The appropriate exception is thrown with diagnostic context
- $_ENV may have been populated by EnvLoader (step 3) — this is a side effect that cannot be rolled back

**Side effect note:** EnvLoader writes to $_ENV as a side effect. If a later step fails, $_ENV remains populated. This matches DGLab source behaviour — EnvLoader does not provide a rollback mechanism. This is acceptable because:
- $_ENV population is additive (only sets keys not already present)
- Failed boot means the process will not serve requests
- The caller is responsible for process cleanup

### ConfigBuilder and EnvLoader retain separate responsibilities

ConfigLoader is a facade — it delegates to ConfigBuilder and EnvLoader. Both retain their public interfaces and can be used independently (e.g., in tests or by the Kernel directly).

Source evidence: DGLab source has ConfigBuilder and EnvLoader as separate classes. The facade (D1+B3) is NEW — it wraps them without replacing them.

---

## 14. Deferred Alternative: Multi-Step ConfigLoader API

**Status:** RECORDED FOR FUTURE CONSIDERATION — NOT APPROVED FOR IMPLEMENTATION

### Alternative API

```php
$configLoader->discoverFiles(string ...$directories): self;
$configLoader->loadFiles(): self;
$configLoader->loadEnvironment(string $envFile): self;
$configLoader->merge(): self;
$configLoader->validate(array $schema): self;
$configLoader->build(): ConfigInterface;
```

### Potential benefits

- **Granular control:** Each step can be inspected, logged, or intercepted
- **Debugging:** Intermediate state accessible between steps
- **Extensibility:** New steps (remote config, feature flags) can be inserted
- **Testability:** Each step tested in isolation

### Disadvantages

- **Complexity:** More API surface to maintain and document
- **Misuse risk:** Caller could skip steps (e.g., forget to validate)
- **State management:** ConfigLoader becomes stateful between steps

### Appropriate future use cases

- Debug/development mode where intermediate config state is inspected
- Remote configuration loading (HUB-01 or HUB-20 integration)
- Hot-reload scenarios (future, not approved)

### NOT part of CORE-10

This alternative is explicitly deferred. CORE-10 implements the single `load()` method per B3. The multi-step API may be proposed as a future ADR if a concrete requirement emerges.

---

## 15. Configuration Repository Immutability and Secret Access Rules

### Immutability (from DGLab source — preserved)

- ConfigRepository is constructed with `private readonly array $data`
- No mutator methods exist on ConfigInterface (no set, no merge, no reload)
- After construction, the backing array cannot change
- ConfigBuilder freezes after build() — further loadFile/withOverride throws

Source evidence: ConfigRepository.php (`private readonly array $data`), ConfigBuilder.php (`$frozen` flag).

### Secret access rules

| Caller Type | Allowed Methods | Secret Exposure |
|---|---|---|
| Trusted internal (DB connection, auth) | get(), getOrFail(), allRaw() | Full access to secret values |
| Logging/diagnostic | all() | Secrets redacted |
| HTTP response/API | all() | Secrets redacted; NEVER allRaw() |
| Tests | All methods | Full access (test fixtures use non-real secrets) |

### Access enforcement

There is NO programmatic access control on the methods. The distinction between trusted and untrusted callers is by convention:
- `allRaw()` is documented as "trusted internal callers only"
- `get()` returns raw values — callers that log must redact
- `all()` always redacts

This matches DGLab source behaviour. No changes approved.

---

## 16. Updated Acceptance-Test Matrix

### ConfigRepository Tests (25 tests)

| # | Test | Source | Requirement |
|---|---|---|---|
| 1 | Get returns value for top-level key | DGLab source | D4: get() |
| 2 | Get returns value for nested key via dot-notation | DGLab source | D4: get() |
| 3 | Get returns default when key absent | DGLab source | D4: get() default |
| 4 | Get returns default when intermediate segment is not array | DGLab source | D4: get() edge |
| 5 | GetOrFail returns value when key present | DGLab source | D4: getOrFail() |
| 6 | GetOrFail throws when key absent | DGLab source | D4: getOrFail() + D7: MissingConfigurationException |
| 7 | GetOrFail throws on intermediate segment missing | DGLab source | D4: getOrFail() edge |
| 8 | Has returns true for present keys | DGLab source | D4: has() |
| 9 | Has returns false for absent keys | DGLab source | D4: has() |
| 10 | Has returns false when intermediate segment is not array | DGLab source | D4: has() edge |
| 11 | All returns entire tree with secrets redacted | DGLab source | D4: all() + secret redaction |
| 12 | AllRaw returns unredacted secrets | DGLab source | D4: allRaw() |
| 13 | Empty key throws | DGLab source | Key validation + D7: InvalidConfigurationException |
| 14 | Consecutive dots throw | DGLab source | Key validation + D7: InvalidConfigurationException |
| 15 | Leading dot throws | DGLab source | Key validation + D7: InvalidConfigurationException |
| 16 | Get supports scalar root values | DGLab source | Edge: scalar root |
| 17 | Get preserves nested array structure | DGLab source | Nested structure |
| 18 | Null value distinguishable from absent key | DGLab source | Missing-vs-null |
| 19 | All redacts nested secrets | DGLab source | Nested redaction |
| 20 | All redacts case-insensitively | DGLab source | Case-insensitive SECRET_PATTERN |
| 21 | Get returns raw secret value | DGLab source | Raw access via get() |
| 22 | All returns empty array with empty data | DGLab source | Empty config |
| 23 | GetEnvironment returns detected environment | NEW (D5) | D5: getEnvironment() |
| 24 | GetEnvironment throws for absent APP_ENV | NEW (D5) | D5: unknown = error |
| 25 | GetEnvironment throws for unrecognised APP_ENV | NEW (D5) | D5: unknown = error |

### ConfigBuilder Tests (18 tests)

| # | Test | Source | Requirement |
|---|---|---|---|
| 26 | Load TOML file merges into builder | NEW (D3) | D3: TOML format |
| 27 | Load TOML merges recursively on key collision | NEW (D3) | D2: recursive merge |
| 28 | Load file throws when missing | DGLab source | D7: ConfigurationFileException |
| 29 | Load file throws on malformed TOML | NEW (D3) | B1: TOML parse error |
| 30 | Load file throws on duplicate TOML keys | NEW (D3) | B1: TOML duplicate keys |
| 31 | WithOverride sets nested key | DGLab source | D2: overrides |
| 32 | WithOverride creates intermediate arrays | DGLab source | D2: nested override |
| 33 | Env vars merged into config | DGLab source | D2: env merge |
| 34 | Overrides take precedence over env vars | DGLab source | D2: precedence |
| 35 | Non-string env values skipped | DGLab source | $_ENV merge rule |
| 36 | Empty string env value skipped | DGLab source | $_ENV merge rule |
| 37 | Build freezes builder | DGLab source | Immutability + D7: InvalidConfigurationException |
| 38 | WithOverride after build throws | DGLab source | Immutability + D7 |
| 39 | Build called once only | DGLab source | Immutability + D7 |
| 40 | Fluent interface returns same instance | DGLab source | API contract |
| 41 | Build with no data returns empty repository | DGLab source | Edge: empty |
| 42 | WithOverride replaces existing value | DGLab source | Override semantics |
| 43 | TOML preserves native types (int, float, bool, datetime) | NEW (D3) | B1: type fidelity |

### EnvLoader Tests (17 tests — all from DGLab source)

| # | Test | Source | Requirement |
|---|---|---|---|
| 44 | Load parses key-value pairs | DGLab source | .env parsing |
| 45 | Load writes to $_ENV | DGLab source | $_ENV population |
| 46 | Load interpolates double-quoted values | DGLab source | ${VAR} interpolation |
| 47 | Load interpolates references to existing env vars | DGLab source | ${VAR} interpolation |
| 48 | Load does not interpolate single-quoted | DGLab source | Single-quote = literal |
| 49 | Load handles bare values | DGLab source | Bare value handling |
| 50 | Load handles export prefix | DGLab source | export stripping |
| 51 | Load skips comment lines | DGLab source | Comment handling |
| 52 | Load does not overwrite existing env vars | DGLab source | Env-wins-over-file |
| 53 | Load throws when file missing | DGLab source | D7: ConfigurationFileException |
| 54 | Load throws on malformed line | DGLab source | D7: ConfigurationFileException |
| 55 | Load throws on invalid key name | DGLab source | D7: ConfigurationFileException |
| 56 | Load handles blank lines | DGLab source | Blank line handling |
| 57 | Load handles CRLF line endings | DGLab source | Line ending handling |
| 58 | Undefined interpolation left literal | DGLab source | ${VAR} undefined |
| 59 | Load returns empty array for empty file | DGLab source | Edge: empty file |
| 60 | Load returns empty array for comments-only | DGLab source | Edge: comments only |

### ConfigValidator Tests (8 tests — all NEW)

| # | Test | Source | Requirement |
|---|---|---|---|
| 61 | Valid config passes validation | NEW (D6) | D6+B2: validation |
| 62 | Missing required key throws | NEW (D6) | D6: required keys |
| 63 | Type mismatch throws | NEW (D6) | D6: expected types |
| 64 | Constraint violation throws | NEW (D6) | D6: configured constraints |
| 65 | Missing required secret throws | NEW (D6) | D6: required secrets |
| 66 | Empty required secret throws | NEW (D6) | D6: non-empty check |
| 67 | All requirements satisfied passes | NEW (D6) | D6: full validation |
| 68 | Validator does not mutate config | NEW (D6) | D6: read-only validation |

### Security Tests (7 tests — from DGLab source)

| # | Test | Source | Requirement |
|---|---|---|---|
| 69 | Env key with dot does not escape namespace | DGLab source | Namespace safety |
| 70 | Override key cannot traverse up | DGLab source | D7: InvalidConfigurationException |
| 71 | Override key cannot be empty | DGLab source | D7: InvalidConfigurationException |
| 72 | Get with empty key throws | DGLab source | D7: InvalidConfigurationException |
| 73 | Config values not executed as eval | DGLab source | Code injection |
| 74 | Newlines in env values preserved | DGLab source | Value safety |
| 75 | Empty env value skipped not null | DGLab source | Value handling |

### Performance Tests (4 tests — from DGLab source)

| # | Test | Source | Requirement |
|---|---|---|---|
| 76 | Nested key resolution under 10µs | DGLab source | Performance target |
| 77 | Has check under 10µs | DGLab source | Performance target |
| 78 | Miss lookup under 10µs | DGLab source | Performance target |
| 79 | Builder build time acceptable | DGLab source | Performance target |

### TOML Parser Tests (15 tests — all NEW per B1)

| # | Test | Source | Requirement |
|---|---|---|---|
| 80 | Parse basic key-value pairs | NEW (B1) | TOML basics |
| 81 | Parse nested tables | NEW (B1) | [parent.child] |
| 82 | Parse array of tables | NEW (B1) | [[items]] |
| 83 | Parse all integer formats (decimal, hex, octal, binary, underscores) | NEW (B1) | TOML integers |
| 84 | Parse floats (standard, exponent, inf, nan) | NEW (B1) | TOML floats |
| 85 | Parse booleans | NEW (B1) | true/false |
| 86 | Parse basic and literal strings | NEW (B1) | "..." and '...' |
| 87 | Parse multi-line basic strings | NEW (B1) | """...""" |
| 88 | Parse multi-line literal strings | NEW (B1) | '''...''' |
| 89 | Parse string escape sequences | NEW (B1) | \n \t \r \\ \" \b \f \uXXXX |
| 90 | Parse datetime variants (offset, local, date, time) | NEW (B1) | TOML datetime |
| 91 | Parse arrays (multi-line, trailing comma) | NEW (B1) | TOML arrays |
| 92 | Parse inline tables | NEW (B1) | {key = value} |
| 93 | Parse dotted and quoted keys | NEW (B1) | a.b.c and "key name" |
| 94 | Reject malformed TOML with line number | NEW (B1) | Error diagnostics |

### ConfigLoader Facade Tests (5 tests — all NEW per B3)

| # | Test | Source | Requirement |
|---|---|---|---|
| 95 | Load with TOML files + .env + overrides produces ConfigRepository | NEW (B3) | D1+B3: facade lifecycle |
| 96 | Load propagates TOML parse errors as ConfigurationFileException | NEW (B3) | B3: failure atomicity |
| 97 | Load propagates environment detection errors as InvalidEnvironmentException | NEW (B3) | B3+D5: failure atomicity |
| 98 | Load propagates validation errors | NEW (B3) | B3+D6: failure atomicity |
| 99 | Load with empty config produces empty repository with default environment error | NEW (B3) | B3+D5: absent APP_ENV |

**Total: 99 acceptance tests** (54 from DGLab source, 45 new SStack requirements)

---

## 17. Remaining Open Questions

### Non-blocking (can resolve during implementation)

| # | Question | Context |
|---|---|---|
| N1 | UndefinedInterpolationException retained as distinct or merged into InvalidConfigurationException? | D7 lists 5 exceptions; UndefinedInterpolationException not among them. Recommend merge into InvalidConfigurationException. |
| N2 | Environment enum helper methods (displayErrors, enableOpcache)? | D5 specifies enum but not methods. Blueprint had them. Recommend defer — add when consumers need them. |
| N3 | SECRET_PATTERN updates for SStack-specific keys? | Source pattern is comprehensive. Can extend during implementation if needed. |
| N4 | TOML parser internal architecture (recursive descent vs table-driven)? | Implementation detail; not architectural. Implementer's choice. |

### Blocking items

**None.** All 3 previously blocking items (B1, B2, B3) are now resolved by owner decisions.

---

## 18. Completion Criteria (per OD-007)

1. **Verified requirements:** This specification (v3) with source evidence and traceability
2. **Agreed scope:** Zero external dependencies; zero Core-internal dependencies; PHP 8.4 + ext-json only
3. **Complete implementation:** All classes fully implemented — no stubs, no placeholders, no empty methods
4. **Tests:** 99 acceptance tests pass (54 source-derived, 45 new SStack)
5. **Static analysis:** PHPStan level max with bleedingEdge.neon — zero errors
6. **Documentation:** Public contracts documented (ConfigInterface, ConfigBuilderInterface, EnvLoaderInterface, ConfigLoader, ConfigValidator, Environment enum, exception hierarchy)
7. **Completion review:** Implementation checked against DGLab source, blueprint, approved architecture, and this specification

**A component is NOT complete if its tests pass but its implementation diverges from this approved specification without documented owner approval.**

---

## 19. Evidence Traceability Summary

| Requirement | DGLab Source | DGLab Tests | Blueprint | Owner Decision | New SStack |
|---|---|---|---|---|---|
| Dot-notation get() | YES | YES (23 tests) | YES | D4 | — |
| getOrFail() | YES | YES | — | D4 | — |
| has() | YES | YES | YES | D4 | — |
| all() with redaction | YES | YES | YES | D4 | — |
| allRaw() | YES | YES | — | D4 | — |
| getEnvironment() | NO | NO | YES (different) | D5 | YES |
| Environment enum | NO | NO | YES (different) | D5 | YES |
| ConfigValidator | NO | NO | YES (sketched) | D6+B2 | YES |
| TOML parser | NO | NO | NO | D3+B1 | YES |
| ConfigLoader facade | NO | NO | NO (monolithic) | D1+B3 | YES |
| ConfigBuilder (immutable) | YES | YES | YES | D1 (preserved) | — |
| EnvLoader (${VAR} interp) | YES | YES (17 tests) | NO | D1 (preserved) | — |
| Secret redaction | YES | YES | YES | Preserved | — |
| Exception hierarchy | PARTIAL | PARTIAL | PARTIAL | D7 | YES (5 classes) |
| Merge precedence | YES | YES | Different | D2 (source retained) | — |
| Immutability | YES | YES | YES | Preserved | — |
| Null vs absent | YES | YES | Different | Preserved (source) | — |
| Performance < 10µs | YES | YES (4 tests) | YES | Preserved | — |

---

*End of CORE-10 Reconstruction Specification v3. All blocking items resolved. Submitted for owner approval. Implementation NOT authorized.*
