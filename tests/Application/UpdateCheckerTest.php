<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Test\Application;

use CubaDevOps\Upgrader\Application\UpdateChecker;
use CubaDevOps\Upgrader\Domain\Exceptions\InvalidVersionException;
use CubaDevOps\Upgrader\Domain\Release;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateCheckerTest extends TestCase
{
    private const CURRENT_VERSION = 'v1.0.0';
    private UpdateChecker $update_checker;
    /**
     * @psalm-var Release&MockObject
     */
    private Release $release_mock;

    /**
     * @throws InvalidVersionException
     */
    public function testIsUpdateNeeded(): void
    {
        $this->release_mock->method('getVersion')->willReturn('v1.1.0');
        $is_update_needed = $this->update_checker->isUpdateNeeded($this->release_mock);
        static::assertTrue($is_update_needed);
    }

    /**
     * @throws InvalidVersionException
     */
    public function testIsMinorUpdate(): void
    {
        $this->release_mock->method('getVersion')->willReturn('v1.1.0');
        $is_minor_update = $this->update_checker->isMinorUpdate($this->release_mock);
        static::assertTrue($is_minor_update);
    }

    /**
     * @throws InvalidVersionException
     */
    public function testIsMajorUpdate(): void
    {
        $this->release_mock->method('getVersion')->willReturn('v2.0.0');
        $is_major_update = $this->update_checker->isMajorUpdate($this->release_mock);
        static::assertTrue($is_major_update);
    }

    /**
     * @throws InvalidVersionException
     */
    public function testIsPatchUpdate(): void
    {
        $this->release_mock->method('getVersion')->willReturn('v1.0.1');
        $is_patch_update = $this->update_checker->isPatchUpdate($this->release_mock);
        static::assertTrue($is_patch_update);
    }

    /**
     * @throws InvalidVersionException
     */
    public function testIsSecureToUpgrade(): void
    {
        $this->release_mock->method('getVersion')->willReturn('v1.1.1');
        $is_secure_to_upgrade = $this->update_checker->isSecureToUpgrade($this->release_mock);
        static::assertTrue($is_secure_to_upgrade);
    }

    /**
     * @throws InvalidVersionException
     */
    public function testIsNotSecureToUpgrade(): void
    {
        $this->release_mock->method('getVersion')->willReturn('v2.0.0');
        $is_secure_to_upgrade = $this->update_checker->isSecureToUpgrade($this->release_mock);
        static::assertFalse($is_secure_to_upgrade);
    }

    protected function setUp(): void
    {
        $this->update_checker = new UpdateChecker(self::CURRENT_VERSION);
        $this->release_mock = $this->createMock(Release::class);
    }
}
