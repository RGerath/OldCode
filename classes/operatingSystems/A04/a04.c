//Bob Gerath
//Operating Systems
//Assignment 04
//A simple shell

/*
fork(2)
exec(3)
wait(2)
fgets(3)
strtok(3)
close(2)
dup(2)
pipe(2)
*/

#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <fcntl.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include <errno.h>

int prompt(char *envp[]);
int forkexec(int argCount, char *argStrings[], char *envp[]);


int main(int argc, char *argv[], char *envp[])
{
	int x = 0;
	while(x<1)
	{
		x = prompt(envp);
	}
	exit(0);
}


//user prompt, accepts input to pass to execute()
int prompt(char *envp[])
{
	int retval = 0;
	char in[100000];
	char *comStrings[2];
	int argCount[2] = {0,0};
	char *argStrings[2][10];
	char *p;
	char *token;

	//display shell-prompt symbol
	printf("$ ");

	//read from stdin
	fgets(in, 100000, stdin);

	//check if '|' occurs in input
	p = strchr(in, '|');

	if(p==NULL)
	{
		//execute following if pipes are not present:

		//set comStrings[0] to the single command, removing trailing newline
		comStrings[0]=strtok(in, "\n");

		//place space-separated tokens into argStrings array
		int i=0;
		token = strtok(comStrings[0], " ");
		while(token != NULL)
		{
			argStrings[0][i] = token;
			argCount[0]++;
			i++;
			token = strtok(NULL, " ");
		}

		//execute commands
		if(strcmp(argStrings[0][0], "exit")==0)
		{
			retval=1;
		}
		else
		{
			//argStrings[0] should now be an array containing a command and some arguments
			retval = forkexec(argCount[0], argStrings[0], envp);
		}
	}
	else
	{
		//execute following if pipes are present:

		//define comStrings[0] and comStrings[1]
		token = strtok(in, " | ");
		comStrings[0] = token;
		token = strtok(NULL, " | ");
		comStrings[1] = token;

		//define argStrings[0] and argStrings[1] arrays of commands and args
	}

	//if retval is ever less than 0, the last command returned under error conditions
	if (retval < 0)
	{
		printf("Error: command execution failed\n");
		exit(-1);
	}

	//return (retval will be 1 if the last command was 'exit' or ctrl-d)
	return retval;
}


int forkexec(int argCount, char *argStrings[], char *envp[])
{
	pid_t child_pid;
	int exitValue;
	child_pid = fork();

	if(child_pid == 0)
	{
		//this is the child thread, and should execute the commands passed to execfork()
		char *ar, *al;
		for(int i=1; i<argCount; i++)
		{
			//check if redirectional arrows occur in this argument
			ar = strchr(argStrings[i], '>');
			al = strchr(argStrings[i], '<');
			if(&ar == &argStrings[i])
			{
				//the remainder of this argument should be written by the command's output
				int outArg = open(argStrings[i]+1, O_RDWR);
				//close stdout and open this argument's path in its place
				dup2(outArg, 1);
				close(outArg);
			}
			if(&al == &argStrings[i])
			{
				//the remainder of this argument should read by the command's input
				int inArg = open(argStrings[i]+1, O_RDWR);
				//close stdin and reopen this argument's path in its place
				dup2(inArg, 0);
				close(inArg);
			}
		}

		if(execve(argStrings[0], argStrings, envp)==-1)
		{
			perror("execve error");
			exit(-1);
		}

		exit(0);
	}
	else
	{
		//this is the parent thread, and should wait on its child
		wait(&exitValue);
		printf("\n");
		return exitValue;
	}
}