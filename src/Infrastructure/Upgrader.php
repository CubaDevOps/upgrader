<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Infrastructure;

use CubaDevOps\Upgrader\Application\ArtifactHandler;
use CubaDevOps\Upgrader\Application\UpdateChecker;
use CubaDevOps\Upgrader\Domain\DTO\Configuration;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotDownloadableException;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotInstallableException;
use CubaDevOps\Upgrader\Domain\Exceptions\DirectoryNotExistsException;
use CubaDevOps\Upgrader\Domain\Exceptions\InvalidVersionException;
use CubaDevOps\Upgrader\Domain\Interfaces\RepositoryInterface;
use CubaDevOps\Upgrader\Domain\ValueObjects\Release;
use CubaDevOps\Upgrader\Domain\ValueObjects\Version;

class Upgrader
{
    private RepositoryInterface $repository;
    private UpdateChecker $checker;
    private Configuration $configuration;
    private Version $current_version;
    private ArtifactHandler $artifact_handler;

    /**
     * @throws InvalidVersionException
     */
    public function __construct(
        string $current_version,
        Configuration $configuration,
        RepositoryInterface $repository,
        ArtifactHandler $artifact_handler,
        UpdateChecker $update_checker
    ) {
        $this->configuration = $configuration;
        $this->repository = $repository;
        $this->checker = $update_checker;
        $this->current_version = Version::fromString($current_version);
        $this->artifact_handler = $artifact_handler;
    }

    /**
     * @throws InvalidVersionException
     * @throws ArtifactNotDownloadableException|ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     */
    public function upgradeTo(string $version, bool $force = false): bool
    {
        $release = $this->repository->getRelease(Version::fromString($version));
        if (null === $release || !$this->checker->isUpdateNeeded($release)) {
            return false;
        }

        if (!$force && !$this->checker->isSecureToUpgrade($release)) {
            return false;
        }

        return $this->upgrade($release);
    }

    /**
     * @throws ArtifactNotDownloadableException
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     */
    protected function upgrade(Release $release): bool
    {
        $artifact_path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$release->getVersion().'.zip';

        if (!$this->artifact_handler->download($release->getArtifactUrl(), $artifact_path)) {
            return false;
        }

        return $this->artifact_handler->install($artifact_path, $this->configuration->getProjectDir(), $this->configuration->getExcludedResources(), $this->configuration->hasRootDirectory());
    }

    /**
     * @return array<string>
     *
     * @throws InvalidVersionException
     */
    public function getUpgradeCandidates(): array
    {
        /** @var array<Release> $releases */
        $releases = $this->repository->getAllReleases();
        $candidates = [];

        foreach ($releases as $release) {
            if (Version::fromString($release->getVersion())->gt($this->current_version)) {
                $candidates[$release->getVersion()]['version'] = $release->getVersion();
                $candidates[$release->getVersion()]['is_secure'] = $this->checker->isSecureToUpgrade($release);
            }
        }

        return $candidates;
    }

    /**
     * @throws ArtifactNotDownloadableException
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     * @throws InvalidVersionException
     */
    public function upgradeSafely(): bool
    {
        $release = $this->repository->getReleaseByMajor($this->current_version->getMajor());
        if (!$this->checker->isUpdateNeeded($release)) {
            return false;
        }

        return $this->upgrade($release);
    }

    /**
     * @throws ArtifactNotDownloadableException
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     * @throws InvalidVersionException
     */
    public function upgradeToLatest(): bool
    {
        $release = $this->repository->getLatestRelease();
        if (!$this->checker->isUpdateNeeded($release)) {
            return false;
        }

        return $this->upgrade($release);
    }
}
