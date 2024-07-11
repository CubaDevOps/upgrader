<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Application;

use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotDownloadableException;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotInstallableException;
use CubaDevOps\Upgrader\Domain\ValueObjects\Release;

class ArtifactHandler
{
    protected Release $release;
    protected \ZipArchive $zip_handler;

    public function __construct(Release $release)
    {
        $this->release = $release;
        $this->zip_handler = new \ZipArchive();
    }

    /**
     * @param string|null $dest_path The path where the artifact will be saved before installing
     *
     * @throws ArtifactNotDownloadableException
     */
    public function download(string $dest_path): bool
    {
        $artifact_resource = $this->getArtifactResource($this->release->getArtifactUrl());

        try {
            return false !== file_put_contents($dest_path, $artifact_resource);
        } catch (\Exception $e) {
            throw new ArtifactNotDownloadableException('The artifact could not be saved to '.$dest_path, $e->getCode(), $e);
        }
    }

    /**
     * @return bool|string
     *
     * @throws ArtifactNotDownloadableException
     */
    protected function getArtifactResource(string $artifact_url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $artifact_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Handle redirects
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: CubaDevOps-Upgrader',
        ]);

        $output = curl_exec($ch);

        if (curl_errno($ch) || 200 !== curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            curl_close($ch);
            throw new ArtifactNotDownloadableException('The artifact could not be downloaded');
        }

        curl_close($ch);

        return $output;
    }

    /**
     * @param array $excluded_resources List of resources to exclude from the installation (directories or files)
     *
     * @throws ArtifactNotInstallableException
     */
    public function install(string $artifact_path, string $to_directory, array $excluded_resources = []): bool
    {
        $this->assertArtifactWasDownloaded($artifact_path);
        $this->removeResourcesFromZip($artifact_path, $excluded_resources); // this method closes the zip file to persist changes
        $this->openZipFile($artifact_path); // Reopen the zip file to extract it

        try {
            return $this->zip_handler->extractTo($to_directory) && $this->zip_handler->close();
        } catch (\Exception $e) {
            throw new ArtifactNotInstallableException('The artifact could not be installed', $e->getCode(), $e);
        }
    }

    /**
     * @throws ArtifactNotInstallableException
     */
    protected function assertArtifactWasDownloaded(string $artifact_path): void
    {
        if (!file_exists($artifact_path)) {
            throw new ArtifactNotInstallableException('The artifact must be downloaded before installing');
        }
    }

    /**
     * @throws ArtifactNotInstallableException
     */
    protected function removeResourcesFromZip(string $artifact_path, array $excluded_resources): void
    {
        if (empty($excluded_resources)) {
            return;
        }
        $this->openZipFile($artifact_path);
        $deleted_files = 0;
        for ($i = 0; $i < $this->zip_handler->count(); ++$i) {
            $file_stat = $this->zip_handler->statIndex($i);
            $file_path = $file_stat['name'];

            foreach ($excluded_resources as $excluded_resource) {
                if (false !== strpos($file_path, $excluded_resource) && $this->zip_handler->deleteIndex($i)) {
                    ++$deleted_files;
                    break;
                }
            }
        }
        $this->zip_handler->close();

        if ($deleted_files < count($excluded_resources)) {
            throw new ArtifactNotInstallableException('Some resources could not be deleted from the artifact');
        }
    }

    /**
     * @throws ArtifactNotInstallableException
     */
    protected function openZipFile(string $artifact_zip): void
    {
        if (true !== ($error = $this->zip_handler->open($artifact_zip))) {
            throw new ArtifactNotInstallableException('The artifact could not be opened', $error);
        }
    }
}
