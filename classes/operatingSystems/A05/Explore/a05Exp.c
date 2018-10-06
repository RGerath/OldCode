//Bob Gerath
//Operating Systems
//Assignment 05
//Explore
//A simple program to interpret an operation in one of two different ways

#include <stdio.h>
#include <stdlib.h>
#include <dlfcn.h>

void usage(char *str);

int main(int argc, char *argv[])
{
	if(argc != 3)
	{
		usage("Wrong number of arguments");
	}

	int in = strtol(argv[1], NULL, 10);
	if(in == 0)
	{
		usage("Invalid numerical argument");
	}

	void *handle = dlopen(argv[2], RTLD_NOW);
	if(handle==NULL)
	{
		usage("Error opening library argument");
	}

	int (*absolute)(int) = dlsym(handle, "absolute");

	printf("Absolute value of %i is %i\n", in, absolute(in));
}

void usage(char *str)
{
	printf("Error: %s\n", str);
	printf("Run this program with exactly 2 arguments.\n");
	printf("Argument 1: Non-Zero Integer\n");
	printf("Negative arguments should be entered with the first value for Argument 2\n");
	printf("Positive arguments should be entered with the second value for Argument 2\n");
	printf("Argument 2: Library Name\n");
	printf("libAbsolutePlus.so\nlibAbsoluteMinus.so\n");
	exit(0);
}