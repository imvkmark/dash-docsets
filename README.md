# Php Docset 生成工具

## 使用方式

**自行下载**

在 [Release](./releases) 页面下载, 自行安装

**订阅**

[dash-feed://https://raw.githubusercontent.com/imvkmark/dash-docsets/master/phpdocset.xml](dash-feed://https%3A%2F%2Fraw.githubusercontent.com%2Fimvkmark%2Fdash-docsets%2Fmaster%2Fphpdocset.xml)


- 订阅 feed, 发布新版本会自行更新

## 特点

- PlayGround 支持菜鸟运行工具 [PHP 菜鸟工具](https://c.runoob.com/compile/1/)

![](./_php/preview.png)

## 步骤

**获取索引**

将最新的 PHP.docset 的索引文件(Dash 官方)放置在 `_php/docSet.dsidx` 位置

- 打开 Dash, `Preference` -> `Docsets` -> `Php`
- 右键选择 `Show In Finder`
- 右键 `PHP.docset` -> `显示包内容`, 找到 `Contents/Resources/docSet.dsidx`

**获取文档**

将最新的 Php 中文版文档解压完成后放置到 `_php/PHP.docset/Contents/Resources/Documents` 目录下, 下载地址 : https://www.php.net/download-docs.php

**运行替换**

- 运行时候自动替换 `Guide` 类型的中文标题
- 运行时候自动优化样式, 去除默认背景

需要

```
php > 8.0.2
```

```
cd laravel
php artisan php:docset
```

**发布**

对文档目录进行打包 tgz 的包

```
tar -cvzf _php/PHP.CN.tgz _php/PHP.docset
```

## 参考

- [Docset Generation Guide](https://kapeli.com/docsets)

