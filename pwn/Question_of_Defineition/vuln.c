#define WIDTH 10
#define HEIGHT 21
#define H_OVR 3
#define BUFH HEIGHT+H_OVR
#define BUFW WIDTH
#define BUFSIZ BUFH * BUFW
#define MIN(a,b) a<b?a:b
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <sys/time.h>
char global_buf[BUFSIZ / 8];
int run() {
	char local_buf[BUFH][BUFW];
	memset(local_buf, 0, BUFH * BUFW);
	for(;;) {
		puts("plz gief inpt");
		char command = fgetc(stdin);
		if(fgetc(stdin) != '\n') {puts("error"); exit(1);}
		switch(command) {
			case 'i': fgets(global_buf, BUFSIZ / 16, stdin); break;
			case 'o': printf("%s", global_buf); break;
			case 'l': memcpy(global_buf, local_buf, MIN(sizeof(local_buf), strlen((char*) local_buf))); break;
			case 'g': memcpy(local_buf, global_buf, MIN(sizeof(global_buf), strlen(global_buf))); break;
			case 'f': memcpy(local_buf, global_buf, BUFSIZ / 8); break;
			case 'q': return 0;
			default: puts("error"); exit(1);
		}
	}
}
void banner() {puts("some mot message");}
void init() {
    struct timeval timeofday;
	gettimeofday(&timeofday, NULL);
    srand(timeofday.tv_usec);
	setbuf(stdout, NULL);
	banner();
}
int main() {init(); run();}
