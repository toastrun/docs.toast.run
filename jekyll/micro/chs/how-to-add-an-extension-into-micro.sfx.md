---
layout: page
title: 如何在micro中加入一个PHP扩展
parent: micro Wiki CHS
nav_order: 60
---

像一般的in-tree扩展构建一样加入扩展即可，以下以[swoole](https://github.com/swoole/swoole-src)在linux的构建为例

## 1 准备php源码

按照安装的说明准备php和micro源码

```bash
# 在你的工作目录
git clone --depth 1 --branch php-8.0.2 https://github.com/php/php-src php-src
# 进入php源码目录
cd php-src
# 准备micro
git clone <micro的git url> sapi/micro
# 打patch（参见readme.md，可选）
patch -p1 < sapi/micro/patches/disable_huge_page.patch
```

## 2 准备扩展源码

( php官方扩展直接跳过本步 )

将扩展源码放到php源码的ext下的子目录，目录名任意

```bash
# 在php源码目录
git clone --depth 1 --branch v4.6.2 https://github.com/swoole/swoole-src ext/swoole
```

## 3 修改扩展代码

( php官方扩展直接跳过本步 )

许多非官方扩展完全未考虑in-tree构建，这导致了这么构建时会出错，参照具体情况修改

以下举例一些常见问题：

### config.h

对于许多需要HAVE_CONFIG_H才能构建的扩展，in-tree构建时不生成config.h而生成main/php_config.h

以下提供了一种缓解方案，以swoole v4.6.2 tag的代码为例，最新的swoole已经支持了in-tree构建

```patch
diff --git a/config.m4 b/config.m4
index 8193d9d69..1a9d1c846 100644
--- a/config.m4
+++ b/config.m4
@@ -286,6 +286,13 @@ fi
 AC_CANONICAL_HOST

 if test "$PHP_SWOOLE" != "no"; then
+dnl solve in-tree build config.h problem
+dnl just make a fake config.h before all things
+PHP_ADD_BUILD_DIR(PHP_EXT_DIR(swoole)"/build", 1)
+cat > PHP_EXT_DIR(swoole)/build/config.h << EOF
+#include "php_config.h"
+EOF
+INCLUDES="-I. -I"PHP_EXT_DIR(swoole)"/build ${INCLUDES}"

     AC_CHECK_LIB(c, accept4, AC_DEFINE(HAVE_ACCEPT4, 1, [have accept4]))
     AC_CHECK_LIB(c, signalfd, AC_DEFINE(HAVE_SIGNALFD, 1, [have signalfd]))
@@ -682,7 +689,7 @@ if test "$PHP_SWOOLE" != "no"; then
         AC_DEFINE(SW_USE_ASM_CONTEXT, 1, [use boost asm context])
     fi

-    PHP_NEW_EXTENSION(swoole, $swoole_source_file, $ext_shared,,$EXTRA_CFLAGS, cxx)
+    PHP_NEW_EXTENSION(swoole, $swoole_source_file, $ext_shared,,"$EXTRA_CFLAGS -DHAVE_CONFIG_H", cxx)

     PHP_ADD_INCLUDE([$ext_srcdir])
     PHP_ADD_INCLUDE([$ext_srcdir/include])
```

### SAPI名称检查

扩展可能检查sapi名称，可能出现类似`Swoole\Http\Server can only be used in CLI mode`的报错

对此可以考虑：

 - 手动patch相关检查（略麻烦）
 - 在构建sfx时在EXTRA_CFLAGS里加入`-DPHP_MICRO_FAKE_CLI`来让micro伪装成cli（不推荐，但省事）

patch的方法：以swoole v4.6.2 tag的代码为例
```patch
diff --git a/ext-src/php_swoole.cc b/ext-src/php_swoole.cc
index d98127733..89ab4380c 100644
--- a/ext-src/php_swoole.cc
+++ b/ext-src/php_swoole.cc
@@ -722,7 +722,7 @@ PHP_MINIT_FUNCTION(swoole) {
     }

     swoole_init();
-    if (strcmp("cli", sapi_module.name) == 0 || strcmp("phpdbg", sapi_module.name) == 0) {
+    if (strcmp("cli", sapi_module.name) == 0 || strcmp("phpdbg", sapi_module.name) == 0 || strcmp("micro", sapi_module.name) == 0 ) {
         SWOOLE_G(cli) = 1;
     }
```

### 源码结构问题

扩展可能

 - 将php_扩展名.h移出扩展根目录（例如swow或者swoole在提交[eba657b](https://github.com/swoole/swoole-src/commit/eba657b310fb7e714764e3b4d4e398138714180d)之后）导致不兼容php构建系统的静态in-tree构建
 - 将所有C/CPP源码移出扩展根目录（例如swow在提交[9218e9c](https://github.com/swow/swow/commit/9218e9c8ca785abb3292d3cb79ef5a41094e0f27)之前）导致不兼容
Windows php构建系统的in-tree构建

对于这类问题可以采用一些缓解方法：

 - 移出任意c文件到扩展根目录，并修改config.m4
 - 在扩展根目录新建一个内容为下的任意名称的头文件
```c
extern zend_module_entry <扩展名>_module_entry;
#define phpext_<扩展名>_ptr &<扩展名>_module_entry
```

## 4 buildconf和configure

像一般的构建操作一样，以非官方扩展swoole和官方扩展pcntl为例：

```bash
# 在php源码目录
./buildconf --force
./configure <other args like --disable-all --disable-cgi --disable-cli --enable-micro --disable-phpdbg --enable-swoole --enable-pcntl
```

## 5 构建

```bash
make -j`nproc`
```
