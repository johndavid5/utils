use strict;
use Data::Dump;

my $GBDebug = 0;

my $line = "";
my $line_num = 0;

my $field_num = 1;

my $i;
for( $i=0; $i<$#ARGV; $i++){
	if( $ARGV[$i] =~ /-n/ ){
		$field_num = $ARGV[++$i];			
	}
	elsif( $ARGV[$i] =~ /-dbg/ ){
		$GBDebug = $ARGV[++$i];
	}
}

debug_print(\"\$GBDebug = \"$GBDebug\"...\n");
debug_print(\"\$field_num = \"$field_num\"...\n");

while($line=<STDIN>){

	$line_num++;
	my $map = map_field_boundaries($line);

	debug_print("line[$line_num] = \"" . $line . "\"...\n");
	debug_print("map for line[$line_num] = " . Data::Dump::dump($map) . "...\n");

	my $start = $map->{$field_num}->{'start'};
	my $end = $map->{$field_num}->{'end'};

	debug_print("\$start = \"" . $start . "\"...\n");
	debug_print("\$end = \"" . $end . "\"...\n");

	my $before = substr($line, 0, $start);
	my $target = substr($line, $start, $end-$start );
	my $after = substr($line, $end );

	debug_print("\$before = \"" . $before . "\"...\n");
	debug_print("\$target = \"" . $target . "\"...\n");
	debug_print("\$after = \"" . $after . "\"...\n");

	my $target_commified = commify($target);

	debug_print("\$target_commified = \"" . $target_commified . "\"...\n");

	debug_print("outputting commified line[$line_num] = \"" . $before . $target_commified . $after . "\n");
	print($before . $target_commified . $after );
}

# http://www.perlmonks.org/?node_id=2145
sub commify {
   my $input = shift;
   $input = reverse $input;
   $input =~ s/(\d\d\d)(?=\d)(?!\d*\.)/$1,/g;
   return reverse $input;
}

use constant { 
	IN_SPACE => "IN_SPACE",
	BETWEEN_SPACE => "BETWEEN_SPACE",
};

# Use finite-state machine to map the field boundaries.
sub map_field_boundaries {

	my ($input) = @_;

	my $sWho = "map_field_boundaries";

	debug_print($sWho . "(): input = \"" . $input . "\"...\n");

	my $len = length($input);

	my $state = BETWEEN_SPACE;
	my $field_num = 1;
	my $field_start = 0;
	my $field_end = -1;
	my $c;
	my $field_map = {};

	for(my $i=0; $i<$len; $i++){

		$c = substr($input, $i, 1);
		
		debug_print($sWho . "(): $i: input[$i] = '" . $c . "'...\n");
		debug_print($sWho . "(): $i: string thus far = \"" . substr($input, 0, $i+1) . "\"...\n");
		debug_print($sWho . "(): $i: BEFORE: state = \"$state\"...\n");

		if( $state eq BETWEEN_SPACE ){	
			if( $c eq " "){	
				$field_end = $i;
				$field_map->{$field_num} = {"start"=>$field_start, "end"=>$field_end}; 

				debug_print($sWho . "(): $i: From BETWEEN_SPACE to IN_SPACE...Add entry...\$field_map->{$field_num} = " . Data::Dump::dump($field_map->{$field_num}) . "...\n");

				$state = IN_SPACE;	
			}
		}
		elsif( $state eq IN_SPACE ){	
			if( $c ne " "){	
				$field_num++;
				$field_start = $i;
				debug_print($sWho . "(): $i: From IN_SPACE to BETWEEN_SPACE...field_num incremented to $field_num...\n");
				$state = BETWEEN_SPACE;	
			}
		}

		debug_print($sWho . "(): $i: AFTER: state = \"$state\"...\n");
	}

	return $field_map;
}

sub debug_print {
	my ($input)=@_;
	if( $GBDebug ){
		print STDERR ("\@\@\@>" . $input); 
	}
}
