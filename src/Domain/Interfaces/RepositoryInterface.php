<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Domain\Interfaces;

use CubaDevOps\Upgrader\Domain\ValueObjects\Release;
use CubaDevOps\Upgrader\Domain\ValueObjects\Version;

interface RepositoryInterface
{
    public function getLatestRelease(): Release;

    public function getAllReleases(): array;

    public function getRelease(Version $version): ?Release;

    public function getReleaseByMajor(int $major): Release;
}
