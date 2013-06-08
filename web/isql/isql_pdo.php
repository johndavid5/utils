<?php 

/** An Interactive SQL web app that uses PHP's PDO to execute
* queries and display their output.
*/

// Set headers to prevent caching...
header("Cache-Control: no-cache, must-revalidate");
# Date in the past
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Charset: UTF-8");

include "jutils.inc.php";
include "QueryManager.php";

/** Use Strategy Design Pattern to implement
* various types of output:
*  {html, json, xml, csv, tab-delimited, sql inserts,...}
*/
interface OutWriterInterface {

	public function doContentType();

	public function doHead();

	public function doTail();

	public function printPreliminaries(
			$dsn, $username, $password,
			$heading_repeat, $field_info_level, $output_type,
			$i_query_count, $s_sub_query, $first_row, $max_rows,
			$table_name
	);

	public function printRowsAffected($rows_affected);

	/*************/

	public function printPrefix();

	public function printHeader($result, $field_info_level);

	public function printRow($result, $row, $row_number, $count, $table_name="");

	public function printSuffix();

	/*************/

	public function printNoRowsReturned();

	public function printError($message);

}

class OutWriter implements OutWriterInterface
{
	private $strategy = NULL;

	/**
	 * @param $content_type : must be equal to
	 *   one of {"html", "json", "xml", "csv", "tab-delimited"}
	 */
	public function __construct($content_type) {
		switch ($content_type)
		{
			case "html":
				$this->strategy = new OutWriterHtml();
				break;

			case "json":
				$this->strategy = new OutWriterJson();
				break;

			case "xml":
				$this->strategy = new OutWriterXml();
				break;

			case "csv":
				$this->strategy = new OutWriterCsv();
				break;

			case "tab-delimited":
				$this->strategy = new OutWriterTabDelimited();
				break;

			case "sql":
				$this->strategy = new OutWriterSqlInserts();
				break;

			default:
				throw new Exception("Invalid content_type!  Must be 'html', 'json', 'xml', 'csv', or 'tab-delimited'.");
				break;
		}
	}

	public function doContentType()
	{
		$this->strategy->doContentType();
	}

	public function doHead()
	{
		$this->strategy->doHead();
	}

	public function doTail()
	{
		$this->strategy->doTail();
	}

	public function printPreliminaries(
			$dsn, $username, $password,
			$heading_repeat, $field_info_level, $output_type,
			$i_query_count, $s_sub_query, $first_row, $max_rows,
			$table_name
	)
	{
		$this->strategy->printPreliminaries(
				$dsn, $username, $password,
				$heading_repeat, $field_info_level, $output_type,
				$i_query_count, $s_sub_query, $first_row, $max_rows,
				$table_name
		);
	}

	public function printRowsAffected($rows_affected)
	{
		$this->strategy->printRowsAffected($rows_affected);
	}

	/***************/

	public function printPrefix()
	{
		$this->strategy->printPrefix();
	}

	public function printHeader($result, $field_info_level)
	{
		$this->strategy->printHeader($result, $field_info_level);
	}

	public function printRow($result, $row, $row_number, $count, $table_name="")
	{
		$this->strategy->printRow($result, $row, $row_number, $count, $table_name);
	}

	public function printSuffix()
	{
		$this->strategy->printSuffix();
	}

	/***************/

	public function printNoRowsReturned()
	{
		$this->strategy->printNoRowsReturned();
	}

	public function printError($message)
	{
		$this->strategy->printError($message);
	}
}

