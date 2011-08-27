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
	case 'POST':
		rest_post($request);
		break;
	case 'PUT':
		rest_put($request);
		break;
	case 'DELETE':
		rest_delete($request);
		break;
	default:
		rest_error($request);
		break;
}

# GET       :   List all lists / Get List detail
#			:		members_get (return all lists and all members)
#			:		members_get_listid (return all members of a particular list)
#			:		members_get_memberid (return all lists a member is a part of)
# PUT       :   Modify a member's information
#			:		member_put (updates the name of a member on ALL lists)
# POST      :   Create a new List
#			:		member_post (add a member to a list)
# DELETE    :   Turn off a List
#			:		member_delete (remove a member from a list)


function rest_error($request) {
	echo "ERROR: Method not supported.";
}

# BEGIN Restful redirector functions

# Parse GET method, and call appropriate function
# depending on the arguments passed
function rest_get($argv) {
	# Ensure only one param was passed
	$argc = count($argv);
	if ($argc == 1) {
		# if the param was empty, then
		if ($argv[0] == "") {
			# No Arguments Passed
			members_get();
		} else {
			# Something was passed
			if (strpos($argv[0], "@")) {
				# This is an email address
				members_get_memberid(urldecode($argv[0]));
			}  else {
				# This is a list name
				members_get_listid($argv[0]);
			}
		}
	} else {
		echo "Only 1 paramater accepted.";
	}
}

# Parse DELETE method, and call delete function
function rest_delete($argv) {
	# Ensure only one param was passed
	$argc = count($argv);
	if ($argc == 2) {
		# This is a list name
		members_delete($argv[0], $argv[1]);
	} else {
		echo "Only 2 paramater accepted.";
	}
}

# Parse PUT method, and call the appropriate function
# depending on the parameters passed
function rest_put($argv) {
	# Ensure only one param was passed
	$argc = count($argv);
	if ($argc == 2) {
		# This is a list name
		members_put($argv[0], urldecode($argv[1]));
	} elseif ($argc == 4) {
		members_put_changemail($argv[0], urldecode($argv[1]), urldecode($argv[2]), urldecode($argv[3]));
	} else {
		echo "Only 2 or 4 paramaters accepted.";
	}
}

# Parse POST method, and call the appropriate function
# depending on the parameters passed
function rest_post($argv) {
	# Ensure only one param was passed
	$argc = count($argv);
	if ($argc == 2) {
		# This is a list name
		members_post($argv[0], urldecode($argv[1]), "");
	} elseif ($argc == 3) {
		# This is a list name
		members_post($argv[0], urldecode($argv[1]), urldecode($argv[2]));
	} else {
		echo "Only 2 or 3 paramater accepted.";
	}
}

# END Restful redirector functions

# Function to return the details of a list
function members_get_listid($listname) {

	global $mmrest_cmd_prefix;

	$cmd = 'members_get_listid "' . $listname . '"' ;
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	$lists = array();

	$address_list = str_replace("\n", ",", $output);
	
	$addresses = mailparse_rfc822_parse_addresses($address_list);

	# Fix to remove trailing empty address from list
	$last_item = end($addresses);
	$last_address = $last_item['address'];
	if (strlen($last_address) < 3) {
		array_pop($addresses);
	}

	# Encode lists as JSON
	echo str_replace('"is_group"', '"isGroup"', json_encode($addresses));
}

# Function to get the lists a member has joined
function members_get_memberid($email) {

	global $mmrest_cmd_prefix;

	$cmd = 'members_get_memberid "' . $email . '"';
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	$lists = split("\n", $output);
	
	if (strlen(end($lists)) < 1) {
		array_pop($lists);
	}

	# Encode lists as JSON
	echo json_encode($lists);
}

# Function to return all the members on this server
function members_get() {

	global $mmrest_cmd_prefix;

	$cmd = 'members_get';
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	$address_list = str_replace("\n", ",", $output);
	
	$addresses = mailparse_rfc822_parse_addresses($address_list);

	# Encode lists as JSON
	echo str_replace('"is_group"', '"isGroup"', json_encode($addresses));
}

# function to remove a member from a list
function members_delete($listname, $email) {
	global $mmrest_cmd_prefix;

	$cmd = 'members_delete "' . $listname . '" "' . $email . '"';
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	$nsm = "such member:";
	if ( strpos($output, $nsm) ) { 
		header("Status: 404");
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	}
}

# Function to add a member to a list
function members_post($listname, $email, $name) {
	global $mmrest_cmd_prefix;

	$cmd = 'members_post "' . $listname . '" "' . $email . '" "' . $name . '"';
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	$aam = "lready a member:";
	$biea = "ad/Invalid email address:";
	if ( strpos($output, $biea) ) { 
		header("Status: 400");
		header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
	} elseif ( strpos($output, $aam) ) {
		header("Status: 409");
		header($_SERVER["SERVER_PROTOCOL"]." 409 Conflict");
	}
}

# Function to update the display name for an email
# address on all lists
function members_put($email, $name) {
	global $mmrest_cmd_prefix;

	$cmd = 'members_put "' . $email . '" "' . $name . '"';
	$output = shell_exec($mmrest_cmd_prefix . $cmd);

	$nsm = "such member:";
	if ( strpos($output, $nsm) ) { 
		header("Status: 404");
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	}
}

# Function to change the email address (and display name) that someone
# is subscribed to a particular list with
function members_put_changemail($list, $old_email, $new_email, $display) {
    global $mmrest_cmd_prefix;

    $cmd = 'members_put_changemail "' . $list . '" "' . $old_email . '" "' . $new_email . '" "' . $display . '"';
    $output = shell_exec($mmrest_cmd_prefix . $cmd);

    $nsm = "such member:";
    if ( strpos($output, $nsm) ) {
        header("Status: 404");
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    }
}


?>
