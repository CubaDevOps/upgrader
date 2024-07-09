<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Test\Infrastructure;

use CubaDevOps\Upgrader\Domain\Exceptions\InvalidVersionException;
use CubaDevOps\Upgrader\Domain\ValueObjects\Version;
use CubaDevOps\Upgrader\Infrastructure\GitHubRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class GitHubRepositoryTest extends TestCase
{
    private const LAST_RELEASE = '{"tag_name": "v2.0.3", "published_at": "2021-01-01T00:00:00Z", "body": "Release notes", "prerelease": false, "zipball_url": "https://example.com/v2.0.3.zip"}';
    private const ALL_RELEASES = '[
        {"tag_name": "v1.0.0", "published_at": "2021-01-01T00:00:00Z", "body": "Release notes", "prerelease": false, "zipball_url": "https://example.com/v1.0.0.zip"},
        {"tag_name": "v1.1.0", "published_at": "2021-01-01T00:00:00Z", "body": "Release notes", "prerelease": false, "zipball_url": "https://example.com/v1.1.0.zip"},
        {"tag_name": "v2.0.3", "published_at": "2021-01-01T00:00:00Z", "body": "Release notes", "prerelease": false, "zipball_url": "https://example.com/v2.0.3.zip"}
    ]';
    private const CODE_REPOSITORY = 'cubadevops/upgrader';
    private const CURRENT_MAJOR = 1;

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetAllReleases(): void
    {
        $this->setupAllReleasesRequestClient();

        $releases = $this->githubRepository->getAllReleases();

        $this->assertCount(3, $releases);
    }

    private function setupAllReleasesRequestClient(): void
    {
        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn(
                $this->createConfiguredMock(
                    StreamInterface::class,
                    ['getContents' => self::ALL_RELEASES]
                )
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', '/repos/'.self::CODE_REPOSITORY.'/releases')
            ->willReturn($response);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetLatestRelease(): void
    {
        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn(
                $this->createConfiguredMock(
                    StreamInterface::class,
                    ['getContents' => self::LAST_RELEASE]
                )
            );

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', '/repos/'.self::CODE_REPOSITORY.'/releases/latest')
            ->willReturn($response);

        $release = $this->githubRepository->getLatestRelease();

        $this->assertSame('v2.0.3', $release->getVersion());
    }

    /**
     * @throws InvalidVersionException
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetRelease(): void
    {
        $this->setupAllReleasesRequestClient();

        $release = $this->githubRepository->getRelease(new Version('2.0.3'));

        $this->assertSame('v2.0.3', $release->getVersion());
    }

    /**
     * @throws InvalidVersionException
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetReleaseNotFound(): void
    {
        $this->setupAllReleasesRequestClient();

        $release = $this->githubRepository->getRelease(new Version('3.0.0'));

        $this->assertNull($release);
    }

    /**
     * @throws InvalidVersionException
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetReleaseByMajor(): void
    {
        $this->setupAllReleasesRequestClient();

        $release = $this->githubRepository->getReleaseByMajor(self::CURRENT_MAJOR);

        $this->assertSame('v1.1.0', $release->getVersion());
    }

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->githubRepository = new GitHubRepository(self::CODE_REPOSITORY, $this->client);
    }
}
