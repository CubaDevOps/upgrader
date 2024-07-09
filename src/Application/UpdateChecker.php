<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Application;

use CubaDevOps\Upgrader\Domain\Exceptions\InvalidVersionException;
use CubaDevOps\Upgrader\Domain\Release;
use CubaDevOps\Upgrader\Domain\ValueObjects\Version;

class UpdateChecker
{
    protected Version $current_version;

    /**
     * @throws InvalidVersionException
     */
    public function __construct(string $current_version)
    {
        $this->current_version = Version::fromString($current_version);
    }

    /**
     * @throws InvalidVersionException
     */
    public function isUpdateNeeded(Release $release): bool
    {
        return Version::fromString($release->getVersion())->gt($this->current_version);
    }

    /**
     * @throws InvalidVersionException
     */
    public function isSecureToUpgrade(Release $release): bool
    {
        return $this->current_version->getMajor() === Version::fromString($release->getVersion())->getMajor();
    }

    /**
     * @throws InvalidVersionException
     */
    public function isMajorUpdate(Release $release): bool
    {
        $new_version = Version::fromString($release->getVersion());

        return $new_version->getMajor() > 0 && 0 === $new_version->getMinor() && 0 === $new_version->getPatch();
    }

    /**
     * @throws InvalidVersionException
     */
    public function isMinorUpdate(Release $release): bool
    {
        $new_version = Version::fromString($release->getVersion());

        return $new_version->getMinor() > 0 && 0 === $new_version->getPatch();
    }

    /**
     * @throws InvalidVersionException
     */
    public function isPatchUpdate(Release $release): bool
    {
        $new_version = Version::fromString($release->getVersion());

        return $new_version->getPatch() > 0;
    }
}
