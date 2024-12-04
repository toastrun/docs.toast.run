---
layout: page
title: Section设置
parent: micro Wiki CHS
nav_order: 50
---

## Section设置

版本0.2.0之后，micro采用ELF/PE/Mach-O文件的section/segment来获取信息，不再采用多次构建。

Section模式支持配置limit，UPX压缩（ELF下需要修改二进制），数字签名，允许构建后修改二进制

### 文件结构

```text
| ELF/PE/Mach-O Executable | INI settings | PHP or PHAR payload | Other data |
```

### limit配置

如果未设置以下的sfxsize section：

- micro_get_sfxsize_limit返回0，所有`size`之后的数据均为payload
- micro_get_sfxsize返回
  - ELF为最后一个Section的结尾，如果没有Section表，返回最后一个`Program Header Segment`的结尾
  - Mach-O为`__LINKEDIT`的结尾
  - Windows下为最后一个Section的结尾

#### sfxsize section

sfxsize section的格式为：

```C
typedef struct _micro_sfxsize_section_t {
    uint64_t size; /* sfx大小，大端序 */
    uint64_t limit; /* limit大小，大端序 */
} __attribute__((pacaked)) micro_sfxsize_section_t;
```

`size`表示可执行文件二进制大小，单位bytes，大端序

`limit`表示sfx二进制+payload大小，单位bytes，大端序

当`limit`为0时，表示不限制大小，所有`size`之后的数据均为payload

#### ELF

对于使用ELF作为可执行文件格式的系统，例如Linux，FreeBSD：

micro通过`.sh_name`为`.sfxsize`的Section获取上面的结构体

#### Mach-O

对于macOS的Mach-O：

micro通过`__DATA` Segment的`__micro_sfxsize` Section获取上面的结构体

#### PE

对于Windows的PE：

micro通过ID为`PHP_MICRO_SFXSIZE_ID`即`12345`的`RC_DATA`获取上面的结构体

### UPX压缩

Windows下可以直接进行UPX压缩

ELF下由于UPX将section表丢掉了，无法获取UPX后的真实结尾位置，所以需要修改二进制：

sfx压缩后，将最后一个`Program Header Segment`后的数据删除，这么做后UPX无法解压，会让你的程序看起来很像病毒。

操作参考：先获取Program Header Segment

```bash
readelf -l micro.sfx.upxed
```

输出类似：

```text
...
Program Headers:
  Type           Offset             VirtAddr           PhysAddr
                 FileSiz            MemSiz              Flags  Align
  LOAD           0x0000000000000000 0x0000000000000000 0x0000000000000000
                 0x0000000000001000 0x0000000001cb6f88  RW     0x1000
  LOAD           0x0000000000000000 0x0000000001cb7000 0x0000000001cb7000
                 0x000000000065aadb 0x000000000065aadb  R E    0x1000
  GNU_STACK      0x0000000000000000 0x0000000000000000 0x0000000000000000
                 0x0000000000000000 0x0000000000000000  RW     0x10
...
```

将文件截断到最大的Offset+FileSiz(0x0000000000000000 + 0x000000000065aadb = 6662875)即可

```bash
dd if=micro.sfx.upxed of=micro.sfx.upxed.stripped bs=6662875 count=1
chmod 0755 micro.sfx.upxed.stripped
```

### 数字签名

TODO：做一个[工具](https://github.com/dixyes/sfx-editor)方便签名

#### Windows

Windows下的数字签名是修改部分PE/COFF头内容（不改变大小），然后在文件结尾添加一段数据，因此参考的步骤是：

0. 对sfx进行想要的修改，比如换个喜欢的logo啥的，可以使用Resource Hacker
1. 在sfx里添加ID为`PHP_MICRO_SIGNATURE_ID`即`12346`的`RC_DATA`，大小为16bytes，记录这个时候sfx的大小
2. 将2中section的数据改为真实数据（`.size` = sfx大小, `.limit` = sfx大小+payload大小，不含之后的padding），参考上面的[sfxsize section](#sfxsize section)
3. 将文件补0对齐到4096（好像512就够，但我用4096）
4. 将最后一个section的结尾设为文件结尾
5. 进行签名

#### Mach-O

Mach-O签名是添加了一个segment/section，因此需要这么操作：

1. Mach-O下将sfx和payload拼接
2. 补0对齐到4096
3. 将__LINKEDIT的结尾设为文件结尾
4. 进行签名




