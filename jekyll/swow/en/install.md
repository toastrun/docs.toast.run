---
layout: page
title: Extension Installation
parent: Swow Wiki EN
nav_order: 10
---

# Extension Installation

There are serval ways to install Swow extension

## Manually Build and Install (UNIX-like or cygwin, msys, wsl)

First prepare PHP developement prerequisites (You need PHP headers, `phpize`, `php-config` etc)

### Prepare (UNIX)

If only you need cURL hook or ssl support

#### linux

```bash
# debian and its variant like ubuntu, kali, armbian, raspbian, deepin, uos
apt-get install libcurl4-openssl-dev libssl-dev
# fedora, rhel 8, centos 8
dnf install libcurl-devel openssl-devel
# legacy fedora, rhel 6/7, centos 6/7
yum install libcurl-devel openssl-devel
# archlinux and its variant like manjaro, archlinuxarm, blackarch
pacman -S curl openssl
# alpine
# if it warns "openssl1.1-compat-dev-1.1.1xxx: conflicts", install curl-dev only
apk add curl-dev openssl-dev
# opensuse, suse
zypper install libcurl-devel libopenssl-devel
```

#### macOS

```bash
brew install curl openssl
```

Run `export PKG_CONFIG_PATH....` according to the brew hint.

You will need this whenever you build Swow, appending this into your bashrc is recommended.

### Build and Install

