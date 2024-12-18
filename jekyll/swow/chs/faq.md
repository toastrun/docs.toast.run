---
layout: page
title: 常见问题
parent: Swow Wiki CHS
nav_order: 20
---

# 常见问题

## 同时安装了`swow` 和 `swoole`

```
Coroutine module is incompatible with some extensions that setup exit user opcode handler in Unknown on line 0
```

不能与不兼容的扩展同时加载，需要在 `php.ini` 中移除存在冲突的扩展。
{: .waring }

## 使用SDB调试时提示 Please re-run your program with "-e" option

```
# 进入id为3的协程进行跟踪调试
> attach 3
Error: Please re-run your program with "-e" option in path/to/vendor/swow/swow/lib/swow-library/src/Debug/Debugger/Debugger.php:0
```

需要在运行的时候加上`-e`参数，如`php -e your_file.php`。
{: .waring }

原因：开启`-e`参数后，PHP会在执行每个OPCode时尝试调用扩展注册的回调函数，因此平时开启`-e`选项会影响程序性能，我们需要在调试的时候手动开启该选项，否则单步调试跟踪和断点功能将无法使用。
{: .hint }

## 启用Swow后PHP提示PHP Warning: PHP Startup: Swow pdo_pgsql hook not enabled

如果你的Swow编译时开启了pgsql支持，在启动时环境又没有pgsql：

- 没有开启pdo扩展，提示: "pdo extension not enabled"
- 没有找到支持的libpq依赖库，提示: "libpq.so/libpq.5.dylib/lipq.dll not found" "libpq is too old"

那么启动时会提示这个，这时pdo_pgsql的hook不工作。

如果你需要支持：

- 开启PHP的[pdo扩展](https://www.php.net/manual/zh/pdo.setup.php)
- [安装libpq](https://www.postgresql.org/download/)

如果你不需要这个支持，可以配置ini：

```plain
swow.hook_pdo_pgsql = Off;
```
