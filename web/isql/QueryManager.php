<?php 

/** 
* Parses input into separate "queries"
* delimited by $_queryDelimiter.
*/
class QueryManager
{
	private static $GBDebug=0;

	private $_buffer="";
	private $_position=0;
	private $_length=0;

	private $_queryDelimiter=";";

	public function __construct($input="")
	{
		$this->setInput($input);
	}/* __construct() */

	public function setQueryDelimiter($delimiter)
	{
		$this->_queryDelimiter = $delimiter;
	}/* setQueryDelimiter() */

	public function getQueryDelimiter()
	{
		return ( $this->_queryDelimiter );
	}/* setQueryDelimiter() */

	public static function setDebug($iDebug)
	{
		self::$GBDebug=$iDebug;
	}

	public static function getDebug()
	{
		return self::$GBDebug;
	}

	//function __construct($sQuery)
	//{
	//  $sWho="QueryManager::QueryManager";
	//  $this->load($sQuery);
	//}/* __construct($sQuery) */

	function setInput($input)
	{
		$sWho="QueryManager::setInput";

		$this->debugPrintLn(	__FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(" . "input=\"" . $input . "\")...");
		$this->_buffer = $input;
		$this->_position= 0;
		$this->_length = strlen($input);
	}/* setInput() */

	const IN_QUERY = "IN_QUERY";
	const IN_SINGLE_QUOTE= "IN_SINGLE_QUOTE";
	const IN_DOUBLE_QUOTE= "IN_DOUBLE_QUOTE";

	// Representing the three kinds of mysql comments:
	const IN_MYSQL_COMMENT = "IN_MYSQL_COMMENT";  // e.g., "/^-- (.*)$"
	const IN_C_COMMENT = "IN_C_COMMENT"; // e.g., /* ... */
	const IN_HASH_COMMENT = "IN_HASH_COMMENT";  // e.g., /^#(.*)$/

	/** Uses finite-state machine to parse out the next query... */
	function getNextQuery(&$comment_out=null)
	{
		$state = self::IN_QUERY;
		$query = "";
		$comment = "";

		for( ; $this->_position < $this->_length ; $this->_position += 1 )
		{
			$c = $this->_buffer[$this->_position];
			$c_before = $this->_position-1 >= 0 ? $this->_buffer[$this->_position-1] : "";
			$c_next = $this->_position+1 < $this->_length ? $this->_buffer[$this->_position+1] : "";
			$c_next_next = $this->_position+2 < $this->_length ? $this->_buffer[$this->_position+2] : "";

			$this->debugPrintLn(	__FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): position={$this->_position}, c='{$c}', c_before='{$c_before}'...");
			$this->debugPrintLn(	__FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): BEFORE: state=$state");

			switch($state)
			{
				case (self::IN_QUERY):
					if( $c == "-" &&  $c_next == "-" && $c_next_next == " ")
					{
						$state = self::IN_MYSQL_COMMENT;
						$this->_position += 2; /* Increment so next loop pass begins after "-- " */
						break;
					}
					else if( $c == "#" )
					{
						$state = self::IN_HASH_COMMENT;
						break;
					}
					else if( $c == "/" &&  $c_next == "*")
					{
						$state = self::IN_C_COMMENT;
						$this->_position += 1; // Increment so next loop pass begins after "/*"
						break;
					}
					else if( $c == "'")
					{
						$state = self::IN_SINGLE_QUOTE;
					}
					else if( $c == "\"")
					{
						$state = self::IN_DOUBLE_QUOTE;
					}
					else if( $c == ";")
					{
						$this->_position += 1; /* Increment for next call to this method. */

						$query = trim($query);

						$this->debugPrintLn(	__FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Encountered a terminating semicolon.  Returning query='$query'");

						if(!is_null($comment_out))
						{
							$comment_out = $comment;
						}

						return $query;
					}

					$query .= $c;

					break;

				case (self::IN_MYSQL_COMMENT): case (self::IN_HASH_COMMENT):
					if( $c == "\n" )
					{
						$state = self::IN_QUERY;
					}
					else
					{
						$comment .= $c;
					}
					break;

				case (self::IN_C_COMMENT):
					if( $c == "*"  && $c_next == "/")
					{
						$state = self::IN_QUERY;
						$this->_position += 1; // Increment so next loop pass begins after "*/"
					}
					else
					{
						$comment .= $c;
					}
					break;


				case (self::IN_SINGLE_QUOTE):
					if( $c == "'" && $c_before != "\\")
					{
						$state = self::IN_QUERY;
					}

					$query .= $c;

					break;

				case (self::IN_DOUBLE_QUOTE):
					if( $c == "\"" && $c_before != "\\")
					{
						$state = self::IN_QUERY;
					}

					$query .= $c;
					break;

			}/* switch($state) */

			$this->debugPrintLn(	__FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): AFTER: state=$state");
			$this->debugPrintLn(	__FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): query=\"{$query}\"");
			$this->debugPrintLn(	__FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): comment=\"{$comment}\"");
		}
	}/* getNextQuery() */


	function debugPrintLn($msg)
	{
		if(self::$GBDebug >= 1)
		{
			print("<!-- \@\@\@> " . $msg . " -->\n");
		}
	}

	public static function runTest($input_file = "input.txt")
	{
		print( __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): " . "Reading input_file '$input_file'...". PHP_EOL);

		$input = file_get_contents($input_file);

		print(
				"input=" . PHP_EOL .
				"===================" . PHP_EOL .
				$input . PHP_EOL .
				"===================" . PHP_EOL
		);

		QueryManager::setDebug(0);
		print("QueryManager::getDebug()=" . QueryManager::getDebug() . PHP_EOL);

		$qm = new QueryManager();

		//$qm->load($input);
		$qm->setInput($input);

		$count = 0;

		//while($qm->hasMoreQueries())
		$comment_out = "";
		while($sSubQuery = $qm->getNextQuery($comment_out))
		{
			$count++;

			//$sSubQuery = $qm->getNextQuery();

			print(
					"#### comment[$count] ############" . PHP_EOL .
					$comment_out . PHP_EOL .
					"++++ query[$count] +++++++++++++++" . PHP_EOL .
					"\"" . $sSubQuery . "\"" . PHP_EOL
			);
		}

		print("+++++++++++++++++++" . PHP_EOL);
	}/* runTest() */

}/* class QueryManager */

//QueryManager::runTest();


