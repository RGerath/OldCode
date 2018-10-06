//Bob Gerath
//Assignment 01
//A simple implementation of the cp command

/*Command Line Arguments
source: a file to be read and copied onto the destination file
destin: the destination file to copied onto
*/

#include <string.h>

int main(int argc, char *argv[])
{
	char source[] = argv[1];
	char destin[] = argv[2];
	
	
	
	//source is not readable
	printf("Error: Source not readable");
	return 1;
	
	//all is well
	return 0;
}