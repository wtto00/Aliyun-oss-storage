<?php

namespace Wtto\AliOSS\Plugins;

use Illuminate\Filesystem\Filesystem;
use League\Flysystem\Plugin\AbstractPlugin;

class CopyDirectory extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'copyDirectory';
    }

    public function handle($path, $newpath)
    {
        return $this->filesystem->getAdapter()->copyDirectory($path, $newpath);
    }
}
