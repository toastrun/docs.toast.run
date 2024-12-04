---
layout: page
title: SAPI接口
parent: micro Wiki CHS
nav_order: 30
functions:
  "micro_version":
    "returnType": "array"
    "params": []
  "micro_get_self_filename":
    "returnType": "string"
    "params": []
  "micro_get_sfxsize":
    "returnType": "int"
    "params": []
  "micro_get_sfxsize_limit":
    "returnType": "int"
    "params": []
  "micro_open_self":
    "returnType": "resource|bool"
    "params": []
  "realloc_console":
    "returnType": "void"
    "params": []
---

## SAPI名称

micro的SAPI名称应该是`"micro"`，这个名称可以通过PHP函数[php_sapi_name](https://www.php.net/manual/zh/function.php-sapi-name.php)获取。

如果PHP代码进行了SAPI名称检查，可以通过定义宏（TODO:用make参数来指定）来将micro返回的SAPI名称变为`"cli"`。

## SAPI函数

在micro主版本号1之前，以下函数的名称，签名等可能发生变化。
{: .warning}

{% include funcSign.html name='micro_version' %}

返回micro的版本号

**返回值** array micro的版本号，格式为数组，其中数组前三个元素分别为版本号的主版本号、次版本号、修订版本号；如果micro目前有后缀，则数组第四个元素为版本号后缀，否则数组只有三个元素。

{% include funcSign.html name='micro_get_self_filename' %}

返回自身的文件名

**返回值** string 自身的文件名字符串，使用绝对路径。

{% include funcSign.html name='micro_get_sfxsize' %}

```text
| ELF/PE/Mach-O Executable | INI settings | PHP or PHAR payload | Other data |
|                          |                                    ^ 如果设置了limit，micro_get_sfxsize_limit 返回的位置
|                          ^ micro_get_sfxsize 返回的位置
^ 文件开头
```

可以通过[Section设置](/micro/chs/sections.html)配置limit，如未配置则micro_get_sfxsize_limit返回0

返回自身的可执行文件（也就是exe/elf/mach-o）部分的大小。

**返回值** 单位为字节（byte）

{% include funcSign.html name='micro_get_sfxsize_limit' %}

返回自身的payload部分的结尾位置，见micro_get_sfxsize

**返回值** 单位为字节（byte）

{% include funcSign.html name='micro_open_self' %}

获取自身的没有去掉sfx头的只读文件勾柄。

**返回值** resource 成功时，返回打开的带sfx头的自身的文件勾柄

**返回值** bool 失败时返回`FALSE`。
