<?php

declare(strict_types=1);

namespace CubaDevOps\Upgrader\Test\TestDoubles\Application;

use CubaDevOps\Upgrader\Application\ArtifactHandler;
use CubaDevOps\Upgrader\Domain\Exceptions\ArtifactNotInstallableException;

class ArtifactHandlerDouble extends ArtifactHandler
{
    public int $final_zip_files_count = 0;
    public int $initial_zip_files_count = 0;

    /**
     * @throws ArtifactNotInstallableException
     */
    protected function removeResourcesFromZip(string $artifact_path, array $excluded_resources): void
    {
        $this->openZipFile($artifact_path);
        $this->initial_zip_files_count = $this->zip_handler->count();

        parent::removeResourcesFromZip($artifact_path, $excluded_resources);

        $this->openZipFile($artifact_path);
        $this->final_zip_files_count = $this->zip_handler->count();
    }
}
