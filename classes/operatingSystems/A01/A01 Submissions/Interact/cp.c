//Bob Gerath
//Assignment 01
//A simple implementation of the cp command

	/* To do:

	Patch FreeBSD kernel:
	-patch: add a system call that prints a message
	-compose a program w/ makefile to demonstrate this syscall
	*/

#include <string.h>
#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>

#define BUF_SIZE 5000

int main(int argc, char *argv[])
{
	//fail if command lacks source and destin arguments
	if (argc < 3)
	{
		printf("Not enough arguments\n");
		exit(-1);
	}

	//open source file to fSource descriptor
	int fSource = open(argv[1], O_RDONLY);
	//fail if given source is an invalid file path
	if (fSource == -1)
	{
		printf("Error: Invalid source file\n");
		exit(-1);
	}

	//establish buffer
	char buf[BUF_SIZE];

	ssize_t rValue, wValue;

	//open destin as a directory
	int fDestin1 = open(argv[2], O_DIRECTORY);
	if (fDestin1 == -1)
	{
		//open destination file to write or create
		int fDestin2 = open(argv[2], O_WRONLY | O_CREAT, 0644);
		
		//copy source to destin
		while ((rValue = read(fSource, &buf, BUF_SIZE)) > 0)
		{
			wValue = write(fDestin2, &buf, (ssize_t) rValue);
		}

		close(fDestin2);
	}
	else
	{
		//strip file name from source file path
		char *sourcePath, *destinPath;

		//define sourcePath as argv[1] if '/' does not occur
		if (sourcePath=strrchr(argv[1],'/'))
		{
			sourcePath++;
		}
		else
		{
			sourcePath = argv[1];
		}

		if (*argv[2]+strlen(argv[2])-1 == '/')
		{
			destinPath = argv[2];
		}
		else
		{
			destinPath = strcat(argv[2],"/");
		}

		strcat(destinPath, sourcePath);

		printf("%s\n", destinPath);

		int fDestin3 = open(destinPath, O_WRONLY | O_CREAT, 0644);
		while ((rValue = read(fSource, &buf, BUF_SIZE)) > 0)
		{
			wValue = write(fDestin3, &buf, (ssize_t) rValue);
		}

		close(fDestin3);
	}

	close(fDestin1);
	close(fSource);
}