<?php

namespace CubaDevOps\Upgrader\Test\Domain;

use CubaDevOps\Upgrader\Domain\Release;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ReleaseTest extends TestCase
{
    private const ARTIFACT_URL = 'https://example.com/releases/v1.0.0.zip';
    private Release $release;

    public function testGetDate(): void
    {
        $this->assertEquals(new DateTimeImmutable('2021-01-01'), $this->release->getDate());
    }

    public function testGetNotes(): void
    {
        $this->assertEquals('Initial release', $this->release->getNotes());
    }

    public function testGetVersion(): void
    {
        $this->assertEquals('v1.0.0', $this->release->getVersion());
    }

    public function testIsPrerelease(): void
    {
        $this->assertFalse($this->release->isPrerelease());
    }

    public function testGetArtifactUrl(): void
    {
        $is_valid_url = filter_var($this->release->getArtifactUrl(), FILTER_VALIDATE_URL);
        $this->assertEquals(self::ARTIFACT_URL, $is_valid_url);
    }

    public function testArtifactUrlIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL');
        $this->release = new Release(
            'v1.0.0',
            new DateTimeImmutable('2021-01-01'),
            'Initial release',
            false,
            'invalid-url'
        );
    }

    protected function setUp(): void
    {
        $this->release = new Release(
            'v1.0.0',
            new DateTimeImmutable('2021-01-01'),
            'Initial release',
            false,
            self::ARTIFACT_URL
        );
    }
}
