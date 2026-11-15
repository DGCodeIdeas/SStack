# CORE-10 Reconstruction Specification

**Component:** Configuration & Environment Loader
**Blueprint:** `Architecture/Core/CORE-10.md`
**DGLab baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`
**Date:** 2026-11-15
**Status:** Specification for owner approval — implementation NOT authorized

---

## 1. DGLab Source Evidence

### Source files (10 PHP + 4 config/test fixtures)

| File | Lines | Kind |
|---|---:|---|
| `Core/Config/ConfigInterface.php` | 87 | Interface (frozen per SDLC-AGRD §2.1) |
| `Core/Config/ConfigBuilderInterface.php` | 51 | Interface |
| `Core/Config/ConfigRepository.php` | 142 | Final class implementing ConfigInterface |
| `Core/Config/ConfigBuilder.php` | 184 | Final class implementing ConfigBuilderInterface |
| `Core/Config/EnvLoaderInterface.php` | 32 | Interface |
| `Core/Config/EnvLoader.php` | 162 | Final class implementing EnvLoaderInterface |
| `Core/Config/Exception/InvalidConfigFileException.php` | 24 | Exception class |
| `Core/Config/Exception/InvalidEnvFileException.php` | 16 | Exception class |
| `Core/Config/Exception/MissingConfigurationException.php` | 20 | Exception class |
| `Core/Config/Exception/UndefinedInterpolationException.php` | 22 | Exception class |

### Test files (5 test classes + 4 fixtures)

| File | Lines | Test Methods | Suite |
|---|---:|---:|---|
| `tests/Unit/ConfigRepositoryTest.php` | 260 | ~20 | Unit |
| `tests/Unit/ConfigBuilderTest.php` | 209 | ~15 | Unit |
| `tests/Unit/EnvLoaderTest.php` | 250 | ~20 | Unit |
| `tests/Performance/ConfigBenchTest.php` | 155 | ~5 | Performance |
| `tests/Security/ConfigSecurityTest.php` | 113 | ~7 | Security |
| `tests/Fixtures/config/app.php` | 29 | — | Fixture |
| `tests/Fixtures/config/local.php` | 18 | — | Fixture |
| `tests/Fixtures/config/not_array.php` | 8 | — | Fixture |
| `tests/Fixtures/config/.env.test` | 20 | — | Fixture |

**Total:** 10 source files (820 lines), 5 test files (~987 lines), ~67 test methods.

### PHPStan configuration

DGLab's `Core/Config/phpstan.neon` specifies `level: max` with `bleedingEdge.neon`. SStack must match this.

---

## 2. Blueprint Requirements (Architecture/Core/CORE-10.md)

### Blueprint vs. Source: Key Differences

The blueprint and the actual DGLab implementation diverge in several areas. These are recorded per OD-008 and require owner decision before implementation.

| Area | Blueprint says | Source implements | Discrepancy |
|---|---|---|---|
| **ConfigLoader class** | Blueprint provides a complete `ConfigLoader` class with `.env` parsing, JSON loading, merge precedence (secrets: env wins; non-secrets: JSON wins), `detectEnvironment()`, `redact()` | Source does NOT implement `ConfigLoader`. Instead: `ConfigBuilder` (PHP file loading + $_ENV merge + overrides) and `EnvLoader` (separate .env parser) | **DISCREPANCY**: Blueprint's `ConfigLoader` is a single class doing everything; source splits into `ConfigBuilder` + `EnvLoader` with different merge semantics |
| **Environment enum** | Blueprint defines `Environment` enum (Dev/Staging/Production) with `displayErrors()` and `enableOpcache()` methods | Source does NOT implement `Environment` enum | **MISSING in source**: No environment detection enum exists |
| **ConfigValidator** | Blueprint defines `ConfigValidator` class with schema validation | Source does NOT implement `ConfigValidator` | **MISSING in source**: No schema validation exists |
| **ConfigKey attribute** | Blueprint defines `#[ConfigKey]` attribute for CORE-17 compiler pass | Source does NOT implement `ConfigKey` attribute | **MISSING in source**: Deferred to CORE-17 |
| **ConfigNotFoundException** | Blueprint defines this exception | Source uses `MissingConfigurationException` instead | **NAME MISMATCH**: Different exception class name |
| **Merge precedence** | Blueprint: secrets → env wins; non-secrets → JSON wins | Source: ConfigBuilder merges PHP files → $_ENV → overrides (overrides win last); no secret-aware merge precedence | **SEMANTIC MISMATCH**: Source has no secret-aware merge logic |
| **getEnvironment()** | Blueprint: `ConfigInterface::getEnvironment(): Environment` | Source: `ConfigInterface` has NO `getEnvironment()` method | **MISSING in source**: No environment getter on interface |
| **allRaw()** | Blueprint: `ConfigInterface::all()` returns redacted | Source: `ConfigInterface` has both `all()` (redacted) and `allRaw()` (unredacted) | **SOURCE ADDITION**: `allRaw()` is an addition not in blueprint |
| **getOrFail()** | Blueprint: `get()` throws `ConfigNotFoundException` when absent and no default | Source: `get()` returns default; separate `getOrFail()` throws `MissingConfigurationException` | **API MISMATCH**: Different throw semantics |
| **.env interpolation** | Blueprint: supports `\n`, `\t`, `\r`, `\\`, `\"`, `\$` escapes in double-quoted; backslash line continuation | Source: supports `${VAR}` interpolation in double-quoted and bare values; no escape sequences; no line continuation | **FEATURE MISMATCH**: Different .env parsing capabilities |
| **$_SERVER population** | Blueprint: writes to both $_ENV and $_SERVER | Source: writes to $_ENV only (explicitly avoids getenv() for thread safety) | **BEHAVIOURAL MISMATCH**: Source is more restrictive |
| **JSON config loading** | Blueprint: `ConfigLoader::loadJson()` | Source: `ConfigBuilder::loadFile()` loads PHP files (require), not JSON | **FORMAT MISMATCH**: Source loads PHP config files, not JSON |

