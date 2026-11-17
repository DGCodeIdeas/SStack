<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SovereignStack\Core\Config\ConfigValidator;
use SovereignStack\Core\Config\Exception\InvalidConfigurationException;
use SovereignStack\Core\Config\Exception\MissingConfigurationException;

final class ConfigValidatorTest extends TestCase
{
    private ConfigValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ConfigValidator();
    }

    // All 8 tests are NEW per D6

    public function testValidConfigPassesValidation(): void
    {
        $data = ['app' => ['name' => 'SStack', 'env' => 'production'], 'debug' => false];
        $schema = ['required' => ['app.name' => 'string', 'app.env' => 'string']];
        $this->validator->validate($data, $schema);
        $this->addToAssertionCount(1);
    }

    public function testMissingRequiredKeyThrows(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->validator->validate([], ['required' => ['app.name' => 'string']]);
    }

    public function testTypeMismatchThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->validator->validate(
            ['app' => ['name' => 123]],
            ['required' => ['app.name' => 'string']],
        );
    }

    public function testConstraintViolationThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->validator->validate(
            ['port' => 99999],
            ['constraints' => ['port' => fn($v) => $v >= 1 && $v <= 65535]],
        );
    }

    public function testMissingRequiredSecretThrows(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->validator->validate([], ['required_secrets' => ['database.password']]);
    }

    public function testEmptyRequiredSecretThrows(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->validator->validate(
            ['password' => ''],
            ['required_secrets' => ['password']],
        );
    }

    public function testAllRequirementsSatisfiedPasses(): void
    {
        $data = [
            'app' => ['name' => 'SStack', 'env' => 'production'],
            'database' => ['password' => 'secret'],
            'port' => 3306,
        ];
        $schema = [
            'required' => ['app.name' => 'string', 'app.env' => 'string'],
            'optional' => ['port' => 'int'],
            'constraints' => ['port' => fn($v) => $v >= 1 && $v <= 65535],
            'required_secrets' => ['database.password'],
        ];
        $this->validator->validate($data, $schema);
        $this->addToAssertionCount(1);
    }

    public function testValidatorDoesNotMutateConfig(): void
    {
        $data = ['app' => ['name' => 'SStack']];
        $this->validator->validate($data, ['required' => ['app.name' => 'string']]);
        self::assertSame('SStack', $data['app']['name']);
    }
}
