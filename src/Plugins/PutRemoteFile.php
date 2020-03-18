<?php
/*
 * @Author: jacob
 * @Date: 2020-03-06 11:34:00
 * @LastEditors: wtto
 * @LastEditTime: 2020-03-18 23:02:18
 * @FilePath: \Aliyun-oss-storage\src\Plugins\PutRemoteFile.php
 */

namespace Wtto\AliOSS\Plugins;

use League\Flysystem\Config;
use League\Flysystem\Plugin\AbstractPlugin;

class PutRemoteFile extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'putRemoteFile';
    }

    public function handle($path, $remoteUrl, array $options = [])
    {
        $config = new Config($options);
        if (method_exists($this->filesystem, 'getConfig')) {
            $config->setFallback($this->filesystem->getConfig());
        }

        //Get file stream from remote url
        $resource = fopen($remoteUrl, 'r');

        return (bool) $this->filesystem->getAdapter()->writeStream($path, $resource, $config);
    }
}
