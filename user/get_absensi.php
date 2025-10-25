<?php
header("Content-Type: application/json");
include "config.php";

$user_id = $_GET['user_id'] ?? '';
$tanggal = date('Y-m-d');

if (!$user_id) {
  echo json_encode(["success" => false, "message" => "User ID kosong"]);
  exit;
}

$stmt = $conn->prepare("SELECT jam_masuk, jam_keluar FROM absensi WHERE karyawan_id=? AND tanggal=?");
$stmt->bind_param("is", $user_id, $tanggal);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  echo json_encode([
    "success" => true,
    "jam_masuk" => $row['jam_masuk'] ?: null,
    "jam_keluar" => $row['jam_keluar'] ?: null
  ]);
} else {
  echo json_encode([
    "success" => true,
    "jam_masuk" => null,
    "jam_keluar" => null,
    "message" => "Belum ada absensi hari ini"
  ]);
}
?>
