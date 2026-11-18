
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getInput() {
    $input = json_decode(file_get_contents("php://input"), true);
    return is_array($input) ? $input : [];
}

$action = $_GET["action"] ?? "";

try {
    switch ($action) {

        // GET ALL QUEUE TOKENS
        case "list":

            $sql = "SELECT id, token_no, customer_name, service,
                           status, created_at, called_at, completed_at
                    FROM queue_tokens
                    ORDER BY id DESC";

            $result = $conn->query($sql);
            $tokens = [];

            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }

            respond($tokens);
            break;


        // GENERATE A NEW TOKEN
        case "add":

            $data = getInput();

            $name = trim($data["customer_name"] ?? "");
            $service = trim($data["service"] ?? "");

            $allowedServices = [
                "General Enquiry",
                "Document Verification",
                "Customer Support",
                "Payment Service"
            ];

            if ($name === "" || strlen($name) > 100) {
                respond(["error" => "Enter a valid customer name."], 422);
            }

            if (!in_array($service, $allowedServices, true)) {
                respond(["error" => "Select a valid service."], 422);
            }

            // Generate short numeric tokens: 001, 002, 003...
            $result = $conn->query(
                "SELECT COALESCE(
                    MAX(CAST(token_no AS UNSIGNED)), 0
                 ) AS last_token
                 FROM queue_tokens
                 WHERE token_no REGEXP '^[0-9]+$'"
            );

            $row = $result->fetch_assoc();
            $nextNumber = ((int)$row["last_token"]) + 1;

            $tokenNo = str_pad(
                (string)$nextNumber,
                3,
                "0",
                STR_PAD_LEFT
            );

            $stmt = $conn->prepare(
                "INSERT INTO queue_tokens
                 (token_no, customer_name, service, status)
                 VALUES (?, ?, ?, 'Waiting')"
            );

            $stmt->bind_param("sss", $tokenNo, $name, $service);
            $stmt->execute();

            respond([
                "success" => true,
                "message" => "Token created successfully.",
                "id" => $conn->insert_id,
                "token_no" => $tokenNo,
                "customer_name" => $name,
                "service" => $service,
                "status" => "Waiting"
            ], 201);

            break;


        // CALL THE NEXT WAITING TOKEN
        case "call_next":

            $conn->begin_transaction();

            try {
                $activeResult = $conn->query(
                    "SELECT id FROM queue_tokens
                     WHERE status = 'Serving'
                     LIMIT 1 FOR UPDATE"
                );

                if ($activeResult->num_rows > 0) {
                    $conn->rollback();

                    respond([
                        "error" => "Complete the currently serving token before calling the next customer."
                    ], 409);
                }

                $result = $conn->query(
                    "SELECT id, token_no, customer_name, service
                     FROM queue_tokens
                     WHERE status = 'Waiting'
                     ORDER BY id ASC
                     LIMIT 1 FOR UPDATE"
                );

                $token = $result->fetch_assoc();

                if (!$token) {
                    $conn->rollback();

                    respond([
                        "error" => "There are no waiting customers."
                    ], 404);
                }

                $stmt = $conn->prepare(
                    "UPDATE queue_tokens
                     SET status = 'Serving', called_at = NOW()
                     WHERE id = ? AND status = 'Waiting'"
                );

                $stmt->bind_param("i", $token["id"]);
                $stmt->execute();

                if ($stmt->affected_rows !== 1) {
                    throw new Exception("Token could not be called.");
                }

                $conn->commit();

                respond([
                    "success" => true,
                    "message" => "Next token is now being served.",
                    "token" => $token["token_no"],
                    "customer_name" => $token["customer_name"],
                    "service" => $token["service"],
                    "status" => "Serving"
                ]);

            } catch (Throwable $e) {
                try {
                    $conn->rollback();
                } catch (Throwable $ignored) {
                }

                throw $e;
            }

            break;


        // COMPLETE A SERVING TOKEN
        case "complete":

            $data = getInput();

            $id = filter_var(
                $data["id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                respond(["error" => "A valid token ID is required."], 422);
            }

            $stmt = $conn->prepare(
                "UPDATE queue_tokens
                 SET status = 'Completed', completed_at = NOW()
                 WHERE id = ? AND status = 'Serving'"
            );

            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                respond([
                    "error" => "Token not found or is not currently being served."
                ], 409);
            }

            respond([
                "success" => true,
                "message" => "Service completed successfully."
            ]);

            break;


        // DELETE A TOKEN
        case "delete":

            $data = getInput();

            $id = filter_var(
                $data["id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                respond(["error" => "A valid token ID is required."], 422);
            }

            $stmt = $conn->prepare(
                "DELETE FROM queue_tokens WHERE id = ?"
            );

            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                respond([
                    "error" => "Token not found. Refresh the queue and try again."
                ], 404);
            }

            respond([
                "success" => true,
                "message" => "Token deleted successfully.",
                "id" => $id
            ]);

            break;


        // INVALID ACTION
        default:
            respond(["error" => "Invalid action."], 400);
    }

} catch (Throwable $e) {
    error_log("SmartQueue API error: " . $e->getMessage());

    respond([
        "error" => "A server error occurred. Check the PHP terminal."
    ], 500);
}
?>
