<?php
require_once "db.php"; // File koneksi database

function sendResponse($status, $data = null, $error = null) {
    header('Content-Type: application/json');
    http_response_code($status);

    $response = ['status' => $status];
    if ($data) {
        $response['data'] = $data;
    }
    if ($error) {
        $response['error'] = $error;
    }
    echo json_encode($response);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Ambil semua favorit berdasarkan user_id
    if (isset($_GET['user_id'])) {
        $userId = $_GET['user_id'];
        $sql = "SELECT * FROM Favorite WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $favorites = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $favorites);
        } else {
            sendResponse(500, null, "Error mengambil data favorit.");
        }
    } else {
        sendResponse(400, null, "User ID tidak disediakan.");
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambahkan favorit baru
    $input = json_decode(file_get_contents("php://input"), true);

    if (isset($input['user_id'], $input['product_id'])) {
        $userId = $input['user_id'];
        $productId = $input['product_id'];

        // Validasi input
        $errors = [];

        if (empty($userId)) {
            $errors[] = "User ID harus diisi.";
        }
        if (empty($productId)) {
            $errors[] = "Product ID harus diisi.";
        }

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO Favorite (user_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $productId);

        if ($stmt->execute()) {
            sendResponse(201, ['favorite_id' => $stmt->insert_id]);
        } else {
            if ($stmt->errno == 1062) { 
                sendResponse(409, null, "Produk sudah ada di daftar favorit.");
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Hapus favorit berdasarkan user_id dan product_id
    if (isset($_GET['user_id'], $_GET['product_id'])) {
        $userId = $_GET['user_id'];
        $productId = $_GET['product_id'];

        $stmt = $db->prepare("DELETE FROM Favorite WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $userId, $productId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(204);
            } else {
                sendResponse(404, null, "Favorit tidak ditemukan.");
            }
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "User ID atau Product ID tidak ditemukan.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
