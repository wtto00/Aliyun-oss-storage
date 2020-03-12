# Aliyun-oss-storage for Laravel 5+

[English Document](./readme_en.md)

本仓库 Fork 自 [jacobcyl/Aliyun-oss-storage](https://github.com/jacobcyl/Aliyun-oss-storage)
由于作者长时间不更新，所以自己稍微修改下，重新发布。

Aliyun oss filesystem storage adapter for laravel 5. You can use Aliyun OSS just like laravel Storage as usual.  
借鉴了一些优秀的代码，综合各方，同时做了更多优化，将会添加更多完善的接口和插件，打造 Laravel 最好的 OSS Storage 扩展

## 借鉴

- [thephpleague/flysystem-aws-s3-v2](https://github.com/thephpleague/flysystem-aws-s3-v2)
- [apollopy/flysystem-aliyun-oss](https://github.com/apollopy/flysystem-aliyun-oss)

## 依赖

- Laravel 5+
- `cURL` PHP 扩展

## 安装

1. 首先运行下面命令来给你的`Laravel`项目安装依赖：

   ```bash
   composer require wtto/ali-oss-storage
   ```

2. 然后你需要在配置文件`config/app.php`中，添加下边的代码来引用依赖：

   ```php
   Wtto\AliOSS\AliOssServiceProvider::class,
   ```

## 配置

1.  添加下边的代码到配置文件`app/filesystems.php`中：

    ```php
    'disks'=>[
        ...
        'oss' => [
            'driver'            => 'oss',
            'access_id'         => env('ALIOSS_KEYID', null), //Your Aliyun OSS AccessKeyId
            'access_key'        => env('ALIOSS_KEYSECRET', null), //Your Aliyun OSS AccessKeySecret
            'bucket'            => env('ALIOSS_BUCKETNAME', null), //OSS bucket name
            'endpoint'          => env('ALIOSS_ENDPOINT', null), //<the endpoint of OSS, E.g: oss-cn-hangzhou.aliyuncs.com | custom domain, E.g:img.abc.com> OSS 外网节点或自定义外部域名
            'endpoint_internal' => env('ALIOSS_ENDPOINT_INTERNAL', null), //<internal endpoint [OSS内网节点] 如：oss-cn-shenzhen-internal.aliyuncs.com> v2.0.4 新增配置属性，如果为空，则默认使用 endpoint 配置(由于内网上传有点小问题未解决，请大家暂时不要使用内网节点上传，正在与阿里技术沟通中)
            'cdnDomain'         => env('ALIOSS_DOMAIN', null), //<CDN domain, cdn域名> 如果isCName为true, getUrl会判断cdnDomain是否设定来决定返回的url，如果cdnDomain未设置，则使用endpoint来生成url，否则使用cdn
            'ssl'               => env('ALIOSS_SSL', false), // true to use 'https://' and false to use 'http://'. default is false,
            'isCName'           => env('ALIOSS_CNAME', false), // 是否使用自定义域名,true: 则Storage.url()会使用自定义的cdn或域名生成文件url， false: 则使用外部节点生成url
            'debug'             => env('ALIOSS_DEBUG', true),
        ],
        ...
    ]
    ```

2.  然后在文件 `.env` 中配置你的相关信息：

    ```php
    FILESYSTEM_DRIVER=oss

    ALIOSS_KEYID=<Your Aliyun OSS AccessKeyId>
    ALIOSS_KEYSECRET=<Your Aliyun OSS AccessKeySecret>
    ALIOSS_BUCKETNAME=<OSS bucket name>
    ALIOSS_ENDPOINT=<the endpoint of OSS, E.g: oss-cn-hangzhou.aliyuncs.com | custom domain, E.g:img.abc.com>
    ALIOSS_ENDPOINT_INTERNAL=<internal endpoint [OSS内网节点] 如：oss-cn-shenzhen-internal.aliyuncs.com>
    ALIOSS_DOMAIN=<CDN domain, cdn域名>
    ALIOSS_SSL=<true|false>
    ALIOSS_CNAME=<true|false>
    ALIOSS_DEBUG=<true|false>
    ```

    > **注意：** 如果你的服务器和你的 OSS Bucket 不在同一个区域的话，请务必不要配置 ALIOSS_ENDPOINT_INTERNAL。
    >
    > 如果配置了 ALIOSS_ENDPOINT_INTERNAL，那么传输将使用阿里云内网，无论是否配置 ALIOSS_CNAME 和 ALIOSS_DOMAIN。
    >
    > **传输优先级：** ALIOSS_ENDPOINT_INTERNAL > ALIOSS_CNAME=true && ALIOSS_DOMAIN > ALIOSS_ENDPOINT。

    好了，这些你就配置完成了。现在你可以像`Laravel Storage`一样使用 `Aliyun OSS` 了。

## 使用

查看 [Larave doc for Storage](https://laravel.com/docs/5.5/filesystem#custom-filesystems)， 或者你可以按照下边的方式使用：

1. 首先在文件开始位置引用`Laravel Storage`

   ```php
   use Illuminate\Support\Facades\Storage;
   ```

2. 然后你就可以使用`Laravel Storage`的`API`了

   ```php
   // 如果你设置的默认文件驱动时oss的话，这一步可以跳过
   Storage::disk('oss');

   // 获取目录下所有的文件
   Storage::files($directory);
   // 递归获取目录下所有的文件
   Storage::allFiles($directory);

   // 上传文件 第一个参数是储存位置，第二个参数是文件内容
   Storage::put('path/to/file/file.jpg', $contents);
   // 上传指定本地文件到指定位置
   Storage::putFile('path/to/file/file.jpg', 'local/path/to/local_file.jpg');

   // 获得指定文件的内容
   Storage::get('path/to/file/file.jpg');
   // 判断指定对象是否存在
   Storage::exists('path/to/file/file.jpg');
   // 获得指定对象的大小
   Storage::size('path/to/file/file.jpg');、
   // 获得指定对象的最后修改时间
   Storage::lastModified('path/to/file/file.jpg');

   // 获得指定位置下的所有目录
   Storage::directories($directory);
   // 递归获得指定位置下的所有目录
   Storage::allDirectories($directory);

   // 复制指定对象到指定位置
   Storage::copy('old/file1.jpg', 'new/file1.jpg');
   // 移动指定对象到指定位置
   Storage::move('old/file1.jpg', 'new/file1.jpg');
   // 重命名指定对象
   Storage::rename('path/to/file1.jpg', 'path/to/file2.jpg');

   // 向指定文件中追加前置内容
   Storage::prepend('file.log', 'Prepended Text');
   // 向指定文件中追加内容
   Storage::append('file.log', 'Appended Text');

   // 删除指定对象
   Storage::delete('file.jpg');
   // 删除多个指定对象
   Storage::delete(['file1.jpg', 'file2.jpg']);

   // 创建指定目录
   Storage::makeDirectory($directory);
   // 删除指定目录 目录中的所有文件同样会被删除 请慎重使用
   Storage::deleteDirectory($directory);

   // 更新新添加函数
   // v2.0 添加插件：
   // 上传远程文件到指定位置
   Storage::putRemoteFile('target/path/to/file/jacob.jpg', 'http://example.com/jacob.jpg');
   // v2.0.1 添加插件：
   // 获得指定文件的网址
   Storage::url('path/to/img.jpg'); // get the file url
   // v2.1.1 添加插件：
   // 获得私有 Bucket 的指定文件的临时公开网址 默认临时公开失效是 3600 秒
   Storage::signUrl('path/to/img.jpg',$timeout);
   // v2.3.0 添加插件：
   // 获得指定位置的所有对象 包括目录以及文件 文件会放回相关属性
   Storage::objects($directory);
   // 递归获得指定位置的所有对象 包括目录以及文件 文件会放回相关属性
   Storage::allObjects($directory);
   // v2.3.1 添加插件：
   // 通过url获得文件所在位置
   Storage::url2path($url);
   ```

## 文档

更多开发文档请查看 [Aliyun OSS DOC](https://help.aliyun.com/document_detail/32099.html?spm=5176.doc31981.6.335.eqQ9dM)

## 许可

源代码是根据 MIT 许可证发布的。有关详细信息，请阅读许可证文件。
