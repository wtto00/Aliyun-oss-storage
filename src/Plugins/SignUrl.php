<?php
/*
 * @Author: wtto
 * @Date: 2020-03-06 09:58:01
 * @LastEditors: wtto
 * @LastEditTime: 2020-03-06 16:28:08
 * @FilePath: \Aliyun-oss-storage\src\Plugins\SignUrl.php
 */
namespace Wtto\AliOSS\Plugins;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use League\Flysystem\Plugin\AbstractPlugin;
use OSS\OssClient;

class SignUrl extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'signUrl';
    }

    public function handle($path, $expire_time = 3600)
    {
        $adapter = $this->filesystem->getAdapter();
        if (!$adapter->has($path)) {
            throw new FileNotFoundException($path . ' not found');
        }
        $client = $adapter->getClient();
        $acl = $client->getObjectAcl($adapter->getBucket(), $path);

        if ($acl == OssClient::OSS_ACL_TYPE_PUBLIC_READ) {
            return $adapter->getUrl($path);
        }

        $signedUrl = $client->signUrl($adapter->getBucket(), $path, $expire_time);

        return $adapter->replaceHttps($signedUrl);
    }
}
