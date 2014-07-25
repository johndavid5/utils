A command-line utility to get and set text from the windows clipboard. 

Examples:
$ clipboard.exe get
$ clipboard.exe set <some_text>

Say you're tired of typing in your username a gazillion times.

Then create a batch file 
   username_to_clipboard.bat
with contents
   clipboard.exe set "myusername@mysite.com"
...and make it into a desktop shortcut.
