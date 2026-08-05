<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");

echo json_encode([
    [
    "id" => 1,
    "title" => "IT Specialist",
    "grade" => 12,
    "remote" => true
    ],
    [
        "id" => 2,
        "title" => "Management and Program Analyst",
        "grade" => 11,
        "remote" => false
    ]
]);