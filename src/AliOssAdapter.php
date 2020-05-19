<?php

namespace Wtto\AliOSS;

use Dingo\Api\Contract\Transformer\Adapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\Util;
use Log;
use OSS\Core\OssException;
use OSS\OssClient;

class AliOssAdapter extends AbstractAdapter
{
    /**
     * @var Log debug Mode true|false
     */
    protected $debug;
    /**
     * @var array
     */
    protected static $resultMap = [
        'Body' => 'raw_contents',
        'Content-Length' => 'size',
        'ContentType' => 'mimetype',
        'Size' => 'size',
        'StorageClass' => 'storage_class',
    ];

    /**
     * @var array
     */
    protected static $metaOptions = [
        'CacheControl',
        'Expires',
        'ServerSideEncryption',
        'Metadata',
        'ACL',
        'ContentType',
        'ContentDisposition',
        'ContentLanguage',
        'ContentEncoding',
    ];

    protected static $metaMap = [
        'CacheControl' => 'Cache-Control',
        'Expires' => 'Expires',
        'ServerSideEncryption' => 'x-oss-server-side-encryption',
        'Metadata' => 'x-oss-metadata-directive',
        'ACL' => 'x-oss-object-acl',
        'ContentType' => 'Content-Type',
        'ContentDisposition' => 'Content-Disposition',
        'ContentLanguage' => 'response-content-language',
        'ContentEncoding' => 'Content-Encoding',
    ];

    //Aliyun OSS Client OssClient
    protected $client;
    //bucket name
    protected $bucket;

    protected $endPoint;

    protected $cdnDomain;

    protected $ssl;

    protected $isCname;

    //配置
    protected $options = [
        'Multipart' => 128,
    ];

    /**
     * AliOssAdapter constructor.
     *
     * @param OssClient $client
     * @param string    $bucket
     * @param string    $endPoint
     * @param bool      $ssl
     * @param bool      $isCname
     * @param bool      $debug
     * @param null      $prefix
     * @param array     $options
     */
    public function __construct(
        OssClient $client,
        $bucket,
        $endPoint,
        $ssl,
        $isCname = false,
        $debug = false,
        $cdnDomain,
        $prefix = null,
        array $options = []
    ) {
        $this->debug = $debug;
        $this->client = $client;
        $this->bucket = $bucket;
        $this->setPathPrefix($prefix);
        $this->endPoint = $endPoint;
        $this->ssl = $ssl;
        $this->isCname = $isCname;
        $this->cdnDomain = $cdnDomain;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get the OssClient bucket.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get the OSSClient instance.
     *
     * @return OssClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get tttps url from http if ssl is true
     * @param string $url
     *
     * @return string $url
     */
    public function replaceHttps($url)
    {
        if ($this->ssl) {
            return str_replace_first('http://', 'https://', $url);
        }

        return $url;
    }

    /**
     * 目录路径后缀补全 /
     *
     * @param string $path
     *
     * @return string
     */
    public function applyDirPath($path)
    {
        return rtrim($this->applyPathPrefix($path), '/') . '/';
    }

    /**
     * 获取路径的目录
     *
     * @param string $path
     *
     * @return string
     */
    public function getDirName($path)
    {
        $path = rtrim($path, '/');
        return substr($path, 0, strripos($path, '/'));
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $this->createDir($this->getDirName($object), $config);
        $options = $this->getOptions($this->options, $config);

        if (!isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = Util::contentSize($contents);
        }
        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, $contents);
        }

        try {
            $this->client->putObject($this->bucket, $object, $contents, $options);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }
        return $this->normalizeResponse($options, $path);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        $options = $this->getOptions($this->options, $config);
        $contents = stream_get_contents($resource);

