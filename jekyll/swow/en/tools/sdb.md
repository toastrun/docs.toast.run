---
layout: page
title: SDB
grand_parent: Swow Wiki EN
parent: Tools
---

# SDB

SDB is a debugger written in PHP

* Easy to use: just one-line PHP
* Network is not required, you can use it at your terminal
* No performance overhead, can be used in production live debug
* (I donot know how to translate this, but it means "SDB is strong")

## Usage

Add the following line in your code:

```php
\Swow\Debug\Debugger::runOnTTY();
```

Run your code with `-e`:

```shell
php -e -d extension=swow index.php
```

It will show you:

```bash
  ____    ____    ____  
 / ___|  |  _ \  | __ ) 
 \___ \  | | | | |  _ \ 
  ___) | | |_| | | |_) |
 |____/  |____/  |____/

Enter 'r' to run your program
> 
```

Type "r" to start debugging

## Supported Commands

1. Show all coroutine status (`ps`)
2. Inspect coroutine (`co id`)
3. Attach coroutine (`attach id`)
4. Show backtrace (`bt`)
5. Forward calling frame (`f index`)
6. Set breakpoint (`b`)
7. Step running (`n`)
8. Continue running (`c`)
9. List source codes (`l`)
10. Print variable(`p $var`)
11. Assign value (`p $var =x`)
12. Run code in coroutine (`p command0`)
13. Show variables (`vars`)
14. Find zombied coroutines(`z <timeout>`)
15. Kill coroutine (`kill id`)
16. Kill all coroutines (`killall`)

## Exit debugger

Type `q` to exit the debugger

If any coroutine is still running when exiting, you can use key combinations to call the debugger out.

The default key combination is `sdb` + enter, you can set this in `runOnTTY()`:

```php
\Swow\Debug\Debugger::runOnTTY('swow');
```

Otherwise, the program will exit immediately.