abstract class OutWriterBase
  implements OutWriterInterface
{
	public function doContentType()
	{
		//error_log(  "TRACE: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Doing header(\"Content-type: text/xml\")...");
		header("Content-Type: text/html");
	}

	public function doHead()
	{
		print("<html>\n");
		print("<head>\n" .
				"<link rel=\"shortcut icon\" href=\"favicon.ico\" />\n" .
				"<link rel=\"stylesheet\" type=\"text/css\" href=\"style4.css\">\n" .
				"</head>\n"
		);
		print("<body>\n");
	}

	public function doTail()
	{
		print("</body>\n");
		print("</html>\n");
	}

	public function printPreliminaries(
			$dsn, $username, $password,
			$heading_repeat, $field_info_level, $output_type,
			$i_query_count, $s_sub_query, $first_row, $max_rows,
			$table_name
	)
	{
		// e.g., "Monday August 15th, 2005  3:12:46 PM"
		print(
	   "<p style=\"color: blue; margin-left: 20px\">\n" .
	   date('l, F jS, Y h:i:s A') . "\n" .
	   "</p>" . PHP_EOL
		);

		print("<p>\n");
		print("dsn='$dsn'&nbsp;&nbsp;");
		print("username='$username'&nbsp;&nbsp;");
		print("password='$password'<br />\n");
		print("heading_repeat={$heading_repeat}, field info level={$field_info_level}, output_type={$output_type}<br />");
		print("first_row={$first_row}, max_rows={$max_rows}, table_name='{$table_name}'<br />");
		print("query #{$i_query_count}:\n");
		print("<pre style=\"margin-left: 10px;\">{$s_sub_query}</pre>\n");
		print("</p>\n");
	}/* public function printPreliminaries() */

	public function printRowsAffected($rows_affected)
	{
		print("<h3>$rows_affected row(s) affected.</h3>" . PHP_EOL);
	}

	public function printNoRowsReturned()
	{
		print("<h3>No rows returned</h3>" . PHP_EOL);
	}

	public function printError($message)
	{
		echo("<p style=\"color: #960000;\">{$message}</p>" . PHP_EOL);
	}

	public abstract function printPrefix();

	public abstract function printHeader($result, $field_info_level);

	public abstract function printRow($result, $row, $row_number, $count, $table_name="");

	public abstract function printSuffix();
}

class OutWriterHtml
  extends OutWriterBase
  implements OutWriterInterface
{
	public function printPrefix()
	{
		echo("<table border=\"1\">" . PHP_EOL);
	}

	public function printHeader($result, $field_info_level)
	{
		$column_count = $result->columnCount();

		echo("  <tr>" . PHP_EOL );
		echo("    <th>row</th>" . PHP_EOL );

		for($i=0; $i < $column_count; $i++)
		{
			$meta = $result->getColumnMeta($i);
			echo("    <th>" . $meta['name'] . "</th>" . PHP_EOL);
		}
		echo("  </tr>" . PHP_EOL );

		if($field_info_level >= 1)
		{
			echo("  <tr>" . PHP_EOL );
			echo("    <th>&nbsp;</th>" . PHP_EOL );
			for($i=0; $i < $column_count; $i++)
			{
				$meta = $result->getColumnMeta($i);
				echo("    <th>" . $meta['native_type'] . "[" . $meta['len'] . "]" . "</th>" . PHP_EOL);
			}
			echo("  </tr>" . PHP_EOL );
		}
	}/* printHeader($result) */

	public function printRow($result, $row, $row_number, $count, $table_name="")
	{
		$column_count = $result->columnCount();

		echo("  <tr>" . PHP_EOL );
		echo("    <th align=\"right\">${row_number}</th>" . PHP_EOL );

		for($i=0; $i<$column_count; $i++)
		{
			$field = $row[$i];

			$pre="";
			$post="";
			//if(strpos($field, PHP_EOL) !== false)
			if(strpos($field, "\n") !== false || strpos($field, "\r") !== false)
			{
				/* If you detect an end-of-line in the cell, use <pre>...</pre> so the user can see the line breaks. */
				$pre="<pre>";
				$post="</pre>";
			}

			if(is_null($field))
			{
				$field="NULL";
			}
			else if(strlen(trim($field))==0)
			{
				$field="&nbsp;";
			}
			else
			{
				$field=htmlentities($field);
			}

			echo("    <td>{$pre}" . $field . "{$post}</td>" . PHP_EOL);
		}
		echo("  </tr>" . PHP_EOL );
}

public function printSuffix()
{
	echo("</table>" . PHP_EOL);
}

}/* class OutWriterHtml */


