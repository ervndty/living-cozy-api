<?php
require_once "db.php"; // File untuk koneksi database

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
    // Ambil semua produk atau produk berdasarkan ID
    if (isset($_GET['product_id'])) {
        // Ambil produk berdasarkan ID
        $productId = $_GET['product_id'];
        $sql = "SELECT * FROM Product WHERE product_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
            sendResponse(200, $product);
        } else {
            sendResponse(404, null, "Produk tidak ditemukan.");
        }
    } else {
        // Ambil semua produk
        $sql = "SELECT * FROM Product";
        $result = $db->query($sql);

        if ($result) {
            $products = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $products);
        } else {
            sendResponse(500, null, "Error mengambil data produk.");
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambahkan produk baru
    if (
        isset(
            $_POST['nama_produk'],
            $_POST['harga'],
            $_POST['deskripsi_bahan'],
            $_POST['kategori_id']
        )
    ) {
        $namaProduk = $_POST['nama_produk'];
        $harga = $_POST['harga'];
        $deskripsiBahan = $_POST['deskripsi_bahan'];
        $kategoriId = $_POST['kategori_id'];

        // Validasi input (tambahkan validasi yang lebih lengkap sesuai kebutuhan)
        $errors = [];

        if (empty($namaProduk)) {
            $errors[] = "Nama produk harus diisi.";
        }
        if (!is_numeric($harga) || $harga <= 0) {
            $errors[] = "Harga harus berupa angka positif.";
        }
        if (empty($deskripsiBahan)) {
            $errors[] = "Deskripsi bahan harus diisi.";
        }
        if (empty($kategoriId)) {
            $errors[] = "Kategori ID harus diisi.";
        }

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO Product (nama_produk, harga, foto, deskripsi_bahan, kategori_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdsss", $namaProduk, $harga, $foto, $deskripsiBahan, $kategoriId);

        if ($stmt->execute()) {
            sendResponse(201, ['product_id' => $stmt->insert_id]);
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update produk berdasarkan ID
    if (isset($_GET['product_id'])) {
        $productId = $_GET['product_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (
            isset(
                $input['nama_produk'],
                $input['harga'],
                $input['deskripsi_bahan'],
                $input['kategori_id']
            )
        ) {
            $namaProduk = $input['nama_produk'];
            $harga = $input['harga'];
            $deskripsiBahan = $input['deskripsi_bahan'];
            $kategoriId = $input['kategori_id'];

            // Validasi input (tambahkan validasi yang lebih lengkap sesuai kebutuhan)
            // ...

            // Update data produk (tanpa mengubah foto untuk saat ini)
            $stmt = $db->prepare("UPDATE Product SET nama_produk=?, harga=?, deskripsi_bahan=?, kategori_id=? WHERE product_id=?");
            $stmt->bind_param("sdssi", $namaProduk, $harga, $deskripsiBahan, $kategoriId, $productId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(200, ['message' => 'Data produk berhasil diperbarui.']);
                } else {
                    sendResponse(404, null, "Produk tidak ditemukan.");
                }
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            sendResponse(400, null, "Data tidak lengkap.");
        }
    } else {
        sendResponse(400, null, "Product ID tidak ditemukan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Hapus produk berdasarkan ID
    if (isset($_GET['product_id'])) {
        $productId = $_GET['product_id'];

        $stmt = $db->prepare("DELETE FROM Product WHERE product_id = ?");
        $stmt->bind_param("i", $productId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(204); 
            } else {
                sendResponse(404, null, "Produk tidak ditemukan.");
            }
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Product ID tidak ditemukan.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
