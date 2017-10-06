/* xtod.c */
/* UNIX-style end-of-line: "\n" becomes DOS-style end-of-line: "\r\n". */

/* 
 * NOTE: This program is smart enough to check that we are 
 * not ALREADY in DOS format.
 */
 

#include <stdio.h>
#include <ctype.h>
#include <string.h>
#include <stdlib.h>
#include <errno.h>

char *GBProgName;
void printFormat(FILE *stream);

int main(int argc, char *argv[])
{
   int c, d;
   int i; 
 
   char *inputFileName; 
   char *outputFileName; 
    
   FILE *inputFilePtr; 
   FILE *outputFilePtr; 

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

   /* c is the character that occured before d. */
   c = -2;
   while ( (d = getc(inputFilePtr))  != EOF )
   {
      if( (d == '\n') && (c != '\r') )
         fprintf(outputFilePtr, "\r\n");
      else
         putc(d, outputFilePtr);

      c = d;
   }

   exit(0);
   return 0;
}


void printFormat(FILE *stream)
{
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
         );
    return;
}