class OutWriterSqlInserts
  extends OutWriterBase
  implements OutWriterInterface
{
	public function doContentType()
	{
		//error_log(  "TRACE: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Doing header(\"Content-type: text/plain\")...");
		header("Content-Type: text/plain");
	}
	
	public function doHead()
	{
	}
	
	
	public function printPreliminaries(
			$dsn, $username, $password,
			$heading_repeat, $field_info_level, $output_type,
			$i_query_count, $s_sub_query, $first_row, $max_rows,
			$table_name
	)
	{
		// e.g., "Monday August 15th, 2005  3:12:46 PM"
		print("-- " . date('l, F jS, Y h:i:s A') . "\n");
			
		print("-- dsn='$dsn'");
		print(", username='$username'");
		print(", password='$password'\n");
		print("-- heading_repeat={$heading_repeat}, field info level={$field_info_level}, output_type={$output_type}\n");
		print("-- first_row={$first_row}, max_rows={$max_rows}, table_name='{$table_name}'\n");
		print("/*" . "\n" .
		"query #{$i_query_count}:\n" .
		$s_sub_query . "\n" .
		"*/\n\n");
	}/* public function printPreliminaries() */

	public function printPrefix()
	{
	}

	public function printHeader($result, $field_info_level)
	{
	}/* printHeader($result) */

	public function printRow($result, $row, $row_number, $count, $table_name="")
	{
		$column_count = $result->columnCount();

		echo(PHP_EOL .
		"/* ${row_number} */" . PHP_EOL
		);

		echo("insert into ${table_name}" . PHP_EOL);
		echo("(" . PHP_EOL);
		for($i=0; $i<$column_count; $i++)
		{
			$meta = $result->getColumnMeta($i);
			$name = $meta['name'];

			print(" " . $name);
			if($i<=$column_count-2)
			{
				print(",");
			}
			print( PHP_EOL );
		}
		echo(")" . PHP_EOL);
		echo("values" . PHP_EOL);
		echo("(" . PHP_EOL);
		for($i=0; $i<$column_count; $i++)
		{
			$field = $row[$i];

			if(is_null($field))
			{
				$field="NULL";
			}
			else
			{
				$field="'" . $field . "'";
			}

			print(" " . $field);
			if($i<=$column_count-2)
			{
				print(",");
			}
			print( PHP_EOL );
		}
		echo(");" . PHP_EOL);
}

public function printSuffix()
{
}

}/* class OutWriterSqlInserts */


class OutWriterJson
  extends OutWriterBase
  implements OutWriterInterface
{
	//public function doContentType()
	//{
		//error_log(  "TRACE: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Doing header(\"Content-type: text/json\")...");
		//header("Content-type: text/json");
	//}

	public function printPrefix()
	{
		echo("<pre>[" . PHP_EOL);
	}

	public function printHeader($result, $field_info_level)
	{
	}/* printHeader() */

	public function printRow($result, $row, $row_number, $count, $table_name="")
	{
		$column_count = $result->columnCount();


		echo(" /* $row_number */ " );

		if($count > 1)
		{
			echo("," );
		}

		echo("{ " );

		for($i=0; $i<$column_count; $i++)
		{
			if($i>0)
			{
				echo(", ");
			}

			$meta = $result->getColumnMeta($i);
			$name = $meta['name'];

			$field = $row[$i];

			if(is_null($field))
			{
				$field="NULL";
			}
			else
			{
				$field="\"" . $field . "\"";
			}
			echo("$name: $field");
		}
		echo("  }" . PHP_EOL );
	}

	public function printSuffix()
	{
		echo("]</pre>" . PHP_EOL);
	}

}/* class OutWriterJson */