### Unresolved Discrepancies (per OD-008 — NOT resolved)

1. **ConfigLoader vs ConfigBuilder+EnvLoader:** The blueprint specifies a single `ConfigLoader` class. The source implements a split architecture (`ConfigBuilder` + `EnvLoader`). Which architecture should SStack follow?
2. **Environment enum:** Blueprint specifies it; source doesn't implement it. Should SStack implement it?
3. **ConfigValidator:** Blueprint specifies schema validation; source doesn't implement it. Should SStack implement it?
4. **Merge precedence:** Blueprint has secret-aware merge (env wins for secrets, JSON wins for non-secrets). Source has simple precedence (files → $_ENV → overrides). Which merge model should SStack use?
5. **Config format:** Blueprint loads JSON. Source loads PHP files. Which format should SStack use?
6. **Exception naming:** Blueprint uses `ConfigNotFoundException`. Source uses `MissingConfigurationException`. Which name should SStack use?
7. **Interface API:** Blueprint has `getEnvironment()`. Source has `allRaw()` and `getOrFail()`. Which API should SStack expose?

---

## 3. SStack PR #1 Comparison

SStack PR #1 (`feature/stage-1-2-cli-core`) implemented a simplified Config:

| SStack PR #1 | DGLab Source | Assessment |
|---|---|---|
| `ConfigRepository` — mutable with `set()` and `merge()` | `ConfigRepository` — immutable (readonly array, no mutators) | **DEVIATION**: SStack made it mutable; DGLab is immutable |
| `ConfigInterface` — `get()`, `has()`, `all()` | `ConfigInterface` — `get()`, `getOrFail()`, `has()`, `all()`, `allRaw()` | **MISSING**: `getOrFail()`, `allRaw()` not in SStack |
| `EnvLoader` — basic .env parser | `EnvLoader` — ${VAR} interpolation, quote handling, env-wins-over-file | **SIMPLIFIED**: SStack's EnvLoader lacks interpolation and proper precedence |
| No `ConfigBuilderInterface` | `ConfigBuilderInterface` — immutable builder pattern | **MISSING**: No builder pattern in SStack |
| No `ConfigBuilder` | `ConfigBuilder` — file loading + $_ENV merge + overrides + freeze | **MISSING**: No builder in SStack |
| No exceptions | 4 exception classes | **MISSING**: No exceptions in SStack |
| No secret redaction | `SECRET_PATTERN` regex + `redact()` method | **MISSING**: No security redaction in SStack |
| No performance tests | `ConfigBenchTest` — resolution < 0.01ms | **MISSING**: No performance tests |
| No security tests | `ConfigSecurityTest` — dot-notation escape prevention | **MISSING**: No security tests |

