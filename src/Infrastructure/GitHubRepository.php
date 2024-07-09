<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Infrastructure;

use CubaDevOps\Upgrader\Domain\Exceptions\InvalidVersionException;
use CubaDevOps\Upgrader\Domain\Interfaces\RepositoryInterface;
use CubaDevOps\Upgrader\Domain\ValueObjects\Release;
use CubaDevOps\Upgrader\Domain\ValueObjects\Version;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GitHubRepository implements RepositoryInterface
{
    protected Client $client;
    private string $code_repository;

    /**
     * @param string $code_repository The repository name in the format "owner/repository_name"
     */
    public function __construct(string $code_repository, Client $client)
    {
        $this->code_repository = $code_repository;
        $this->client = $client;
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     * @throws \Exception
     */
    public function getLatestRelease(): Release
    {
        $response = $this->client->request('GET', "/repos/$this->code_repository/releases/latest");
        $release_data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $this->parseSingleResponse($release_data);
    }

    /**
     * @psalm-param  array{'tag_name': string, 'published_at': string, 'body': string, 'prerelease': bool, 'zipball_url': string} $release_data
     *
     * @throws \Exception
     */
    private function parseSingleResponse(array $release_data): Release
    {
        return new Release(
            $release_data['tag_name'],
            new \DateTimeImmutable($release_data['published_at']),
            $release_data['body'],
            $release_data['prerelease'],
            $release_data['zipball_url']
        );
    }

    /**
     * @throws GuzzleException
     * @throws InvalidVersionException
     * @throws \JsonException
     */
    public function getRelease(Version $version): ?Release
    {
        $releases = $this->getAllReleases();
        foreach ($releases as $release) {
            if (Version::fromString($release->getVersion())->getFullSemver() === $version->getFullSemver()) {
                return $release;
            }
        }

        return null;
    }

    /**
     * @psalm-return array<Release>
     *
     * @throws GuzzleException
     * @throws \JsonException
     * @throws \Exception
     */
    public function getAllReleases(): array
    {
        $response = $this->client->request('GET', "/repos/$this->code_repository/releases");
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $releases = [];
        foreach ($data as $release_data) {
            $releases[] = $this->parseSingleResponse($release_data);
        }

        return $releases;
    }

    /**
     * @throws InvalidVersionException
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getReleaseByMajor(int $major): Release
    {
        $releases = $this->getAllReleases();
        $candidates = [];
        foreach ($releases as $release) {
            if (Version::fromString($release->getVersion())->getMajor() === $major) {
                $candidates[] = $release;
            }
        }
        // return the latest release candidate
        usort($candidates, static fn (Release $a, Release $b) => $a->getDate() <=> $b->getDate());

        return end($candidates);
    }
}
