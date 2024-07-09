<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Domain\ValueObjects;

use CubaDevOps\Upgrader\Domain\Exceptions\InvalidVersionException;
use CubaDevOps\Upgrader\Domain\Traits\Comparable;

/**
 * Original Code on PHLAK\SemVer package
 * Modified by CubaDevOps to fit the project.
 */
class Version
{
    use Comparable;

    protected int $major;

    protected int $minor;

    protected int $patch;

    protected ?string $pre_release;

    protected ?string $build;

    /**
     * Class constructor, runs on object creation.
     *
     * @param string $version Version string
     *
     * @throws InvalidVersionException
     */
    public function __construct(string $version = '0.1.0')
    {
        $this->setVersion($version);
    }

    /**
     * Set (override) the entire version value.
     *
     * @param string $version Version string
     *
     * @return self This Version object
     *
     * @throws InvalidVersionException
     */
    public function setVersion(string $version): self
    {
        $matches = self::parse($version);

        $this->major = (int) $matches['major'];
        $this->minor = (int) $matches['minor'];
        $this->patch = (int) $matches['patch'];
        $this->pre_release = $matches['pre_release'];
        $this->build = $matches['build'];

        return $this;
    }

    /**
     * Attempt to parse an incomplete version string.
     *
     * Examples: 'v1', 'v1.2', 'v1-beta.4', 'v1.3+007'
     *
     * @param string $version Version string
     *
     * @return array Destructured version string
     *
     * @throws InvalidVersionException
     */
    public static function parse(string $version): array
    {
        $semverRegex = '/^v?(?<major>\d+)(?:\.(?<minor>\d+)(?:\.(?<patch>\d+))?)?(?:-(?<pre_release>[0-9A-Za-z-.]+))?(?:\+(?<build>[0-9A-Za-z-.]+)?)?$/';

        if (!preg_match($semverRegex, $version, $matches, PREG_UNMATCHED_AS_NULL)) {
            throw new InvalidVersionException('Invalid semantic version string provided');
        }

        return [
            'major' => $matches['major'],
            'minor' => $matches['minor'] ?? '0',
            'patch' => $matches['patch'] ?? '0',
            'pre_release' => $matches['pre_release'],
            'build' => $matches['build'],
        ];
    }

    /**
     * @throws InvalidVersionException
     */
    public static function fromString(string $version): self
    {
        return new self($version);
    }

    /**
     * Magic toString method; allows object interaction as if it were a string.
     *
     * @return string Current version string
     */
    public function __toString(): string
    {
        return $this->prefixed();
    }

    /**
     * Get the version string prefixed with a custom string.
     *
     * @param string $prefix String to prepend to the version string
     *                       (default: 'v')
     *
     * @return string Prefixed version string
     */
    public function prefixed(string $prefix = 'v'): string
    {
        return $prefix.$this->getFullSemver();
    }

    public function getFullSemver(): string
    {
        $version = implode('.', [$this->major, $this->minor, $this->patch]);

        if ($this->pre_release) {
            $version .= '-'.$this->pre_release;
        }

        if ($this->build) {
            $version .= '+'.$this->build;
        }

        return $version;
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function getPatch(): int
    {
        return $this->patch;
    }

    public function getPreRelease(): ?string
    {
        return $this->pre_release;
    }

    public function getBuild(): ?string
    {
        return $this->build;
    }
}