**SStack PR #1's Config is a simplified from-scratch implementation that omits the immutability, builder pattern, secret redaction, exception types, and test depth of DGLab's actual implementation.** PR #1 must be discarded for this component.

---

## 4. Dependency Analysis

### Source imports (use statements)

**ConfigRepository.php:**
- `SovereignStack\Core\Config\Exception\MissingConfigurationException` (internal)

**ConfigBuilder.php:**
- `SovereignStack\Core\Config\Exception\InvalidConfigFileException` (internal)

**EnvLoader.php:**
- `SovereignStack\Core\Config\Exception\InvalidEnvFileException` (internal)

### External dependencies

**None.** CORE-10 has zero external dependencies:
- No PSR packages
- No third-party libraries
- No Core-internal dependencies (no imports from other Core components)
- PHP runtime only (`ext-json` is built into PHP 8.4)

This makes CORE-10 the simplest component to reconstruct — it depends on nothing except PHP itself.

### Constructor parameters

- `ConfigRepository`: `array $data` (plain array — no injected services)
- `ConfigBuilder`: no constructor parameters
- `EnvLoader`: no constructor parameters

---

## 5. Proposed Component Architecture

### Architectural Decision Required

The blueprint and source diverge significantly (see §2). Before implementation, the owner must decide which architecture to follow. The options are:

**Option A: Follow the source (ConfigBuilder + EnvLoader)**
- Pro: Source is implemented, tested (67 test methods), and proven in DGLab
- Pro: PHP file config format is simpler than JSON (can return closures, constants)
- Pro: Immutable builder pattern enforces worker-scope discipline
- Con: Lacks blueprint's secret-aware merge, environment detection, schema validation
- Con: Blueprint's `ConfigLoader` is not implemented

**Option B: Follow the blueprint (ConfigLoader)**
- Pro: Blueprint provides complete reference implementation with secret-aware merge
- Pro: Includes Environment enum, ConfigValidator, ConfigKey attribute
- Con: Blueprint's ConfigLoader is untested (no source implementation exists)
- Con: JSON config format requires ext-json (minor, but different from source)
- Con: Blueprint's merge semantics differ from source's actual behaviour

**Option C: Reconcile both**
- Take the source's proven architecture (ConfigBuilder + EnvLoader + ConfigRepository)
- Add blueprint's missing features (Environment enum, ConfigValidator, ConfigKey)
- Keep the source's PHP config file format
- Add the source's secret redaction (already in source but not in blueprint's interface)
- Pro: Preserves proven behaviour while adding missing features
- Con: Requires resolving each discrepancy individually (per OD-008)

**Not resolved. Owner must decide.**

### Class Responsibilities (regardless of option chosen)

| Class | Responsibility | Source Status |
|---|---|---|
| `ConfigInterface` | Immutable read-only config access contract | IMPLEMENTED (87 lines) |
| `ConfigRepository` | Frozen config store; O(1) dot-notation lookup; secret redaction | IMPLEMENTED (142 lines) |
| `ConfigBuilderInterface` | Immutable builder contract (loadFile, withOverride, build) | IMPLEMENTED (51 lines) |
| `ConfigBuilder` | Merges PHP files + $_ENV + overrides; freezes after build() | IMPLEMENTED (184 lines) |
| `EnvLoaderInterface` | .env file loading contract | IMPLEMENTED (32 lines) |
| `EnvLoader` | Parses .env into $_ENV; ${VAR} interpolation; env-wins-over-file | IMPLEMENTED (162 lines) |
| `InvalidConfigFileException` | Thrown for missing/non-array config files | IMPLEMENTED (24 lines) |
| `InvalidEnvFileException` | Thrown for missing/unreadable .env files | IMPLEMENTED (16 lines) |
| `MissingConfigurationException` | Thrown by getOrFail() for absent required keys | IMPLEMENTED (20 lines) |
| `UndefinedInterpolationException` | Thrown for undefined ${VAR} in strict mode | IMPLEMENTED (22 lines) |

---

