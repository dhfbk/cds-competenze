<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 'On');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ini_set("display_errors", "On");

require_once 'vendor/autoload.php';
// print_r($_REQUEST);

$script_uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";

$base_uri = implode('/', array_splice( explode('/', $script_uri), 0, -1 ) );
$base_uri = implode('/', array_splice( explode('/', $base_uri), 0, -1 ) );

session_start();

require_once("config.php");
require_once("include.php");
require_once("Mysql_connector.class.php");

$Action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";

$DB = new Mysql_connector($DB_HOST, $DB_USERNAME, $DB_PASSWORD);
$DB->select_db($DB_NAME);

$mysqli = $DB->connection;

$_SESSION['Options'] = loadOptions();
if (!isset($_SESSION['Options'])) {
    $_SESSION['Options'] = loadOptions();
}
$Options = $_SESSION['Options'];

if (!isset($_SESSION['User'])) {
    $_SESSION['User'] = [];
}

$json_params = file_get_contents("php://input");
if (strlen($json_params) > 0 && isValidJSON($json_params)) {
    $values = json_decode($json_params, true);
    // $ret['values'] = print_r($values, true);
    $_POST = $values;
    foreach ($_POST as $key => $value) {
        $_REQUEST[$key] = $value;
    }
}

// require_once("update.php");

$google_client = new Google_Client();
$google_client->setClientId($Options['googleClientId']);
$google_client->setClientSecret($Options['googleClientSecret']);
$google_client->setRedirectUri($script_uri . "?action=generateGoogle");

$google_client->addScope('email');

$ret = array();
// $ret['options'] = $Options;
$ret['logged'] = $_SESSION['User'];
$ret['request'] = $_REQUEST;

