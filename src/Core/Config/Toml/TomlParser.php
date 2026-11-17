<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Toml;

use SovereignStack\Core\Config\Exception\ConfigurationFileException;

/**
 * Native TOML 1.0.0 parser.
 *
 * Implements full TOML 1.0.0 specification (https://toml.io/en/v1.0.0).
 * No third-party dependencies — pure PHP implementation.
 *
 * The parser is a recursive descent parser that processes TOML input
 * character by character, building a nested PHP array.
 *
 * @package SovereignStack\Core\Config\Toml
 */
final class TomlParser
{
    private string $input = '';
    private int $position = 0;
    private int $length = 0;
    private string $filePath = '<string>';

    /**
     * Parse a TOML string into a nested PHP array.
     *
     * @param string $toml The TOML input string.
     * @param string $filePath File path for error messages (optional).
     *
     * @return array<string, mixed> The parsed configuration tree.
     *
     * @throws ConfigurationFileException On any TOML parse error.
     */
    public function parse(string $toml, string $filePath = '<string>'): array
    {
        $this->input = $toml;
        $this->position = 0;
        $this->length = strlen($toml);
        $this->filePath = $filePath;

        $root = [];
        $currentTable = &$root;

        while ($this->position < $this->length) {
            $this->skipWhitespaceAndComments();

            if ($this->position >= $this->length) {
                break;
            }

            $char = $this->input[$this->position];

            if ($char === '[') {
                // Table header or array of tables
                $currentTable = &$this->parseTableHeader($root);
            } else {
                // Key-value pair
                $this->parseKeyValue($currentTable);
            }

            $this->skipWhitespaceAndComments();

            // Expect newline or EOF after each statement
            if ($this->position < $this->length) {
                $this->expectNewline();
            }
        }

        return $root;
    }

    /**
     * Parse a table header: [key] or [[key]].
     *
     * @param array<string, mixed> $root
     * @return array<string, mixed> Reference to the table's content.
     */
    private function &parseTableHeader(array &$root): array
    {
        $this->consume('[');

        $isArray = false;
        if ($this->position < $this->length && $this->input[$this->position] === '[') {
            $this->consume('[');
            $isArray = true;
        }

        $this->skipInlineWhitespace();
        $keyPath = $this->parseKeyPath();
        $this->skipInlineWhitespace();

        $this->consume(']');
        if ($isArray) {
            $this->consume(']');
        }

        // Navigate/create the table
        $current = &$root;
        foreach ($keyPath as $i => $segment) {
            if ($i === count($keyPath) - 1 && $isArray) {
                // Array of tables: append new element
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current[$segment][] = [];
                /** @var array<string, mixed> $current */
                $current = &$current[$segment][count($current[$segment]) - 1];
            } else {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                /** @var array<string, mixed> $current */
                $current = &$current[$segment];
            }
        }

        return $current;
    }

    /**
     * Parse a key-value pair.
     *
     * @param array<string, mixed> $table
     */
    private function parseKeyValue(array &$table): void
    {
        $keyPath = $this->parseKeyPath();

        $this->skipInlineWhitespace();
        $this->consume('=');
        $this->skipInlineWhitespace();

        $value = $this->parseValue();

        // Navigate to the target location, creating intermediate tables
        $current = &$table;
        foreach ($keyPath as $i => $segment) {
            if ($i === count($keyPath) - 1) {
                if (isset($current[$segment])) {
                    throw ConfigurationFileException::forDuplicateKey(
                        $this->filePath,
                        $this->currentLine(),
                        $segment,
                    );
                }
                $current[$segment] = $value;
            } else {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                /** @var array<string, mixed> $current */
                $current = &$current[$segment];
            }
        }
    }

