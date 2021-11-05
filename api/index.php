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

$base_uri = @implode('/', array_splice( explode('/', $script_uri), 0, -1 ) );
$base_uri = @implode('/', array_splice( explode('/', $base_uri), 0, -1 ) );

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

$jsonAuth = file_get_contents(__DIR__ . '/../../fbk-cds-723aae02d1f1.json');

$ret = array();
$ret['logged'] = $_SESSION['User']; // Logged user information, LEAVE HERE!

// Debug stuff
// $ret['options'] = $Options;
// $ret['request'] = $_REQUEST;

switch ($Action) {
    case "stats":
        $query = "SELECT s.*, t.name topic
            FROM sessions s
            LEFT JOIN users u ON u.email = s.user
            LEFT JOIN topics t ON t.id = u.topic
            WHERE confirmed = 1 AND deleted = 0
            ORDER BY updated_time";
        $DB->query($query);

        $totals = [];
        $totals[0] = ["yes" => 0, "no" => 0];
        $totals[1] = ["yes" => 0, "no" => 0];
        $totals2 = [];
        $totals2[0] = ["yes" => 0, "no" => 0];
        $totals2[1] = ["yes" => 0, "no" => 0];

        $results = [];

        while ($row = $DB->fetch_a()) {
            if (endsWith($row['user'], "fbk.eu") || endsWith($row['user'], "apnetwork.it") || endsWith($row['user'], "gmail.com")) {
                continue;
            }

            $user = $row['user'];
            $sentence_id = $row['sentence_id'];
            $topic = $row['topic'];
            $values = json_decode($row['value'], true);

            // print_r($values);

            $types = $values[2];

            switch ($types['tab']) {
                case "nav-free-tab":
                for ($i = 0; $i < 2; $i++) {
                    if ($values[$i][0]["yes"]) {
                        $totals[$i]["yes"]++;
                    }
                    if ($values[$i][0]["no"]) {
                        $totals[$i]["no"]++;
                    }
                    if ($values[$i][1]["yes"]) {
                        $totals[$i]["yes"]++;
                    }
                    if ($values[$i][1]["no"]) {
                        $totals[$i]["no"]++;
                    }
                }
                break;

                case "nav-choice-tab":
                if (!isset($results[$types['sentence_id']])) {
                    $results[$types['sentence_id']] = [];
                }
                for ($i = 0; $i < 2; $i++) {
                    foreach ($values[$i] as $r) {
                        if (!isset($results[$types['sentence_id']][$r['key']])) {
                            $results[$types['sentence_id']][$r['key']] = ["yes" => 0, "no" => 0];
                        }
                        if ($r['yes']) {
                            $results[$types['sentence_id']][$r['key']]['yes']++;
                        }
                        if ($r['no']) {
                            $results[$types['sentence_id']][$r['key']]['no']++;
                        }
                    }
                }

                for ($i = 0; $i < 2; $i++) {
                    if ($values[$i][0]["yes"]) {
                        $totals2[$i]["yes"]++;
                    }
                    if ($values[$i][0]["no"]) {
                        $totals2[$i]["no"]++;
                    }
                    if ($values[$i][1]["yes"]) {
                        $totals2[$i]["yes"]++;
                    }
                    if ($values[$i][1]["no"]) {
                        $totals2[$i]["no"]++;
                    }
                }
                break;
            }

        }

        foreach ($results as $key => $value) {
            foreach ($value as $key2 => $vote) {
                $sum = $vote["yes"] + $vote["no"];
                $avg = 0.0;
                if ($sum > 0) {
                    $avg = $vote["yes"] / $sum;
                }
                $results[$key][$key2]["avg"] = $avg;
                $results[$key][$key2]["sum"] = $sum;
                $results[$key][$key2]["diff"] = $vote["yes"] - $vote["no"];
            }
        }

        $ret['results'] = $results;
        $ret['totals1'] = $totals;
        $ret['totals2'] = $totals2;
        // print_r($totals);
        // print_r($totals2);
        break;

    case "results":
        $google_client->addScope(Google_Service_Sheets::SPREADSHEETS);
        $google_client->setAuthConfig(json_decode($jsonAuth, true));
        $service = new Google_Service_Sheets($google_client);
        $spreadsheetId = "1b6yfcbWUukY1qgY13L9iCEjH_H3b-TFSkBLt7E-6zUk";
        $requestBody = new Google_Service_Sheets_CopySheetToAnotherSpreadsheetRequest();

        // print_r($spreadsheet);
        // exit();

        // $response = $service->spreadsheets_values->get("1b6yfcbWUukY1qgY13L9iCEjH_H3b-TFSkBLt7E-6zUk");
        // $values = $response->getValues();

        $topicSet = [];
        $query = "SELECT * FROM topics WHERE active = 1 ORDER BY id";
        $DB->query($query);
        while ($row = $DB->fetch_a()) {
            $topicSet[$row['name']] = 1;
        }

        // print_r($topicSet);

        $query = "SELECT s.*, t.name topic
            FROM sessions s
            LEFT JOIN users u ON u.email = s.user
            LEFT JOIN topics t ON t.id = u.topic
            WHERE confirmed = 1 AND deleted = 0
            ORDER BY updated_time";
        $DB->query($query);

        $Res = [];
        $Res_tot = [];
        $Res_free = [];

        $Topics = [];

        while ($row = $DB->fetch_a()) {
            if (endsWith($row['user'], "fbk.eu") || endsWith($row['user'], "apnetwork.it") || endsWith($row['user'], "gmail.com")) {
                continue;
            }

            $user = $row['user'];
            $sentence_id = $row['sentence_id'];
            $topic = $row['topic'];
            $values = json_decode($row['value'], true);
            $sentence_id .= " - " . $values[2]['text'];

            $Topics[$user] = $topic;

            if ($values[2]['tab'] == "nav-choice-tab") {
                for ($i = 0; $i < 2; $i++) {
                    $alg_str = "";
                    foreach ($values[$i] as $v) {
                        $alg_str .= $v['key'];
                    }
                    $alg_str = $i."_".md5($alg_str);

                    if (!isset($Res[$sentence_id])) {
                        $Res[$sentence_id] = [];
                    }
                    if (!isset($Res_tot[$sentence_id])) {
                        $Res_tot[$sentence_id] = [];
                    }

                    if (!isset($Res[$sentence_id][$alg_str])) {
                        $Res[$sentence_id][$alg_str] = [];
                    }

                    foreach ($values[$i] as $v) {
                        $key = $v['text'] . " - " . $v['key'];
                        if (!isset($Res[$sentence_id][$alg_str][$key])) {
                            $Res[$sentence_id][$alg_str][$key] = ["yes" => 0, "no" => 0];
                        }
                        if (!isset($Res_tot[$sentence_id][$key])) {
                            $Res_tot[$sentence_id][$key] = ["yes" => 0, "no" => 0];
                            foreach ($topicSet as $tkey =>$tvalue) {
                                $Res_tot[$sentence_id][$key]["yes_" . $tkey] = 0;
                                $Res_tot[$sentence_id][$key]["no_" . $tkey] = 0;
                            }
                        }
                        if ($v['yes']) {
                            $Res[$sentence_id][$alg_str][$key]['yes'] += 1;
                            $Res_tot[$sentence_id][$key]['yes'] += 1;
                            $Res_tot[$sentence_id][$key]["yes_" . $row['topic']] += 1;
                        }
                        if ($v['no']) {
                            $Res_tot[$sentence_id][$key]['no'] += 1;
                            $Res_tot[$sentence_id][$key]["no_" . $row['topic']] += 1;
                        }
                    }
                    // echo sprintf("%-50s %2d %10s %s\n", $user, $sentence_id, $i."_".md5($alg_str), $row['updated_time']);
                }
            }
            else {
                if (!isset($Res_free[$user])) {
                    $Res_free[$user] = [];
                }
                $Res_free[$user][] = $values[2]['text'];
            }

            // echo "$user\n";
            // echo "$sentence_id\n";
            // echo "$topic\n";
            // // echo "{$values[2]['tab']}\n";
            // echo "{$row['updated_time']}\n";
            // print_r($values[2]);
        }



        // print_r($Res_tot);
        ksort($Res_tot);

        foreach($Res_tot as $Key => $Value) {

            $mainIndex = strpos($Key, " - ");
            $mainIndex = substr($Key, 0, $mainIndex);

            try {
                $body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
                    'requests' => array(
                        'duplicateSheet' => array(
                            'sourceSheetId' => 0, //Source sheet id goes here as an integer
                            'insertSheetIndex' => 2, //Position where the new sheet should be inserted
                            'newSheetName' => $mainIndex //Set new name if you want
                        )
                    )
                ));
                $spreadsheet = $service->spreadsheets->batchUpdate($spreadsheetId, $body);

                $values = [];
                $vRow = [$Key, "", "yes"];
                foreach ($topicSet as $tkey =>$tvalue) {
                    $vRow[] = "yes_" . $tkey;
                }
                $vRow[] = "no";
                foreach ($topicSet as $tkey =>$tvalue) {
                    $vRow[] = "no_" . $tkey;
                }
                $values[] = $vRow;

                // echo "$Key\t\tyes\tno\n";
                foreach($Value as $k => $v) {
                    $index = strrpos($k, " - ");
                    $str1 = substr($k, 0, $index);
                    $str2 = substr($k, $index + 3);
                    $vRow = [$str1, $str2, $v['yes']];
                    foreach ($topicSet as $tkey =>$tvalue) {
                        $vRow[] = $v["yes_" . $tkey];
                    }
                    $vRow[] = $v['no'];
                    foreach ($topicSet as $tkey =>$tvalue) {
                        $vRow[] = $v["no_" . $tkey];
                    }
                    $values[] = $vRow;
                    // $values[] = [$str1, $str2, $v['yes'], $v['no']];
                    // echo "$str1\t$str2\t{$v['yes']}\t{$v['no']}\n";
                }
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => $values
                ]);
                $range = $mainIndex.'!A1:Z'.count($values);

                $result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, ["valueInputOption" => USER_ENTERED]);
                // printf("%d cells updated.", $result->getUpdatedCells());
            } catch (Exception $e) {
                print_r($e);
                break;
            }

            // echo "\n";
        }

        $values = [];
        foreach ($Res_free as $user => $value) {
            foreach ($value as $sentence) {
                $values[] = [$user, $Topics[$user], $sentence];
            }
        }
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        $range = 'Free!A2:C'.(count($values) + 1);
        $result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, ["valueInputOption" => USER_ENTERED]);

        exit();
        break;

    case "listSentences":
    case "setSession":
    case "confirmSession":
    case "deleteSession":
    case "getTopics":
    case "setData":
    case "getAnnotations":

        if (!count($_SESSION['User'])) {
            $ret['result'] = "ERR";
            $ret['error'] = "Utente non loggato";
            break;
        }

        switch ($Action) {
            case "setData":
                $show_help = boolval($_REQUEST['show_help']);
                $r = $DB->queryupdate("users",
                    [
                        "topic" => $_REQUEST['topic'],
                        "show_help" => $show_help ? 1 : 0,
                        "notes" => $_REQUEST['notes']
                    ],
                    ["email" => $_SESSION['User']['email']]
                );
                if (!$r) {
                    $ret['result'] = "ERR";
                    $ret['error'] = $DB->get_error();
                    break;
                }

                setLog($_SESSION['User']['email'], "set_data");
                $email = $_SESSION['User']['email'];
                $query = "SELECT * FROM users WHERE email = '$email'";
                $DB->query($query);
                $_SESSION['User'] = $DB->fetch_a();

                $ret['result'] = "OK";
                break;

            case "getTopics":
                $topics = [];
                $query = "SELECT * FROM topics WHERE active = '1' ORDER BY id";
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

                setLog($_SESSION['User']['email'], "show_annotations", $page);
                $ret['error'] = $DB->get_error();
                $query = "SELECT *
                    FROM sessions
                    WHERE user = '{$_SESSION['User']['email']}'
                        AND deleted = '0'
                        AND confirmed = '1'
                    ORDER BY created_time DESC";
                $total = $DB->querynum($query);
                $ret['total'] = $total;
                // $ret['totals'] = [];
                // while ($row = $DB->fetch_a()) {
                //     $r['value'] = json_decode($row['value'], true);

                //     $type = $r['value'][2]['tab'];
                //     if (!isset($ret['totals'][$type])) {
                //         $ret['totals'][$type] = 0;
                //     }
                //     $ret['totals'][$type]++;
                // }

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

                    // $type = $r['value'][2]['tab'];
                    // if (!isset($ret['totals'][$type])) {
                    //     $ret['totals'][$type] = 0;
                    // }
                    // $ret['totals'][$type]++;

                    $results[] = $r;
                }
                $ret['sessions'] = $results;
                break;

            case "listSentences":
                $sentences = [];
                $query = "SELECT s.*, COUNT(t.session_id) done
                    FROM sentences s
                    LEFT JOIN sessions t ON (
                        s.id = t.sentence_id AND
                        t.user = '{$_SESSION['User']['email']}' AND
                        t.confirmed = '1' AND
                        t.deleted = '0'
                    )
                    WHERE s.active = '1'
                    GROUP BY s.id
                    ORDER BY s.id";
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

                setLog($_SESSION['User']['email'], "delete_session", $session_id);
                $ret['result'] = "OK";
                break;

            case "confirmSession":
                if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
                    $session_id = addslashes($_REQUEST['session_id']);
                    if ($DB->queryupdate("sessions", ["confirmed" => 1], ["session_id" => $session_id])) {
                        $ret['result'] = "OK";
                        $ret['session_id'] = $session_id;
                        setLog($_SESSION['User']['email'], "confirm_session", $session_id);
                        break;
                    }
                    else {
                        $ret['result'] = "ERR";
                        $ret['error'] = $DB->get_error();
                        break;
                    }
                }
                else {
                    $ret['result'] = "ERR";
                    $ret['error'] = "Invalid session ID";
                    break;
                }
                break;

            case "setSession":
                $data = [];
                $data['value'] = json_encode($_REQUEST['value']);
                $data['sentence_id'] = addslashes($_REQUEST['value'][2]['sentence_id']);

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
                        setLog($_SESSION['User']['email'], "update_session", $session_id);
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
                        setLog($_SESSION['User']['email'], "insert_session", $DB->last_id);
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
        if (isset($_SESSION['User']['email']) && $_SESSION['User']['email']) {
            // bad
            $query = "SELECT *
                FROM sessions
                WHERE user = '{$_SESSION['User']['email']}'
                    AND deleted = '0'
                    AND confirmed = '1'
                ORDER BY created_time DESC";
            $total = $DB->querynum($query);
            $ret['total'] = $total;
            $ret['totals'] = [];
            $ret['totals']['nav-choice-tab'] = 0;
            $ret['totals']['nav-free-tab'] = 0;
            while ($row = $DB->fetch_a()) {
                $r['value'] = json_decode($row['value'], true);

                $type = $r['value'][2]['tab'];
                if (!isset($ret['totals'][$type])) {
                    $ret['totals'][$type] = 0;
                }
                $ret['totals'][$type]++;
            }
        }
        $ret['lastError'] = isset($_SESSION['lastError']) && $_SESSION['lastError'] ? $_SESSION['lastError'] : "";
        unset($_SESSION['lastError']);
        break;

    case "logout":
        if (isset($_SESSION['User']['email']) && $_SESSION['User']['email']) {
            setLog($_SESSION['User']['email'], "logout");
        }
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
                    setLog($_SESSION['User']['email'], "login");
                }
                else {
                    $_SESSION['lastError'] = "Errore nella creazione dell'utente";
                }
                header("Location: $base_uri");
                exit();
            }
            else {
                $_SESSION['lastError'] = "L'utente $email non è abilitato";
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

// print_r($ret);
echo json_encode($ret);
