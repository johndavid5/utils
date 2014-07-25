/* Set these two macros to support large files in Linux. */
#define _FILE_OFFSET_BITS 64
#define _LARGEFILE_SOURCE

#include <stdio.h>
#include <string.h>
#include <errno.h>
#include <stdlib.h> /* e.g., malloc() */

#include <string>
#include <iostream>
#include <ostream>
#include <sstream>
#include <iomanip> /* e.g., setfill(), setwidth()... */
using namespace std;

//#define BUFFER_SIZE 2048
const int BUFFER_SIZE = 2048;

//const int DEFAULT_NUM_LINES_PER_SEGMENT = 100000;
const int DEFAULT_SEG_NUMBER_WIDTH = 3;

const char* PROG_NAME = "seg";
const char* VERSION= "1.1A";

#ifndef TRUE
  #define TRUE 1
#endif


#ifndef FALSE
  #define FALSE 0
#endif


void printHelp(ostream* pStream);

void splitFileName( string sFileName, string* psFileSansSuffixOut, string* psFileSuffixOut );

string getSegFileName( int iSegmentNumber, int iSegNumberWidth, string sFileSansSuffix, string sFileSuffix ); 

int main(int argc, char* argv[])
{
	/* Get C and C++ I/O to play together nicely... */
	cout.sync_with_stdio(true);

	//char *pFileName=NULL;
	string sFileName = "";
	FILE *pFile;

	//int iNumLinesPerSegment = DEFAULT_NUM_LINES_PER_SEGMENT;
	int iNumLinesPerSegment = -1;
	int iSegNumberWidth = DEFAULT_SEG_NUMBER_WIDTH;
	
	int verbose=FALSE;

	int iTemp;
	int i;
	for(i=1; i<argc; i++)
	{
		if(strcmp(argv[i], "-?")==0 || strcmp(argv[i], "-help")==0 || strcmp(argv[i], "--help")==0)
		{
			printHelp(&cerr);
			return 1;
		}
		else if(strcmp(argv[i], "-v")==0)
		{
			verbose=TRUE;
		}
		else if( (strcmp(argv[i], "-n") == 0 || strcmp(argv[i], "-N") == 0) && i+1 < argc )
		{
			i++;
			if( sscanf( argv[i], "%d", &iTemp ) == 1 ){
				iNumLinesPerSegment = iTemp;
			}
		}
		else if( (strcmp(argv[i], "-w") == 0 || strcmp(argv[i], "-W") == 0) && i+1 < argc )
		{
			i++;
			if( sscanf( argv[i], "%d", &iTemp ) == 1 ){
				iSegNumberWidth = iTemp;
			}
		}
		else
		{
			sFileName = argv[i];
		}
	}

	if(verbose)
	{
		cerr << PROG_NAME << ": iNumLinesPerSegment = " << iNumLinesPerSegment << ", iSegNumberWidth = " << iSegNumberWidth << ",\n"
		   << "verbose = " << (verbose?"TRUE":"FALSE") << ", sFileName = '" << sFileName << "'..." << endl; 
	}

	if(iNumLinesPerSegment == -1 ){
		cerr << "You must specify number of lines per segment." << endl;
		printHelp( &cerr );
		return 1;
	}

	if(sFileName.length() == 0 ){
		cerr << "You must specify a filename." << endl;
		printHelp( &cerr );
		return 1;
	}

	string sFileSansSuffix;
	string sFileSuffix;
	splitFileName( sFileName, &sFileSansSuffix, &sFileSuffix);

	if(verbose)
	{
		cerr << PROG_NAME << ": sFileSansSuffix = '" << sFileSansSuffix << "', sFileSuffix = '" << sFileSuffix << "'..." << endl;
	}

	//pFile = fopen (sFileName , "r");

	if( verbose ){
		cerr << PROG_NAME << ": Opening file '" << sFileName << "' for reading..." << endl;
	}

	/* Use Windows-specific _fsopen() to open file for reading with shared access... */
	pFile = _fsopen( sFileName.c_str(), "r", _SH_DENYNO );
	if (pFile == NULL)
	{
		cerr << PROG_NAME << ": Error opening file '" << sFileName << "' for reading: \"" << strerror(errno) << "\"" << endl;
		return 2;
	}


	int c;
	int cPrevious;

	long iByteCount = 0;
	long iSegmentByteCount = 0;

	long iLineNumber = 1;
	long iSegmentLineNumber = 1;

	int iSegmentNumber = 1;
	FILE* pSegFile = NULL;

	string sSegFileName;

	sSegFileName = getSegFileName( iSegmentNumber, iSegNumberWidth, sFileSansSuffix, sFileSuffix );
	if( verbose ){
		cerr << PROG_NAME << ": Opening file '" << sSegFileName << "' for writing..." << endl;
	}
	pSegFile = _fsopen( sSegFileName.c_str(), "w", _SH_DENYNO );
	if (pSegFile == NULL)
	{
		cerr << PROG_NAME << ": Error opening file '" << sSegFileName << "' for writing: \"" << strerror(errno) << "\"" << endl;
		return 2;
	}

	while( ( c = fgetc( pFile ) ) != EOF ){

		iByteCount++;
		iSegmentByteCount++;

		fputc( c, pSegFile );

		if( c == '\n' ){ // Works for UNIX-style "\n" end-of-line or Windows-style "\r\n" end-of-line.		
						 // Fancier logic is required to handle old MAC-style "\r" end-of-line as well...
			iLineNumber++;	
			iSegmentLineNumber++;	

			if( iSegmentLineNumber > iNumLinesPerSegment ){

				if( verbose ){
					cerr << PROG_NAME << ": Wrote " << (iSegmentLineNumber-1) << " lines and " << iSegmentByteCount << " bytes to '" << sSegFileName << "'..." << endl; 
				}

				fclose( pSegFile );

				iSegmentNumber++;

				sSegFileName = getSegFileName( iSegmentNumber, iSegNumberWidth, sFileSansSuffix, sFileSuffix );

				if( verbose ){
					cerr << PROG_NAME << ": Opening file '" << sSegFileName << "' for writing..." << endl;
				}
				pSegFile = _fsopen( sSegFileName.c_str(), "w", _SH_DENYNO );
				if (pSegFile == NULL)
				{
					cerr << PROG_NAME << ": Error opening file '" << sSegFileName << "' for writing: \"" << strerror(errno) << "\"" << endl;
					return 2;
				}

				iSegmentLineNumber = 1;	
				iSegmentByteCount = 0;	
			}
		}/* if( c == '\n' ) */

	}/* while( ( c = fgetc( pFile ) ) != EOF ) */

	if( verbose ){
		cerr << PROG_NAME << ": Wrote " << (iSegmentLineNumber-1) << " lines and " << iSegmentByteCount << " bytes to '" << sSegFileName << "'..." << endl; 
	}

	fclose( pSegFile );


	if( verbose ){
		cerr << PROG_NAME << ": Wrote total " << (iLineNumber-1) << " lines and total " << iByteCount << " bytes to segment files...." << endl;
	}
	fclose( pFile );

}/* main() */

