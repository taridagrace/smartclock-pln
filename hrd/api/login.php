<?php
session_start();
include '../config.php';
header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
  echo json_encode(['status' => 'error', 'message' => 'Email dan password wajib diisi']);
  exit;
}

$stmt = $conn->prepare("SELECT id, nama, email, password, jabatan, role FROM karyawan WHERE email=? AND role='hrd' LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  if ($row['password'] === $password) {

    $_SESSION['user'] = [
      'id' => $row['id'],
      'nama' => $row['nama'],
      'role' => $row['role']
    ];

    echo json_encode(['status' => 'ok']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Password salah']);
  }
} else {
  echo json_encode(['status' => 'error', 'message' => 'Akun HRD tidak ditemukan']);
}
?>
