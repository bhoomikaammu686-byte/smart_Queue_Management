
<?php
header("Content-Type: application/json");
session_start();

require_once "db.php";

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data["username"] ?? "");
$password = $data["password"] ?? "";

if ($username === "" || $password === "") {
    respond([
        "success" => false,
        "error" => "Please enter username and password."
    ], 400);
}

$stmt = $conn->prepare(
    "SELECT id, username, email, password_hash
     FROM users
     WHERE username = ? OR email = ?
     LIMIT 1"
);

if (!$stmt) {
    respond([
        "success" => false,
        "error" => "Database query failed."
    ], 500);
}

$stmt->bind_param("ss", $username, $username);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user["password_hash"])) {

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["username"] = $user["username"];

    respond([
        "success" => true,
        "message" => "Login successful.",
        "username" => $user["username"]
    ]);

} else {
    respond([
        "success" => false,
        "error" => "Invalid username or password."
    ], 401);
}

$stmt->close();
$conn->close();
?>
