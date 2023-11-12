<?php

/**
 * Пример кода на PHP8+ для отправки лада в API LawTask от 28.07.2022.
 * Инструкция по работе с API: https://telegra.ph/API-06-17
 * Данный скрипт собирает лид и нужные тех. данные, проверяет их валидность и консистентность, отправляет лида в API и проверяет результат.
 */

function is_lead_valid(array $lead): array {
	/**
	 * Функция проверяет консистентность и валидность массива данных перед отправкой в API. Если что-то не так, выбрасывает исключение, логгирует ошибку. Возвращает результат и описание ошибки.
	 * @param array $lead - Массив c данными лида и тех. информацией
	 * @return array - массив с результатом и описанием [result => bool, reason => string].
	 */

	// Зададим валидные параметры: требуется ли вообще и максимальная длина значения. Например, lead_id - необязателен, но если есть, то должен быть не длиннее 30 символов.
	$valid_params = [
		'lead_id' => [false, 30],
		'integration_id' => [true, 30],
		'phone' => [true, 30],
		'name' => [false, 250],
		'city' => [true, 70],
		'situation' => [true, 3500],
		'test' => [false, 5],
	];

	try {
		foreach ($valid_params as $param => [$required, $max]) {
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

function send_lead(string $url, array $lead): array {
	/** 
	* Send a POST request using cURL 
	* @param string $url to request 
	* @param array $lead values to send 
	* @return array [
			result => bool,
			code => int,
			reason => string,
			data => array
		]
	*/ 
    
    $post_params = array( 
        CURLOPT_POST => 1, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => $url, 
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

/**
 * Начало
 * Предположим, мы получили с фронтенда заполненную лидом форму. Соберём в массив полученные данные.
 */
$lead = [
	'name' => 'Дмитрий',
	'situation' => 'Нужна такая-то помощь юриста',
	'phone' => '+123 (456) 78-90', // в любом формате
	'city' => 'Деревня такая-то'
];

// Пользователь мог указать какой-то некорректный город, поэтому, на всякий случай, захардкодим город когда это возможно:
$lead['city'] = 'Такой-то город';

/**
 * Дополним массив техническими данными. 
 * Подробнее https://telegra.ph/API-06-17
 */
$technical_info = [
	'integration_id' => 12345, // обязательно
	'lead_id' => 54321, // факультативно
	'test' => true // факультативно
];
$lead = [...$lead, ...$technical_info];

// Проверим консистентность и валидность данных перед отправкой.
[
	'result' => $result, 
	'reason' => $reason
] = is_lead_valid($lead);
if (!$result) {
	error_log('FAIL: lead was not send');
	exit; // можно вернуть описание ошибки пользователю
}

// Передадим лид
[
	'result' =>  $result, 
	'code' => $code,
	'reason' => $reason, 
	'data' => $data
] = send_lead(
	url: 'https://lawtask.pro/API/api.php', 
	lead: $lead,
);
if (!$result) {
	// значит лид не был принят или не был сохранен на принимающей стороне
	error_log("FAIL: Error with code $code. Lead was not send or save because $reason");
	echo 'FAIL';
} else {
	echo 'SUCCESS';
}

?>
