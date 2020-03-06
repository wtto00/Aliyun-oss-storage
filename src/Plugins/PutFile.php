<?php
/*
 * @Author: jacob
 * @Date: 2020-03-06 11:34:00
 * @LastEditors: wtto
 * @LastEditTime: 2020-03-06 12:08:28
 * @FilePath: \Aliyun-oss-storage\src\Plugins\PutFile.php
 */

namespace Wtto\AliOSS\Plugins;

use League\Flysystem\Config;
use League\Flysystem\Plugin\AbstractPlugin;

class PutFile extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'putFile';
    }

    public function handle($path, $filePath, array $options = [])
    {
        $config = new Config($options);
        if (method_exists($this->filesystem, 'getConfig')) {
            $config->setFallback($this->filesystem->getConfig());
        }

        return (bool) $this->filesystem->getAdapter()->writeFile($path, $filePath, $config);
    }
}
