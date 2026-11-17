# Dependency and Licensing Analysis

**Document type:** Deliverable 11 of 15
**Inspection date:** 2026-11-17

---

## 1. SStack Dependency Policy (Binding)

**DOCUMENT-VERIFIED from `docs/architecture/owner-decisions-2026-11.md`** (on SStack remote `docs/k-s0b-core-dependency-analysis` branch at `d042db17`):

> SStack MAY use:
> - PHP runtime and justified PHP extensions
> - PSR contract/interface packages where actually required
> - SovereignStack-native implementations
>
> SStack MUST NOT depend on third-party implementation libraries. Historical DGLab dependencies are evidence, not authorization.

The CORE-10 spec §18.2 reinforces: "Agreed scope: Zero external dependencies; zero Core-internal dependencies; PHP 8.4 + ext-json only."

---

## 2. Per-Repository Dependency Audit

### 2.1 Devium (`devium/toml`)

**SOURCE-VERIFIED from `composer.json`** at `/home/z/my-project/research/toml-1.1/repos/devium/composer.json`:

```json
{
    "name": "devium/toml",
    "license": "MIT",
    "require": {
        "php": ">=8.2",
        "ext-json": "*",
        "php-ds/php-ds": "^1.3",
        "symfony/polyfill-mbstring": "^1.0"
    },
    "require-dev": {
        "laravel/pint": "^1.0",
        "pestphp/pest": "^2.0",
        "phpstan/phpstan": "^1.0",
        "rector/rector": "^0.18",
        "symfony/var-dumper": "^6.0"
    }
}
```

**Runtime dependencies (would become SStack deps if adopted):**

| Package | Version | Type | License | Status vs SStack policy |
|---|---|---|---|---|
| `php` | >=8.2 | language | — | OK (SStack requires 8.4) |
| `ext-json` | * | bundled ext | — | OK (always present in PHP 8) |
| `php-ds/php-ds` | ^1.3 | **third-party extension polyfill** | MIT | **VIOLATION — third-party implementation library** |
| `symfony/polyfill-mbstring` | ^1.0 | polyfill | MIT | **VIOLATION — third-party implementation library** |

**Why `php-ds/php-ds` violates SStack policy:**

`php-ds/php-ds` is a polyfill for the `ext-ds` PHP extension (provides `Ds\Set`, `Ds\Vector`, `Ds\Map`, etc.). Devium uses `Ds\Set` and `Ds\Vector` in `TomlKeystore` for duplicate-key tracking. The polyfill is a userland PHP implementation of the extension's API — it is a "third-party implementation library" per the SStack policy.

Devium could in principle be refactored to use plain PHP arrays instead of `Ds\*` types, but as-shipped it is non-compliant with SStack's policy.

**Why `symfony/polyfill-mbstring` violates SStack policy:**

`symfony/polyfill-mbstring` is a userland PHP implementation of the `ext-mbstring` functions. SStack already requires `ext-mbstring` directly (per `composer.json`), so the polyfill is redundant for SStack. However, if Devium were adopted as a dependency, the polyfill would be pulled in transitively.

**License:** MIT. Compatible with SStack (SStack is Apache-2.0; MIT is permissive and compatible).

**Verdict:** Devium CANNOT be adopted as a SStack runtime dependency due to two policy violations.

### 2.2 php-collective (`php-collective/toml`)

**SOURCE-VERIFIED from `composer.json`** at `/home/z/my-project/research/toml-1.1/repos/php-collective/composer.json`:

```json
{
    "name": "php-collective/toml",
    "license": "MIT",
    "require": {
        "php": ">=8.2",
        "symfony/polyfill-mbstring": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "phpstan/phpstan": "^1.0",
        "php-collective/code-sniffer": "^1.0"
    }
}
```

**Runtime dependencies:**

| Package | Version | Type | License | Status vs SStack policy |
|---|---|---|---|---|
| `php` | >=8.2 | language | — | OK |
| `symfony/polyfill-mbstring` | ^1.0 | polyfill | MIT | **VIOLATION — third-party implementation library** |

**Notable:** php-collective's `symfony/polyfill-mbstring` dependency is the ONLY runtime dep. The README mentions `ext-ds` as "suggested" but source inspection (`php-collective-analysis.md`) confirms it is NOT used in `src/`.

**License:** MIT. Compatible with SStack.

**Verdict:** php-collective CANNOT be adopted as a SStack runtime dependency due to one policy violation (the mbstring polyfill). However, the violation is more cosmetic than Devium's — SStack already requires `ext-mbstring` directly, so the polyfill would be redundant. If php-collective dropped the polyfill dependency (which it could, since `ext-mbstring` is bundled with most PHP installs), it would be policy-compliant.

### 2.3 PetalBranch (`petalbranch/toml`)

**SOURCE-VERIFIED from `composer.json`** at `/home/z/my-project/research/toml-1.1/repos/petalbranch/composer.json`:

```json
{
    "name": "petalbranch/toml",
    "license": "Apache-2.0",
    "require": {
        "php": "^8.3",
        "ext-json": "*",
        "ext-mbstring": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.0"
    }
}
```

**Runtime dependencies:**

| Package | Version | Type | License | Status vs SStack policy |
|---|---|---|---|---|
| `php` | ^8.3 | language | — | OK |
| `ext-json` | * | bundled ext | — | OK |
| `ext-mbstring` | * | bundled ext | — | OK |

**Notable:** PetalBranch is the ONLY one of the three implementations with **zero third-party runtime dependencies**. It depends only on bundled PHP extensions.