class OutWriterXml
  extends OutWriterBase
  implements OutWriterInterface
{
	//protected $lt = "&lt;";
	protected $lt = "<";
	//protected $gt = "&gt;";
	protected $gt = ">";

	/* Start Helper Methods */
	public function keyVal($key, $value)
	{
		return $this->lt . $key . $this->gt . $value . $this->lt . "/" . $key . $this->gt;
	}

	public function startTag($tag)
	{
		return $this->lt . $tag . $this->gt;
	}

	public function endTag($tag)
	{
		return $this->lt . "/" . $tag . $this->gt;
	}
	/* End Helper Methods */

	public function doContentType()
	{
		//error_log(  "TRACE: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Doing header(\"Content-type: text/xml\")...");
		header("Content-type: text/xml");
	}

	public function doHead()
	{
		print($this->startTag("xml"));
		//print($this->lt . "?xml version=\"1.0\" encoding=\"UTF-8\"?" . $this->gt . PHP_EOL);
	}

	public function doTail()
	{
		print($this->endTag("xml"));
	}

	public function printPreliminaries(
			$dsn, $username, $password,
			$heading_repeat, $field_info_level, $output_type,
			$i_query_count, $s_sub_query, $first_row, $max_rows,
			$table_name
	)
	{
		// Prints something like: Monday August 15th, 2005  3:12:46 PM
		print(" " . $this->keyVal("date",  date('l, F jS, Y h:i:s A') ) . PHP_EOL );
		print(" " . $this->keyVal("dsn",  $dsn) . PHP_EOL);
		print(" " . $this->keyVal("username",  $username) . PHP_EOL);
		print(" " . $this->keyVal("password",  $password) . PHP_EOL);
		print(" " . $this->keyVal("heading_repeat",  $heading_repeat) . PHP_EOL);
		print(" " . $this->keyVal("field_info_level",  $field_info_level) . PHP_EOL);
		print(" " . $this->keyVal("output_type",  $output_type) . PHP_EOL);
		print(" " . $this->keyVal("query_count",  $i_query_count) . PHP_EOL);
		print(" " . $this->keyVal("first_row",  $first_row) . PHP_EOL);
		print(" " . $this->keyVal("max_rows",  $max_rows) . PHP_EOL);
		print(" " . $this->keyVal("sub_query",  PHP_EOL . "<![CDATA[" . PHP_EOL . $s_sub_query . PHP_EOL . "]]>" . PHP_EOL ) . PHP_EOL);
		//print(" " . $this->keyVal("sub_query",  htmlentities($s_sub_query)) . PHP_EOL);
	}/* public function printPreliminaries() */

	public function printRowsAffected($rows_affected)
	{
		print(" " . $this->keyVal("rows_affected",  $rows_affected) . PHP_EOL);
	}

	public function printNoRowsReturned()
	{
		print(" " . $this->keyVal("no_rows_returned",  "true") . PHP_EOL);
	}

	public function printError($message)
	{
		print(" " . $this->keyVal("error",  $message) . PHP_EOL);
	}

	public function printPrefix()
	{
		//echo("<pre>" . PHP_EOL);
		print(" " . $this->startTag("results") . PHP_EOL);
	}

	public function printHeader($result, $field_info_level)
	{
	}/* printHeader() */

	public function printRow($result, $row, $row_number, $count, $table_name="")
	{
		$column_count = $result->columnCount();

		echo("  " . $this->startTag("row") . " {$this->lt}!-- $row_number --{$this->gt}" . PHP_EOL);

		for($i=0; $i<$column_count; $i++)
		{
			$meta = $result->getColumnMeta($i);
			$name = $meta['name'];

			$field = $row[$i];

			if(is_null($field))
			{
				$field="NULL";
			}
			echo("   " . $this->keyVal($name, "<![CDATA[" . PHP_EOL . $field . PHP_EOL . "]]>") . PHP_EOL);
		}

		echo("  " . $this->endTag("row") . PHP_EOL);
	}

	public function printSuffix()
	{
		print(" " . $this->endTag("results") . PHP_EOL);
		//echo("</pre>" . PHP_EOL);
	}

}/* class OutWriterXml */

