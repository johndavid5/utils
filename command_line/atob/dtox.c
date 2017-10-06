/* dtox.c */

/* DOS-style end-of-line: "\r\n" becomes
 * UNIX-style end-of-line: "\n". 
*/

#include <stdio.h>
#include <ctype.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>

char *GBProgName, *GBArgvZero;
void printFormat(FILE *stream);

int main(int argc, char *argv[])
{
   int i;

   char *inputFileName; 
   char *outputFileName; 
    
   FILE *inputFilePtr; 
   FILE *outputFilePtr; 

   int c, d;

   GBArgvZero = argv[0];
   GBProgName = argv[0];
    
   inputFileName = NULL; 
   outputFileName = NULL; 
   for(i=1; i<argc; i++) 
   { 
       if(argv[i][0] == '-') 
       { 
           if(argv[i][1] == '?') 
           { 
               printFormat(stderr); 
               exit(1); 
           } 
           else if(tolower(argv[i][1]) == 'o') 
           { 
               if((i+1) < argc) 
               { 
                  outputFileName = argv[++i]; 
               } 
               else 
               { 
                   printFormat(stderr); 
                   exit(1); 
               } 
           } 
           else if(tolower(argv[i][1]) == 'i') 
           { 
               if((i+1) < argc) 
               { 
                  inputFileName = argv[++i]; 
               } 
               else 
               { 
                   printFormat(stderr); 
                   exit(1); 
               } 
           } 
       } 
       else 
       { 
           inputFileName = argv[i]; 
       } 
 
   } 
   
   if(inputFileName != NULL)
      fprintf(stderr, "input_file = \"%s\".\n", inputFileName);
      
   if(outputFileName != NULL)
      fprintf(stderr, "output_file = \"%s\".\n", outputFileName);
      
   if(inputFileName != NULL) 
   { 
      inputFilePtr = fopen(inputFileName, "rb");
      if ( inputFilePtr == NULL )
      {
          fprintf(stderr, 
             "%s: Can't open file \"%s\" for reading [%d: %s].\n", 
             argv[0], inputFileName, errno, strerror(errno) );
          exit(1);
      }
   }
   else
   {
      inputFilePtr = stdin;
   }
   
   if(outputFileName != NULL) 
   { 
      outputFilePtr = fopen(outputFileName, "wb");
      if ( outputFilePtr == NULL )
      {
          fprintf(stderr, 
             "%s: Can't open file \"%s\" for writing [%d: %s].\n", 
             argv[0], outputFileName, errno, strerror(errno) );
          exit(1);
      }
   }
   else
   {
      outputFilePtr = stdout;
   }

   while ( (c = getc(inputFilePtr))  != EOF )
   {
      if (c == '\r')
      {
         /* We may have a "\r\n" here! */
         if((d = getc(inputFilePtr)) == EOF)
         {
            /* Just output your char and close up shop now. */
            putc(c, outputFilePtr);
            exit(0);
         }
         else if( d == '\n')
         {
            /* We got a live one! */
            putc('\n', outputFilePtr); 
         }
         else
         {
            /* False alarm. */
            fprintf(outputFilePtr, "%c%c", c, d);
         }
      }/* END "if(c == '\r')" */
      else
      {
         putc(c, outputFilePtr);
      }

   }/* END while ( (c = getc(inputFilePtr))  != EOF ) */

   exit(0);
   return 0;

} /* END main() */


void printFormat(FILE *stream)
{
   fprintf(stream, "'%s' Version 1.23\n",
	   GBArgvZero);
   fprintf(stream, 
      "Format: \"%s [<input_file_name>] [-o <output_file_name>]\"",
         GBProgName);
   fprintf(stream,
      "(==> If <input_file_name> is not specified, "
         "input is taken from standard input.\n"
      " ==> If <output_file_name> is not specified, "
         "output goes to standard output.\n"
      " ==> For backwards compatibility, you may also use "
         "\"-i <input_file_name>\" to specify input file.\n"
         " NOTE: In WIN32, you *must* use the -o option or else\n"
         " the output will be in \"text\" mode, meaning they'll\n"
         " translate the \"\\n\" into \"\\r\\n\", and we'll be\n"
         " back where we started, won't we?\n"
         );
    return;
}
