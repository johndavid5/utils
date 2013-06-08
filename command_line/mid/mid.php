<?php

	$sFileName="";
	$pFile=null;

	$startLineNumber=1;
	$endLineNumber =-1;

	$lineNumbersShow=FALSE;
	$lineNumbersLeadingZeroes=FALSE;
	$lineNumbersFieldWidth=-1;

	$verbose=FALSE;

	$iTemp;
	$iWhich=0;
	$iWhere=-1;
	for($i=1; $i<count($argv); $i++)
	{
		if(strcmp($argv[$i], "-?")==0 || strcmp($argv[$i], "-help")==0 || strcmp($argv[$i], "--help")==0)
		{
			printHelp(STDERR);
			return 1;
		}
		if($argv[$i][0]=='-' && strlen($argv[$i]) >= 2 && $argv[$i][1]=='n')
		{
			$lineNumbersShow=TRUE;

			if(strlen($argv[$i]) >= 3 && $argv[$i][2]=='0')
			{
				$lineNumbersLeadingZeroes=TRUE;		
				$iWhere = 3; /* The "-n" in argv[i] is followed by a zero, so begin looking for field width at argv[i][3]. */
			}
			else
			{
				$iWhere = 2; /* The "-n" in argv[i] is not followed by a zero, so begin looking for field width at argv[i][2]. */
			}

			/* Look for field width... */
			if(sscanf(substr($argv[$i], $iWhere), "%d", $iTemp)==1 && $iTemp > 0)
			{
				$lineNumbersFieldWidth = $iTemp;
			}
		}
		else if(strcmp($argv[$i], "-v")==0)
		{
			$verbose=TRUE;
		}
		else if($iWhich >= 0 && $iWhich <= 1 && sscanf($argv[$i], "%d", $iTemp)==1) 
		{
			$iWhich++;
			if($iWhich == 1)
			{
				$startLineNumber = $iTemp;
			}
			else if($iWhich == 2)
			{
				$endLineNumber = $iTemp;
			}
		}
		else
		{
			$sFileName = $argv[$i];
		}
   }

	$lineNumbersFormatString = createLineNumbersFormatString($lineNumbersShow, $lineNumbersLeadingZeroes, $lineNumbersFieldWidth);

	if($verbose)
	{
		fprintf(STDERR,
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
		/* If filename specified, open file for reading. */
   		$pFile = fopen ($sFileName , "r");

		if ($pFile === FALSE)
		{
			fprintf(STDERR, "Error opening file '%s': \"%s\"\n", $sFileName, print_r(error_get_last(), true));
			exit(2);
		}
	}
	else
	{
		/* If filename not specified, read from standard input. */
		$pFile = STDIN; 
	}


	$lineNumber = 0;
    while( ( $mybuff = fgets($pFile) ) !== false )
	{
		if($verbose && false)
		{
			fprintf(STDERR, "\$lineNumber=%d, \$mybuff='%s'\n"
					,$lineNumber ,$mybuff );
		}

		$lineNumber++;

		if($endLineNumber != -1 && $lineNumber > $endLineNumber)
		{
			break;
		}
		
		if($startLineNumber == -1 || $lineNumber >= $startLineNumber) 
		{
			if($lineNumbersShow)
			{
				/* For example, if lineNumber=7
				* fprintf(stdout, "%d: ", lineNumber) prints "7: "
				* fprintf(stdout, "%2d: ", lineNumber) prints " 7: "
				* fprintf(stdout, "%02d: ", lineNumber) prints "07: "
				*/
				fprintf(STDOUT, $lineNumbersFormatString, $lineNumber);
			}

			fwrite(STDOUT, $mybuff);
			//fprintf(STDOUT, $mybuff);
			fflush(STDOUT);
		}
	}

	if( $pFile != STDIN )
	{
		/* If we're not reading from standard input, close the file. */
		//if($verbose) fprintf(STDERR, "Closing file handle...".PHP_EOL);
		fclose($pFile); 
	}


function printHelp($fileHandle)
{
	fprintf($fileHandle, 
	"mid: version 1.0\n".
	"FORMAT: mid <startlinenum> <endlinenum> [-n|-n<width>|-n0<width>] [-v] [filename]\n".
	" <startlinenum>: DEFAULT: 1 (starts at first line of file)\n".
	" <endlinenum>: DEFAULT: -1 (ends at last line of file)\n".
	" -n ==> show line numbers\n".
	" -n<width> ==> show line numbers with specified field width, left padded with spaces, e.g., -n3 prints \"  1\", \"  2\", \"  3\",...\n".
	" -n0<width> ==> show line numbers with specified field width, left padded with zeroes, e.g., -n03 prints \"001\", \"002\", \"003\",...\n".
	" -v ==> verbose - print info to STDERR\n"
	);
	
}/* printHelp() */


/** 
* Create suitable format string for 
*    fprintf(STDOUT, $lineNumbersFormatString, $lineNumber);
*
* Examples:
*  if lineNumbersShow is FALSE, fills in "" (empty string),
*  if lineNumbersShow is TRUE, lineNumbersLeadingZeroes is FALSE and lineNumbersFieldWidth is 3, returns "%3d: "
*  if lineNumbersShow is TRUE, lineNumbersLeadingZeroes is TRUE and lineNumbersFieldWidth is 3, returns "%03d: "
*
* @return the format string
*/
function createLineNumbersFormatString($lineNumbersShow, $lineNumbersLeadingZeroes, $lineNumbersFieldWidth)
{
	$lineNumbersFormatString = "";

	if(!$lineNumbersShow)
	{
		return $lineNumbersFormatString; 
	}

	$lineNumbersFormatString = "%";
	
	if($lineNumbersLeadingZeroes)
	{
		$lineNumbersFormatString .= "0";
	}

	if($lineNumbersFieldWidth >= 1)
	{
		$lineNumbersFormatString .= sprintf("%d", $lineNumbersFieldWidth);
	}

	$lineNumbersFormatString .= "d: ";

	return 	$lineNumbersFormatString;
}
