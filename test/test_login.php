<?php
echo "<h2>TEST LOGIN</h2>";

$url = "http://localhost/sisco-edu/src/api/login.php";

$data = [
    "usuario" => "admin1",
    "password" => "123456"
];

$options = [
    "http" => [
        "header"  => "Content-type: application/x-www-form-urlencoded",
        "method"  => "POST",
        "content" => http_build_query($data),
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<pre>";
print_r($result);
echo "</pre>";
?>