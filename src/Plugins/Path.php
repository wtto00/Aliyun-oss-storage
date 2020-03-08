<?php
/*
 * @Author: wtto
 * @Date: 2020-03-08 21:55:26
 * @LastEditors: wtto
 * @LastEditTime: 2020-03-08 22:12:15
 * @FilePath: \Aliyun-oss-storage\src\Plugins\Path.php
 * @Description:
 */
namespace Wtto\AliOSS\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class Path extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'url2path';
    }

    public function handle($url)
    {
        return $this->filesystem->getAdapter()->url2path($url);
    }
}
