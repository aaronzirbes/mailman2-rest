<?php

$mmrest_cmd_prefix = 'sudo -u list /usr/share/mailman-rest/bin/';

# Detect Request Method
$method = $_SERVER['REQUEST_METHOD'];
$path_info = $_SERVER['PATH_INFO'];
$request = split("/", substr($path_info, 1));

switch ($method) {
	case 'GET':
		rest_get($request);
		break;
	default:
		rest_error($request);
		break;
}

# GET	:	List all lists / Get List detail
#	:	aliases_get	


function rest_get($argv) {
	# We don't care about parameters
	aliases_get();
}

function aliases_get() {

	global $mmrest_cmd_prefix;

	$cmd = 'aliases_get';
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	$aliases = array();

	$lines = split("\n", $output);
	# Loop through each line in the output
	foreach ($lines as $line) {
		# Remove tab characters
		$line = str_replace("\t", "", $line);
		# Split the line into alias, members
		$parts = split(":", $line);
		$aliasname = $parts[0];
		$members = split(",", $parts[1]);

		if (strlen($aliasname) > 0) {
			# Create assosciative array from the aliases entry
			# that was split into a numeric array
			$alias = array("alias"=>$aliasname, "members"=>$members);
			# Append our new associative array to the numeric
			# array of lists
			array_push($aliases, $alias);
		}
	}

	# Encode lists as JSON
	echo json_encode($aliases);
}

function rest_error($request) {
	echo "ERROR: Method not supported.";
}

?>
