# -------------------------------------------------------------
#  MSC.BAT -- Set up environment for Microsoft C/C++ 7.0 NMAKE
# -------------------------------------------------------------
# /Zp[n] pack structs on n-byte boundary
# /TP compile all files as .cpp
# /MT link with LIBCMT.LIB

build=./build

# Set bin to the location where you want
# the executable to be plopped into...
bin=.

CC=cl /EHsc /I.

#CFLAGS=-c -DSTRICT -D_CRT_SECURE_NO_WARNINGS -W3 -Zp -Tp 
CFLAGS = -c -DSTRICT -W3 -Zp -Tp 

#CFLAGSMT=-c -DSTRICT -MT -D_CRT_SECURE_NO_WARNINGS -W3 -Zp -Tp 
#CFLAGSMT = -c -DSTRICT -MT -W3 -Zp -Tp 
# MySQL's Flags below...
#CFLAGSMT=-c /MT /Zi /O2 /Ob1 /D NDEBUG -DDBUG_OF
#CFLAGSMT=-c /MT /Zi /O2 /Ob1
CFLAGSMT = -c -DSTRICT -W3 -Zp -Tp 

LINKER=link

# MySQL's GUILIBS...
#GUILIBS = wsock32.lib advapi32.lib user32.lib ws2_32.lib Secur32.lib

#OBJS = tests.obj IrrPassThroughFilterTest.cppunit.obj IrrPassThroughFilter.obj irrXML.obj utils.obj logger.obj 
OBJS = $(build)/seg.obj

$(bin)/seg.exe : $(OBJS)
	$(LINKER) $(GUIFLAGS) -OUT:$(bin)/seg.exe $(OBJS) $(GUILIBS)

$(build)/seg.obj : seg.cpp
     $(CC) /Fo$(build)/seg.obj $(CFLAGSMT) seg.cpp

clean:
	del "$(build)/*.obj" *.exe 

all: ../bin/seg.exe