## 6. Verified Configuration and Environment-Loading Behaviour

### ConfigRepository behaviour (verified from source)

1. **Construction:** Takes `array $data`; stores as `private readonly array` — immutable after construction
2. **get(key, default):** Splits key by `.`; traverses nested array; returns value or default if absent
3. **getOrFail(key):** Same traversal; throws `MissingConfigurationException::forKey($key)` if absent
4. **has(key):** Same traversal; returns bool
5. **all():** Returns recursive copy with secrets redacted (keys matching `SECRET_PATTERN` → `'***REDACTED***'`)
6. **allRaw():** Returns raw data array without redaction (trusted callers only)
7. **Key validation:** Empty keys and keys with consecutive dots (`..`) throw `\InvalidArgumentException`
8. **Secret pattern:** `/password|passwd|secret|token|authorization|cookie|private_key|api_key|secret_key|\bkey\b/i`

### ConfigBuilder behaviour (verified from source)

1. **loadFile(path):** Requires PHP file; merges returned array recursively; throws `InvalidConfigFileException` if missing or not array
2. **withOverride(key, value):** Validates key (no empty, no consecutive dots); stores override
3. **build():** Freezes builder; merges: (1) file data → (2) $_ENV (string values, underscores → dots, lowercased) → (3) explicit overrides; returns `ConfigRepository`
4. **Freeze:** After `build()`, further `loadFile()` or `withOverride()` calls throw `\LogicException`
5. **Recursive merge:** When both sides have arrays at same key, merges recursively; otherwise right wins
6. **$_ENV conversion:** `APP_URL` → `app.url` (lowercase, underscores → dots)

### EnvLoader behaviour (verified from source)

1. **load(path):** Reads .env file; throws `InvalidEnvFileException` if missing/unreadable
2. **Parsing:** Skips blank lines and `#` comments; strips `export ` prefix; requires `KEY=VALUE` format
3. **Key validation:** Keys must match `/^[A-Z_][A-Z0-9_]*$/i`
4. **Quote handling:** Single-quoted = literal (no interpolation); double-quoted = interpolation applies; bare = trimmed + interpolation
5. **${VAR} interpolation:** Resolves `${VAR_NAME}` against $_ENV; undefined vars left as literal `${VAR_NAME}`
6. **Precedence:** Existing $_ENV keys are NEVER overwritten — environment wins over .env file
7. **Thread safety:** Writes to $_ENV only; never calls `putenv()` or `getenv()`
8. **Malformed lines:** Throws `\UnexpectedValueException` for missing `=` or invalid key format

---

## 7. Behavioural Acceptance Tests

### ConfigRepository Tests

| # | Test | Expected Behaviour |
|---|---|---|
| 1 | `get('app.name')` on nested config | Returns value at path |
| 2 | `get('nonexistent')` without default | Returns null |
| 3 | `get('nonexistent', 'fallback')` | Returns 'fallback' |
| 4 | `getOrFail('missing.key')` | Throws `MissingConfigurationException` |
| 5 | `has('existing.key')` | Returns true |
| 6 | `has('missing.key')` | Returns false |
| 7 | `all()` with secrets | Secrets replaced with `'***REDACTED***'` |
| 8 | `allRaw()` | Returns unredacted data |
| 9 | `get('')` empty key | Throws `\InvalidArgumentException` |
| 10 | `get('app..name')` consecutive dots | Throws `\InvalidArgumentException` |
| 11 | Deeply nested key (5+ levels) | Resolves correctly |
| 12 | Top-level key (no dots) | Returns entire subtree |

### ConfigBuilder Tests

| # | Test | Expected Behaviour |
|---|---|---|
| 13 | loadFile with valid PHP config | Merges into builder |
| 14 | loadFile with missing file | Throws `InvalidConfigFileException::missing()` |
| 15 | loadFile with non-array return | Throws `InvalidConfigFileException::notArray()` |
| 16 | Multiple loadFile calls | Recursive merge (nested keys merge, not replace) |
| 17 | withOverride with valid key | Stores override |
| 18 | withOverride with empty key | Throws `\InvalidArgumentException` |
| 19 | withOverride with consecutive dots | Throws `\InvalidArgumentException` |
| 20 | build() merges files + $_ENV + overrides | Overrides win; $_ENV converts to dot notation |
| 21 | build() freezes builder | Subsequent loadFile/withOverride throws `\LogicException` |
| 22 | build() with $_ENV containing non-string values | Non-string values skipped |
| 23 | local.php overrides app.debug only | Other app.* keys preserved from base file |

