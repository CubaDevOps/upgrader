<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Test\Infrastructure;

use CubaDevOps\Upgrader\Application\ArtifactHandler;
use CubaDevOps\Upgrader\Application\UpdateChecker;
use CubaDevOps\Upgrader\Domain\DTO\Configuration;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotDownloadableException;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotInstallableException;
use CubaDevOps\Upgrader\Domain\Exceptions\DirectoryNotExistsException;
use CubaDevOps\Upgrader\Domain\Exceptions\InvalidVersionException;
use CubaDevOps\Upgrader\Domain\Interfaces\RepositoryInterface;
use CubaDevOps\Upgrader\Domain\ValueObjects\Release;
use CubaDevOps\Upgrader\Infrastructure\Upgrader;
use PHPUnit\Framework\TestCase;

class UpgraderTest extends TestCase
{
    private const CURRENT_VERSION = '1.0.0';
    private Upgrader $upgrader;
    private ArtifactHandler $artifact_handler;
    private UpdateChecker $update_checker;

    /**
     * @throws ArtifactNotInstallableException
     * @throws InvalidVersionException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeToLatest(): void
    {
        $release_mock = $this->createMock(Release::class);
        $release_mock->method('getVersion')->willReturn('v2.0.0');
        $release_mock->method('getArtifactUrl')->willReturn('https://test.com/releases/2.0.0.zip');
        $this->repository->method('getLatestRelease')->willReturn($release_mock);
        $this->update_checker->method('isUpdateNeeded')->willReturn(true);
        $this->artifact_handler->method('download')->willReturn(true);
        $this->artifact_handler->method('install')->willReturn(true);
        self::assertTrue($this->upgrader->upgradeToLatest());
    }

    /**
     * @throws InvalidVersionException
     */
    public function testGetUpgradeCandidates(): void
    {
        $this->update_checker->method('isSecureToUpgrade')->willReturnOnConsecutiveCalls(true, false);
        $candidates = $this->upgrader->getUpgradeCandidates();
        self::assertCount(2, $candidates);
        self::assertArrayHasKey('1.1.0', $candidates);
        self::assertArrayHasKey('2.0.0', $candidates);
        self::assertTrue((bool) $candidates['1.1.0']['is_secure']);
        self::assertFalse((bool) $candidates['2.0.0']['is_secure']);
    }

