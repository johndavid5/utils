#include <windows.h>

#include <stdio.h>
#include <string.h>

#include <string>
#include <iostream>
using namespace std;

#define CF_TEXT 1 
// Text format. Each line ends with a carriage return/linefeed (CR-LF) combination.
// A null character signals the end of the data. Use this format for ANSI text.
// http://msdn.microsoft.com/en-us/library/windows/desktop/ff729168(v=vs.85).aspx

#define CF_UNICODETEXT 13 
// Unicode text format. Each line ends with a carriage return/linefeed (CR-LF) combination. A null character signals the end of the data.
 
//#define CF_TCHAR CF_UNICODETEXT
#define CF_TCHAR CF_TEXT

const char* APP_NAME = "clipboard";

const char* VERSION = "1.0";

void printFormat( ostream* op ){
	*op << APP_NAME << " " << VERSION << "\n";
	*op << "FORMAT:\n";
   	*op << " " << APP_NAME << " get" << "\n"; 
   	*op << " " << "-or-" << "\n";
    *op << " " << APP_NAME << " set <text>" << endl;
}


void putToClipBoard( const std::string& text ){
	int size = ( lstrlen( text.c_str() ) + 1 ) * sizeof(char);
	HGLOBAL hGlobal = GlobalAlloc( GHND | GMEM_SHARE, size );
    PTSTR pGlobal = (PTSTR) GlobalLock( hGlobal ); 
	lstrcpy( pGlobal, text.c_str() ); 
	GlobalUnlock( hGlobal);

	OpenClipboard(NULL);
	EmptyClipboard();
	SetClipboardData( CF_TCHAR, hGlobal );
	CloseClipboard();
}/* putToClipBoard() */

std::string getFromClipBoard(){ 
	OpenClipboard( NULL );

	HGLOBAL hGlobal;
    PTSTR pGlobal;
    //PTSTR pText ;
	std::string textOut;

	if( hGlobal = GetClipboardData( CF_TCHAR ) ){
		pGlobal = (PTSTR) GlobalLock( hGlobal );
		//pText = malloc( GlobalSize( hGlobal ));
		//lstrcpy( pText, pGlobal );
		textOut = pGlobal;
	}

	CloseClipboard();

	return textOut;

}/* getFromClipBoard() */

int main(int argc, char** argv){


	std::string leVerb="";
	std::string leText="";
	bool debug = false;
	int iSleep = 0;
	
	for(int i=1; i<argc; i++){
		if( strcmp(argv[i], "get") == 0 ){
			leVerb = "get";
		}
		else if( strcmp(argv[i], "set") == 0 ){
			leVerb = "set";
			if( i+1 < argc ){
				leText = argv[++i];
			}
		}
		else if( strcmp( argv[i], "-dbg") == 0 ){ 
			debug = true;
		}
		else if( strcmp( argv[i], "-sleep") == 0 && i+1<argc ){ 
			int iTemp;
			if( sscanf( argv[++i], "%d", &iTemp) == 1 ){
				iSleep = iTemp;
			}
		}
	}

	if( debug ){
		cout << APP_NAME << ": leVerb = '" << leVerb << "'" << endl;
		cout << APP_NAME << ": leText = '" << leText << "'" << endl;
		cout << APP_NAME << ": iSleep = " << iSleep << endl;
	}

	if( leVerb.length() == 0 ){
		printFormat( &cerr );
		exit(1);
	}

	if( leVerb != "set" && leVerb != "get" ){
		printFormat( &cerr );
		exit(1);
	}

	if( leVerb == "set" && leText.length() == 0 ){
		printFormat( &cerr );
		exit(1);
	}

	if( leVerb == "set" ){
		putToClipBoard( leText );
		cout << "set clipboard_text=\"" << leText << "\"" << endl;
	}
	else if( leVerb == "get" ){
		leText = getFromClipBoard();
		cout << "got clipboard_text=\"" << leText << "\"" << endl;
	}

	if( iSleep > 0 ){
		cout << "Sleeping for " << iSleep << " milli-second" << (iSleep==1?"":"s") << "..." << endl;
		Sleep(iSleep);
	}

	cout << "Hasta la vista, Baby..." << endl;

	exit(0);

}/* main() */

