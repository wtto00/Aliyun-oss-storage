<?php
/*
 * @Author: wtto
 * @Date: 2020-03-06 09:58:01
 * @LastEditors: wtto
 * @LastEditTime: 2020-03-08 00:21:10
 * @FilePath: \Aliyun-oss-storage\src\Plugins\AllObjects.php
 */
namespace Wtto\AliOSS\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class AllObjects extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'allObjects';
    }

    public function handle($path)
    {
        $objects = $this->filesystem->listContents($path);
        return $objects;
    }
}
