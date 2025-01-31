<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Test\Application;

use CubaDevOps\Upgrader\Application\ArtifactHandler;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotDownloadableException;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotInstallableException;
use CubaDevOps\Upgrader\Domain\Exceptions\DirectoryNotExistsException;
use CubaDevOps\Upgrader\Domain\ValueObjects\Release;
use CubaDevOps\Upgrader\Test\TestDoubles\Application\ArtifactHandlerDouble;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArtifactHandlerTest extends TestCase
{
    private const DOWNLOAD_PATH = '/tmp/test.zip';
    private const ARTIFACT_URL = 'https://api.github.com/repos/CubaDevOps/upgrader/zipball/v1.0.0';
    private const INSTALLATION_PATH = '/tmp/upgrader';
    public const EXCLUDED_DIR = '.github/';
    public const EXCLUDED_FILE = 'LICENSE';
    /**
     * @var Release|(Release&object&MockObject)|(Release&MockObject)|(object&MockObject)|MockObject
     */
    private $release;

    /**
     * @throws ArtifactNotDownloadableException
     */
    public function testArtifactWasDownloaded(): void
    {
        static::assertTrue($this->artifact_handler->download(self::ARTIFACT_URL, self::DOWNLOAD_PATH));
        static::assertFileExists(self::DOWNLOAD_PATH);
    }

    /**
     * @throws ArtifactNotDownloadableException
     */
    public function testArtifactWasNotDownloadedFromInvalidUrl(): void
    {
        $this->expectException(ArtifactNotDownloadableException::class);
        $this->expectExceptionMessage('The artifact could not be downloaded');
        $this->artifact_handler->download('https://wrong-artifact.url', self::DOWNLOAD_PATH);
    }

    /**
     * @throws ArtifactNotDownloadableException
     */
    public function testArtifactWasNotSavedToInvalidPath(): void
    {
        $this->expectException(ArtifactNotDownloadableException::class);
        $this->expectExceptionMessage('The artifact could not be saved to /wrong/path.zip');
        $this->release->method('getArtifactUrl')->willReturn(self::ARTIFACT_URL);
        $this->artifact_handler->download(self::ARTIFACT_URL, '/wrong/path.zip');
    }

    /**
     * @depends testFilesWasExcludedFromUpdate
     *
     * @throws ArtifactNotInstallableException
     * @throws ArtifactNotDownloadableException
     * @throws DirectoryNotExistsException
     */
    public function testArtifactWasInstalled(): void
    {
        $this->artifact_handler->download(self::ARTIFACT_URL, self::DOWNLOAD_PATH);
        $installed = $this->artifact_handler->install(self::DOWNLOAD_PATH, self::INSTALLATION_PATH);
        static::assertTrue($installed);
        static::assertDirectoryExists(self::INSTALLATION_PATH);
    }

    /**
     * @throws DirectoryNotExistsException
     */
    public function testForbiddenInstallBeforeDownload(): void
    {
        $this->expectException(ArtifactNotInstallableException::class);
        $this->expectExceptionMessage('The artifact must be downloaded before installing');
        $this->artifact_handler->install(self::DOWNLOAD_PATH, self::INSTALLATION_PATH);
    }

    /**
     * @throws ArtifactNotDownloadableException|DirectoryNotExistsException
     */
    public function testArtifactCouldNotBeOpenToInstall(): void
    {
        $this->expectException(ArtifactNotInstallableException::class);
        $this->expectExceptionMessage('The artifact could not be opened');
        $this->artifact_handler->download(self::ARTIFACT_URL, self::DOWNLOAD_PATH);
        file_put_contents(self::DOWNLOAD_PATH, 'invalid-zip-content');
        $this->artifact_handler->install(self::DOWNLOAD_PATH, self::INSTALLATION_PATH);
    }

    /**
     * @throws ArtifactNotDownloadableException
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     */
    public function testFilesWasExcludedFromUpdate(): void
    {
        $excluded_resources = [self::EXCLUDED_DIR, self::EXCLUDED_FILE];
        $this->release->method('getArtifactUrl')->willReturn(self::ARTIFACT_URL);
        $this->artifact_handler = new ArtifactHandlerDouble(new \ZipArchive());
        $this->artifact_handler->download($this->release->getArtifactUrl(), self::DOWNLOAD_PATH);
        $installed = $this->artifact_handler->install(self::DOWNLOAD_PATH, self::INSTALLATION_PATH, $excluded_resources);
        static::assertTrue($installed);
        static::assertNotEquals($this->artifact_handler->initial_zip_files_count, $this->artifact_handler->final_zip_files_count);
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     * @throws ArtifactNotDownloadableException
     */
    public function artifactInstallSucceedsWithExcludedResources(): void
    {
        $excluded_resources = [self::EXCLUDED_DIR, self::EXCLUDED_FILE];
        $this->artifact_handler->download(self::ARTIFACT_URL, self::DOWNLOAD_PATH);
        $installed = $this->artifact_handler->install(self::DOWNLOAD_PATH, self::INSTALLATION_PATH, $excluded_resources);
        static::assertTrue($installed);
        static::assertDirectoryDoesNotExist(self::INSTALLATION_PATH.'/'.self::EXCLUDED_DIR);
        static::assertFileDoesNotExist(self::INSTALLATION_PATH.'/'.self::EXCLUDED_FILE);
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws ArtifactNotDownloadableException
     */
    public function testArtifactInstallFailsWithInvalidInstallationPath(): void
    {
        $this->expectException(DirectoryNotExistsException::class);
        $this->expectExceptionMessage('Directory "/invalid/installation/path" was not created');
        $this->artifact_handler->download(self::ARTIFACT_URL, self::DOWNLOAD_PATH);
        $this->artifact_handler->install(self::DOWNLOAD_PATH, '/invalid/installation/path');
    }

    protected function setUp(): void
    {
        $this->release = $this->createMock(Release::class);
        $this->artifact_handler = new ArtifactHandler(new \ZipArchive());
        @unlink(self::DOWNLOAD_PATH); // Ensure the file does not exist
        @unlink(self::INSTALLATION_PATH); // Ensure the dir does not exist
    }
}