void splitFileName( string sFileName, string* psFileSansSuffixOut, string* psFileSuffixOut ){

	size_t pos = sFileName.rfind(".");

	if( pos != std::string::npos ){
		*psFileSansSuffixOut = sFileName.substr( 0, pos );
		*psFileSuffixOut = sFileName.substr( pos+1 );
	}
	else{
		*psFileSansSuffixOut = sFileName;
		*psFileSuffixOut = "";
	}
}

string getSegFileName( int iSegmentNumber, int iSegNumberWidth, string sFileSansSuffix, string sFileSuffix ){ 

	ostringstream ossSegFileName;

	ossSegFileName.str("");
  	ossSegFileName << sFileSansSuffix;
	// setiosflags( ios::right ) : default -- padding on the left
  	ossSegFileName << "-" << std::setw( iSegNumberWidth ) << std::setfill( '0' ) << std::setiosflags( ios::right ) << iSegmentNumber;
  	ossSegFileName << "." << sFileSuffix;

	return ossSegFileName.str();
}



void printHelp(ostream* pStream)
{
	*pStream << PROG_NAME << ", version " << VERSION << "\n"
	<< "FORMAT: " << PROG_NAME << " -n <number_of_lines_per_segment> <filename>\n"
	<< "OPTIONAL ARGS:" << "\n"
	<< "	-v ==> verbose - print info to STDERR" << "\n"
	<< "	-w <seg_number_width> ==> width to left pad the segment number with zero's..." << "\n"
	<< "	      DEFAULT: " << DEFAULT_SEG_NUMBER_WIDTH << endl;

}/* printHelp() */
