---
layout: page
title: micro是什么
parent: micro Wiki CHS
nav_order: 10
---

## 或许会有这样的故事
张三是一个PHP开发者，张三非常喜欢用PHP写一些小工具：

例如用PHP配合exec系函数来开停一些docker容器；用PHP清一下临时文件什么的。

张三知道，在linux下只要给PHP文件开头加上`#!php`，然后`chmod 0755`他就可以当一般的可执行文件用了；

如果把这个文件位置放进\$PATH里，甚至可以在shell里直接当一般命令用，美滋滋。

### 可能的场景1
某一天，公司新来了个小伙伴，ta也想清一下临时文件，但没有现成的东西。张三十分开心能帮小伙伴的忙，于是把自己的脚本拷给了小伙伴。

整好权限环境变量， 小伙伴激动的执行了这个文件，它却报错了，原来写这个工具的张三坚持使用最新的cli，使用了大量新版PHP语法，而小伙伴的PHP就不太新了。

### 可能的场景2
某一天，公司发现PHP写的产品需要用到张三的小工具，处理时还要一点算术逻辑。

如果用shell或者bat写这些逻辑就麻烦坏了；用go或者rust重写又有点小题大做；如果用现在这个PHP工具，又怕用户的PHP版本太旧或者根本没装cli，为此单独分发或者指定PHP版本也不太行。

## 这些场景指出的问题
PHP写一些命令行工具还是挺方便的：

 - 相较于go或者rust这类编译运行的语言，直接跑的脚本要方便开发的多，brew这样的工具都是用脚本语言写的；
 - 相较于部分其他脚本语言，标准库中有各种方便的工具函数可以直接调用：`unlink()` `mkdir()`一下都不需要`import`、`include`、`require`别的库；
 - 相较于shell，代数运算和字符串逻辑完善得多，用shell算个数可愁死个人，没有bc之类的工具几乎没法实现；

显然用PHP写一些命令行工具不失为一种好选择。虽然没有py2和3之间分裂那么大，但这种分发还是面临版本差异，库依赖等问题。

PHP库依赖可以通过Phar解决，那C库（PHP扩展）依赖和版本问题呢，很简单，用PHPmicro。

## micro SAPI

### 什么是PHPmicro
micro是一个SAPI，跟cli、cgi或者apache php mod一样。

PHPmicro的工作模式类似于Windows下常见的自解压程序（Self-extracted executable， sfx），当PHPmicro被执行时，它找到自己的尾巴并把尾巴当作PHP代码执行。

相较于自解压程序，PHPmicro执行时不会真的解压（拆分）自己，而是将尾巴部分直接交给PHP解释器执行，如果尾巴部分是Phar，PHP解释器会通过PHP内置的文件系统抽象层读取源码执行，在大多数情况下，会使用操作系统的文件映射来读取自身。换句话说，几乎不存在写入磁盘的临时文件。

相较于其他SAPI而言，通过修改PHP构建系统，PHPmicro进行静态编译，这使得PHPmicro不依赖系统的C库或其他库，也就是像go那样的一次编译，到处能用。

### 为什么有PHPmicro
PHP写命令行工具的好处已经说过了，所面临的问题也阐述了，PHPmicro是为了方便实现“可移植”的命令行工具而生的。

PHP相较于其他脚本语言（py，perl，lua，es等）有个显著的不同：大多数标准库函数/类由C实现在PHP扩展中，而其他脚本语言有大量本语言标准库：这使得单文件的PHPmicro工作模式是可以在PHP中实现的，而在其他语言中这就会比较复杂。

类似python的setuptools的单可执行可执行文件工具py2exe要解压整个依赖的标准库到临时文件。

### 什么时候用PHPmicro
PHPmicro的理论上的应用场景上面已经列出两个，除此之外：

如果你的PHP项目用了Swoole或者Swow，纯cli程序就可以完成任务，那就可以带上Swo\*编译PHPmicro，将你的代码打包成phar随PHPmicro分发。

发挥一下想象力：

 - Windows下你用sciter写了个gui，配合PHPmicro再修改下exe的图标和说明，看起来就会很高大上
 - 如果你的PHP项目需要nginx fpm等， 你可以将对应的二进制用Phar打包，和PHPmicro拼接发布，解压后用上他们
 - 甚至不是PHP项目，那也可以将所有用到的二进制用Phar打包，再用php解压一下用上他们
 - 甚至是一个docker容器，通过ffi（本文写时PHPmicro的ffi不可用，未来可能会实现）调用相关的api来实现单可执行文件的容器

### 怎么用PHPmicro
参考PHPmicro的gh说明构建：[https://github.com/easysoft/phpmicro/blob/master/Readme.md](https://github.com/easysoft/phpmicro/blob/master/Readme.md)

或者去release下个构建好的sfx：[https://github.com/easysoft/phpmicro/releases](https://github.com/easysoft/phpmicro/releases)

然后拼接一下

Windows下：`copy /b C:\路径\到\micro.sfx + D:\我的\代码.phar E:\输出\路径\打包好的.exe`

unix-like下：`cat /路径/到/micro.sfx /我的/代码.phar > /tmp/打包好的 && chmod 0755 /tmp/打包好的`

很快啊，一下就整好了。

### （目前的）PHPmicro的坑
linux的C库实现上并没有考虑静态/静态PIE文件的libdl功能，因此目前linux的PHPmicro并不能使用FFI相关函数，通过自定义的ld脚本和对C库小修改或许可以实现（Windows下FFI工作正常）。

测试还很不完善，欢迎大家使用测试反馈。
