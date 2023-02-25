---
layout: page
title: FAQ
parent: Swow Wiki EN
nav_order: 20
---

# Frequently Asked Questions

## Both Swow and Swoole enabled

```
Coroutine module is incompatible with some extensions that setup exit user opcode handler in Unknown on line 0
```

Swow and Swoole provides similar kind of facilities, so they are exclusive, please remove Swow or Swoole from your php.ini
{: .waring }

## `Please re-run your program with "-e" option` when using SDB

```
> attach 3
Error: Please re-run your program with "-e" option in path/to/vendor/swow/swow/lib/swow-library/src/Debug/Debugger/Debugger.php:0
```

Append `-e` argument in php command line like: `php -e your_file.php`。
{: .waring }

When `-e` is enabled, PHP will call extension registered OPCode hanlder, this may harm performance in usual.
{: .hint }
