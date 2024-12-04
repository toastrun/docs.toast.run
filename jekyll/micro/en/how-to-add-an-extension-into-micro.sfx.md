---
layout: page
title: How to add an extension into micro.sfx
parent: micro Wiki EN
nav_order: 60
---

Like in-tree extensions, you can add extensions to micro.sfx. Here is an example of building [swoole](https://github.com/swoole/swoole-src) on Linux.

## 1 Prepare PHP source code

Prepare PHP and micro source code according to the installation instructions.

```bash
# at your working directory
git clone --depth 1 --branch php-8.0.2 https://github.com/php/php-src php-src
# enter the php source code directory
cd php-src
# prepare micro
git clone <micro git url> sapi/micro
# apply patches (see readme.md under patches/, optional)
patch -p1 < sapi/micro/patches/disable_huge_page.patch
```

## 2 Prepare extension source code

( Skip this step for official PHP extensions )

Place the extension source code in a subdirectory under ext in the PHP source code.

```bash
# at the php source code directory
git clone --depth 1 --branch v4.6.2 https://github.com/swoole/swoole-src ext/swoole
```

## 3 Modify the extension code

( Skip this step for official PHP extensions )

Many unofficial extensions are not designed for in-tree builds, which can cause errors when building in-tree. Modify as needed.

Here are some common issues:

### config.h

For many extensions that require HAVE_CONFIG_H to build, config.h is not generated during in-tree builds, but main/php_config.h is generated instead.

Here is a workaround. Using the code from the swoole v4.6.2 tag as an example, the latest swoole already supports in-tree builds.

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

### SAPI name check

Extensions may check the SAPI name, which may cause errors like `Swoole\Http\Server can only be used in CLI mode`.

You can consider:

- Manually patch related checks (a bit cumbersome)
- Add `-DPHP_MICRO_FAKE_CLI` to `EXTRA_CFLAGS` in the build of sfx to make micro masquerade as cli (not recommended, but easier)

How to patch: Take the code from the swoole v4.6.2 tag as an example

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

### Source code structure issues

Extensions may have issues with the source code structure, such as:

- Moving php_<extension name>.h out of the extension root directory (e.g., swow or swoole after commit [eba657b](https://github.com/swoole/swoole-src/commit/eba657b310fb7e714764e3b4d4e398138714180d)) causing incompatibility with the PHP build system's static in-tree build
- Moving all C/CPP source code out of the extension root directory (e.g., swow before commit [9218e9c](https://github.com/swow/swow/commit/9218e9c8ca785abb3292d3cb79ef5a41094e0f27)) causing incompatibility with the Windows PHP build system's in-tree build

For these issues, you can use some workarounds:

- Move any C file to the extension root directory and modify config.m4
- Create a header file with the following content in the extension root directory

```c
extern zend_module_entry <extension name>_module_entry;
#define phpext_<extension name>_ptr &<extension name>_module_entry
```

## 4 buildconf and configure

Just like general build operations, take the unofficial extension swoole and the official extension pcntl as examples:

```bash
# at the php source code directory
./buildconf --force
./configure <other args like --disable-all --disable-cgi --disable-cli --enable-micro --disable-phpdbg --enable-swoole --enable-pcntl
```

## 5 Build it

```bash
make -j`nproc`
```
