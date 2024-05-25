<?php
require_once "db.php"; // File untuk koneksi database

// Fungsi untuk response JSON
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
    $sql = "SELECT user_id, username, email FROM users"; 
    $result = $db->query($sql);

    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
        sendResponse(200, $users);
    } else {
        sendResponse(500, null, "Error mengambil data pengguna.");
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset(
            $_POST['nama_lengkap'], 
            $_POST['alamat'], 
            $_POST['no_tlp'], 
            $_POST['email'], 
            $_POST['username'], 
            $_POST['password']
        ) 
    ) {
        $nama_lengkap = $_POST['nama_lengkap'];
        $alamat = $_POST['alamat'];
        $no_tlp = $_POST['no_tlp'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Hashing password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Validasi input
        $errors = [];

        if (empty($nama_lengkap)) {
            $errors[] = "Nama lengkap harus diisi.";
        }
        if (empty($alamat)) {
            $errors[] = "Alamat harus diisi.";
        }
        if (empty($no_tlp)) {
            $errors[] = "Nomor telepon harusdiisi.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid.";
        }
        if (empty($username)) {
            $errors[] = "Username harus diisi.";
        }
        if (empty($password)) {
            $errors[] = "Password harus diisi.";
        }

        if (!empty($errors)) {
            sendResponse(400, null, $errors);
            return;
        }

        $stmt = $db->prepare("INSERT INTO users (nama_lengkap, alamat, no_tlp, email, username, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nama_lengkap, $alamat, $no_tlp, $email, $username, $hashedPassword);

        if ($stmt->execute()) {
            sendResponse(201, ['user_id' => $stmt->insert_id]); 
        } else {
            if ($stmt->errno == 1062) {
                sendResponse(409, null, "Email sudah terdaftar.");
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "Data tidak lengkap.");
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (isset($_GET['user_id'])) {
        $input = json_decode(file_get_contents("php://input"), true);

        if (
            isset(
                $input['nama_lengkap'],
                $input['alamat'],
                $input['no_tlp'],
                $input['email'],
                $input['username']
            )
        ) {
            $user_id = $_GET['user_id'];
            $nama_lengkap = $input['nama_lengkap'];
            $alamat = $input['alamat'];
            $no_tlp = $input['no_tlp'];
            $email = $input['email'];
            $username = $input['username'];

            // Validasi input
            $errors = [];

            if (empty($nama_lengkap)) {
                $errors[] = "Nama lengkap harus diisi.";
            }
            if (empty($alamat)) {
                $errors[] = "Alamat harus diisi.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format email tidak valid.";
            }
            if (empty($username)) {
                $errors[] = "Username harus diisi.";
            }

            if (!empty($errors)) {
                sendResponse(400, null, $errors);
                return; 
            }

            $stmt = $db->prepare("UPDATE users SET nama_lengkap=?, alamat=?, no_tlp=?, email=?, username=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $nama_lengkap, $alamat, $no_tlp, $email, $username, $user_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(200, ['message' => 'Data pengguna berhasil diperbarui.']);
                } else {
                    sendResponse(404, null, "User ID tidak ditemukan.");
                }
            } else {
                sendResponse(500, null, "Error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            sendResponse(400, null, "Data tidak lengkap.");
        }
    } else {
        sendResponse(400, null, "User ID tidak ditemukan.");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];

        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) { 
                sendResponse(204); 
            } else {
                sendResponse(404, null, "User ID tidak ditemukan.");
            }
        } else {
            sendResponse(500, null, "Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        sendResponse(400, null, "User ID tidak ditemukan.");
    }
} else {
    sendResponse(405, null, "Method tidak diizinkan.");
}
