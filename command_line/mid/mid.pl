use strict;

use constant TRUE => 1;
use constant FALSE => 0;

my $sFileName="";
my $pFile=undef();

my $startLineNumber=1;
my $endLineNumber =-1;

my $lineNumbersShow=FALSE;
my $lineNumbersLeadingZeroes=FALSE;
my $lineNumbersFieldWidth=-1;
my $lineNumbersFormatString="";

my $lineNumber;
my $mybuff;

my $verbose=FALSE;

my $iWhich=0;
my $iWhere=-1;

my $i;

#foreach $i (0 .. $#ARGV) {
for($i=0; $i<=$#ARGV; $i++)
{
	#print("\$ARGV[$i] = " . $ARGV[$i] . "\n");

	if( $ARGV[$i] eq "-?" || $ARGV[$i] eq "-help" || $ARGV[$i] eq "--help" )
	{
		printHelp( *STDERR );
		return 1;
	}

	if( substr( $ARGV[$i], 0, 1 ) eq '-' && substr( $ARGV[$i], 1, 1 ) eq 'n' )
	{
		$lineNumbersShow=TRUE;

		if( substr( $ARGV[$i], 2, 1 ) eq '0' )
		{
			$lineNumbersLeadingZeroes=TRUE;		
			$iWhere = 3; # /* The "-n" in argv[i] is followed by a zero, so begin looking for field width at argv[i][3]. */
		}
		else
		{
			$iWhere = 2; # /* The "-n" in argv[i] is not followed by a zero, so begin looking for field width at argv[i][2]. */
		}

		# /* Look for field width... */
		if( substr($ARGV[$i], $iWhere) =~ /(\d+)/ )
		{
			$lineNumbersFieldWidth = $1;
		}
	}
	elsif( $ARGV[$i] eq  "-v" )
	{
		$verbose=TRUE;
	}
	elsif( $iWhich >= 0 && $iWhich <= 1 && $ARGV[$i] =~ /(\d+)/ )
	{
			$iWhich++;
			if($iWhich == 1)
			{
				$startLineNumber = $1;
			}
			elsif($iWhich == 2)
			{
				$endLineNumber = $1;
			}
	}
	else
	{
		$sFileName = $ARGV[$i];
	}

}# /* for($i=1; $i<count($argv); $i++) */


	$lineNumbersFormatString = createLineNumbersFormatString( $lineNumbersShow, $lineNumbersLeadingZeroes, $lineNumbersFieldWidth );

	if($verbose)
	{
		printf(STDERR
		"mid: startLineNumber=%d, endLineNumber=%d, fileName='%s', lineNumbersShow=%s, lineNumbersLeadingZeroes=%s, lineNumbersFieldWidth=%d, lineNumbersFormatString='%s', verbose=%s\n"
			,$startLineNumber
			,$endLineNumber
			,$sFileName
			,($lineNumbersShow)?("TRUE"):("FALSE")
			,($lineNumbersLeadingZeroes)?("TRUE"):("FALSE")
			,$lineNumbersFieldWidth
			,$lineNumbersFormatString
			,($verbose)?("TRUE"):("FALSE")
		);
	}


	if($sFileName)
	{
		#/* If filename specified, open file for reading. */
		unless ( open ($pFile, "<",  $sFileName) )
		{
			printf(STDERR "Error opening file '%s': \"%s\"\n", $sFileName, $!);
			exit(2);
		}
	}
	else
	{
		#/* If filename not specified, read from standard input. */
		$pFile = *STDIN; 
	}


	$lineNumber = 0;
	while( $mybuff = <$pFile> )
	{
		if($verbose && FALSE)
		{
			printf(STDERR "\$lineNumber=%d, \$mybuff='%s'\n"
					,$lineNumber ,$mybuff );
		}

		$lineNumber++;

		if($endLineNumber != -1 && $lineNumber > $endLineNumber)
		{
			last;
		}
		
		if($startLineNumber == -1 || $lineNumber >= $startLineNumber) 
		{
			if($lineNumbersShow)
			{
				#/* For example, if lineNumber=7
				#* fprintf(stdout, "%d: ", lineNumber) prints "7: "
				#* fprintf(stdout, "%2d: ", lineNumber) prints " 7: "
				#* fprintf(stdout, "%02d: ", lineNumber) prints "07: "
				#*/
				printf(STDOUT $lineNumbersFormatString, $lineNumber);
			}

			#fwrite(STDOUT, $mybuff);
			#fprintf(STDOUT, $mybuff);
			#fflush(STDOUT);
			print( STDOUT $mybuff );
		}
	}

	if( $pFile != *STDIN )
	{
		#/* If we're not reading from standard input, close the file. */
		#printf(STDERR "Closing file handle..."."\n");
		close($pFile); 
	}


sub printHelp
{
	my ($filehandle) = @_;

	printf($filehandle
	"mid: version 1.0\n".
	"FORMAT: mid <startlinenum> <endlinenum> [-n|-n<width>|-n0<width>] [-v] [filename]\n".
	" <startlinenum>: DEFAULT: 1 (starts at first line of file)\n".
	" <endlinenum>: DEFAULT: -1 (ends at last line of file)\n".
	" -n ==> show line numbers\n".
	" -n<width> ==> show line numbers with specified field width, left padded with spaces, e.g., -n3 prints \"  1\", \"  2\", \"  3\",...\n".
	" -n0<width> ==> show line numbers with specified field width, left padded with zeroes, e.g., -n03 prints \"001\", \"002\", \"003\",...\n".
	" -v ==> verbose - print info to STDERR\n"
	);
	
}#/* printHelp() */

#/** 
#* Create suitable format string for 
#*    fprintf(STDOUT, $lineNumbersFormatString, $lineNumber);
#*
#* Examples:
#*  if lineNumbersShow is FALSE, fills in "" (empty string),
#*  if lineNumbersShow is TRUE, lineNumbersLeadingZeroes is FALSE and lineNumbersFieldWidth is 3, returns "%3d: "
#*  if lineNumbersShow is TRUE, lineNumbersLeadingZeroes is TRUE and lineNumbersFieldWidth is 3, returns "%03d: "
#*
#* @return the format string
#*/
sub createLineNumbersFormatString
{
	my ($lineNumbersShow, $lineNumbersLeadingZeroes, $lineNumbersFieldWidth) = @_;

	my $lineNumbersFormatString = "";

	if( ! $lineNumbersShow )
	{
		return $lineNumbersFormatString; 
	}

	$lineNumbersFormatString = "%";
	
	if( $lineNumbersLeadingZeroes )
	{
		$lineNumbersFormatString .= "0";
	}

	if( $lineNumbersFieldWidth >= 1 )
	{
		$lineNumbersFormatString .= sprintf("%d", $lineNumbersFieldWidth);
	}

	$lineNumbersFormatString .= "d: ";

	return 	$lineNumbersFormatString;
}