Download tarball or git-clone source, in your terminal, and run these commands to build and install. for build args, see [instrutions below](#configure-arguments)

```shell
# fetch source
git clone https://github.com/swow/swow.git swow
cd swow/ext
# generate configure
phpize
# execute configure, for build args, see below
./configure
# make it, use -jx to make concurrency
make -j4
# install it, if your prefix is not in system path, "sudo" can be omitted
sudo make install
```

Use `-d` to load Swow Extension is recommanded: `php -d extension=swow`
{: .hint }

## Manually Build and Install (Windows)

### Prepare MSVC

Choose your MSVC version. For example, If your php is downloaded from windows.php.net, with PHP 8.0.1 "VS16 x64 Non Thread Safe", you need VS16 version, aka Visual Studio 2019.

| VC version | VS version | Note |
| - | - | - |
| VS17 | 2022 |  |
| VS16 | 2019 | Install VS2019 or install VS2022 with VS2019 toolset |
| VC15 | 2017 | Install VS2017 or install VS2019 with VS2017 toolset |
| VC14 | 2015 | Install VS2015 or install VS2017 with VS2015 toolset |

### Prepare devpack

Download devpack from [PHP Windows download page](https://windows.php.net/download/): Find your version's "Development package (SDK to develop PHP extensions)"

Extract it to any path (e.g. C:\php-8.0.1-devel-vs16-x64)

### Prepare php-sdk-binary-tools

Clone php-sdk-binary-tools from ofically PHP to any path (e.g. C:\php-sdk-binary-tools)

```batch
git clone https://github.com/php/php-sdk-binary-tools
```

### Prepare (Windows)

Find dependencies from `https://windows.php.net/downloads/php-sdk/deps/<vc version like "vc15" or "vs16">/<arch like "x64">/`

Make sure the version is matched with your extension. Unmatched dep version may cause strange problems like segfault, stuck, and corrupt behavior. Files in `https://windows.php.net/downloads/php-sdk/deps/series/` may help.

Extract to any path  (e.g. C:\deps)

If the extracted dir is at the same level where Swow extension source locates, you can omit `--with-php-build` below.

i.e. Swow is at C:\swow, Its extension code is at C:\swow\ext, deps is at C:\swow\deps.

### Build

Open PHP SDK cmd.

For metioned PHP8.0 VS16 x64 NTS: run C:\php-sdk-binary-tools\phpsdk-vs16-x64.bat

In the opened window, run the following commands

```batch
git clone https://github.com/swow/swow.git swow
CD swow\ext
REM C:\php-8.0.1-devel-vs16-x64 is the path we prepared before
C:\php-8.0.1-devel-vs16-x64\phpize.bat
configure.bat --enable-swow --with-php-build=C:\deps
nmake
```

When the build is done, copy the built php_swow.dll (in the architecture dir like "x64") into your extension_dir (In default it's ext dir at the same dir of your php.exe or "C:\php\ext", see your PHP release note for detail)

Use `-d` to load Swow Extension is recommanded: `php -d extension=swow`
{: .hint }

## Composer

You can fetch swow source with composer:

```shell
composer require swow/swow
```

At `vendor/bin`, there will be a script file `swow-builder`, use it to install Swow.

```shell
# Compiles extension
php vendor/bin/swow-builder

# Compiles extension and specify the php-config path
php vendor/bin/swow-builder --php-config=/path/to/php-config

# Show help
php vendor/bin/swow-builder --help

# Simulate running, used to view the compilation command
php vendor/bin/swow-builder --dry-run

# Show full compilation log
php vendor/bin/swow-builder --show-log

# Do not ask during the compilation process (such as whether to install)
php vendor/bin/swow-builder --quiet

# Rebuild extension
php vendor/bin/swow-builder --rebuild

# Compile and install extension
php vendor/bin/swow-builder --install

# Compile and install extension with administrator privileges
php vendor/bin/swow-builder --install --sudo

# Rebuild and install extension
php vendor/bin/swow-builder --rebuild --install

# Rebuild and install extension and enable some features
php vendor/bin/swow-builder --rebuild --install --ssl --curl

# Compile and install extension and open the extension debug mode
php vendor/bin/swow-builder --install --debug
```

Use `-d` to load Swow Extension is recommanded: `php -d extension=swow`
{: .hint }

## Configure Arguments

> 1. PHP type compile arguments should be specified when compiling PHP
>
> 2. DEBUG type compile arguments need to enable `--debug` first to take effect
>
> 3. `--enable` or `--with` arguments mostly support path parameters after equal sign
>
> 4. Builder Alias is the alias of the argument when using `swow-builder`

| Option                              | Alias in Builder      | Type  | Platform  | Description                                                  |
| ----------------------------------- | --------------------- | ----- | --------- | ------------------------------------------------------------ |
| `--with-php-config=<path>`          | `--php-config=<path>` |       |           | 指定`php-config`路径                                         |
| `--enable-debug`                    |                       | PHP   |           | 打开PHP的调试模式，需要在**编译PHP时**指定，在编译Swow时指定无效 |
| `--enable-swow`                     |                       |       |           | 启用Swow扩展的编译（默认启用）                               |
| `--enable-debug-pack`               |                       |       | Windows   | 打开扩展的的debug pack构建，用于Windows下Release版本PHP的Swow调试，**编译Swow时**指定，不能与`--enable-debug`一同使用 |
| `--enable-swow-debug`               | `--debug`             |       |           | 启用DEBUG编译                                                |
| `--eanble-swow-memory-sanitizer`    | `--msan`              | Debug | Unix-like | 启用`memory-sanitizer`帮助底层进行内存分析                   |
| `--enable-swow-address-sanitizer`   | `--asan`              | Debug | Unix-like | 启用`address-sanitizer`帮助底层进行内存分析                  |
| `--enable-swow-undefined-sanitizer` | `--ubsan`             | Debug | Unix-like | 启用`undefined-sanitizer`帮助底层进行未定义行为分析          |
| `--enable-swow-gcov`                | `--gcov`              | Debug | Unix-like | 启用GCOV支持，用于支持统计C代码覆盖率                        |
| `--enable-swow-valgrind`            | `--valgrind`          | Debug | Linux     | 启用Valgrind支持（默认检测到有则自动启用）                   |
| `--enable-swow-thread-context`      | `--thread-context`    |       |           | 使用线程而不是`boost-context`作为协程上下文管理              |
| `--enable-swow-ssl`                 | `--ssl`               |       |           | 启用SSL支持，需要`OpenSSL`（默认检测到有则自动启用）         |
| `--enable-swow-curl`                | `--curl`              |       |           | 启用cURL支持，需要`libcurl`（默认检测到有则自动启用）        |