        return $this->write($path, $contents, $config);
    }

    public function writeFile($path, $filePath, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $options = $this->getOptions($this->options, $config);

        $options[OssClient::OSS_CHECK_MD5] = true;

        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, '');
        }
        try {
            $this->client->uploadFile($this->bucket, $object, $filePath, $options);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }
        return $this->normalizeResponse($options, $path);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        if (!$config->has('visibility') && !$config->has('ACL')) {
            $config->set(static::$metaMap['ACL'], $this->getObjectACL($path));
        }
        // $this->delete($path);
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        $contents = stream_get_contents($resource);
        return $this->update($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        if (!$this->copy($path, $newpath)) {
            return false;
        }

        return $this->delete($path);
    }

    /**
     * 重命名目录
     */
    public function renameDirectory($path, $newpath)
    {
        $path = $this->applyDirPath($path);
        $newpath = $this->applyDirPath($newpath);

        if (!$this->copyDirectory($path, $newpath)) {
            return false;
        }

        return $this->deleteDir($path);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $object = $this->applyPathPrefix($path);
        $newObject = $this->applyPathPrefix($newpath);
        try {
            $this->client->copyObject($this->bucket, $object, $this->bucket, $newObject);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return true;
    }

    /**
     * Copy a directory from one location to another.
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  int     $options
     * @return bool
     */
    public function copyDirectory($directory, $destination, $options = [])
    {
        $directory = $this->applyDirPath($directory);

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        if (!$this->has($destination)) {
            $config = new Config($options);
            $bool = $this->createDir($destination, $config);
            if (!$bool) {
                return false;
            }
        }

        $items = $this->listDirObjects($directory);

        foreach ($items['objects'] as $item) {
            $filename = substr($item['Key'], strlen($directory));
            $bool = $this->copy($item['Key'], $destination . $filename);
            if (!$bool) {
                return false;
            }
        }
        foreach ($items['prefix'] as $item) {
            $dirname = substr($item['Prefix'], strlen($directory));
            $bool = $this->copyDirectory($item['Prefix'], $destination . $dirname);
            if (!$bool) {
                return false;
            }
        }

        return true;
    }

    /**
     * Move a directory from one location to another.
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  int     $options
     * @return bool
     */
    public function moveDirectory($directory, $destination, $options = [])
    {
        if (!$this->copyDirectory($directory, $destination, $options)) {
            return false;
        }
        return $this->deleteDir($directory);
    }

    /**
     * 分页查询所有文件及目录
     */
    public function listAllObjectsByPage($directory, $page_size, $next_marker)
    {
        $dirname = $this->applyPathPrefix($this->applyDirPath($directory));
        $delimiter = '/';
        $maxkeys = $page_size;

        $result = [
            'objects' => [],
            'prefix' => [],
            'nextMarker' => '',
        ];

        $options = [
            'delimiter' => $delimiter,
            'prefix' => $dirname,
            'max-keys' => $maxkeys,
            'marker' => $next_marker,
        ];

        try {
            $listObjectInfo = $this->client->listObjects($this->bucket, $options);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            // return false;
            throw $e;
        }

        $nextMarker = $listObjectInfo->getNextMarker(); // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
        $objectList = $listObjectInfo->getObjectList(); // 文件列表
        $prefixList = $listObjectInfo->getPrefixList(); // 目录列表

        if (!empty($objectList)) {
            foreach ($objectList as $objectInfo) {
                if ($objectInfo->getKey() == $dirname) {
                    // 目录本身不要列出
                    continue;
                }
                $object['Prefix'] = $dirname;
                $object['Key'] = $objectInfo->getKey();
                $object['LastModified'] = $objectInfo->getLastModified();
                $object['eTag'] = $objectInfo->getETag();
                $object['Type'] = $objectInfo->getType();
                $object['Size'] = $objectInfo->getSize();
                $object['StorageClass'] = $objectInfo->getStorageClass();

                $result['objects'][] = $object;
            }
        }

        if (!empty($prefixList)) {
            foreach ($prefixList as $prefixInfo) {
                $result['prefix'][] = [
                    'Prefix' => $prefixInfo->getPrefix(),
                ];
            }
        }

        $contents = array_merge($result['prefix'], $result["objects"]);
        $data = array_map([$this, 'normalizeResponse'], $contents);
        $data = array_filter($data, function ($value) {
            return $value['path'] !== false;
        });

        return [
            'list' => $data,
            'nextMarker' => $nextMarker,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $bucket = $this->bucket;
        $object = $this->applyPathPrefix($path);

        try {
            $this->client->deleteObject($bucket, $object);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return !$this->has($path);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $dirname = $this->applyDirPath($dirname);
        $dirObjects = $this->listDirObjects($dirname, true);

        if (count($dirObjects['objects']) > 0) {
            foreach ($dirObjects['objects'] as $object) {
                $objects[] = $object['Key'];
            }

            try {
                $this->client->deleteObjects($this->bucket, $objects);
            } catch (OssException $e) {
                $this->logErr(__FUNCTION__, $e);
                return false;
            }

        }

        // 添加目录之中的目录，也是要删除的
        if (count($dirObjects['prefix']) > 0) {
            foreach ($dirObjects['prefix'] as $dir) {
                $dirs[] = $dir['Prefix'];
            }
            try {
                $this->client->deleteObjects($this->bucket, $dirs);
            } catch (OssException $e) {
                $this->logErr(__FUNCTION__, $e);
                return false;
            }
        }

        try {
            $this->client->deleteObject($this->bucket, $dirname);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return true;
    }

    /**
     * 列举文件夹内文件列表；可递归获取子文件夹；
     * @param string $dirname 目录
     * @param bool $recursive 是否递归
     * @return mixed
     * @throws OssException
     */
    public function listDirObjects($dirname = '', $recursive = false)
    {
        $dirname = $this->applyPathPrefix($this->applyDirPath($dirname));
        $delimiter = '/';
        $nextMarker = '';
        $maxkeys = 1000;

        //存储结果
        $result = [
            'objects' => [],
            'prefix' => [],
        ];

        while (true) {
            $options = [
                'delimiter' => $delimiter,
                'prefix' => $dirname,
                'max-keys' => $maxkeys,
                'marker' => $nextMarker,
            ];

            try {
                $listObjectInfo = $this->client->listObjects($this->bucket, $options);
            } catch (OssException $e) {
                $this->logErr(__FUNCTION__, $e);
                // return false;
                throw $e;
            }

            $nextMarker = $listObjectInfo->getNextMarker(); // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
            $objectList = $listObjectInfo->getObjectList(); // 文件列表
            $prefixList = $listObjectInfo->getPrefixList(); // 目录列表

            if (!empty($objectList)) {
                foreach ($objectList as $objectInfo) {
                    if ($objectInfo->getKey() == $dirname) {
                        // 目录本身不要列出
                        continue;
                    }
                    $object['Prefix'] = $dirname;
                    $object['Key'] = $objectInfo->getKey();
                    $object['LastModified'] = $objectInfo->getLastModified();
                    $object['eTag'] = $objectInfo->getETag();
                    $object['Type'] = $objectInfo->getType();
                    $object['Size'] = $objectInfo->getSize();
                    $object['StorageClass'] = $objectInfo->getStorageClass();

                    $result['objects'][] = $object;
                }
            }

            if (!empty($prefixList)) {
                foreach ($prefixList as $prefixInfo) {
                    $result['prefix'][] = [
                        'Prefix' => $prefixInfo->getPrefix(),
                    ];
                }
            }

            //递归查询子目录所有文件
            if ($recursive) {
                foreach ($result['prefix'] as $pfix) {
                    $next = $this->listDirObjects($pfix['Prefix'], $recursive);
                    $result["objects"] = array_merge($result['objects'], $next["objects"]);
                    $result["prefix"] = array_merge($next["prefix"], $result['prefix']);
                }
            }

            //没有更多结果了
            if ($nextMarker === '') {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $object = $this->applyPathPrefix($dirname);
        // 如果多层目录一起创建 则获取目录属性时，会报错
        $objectArr = explode('/', $object);
        $options = $this->getOptionsFromConfig($config);

        try {
            $path = '';
            $separator = '';
            foreach ($objectArr as $dir) {
                $path .= $separator . $dir;
                if (!empty($dir)) {
                    $this->client->createObjectDir($this->bucket, $path, $options);
                }
                $separator = '/';
            }
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        $object = $this->applyPathPrefix($path);
        $acl = ($visibility === AdapterInterface::VISIBILITY_PUBLIC) ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;

        $this->client->putObjectAcl($this->bucket, $object, $acl);

        return compact('visibility');
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        if (empty($path)) {
            return false;
        }
        $object = $this->applyPathPrefix($path);

        return $this->client->doesObjectExist($this->bucket, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $result = $this->readObject($path);
        $result['contents'] = (string) $result['raw_contents'];
        unset($result['raw_contents']);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $result = $this->readObject($path);
        $result['stream'] = $result['raw_contents'];
        rewind($result['stream']);
        // Ensure the EntityBody object destruction doesn't close the stream
        $result['raw_contents']->detachStream();
        unset($result['raw_contents']);

        return $result;
    }

    /**
     * Read an object from the OssClient.
     *
     * @param string $path
     *
     * @return array
     */
    protected function readObject($path)
    {
        $object = $this->applyPathPrefix($path);

        $result['Body'] = $this->client->getObject($this->bucket, $object);
        $result = array_merge($result, ['type' => 'file']);
        return $this->normalizeResponse($result, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $dirObjects = $this->listDirObjects($directory, $recursive);
        $contents = array_merge($dirObjects['prefix'], $dirObjects["objects"]);

        $result = array_map([$this, 'normalizeResponse'], $contents);
        $result = array_filter($result, function ($value) {
            return $value['path'] !== false;
        });

        return Util::emulateDirectories($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $objectMeta = $this->client->getObjectMeta($this->bucket, $object);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            try {
                // as a directory, if exist?
                $objectMeta = $this->client->getObjectMeta($this->bucket, $object . '/');
            } catch (OssException $e) {
                $this->logErr(__FUNCTION__, $e);
                return false;
            }
        }

        return $objectMeta;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        $object = $this->getMetadata($path);
        $object['size'] = $object['content-length'];
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        if ($object = $this->getMetadata($path)) {
            $object['mimetype'] = $object['content-type'];
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        if ($object = $this->getMetadata($path)) {
            $object['timestamp'] = strtotime($object['last-modified']);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        $object = $this->applyPathPrefix($path);
        try {
            $acl = $this->client->getObjectAcl($this->bucket, $object);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        if ($acl == OssClient::OSS_ACL_TYPE_PUBLIC_READ) {
            $res['visibility'] = AdapterInterface::VISIBILITY_PUBLIC;
        } else {
            $res['visibility'] = AdapterInterface::VISIBILITY_PRIVATE;
        }

        return $res;
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function getUrl($path)
    {
        if (!$this->has($path)) {
            return $this->fileNotFound($path);
        }

        return $this->replaceHttps('http://' .
            ($this->isCname ? ($this->cdnDomain == '' ? $this->endPoint : $this->cdnDomain) : $this->bucket . '.' . $this->endPoint) . '/' . ltrim($path, '/'));

    }

    /**
     * 通过url获得path
     *
     * @param string $url
     *
     * @return string
     */
    public function url2path($url)
    {
        if (empty($url)) {
            return $url;
        }
        $host = $this->isCname ? ($this->cdnDomain == '' ? $this->endPoint : $this->cdnDomain) : $this->bucket . '.' . $this->endPoint;
        $index = strpos($url, $host);
        if ($index === false) {
            return $url;
        }
        return substr($url, $index + strlen($host) + 1);
    }

    /**
     * The the ACL visibility.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getObjectACL($path)
    {
        $metadata = $this->getVisibility($path);

        return $metadata['visibility'] === AdapterInterface::VISIBILITY_PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
    }

    /**
     * Normalize a result from OSS.
     *
     * @param array  $object
     * @param string $path
     *
     * @return array file metadata
     */
    protected function normalizeResponse(array $object, $path = null)
    {
        $result = ['path' => $path ?: $this->removePathPrefix(isset($object['Key']) ? $object['Key'] : $object['Prefix'])];
        $result['dirname'] = Util::dirname($result['path']);
        $result['name'] = rtrim(substr($result['path'], (strlen($result['dirname']) ?: -1) + 1), '\\/');

        if (isset($object['LastModified'])) {
            $result['timestamp'] = strtotime($object['LastModified']);
        }

        if (substr($result['path'], -1) === '/') {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');

            return $result;
        }

        $result = array_merge($result, Util::map($object, static::$resultMap), ['type' => 'file']);

        return $result;
    }

    /**
     * Get options for a OSS call. done
     *
     * @param array  $options
     *
     * @return array OSS options
     */
    protected function getOptions(array $options = [], Config $config = null)
    {
        $options = array_merge($this->options, $options);

        if ($config) {
            $options = array_merge($options, $this->getOptionsFromConfig($config));
        }

        return array(OssClient::OSS_HEADERS => $options);
    }

    /**
     * Retrieve options from a Config instance. done
     *
     * @param Config $config
     *
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = [];

        foreach (static::$metaOptions as $option) {
            if (!$config->has($option)) {
                continue;
            }
            $options[static::$metaMap[$option]] = $config->get($option);
        }

        if ($visibility = $config->get('visibility')) {
            // For local reference
            // $options['visibility'] = $visibility;
            // For external reference
            $options['x-oss-object-acl'] = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
        }

        if ($mimetype = $config->get('mimetype')) {
            // For local reference
            // $options['mimetype'] = $mimetype;
            // For external reference
            $options['Content-Type'] = $mimetype;
        }

        return $options;
    }

    /**
     * @param $fun string function name : __FUNCTION__
     * @param $e
     */
    protected function logErr($fun, $e)
    {
        if ($this->debug) {
            Log::error($fun . ": FAILED");
            Log::error($e->getMessage());
        }
    }

    /**
     * File not found, Log error
     * don't throw new Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @param string $path
     * @return void
     */
    public function fileNotFound($path)
    {
        Log::error('aliyun-oss:' . $path . ' not found');
        return $this->replaceHttps('http://' .
            ($this->isCname ? ($this->cdnDomain == '' ? $this->endPoint : $this->cdnDomain) : $this->bucket . '.' . $this->endPoint) . '/' . ltrim($path, '/'));
    }
}
