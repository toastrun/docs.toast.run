---
layout: page
title: How micro work
parent: micro Wiki EN
nav_order: 20
---

micro is a SAPI of PHP, like self-extract program of 7z or WinRAR.

When micro's sfx and php code (or a PHAR, we call them php code here) are concatenated together, execute this file, micro runs the concatenated php code.

## Some detials of micro execution

Here is some details

## Global variables

Most global variables follow the cli SAPI. The special ones are

- PHP_BINARY：If there is no INI settings (see [INI settings](/micro/en/ini-settings.html)), PHP_BINARY is set to an empty string `""`; otherwise it is set to the value of the INI settings

## File streams

File stream means the stream abstraction in PHP, the resource opened by PHP function `fopen` is a file stream
{: .hint }

If the absolute path file name of a file stream is equal to the absolute path of the executed micro program, it is called a self file stream.

For the php code executed in micro:

- When using ftell() and fseek() on the self file stream, the sfx size is subtracted
- When using fstat() on the self file stream, the file size attribute is subtracted by the sfx size

That is to say, the self file opened in the php code executed in micro will be consistent with the concatenated php code, and there will be no sfx ELF/PE header or INI settings.

The purpose of this design is that if a text php code or a phar file opens itself (whether through include, require or fopen series functions), its behavior should be consistent with the code being executed by cli.

For example:

```PHP
<?php
$f = fopen(__FILE__, "r");
var_dump(fread($f, 5));
```

When this file is executed in micro, it prints `"<?php"` instead of `"ELF"` or `"MZ"`

## SAPI Interfaces

In addition, SAPI implements some PHP interfaces

See [SAPI Interface](/micro/en/sapi-interface.html)
