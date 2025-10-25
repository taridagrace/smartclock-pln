<?php
header("Content-Type: application/json");
include "config.php";

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
  echo json_encode(["success" => false, "message" => "Email dan password wajib diisi"]);
  exit;
}

$stmt = $conn->prepare("SELECT * FROM karyawan WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && $user['password'] === $password) {
  echo json_encode([
    "success" => true,
    "data" => [
      "id" => $user['id'],
      "nama" => $user['nama'],
      "jabatan" => $user['jabatan'],
      "role" => $user['role']
    ]
  ]);
} else {
  echo json_encode(["success" => false, "message" => "Username atau password salah"]);
}
?>
