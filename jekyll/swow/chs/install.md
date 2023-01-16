---
layout: page
title: 扩展安装
parent: Swow Wiki CHS
nav_order: 10
---

# 扩展安装

Swow 扩展安装提供了以下几种方法

## 通过 Composer 安装

可以使用 Composer 下载源码

```shell
composer require swow/swow
```

下载完成后在 `vendor/bin` 目录中会有一个 `swow-builder` 的文件，我们可以使用此脚本文件来安装扩展

```shell
# 编译扩展
php vendor/bin/swow-builder

# 编译扩展并指定php-config路径
php vendor/bin/swow-builder --php-config=/path/to/php-config

# 查看帮助
php vendor/bin/swow-builder --help

# 模拟运行，用于查看编译命令
php vendor/bin/swow-builder --dry-run

# 编译扩展时显示完整编译日志信息
php vendor/bin/swow-builder --show-log

# 编译扩展过程中不进行询问 (如询问是否安装)
php vendor/bin/swow-builder --quiet

# 清理后编译扩展
php vendor/bin/swow-builder --clean

# 重新检测配置并清理后再编译扩展
php vendor/bin/swow-builder --rebuild

# 编译并安装扩展
php vendor/bin/swow-builder --install

# 编译并使用管理员权限安装扩展
php vendor/bin/swow-builder --install --sudo

# 清理后再编译并安装扩展
php vendor/bin/swow-builder --clean --install

# 重新检测配置并清理后再编译并安装扩展
php vendor/bin/swow-builder --rebuild --install

# 重新编译并安装扩展且手动指定启用一些功能
php vendor/bin/swow-builder --rebuild --install --ssl --curl

# 编译安装扩展并打开扩展的调试模式
php vendor/bin/swow-builder --install --debug
```

使用 `swow-builder` 安装时，程序在最后会询问并尝试使用管理员权限安装 Swow 扩展到系统路径，此时 `sudo` 可能会询问你管理员密码来安装 Swow 到系统目录。

安装程序在安装过程中会输出类似于下述的指令：

```shell
/usr/bin/env php -d extension=/path/to/your-project/vendor/swow/swow/ext/modules/swow.so --ri swow
```

你可以复制该指令并在命令行中运行，以检查 Swow 是否安装成功。

你也可以参考它来运行你的程序，而不是将 so 添加到全局的 ini 配置文件中，这样做的好处是你可以在不同的项目中使用不同的 Swow 版本。

编译安装成功后，在使用时推荐通过 `-d` 来按需加载 Swow 扩展，如：`php -d extension=swow`
{: .hint }

---

## 编译参数

> 1. PHP类型的编译参数需要在编译PHP时指定
>
> 2. DEBUG类型的编译参数需要先启用`--debug`才能生效
>
> 3. `--enable`或`--with`参数大都支持在后面用等于号指定路径参数
> 4. Builder Alias 是指在使用`swow-builder`时传参的别名

| 选项                                | 在Builder中的别名     | 类型  | 支持平台  | 描述                                                         |
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

## 手动编译安装 (UNIX-like 或 cygwin、msys、wsl)

首先安装PHP和它的开发包（php头文件和phpize，php-config等），安装方法参考各发行版说明

### 准备构建依赖（UNIX）

如果你需要cURL hook支持或者ssl支持

#### linux

```bash
# debian和它的变种，如ubuntu, kali, armbian, raspbian, deepin, uos
apt-get install libcurl4-openssl-dev libssl-dev
# fedora, rhel 8, centos 8
dnf install libcurl-devel openssl-devel
# 旧版fedora, rhel 6/7, centos 6/7
yum install libcurl-devel openssl-devel
# archlinux和它的变种，如manjaro, archlinuxarm, blackarch
pacman -S curl openssl
# alpine
# 如果提示openssl1.1-compat-dev-1.1.1xxx: conflicts，只安装curl-dev就行
apk add curl-dev openssl-dev
# opensuse, suse
zypper install libcurl-devel libopenssl-devel
```

#### macOS

```bash
brew install curl openssl
```

然后根据提示执行export PKG_CONFIG_PATH来让configure能够找到它们。

每次构建Swow都需要这个export，建议加入你的bashrc。

### 构建安装

下载或者 clone 源代码后，在终端进入源码目录，执行下面的命令进行编译和安装，构建参数见[下面的说明](#编译参数)

```shell
# 获取源码
git clone https://github.com/swow/swow.git swow
cd swow/ext
# 生成configure
phpize
# 执行configure，构建参数见下面的说明
./configure
# 构建，可以使用 -j+数字 来并行构建
make -j4
# 安装，如果configure制定了prefix，可以不使用sudo
sudo make install
```

编译成功后，在使用时推荐通过 `-d` 来按需加载 Swow 扩展，如：`php -d extension=swow`
{: .hint }

---

## 手动编译安装 (Windows)

### 准备MSVC

根据你所使用的PHP发布选择安装MSVC的版本，例如使用了PHP 8.0.1的"VS16 x64 Non Thread Safe"选项，则需要选择VS16，也就是VS2019

| VC版本号 | VS版本 | 说明 |
| - | - | - |
| VS17 | 2022 |  |
| VS16 | 2019 | 安装VS2019或者在安装VS2022时选择VS2019工具链 |
| VC15 | 2017 | 安装VS2017或者在安装VS2019时选择VS2017工具链 |
| VC14 | 2015 | 安装VS2015或者在安装VS2017时选择VS2015工具链 |

### 准备devpack

在 [PHP Windows 下载页](https://windows.php.net/download/) 找到你所使用PHP版本的"Development package (SDK to develop PHP extensions)"链接，下载它

解压到任意目录（以下使用C:\php-8.0.1-devel-vs16-x64为例）

### 准备php-sdk-binary-tools

clone PHP官方提供的php-sdk-binary-tools到任意目录（以下使用C:\php-sdk-binary-tools为例）

```batch
git clone https://github.com/php/php-sdk-binary-tools
```

### 准备构建依赖（Windows）

在 `https://windows.php.net/downloads/php-sdk/deps/<vc版本例如vc15或者vs16>/<架构名例如x64>/` 找到依赖的包（例如curl）

注意版本对齐，未对齐的依赖版本可能导致奇怪的segfault，PHP无法正常退出等神奇问题，`https://windows.php.net/downloads/php-sdk/deps/series/`中的文件提供了这些版本信息

解压到任意目录（以下使用C:\deps为例）

如果解压到Swow扩展源码目录的同级deps目录，则下面可以省去--with-php-build参数

例如Swow源码在C:\swow，Swow扩展源码目录在C:\swow\ext，deps在C:\swow\deps时

### 构建

打开PHP工具命令行：

例如在为之前提到的PHP8.0 VS16 x64 NTS构建swow扩展则执行C:\php-sdk-binary-tools\phpsdk-vs16-x64.bat

在打开的命令行中下载或者 clone 源代码后，进入源码目录，执行下面的命令进行构建

```batch
git clone https://github.com/swow/swow.git swow
CD swow\ext
REM 下面的C:\php-8.0.1-devel-vs16-x64是之前解压的devpack路径
C:\php-8.0.1-devel-vs16-x64\phpize.bat
configure.bat --enable-swow --with-php-build=C:\deps
nmake
```

构建完成后，将生成的php_swow.dll（位于当前目录的架构名目录中，如x64）放置于extension_dir中（默认的，这个目录是php文件同级的ext目录或者C:\php\ext，具体情况参照所使用的PHP发行说明）

编译成功后，在使用时推荐通过 `-d` 来按需加载 Swow 扩展，如：`php -d extension=swow`
{: .note }
