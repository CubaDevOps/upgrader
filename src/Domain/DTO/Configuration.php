<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Domain\DTO;

use CubaDevOps\Upgrader\Domain\Exceptions\ConfigNotFoundException;

class Configuration
{
    private bool $has_root_directory;
    private string $project_dir;
    private array $excluded_resources;
    private string $project_repository;
    private string $repository_provider;

    /**
     * @param string $repository_provider   | (e.g. github, gitlab, bitbucket)
     * @param string $repository_identifier | (e.g. username/repository-name)
     * @param string $project_dir           | Absolute path to the project root dir (e.g. /var/www/html)
     * @param bool   $has_root_directory    | If the release artifact(.zip) has a root directory
     * @param array  $excluded_resources    | List of resources that should not be overwritten during installation (directories or files)
     */
    public function __construct(
        string $repository_provider,
        string $repository_identifier,
        string $project_dir,
        bool $has_root_directory,
        array $excluded_resources = []
    ) {
        $this->project_repository = $repository_identifier;
        $this->project_dir = $project_dir;
        $this->has_root_directory = $has_root_directory;
        $this->excluded_resources = $excluded_resources;
        $this->repository_provider = $repository_provider;
    }

    /**
     * @throws \JsonException
     */
    protected static function fromJson(string $json_file): self
    {
        $json = file_get_contents($json_file);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($data['repository_provider'], $data['repository_identifier'], $data['project_dir'], $data['has_root_directory'])) {
            throw new \InvalidArgumentException('Invalid configuration file');
        }

        return new self(
            $data['repository_provider'],
            $data['repository_identifier'],
            $data['project_dir'],
            $data['has_root_directory'],
            $data['excluded_resources'] ?? []
        );
    }

    /**
     * @throws ConfigNotFoundException
     * @throws \JsonException
     */
    public static function buildFromConfigFile(): self
    {
        $config_file = COMPOSER_BASE_DIR.DIRECTORY_SEPARATOR.'upgrader.json';

        if (!file_exists($config_file) && !file_exists($config_file .= '.dist')) {
            throw new ConfigNotFoundException('Config file not found and it is required to run the upgrader.');
        }

        return self::fromJson($config_file);
    }

    public function hasRootDirectory(): bool
    {
        return $this->has_root_directory;
    }

    public function getProjectDir(): string
    {
        return $this->project_dir;
    }

    public function getExcludedResources(): array
    {
        return $this->excluded_resources;
    }

    public function getProjectRepository(): string
    {
        return $this->project_repository;
    }

    public function getRepositoryProvider(): string
    {
        return $this->repository_provider;
    }
}
