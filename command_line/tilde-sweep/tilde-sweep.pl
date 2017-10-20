use strict;
use File::Spec::Functions;

# Turn on auto-flush for STDOUT...
$| = 1;

my $GBDebug = 0;

my $START_DIR = ".";
my $B_DRY_RUN = 0;

for( my $i = 0; $i < scalar(@ARGV); $i++ ){
	if( $ARGV[$i] =~ /-dry/ ){
		$B_DRY_RUN = 1;
	}
	else {
		$START_DIR = $ARGV[$i];
	}
}	

debug_print("$START_DIR = \"" . $START_DIR . "\"...\n");
debug_print("$B_DRY_RUN = \"" . $B_DRY_RUN . "\"...\n");

my $DELETE_COUNT = 0;

do_dir( $START_DIR );

if( $DELETE_COUNT == 0 ){
	print("No tilde files found.\n");
}
else {
	print("Deleted " . $DELETE_COUNT . " tilde file" . ($DELETE_COUNT==1?"":"s") );
	if( $B_DRY_RUN ){
		print("...NOT!\n");
	}
	else {
		print(".\n");
	}
}

sub do_dir {

	my $sWho = "do_dir";

	my ($s_dir, $depth ) = @_;

	if( ! $depth ){
		$depth = 1;
	}
	
	debug_print($sWho . "(): s_dir=\"$s_dir\", depth=\"$depth\"...\n");

	my $dh;

	opendir($dh, $s_dir ) || die "$sWho(): Can't open directory '$s_dir': '$!'";

	my $dir_entry = "";
	my $dir_path = "";
	my $i_count = 0;
	my $s_what = "";

	my $s_prefix = " " x $depth;
	debug_print($sWho . "(): s_prefix=\"$s_prefix\"...\n");

	while($dir_entry = readdir $dh) {

		if( $dir_entry eq "." || $dir_entry eq ".." || $dir_entry eq ".git" || $dir_entry eq "node_modules" ){
			next;
		}

		$i_count++;

		debug_print("$s_prefix" . "$depth.$i_count: dir_entry = \"" . $dir_entry . "\"...\n");

		$dir_path = File::Spec::Functions::catfile( $s_dir, $dir_entry );

		$s_what = "";
		if( -d $dir_path ){
			$s_what = "DIRECTORY";
		}
		elsif( -f $dir_path ){
			$s_what = "FILE";
		}
		else {
			$s_what = "???";
		}
		
		debug_print("$s_prefix" . "$depth.$i_count:   dir_path = \"" . $dir_path . "\" ($s_what)...\n");

		if( -d $dir_path ){
			do_dir( $dir_path, $depth+1 );
		}
		elsif( -f $dir_path ){
			if( $dir_entry =~ /~$/ ){
				print("+ rm $dir_path...");

				if( $B_DRY_RUN ){ 
					$DELETE_COUNT++;
					print ("--> Dry run only, not deleting...\n");
				}
				else {
					if( ! unlink( $dir_path ) ){
						print("--> Trouble deleting \"$dir_path\": \"$!\"\n");
					}
					else {
						$DELETE_COUNT++;
						print("\n");
					}
				}
			}
		}

	}# while($dir_entry = readdir $dh) 

	closedir $dh;

}# do_dir()


sub debug_print {
	my ($msg) = @_;
	if( $GBDebug ){
		print( "\@\@*> " . $msg );
	}
}