### EnvLoader Tests

| # | Test | Expected Behaviour |
|---|---|---|
| 24 | Load valid .env file | Returns key-value pairs; writes to $_ENV |
| 25 | Load missing file | Throws `InvalidEnvFileException::missing()` |
| 26 | Comments and blank lines | Skipped |
| 27 | `export KEY=VALUE` prefix | Stripped; key loaded |
| 28 | Single-quoted value | Literal — no interpolation |
| 29 | Double-quoted with `${VAR}` | Interpolated against $_ENV |
| 30 | Bare value with `${VAR}` | Interpolated against $_ENV |
| 31 | Existing $_ENV key not overwritten | Environment wins over .env file |
| 32 | Malformed line (no =) | Throws `\UnexpectedValueException` |
| 33 | Invalid key format | Throws `\UnexpectedValueException` |
| 34 | Undefined `${VAR}` reference | Left as literal `${VAR}` |

### Security Tests

| # | Test | Expected Behaviour |
|---|---|---|
| 35 | Env key with literal dot | Does not escape config namespace |
| 36 | Env value with control characters | Stored as-is; no injection |
| 37 | `all()` never exposes secrets | All matching keys redacted |
| 38 | `allRaw()` exposes secrets | Only callable by trusted code |

### Performance Tests

| # | Test | Expected Behaviour |
|---|---|---|
| 39 | 10,000 resolutions of nested key | Mean < 0.01ms (10µs) per resolution |
| 40 | ConfigRepository construction with 50-section tree | < 1ms total |

---

## 8. Component Completion Criteria

Per OD-007, CORE-10 is complete when ALL of the following are satisfied:

1. **Verified source and blueprint requirements:** DGLab source inspected (this document); blueprint read; discrepancies recorded (§2)
2. **Agreed implementation scope and dependency contracts:** Zero external dependencies; zero Core-internal dependencies; PHP 8.4 + ext-json only
3. **Complete implementation of approved scope:** All classes fully implemented (no stubs, no empty methods, no placeholders)
4. **Meaningful unit tests:** All 34 unit tests in §7 pass; tests verify behaviour, not just class existence
5. **Integration tests:** N/A (CORE-10 has no dependencies to integrate with)
6. **Static analysis:** PHPStan level max with bleedingEdge.neon — zero errors
7. **Security tests:** All 4 security tests in §7 pass
8. **Performance tests:** Resolution benchmark < 0.01ms per lookup
9. **Documentation:** Public contracts (ConfigInterface, ConfigBuilderInterface, EnvLoaderInterface) documented with behaviour and edge cases
10. **Completion review:** Implementation checked against DGLab source, blueprint, and approved architecture (per owner safeguard)

**A component is NOT complete if its tests pass but its implementation diverges from the approved specification without documented owner approval.**

---

## 9. Open Items for Owner Decision

| # | Item | Options | Blocking? |
|---|---|---|---|
| 1 | ConfigLoader vs ConfigBuilder+EnvLoader architecture | A (source), B (blueprint), C (reconcile) | YES — blocks implementation |
| 2 | Environment enum (Dev/Staging/Production) | Implement or defer | NO — can be added later |
| 3 | ConfigValidator (schema validation) | Implement or defer | NO — can be added later |
| 4 | Merge precedence model | Source (files→$_ENV→overrides) or blueprint (secrets: env wins; non-secrets: JSON wins) | YES — affects implementation |
| 5 | Config file format | PHP files (source) or JSON (blueprint) | YES — affects implementation |
| 6 | Exception naming | `MissingConfigurationException` (source) or `ConfigNotFoundException` (blueprint) | NO — cosmetic |
| 7 | Interface API | Source's `getOrFail()`+`allRaw()` or blueprint's `getEnvironment()` | YES — affects interface contract |
| 8 | ConfigKey attribute | Implement now or defer to CORE-17 | NO — deferred by blueprint |

---

*End of CORE-10 Reconstruction Specification. Submitted for owner approval. Implementation NOT authorized.*