**License:** Apache-2.0. Compatible with SStack (same license).

**Verdict:** PetalBranch is **policy-compliant** in terms of dependencies. However, other concerns (no CI, 8 months inactive, false "zero-copy" claim, datetime round-trip bug) make it unsuitable as a SStack dependency.

### 2.4 toml-lang/toml (spec repo)

Not a PHP implementation. No composer.json. License: MIT (per `LICENSE` file). The spec and ABNF are documents, not code — they can be cited as references without licensing concerns.

### 2.5 toml-lang/toml-test (conformance suite)

Written in Go. License: MIT (per `LICENSE` file in the repo). The test fixtures (TOML input files and expected JSON outputs) are MIT-licensed and CAN be vendored into SStack's test suite with attribution (see `toml-conformance-analysis.md` §6.2).

---

## 3. Transitive Dependency Analysis

For each implementation, the transitive dependency tree was not exhaustively walked (would require `composer install` + reading `composer.lock`). However, the runtime deps are small enough that the transitive trees are bounded:

| Repo | Direct runtime deps | Likely transitive deps | Total dep count (estimate) |
|---|---|---|---|
| Devium | `php-ds/php-ds ^1.3`, `symfony/polyfill-mbstring ^1.0` | Both are leaf packages with no further deps | 2 |
| php-collective | `symfony/polyfill-mbstring ^1.0` | Leaf package | 1 |
| PetalBranch | (none beyond PHP + extensions) | (none) | 0 |

The polyfill packages are leaf packages (no transitive deps). The risk of supply-chain attacks via transitive deps is therefore low for all three.

---

## 4. Optional Extensions

| Repo | Extensions used | Bundled with PHP? |
|---|---|---|
| Devium | `ext-json` (required), `ext-ds` (optional via polyfill) | `ext-json` yes; `ext-ds` NO (requires PECL install) |
| php-collective | `ext-mbstring` (via polyfill) | yes |
| PetalBranch | `ext-json`, `ext-mbstring` | both yes |

`ext-ds` is the only non-bundled extension required by any implementation. Devium's use of `ext-ds` (via polyfill when the extension is absent) is its main policy violation.

---

## 5. Licensing Compatibility Matrix

| Repo | License | Compatible with SStack (Apache-2.0)? |
|---|---|---|
| Devium | MIT | Yes — MIT is permissive and compatible with Apache-2.0 |
| php-collective | MIT | Yes |
| PetalBranch | Apache-2.0 | Yes — same license |
| toml-lang/toml (spec) | MIT | Yes (citation only, not adoption) |
| toml-lang/toml-test | MIT | Yes (test fixtures can be vendored with attribution) |

All five repositories use licenses compatible with SStack. No licensing blocker exists for borrowing architectural ideas (with attribution) or vendoring test fixtures.

---

## 6. Attribution Requirements

If SStack borrows architectural ideas (not code) from any of these repos:

- **MIT-licensed repos (Devium, php-collective, toml-lang/*):** MIT license requires preserving the copyright notice and license text if code is copied. For architectural ideas (no code copying), attribution in the SStack documentation (e.g. "Parser architecture informed by php-collective's `ParseError` struct pattern") is best practice but not legally required.

- **Apache-2.0 repo (PetalBranch):** Apache-2.0 requires preserving the copyright notice and license text if code is copied, plus a NOTICE file if one exists. For architectural ideas (no code copying), attribution in SStack documentation is best practice.

**Recommendation:** Add an "Attributions" section to the SStack TOML parser's class-level docblock crediting the repos whose ideas informed the design:

```php
/**
 * TOML 1.1.0 native parser for SovereignStack.
 *
 * Architecture informed by:
 * - php-collective/toml — ParseError struct + error code enum pattern
 * - PetalBranch/toml — verified \e and \xHH escape implementations
 * - Devium/toml — confirmed DateTimeImmutable naive usage for local datetimes
 *
 * No code copied from these repositories. SStack implementation is original
 * work subject to the Apache-2.0 license.
 */
```

For the vendored toml-test fixtures (if adopted per `toml-conformance-analysis.md` §6.2):

- Include a `tests/Toml/Conformance/LICENSE-ATTRIBUTION.md` file that:
  - Credits the toml-test project.
  - Reproduces the MIT license text.
  - Records the toml-test version/commit pinned for the fixture set.

---

## 7. Conclusion

| Repo | Runtime deps | License | SStack policy compliant? | Adoptable as SStack dep? |
|---|---|---|---|---|
| Devium | 2 third-party | MIT | NO (`php-ds`, polyfill) | NO |
| php-collective | 1 third-party (polyfill) | MIT | NO (polyfill) | NO |
| PetalBranch | 0 third-party | Apache-2.0 | YES | NO (other concerns — no CI, inactive, bugs) |

**None of the three PHP implementations should be adopted as a SStack runtime dependency.**

- Devium and php-collective fail on dependency policy.
- PetalBranch passes dependency policy but fails on other grounds (no CI, inactivity, false claims, datetime bug).

**The SStack TOML parser must remain a native implementation.** Architectural ideas may be borrowed (with attribution) from any of the three repos. Test fixtures from the MIT-licensed toml-test suite may be vendored (with attribution).

---

## 8. Cross-References

- Native architecture proposal: `sovereignstack-native-parser-architecture.md`
- Conformance suite vendoring strategy: `toml-conformance-analysis.md` §6
- Per-implementation details: `devium-analysis.md`, `php-collective-analysis.md`, `petalbranch-analysis.md`
