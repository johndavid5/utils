/* Set these two macros to support large files in Linux. */
#define _FILE_OFFSET_BITS 64
#define _LARGEFILE_SOURCE

#include <stdio.h>
#include <string.h>
#include <errno.h>
#include <stdlib.h> /* e.g., malloc() */

#define BUFFER_SIZE 2048
#define CSTR_WIDTH 128

#ifndef TRUE
  #define TRUE 1
#endif

#ifndef FALSE
  #define FALSE 0
#endif

void printHelp(FILE* helpStream);
void createLineNumbersFormatString(char* lineNumbersFormatString/* in-out char buffer */, int lineNumbersShow, int lineNumbersLeadingZeroes, int lineNumbersFieldWidth);

int main(int argc, char* argv[])
{
	char *pFileName=NULL;
	FILE * pFile;

	int startLineNumber=1;
	int endLineNumber =-1;

	int lineNumbersShow=FALSE;
	int lineNumbersLeadingZeroes=FALSE;
	int lineNumbersFieldWidth=-1;

	char lineNumbersFormatString[CSTR_WIDTH];

	int verbose=FALSE;

	int iTemp;
	int iWhich=0;
	int iWhere=-1;
	int i;
	for(i=1; i<argc; i++)
	{
		if(strcmp(argv[i], "-?")==0 || strcmp(argv[i], "-help")==0 || strcmp(argv[i], "--help")==0)
		{
			printHelp(stderr);
			return 1;
		}
		if(argv[i][0]=='-' && argv[i][1]=='n')
		{
			/* e.g., "-n" parses to lineNumbersShow=TRUE, lineNumbersLeadingZeroes=FALSE, lineNumbersFieldWidth=-1
			* "-n2" parses to lineNumbersShow=TRUE, lineNumbersLeadingZeroes=FALSE, lineNumbersFieldWidth=2
			* "-n02" parses to lineNumbersShow=TRUE, lineNumbersLeadingZeroes=TRUE, lineNumbersFieldWidth=2
			*/
			lineNumbersShow=TRUE;

			if(argv[i][2]=='0')
			{
				lineNumbersLeadingZeroes=TRUE;		
				iWhere = 3; /* The "-n" in argv[i] is followed by a zero, so begin looking for field width at &argv[i][3]. */
			}
			else
			{
				iWhere = 2; /* The "-n" in argv[i] is not followed by a zero, so begin looking for field width at &argv[i][2]. */
			}

			/* Look for field width at &argv[i][iWhere]... */
			if(sscanf(&argv[i][iWhere], "%d", &iTemp)==1 && iTemp > 0)
			{
				lineNumbersFieldWidth = iTemp;
			}
		}
		else if(strcmp(argv[i], "-v")==0)
		{
			verbose=TRUE;
		}
		else if(iWhich >= 0 && iWhich <= 1 && sscanf(argv[i], "%d", &iTemp)==1) 
		{
			iWhich++;
			if(iWhich == 1)
			{
				startLineNumber = iTemp;
			}
			else if(iWhich == 2)
			{
				endLineNumber = iTemp;
			}
		}
		else
		{
			pFileName = argv[i];
		}
   }

	createLineNumbersFormatString(lineNumbersFormatString/* in-out */, lineNumbersShow, lineNumbersLeadingZeroes, lineNumbersFieldWidth);

	if(verbose)
	{
		fprintf(stderr,
		"mid: startLineNumber=%d, endLineNumber=%d, fileName='%s',\n"
		"lineNumbersShow=%s, lineNumbersLeadingZeroes=%s, lineNumbersFieldWidth=%d, lineNumbersFormatString='%s',\n"
	    "verbose=%s\n"
			,startLineNumber ,endLineNumber ,((pFileName!=NULL)?(pFileName):(""))
				,(lineNumbersShow)?("TRUE"):("FALSE")
				,(lineNumbersLeadingZeroes)?("TRUE"):("FALSE")
				,lineNumbersFieldWidth
				,lineNumbersFormatString
			,(verbose)?("TRUE"):("FALSE")
		);
	}

	if(pFileName != NULL)
	{
		/* If filename specified, open file for reading. */
   		pFile = fopen (pFileName , "r");

		if (pFile == NULL)
		{
			fprintf(stderr, "Error opening file '%s': \"%s\"\n", pFileName, strerror(errno));
			return 2;
		}
	}
	else
	{
		/* If filename not specified, read from standard input. */
		pFile = stdin; 
	}

	char mybuff[BUFFER_SIZE];

	int lineNumber = 1;
	int beginningOfNewLine = TRUE;
    while( fgets (mybuff , BUFFER_SIZE, pFile) != NULL )
	{
		if(endLineNumber != -1 && lineNumber > endLineNumber)
		{
			break;
		}
		
		if(startLineNumber == -1 || lineNumber >= startLineNumber) 
		{
			if(lineNumbersShow && beginningOfNewLine)
			{
				/* For example, if lineNumber=7
				* fprintf(stdout, "%d: ", lineNumber) prints "7: "
				* fprintf(stdout, "%2d: ", lineNumber) prints " 7: "
				* fprintf(stdout, "%02d: ", lineNumber) prints "07: "
				*/
				fprintf(stdout, lineNumbersFormatString, lineNumber);
			}

			fputs(mybuff, stdout);
			fflush(stdout);
		}

		int len = strlen(mybuff);

		/* This should detect UNIX-style line endings "\n" and DOS-style line endings: "\r\n"
		* (since in both cases, the _last_ character in the line will be "\n"),
		* as well as MAC-style line endings "\r".
		*/
		if(mybuff[len-1] == '\n' || mybuff[len-1] == '\r')
		{
			lineNumber++;
			beginningOfNewLine = TRUE;
		}
		else
		{
			beginningOfNewLine = FALSE;
		}
	}

	if( pFile != stdin )
	{
		fclose(pFile); /* If we're not reading from standard input, close the file. */
	}

	return 0;

}/* main() */

