<?php

function setLog($user, $action, $id = null) {
	global $DB;

	$data = [];
	$data['user'] = $user;
	$data['action'] = $action;
	if (!is_null($id)) {
		$data['related_id'] = $id;
	}

	return $DB->queryinsert("logs", $data, "log");
}

function loadOptions() {
	global $mysqli;
	
	$Options = array();
	$query = "SELECT * FROM options";
	$result = $mysqli->query($query);
	while ($obj = $result->fetch_object()) {
		$Options[$obj->id] = $obj->value;
	}
	return $Options;
}

function find($table, $id, $text) {
	global $mysqli;
	
	$stmt_up = $mysqli->prepare("SELECT * FROM {$table} WHERE id = ?");
	$stmt_up->bind_param("s", $id);
	$stmt_up->execute();
	$r = $stmt_up->get_result();
	if ($r->num_rows) {
		return $r->fetch_assoc();
	}

	$ret = array();
    $ret['result'] = "ERR";
    $ret['error'] = $text;
	echo json_encode($ret);
	exit();
}

function isValidJSON($str) {
	json_decode($str);
	return json_last_error() == JSON_ERROR_NONE;
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    return $length > 0 ? substr($haystack, -$length) === $needle : true;
}
