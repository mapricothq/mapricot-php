<?php

$log = getenv('LOG');
file_put_contents($log, $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);
$failure = json_decode(getenv('FAILURES'), true)[count(file($log)) - 1] ?? null;
$body = file_get_contents('php://input');
if ($body !== '') {
    file_put_contents($log, $body . "\n", FILE_APPEND);
}
header('Content-Type: application/json');
header('X-Request-Id: r1');
if ($failure === null) {
    echo '{"ok":true}';
    return;
}
http_response_code($failure[0]);
echo json_encode(['error' => ['code' => $failure[1], 'message' => $failure[1]], 'requestId' => 'r1']);
