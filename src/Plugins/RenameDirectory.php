<?php

namespace Wtto\AliOSS\Plugins;

use Illuminate\Filesystem\Filesystem;
use League\Flysystem\Plugin\AbstractPlugin;

class RenameDirectory extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'renameDirectory';
    }

    public function handle($path, $newpath)
    {
        return $this->filesystem->getAdapter()->renameDirectory($path, $newpath);
    }
}
