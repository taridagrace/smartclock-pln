<?php
header("Content-Type: application/json");
include "config.php";

$action = $_POST['action'] ?? '';
$user_id = $_POST['user_id'] ?? '';

if (!$user_id) {
  echo json_encode(["success" => false, "message" => "User ID tidak ditemukan"]);
  exit;
}

$tanggal = date('Y-m-d');

if ($action === 'clock_in') {
  $jam_masuk = date('H:i:s');

  $cek = $conn->prepare("SELECT * FROM absensi WHERE karyawan_id=? AND tanggal=?");
  $cek->bind_param("is", $user_id, $tanggal);
  $cek->execute();
  $result = $cek->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  echo json_encode([
    "success" => false,
    "message" => "Sudah melakukan Clock In hari ini",
    "jam_masuk" => $row['jam_masuk'] ?? null
  ]);
  exit;
}


  $stmt = $conn->prepare("INSERT INTO absensi (karyawan_id, tanggal, jam_masuk, status) VALUES (?, ?, ?, 'Hadir')");
  $stmt->bind_param("iss", $user_id, $tanggal, $jam_masuk);

  if ($stmt->execute()) {
    echo json_encode([
      "success" => true,
      "message" => "Clock In berhasil pada $jam_masuk",
      "jam_masuk" => $jam_masuk
    ]);
  } else {
    echo json_encode(["success" => false, "message" => "Gagal menyimpan data absensi"]);
  }
}

elseif ($action === 'clock_out') {
  $jam_keluar = date('H:i:s');

  $cek = $conn->prepare("SELECT * FROM absensi WHERE karyawan_id=? AND tanggal=?");
  $cek->bind_param("is", $user_id, $tanggal);
  $cek->execute();
  $result = $cek->get_result();

  if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO absensi (karyawan_id, tanggal, jam_keluar, status) VALUES (?, ?, ?, 'Hadir')");
    $stmt->bind_param("iss", $user_id, $tanggal, $jam_keluar);
  } else {
    $stmt = $conn->prepare("UPDATE absensi SET jam_keluar=? WHERE karyawan_id=? AND tanggal=?");
    $stmt->bind_param("sis", $jam_keluar, $user_id, $tanggal);
  }

  if ($stmt->execute()) {
    echo json_encode([
      "success" => true,
      "message" => "Clock Out berhasil pada $jam_keluar",
      "jam_keluar" => $jam_keluar
    ]);
  } else {
    echo json_encode(["success" => false, "message" => "Gagal update data absensi"]);
  }
}

else {
  echo json_encode(["success" => false, "message" => "Aksi tidak valid"]);
}
?>
