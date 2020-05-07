<?php

namespace Wtto\AliOSS;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;
use Wtto\AliOSS\Plugins\AllObjects;
use Wtto\AliOSS\Plugins\CopyDir;
use Wtto\AliOSS\Plugins\Objects;
use Wtto\AliOSS\Plugins\Path;
use Wtto\AliOSS\Plugins\PutFile;
use Wtto\AliOSS\Plugins\PutRemoteFile;
use Wtto\AliOSS\Plugins\SignUrl;

class AliOssServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('oss', function ($app, $config) {
            $accessId = $config['access_id'];
            $accessKey = $config['access_key'];

            $cdnDomain = empty($config['cdnDomain']) ? '' : $config['cdnDomain'];
            $bucket = $config['bucket'];
            $ssl = empty($config['ssl']) ? false : $config['ssl'];
            $isCname = empty($config['isCName']) ? false : $config['isCName'];
            $debug = empty($config['debug']) ? false : $config['debug'];

            $endPoint = $config['endpoint']; // 默认作为外部节点
            // $epInternal = $isCname ? $cdnDomain : (empty($config['endpoint_internal']) ? $endPoint : $config['endpoint_internal']); // 内部节点
            $epInternal = $config['endpoint_internal'] ?: ($isCname ? $cdnDomain : $endPoint);

            if ($debug) {
                Log::debug('OSS config:', $config);
            }

            $client = new OssClient($accessId, $accessKey, $epInternal, $config['endpoint_internal'] ? false : $isCname);
            $adapter = new AliOssAdapter($client, $bucket, $endPoint, $ssl, $isCname, $debug, $cdnDomain);

            //Log::debug($client);
            $filesystem = new Filesystem($adapter);

            $filesystem->addPlugin(new PutFile());
            $filesystem->addPlugin(new PutRemoteFile());
            $filesystem->addPlugin(new SignUrl());
            $filesystem->addPlugin(new Objects());
            $filesystem->addPlugin(new AllObjects());
            $filesystem->addPlugin(new Path());
            $filesystem->addPlugin(new CopyDir());
            return $filesystem;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

}
