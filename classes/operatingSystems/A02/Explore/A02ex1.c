//Bob Gerath
//Assignment 02
//Explore - 1

/*
System does not choose the same virtual address for the first mmap() of every process
*/

#include <stdio.h>
#include <sys/mman.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

int main(int argc, char *argv[])
{
	//open source code file
	int x = open("A02ex1.c", O_RDWR);
	//map file to memory
	void *m = mmap(0, 517, PROT_READ|PROT_WRITE, MAP_SHARED, x, 0);
	//print system-selected memory address
	printf("%p\n", m);
	//unmap file
	munmap(m, 517);
}