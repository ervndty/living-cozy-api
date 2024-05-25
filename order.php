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
    // Ambil semua pesanan atau pesanan berdasarkan ID
    if (isset($_GET['order_id'])) {
        // Ambil pesanan berdasarkan ID
        $orderId = $_GET['order_id'];
        $sql = "SELECT * FROM Orders WHERE order_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $order = $result->fetch_assoc();
            sendResponse(200, $order);
        } else {
            sendResponse(404, null, "Pesanan tidak ditemukan.");
        }
    } else {
        // Ambil semua pesanan
        $sql = "SELECT * FROM Orders";
        $result = $db->query($sql);

        if ($result) {
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $orders);
        } else {
            sendResponse(500, null, "Error mengambil data pesanan.");
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Buat pesanan baru
    $input = json_decode(file_get_contents("php://input"), true);

    if (isset($input['user_id'], $input['tanggal_pemesanan'], $input['status_pesanan'])) {
        $userId = $input['user_id'];
        $tanggalPemesanan = $input['tanggal_pemesanan'];
        $statusPesanan = $input['status_pesanan'];
        $promoId = isset($input['promo_id']) ? $input['promo_id'] : null; 

        $errors = [];

        if (empty($userId)) {
            $errors[] = "User ID harus diisi.";
        }
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggalPemesanan)) {
            $errors[] = "Format tanggal pemesanan tidak valid (YYYY-MM-DD).";
        }
        if (empty($statusPesanan)) {
            $errors[] = "Status pesanan harus diisi.";
        }

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO Orders (user_id, tanggal_pemesanan, status_pesanan, promo_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $tanggalPemesanan, $statusPesanan, $promoId);

        if ($stmt->execute()) {
            sendResponse(201, ['order_id' => $stmt->insert_id]);
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update pesanan berdasarkan ID
    if (isset($_GET['order_id'])) {
        $orderId = $_GET['order_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (isset($input['tanggal_pemesanan'], $input['status_pesanan'])) {
            $tanggalPemesanan = $input['tanggal_pemesanan'];
            $statusPesanan = $input['status_pesanan'];
            $promoId = isset($input['promo_id']) ? $input['promo_id'] : null; 

            $stmt = $db->prepare("UPDATE Orders SET tanggal_pemesanan=?, status_pesanan=?, promo_id=? WHERE order_id=?");
            $stmt->bind_param("sssi", $tanggalPemesanan, $statusPesanan, $promoId, $orderId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(200, ['message' => 'Data pesanan berhasil diperbarui.']);
                } else {
                    sendResponse(404, null, "Pesanan tidak ditemukan.");
                }
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            sendResponse(400, null, "Data tidak lengkap.");
        }
    } else {
        sendResponse(400, null, "Order ID tidak ditemukan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Hapus pesanan berdasarkan ID
    if (isset($_GET['order_id'])) {
        $orderId = $_GET['order_id'];

        $stmt = $db->prepare("DELETE FROM Orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(204); 
            } else {
                sendResponse(404, null, "Pesanan tidak ditemukan.");
            }
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Order ID tidak ditemukan.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
