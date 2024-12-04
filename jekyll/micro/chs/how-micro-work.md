---
layout: page
title: micro怎么工作
parent: micro Wiki CHS
nav_order: 20
---

micro是一个自执行文件，类似于7z或者WinRAR的自解压程序。

当micro的sfx和php代码（或者phar包，这里我们都称为php代码）拼接在一起后，执行这个文件，micro直接执行被拼接的php代码。

## micro执行模式细节

下面描述下micro执行中的一些细节

## 全局变量

大多数全局变量遵循cli的工作模式。较为特殊的有

- PHP_BINARY： 如果不进行INI配置（见[INI配置](/micro/chs/ini-settings.html)），PHP_BINARY变量被设置为空字符串`""`；否则为INI配置的值

## 文件流

文件流 指PHP里的stream抽象，PHP函数`fopen`打开的resource就是一个文件流
{: .hint }

如果一个文件流的绝对路径文件名等于被执行的micro sfx的绝对路径，则称它为自身的文件流。

对于micro中执行的php代码：

- 对打开的自身文件流使用ftell和fseek时，减去了sfx大小
- 对打开的自身文件流使用fstat时，其中的文件大小属性减去了sfx大小

也就是说，php代码中打开的自身文件，在php看来将会与被拼接的php代码一致，不会出现sfx的ELE/PE头或者INI设置。

这个设计的目的是如果一个文本php代码或phar文件打开自身（无论是通过include、require还是fopen系列函数），它的行为应该与这个代码被cli执行一致。

例如：

```PHP
<?php
$f = fopen(__FILE__, "r");
var_dump(fread($f, 5));
```

这个文件在micro中执行时，打印的是`"<?php"`而非`"ELF"`或者`"MZ"`

## SAPI接口

除此之外SAPI实现了一些PHP接口

见[SAPI接口](/micro/chs/sapi-interface.html)
