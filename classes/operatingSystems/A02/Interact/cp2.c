//Bob Gerath
//Assignment 02
//A modified implementation of the cp command

/*
TODO

write simple programs demonstrating:
-system doesn't select same vaddress first time a process requests
  memory w/ mmap
-the rough range of virtual addresses the system selects for mmap
  describe methodology for testing vaddress range

*/

#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/mman.h>
#include <fcntl.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>

int main(int argc, char *argv[])
{
	//fail if command lacks source and destin arguments
	if (argc < 3)
	{
		printf("Not enough arguments\n");
		exit(-1);
	}

	//open source file to fSource descriptor
	int fSource = open(argv[1], O_RDWR);
	//fail if given source is an invalid file path
	if (fSource == -1)
	{
		printf("Error: Invalid source file\n");
		exit(-1);
	}

	struct stat fileStat;
	fstat(fSource, &fileStat);

	//length of source file
	off_t len = fileStat.st_size;

	//open destin as a directory
	int fDestin1 = open(argv[2], O_DIRECTORY);
	if (fDestin1 == -1)
	{
		//open destination file to write or create
		int fDestin2 = open(argv[2], O_RDWR | O_CREAT, 0644);
		//truncate destin file to the length of source
		ftruncate(fDestin2, len);
		
		//copy source to destin:
		void *d = mmap(0, len, PROT_READ|PROT_WRITE, MAP_SHARED, fDestin2, 0);
		if (d==MAP_FAILED)
		{
			perror("d mmap");
			exit(-1);
		}
		void *s = mmap(0, len, PROT_READ|PROT_WRITE, MAP_SHARED, fSource, 0);
		if (s==MAP_FAILED)
		{
			perror("s mmap");
			exit(-1);
		}
		memcpy(d, s, len);

		//flush changes
		msync(d, len, MS_SYNC);

		//unmap both files
		munmap(d, len);
		munmap(s, len);

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

		int fDestin3 = open(destinPath, O_RDWR | O_CREAT, 0644);
		//truncate destin file to the length of source
		ftruncate(fDestin3, len);

		//copy source to destin:
		void *d = mmap(0, len, PROT_READ|PROT_WRITE, MAP_SHARED, fDestin3, 0);
		if (d==MAP_FAILED)
		{
			perror("d mmap");
			exit(-1);
		}
		void *s = mmap(0, len, PROT_READ|PROT_WRITE, MAP_SHARED, fSource, 0);
		if (s==MAP_FAILED)
		{
			perror("s mmap");
			exit(-1);
		}
		memcpy(d, s, len);

		//flush changes
		msync(d, len, MS_SYNC);

		//unmap both files
		munmap(d, len);
		munmap(s, len);

		close(fDestin3);
	}

	close(fDestin1);
	close(fSource);
}