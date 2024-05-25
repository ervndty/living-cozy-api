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
    // Ambil semua item keranjang berdasarkan user_id
    if (isset($_GET['user_id'])) {
        $userId = $_GET['user_id'];
        $sql = "SELECT c.*, p.nama_produk, p.harga 
                FROM Cart c 
                JOIN Product p ON c.product_id = p.product_id 
                WHERE c.user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $cartItems = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $cartItems);
        } else {
            sendResponse(500, null, "Error mengambil data keranjang belanja.");
        }
    } else {
        sendResponse(400, null, "User ID tidak disediakan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambahkan item ke keranjang belanja
    $input = json_decode(file_get_contents("php://input"), true);

    if (isset($input['user_id'], $input['product_id'], $input['jumlah'])) {
        $userId = $input['user_id'];
        $productId = $input['product_id'];
        $jumlah = $input['jumlah'];

        // Validasi input
        $errors = [];

        if (empty($userId)) {
            $errors[] = "User ID harus diisi.";
        }
        if (empty($productId)) {
            $errors[] = "Product ID harus diisi.";
        }
        if (!is_numeric($jumlah) || $jumlah <= 0) {
            $errors[] = "Jumlah harus berupa angka positif.";
        }

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO Cart (user_id, product_id, jumlah) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $userId, $productId, $jumlah);

        if ($stmt->execute()) {
            sendResponse(201, ['cart_id' => $stmt->insert_id]);
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update jumlah item di keranjang belanja
    if (isset($_GET['cart_id'])) {
        $cartId = $_GET['cart_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (isset($input['jumlah'])) {
            $jumlah = $input['jumlah'];

            // Validasi input
            $errors = [];

            if (!is_numeric($jumlah) || $jumlah <= 0) {
                $errors[] = "Jumlah harus berupa angka positif.";
            }

            if (!empty($errors)) {
                sendResponse(400, null, $errors);
                return;
            }

            $stmt = $db->prepare("UPDATE Cart SET jumlah = ? WHERE cart_id = ?");
            $stmt->bind_param("ii", $jumlah, $cartId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(200, ['message' => 'Jumlah item berhasil diperbarui.']);
                } else {
                    sendResponse(404, null, "Item keranjang tidak ditemukan.");
                }
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            sendResponse(400, null, "Data tidak lengkap.");
        }
    } else {
        sendResponse(400, null, "Cart ID tidak ditemukan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Hapus item dari keranjang belanja
    if (isset($_GET['cart_id'])) {
        $cartId = $_GET['cart_id'];

        $stmt = $db->prepare("DELETE FROM Cart WHERE cart_id = ?");
        $stmt->bind_param("i", $cartId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(204); // No Content
            } else {
                sendResponse(404, null, "Item keranjang tidak ditemukan.");
            }
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Cart ID tidak ditemukan.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
