# Php Docset 生成工具

## 获取索引

将最新的 PHP.docset 的索引文件放置在 `_php/docSet.dsidx` 位置

- 打开 Dash, `Preference` -> `Docsets` -> `Php`
- 右键选择 `Show In Finder`
- 右键 `PHP.docset` -> `显示包内容`, 找到 `Contents/Resources/docSet.dsidx`

## 获取文档

将最新的 Php 中文版文档解压完成后放置到 `_php/PHP.docset/Contents/Resources/Documents` 目录下, 下载地址 : https://www.php.net/download-docs.php

## 运行替换

运行时候自动替换 `Guide` 类型的中文标题

```
cd laravel
php artisan php:docset
```