<?php
/*
 * @Author: wtto
 * @Date: 2020-03-07 17:07:58
 * @LastEditors: wtto
 * @LastEditTime: 2020-03-08 00:20:19
 * @FilePath: \Aliyun-oss-storage\src\Plugins\Objects.php
 * @Description:
 */
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

    public function handle($path)
    {
        $objects = $this->filesystem->listContents($path);
        return $objects;
    }
}
