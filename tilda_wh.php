<?php

/**
 * Script was created using https://help-ru.tilda.cc/forms/webhook instruction. It's a webhook for tilda forms. Idea is to get data from tilda form and send it to LawTask CRM.
 */

// turn on display errors for debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

const INTEGRATION_ID = 1234; // replace to your integration_id
const INTEGRATION_CITY = 'London'; // you can hardcode city here if you wish

const HOST = 'https://lawtask.pro';
const CRM_URL = HOST . '/API/api.php';

try {
	check_for_tilda_test();
	get_lead_from_post();
}  catch (Exception $e) {
	$message = $e->getMessage() . ' on line ' . $e->getLine() . ' in ' . $e->getFile();
	error_log($message);
	header('Content-type: application/json;charset=utf-8');
	http_response_code(500);
	echo json_encode(['code' => 500, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * Tilda send test=test in get request to check if our server is alive. We should return code 200 OK. So, this function checks if we have get request with test=test and if we have, then we return code 200 OK.
 * @return void
 */
function check_for_tilda_test() {
	$data = get_universal_post_data();
	if (empty($data)) return;
	if (isset($_POST['test']) && $_POST['test'] === 'test') {
        header('Content-type: application/json;charset=utf-8');
        http_response_code(200);
        echo json_encode(['code' => 200, 'message' => 'OK'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Function to get lead from post request and send it to our CRM
 * @return void
 * @throws Exception - if there is no post request
 */
function get_lead_from_post() {
	$data = get_universal_post_data();
	if (empty($data)) {
		throw new Exception ('Error. Nothing has been received.');
	}
	$data['integration_id'] = INTEGRATION_ID;
	$data['city'] ??= INTEGRATION_CITY;
	$data['situation'] ??= 'Нет описания проблемы лида';
	$results = send_lead(CRM_URL, $data);

	header('Content-type: application/json;charset=utf-8');
	http_response_code($results['code']);
	echo json_encode($results, JSON_UNESCAPED_UNICODE);
}

/**
 * Function to get POST data regardless of the Content-Type. 
 * @return array $postData - POST data
 */
function get_universal_post_data(): array {
    if (!empty($_POST)) {
        return $_POST;
    }

    $rawData = file_get_contents('php://input');
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $decodedData = json_decode($rawData, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodedData;
        }
    } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        parse_str($rawData, $decodedData);
        return $decodedData;
    } else {
        parse_str($rawData, $decodedData);
        return $decodedData;
    }

    return [];
}


?>
