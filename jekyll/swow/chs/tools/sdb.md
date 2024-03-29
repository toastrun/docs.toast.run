---
layout: page
title: SDB
grand_parent: Swow Wiki CHS
parent: 工具们
---

# SDB

SDB 是一款使用 PHP 编写的协程调试器工具。

* 使用简单，只需要一行代码即可开启；
* 无需端口，可直接运行在 TTY 上；
* 零成本，可在生产环境使用，不影响性能；
* 功能强大，深度定制，量身打造类微型操作系统；

## 使用

只需要在你的代码当中加入一行代码：

```php
\Swow\Debug\Debugger\Debugger::runOnTTY();
```

在终端中启动运行时加上`-e`：

```shell
php -e -d extension=swow index.php
```

你就会在终端上得到这样的输出：

```bash
  ____    ____    ____  
 / ___|  |  _ \  | __ ) 
 \___ \  | | | | |  _ \ 
  ___) | | |_| | | |_) |
 |____/  |____/  |____/

Enter 'r' to run your program
> 
```

键入`r`开始调试之旅吧

## 支持命令

1. 查看当前所有协程状态(`ps`)
2. 窥视协程(`co <id>`)
3. 进入协程(`attach <id>`)
4. 查看调用栈(`bt`)
5. 查栈帧(`f ,index>`)
6. 打断点(`b`)
7. 单步调试(`n`)
8. 恢复运行(`c`)
9. 查看更多源码(`l`)
10. 打印变量(`p $var`)
11. 修改变量(`p $var = x`)
12. 执行代码(`p command0`)
13. 查看变量(`vars`)
14. 扫描僵尸协程(`z <时间>`)
15. 杀死协程(`kill <id>`)
16. 杀死所有协程(`killall`)

## 退出调试器

调试完毕后可以键入`q`退出调试器。

如果进程中还存在协程在运行，那么可以再次键入所设置的`关键字+回车`呼出调试器。

关键词默认为`sdb`，可以在调用`runOnTTY()`方法时设置，如：

```php
\Swow\Debug\Debugger\Debugger::runOnTTY('swow');
```

反之则直接退出。