class OutWriterCsv
  extends OutWriterBase
  implements OutWriterInterface
{
	//public function doContentType()
	//{
	//error_log(  "TRACE: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Doing header(\"Content-type: text/csv\")...");
	//	header("Content-type: text/csv");
	//}

	public function printPrefix()
	{
		print("<pre>" . PHP_EOL);
	}

	public function printHeader($result, $field_info_level)
	{
		$column_count = $result->columnCount();

		for($i=0; $i<$column_count; $i++)
		{
			if($i>0)
			{
				echo(",");
			}

			$meta = $result->getColumnMeta($i);
			$name = $meta['name'];

			echo("\"$name\"");
		}

		echo(PHP_EOL);

	}/* printHeader() */

	public function printRow($result, $row, $row_number, $count, $table_name="")
	{
		$column_count = $result->columnCount();


		for($i=0; $i<$column_count; $i++)
		{
			if($i>0)
			{
				echo(",");
			}

			$field = $row[$i];

			if(is_null($field))
			{
				$field="NULL";
			}
			echo("\"$field\"");
		}

		echo(PHP_EOL);
	}

	public function printSuffix()
	{
		echo("</pre>" . PHP_EOL);
	}

}/* class OutWriterCsv */

class OutWriterTabDelimited
  extends OutWriterBase
  implements OutWriterInterface
{
	public function printPrefix()
	{
		print("<pre>" . PHP_EOL);
	}

	public function printHeader($result, $field_info_level)
	{
		$column_count = $result->columnCount();

		for($i=0; $i<$column_count; $i++)
		{
			if($i>0)
			{
				echo("\t");
			}

			$meta = $result->getColumnMeta($i);
			$name = $meta['name'];

			echo("\"$name\"");
		}

		echo(PHP_EOL);

	}/* printHeader() */

	public function printRow($result, $row, $row_number, $count, $table_name="")
	{
		$column_count = $result->columnCount();


		for($i=0; $i<$column_count; $i++)
		{
			if($i>0)
			{
				echo("\t");
			}

			$field = $row[$i];

			if(is_null($field))
			{
				$field="NULL";
			}
			echo("\"$field\"");
		}

		echo(PHP_EOL);
	}

	public function printSuffix()
	{
		echo("</pre>" . PHP_EOL);
	}

}/* class OutWriterTabDelimited */


//print("<!-- \$_REQUEST=" . JUtils::var_dump_str($_REQUEST) . " -->\n");
print("<!-- Version=1.5 -->\n");

$mode = JUtils::get_it($_REQUEST, "mode");
//print("<!-- mode='" . $mode . "' -->\n");

if(strcmp($mode, "top")==0)
{
	doTop();
}
elseif(strcmp($mode, "bot")==0)
{
	doBot();
}
else
{
	doFrames();
}


function doFrames()
{
	header("Content-Type: text/html");

	//$who="isql_pdo.php";
	$who=$_SERVER['PHP_SELF'];

	?>
<html>
<head>
<link rel="shortcut icon" href="favicon.ico" />
<title>isql_pdo.php</title>
</head>
<frameset rows="200,*" id="mainFrameset">
	<frame frameborder="1" id="top" src="<?php echo $who; ?>?mode=top"
		name="top" />
	<frame frameborder="1" id="bot" src="<?php echo $who; ?>?mode=bot"
		name="bot" />
	<noframes>
		<body>
			<p>
				This web page is more friendly with a <b>frames-capable</b> browser.
			</p>
		</body>
	</noframes>
</frameset>
</html>
<?php

}/* doFrames() */

