<?php
class JUtils
{
	private static $GBDebug = 0;

	private static $_host="localhost";
	private static $_port=3306;
	private static $_username="johnny";
	private static $_password="johnny";

	private static $_database="johnny";


	public function setDebug($debug)
	{
		self::$GBDebug = $debug;
	}

	public function getDebug()
	{
		return self::$GBDebug;
	}


	/**  Does mysql_connect() and mysql_select_db() using
	 * private static member variables:
	 *  $_host, $_port, $_username, $_password
	 * and mysql_select_db()
	 * using $_database
	 *
	 * Throws an Exception if any difficulty
	 * is encountered.
	 *
	 * @returns: The connection
	 */
	public function mysql_connect() #throws Exception
	{
		$sWho = "Utils::mysql_connect";

		$host_port=self::$_host;

		if(strlen(self::$_port) > 0)
		{
			$host_port .= ":" . self::$_port;
		}

		self::debugPrint("$sWho(): Calling mysql_connect('{$host_port}', '" . self::$_username . "', '" . self::$_password . "')\n");

		$con = mysql_connect($host_port, self::$_username, self::$_password);

		if(!$con)
		{
			$s_err = "$sWho(): Trouble with mysql_connect: '" . mysql_error() . "'";
			self::debugPrint($s_err);
			throw new Exception($s_err);
		}

		self::debugPrint("$sWho(): Calling mysql_select_db(" . self::$_database . ")...");
		$rc = mysql_select_db( self::$_database, $con );

		if(!$rc)
		{
			$s_err = "$sWho(): Trouble with mysql_select_db(" . self::$_database . "): '" . mysql_error() . "'";
			self::debugPrint($s_err);
			throw new Exception($s_err);
		}

		return $con;
	}

	public static function var_dump_str($var)
	{
		ob_start();
		var_dump($var);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}/* function var_dump_str() */

	public static function bool_to_string($bool)
	{
		if(true === $bool)
		{
			return "TRUE";
		}
		else if(true == $bool)
		{
			return "true";
		}
		else if(false === $bool)
		{
			return "FALSE";
		}
		else if(false == $bool)
		{
			return "false";
		}
		else
		{
			return "???";
		}
	}

	public static function get_it($array, $key, $default="")
	{
		if (array_key_exists($key, $array) && $array[$key])
		{
			return $array[$key];
		}
		else
		{
			return $default;
		}
	}/* function get_it() */

	public static function debugPrint($msg)
	{
		if( self::$GBDebug )
		{
			print("<!--" . $msg . "-->\n");
		}
	}

	public static function getRequestField($field, $default)
	{
		if(array_key_exists($field, $_REQUEST))
		{
			return $_REQUEST[$field];
		}
		else
		{
			return $default;
		}
	}

	/**
	 * Prettifies an XML string into a human-readable and indented work of art
	 *
	 *  @param string $xml The XML as a string
	 *  @param boolean $html_output True if the output should be escaped (for use in HTML)
	 *
	 *  @many_thanks_to http://gdatatips.blogspot.com/2008/11/xml-php-pretty-printer.html.
	 */
	public static function xml_pretty($xml, $html_output=false) {
		$xml_obj = new SimpleXMLElement($xml);
		$level = 4;
		$indent = 0; // current indentation level
		$pretty = array();

		// get an array containing each XML element
		$xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));

		// shift off opening XML tag if present
		if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
			$pretty[] = array_shift($xml);
		}

		foreach ($xml as $el) {
			if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {
				// opening tag, increase indent
				$pretty[] = str_repeat(' ', $indent) . $el;
				$indent += $level;
			} else {
				if (preg_match('/^<\/.+>$/', $el)) {
					$indent -= $level;  // closing tag, decrease indent
				}
				if ($indent < 0) {
					$indent += $level;
				}
				$pretty[] = str_repeat(' ', $indent) . $el;
			}
		}
		$xml = implode("\n", $pretty);
		return ($html_output) ? htmlentities($xml) : $xml;
	}


	public static function findFieldCoordinates($input, $fieldSeparators=" \t", $debug=0)
	{
		$IN_FIELD = 1;
		$BETWEEN_FIELDS = 2;
		$NOWHERE = 3;

		$IN_FIELD = "IN_FIELD";
		$BETWEEN_FIELDS = "BETWEEN_FIELDS";

		$len = strlen($input);
	
		$state = $BETWEEN_FIELDS;
	
		$fieldNum=0;
	
		$fieldMap = array();
	
		$fieldBegin=-1;
		$fieldEnd=-1;

		$sWho="findFielCoordinates";
		if($debug)
		{
			print("$sWho(): input='$input', len=$len, fieldSeparators='$fieldSeparators'..." . PHP_EOL);
		}
	
		for( $i=0; $i<$len; $i++ )
		{
			$char = $input[$i];

			if($debug)
			{
				print("$sWho(): char[$i]='$char'..." . PHP_EOL);
				print("$sWho(): BEFORE: state=$state, fieldNum=$fieldNum, fieldBegin=$fieldBegin, fieldEnd=$fieldEnd, fieldMap=" . self::var_dump_str($fieldMap) . "..." . PHP_EOL);
			}
	
			switch( $state )
			{
				case $BETWEEN_FIELDS:
					if( strpos($fieldSeparators, $char) !== false )	
					{
						/* We found a character that is a field separator... */
					}
					else
					{
						/* We found a character that's not a field separator... */
						$state = $IN_FIELD;
	
						$fieldNum++;
	
						$fieldBegin = $i;
						$fieldEnd = -1;
					}
					break;
	
				case $IN_FIELD:
					if( strpos($fieldSeparators, $char) !== false )	
					{
						/* We found a character that is a field separator... */
						$state = $BETWEEN_FIELDS;
						$fieldEnd = $i-1;
	
						$fieldMap[$fieldNum]=array("begin"=>$fieldBegin, "end"=>$fieldEnd);
					}
					else
					{
						/* We found a character that's not a field separator... */
					}
					break;
			}/* switch( $state ) */

			if($debug)
			{
				print("$sWho(): AFTER: state=$state, fieldNum=$fieldNum, fieldBegin=$fieldBegin, fieldEnd=$fieldEnd, fieldMap=" . self::var_dump_str($fieldMap) . "..." . PHP_EOL);
			}

		}/* for( $i=0; $i<$len; $i++) */

		if($state == $IN_FIELD)
		{
			/* We were in a field when we came to the end of the string. */

			$fieldEnd = $i-1;
			$fieldMap[$fieldNum]=array("begin"=>$fieldBegin, "end"=>$fieldEnd);
		}

		if($debug)
		{
			print("$sWho(): FINAL: state=$state, fieldNum=$fieldNum, fieldBegin=$fieldBegin, fieldEnd=$fieldEnd, fieldMap=" . self::var_dump_str($fieldMap) . "..." . PHP_EOL);
		}

		return $fieldMap;
	
	}/* function findFieldCoordinates($input, $fieldSeparators) */



}/* class JUtils */
?>