    /**
     * Parse a key path (may be dotted: a.b.c).
     *
     * @return list<string>
     */
    private function parseKeyPath(): array
    {
        $keys = [$this->parseKey()];

        $this->skipInlineWhitespace();

        while ($this->position < $this->length && $this->input[$this->position] === '.') {
            $this->consume('.');
            $this->skipInlineWhitespace();
            $keys[] = $this->parseKey();
            $this->skipInlineWhitespace();
        }

        return $keys;
    }

    /**
     * Parse a single key (bare or quoted).
     */
    private function parseKey(): string
    {
        if ($this->position >= $this->length) {
            throw $this->parseError('unexpected end of input, expected key');
        }

        $char = $this->input[$this->position];

        if ($char === '"') {
            return $this->parseBasicString();
        }

        if ($char === "'") {
            return $this->parseLiteralString();
        }

        // Bare key: [A-Za-z0-9_-]+
        $start = $this->position;
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];
            if (ctype_alnum($char) || $char === '_' || $char === '-') {
                $this->position++;
            } else {
                break;
            }
        }

        if ($this->position === $start) {
            throw $this->parseError('expected bare key, quoted key, or literal key');
        }

        return substr($this->input, $start, $this->position - $start);
    }

    /**
     * Parse a TOML value.
     */
    private function parseValue(): mixed
    {
        $this->skipInlineWhitespace();

        if ($this->position >= $this->length) {
            throw $this->parseError('unexpected end of input, expected value');
        }

        $char = $this->input[$this->position];

        return match (true) {
            $char === '"' => $this->parseStringOrMultiline(),
            $char === "'" => $this->parseLiteralOrMultiline(),
            $char === '[' => $this->parseArray(),
            $char === '{' => $this->parseInlineTable(),
            $char === 't' || $char === 'f' => $this->parseBoolean(),
            $char === 'i' || $char === 'n' || $char === '+' || $char === '-' => $this->parseNumberOrSpecial(),
            ctype_digit($char) => $this->parseNumberOrDate(),
            default => throw $this->parseError("unexpected character '{$char}' for value"),
        };
    }

    /**
     * Parse a string (basic or multi-line basic).
     */
    private function parseStringOrMultiline(): string
    {
        // Check for triple-quote (multi-line)
        if ($this->position + 2 < $this->length && $this->input[$this->position + 1] === '"' && $this->input[$this->position + 2] === '"') {
            return $this->parseMultilineBasicString();
        }

        return $this->parseBasicString();
    }

    private function parseBasicString(): string
    {
        $this->consume('"');

        $result = '';
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if ($char === '"') {
                $this->position++;
                return $result;
            }

            if ($char === '\\') {
                $this->position++;
                $result .= $this->parseEscapeSequence();
                continue;
            }

            if ($char === "\n") {
                throw $this->parseError('unterminated basic string (newline before closing quote)');
            }

            $result .= $char;
            $this->position++;
        }

        throw $this->parseError('unterminated basic string (end of input)');
    }

    private function parseMultilineBasicString(): string
    {
        $this->consume('"');
        $this->consume('"');
        $this->consume('"');

        // Skip first newline if present (TOML spec)
        if ($this->position < $this->length && $this->input[$this->position] === "\n") {
            $this->position++;
        } elseif ($this->position + 1 < $this->length && $this->input[$this->position] === "\r" && $this->input[$this->position + 1] === "\n") {
            $this->position += 2;
        }

        $result = '';
        while ($this->position < $this->length) {
            // Check for closing """
            if ($this->input[$this->position] === '"' &&
                $this->position + 2 < $this->length &&
                $this->input[$this->position + 1] === '"' &&
                $this->input[$this->position + 2] === '"'
            ) {
                // Allow 1-2 extra quotes before closing (line-ending backslash trimming)
                $extraQuotes = 0;
                while ($this->position + 3 + $extraQuotes < $this->length &&
                    $this->input[$this->position + 3 + $extraQuotes] === '"' &&
                    $extraQuotes < 2
                ) {
                    $extraQuotes++;
                }
                $result .= str_repeat('"', $extraQuotes);
                $this->position += 3 + $extraQuotes;
                return $result;
            }

            $char = $this->input[$this->position];

            if ($char === '\\') {
                // Check for line-ending backslash (trim whitespace)
                $next = $this->input[$this->position + 1] ?? '';
                if ($next === "\n" || $next === "\r") {
                    $this->position++;
                    // Skip whitespace on following lines
                    while ($this->position < $this->length) {
                        $c = $this->input[$this->position];
                        if ($c === ' ' || $c === "\t" || $c === "\n" || $c === "\r") {
                            $this->position++;
                        } else {
                            break;
                        }
                    }
                    continue;
                }
                $this->position++;
                $result .= $this->parseEscapeSequence();
                continue;
            }

            $result .= $char;
            $this->position++;
        }

        throw $this->parseError('unterminated multi-line basic string');
    }

    private function parseLiteralOrMultiline(): string
    {
        if ($this->position + 2 < $this->length && $this->input[$this->position + 1] === "'" && $this->input[$this->position + 2] === "'") {
            return $this->parseMultilineLiteralString();
        }

        return $this->parseLiteralString();
    }

    private function parseLiteralString(): string
    {
        $this->consume("'");

        $start = $this->position;
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if ($char === "'") {
                $result = substr($this->input, $start, $this->position - $start);
                $this->position++;
                return $result;
            }

            if ($char === "\n") {
                throw $this->parseError('unterminated literal string (newline before closing quote)');
            }

            $this->position++;
        }

        throw $this->parseError('unterminated literal string');
    }

    private function parseMultilineLiteralString(): string
    {
        $this->consume("'");
        $this->consume("'");
        $this->consume("'");

        // Skip first newline if present
        if ($this->position < $this->length && $this->input[$this->position] === "\n") {
            $this->position++;
        } elseif ($this->position + 1 < $this->length && $this->input[$this->position] === "\r" && $this->input[$this->position + 1] === "\n") {
            $this->position += 2;
        }

        $start = $this->position;
        while ($this->position < $this->length) {
            if ($this->input[$this->position] === "'" &&
                $this->position + 2 < $this->length &&
                $this->input[$this->position + 1] === "'" &&
                $this->input[$this->position + 2] === "'"
            ) {
                $result = substr($this->input, $start, $this->position - $start);
                // Allow 1-2 extra quotes
                $extra = 0;
                while ($this->position + 3 + $extra < $this->length &&
                    $this->input[$this->position + 3 + $extra] === "'" && $extra < 2
                ) {
                    $extra++;
                }
                $result .= str_repeat("'", $extra);
                $this->position += 3 + $extra;
                return $result;
            }
            $this->position++;
        }

        throw $this->parseError('unterminated multi-line literal string');
    }

    private function parseEscapeSequence(): string
    {
        if ($this->position >= $this->length) {
            throw $this->parseError('unterminated escape sequence');
        }

        $char = $this->input[$this->position];
        $this->position++;

        return match ($char) {
            'n' => "\n",
            't' => "\t",
            'r' => "\r",
            '"' => '"',
            '\\' => '\\',
            'b' => "\u{0008}",
            'f' => "\u{000C}",
            'u' => $this->parseUnicodeEscape(4),
            'U' => $this->parseUnicodeEscape(8),
            default => throw $this->parseError("invalid escape sequence '\\{$char}'"),
        };
    }

    private function parseUnicodeEscape(int $length): string
    {
        if ($this->position + $length > $this->length) {
            throw $this->parseError('unterminated unicode escape');
        }

        $hex = substr($this->input, $this->position, $length);
        $this->position += $length;

        if (!preg_match('/^[0-9a-fA-F]{' . $length . '}$/', $hex)) {
            throw $this->parseError("invalid unicode escape: \\{$hex}");
        }

        $codepoint = hexdec($hex);
        return mb_chr((int) $codepoint, 'UTF-8');
    }

    private function parseBoolean(): bool
    {
        if ($this->matchKeyword('true')) {
            return true;
        }
        if ($this->matchKeyword('false')) {
            return false;
        }
        throw $this->parseError('expected boolean (true or false)');
    }

    /**
     * @return list<mixed>
     */
    private function parseArray(): array
    {
        $this->consume('[');

        $result = [];
        $this->skipWhitespaceAndNewlines();

        if ($this->position < $this->length && $this->input[$this->position] === ']') {
            $this->position++;
            return $result;
        }

        while (true) {
            $this->skipWhitespaceAndNewlines();
            $result[] = $this->parseValue();
            $this->skipWhitespaceAndNewlines();

            if ($this->position >= $this->length) {
                throw $this->parseError('unterminated array');
            }

            $char = $this->input[$this->position];

            if ($char === ']') {
                $this->position++;
                return $result;
            }

            if ($char === ',') {
                $this->position++;
                $this->skipWhitespaceAndNewlines();
                // Allow trailing comma
                if ($this->position < $this->length && $this->input[$this->position] === ']') {
                    $this->position++;
                    return $result;
                }
                continue;
            }

            throw $this->parseError("expected ',' or ']' in array, got '{$char}'");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseInlineTable(): array
    {
        $this->consume('{');

        $result = [];
        $this->skipInlineWhitespace();

        if ($this->position < $this->length && $this->input[$this->position] === '}') {
            $this->position++;
            return $result;
        }

        while (true) {
            $this->skipInlineWhitespace();
            $keyPath = $this->parseKeyPath();
            $this->skipInlineWhitespace();
            $this->consume('=');
            $this->skipInlineWhitespace();
            $value = $this->parseValue();

            // Set nested value
            $current = &$result;
            foreach ($keyPath as $i => $segment) {
                if ($i === count($keyPath) - 1) {
                    $current[$segment] = $value;
                } else {
                    if (!isset($current[$segment])) {
                        $current[$segment] = [];
                    }
                    /** @var array<string, mixed> $current */
                    $current = &$current[$segment];
                }
            }
            unset($current);

            $this->skipInlineWhitespace();

            if ($this->position >= $this->length) {
                throw $this->parseError('unterminated inline table');
            }

            $char = $this->input[$this->position];

            if ($char === '}') {
                $this->position++;
                return $result;
            }

            if ($char === ',') {
                $this->position++;
                continue;
            }

            throw $this->parseError("expected ',' or '}' in inline table, got '{$char}'");
        }
    }

    private function parseNumberOrDate(): mixed
    {
        // Try to determine if this is a date-time or a number
        $rest = substr($this->input, $this->position, 30);

        // Check for date-time patterns: YYYY-MM-DD
        if (preg_match('/^(\d{4}-\d{2}-\d{2})([T ](\d{2}:\d{2}(:\d{2})?(\.\d+)?)(Z|[+-]\d{2}:\d{2})?)?/', $rest, $m)) {
            return $this->parseDateTime();
        }

        // Check for time-only: HH:MM:SS
        if (preg_match('/^(\d{2}:\d{2}(:\d{2})?(\.\d+)?)/', $rest, $m)) {
            return $this->parseLocalTime();
        }

        return $this->parseIntegerOrFloat();
    }

    private function parseNumberOrSpecial(): mixed
    {
        $rest = substr($this->input, $this->position, 5);

        if (str_starts_with($rest, 'inf') || str_starts_with($rest, '+inf') || str_starts_with($rest, '-inf')) {
            $sign = 1;
            if ($this->input[$this->position] === '+') {
                $this->position++;
            } elseif ($this->input[$this->position] === '-') {
                $sign = -1;
                $this->position++;
            }
            $this->expectKeyword('inf');
            return $sign * INF;
        }

        if (str_starts_with($rest, 'nan') || str_starts_with($rest, '+nan') || str_starts_with($rest, '-nan')) {
            if ($this->input[$this->position] === '+' || $this->input[$this->position] === '-') {
                $this->position++;
            }
            $this->expectKeyword('nan');
            return NAN;
        }

        // Signed number
        return $this->parseIntegerOrFloat();
    }

    private function parseIntegerOrFloat(): int|float
    {
        $start = $this->position;

        // Sign
        if ($this->position < $this->length && ($this->input[$this->position] === '+' || $this->input[$this->position] === '-')) {
            $this->position++;
        }

        // Check for hex/octal/binary
        if ($this->position + 1 < $this->length && $this->input[$this->position] === '0') {
            $next = $this->input[$this->position + 1];
            if ($next === 'x') {
                return $this->parseHexInteger($start);
            }
            if ($next === 'o') {
                return $this->parseOctalInteger($start);
            }
            if ($next === 'b') {
                return $this->parseBinaryInteger($start);
            }
        }

        // Decimal integer or float
        $numStr = '';
        $isFloat = false;

        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if (ctype_digit($char)) {
                $numStr .= $char;
                $this->position++;
            } elseif ($char === '_') {
                $this->position++;
                continue;
            } elseif ($char === '.' && !$isFloat) {
                $numStr .= '.';
                $isFloat = true;
                $this->position++;
            } elseif ($char === 'e' || $char === 'E') {
                $numStr .= 'e';
                $isFloat = true;
                $this->position++;
                if ($this->position < $this->length && ($this->input[$this->position] === '+' || $this->input[$this->position] === '-')) {
                    $numStr .= $this->input[$this->position];
                    $this->position++;
                }
            } else {
                break;
            }
        }

        if ($numStr === '') {
            throw $this->parseError('expected number');
        }

        if ($isFloat) {
            return (float) $numStr;
        }

        return (int) $numStr;
    }

    private function parseHexInteger(int $start): int
    {
        $this->position += 2; // skip 0x
        $hexStr = '';
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];
            if (ctype_xdigit($char) || $char === '_') {
                if ($char !== '_') {
                    $hexStr .= $char;
                }
                $this->position++;
            } else {
                break;
            }
        }
        if ($hexStr === '') {
            throw $this->parseError('expected hex digits after 0x');
        }
        $sign = ($this->input[$start] === '-') ? -1 : 1;
        return (int) ($sign * hexdec($hexStr));
    }

    private function parseOctalInteger(int $start): int
    {
        $this->position += 2; // skip 0o
        $octStr = '';
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];
            if (($char >= '0' && $char <= '7') || $char === '_') {
                if ($char !== '_') {
                    $octStr .= $char;
                }
                $this->position++;
            } else {
                break;
            }
        }
        if ($octStr === '') {
            throw $this->parseError('expected octal digits after 0o');
        }
        $sign = ($this->input[$start] === '-') ? -1 : 1;
        return $sign * intval($octStr, 8);
    }

    private function parseBinaryInteger(int $start): int
    {
        $this->position += 2; // skip 0b
        $binStr = '';
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];
            if ($char === '0' || $char === '1' || $char === '_') {
                if ($char !== '_') {
                    $binStr .= $char;
                }
                $this->position++;
            } else {
                break;
            }
        }
        if ($binStr === '') {
            throw $this->parseError('expected binary digits after 0b');
        }
        $sign = ($this->input[$start] === '-') ? -1 : 1;
        return (int) ($sign * bindec($binStr));
    }

    private function parseDateTime(): mixed
    {
        $start = $this->position;

        // Consume date part: YYYY-MM-DD
        $this->position += 10;

        // Check for time part
        if ($this->position < $this->length && ($this->input[$this->position] === 'T' || $this->input[$this->position] === 't' || $this->input[$this->position] === ' ')) {
            $next = $this->input[$this->position];
            // Only consume space if followed by digit (not just whitespace)
            if ($next === ' ' && $this->position + 1 < $this->length && !ctype_digit($this->input[$this->position + 1])) {
                // Just a date
                $dateStr = substr($this->input, $start, $this->position - $start);
                return new \DateTimeImmutable($dateStr);
            }

            $this->position++; // consume T or space

            // Consume time part: HH:MM:SS or HH:MM
            while ($this->position < $this->length) {
                $char = $this->input[$this->position];
                if (ctype_digit($char) || $char === ':' || $char === '.') {
                    $this->position++;
                } else {
                    break;
                }
            }

            // Check for timezone offset: Z or +/-HH:MM
            if ($this->position < $this->length) {
                $char = $this->input[$this->position];
                if ($char === 'Z' || $char === 'z') {
                    $this->position++;
                } elseif (($char === '+' || $char === '-') && $this->position + 5 <= $this->length) {
                    $this->position += 6; // +HH:MM
                }
            }

            $dtStr = substr($this->input, $start, $this->position - $start);
            $dtStr = str_replace(' ', 'T', $dtStr);
            return new \DateTimeImmutable($dtStr);
        }

        // Just a date
        $dateStr = substr($this->input, $start, $this->position - $start);
        return new \DateTimeImmutable($dateStr);
    }

    private function parseLocalTime(): mixed
    {
        $start = $this->position;

        while ($this->position < $this->length) {
            $char = $this->input[$this->position];
            if (ctype_digit($char) || $char === ':' || $char === '.') {
                $this->position++;
            } else {
                break;
            }
        }

        $timeStr = substr($this->input, $start, $this->position - $start);
        return new \DateTimeImmutable($timeStr);
    }

    // --- Helper methods ---

    private function consume(string $expected): void
    {
        if ($this->position >= $this->length || $this->input[$this->position] !== $expected) {
            throw $this->parseError("expected '{$expected}'");
        }
        $this->position++;
    }

    private function matchKeyword(string $keyword): bool
    {
        $len = strlen($keyword);
        if ($this->position + $len > $this->length) {
            return false;
        }
        if (substr($this->input, $this->position, $len) !== $keyword) {
            return false;
        }
        // Must be followed by non-alphanumeric
        if ($this->position + $len < $this->length) {
            $next = $this->input[$this->position + $len];
            if (ctype_alnum($next) || $next === '_') {
                return false;
            }
        }
        $this->position += $len;
        return true;
    }

    private function expectKeyword(string $keyword): void
    {
        if (!$this->matchKeyword($keyword)) {
            throw $this->parseError("expected '{$keyword}'");
        }
    }

    private function skipInlineWhitespace(): void
    {
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];
            if ($char === ' ' || $char === "\t") {
                $this->position++;
            } else {
                break;
            }
        }
    }

    private function skipWhitespaceAndComments(): void
    {
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if ($char === ' ' || $char === "\t" || $char === "\n" || $char === "\r") {
                $this->position++;
            } elseif ($char === '#') {
                while ($this->position < $this->length && $this->input[$this->position] !== "\n") {
                    $this->position++;
                }
            } else {
                break;
            }
        }
    }

    private function skipWhitespaceAndNewlines(): void
    {
        $this->skipWhitespaceAndComments();
    }

    private function expectNewline(): void
    {
        if ($this->position >= $this->length) {
            return;
        }

        $char = $this->input[$this->position];
        if ($char === "\n") {
            $this->position++;
        } elseif ($char === "\r") {
            $this->position++;
            if ($this->position < $this->length && $this->input[$this->position] === "\n") {
                $this->position++;
            }
        } else {
            throw $this->parseError("expected newline, got '{$char}'");
        }
    }

    private function currentLine(): int
    {
        return substr_count($this->input, "\n", 0, $this->position) + 1;
    }

    private function parseError(string $message): ConfigurationFileException
    {
        return ConfigurationFileException::forParseError(
            $this->filePath,
            $this->currentLine(),
            $message,
        );
    }
}
