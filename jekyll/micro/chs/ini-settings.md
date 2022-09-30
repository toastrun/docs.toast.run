---
layout: page
title: INI配置
parent: micro Wiki CHS
nav_order: 40
---

## INI设置

在micro主版本号1之前，下面用到的magic可能发生变化。
{: .hint}

micro通过特殊的magic来标识INI文件头，通过INI文件头可以实现micro带INI启动。

一个带INI的micro自执行文件的结构：

```brainfuck
|--------------------------|--------------------------------------------------------------------|----------------------|
| micro.sfx ELF/PE 头      |                            ini 部分                                |    php/phar code     |
|                          |                    |                         |                     |                      |
| micro_get_sfx_filesize() |    4 byte magic    | 4 byte sizeof(ini text) |   n byte ini text   |                      |
| 返回这个大小              | "\xfd\xf6\x69\xe6" | "\0\0\0\x10"(大端序的16) | "ini_key=\"value\"" | "<?php things();..." |
|--------------------------|--------------------------------------------------------------------|----------------------|
^ micro_open_self() 返回的文件从这里开始                                                         |
                                                            fopen(__FILE__) 返回的文件从这里开始 ^

```

ini文件头为

```c
struct MICRO_INI_HEADER{
    uint32_t magic; // "\xfd\xf6\x69\xe6"
    uint32_t size; // in big-endian
}
```

例如：使用以下php代码来生成ini头：

```php
$myini = "
ffi.enable=1
micro.php_binary=cafebabe
"
$f=fopen("myiniheader.bin", "wb");
fwrite($f, "\xfd\xf6\x69\xe6");
fwrite($f, pack("N", strlen($myini)));
fwrite($f, $myini);
fclose($f);
```

然后执行

Windows:
```batch
COPY /B micro.sfx + myiniheader.bin + myawesomeapp.phar myawesomeapp.exe
```

Unix:
```batch
cat micro.sfx myiniheader.bin myawesomeapp.phar > myawesomeapp
chmod 0755 myawesomeapp
```

来生成带INI设置的micro自执行文件
