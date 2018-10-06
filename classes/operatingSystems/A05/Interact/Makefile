all: libA05.o libA05.so driver

libA05.o: libA05.c
	gcc -c -fpic libA05.c

libA05.so: libA05.o
	gcc -shared -o libA05.so libA05.o

driver: libA05.so libA05.h a05Int.c
	gcc -I. -L. -o driver a05Int.c -lA05