void printHelp(FILE* fileStream)
{
	fprintf(fileStream, 
	"mid: version 1.0\n"
	"FORMAT: mid <startlinenum> <endlinenum> [-n|-n<width>|-n0<width>] [-v] [filename]\n"
	" <startlinenum>: DEFAULT: 1 (starts at first line of file)\n"
	" <endlinenum>: DEFAULT: -1 (ends at last line of file)\n"
	" -n ==> show line numbers\n"
	" -n<width> ==> show line numbers with specified field width, left padded with spaces, e.g., -n3 prints \"  1\", \"  2\", \"  3\",...\n"
	" -n0<width> ==> show line numbers with specified field width, left padded with zeroes, e.g., -n03 prints \"001\", \"002\", \"003\",...\n"
	" -v ==> verbose - print info to STDERR\n"
	);
	
}/* printHelp() */

/** 
* Create suitable format string for 
*    fprintf(stdout, lineNumbersFormatString, lineNumber);
* copying into lineNumbersFormatString char buffer.
*
* Examples:
* if lineNumbersShow is FALSE, fills in "" (empty string),
* if lineNumbersShow is TRUE, lineNumbersLeadingZeroes is FALSE and lineNumbersFieldWidth is 3, returns "%3d: "
* if lineNumbersShow is TRUE, lineNumbersLeadingZeroes is TRUE and lineNumbersFieldWidth is 3, returns "%03d: "
*/
void createLineNumbersFormatString(char* lineNumbersFormatStringBuffer/* in-out char buffer */, int lineNumbersShow, int lineNumbersLeadingZeroes, int lineNumbersFieldWidth)
{
	strcpy(lineNumbersFormatStringBuffer, "");

	if(!lineNumbersShow)
	{
		return;
	}

	strcat(lineNumbersFormatStringBuffer, "%");
	
	if(lineNumbersLeadingZeroes)
	{
		strcat(lineNumbersFormatStringBuffer, "0");
	}

	if(lineNumbersFieldWidth >= 1)
	{
		sprintf(&lineNumbersFormatStringBuffer[strlen(lineNumbersFormatStringBuffer)], "%d", lineNumbersFieldWidth);
	}

	strcat(lineNumbersFormatStringBuffer, "d: ");
}
