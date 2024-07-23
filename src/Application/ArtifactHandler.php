<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Application;

use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotDownloadableException;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotInstallableException;
use CubaDevOps\Upgrader\Domain\Exceptions\DirectoryNotExistsException;

class ArtifactHandler
{
    protected \ZipArchive $zip_handler;

    public function __construct()
    {
        $this->zip_handler = new \ZipArchive();
    }

    /**
     * @param string|null $dest_path The path where the artifact will be saved before installing
     *
     * @throws ArtifactNotDownloadableException
     */
    public function download(string $artifact_url, string $dest_path): bool
    {
        $artifact_resource = $this->getArtifactResource($artifact_url);

        try {
            $this->assertDirectoryExists(dirname($dest_path));

            return false !== file_put_contents($dest_path, $artifact_resource, LOCK_EX);
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
     * @throws DirectoryNotExistsException
     */
    private function assertDirectoryExists(string $to_directory): void
    {
        if (!is_dir($to_directory) && !mkdir($to_directory) && !is_dir($to_directory)) {
            throw new DirectoryNotExistsException(sprintf('Directory "%s" was not created', $to_directory));
        }
    }

    /**
     * @param array $excluded_resources List of resources to exclude from the installation (directories or files)
     *
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     */
    public function install(string $artifact_path, string $to_directory, array $excluded_resources = [], bool $has_root_directory = true): bool
    {
        $this->assertArtifactWasDownloaded($artifact_path);
        $this->assertDirectoryExists($to_directory);
        $this->removeResourcesFromZip($artifact_path, $excluded_resources); // this method closes the zip file to persist changes
        $this->openZipFile($artifact_path); // Reopen the zip file to extract it

        try {
            return $this->extractTo($to_directory, $has_root_directory);
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
            throw new ArtifactNotInstallableException('The artifact could not be opened, check this ZipArchive code: '.$error);
        }
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     */
    protected function extractTo(string $to_directory, bool $has_root_directory = true): bool
    {
        if (!$has_root_directory) {
            return $this->zip_handler->extractTo($to_directory);
        }

        $root_directory_name = $this->zip_handler->getNameIndex(0);
        $temp_directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('zip_extract_', true);
        $this->assertDirectoryExists($temp_directory);

        if (!$this->zip_handler->extractTo($temp_directory)) {
            throw new ArtifactNotInstallableException('Could not extract the ZIP file to the temporary directory');
        }
        $this->moveExtractedFiles($temp_directory.DIRECTORY_SEPARATOR.$root_directory_name, $to_directory);

        return $this->zip_handler->close();
    }

    /**
     * @throws ArtifactNotInstallableException
     * @throws DirectoryNotExistsException
     */
    protected function moveExtractedFiles(string $from_directory, string $to_directory): void
    {
        $files = new \FilesystemIterator($from_directory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::KEY_AS_PATHNAME);

        foreach ($files as $path => $file) {
            $target = $to_directory.DIRECTORY_SEPARATOR.$file->getFilename();
            if ($file->isDir()) {
                $this->assertDirectoryExists($target);
                continue;
            }

            if (!copy($path, $target)) {
                throw new ArtifactNotInstallableException(sprintf('Could not copy file "%s" to "%s"', $file, $target));
            }
        }
    }
}
