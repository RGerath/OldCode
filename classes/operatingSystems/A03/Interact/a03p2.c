/*
Bob Gerath
Assignment 03
Program 2

Two of two programs that take roughly the same time to complete
Uses the 'nice' system call to finish later than p1
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

	nice(19);

	for (int i=0; i<100000; i++)
	{
		f = open("f2", O_RDWR);

		m = mmap(0, 50000, PROT_READ|PROT_WRITE, MAP_SHARED, f, 0);
		ftruncate(f, 100000);
		n = mmap(0, 100000, PROT_READ|PROT_WRITE, MAP_SHARED, f, 0);

		memcpy(n+50000, m, 50000);

		ftruncate(f, 50000);
		close(f);
		munmap(m, 50000);
		munmap(n, 100000);
	}

	printf("Job 2 done\n");
}