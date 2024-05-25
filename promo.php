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
    // Ambil semua promo atau promo berdasarkan ID
    if (isset($_GET['promo_id'])) {
        // Ambil promo berdasarkan ID
        $promoId = $_GET['promo_id'];
        $sql = "SELECT * FROM Promo WHERE promo_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $promoId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $promo = $result->fetch_assoc();
            sendResponse(200, $promo);
        } else {
            sendResponse(404, null, "Promo tidak ditemukan.");
        }
    } else {
        // Ambil semua promo
        $sql = "SELECT * FROM Promo";
        $result = $db->query($sql);

        if ($result) {
            $promos = $result->fetch_all(MYSQLI_ASSOC);
            sendResponse(200, $promos);
        } else {
            sendResponse(500, null, "Error mengambil data promo.");
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambahkan promo baru
    if (
        isset(
            $_POST['kode_promo'],
            $_POST['diskon'],
            $_POST['tanggal_mulai'],
            $_POST['tanggal_berakhir']
        )
    ) {
        $kodePromo = $_POST['kode_promo'];
        $diskon = $_POST['diskon'];
        $tanggalMulai = $_POST['tanggal_mulai'];
        $tanggalBerakhir = $_POST['tanggal_berakhir'];

        // Validasi input
        $errors = [];

        if (empty($kodePromo)) {
            $errors[] = "Kode promo harus diisi.";
        }
        if (!is_numeric($diskon) || $diskon <= 0 || $diskon > 100) {
            $errors[] = "Diskon harus berupa angka antara 0 dan 100.";
        }
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggalMulai)) {
            $errors[] = "Format tanggal mulai tidak valid (YYYY-MM-DD).";
        }
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggalBerakhir)) {
            $errors[] = "Format tanggal berakhir tidak valid (YYYY-MM-DD).";
        }

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO Promo (kode_promo, diskon, tanggal_mulai, tanggal_berakhir) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $kodePromo, $diskon, $tanggalMulai, $tanggalBerakhir);

        if ($stmt->execute()) {
            sendResponse(201, ['promo_id' => $stmt->insert_id]);
        } else {
            if ($stmt->errno == 1062) { // Duplicate entry error
                sendResponse(409, null, "Kode promo sudah ada.");
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update promo berdasarkan ID
    if (isset($_GET['promo_id'])) {
        $promoId = $_GET['promo_id'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (
            isset(
                $input['kode_promo'],
                $input['diskon'],
                $input['tanggal_mulai'],
                $input['tanggal_berakhir']
            )
        ) {
            $kodePromo = $input['kode_promo'];
            $diskon = $input['diskon'];
            $tanggalMulai = $input['tanggal_mulai'];
            $tanggalBerakhir = $input['tanggal_berakhir'];

            // Validasi input (sama seperti pada POST)
            // ...

            $stmt = $db->prepare("UPDATE Promo SET kode_promo=?, diskon=?, tanggal_mulai=?, tanggal_berakhir=? WHERE promo_id=?");
            $stmt->bind_param("sdsss", $kodePromo, $diskon, $tanggalMulai, $tanggalBerakhir, $promoId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(200, ['message' => 'Data promo berhasil diperbarui.']);
                } else {
                    sendResponse(404, null, "Promo tidak ditemukan.");
                }
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            sendResponse(400, null, "Data tidak lengkap.");
        }
    } else {
        sendResponse(400, null, "Promo ID tidak ditemukan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Hapus promo berdasarkan ID
    if (isset($_GET['promo_id'])) {
        $promoId = $_GET['promo_id'];

        $stmt = $db->prepare("DELETE FROM Promo WHERE promo_id = ?");
        $stmt->bind_param("i", $promoId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(204); // No Content
            } else {
                sendResponse(404, null, "Promo tidak ditemukan.");
            }
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Promo ID tidak ditemukan.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