function doTop()
{
	//error_log(  "TRACE: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "()...");
	header("Content-Type: text/html");

?>
<html>
<head>
<link rel="shortcut icon" href="favicon.ico" />
<script language="JavaScript">
	function Server(name, dsn, username, password)
	{
		this.name = name;
		this.dsn = dsn;
		this.username = username;
		this.password = password;

		this.toString = ServerToString;
	}

	function ServerToString()
	{
		return ("name=" + this.name + "\n" +
		"dsn=" + this.dsn + "\n" +
        "username=" + this.username + "\n" +
		"password=" + this.password);
	}

	/*
    * --- FILL IN YOUR SERVER INFO HERE ---
	* --- FORMAT: 
	* ---   g_assoc_servers["mysql-<host1>-<dbname1>"] = new Server("mysql-<host1>-<dbname1>", "mysql:host=<host1>;dbname=<dbname1>", "<username1>", "<password1>");
	* ---   g_assoc_servers["mysql-<host2>-<dbname2>"] = new Server("mysql-<host2>-<dbname2>", "mysql:host=<host2>;dbname=<dbname2>", "<username2>", "<password2>");
	* --- EXAMPLE:
	* ---   g_assoc_servers["mysql-localhost-mytestdb"] = new Server("mysql-localhost-mytestdb", "mysql:host=localhost;dbname=mytestdb", "my_username", "my_password");
	* ---   g_assoc_servers["mysql-remotehost-myremotedb"] = new Server("mysql-remotehost-myremotedb", "mysql:host=www.remotehost.com;dbname=myremotedb", "my_remote_username", "my_remote_password");
	*/
	var g_assoc_servers = new Object();
	g_assoc_servers["mysql-diana-codeigniter"] = new Server("mysql-diana-codeigniter", "mysql:host=diana;dbname=codeigniter", "monty", "some_pass");
	g_assoc_servers["mysql-diana-storefront"] = new Server("mysql-diana-storefront", "mysql:host=diana;dbname=storefront", "monty", "some_pass");
	g_assoc_servers["mysql-diana-store"] = new Server("mysql-diana-store", "mysql:host=diana;dbname=store", "monty", "some_pass");
	g_assoc_servers["mysql-diana-wordpress"] = new Server("mysql-diana-wordpress", "mysql:host=diana;dbname=wordpress", "monty", "some_pass");
	g_assoc_servers["sqlite-diana-depot"] = new Server("sqlite-diana-depot", "sqlite:C:/www/rails/depot/db/development.sqlite3");
	g_assoc_servers["mysql-diana-johnny"] = new Server("mysql-diana-johnny", "mysql:host=diana;dbname=johnny", "johnny", "johnny");
	g_assoc_servers["mysql-diana-moodle"] = new Server("mysql-diana-moodle", "mysql:host=diana;dbname=moodle", "johnny", "johnny");

	//for(var key in g_assoc_servers)
	//{
	//	alert("g_assoc_servers[" + key + "]='" + g_assoc_servers[key].toString() + "'");
	//}

	function loadMenu()
	{
	  var index=-1;
	  for(var key in g_assoc_servers)
	  {
		index++;
		//alert("g_assoc_servers[" + key + "]='" + g_assoc_servers[key].toString() + "'");
		document.joe_form.db_menu.options[index]=new Option(g_assoc_servers[key].name, g_assoc_servers[key].name, false, false);
	  }
	}

	function doMenu()
	{
	  //alert("doMenu()...");
	  var the_menu = document.joe_form.db_menu;
	  var selected_index = the_menu.selectedIndex;
	  var selected_value = the_menu.options[selected_index].value;
	  var selected_text = the_menu.options[selected_index].text;

      //alert("selected_index=" + selected_index + ", selected_value=" + selected_value + ", selected_text=" + selected_text);
      //alert("g_assoc_servers[" + selected_value + "]='" + g_assoc_servers[selected_value].toString() + "'");

	  document.getElementById("dsn").value = g_assoc_servers[selected_value].dsn;
	  document.getElementById("username").value = g_assoc_servers[selected_value].username;
	  document.getElementById("password").value = g_assoc_servers[selected_value].password;
	}

   </script>
   <link rel="stylesheet" type="text/css" href="style4.css">
   </head>

    <body onLoad="loadMenu(); doMenu();">
	<form name="joe_form" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?mode=bot" target="bot">
	server:&nbsp;<select name="db_menu" size="1" onChange="doMenu();"></select>&nbsp;&nbsp;dsn:&nbsp;<input type="text" name="dsn" id="dsn" size="35"/>&nbsp;username:&nbsp;<input type="text" name="username" id="username" size="10"/>&nbsp;password:&nbsp;<input type="text" name="password" id="password" size="10"/><br />

<?php
   print(
   "<input type=\"submit\" name=\"Submit\" value=\"Submit Query\" />" .
   "&nbsp;&nbsp;&nbsp;heading repeat:&nbsp;<input type=\"text\" size=\"2\" name=\"heading_repeat\" id=\"heading_repeat\" value=\"\" />" .
   "&nbsp;&nbsp;field&nbsp;info&nbsp;level:<select name=\"field_info_level\" id=\"field_info_level\">\n" .
   "<option value=\"0\" selected=\"selected\">0-none</option>\n" .
   "<option value=\"1\">1-inline</option>\n" .
   "<option value=\"2\">2-extended</option>\n" .
   "</select>" .
   "&nbsp;&nbsp;output&nbsp;type:&nbsp;<select name=\"output_type\" id=\"output_type\">\n" .
   "<option value=\"html\" selected=\"selected\">HTML</option>\n" .
   "<option value=\"json\">JSON</option>\n" .
   "<option value=\"xml\">XML</option>\n" .
   "<option value=\"csv\">CSV</option>\n" .
   "<option value=\"sql\">SQL</option>\n" .
   "<option value=\"tab-delimited\">TAB-DELIMITED</option>\n" .
   "</select>" .
   "&nbsp;first&nbsp;row:&nbsp;<input type=\"text\" name=\"first_row\" id=\"first_row\" size=\"2\" value=\"1\" />" .
   "&nbsp;max&nbsp;rows:&nbsp;<input type=\"text\" name=\"max_rows\" id=\"max_rows\" size=\"2\" value=\"100\" />" .
   "&nbsp;<label for=\"table_name\">table&nbsp;name(for SQL output):</label><input type=\"text\" name=\"table_name\" id=\"table_name\" size=\"5\" value=\"\" /><br />\n"
   );
?>

   &nbsp;&nbsp;Queries Below ( each must end with a semi-colon ';' )<br />
   <textarea name="query" rows="60" cols="160">
   show tables;
   </textarea>
   </form>
   </body>
   </html>
<?php
}