    /**
     * @throws InvalidVersionException
     * @throws ArtifactNotInstallableException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeSafely(): void
    {
        $release_mock = $this->createMock(Release::class);
        $release_mock->method('getVersion')->willReturn('v1.1.0');
        $this->repository->method('getReleaseByMajor')->willReturn($release_mock);
        $this->update_checker->method('isUpdateNeeded')->willReturn(true);
        $this->artifact_handler->method('download')->willReturn(true);
        $this->artifact_handler->method('install')->willReturn(true);
        self::assertTrue($this->upgrader->upgradeSafely());
    }

    /**
     * @throws InvalidVersionException
     * @throws ArtifactNotInstallableException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testForceUpgradeToVersion(): void
    {
        $release_mock = $this->createMock(Release::class);
        $release_mock->method('getVersion')->willReturn('v2.0.0');
        $this->repository->method('getRelease')->willReturn($release_mock);
        $this->update_checker->method('isUpdateNeeded')->willReturn(true);
        $this->update_checker->method('isSecureToUpgrade')->willReturn(false);
        $this->artifact_handler->method('download')->willReturn(true);
        $this->artifact_handler->method('install')->willReturn(true);
        self::assertTrue($this->upgrader->upgradeTo('v2.0.0', true));
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws InvalidVersionException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testNotUpgradeToInsecure(): void
    {
        self::assertFalse($this->upgrader->upgradeTo('2.0.0'));
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws InvalidVersionException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeToLatestWithNoUpdatesNeeded(): void
    {
        $this->update_checker->method('isUpdateNeeded')->willReturn(false);
        self::assertFalse($this->upgrader->upgradeToLatest());
    }

    /**
     * @throws InvalidVersionException
     * @throws ArtifactNotInstallableException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeToLatestWithUpdateFailsToDownload(): void
    {
        $this->update_checker->method('isUpdateNeeded')->willReturn(true);
        $this->artifact_handler->method('download')->willReturn(false);
        self::assertFalse($this->upgrader->upgradeToLatest());
    }

    /**
     * @throws InvalidVersionException
     * @throws ArtifactNotInstallableException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeToLatestWithUpdateFailsToInstall(): void
    {
        $this->update_checker->method('isUpdateNeeded')->willReturn(true);
        $this->artifact_handler->method('download')->willReturn(true);
        $this->artifact_handler->method('install')->willReturn(false);
        self::assertFalse($this->upgrader->upgradeToLatest());
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws InvalidVersionException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeSafelyWithNoUpdateNeeded(): void
    {
        $this->update_checker->method('isUpdateNeeded')->willReturn(false);
        self::assertFalse($this->upgrader->upgradeSafely());
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     * @throws ArtifactNotDownloadableException
     */
    public function testUpgradeToWithInvalidVersion(): void
    {
        $this->expectException(InvalidVersionException::class);
        $this->upgrader->upgradeTo('invalid.version');
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws InvalidVersionException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeToWithDownloadFails(): void
    {
        $this->update_checker->method('isUpdateNeeded')->willReturn(true);
        $this->artifact_handler->method('download')->willReturn(false);
        self::assertFalse($this->upgrader->upgradeTo('v2.0.0'));
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws InvalidVersionException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeToWithInstallFails(): void
    {
        $this->update_checker->method('isUpdateNeeded')->willReturn(true);
        $this->artifact_handler->method('download')->willReturn(true);
        $this->artifact_handler->method('install')->willReturn(false);
        self::assertFalse($this->upgrader->upgradeTo('v2.0.0'));
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws InvalidVersionException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testUpgradeToNonExistentVersionReturnsFalseWithoutException(): void
    {
        $this->repository->method('getRelease')->willReturn(null);
        self::assertFalse($this->upgrader->upgradeTo('v1.2.3'));
    }

    /**
     * @throws InvalidVersionException
     * @throws ArtifactNotInstallableException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testNotUpgradeNeededReturnsFalseWhenCurrentVersionIsLatest(): void
    {
        $this->update_checker->method('isUpdateNeeded')->willReturn(false);
        self::assertFalse($this->upgrader->upgradeTo(self::CURRENT_VERSION));
    }

    /**
     * @throws InvalidVersionException
     */
    protected function setUp(): void
    {
        $this->configuration = new Configuration(
            'github',
            'username/repository-name',
            '/tmp/upgrader/code',
            true,
            ['config', 'public']
        );
        $this->repository = $this->createMock(RepositoryInterface::class);
        $this->repository->method('getAllReleases')->willReturn(
            [
                new Release(
                    '1.0.0',
                    new \DateTimeImmutable(),
                    'Release notes',
                    false,
                    'https://test.com/releases/1.0.0.zip'
                ),
                new Release(
                    '1.1.0',
                    new \DateTimeImmutable(),
                    'Release notes',
                    false,
                    'https://test.com/releases/1.1.0.zip'
                ),
                new Release(
                    '2.0.0',
                    new \DateTimeImmutable(),
                    'Release notes',
                    false,
                    'https://test.com/releases/2.0.0.zip'
                ),
            ]
        );
        $this->artifact_handler = $this->createMock(ArtifactHandler::class);
        $this->update_checker = $this->createMock(UpdateChecker::class);
        $this->upgrader = new Upgrader(
            self::CURRENT_VERSION,
            $this->configuration,
            $this->repository,
            $this->artifact_handler,
            $this->update_checker
        );
    }
}
