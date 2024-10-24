> I feel like this challenge contains a massive bait..
> I did not use `terminal.txt` at any point

## Step 1: Understanding `EncFile.c`
We are provided with a C program, but the headers are missing.

```C
#include "stdio.h"
#include "cipher.h"
#include "mode.h"
#include "utils.h"

int main(int argc, char *argv[]) {
	FILE * inf = NULL;
	FILE * ouf = NULL;
	UI8 * em = NULL;
	UI8 * lts = NULL;
	UI8 * txt = NULL;
	I64 eml, ltsl, txtl, blk;
	int f;
	inf = fopen("fk.txt", "r");
	if (inf == NULL) {goto BEnd;}
	blk = C64(128);
	ltsl = C64(32);
	txtl = C64(1);
	eml = txtl+16-(txtl%(blk/8));
	if (!(lts = PtrAlloc(ltsl))) {goto BEnd;}
	mymemset(lts, CU8(0), ltsl);
	fseek(inf, 0, SEEK_SET);
	if (ByteSeqReadFile(inf, "lts:", C64(9), lts, ltsl) == C64(1)) {goto BEnd;}
	if (inf) {fclose(inf); inf = NULL;}
	inf = fopen("f2e.txt", "r");
	if (inf == NULL) {goto BEnd;}
	ouf = fopen("fe.txt", "a+");
	if (ouf == NULL) {goto BEnd;}
	if (!(em = PtrAlloc(eml))) {goto BEnd;}
	mymemset(em, CU8(0), eml);
	if (!(txt = PtrAlloc(txtl))) {goto BEnd;}
	mymemset(txt, CU8(0), txtl);
	do{txt[0] = fgetc(inf);if(Encrypt(lts, ltsl, txt, txtl, em) == C64(1)){goto BEnd;}for(I64 i = C64(0); i < eml; i++){fprintf(ouf, "%02X", em[i]);}} while(txt[0] != (UI8) EOF);
	f = 0;
	goto End;
	BEnd:
	f = 1;
	goto End;
	End:
	if (inf) {fclose(inf); inf = NULL;}
	if (ouf) {fclose(ouf); ouf = NULL;}
	if (em) {PtrNullFree(em); em = NULL;}
	if (lts) {PtrNullFree(lts); lts = NULL;}
	if (txt) {PtrNullFree(txt); txt = NULL;}
	return f;
}
```

Let's try to understand what is happening at a high level:
-  We seem to be reading a key of size `ltsl` into `lts`
-  We read a single character of the input (`txt[0] = fgetc(inf)`) then encrypt it
-  We write the resulting encrypted bytes stored in `em` to a file

## Solving the challenge

We don't actually need to focus on finding the header or the encryption algorithm, we already found the vulnerability.

Indeed, each byte appears to be encrypted independently, making it victim to frequency attacks on large texts.

Luckily for us, the encrypted text: `FileEncrypted` is extremely long, and we are guessing it contains English.

> At this point, I'd seriously encourage you to try this challenge for yourself, it's quite fun.

## Frequency analysis

Before diving too deep into this approach, I identified that out of the 990569 blocks there only existed 93 unique ones. This confirms our previous hypothesis.

The idea behind frequency analysis is that certain letters appear more often in the English language.

Here is the distribution from our data:

![[Pasted image 20241025010914.png]]

The character that appears 18% of the time is likely not `e` but rather `<space>`.

After identifying this first character, I guessed the other ones, based off of English's letter frequency.

Substituting each block for its letter (or a question mark if it was ranked worse than 27), the result was a jumbled mess:

```
```

But at least the spaces look correct!

### Word frequency analysis

The next step involved identifying the most common words. For example one three letter word appears 10K times we can guess it's probably `the`.

This "pins" 3 letters, going through the top 30, picking words that make sens based on their frequency and their impact on other words I was able to identify most of the letters.

A breakthrough occurred when I identified 2 characters that often followed each other as `\r\n`, this made the output much more readable. 

### Solution
After a lot of back and forwards, I was able to identify the text as `THE LORD OF THE RINGS by J. R. R. TOLKIEN`.

A quick `Ctrl+F` for `ECW` in the output reveals the location of the flag (I had to add the opening and closing braces myself)

`ECW{N0wD0n'tB3H4sty,M4st3rM3r14d0c}`
