#-------------------------------------------------
# CLIPBOARD.MAK make file for Microsoft Visual C++
#-------------------------------------------------
# -------------------------------------------------------------
#  MSC.BAT -- Set up environment for Microsoft C/C++ 7.0 NMAKE
# -------------------------------------------------------------
#
# To compile from Visual C++ Command Prompt:
#    $ nmake /f clipboard.mak 
#

builddir=./build
# Note: Set "bin" to the directory where you want the
# executable to be plopped into...
bin=C:/Users/john.aynedjian/bin
bin=.

CC=cl /I. /EHsc

CFLAGS=-c -DSTRICT -D_CRT_SECURE_NO_WARNINGS -W3 -Zp -Tp 
CFLAGSMT=-c -DSTRICT -D_CRT_SECURE_NO_WARNINGS -MT -W3 -Zp -Tp
LINKER=link
GUIFLAGS=-SUBSYSTEM:windows
CONSOLEFLAGS=-SUBSYSTEM:console
DLLFLAGS=-SUBSYSTEM:windows -DLL
GUILIBS=-DEFAULTLIB:user32.lib gdi32.lib winmm.lib comdlg32.lib comctl32.lib
RC=rc
RCVARS=-r -DWIN32

$(bin)/clipboard.exe : $(builddir)/clipboard.obj $(builddir)/clipboard.res 
	$(LINKER) -OUT:$(bin)/clipboard.exe $(builddir)/clipboard.obj $(builddir)/clipboard.res $(GUILIBS) $(CONSOLEFLAGS)

$(builddir)/clipboard.obj : clipboard.cpp 
     $(CC) /Fo$(builddir)/clipboard.obj $(CFLAGSMT) clipboard.cpp

$(builddir)/clipboard.res : clipboard.rc formview.ico
     $(RC) $(RCVARS) /Fo$(builddir)/clipboard.res clipboard.rc
