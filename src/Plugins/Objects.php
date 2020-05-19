<?php

namespace Wtto\AliOSS\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class Objects extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'objects';
    }

    public function handle($path, $page_size, $next_marker)
    {
        if ($page_size === null) {
            return $this->filesystem->listContents($path);
        }
        return $this->filesystem->getAdapter()->listAllObjectsByPage($path, $page_size, $next_marker);
    }
}
