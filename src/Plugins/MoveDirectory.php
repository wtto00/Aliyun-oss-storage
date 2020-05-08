<?php

namespace Wtto\AliOSS\Plugins;

use Illuminate\Filesystem\Filesystem;
use League\Flysystem\Plugin\AbstractPlugin;

class MoveDirectory extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'moveDirectory';
    }

    public function handle($path, $newpath)
    {
        return $this->filesystem->getAdapter()->moveDirectory($path, $newpath);
    }
}
