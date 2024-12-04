---
layout: page
title: Section Settings
parent: micro Wiki EN
nav_order: 50
---

## Section Settings

After version 0.2.0, micro uses the section/segment of ELF/PE/Mach-O files to obtain information, no longer using multiple builds.

Section mode supports configuration of limit, UPX compression (need to modify binary under ELF), digital signature, and allows modification of binary after building

### File Structure

```text
| ELF/PE/Mach-O Executable | INI settings | PHP or PHAR payload | Other data |
```

### limit Configuration

If the following sfxsize section is not set:

- `micro_get_sfxsize_limit` returns 0, all data after `size` is payload
- `micro_get_sfxsize` returns, when
  - ELF: The end of the last Section, if there is no Section table, return the end of the last `Program Header Segment`
  - Mach-O: The end of `__LINKEDIT`
  - Windows: The end of the last Section

#### sfxsize section

The format of the sfxsize section is:

```C
typedef struct _micro_sfxsize_section_t {
    uint64_t size; /* sfx size, big endian */
    uint64_t limit; /* limit size, big endian */
} __attribute__((pacaked)) micro_sfxsize_section_t;
```

`size` represents the size of the executable file binary, in bytes, big endian

`limit` represents the size of sfx binary + payload, in bytes, big endian

When `limit` is 0, it means that there is no size limit, and all data after `size` is payload

#### ELF

For systems that use ELF as the executable file format, such as Linux, FreeBSD:

micro obtains the above structure through the Section with `.sh_name` as `.sfxsize`

#### Mach-O

For macOS's Mach-O:

micro obtains the above structure through the `__micro_sfxsize` Section of the `__DATA` Segment

#### PE

For PE(EXE) format used by Windows:

micro obtains the above structure through the Section with ID as `PHP_MICRO_SIGNATURE_ID`

### UPX

Under Windows, UPX compression is supported, and the compressed binary can be executed directly.

For ELF, because UPX discards the section table, it is impossible to obtain the real end position after UPX, so the binary needs to be modified:

Drop the data after the last `Program Header Segment` after UPX compression, so that UPX cannot decompress it, making your program look like a virus.

For reference: Get last `Program Header Segment` end position first

```bash
readelf -l micro.sfx.upxed
```

The output looks like:

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

Truncate the file to the largest Offset+FileSiz(0x0000000000000000 + 0x000000000065aadb = 6662875)

```bash
dd if=micro.sfx.upxed of=micro.sfx.upxed.stripped bs=6662875 count=1
chmod 0755 micro.sfx.upxed.stripped
```

### Digital Signature

TODO: Make a [tool](https://github.com/dixyes/sfx-editor) to facilitate signing

#### Windows

The digital signature under Windows is to modify part of the PE/COFF header content (without changing the size), and then add some data at the end of the file, so the steps to refer to are:

0. Modify sfx as desired, such as changing the logo to a favorite one, you can use Resource Hacker
1. Add an `RC_DATA` with ID `PHP_MICRO_SIGNATURE_ID` or `12346` in sfx, size is 16 bytes, record the size of sfx at this time
2. Change the data of the section to real data (`.size` = sfx size, `.limit` = sfx size + payload size, excluding the padding after), refer to the [sfxsize section](#sfxsize-section)
3. Align the file to 4096 (512 seems enough, but I use 4096)
4. Set the end of the last section to the end of the file
5. Sign it

#### Mach-O

The digital signature under macOS is to add a segment/section, so the operation is:

1. Concatenate sfx and payload
2. Align to 4096
3. Set the end of `__LINKEDIT` to the end of the file
4. Sign it
