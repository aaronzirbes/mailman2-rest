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

# GET       :   List all lists / Get List detail
#			:		lists_get
#			:		lists_get_id
# PUT       :   Modify a List's settings
#			:		???
# POST      :   Create a new List
#			:		???
# DELETE    :   Turn off a List
#			:		???


function rest_get($argv) {
	# Ensure only one param was passed
	$argc = count($argv);
	if ($argc == 1) {
		# if the param was empty, then
		if ($argv[0] == "") {
			# No Arguments Passed
			lists_get();
		} else {
			lists_get_id($argv[0]);
		}
	} else {
		echo "Only 1 paramater accepted.";
	}
}

function lists_get_id($list_name) {

	global $mmrest_cmd_prefix;

	$cmd = 'lists_get_id "' . $list_name . '"';
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	# split the tab delimited line
	$items = split("\t", $output);

	$list = array("name"=>$items[0], "description"=>$items[1], "subjectPrefix"=>$items[2]);

	# Encode list as JSON
	echo json_encode($list);

}

function lists_get() {

	global $mmrest_cmd_prefix;

	$cmd = 'lists_get';
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	$lists = array();

	$lines = split("\n", $output);
	# Loop through each line in the output
	foreach ($lines as $line) {
		if (strlen($line) > 3) {
			$items = split("\t", $line);
			# Create assosciative array from the tab delimeted line
			# that was split into a numeric array
			$list = array("name"=>$items[0], "description"=>$items[1]);
			# Append our new associative array to the numeric
			# array of lists
			array_push($lists, $list);
		}
	}

	# Encode lists as JSON
	echo json_encode($lists);
}

function rest_error($request) {
	echo "ERROR: Method not supported.";
}

?>
