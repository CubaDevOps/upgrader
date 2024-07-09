<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Domain\Traits;

use CubaDevOps\Upgrader\Domain\ValueObjects\Version;

/**
 * Get from PHLAK\SemVer package.
 */
trait Comparable
{
    /**
     * Check if this Version object is greater than another.
     *
     * @return bool True if this Version object is greater than the comparing
     *              object, otherwise false
     */
    public function gt(Version $version): bool
    {
        return self::compare($this, $version) > 0;
    }

    /**
     * Compare two versions. Returns -1, 0 or 1 if the first version is less
     * than, equal to or greater than the second version respectively.
     */
    public static function compare(Version $version1, Version $version2): int
    {
        $v1 = [$version1->major, $version1->minor, $version1->patch];
        $v2 = [$version2->major, $version2->minor, $version2->patch];

        $baseComparison = $v1 <=> $v2;

        if (0 !== $baseComparison) {
            return $baseComparison;
        }

        if (null !== $version1->pre_release && null === $version2->pre_release) {
            return -1;
        }

        if (null === $version1->pre_release && null !== $version2->pre_release) {
            return 1;
        }

        $v1preReleaseParts = explode('.', $version1->pre_release ?? '');
        $v2preReleaseParts = explode('.', $version2->pre_release ?? '');

        $preReleases1 = array_pad($v1preReleaseParts, count($v2preReleaseParts), null);
        $preReleases2 = array_pad($v2preReleaseParts, count($v1preReleaseParts), null);

        return $preReleases1 <=> $preReleases2;
    }

    /**
     * Check if this Version object is less than another.
     *
     * @return bool True if this Version object is less than the comparing
     *              object, otherwise false
     */
    public function lt(Version $version): bool
    {
        return self::compare($this, $version) < 0;
    }

    /**
     * Check if this Version object is equal to than another.
     *
     * @return bool True if this Version object is equal to the comparing
     *              object, otherwise false
     */
    public function eq(Version $version): bool
    {
        return 0 === self::compare($this, $version);
    }

    /**
     * Check if this Version object is not equal to another.
     *
     * @return bool True if this Version object is not equal to the comparing
     *              object, otherwise false
     */
    public function neq(Version $version): bool
    {
        return 0 !== self::compare($this, $version);
    }

    /**
     * Check if this Version object is greater than or equal to another.
     *
     * @return bool True if this Version object is greater than or equal to the
     *              comparing object, otherwise false
     */
    public function gte(Version $version): bool
    {
        return self::compare($this, $version) >= 0;
    }

    /**
     * Check if this Version object is less than or equal to another.
     *
     * @return bool True if this Version object is less than or equal to the
     *              comparing object, otherwise false
     */
    public function lte(Version $version): bool
    {
        return self::compare($this, $version) <= 0;
    }
}
