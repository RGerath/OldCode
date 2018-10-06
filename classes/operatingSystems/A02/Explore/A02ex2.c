//Bob Gerath
//Assignment 02
//Explore - 2

/*
Determines approximate range of virtual addresses system chooses for mmap()
*/

#include <stdio.h>
#include <unistd.h>
#include <sys/mman.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

int main(int argc, char *argv[])
{
	int x = open("t", O_RDONLY);

	void *least, *greatest, *test;
	test = mmap(0, 5000, PROT_READ|PROT_WRITE, MAP_PRIVATE, x, 0);

	least = test;
	greatest = test;

	close(x);

	munmap(test, 5000);

	//call mmap many times
	for(int i=1; i<1000; i++)
	{
		x = open("t", O_RDONLY);

		test = mmap(0, 5000*i, PROT_READ|PROT_WRITE, MAP_PRIVATE, x, 0);

		if(test<least)
			least=test;
		if(test>greatest)
			greatest=test;

		munmap(test, 5000*i);
		close(x);
	}

	printf("Least:		%p\nGreatest:	%p\n", least, greatest);
}