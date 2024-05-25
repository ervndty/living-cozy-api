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
    // Ambil semua kategori
    $sql = "SELECT * FROM Category";
    $result = $db->query($sql);

    if ($result) {
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        sendResponse(200, $categories);
    } else {
        sendResponse(500, null, "Error mengambil data kategori.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nama_kategori'])) {
        $namaKategori = $_POST['nama_kategori'];

        // Validasi input 
        $errors = [];

        if (empty($namaKategori)) {
            $errors[] = "Nama kategori harus diisi.";
        }

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO Category (nama_kategori) VALUES (?)");
        $stmt->bind_param("s", $namaKategori);

        if ($stmt->execute()) {
            sendResponse(201, ['category_id' => $stmt->insert_id]); 
        } else {
            if ($stmt->errno == 1062) { 
                sendResponse(409, null, "Nama kategori sudah ada.");
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (isset($_GET['category_id'])) {
        $input = json_decode(file_get_contents("php://input"), true);

        if (isset($input['nama_kategori'])) {
            $categoryId = $_GET['category_id'];
            $namaKategori = $input['nama_kategori'];

            // Validasi input
            $errors = [];
            if (empty($namaKategori)) {
                $errors[] = "Nama kategori harus diisi.";
            }

            if (!empty($errors)) {
                sendResponse(400, null, $errors);
                return; 
            }

            $stmt = $db->prepare("UPDATE Category SET nama_kategori=? WHERE category_id=?");
            $stmt->bind_param("si", $namaKategori, $categoryId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(200, ['message' => 'Data kategori berhasil diperbarui.']);
                } else {
                    sendResponse(404, null, "Kategori tidak ditemukan.");
                }
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            sendResponse(400, null, "Data tidak lengkap.");
        }
    } else {
        sendResponse(400, null, "Category ID tidak ditemukan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['category_id'])) {
        $categoryId = $_GET['category_id'];

        $stmt = $db->prepare("DELETE FROM Category WHERE category_id = ?");
        $stmt->bind_param("i", $categoryId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(204);
            } else {
                sendResponse(404, null, "Kategori tidak ditemukan.");
            }
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Category ID tidak ditemukan.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