switch ($Action) {
    case "listSentences":
    case "setSession":
    case "deleteSession":
    case "getTopics":
    case "setTopic":
    case "getAnnotations":
        if (!count($_SESSION['User'])) {
            $ret['result'] = "ERR";
            $ret['error'] = "Utente non loggato";
            break;
        }

        switch ($Action) {
            case "setTopic":
                $r = $DB->queryupdate("users", ["topic" => $_REQUEST['topic']], ["email" => $_SESSION['User']['email']]);
                if (!$r) {
                    $ret['result'] = "ERR";
                    $ret['error'] = $DB->get_error();
                    break;
                }

                $email = $_SESSION['User']['email'];
                $query = "SELECT * FROM users WHERE email = '$email'";
                $DB->query($query);
                $_SESSION['User'] = $DB->fetch_a();

                $ret['result'] = "OK";
                break;

            case "getTopics":
                $topics = [];
                $query = "SELECT * FROM topics WHERE active = '1' ORDER BY name";
                $DB->query($query);
                while ($row = $DB->fetch_a()) {
                    $topics[] = $row;
                }
                $ret['topics'] = $topics;
                break;

            case "getAnnotations":
                $page = 0;
                if (isset($_REQUEST['page'])) {
                    if (!preg_match("/^[0-9]+$/", $_REQUEST['page'])) {
                        $ret['result'] = "ERR";
                        $ret['error'] = "Invalid page";
                        break;
                    }
                    $page = $_REQUEST['page'];
                }
                $query = "SELECT * FROM sessions WHERE user = '{$_SESSION['User']['email']}' AND deleted = '0' ORDER BY created_time DESC";
                $total = $DB->querynum($query);

                $per_page = $Options['per_page'];
                $offset = $page * $per_page;
                $query .= " LIMIT $offset, $per_page";
                if (!$DB->query($query)) {
                    $ret['result'] = "ERR";
                    $ret['error'] = $DB->get_error();
                    break;
                }

                $ret['result'] = "OK";
                $ret['pages'] = ceil($total / $per_page);
                $results = [];
                while ($row = $DB->fetch_a()) {
                    $r = [];
                    $r['session_id'] = $row['session_id'];
                    $r['created_time'] = $row['created_time'];
                    $r['value'] = json_decode($row['value'], true);
                    $results[] = $r;
                    $ret['sessions'] = $results;
                }
                break;

            case "listSentences":
                $sentences = [];
                $query = "SELECT * FROM sentences WHERE active = '1' ORDER BY id";
                $DB->query($query);
                while ($row = $DB->fetch_a()) {
                    $sentences[] = $row;
                }
                $ret['sentences'] = $sentences;
                break;

            case "deleteSession":
                $session_id = addslashes($_REQUEST['session_id']);
                $query = "SELECT * FROM sessions WHERE session_id = '$session_id' AND user = '{$_SESSION['User']['email']}' AND deleted = '0'";
                if (!$DB->querynum($query)) {
                    $ret['result'] = "ERR";
                    $ret['error'] = "Errore di sessione";
                    break;
                }

                if (!$DB->queryupdate("sessions", ["deleted" => 1], ["session_id" => $session_id])) {
                    $ret['result'] = "ERR";
                    $ret['error'] = $DB->get_error();
                    break;
                }

                $ret['result'] = "OK";
                break;

            case "setSession":
                $data = [];
                $data['value'] = json_encode($_REQUEST['value']);

                if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
                    $session_id = addslashes($_REQUEST['session_id']);
                    $query = "SELECT * FROM sessions WHERE session_id = '$session_id' AND user = '{$_SESSION['User']['email']}' AND deleted = '0'";
                    if (!$DB->querynum($query)) {
                        $ret['result'] = "ERR";
                        $ret['error'] = "Errore di sessione";
                        break;
                    }
                    if ($DB->queryupdate("sessions", $data, ["session_id" => $session_id])) {
                        $ret['result'] = "OK";
                        $ret['session_id'] = $session_id;
                        break;
                    }
                    else {
                        $ret['result'] = "ERR";
                        $ret['error'] = $DB->get_error();
                        break;
                    }
                }
                else {
                    $data['user'] = $_SESSION['User']['email'];
                    if ($DB->queryinsert("sessions", $data)) {
                        $ret['result'] = "OK";
                        $ret['session_id'] = $DB->last_id;
                        break;
                    }
                    else {
                        $ret['result'] = "ERR";
                        $ret['error'] = $DB->get_error();
                        break;
                    }
                }
                break;
        }
        break;

    case "getLoginInfo":
        // do nothing (for now)
        $ret['lastError'] = $_SESSION['lastError'] ? $_SESSION['lastError'] : "";
        unset($_SESSION['lastError']);
        break;

    case "logout":
        unset($_SESSION['User']);
        $ret['result'] = "OK";
        break;

    case "generateGoogle":
        if ($_GET['code']) {
            $token = $google_client->fetchAccessTokenWithAuthCode($_GET["code"]);
            if (!isset($token['access_token'])) {
                exit($token['error_description']);
            }

            $google_client->setAccessToken($token['access_token']);
            $google_service = new Google_Service_Oauth2($google_client);
            $data = $google_service->userinfo->get();

            $email = $data['email'];

            $query = "SELECT * FROM allowed_addresses WHERE address = '$email' AND deleted = 0";
            if ($DB->querynum($query)) {
                $query = "INSERT INTO users (`email`) VALUES ('$email') ON DUPLICATE KEY UPDATE `email` = '$email'";
                if ($DB->query($query)) {
                    $query = "SELECT * FROM users WHERE email = '$email'";
                    $DB->query($query);
                    $_SESSION['User'] = $DB->fetch_a();
                }
                else {
                    $_SESSION['lastError'] = "Errore nella creazione dell'utente";
                }
                header("Location: $base_uri");
                exit();
            }
            else {
                $_SESSION['lastError'] = "User $email is not allowed to login";
                header("Location: $base_uri");
                exit();
            }
        }
        else {
            $ret['result'] = "ERR";
            $ret['error'] = "Login non valido";
        }

        break;

    case "login":
        header("Location: " . $google_client->createAuthUrl());
        exit();
        break;
}

echo json_encode($ret);