function doBot()
{
	//error_log(  "TRACE: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "()...");

	//print("<!-- PDO Drivers: -->\n");
	//print("<ol>\n");
	//foreach(PDO::getAvailableDrivers() as $driver)
	//{
    //	echo "<li>$driver</li>\n";
    //	echo "<!-- $driver -->\n";
    //}
	//print("</ol>\n");

   $dsn = JUtils::get_it($_REQUEST, "dsn");
   $username = JUtils::get_it($_REQUEST, "username");
   $password = JUtils::get_it($_REQUEST, "password");

   $heading_repeat = JUtils::get_it($_REQUEST, "heading_repeat", 0);
   $field_info_level = JUtils::get_it($_REQUEST, "field_info_level");
   $output_type = JUtils::get_it($_REQUEST, "output_type", "html");

   $first_row = JUtils::get_it($_REQUEST, "first_row", -1);
   $max_rows = JUtils::get_it($_REQUEST, "max_rows", -1);
   $table_name = JUtils::get_it($_REQUEST, "table_name", -1);

   $query = JUtils::get_it($_REQUEST, "query");
   //print("<!-- query: \n" .  PHP_EOL . $query .  PHP_EOL . "-->\n");

   $outWriter = new OutWriter($output_type);

   $outWriter->doContentType();
   $outWriter->doHead();
    
   $iQueryCount=0;
   $sSubQuery="";

   QueryManager::setDebug(0);
   //print("<!-- QueryManager::getDebug()=" . QueryManager::getDebug() . " -->\n");
   $qm = new QueryManager();
   $qm->setInput($query);

   $comment_out = "";
   while($sSubQuery = $qm->getNextQuery($comment_out))
   {
   	$iQueryCount++;

   	$outWriter->printPreliminaries($dsn, $username, $password,
			$heading_repeat, $field_info_level, $output_type,
			$iQueryCount, $sSubQuery, $first_row, $max_rows,
			$table_name
		);

		try
		{
			//print("<!-- Calling new PDO('{$dsn}', '{$username}', '{$password}')... -->\n");

			$dbh = new PDO($dsn, $username, $password);
			$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, TRUE);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			//error_log(  "TRACE: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Calling dbh->query($sSubQuery)...");


			$starttime = microtime(true);	// microtime(true) returns the unix timestamp plus milliseconds as a float

			$result = $dbh->query($sSubQuery);

			//$result->setFetchMode( PDO::FETCH_NAMED ); // Return result set as associative array.
			//										   // If result set contains mutiple rows with same name,
			//										   // returns an array of values per common name.

			$column_count = $result->columnCount();
			$rows_affected = $result->rowCount();

			if($column_count == 0)
			{
				$outWriter->printRowsAffected($rows_affected);
			}

			if($column_count != 0 && $field_info_level >= 2)
			{
				print("<pre>" . PHP_EOL);
				for($i=0; $i<$column_count; $i++)
				{
					printf("column[%d]={%s}" . PHP_EOL, $i, JUtils::var_dump_str($result->getColumnMeta($i)) );
				}
				print("</pre>" . PHP_EOL);
			}

			if( $column_count != 0 )
			{
				$outWriter->printPrefix();

				$row_number=0;
				$count=0;
				foreach( $result as $row )
				{
					//echo "<p>row[$count]=" . JUtils::var_dump_str($row) . "</p>" . PHP_EOL;

					$row_number++;

					if($first_row > 0 && $row_number < $first_row)
					{
						continue;
					}

					$count++;

					if($max_rows > 0 && $count > $max_rows)
					{
						break;
					}

					if(
						$count == 1
							||
						($heading_repeat > 0 && ($count - 1) % $heading_repeat == 0)
					)
					{
						$outWriter->printHeader($result, $field_info_level);
					}

					$outWriter->printRow($result, $row, $row_number, $count, $table_name);


				}/* foreach( $result as $row ) */

				$outWriter->printSuffix();

				if($count == 0)
				{
					$outWriter->printNoRowsReturned();
				}

			
				$endtime = microtime(true);	// microtime(true) returns the unix timestamp plus milliseconds as a float

				//print("starttime=" . $starttime . "<br />" . PHP_EOL);
				//print("endtime=" . $endtime. "<br />" . PHP_EOL);
				$elapsed_time = $endtime-$starttime;
				print("<br />Query Time = " . number_format($elapsed_time * 1000.0, 1) . " milliseconds<br />" . PHP_EOL);

			}/* if( $column_count != 0 ) */
		}
		catch(PDOException $e)
		{
			//error_log(  "WARN: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Trouble with PDO: '" . $e->getMessage() . "'" . PHP_EOL);
			$outWriter->printError('Trouble with PDO: ' . $e->getMessage());
		}
		catch(Exception $e)
		{
			//error_log(  "WARN: " . __FILE__ . ":" . __LINE__ . ":" . __METHOD__ . "(): Trouble with PDO: '" . $e->getMessage() . "'" . PHP_EOL);
			$outWriter->printError('Trouble: ' . $e->getMessage());
		}

   }/* while($qm->hasMoreQueries()) */

   $outWriter->doTail();

}/* function doBot() */

?>

