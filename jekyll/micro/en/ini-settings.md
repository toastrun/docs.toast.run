---
layout: page
title: INI Configuration
parent: micro Wiki EN
nav_order: 40
---

## INI Configuration

The magic number used in the INI file header may change before micro's main version 1.
{: .hint}

micro identifies the INI file header with a special magic number, and through the INI file header, micro can be started with INI settings.

The INI file header is placed at the beginning of the micro self-executable file, and the INI settings are placed after the INI file header.

```brainfuck

|--------------------------|---------------------------------------------------------------------------|----------------------|
| micro.sfx ELF/PE header  |                            ini part                                       |    php/phar code     |
|                          |                    |                                |                     |                      |
| micro_get_sfx_filesize() |    4 byte magic    |     4 byte sizeof(ini text)    |   n byte ini text   |                      |
| returns sizeof this part | "\xfd\xf6\x69\xe6" | "\0\0\0\x10"(16 in big endian) | "ini_key=\"value\"" | "<?php things();..." |
|--------------------------|---------------------------------------------------------------------------|----------------------|
^ micro_open_self() will return stream start at here                                                   |
                     fopen functions will create stream start reading at here, code will run from here ^
```

The INI file header is defined as:

```c
struct MICRO_INI_HEADER{
    uint32_t magic; // "\xfd\xf6\x69\xe6"
    uint32_t size; // in big-endian
} __attribute__((packed));
```

For example: use the following PHP code to generate an INI header:

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

Then execute:

Windows (CMD):
```batch
COPY /B micro.sfx + myiniheader.bin + myawesomeapp.phar myawesomeapp.exe
```

PowerShell in Windows cannot use the `COPY` command like this, due to some annoying codepage issues, it is recommended to use CMD or PHP script to concatenate.

Unix:
```batch
cat micro.sfx myiniheader.bin myawesomeapp.phar > myawesomeapp
chmod 0755 myawesomeapp
```
