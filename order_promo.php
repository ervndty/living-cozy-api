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
    // Ambil semua order promo atau berdasarkan order_id atau promo_id
    if (isset($_GET['order_promo_id'])) {
        // Ambil order promo berdasarkan order_promo_id
        $orderPromoId = $_GET['order_promo_id'];
        $sql = "SELECT * FROM OrderPromo WHERE order_promo_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $orderPromoId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $orderPromo = $result->fetch_assoc();
            sendResponse(200, $orderPromo);
        } else {
            sendResponse(404, null, "OrderPromo tidak ditemukan.");
        }
    } elseif (isset($_GET['order_id'])) {
        // Ambil semua order promo berdasarkan order_id
        $orderId = $_GET['order_id'];
        $sql = "SELECT * FROM OrderPromo WHERE order_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $orderPromos = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $orderPromos);
        } else {
            sendResponse(500, null, "Error mengambil data order promo.");
        }
    } elseif (isset($_GET['promo_id'])) {
        // Ambil semua order promo berdasarkan promo_id
        $promoId = $_GET['promo_id'];
        $sql = "SELECT * FROM OrderPromo WHERE promo_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $promoId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $orderPromos = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $orderPromos);
        } else {
            sendResponse(500, null, "Error mengambil data order promo.");
        }
    } else {
        // Ambil semua order promo
        $sql = "SELECT * FROM OrderPromo";
        $result = $db->query($sql);

        if ($result) {
            $orderPromos = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $orderPromos);
        } else {
            sendResponse(500, null, "Error mengambil data order promo.");
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambahkan order promo baru
    $input = json_decode(file_get_contents("php://input"), true);

    if (isset($input['order_id'], $input['promo_id'])) {
        $orderId = $input['order_id'];
        $promoId = $input['promo_id'];

        // Validasi input
        $errors = [];

        // Cek keberadaan order_id dan promo_id di tabel masing-masing
        $checkOrderStmt = $db->prepare("SELECT order_id FROM Orders WHERE order_id = ?");
        $checkOrderStmt->bind_param("i", $orderId);
        $checkOrderStmt->execute();
        if ($checkOrderStmt->get_result()->num_rows === 0) {
            $errors[] = "Order ID tidak valid.";
        }
        $checkOrderStmt->close();

        $checkPromoStmt = $db->prepare("SELECT promo_id FROM Promo WHERE promo_id = ?");
        $checkPromoStmt->bind_param("i", $promoId);
        $checkPromoStmt->execute();
        if ($checkPromoStmt->get_result()->num_rows === 0) {
            $errors[] = "Promo ID tidak valid.";
        }
        $checkPromoStmt->close();

        // Cek apakah order promo sudah ada
        $checkOrderPromoStmt = $db->prepare("SELECT order_promo_id FROM OrderPromo WHERE order_id = ? AND promo_id = ?");
        $checkOrderPromoStmt->bind_param("ii", $orderId, $promoId);
        $checkOrderPromoStmt->execute();
        if ($checkOrderPromoStmt->get_result()->num_rows > 0) {
            $errors[] = "Order promo sudah ada.";
        }
        $checkOrderPromoStmt->close();

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO OrderPromo (order_id, promo_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $orderId, $promoId);

        if ($stmt->execute()) {
            sendResponse(201, ['order_promo_id' => $stmt->insert_id]);
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
