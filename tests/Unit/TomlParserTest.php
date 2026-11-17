<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SovereignStack\Core\Config\Exception\ConfigurationFileException;
use SovereignStack\Core\Config\Toml\TomlParser;

final class TomlParserTest extends TestCase
{
    private TomlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TomlParser();
    }

    // All 15 tests are NEW per B1

    public function testParseBasicKeyValuePairs(): void
    {
        $result = $this->parser->parse('key = "value"');
        self::assertSame('value', $result['key']);
    }

    public function testParseNestedTables(): void
    {
        $result = $this->parser->parse("[parent.child]\nkey = \"value\"");
        self::assertSame('value', $result['parent']['child']['key']);
    }

    public function testParseArrayOfTables(): void
    {
        $result = $this->parser->parse("[[items]]\nname = \"first\"\n[[items]]\nname = \"second\"");
        self::assertCount(2, $result['items']);
        self::assertSame('first', $result['items'][0]['name']);
        self::assertSame('second', $result['items'][1]['name']);
    }

    public function testParseAllIntegerFormats(): void
    {
        $result = $this->parser->parse("decimal = 42\nhex = 0xff\noctal = 0o17\nbinary = 0b1010\nunderscores = 1_000");
        self::assertSame(42, $result['decimal']);
        self::assertSame(255, $result['hex']);
        self::assertSame(15, $result['octal']);
        self::assertSame(10, $result['binary']);
        self::assertSame(1000, $result['underscores']);
    }

    public function testParseFloats(): void
    {
        $result = $this->parser->parse("standard = 3.14\nexponent = 5e+22\ninf_val = inf\nnan_val = nan");
        self::assertSame(3.14, $result['standard']);
        self::assertSame(5.0e22, $result['exponent']);
        self::assertSame(INF, $result['inf_val']);
        self::assertNan($result['nan_val']);
    }

    public function testParseBooleans(): void
    {
        $result = $this->parser->parse("t = true\nf = false");
        self::assertTrue($result['t']);
        self::assertFalse($result['f']);
    }

    public function testParseBasicAndLiteralStrings(): void
    {
        $result = $this->parser->parse('basic = "hello\nworld"' . "\n" . 'literal = \'hello\nworld\'');
        self::assertSame("hello\nworld", $result['basic']);
        self::assertSame('hello\nworld', $result['literal']);
    }

    public function testParseMultilineBasicStrings(): void
    {
        $toml = 'multi = """line one\nline two"""';
        $result = $this->parser->parse($toml);
        self::assertSame("line one\nline two", $result['multi']);
    }

    public function testParseMultilineLiteralStrings(): void
    {
        $toml = "multi = '''line one\nline two'''";
        $result = $this->parser->parse($toml);
        self::assertSame("line one\nline two", $result['multi']);
    }

    public function testParseStringEscapeSequences(): void
    {
        $result = $this->parser->parse('escaped = "tab\there"');
        self::assertSame("tab\there", $result['escaped']);
    }

    public function testParseDatetimeVariants(): void
    {
        $result = $this->parser->parse('dt = 1979-05-27T07:32:00Z');
        self::assertInstanceOf(\DateTimeImmutable::class, $result['dt']);
    }

    public function testParseArrays(): void
    {
        $result = $this->parser->parse("arr = [1, 2, 3]");
        self::assertSame([1, 2, 3], $result['arr']);
    }

    public function testParseInlineTables(): void
    {
        $result = $this->parser->parse('inline = {a = 1, b = "two"}');
        self::assertSame(1, $result['inline']['a']);
        self::assertSame('two', $result['inline']['b']);
    }

    public function testParseDottedAndQuotedKeys(): void
    {
        $result = $this->parser->parse('a.b.c = "dotted"' . "\n" . '"quoted key" = "value"');
        self::assertSame('dotted', $result['a']['b']['c']);
        self::assertSame('value', $result['quoted key']);
    }

    public function testRejectMalformedTomlWithLineNumber(): void
    {
        $this->expectException(ConfigurationFileException::class);
        $this->parser->parse("key = \"value\"\nbad = \n", 'test.toml');
    }
}
