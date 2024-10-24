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