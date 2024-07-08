<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Domain;

use DateTimeImmutable;

class Release
{

    protected string $version;
    protected DateTimeImmutable $date;
    protected string $notes;
    protected bool $is_prerelease;

    protected string $artifact_url;

    public function __construct(
        string $version,
        DateTimeImmutable $release_date,
        string $notes,
        bool $is_prerelease,
        string $artifact_url
    ) {
        $this->version = $version;
        $this->date = $release_date;
        $this->notes = $notes;
        $this->is_prerelease = $is_prerelease;
        $this->assertIsValidUrl($artifact_url);
        $this->artifact_url = $artifact_url;
    }

    private function assertIsValidUrl(string $artifact_url): void
    {
        if (!filter_var($artifact_url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL');
        }
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function isPrerelease(): bool
    {
        return $this->is_prerelease;
    }

    public function getArtifactUrl(): string
    {
        return $this->artifact_url;
    }
}