# Aliyun-oss-storage for Laravel 5+

[简体中文](./readme.md) | English

This repository fork from [jacobcyl/Aliyun-oss-storage](https://github.com/jacobcyl/Aliyun-oss-storage).
Because the author didn't update for a long time, I modified it a little and republished it.

Aliyun oss filesystem storage adapter for laravel 5. You can use Aliyun OSS just like laravel Storage as usual.

## Inspired By

- [thephpleague/flysystem-aws-s3-v2](https://github.com/thephpleague/flysystem-aws-s3-v2)
- [apollopy/flysystem-aliyun-oss](https://github.com/apollopy/flysystem-aliyun-oss)

## Require

- Laravel 5+
- cURL extension

## Installation

In order to install AliOSS-storage, you can simply run below command:

```bash
composer require wtto/ali-oss-storage
```

Then in your `config/app.php` add this line to providers array:

```php
Wtto\AliOSS\AliOssServiceProvider::class,
```

## Configuration

Add the following in `app/filesystems.php`:

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

Then set the default driver and oss config in `.env`:

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

> **Notice:** If your server and your OSS bucket are not in the same region, please do not configure _ALIOSS_ENDPOINT_INTERNAL_.
>
> If _ALIOSS_ENDPOINT_INTERNAL_ is configured, Alibaba cloud intranet will be used for transmission, regardless of whether _ALIOSS_CNAME_ and _ALIOSS_DOMAIN_ are configured.
>
> **Transmission priority:** _ALIOSS_ENDPOINT_INTERNAL_ > _ALIOSS_CNAME_=true && _ALIOSS_DOMAIN_ > _ALIOSS_ENDPOINT_.

Ok, well! You are finish to configure. Just feel free to use Aliyun OSS like Storage!

## Usage

See [Larave doc for Storage](https://laravel.com/docs/5.5/filesystem#custom-filesystems)
Or you can learn here:

1. First you must use Storage facade

   ```php
   use Illuminate\Support\Facades\Storage;
   ```

2. Then You can use all APIs of laravel Storage

   ```php
   Storage::disk('oss'); // if default filesystems driver is oss, you can skip this step

   //fetch all files of specified bucket(see upond configuration)
   Storage::files($directory);
   Storage::allFiles($directory);

   Storage::put('path/to/file/file.jpg', $contents); //first parameter is the target file path, second paramter is file content
   Storage::putFile('path/to/file/file.jpg', 'local/path/to/local_file.jpg'); // upload file from local path

   Storage::get('path/to/file/file.jpg'); // get the file object by path
   Storage::exists('path/to/file/file.jpg'); // determine if a given file exists on the storage(OSS)
   Storage::size('path/to/file/file.jpg'); // get the file size (Byte)
   Storage::lastModified('path/to/file/file.jpg'); // get date of last modification

   Storage::directories($directory); // Get all of the directories within a given directory
   Storage::allDirectories($directory); // Get all (recursive) of the directories within a given directory

   Storage::copy('old/file1.jpg', 'new/file1.jpg');
   Storage::move('old/file1.jpg', 'new/file1.jpg');
   Storage::rename('path/to/file1.jpg', 'path/to/file2.jpg');

   Storage::prepend('file.log', 'Prepended Text'); // Prepend to a file.
   Storage::append('file.log', 'Appended Text'); // Append to a file.

   Storage::delete('file.jpg');
   Storage::delete(['file1.jpg', 'file2.jpg']);

   Storage::makeDirectory($directory); // Create a directory.
   Storage::deleteDirectory($directory); // Recursively delete a directory.It will delete all files within a given directory, SO Use with caution please.

   // upgrade logs
   // new plugin for v2.0 version
   Storage::putRemoteFile('target/path/to/file/jacob.jpg', 'http://example.com/jacob.jpg'); //upload remote file to storage by remote url
   // new function for v2.0.1 version
   Storage::url('path/to/img.jpg'); // get the file url
   // new function for v2.1.1
   Storage::signUrl('path/to/img.jpg',$timeout); // get the file url with signature,default timeout = 3600
   // new function for v2.3.0
   Storage::objects($directory); // Get all of the directories and files within a given directory
   Storage::allObjects($directory); // Get all (recursive) of the directories and files within a given directory
   // new function for v2.3.1
   Storage::url2path($url); // Get path from url
   // new function for v2.3.3
   Storage::copyDir('path/from/','path/to/'); // Copy a directory
   ```

## Documentation

More development detail see [Aliyun OSS DOC](https://help.aliyun.com/document_detail/32099.html?spm=5176.doc31981.6.335.eqQ9dM)

## License

Source code is release under MIT license. Read LICENSE file for more information.
