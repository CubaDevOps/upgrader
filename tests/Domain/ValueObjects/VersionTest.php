<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Test\Domain\ValueObjects;

use CubaDevOps\Upgrader\Domain\Exceptions\InvalidVersionException;
use CubaDevOps\Upgrader\Domain\ValueObjects\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    private const SHORT_VERSION = '1.1.1';
    private const FULL_VERSION = '1.0.0-rc.1+build.1';
    private const FULL_VERSION_PREFIXED = 'v1.0.0-rc.1+build.1';

    public function testGetFullSemver(): void
    {
        self::assertEquals(self::FULL_VERSION, $this->version->getFullSemver());
    }

    public function testPrefixed(): void
    {
        $this->assertEquals(self::FULL_VERSION_PREFIXED, $this->version->prefixed());
    }

    public function testGetPatch(): void
    {
        $this->assertEquals(0, $this->version->getPatch());
    }

    /**
     * @throws InvalidVersionException
     */
    public function testParseFullVersion(): void
    {
        $this->assertEquals([
            'major' => 1,
            'minor' => 0,
            'patch' => 0,
            'pre_release' => 'rc.1',
            'build' => 'build.1',
        ], Version::parse(self::FULL_VERSION));
    }

    /**
     * @throws InvalidVersionException
     */
    public function testParseShortVersion(): void
    {
        $this->assertEquals([
            'major' => 1,
            'minor' => 1,
            'patch' => 1,
            'pre_release' => null,
            'build' => null,
        ], Version::parse(self::SHORT_VERSION));
    }

    public function testParseThrowAnExceptionOnInvalidVersionString(): void
    {
        $this->expectException(InvalidVersionException::class);
        $this->expectExceptionMessage('Invalid semantic version string provided');
        Version::parse('invalid.version');
    }

    public function testGetMinor(): void
    {
        $this->assertEquals(0, $this->version->getMinor());
    }

    public function testGetBuild(): void
    {
        $this->assertEquals('build.1', $this->version->getBuild());
    }

    /**
     * @throws InvalidVersionException
     */
    public function testFromString(): void
    {
        $this->assertEquals($this->version, Version::fromString(self::FULL_VERSION));
    }

    public function testGetPreRelease(): void
    {
        $this->assertEquals('rc.1', $this->version->getPreRelease());
    }

    /**
     * @throws InvalidVersionException
     */
    public function testSetVersion(): void
    {
        $this->assertEquals(self::SHORT_VERSION, $this->version->setVersion(self::SHORT_VERSION)->getFullSemver());
    }

    public function testGetMajor(): void
    {
        $this->assertEquals(1, $this->version->getMajor());
    }

    public function testCompareIsGreaterThan(): void
    {
        $this->assertTrue($this->version->gt(new Version('v0.1.1')));
    }

    public function testCompareIsNotGreaterThan(): void
    {
        $this->assertFalse($this->version->gt(new Version('v1.0.1')));
    }

    public function testCompareIsLessThan(): void
    {
        $this->assertTrue($this->version->lt(new Version('v1.0.1')));
    }

    public function testCompareIsEqual(): void
    {
        $this->assertTrue($this->version->eq(new Version(self::FULL_VERSION)));
    }

    public function testCompareIsNotEqual(): void
    {
        $this->assertTrue($this->version->neq(new Version('1.0.0')));
    }

    protected function setUp(): void
    {
        $this->version = new Version(self::FULL_VERSION);
    }
}
