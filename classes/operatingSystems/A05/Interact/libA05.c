//Bob Gerath
//Operating Systems
//Assignment 05
//Source code for shared library libA05.so

#include <stdio.h>
#include <stdlib.h>

void f1()
{
	printf("Congratulations for choosing to use the libA05 shared library\n");
}

void f2(char *str)
{
	printf("You have just passed the string \"%s\" to shared function f2()\n", str);
}