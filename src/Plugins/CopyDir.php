<?php

namespace Wtto\AliOSS\Plugins;

use Illuminate\Filesystem\Filesystem;
use League\Flysystem\Plugin\AbstractPlugin;

class CopyDir extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'copyDir';
    }

    public function handle($path, $newpath)
    {
        return $this->filesystem->getAdapter()->copyDirectory($path, $newpath);
    }
}
