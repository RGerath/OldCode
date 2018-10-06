all: libAbsolutePlus.o libAbsolutePlus.so libAbsoluteMinus.o libAbsoluteMinus.so driver

libAbsolutePlus.o: libAbsolutePlus.c
	gcc -c -fpic libAbsolutePlus.c

libAbsolutePlus.so: libAbsolutePlus.o
	gcc -shared -o libAbsolutePlus.so libAbsolutePlus.o

libAbsoluteMinus.o: libAbsoluteMinus.c
	gcc -c -fpic libAbsoluteMinus.c

libAbsoluteMinus.so: libAbsoluteMinus.o
	gcc -shared -o libAbsoluteMinus.so libAbsoluteMinus.o

driver: libAbsolutePlus.so libAbsoluteMinus.so a05Exp.c
	gcc -I. -L. -ldl -o driver a05Exp.c