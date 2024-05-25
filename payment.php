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
    // Ambil semua pembayaran atau pembayaran berdasarkan ID
    if (isset($_GET['payment_id'])) {
        // Ambil pembayaran berdasarkan ID
        $paymentId = $_GET['payment_id'];
        $sql = "SELECT * FROM Payment WHERE payment_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $paymentId);
    } else {
        // Ambil semua pembayaran
        $sql = "SELECT * FROM Payment";
        $stmt = $db->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $payments = $result->fetch_all(MYSQLI_ASSOC);
        sendResponse(200, $payments);
    } else {
        sendResponse(500, null, "Error mengambil data pembayaran.");
    }

    $stmt->close();

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambahkan pembayaran baru
    $input = json_decode(file_get_contents("php://input"), true);

    if (
        isset(
            $input['user_id'],
            $input['order_id'],
            $input['nama_produk'],
            $input['jumlah_produk'],
            $input['harga_produk'],
            $input['metode_bayar'],
            $input['tanggal_pembayaran'] 
        )
    ) {
        $userId = $input['user_id'];
        $orderId = $input['order_id'];
        $namaProduk = $input['nama_produk'];
        $jumlahProduk = $input['jumlah_produk'];
        $hargaProduk = $input['harga_produk'];
        $metodeBayar = $input['metode_bayar'];
        $tanggalPembayaran = $input['tanggal_pembayaran'] ?: date('Y-m-d'); 

        // Validasi input
        $errors = [];

        // Validasi user_id dan order_id (cek apakah ada di tabel masing-masing)
        if (!is_numeric($userId) || $userId <= 0) {
            $errors[] = "User ID harus berupa angka positif.";
        } else {
            $checkUserStmt = $db->prepare("SELECT user_id FROM Users WHERE user_id = ?");
            $checkUserStmt->bind_param("i", $userId);
            $checkUserStmt->execute();
            $checkUserResult = $checkUserStmt->get_result();
            if ($checkUserResult->num_rows === 0) {
                $errors[] = "User ID tidak valid.";
            }
            $checkUserStmt->close();
        }

        if (!is_numeric($orderId) || $orderId <= 0) {
            $errors[] = "Order ID harus berupa angka positif.";
        } else {
            $checkOrderStmt = $db->prepare("SELECT order_id FROM Orders WHERE order_id = ?");
            $checkOrderStmt->bind_param("i", $orderId);
            $checkOrderStmt->execute();
            $checkOrderResult = $checkOrderStmt->get_result();
            if ($checkOrderResult->num_rows === 0) {
                $errors[] = "Order ID tidak valid.";
            }
            $checkOrderStmt->close();
        }

        // Validasi lainnya (nama produk, jumlah, harga, metode bayar, tanggal pembayaran)
        if (empty($namaProduk)) {
            $errors[] = "Nama produk harus diisi.";
        }
        if (!is_numeric($jumlahProduk) || $jumlahProduk <= 0) {
            $errors[] = "Jumlah produk harus berupa angka positif.";
        }
        if (!is_numeric($hargaProduk) || $hargaProduk <= 0) {
            $errors[] = "Harga produk harus berupa angka positif.";
        }
        // Tambahkan validasi untuk metode bayar 
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggalPembayaran)) {
            $errors[] = "Format tanggal pembayaran tidak valid (YYYY-MM-DD).";
        }

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO Payment (user_id, order_id, nama_produk, jumlah_produk, harga_produk, metode_bayar, tanggal_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisdsss", $userId, $orderId, $namaProduk, $jumlahProduk, $hargaProduk, $metodeBayar, $tanggalPembayaran);

        if ($stmt->execute()) {
            sendResponse(201, ['payment_id' => $stmt->insert_id]);
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
