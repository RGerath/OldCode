/*
Bob Gerath
Assignment 03
Program 1

One of two programs that take roughly the same time to complete
Unmodified priority level
*/

#include <stdio.h>
#include <unistd.h>
#include <string.h>
#include <sys/mman.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

int main(int argc, char *argv[])
{
	int f;
	void *m, *n;



	for (int i=0; i<100000; i++)
	{
		f = open("f1", O_RDWR);

		m = mmap(0, 50000, PROT_READ|PROT_WRITE, MAP_SHARED, f, 0);
		ftruncate(f, 100000);
		n = mmap(0, 100000, PROT_READ|PROT_WRITE, MAP_SHARED, f, 0);

		memcpy(n+50000, m, 50000);

		ftruncate(f, 50000);
		close(f);
		munmap(m, 50000);
		munmap(n, 100000);
	}

	printf("Job 1 done\n");
}