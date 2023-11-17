<?php

/**
 * PHP 8.2 script for sending leads to LawTask CRM API.
 * API instruction: https://telegra.ph/API-06-17
 * This script collects lead and technical data, checks their validity and consistency, sends lead to API and checks the result.
 */

// SETUP
const API_URL = 'https://lawtask.pro/API/api.php';
// Setup valid params: is it required and max length. For example, lead_id is not required, but if it exists, it should be no longer than 30 characters.
const VALID_PARAMS = [
	'integration_id' => [true, 30],
	'phone' => [true, 30],
	'name' => [false, 250],
	'city' => [true, 70],
	'situation' => [true, 3500],
	'test' => [false, 5],
];

/**
 * Function to make response back to HTML
 * @param string $status - 'success' or 'fail'
 * @param string $reason - reason of 'fail'
 * @return void - just send json response
 * @throws JsonException - if something is wrong
 */
function response(string $status, string $reason = ''): void {
	error_log('FAIL: lead was not send: ' . $reason);
    header('Content-Type: application/json');
    $response = [
        'status' => $status,
        'reason' => $reason
    ];

    try {
        echo json_encode($response, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'fail', 'reason' => 'Internal Server Error'], JSON_THROW_ON_ERROR);
    }
}


/**
 * Function to check lead data consistency and validity
 * @param array $lead - array with lead and technical data
 * @return array - array with result and reason [result => bool, reason => string].
 * @throws Exception - if something is wrong
 */
function is_lead_valid(array $lead): array {
	try {
		foreach (VALID_PARAMS as $param => [$required, $max]) {
			if ($required && !key_exists($param, $lead)) {
				throw new Exception("$param is missing");
			} else if (strlen($lead[$param]) > $max) {
				throw new Exception("$param has to be $max length max.");
			}
		}
		return ['result' => true, 'reason' => ''];
	} catch (Exception $e) {
		error_log(
			$e->getMessage() 
			. ' on line ' . $e->getLine() 
			. ' in ' . $e->getFile()
		);
		return [
			'result' => false, 
			'reason' => $e->getMessage()
		];
	}
}

/** 
* Send a POST request using cURL 
* @param array $lead values to send 
* @return array [
		result => bool,
		code => int,
		reason => string,
		data => array
	]
* @throws Exception if something is wrong
*/ 
function send_lead(array $lead): array {
    $post_params = array( 
        CURLOPT_POST => 1, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => API_URL, 
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1, 
        CURLOPT_TIMEOUT => 4, 
        CURLOPT_POSTFIELDS => http_build_query($lead) 
    ); 
	try {
		$ch = curl_init(); 
		curl_setopt_array($ch, $post_params); 
		if (!$result = curl_exec($ch)) {
			throw new Exception(curl_error($ch)); 
		} 
		curl_close($ch);
		return json_decode($result, true);
	} catch (Exception $e) {
		$error_message = $e->getMessage() 
			. ' on line ' . $e->getLine() 
			. ' in ' . $e->getFile();
		error_log($error_message);
		return [
			'result' => false,
			'code' => 400,
			'reason' => $error_message,
			'data' => $lead
		];
	}
}

// START
$lead = $_POST;

// Check lead data consistency and validity before sending
[
	'result' => $result, 
	'reason' => $reason
] = is_lead_valid($lead);
if (!$result) {
	response('fail', $reason);
	exit;
}

// Send lead to API
[
	'result' =>  $result, 
	'code' => $code,
	'reason' => $reason, 
	'data' => $data
] = send_lead(lead: $lead);

if (!$result) {
	// it's mean that lead was not accepted or not saved on the receiving side
	$msg = "FAIL: Error with code $code. Lead was not send or save because $reason";
	response('fail', $msg);
} else {
	// mean that lead was accepted and saved on the receiving side
	response('success');
}

?>
