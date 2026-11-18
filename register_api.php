
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond([
        "success" => false,
        "message" => "POST request required."
    ], 405);
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    respond([
        "success" => false,
        "message" => "Invalid request data."
    ], 400);
}

$username = trim($input["username"] ?? "");
$email = trim($input["email"] ?? "");
$password = $input["password"] ?? "";

if ($username === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
    respond([
        "success" => false,
        "message" => "Enter a username, valid email, and password."
    ], 400);
}

if (strlen($username) > 50 || strlen($email) > 150) {
    respond([
        "success" => false,
        "message" => "Username or email is too long."
    ], 400);
}

if (strlen($password) < 8) {
    respond([
        "success" => false,
        "message" => "Password must contain at least 8 characters."
    ], 400);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (username, email, password_hash)
     VALUES (?, ?, ?)"
);

if (!$stmt) {
    respond([
        "success" => false,
        "message" => "Could not prepare registration."
    ], 500);
}

$stmt->bind_param("sss", $username, $email, $passwordHash);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    respond([
        "success" => true,
        "message" => "Registration successful. You can now sign in."
    ], 201);
}

$errorCode = $stmt->errno;
$stmt->close();
$conn->close();

if ($errorCode === 1062) {
    respond([
        "success" => false,
        "message" => "Username or email already exists."
    ], 409);
}

respond([
    "success" => false,
    "message" => "Registration failed. Please check your database."
], 500);
?>
