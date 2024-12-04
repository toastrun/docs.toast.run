---
layout: page
title: SAPI Interface
parent: micro Wiki EN
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

## SAPI Name

SAPI name of micro sholud be `"micro"`, which can be obtained by PHP function [php_sapi_name](https://www.php.net/manual/en/function.php-sapi-name.php).

If PHP code checks SAPI name, you can define a macro (TODO: use `make` parameter to specify) to make micro return `"cli"` as SAPI name.

## SAPI Functions

Before major version 1, the names, signatures, etc. of the following functions may change.
{: .warning}

{% include funcSign.html name='micro_version' %}

Return the version number of micro.

**Returns** array The version number of micro, in the format of an array, where the first three elements of the array are the major version number, the minor version number, and the revision version number of the version; if micro currently has a suffix, the fourth element of the array is the version number suffix, otherwise the array has only three elements.

{% include funcSign.html name='micro_get_self_filename' %}

Return the filename of self.

**Returns** string The filename of self as a string, using an absolute path.

{% include funcSign.html name='micro_get_sfxsize' %}

```text
| ELF/PE/Mach-O Executable | INI settings | PHP or PHAR payload | Other data |
|                          |                                    ^ If limit is set, micro_get_sfxsize_limit returns the position
|                          ^ micro_get_sfxsize returns the position
^ The beginning of the file
```

You may configure the limit through [Section settings](/micro/en/sections.html), if limit is not configured, `micro_get_sfxsize_limit` returns 0.

Returns the sfx executable part (the ELF/PE/Mach-O) end position.

**Returns** int The position in bytes.

{% include funcSign.html name='micro_get_sfxsize_limit' %}

Returns the size of the sfx executable part plus the payload size, see micro_get_sfxsize for details.

**Returns** int The position in bytes.

{% include funcSign.html name='micro_open_self' %}

Open the self file, with the Executable part in.

**Returns** resource If successful, returns a file pointer resource.

**Returns** bool If failed, returns `FALSE`.
