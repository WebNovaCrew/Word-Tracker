<?php
// backend-php/api/preview_plan.php

$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/core/Algorithm.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->total_word_count) &&
    !empty($data->start_date) &&
    !empty($data->end_date) &&
    !empty($data->algorithm_type)
) {
    $rules = [
        'intensity' => isset($data->strategy_intensity) ? $data->strategy_intensity : 'average',
        'weekend_rule' => isset($data->weekend_rule) ? $data->weekend_rule : 'none',
        'custom_rules' => isset($data->custom_rules) ? $data->custom_rules : []
    ];

    $schedule = Algorithm::calculate(
        $data->algorithm_type,
        $data->total_word_count,
        $data->start_date,
        $data->end_date,
        $rules
    );

    if (!empty($schedule)) {
        http_response_code(200);
        echo json_encode(array("success" => true, "data" => $schedule));
    } else {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Could not calculate schedule."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data."));
}
?>