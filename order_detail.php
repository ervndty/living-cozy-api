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
    // Ambil detail pesanan berdasarkan order_id
    if (isset($_GET['order_id'])) {
        $orderId = $_GET['order_id'];
        $sql = "SELECT * FROM OrderDetail WHERE order_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $orderDetails = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $orderDetails);
        } else {
            sendResponse(500, null, "Error mengambil detail pesanan.");
        }
    } else {
        sendResponse(400, null, "Order ID tidak disediakan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambahkan detail pesanan baru
    $input = json_decode(file_get_contents("php://input"), true);

    if (
        isset($input['order_id'], $input['product_id'], $input['jumlah'], $input['harga_satuan']) &&
        is_numeric($input['order_id']) &&
        is_numeric($input['product_id']) &&
        is_numeric($input['jumlah']) && $input['jumlah'] > 0 &&
        is_numeric($input['harga_satuan']) && $input['harga_satuan'] >= 0
    ) {
        $orderId = $input['order_id'];
        $productId = $input['product_id'];
        $jumlah = $input['jumlah'];
        $hargaSatuan = $input['harga_satuan'];
        $diskon = isset($input['diskon']) && is_numeric($input['diskon']) && $input['diskon'] >= 0 ? $input['diskon'] : 0;

        // Validasi order_id dan product_id (apakah keduanya ada di tabel masing-masing)
        $checkOrderStmt = $db->prepare("SELECT order_id FROM Orders WHERE order_id = ?");
        $checkOrderStmt->bind_param("i", $orderId);
        $checkOrderStmt->execute();
        $checkOrderResult = $checkOrderStmt->get_result();
        if ($checkOrderResult->num_rows === 0) {
            sendResponse(400, null, "Order ID tidak valid.");
            return;
        }
        $checkOrderStmt->close();

        $checkProductStmt = $db->prepare("SELECT product_id FROM Product WHERE product_id = ?");
        $checkProductStmt->bind_param("i", $productId);
        $checkProductStmt->execute();
        $checkProductResult = $checkProductStmt->get_result();
        if ($checkProductResult->num_rows === 0) {
            sendResponse(400, null, "Product ID tidak valid.");
            return;
        }
        $checkProductStmt->close();

        $stmt = $db->prepare("INSERT INTO OrderDetail (order_id, product_id, jumlah, harga_satuan, diskon) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $orderId, $productId, $jumlah, $hargaSatuan, $diskon);

        if ($stmt->execute()) {
            sendResponse(201, ['order_detail_id' => $stmt->insert_id]);
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap atau format tidak valid.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update detail pesanan (jumlah, harga_satuan, diskon) berdasarkan order_detail_id
    if (isset($_GET['order_detail_id'])) {
        $orderDetailId = $_GET['order_detail_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (
            isset($input['jumlah'], $input['harga_satuan'], $input['diskon']) &&
            is_numeric($input['jumlah']) && $input['jumlah'] > 0 &&
            is_numeric($input['harga_satuan']) && $input['harga_satuan'] >= 0 &&
            is_numeric($input['diskon']) && $input['diskon'] >= 0
        ) {
            $jumlah = $input['jumlah'];
            $hargaSatuan = $input['harga_satuan'];
            $diskon = $input['diskon'];

            $stmt = $db->prepare("UPDATE OrderDetail SET jumlah=?, harga_satuan=?, diskon=? WHERE order_detail_id=?");
            $stmt->bind_param("iddi", $jumlah, $hargaSatuan, $diskon, $orderDetailId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(200, ['message' => 'Detail pesanan berhasil diperbarui.']);
                } else {
                    sendResponse(404, null, "Detail pesanan tidak ditemukan.");
                }
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            sendResponse(400, null, "Data tidak lengkap atau format tidak valid.");
        }
    } else {
        sendResponse(400, null, "Order Detail ID tidak ditemukan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Hapus detail pesanan berdasarkan order_detail_id
    if (isset($_GET['order_detail_id'])) {
        $orderDetailId = $_GET['order_id'];

        $stmt = $db->prepare("DELETE FROM OrderDetail WHERE order_detail_id = ?");
        $stmt->bind_param("i", $orderDetailId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(204); 
            } else {
                sendResponse(404, null, "Detail pesanan tidak ditemukan.");
            }
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Order Detail ID tidak ditemukan.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
