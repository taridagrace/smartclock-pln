<?php
header("Content-Type: application/json");
include "config.php";

$user_id = $_GET['user_id'] ?? '';
$month   = $_GET['month'] ?? date('m');
$year    = $_GET['year'] ?? date('Y');

if (!$user_id) {
  echo json_encode(["success" => false, "message" => "User ID tidak ditemukan"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT tanggal, jam_masuk, jam_keluar, status 
  FROM absensi 
  WHERE karyawan_id=? 
    AND MONTH(tanggal)=? 
    AND YEAR(tanggal)=?
  ORDER BY tanggal ASC
");
$stmt->bind_param("iii", $user_id, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
  $tgl = date('j', strtotime($row['tanggal']));
  $data[$tgl] = [
    "tanggal" => $row['tanggal'],
    "jam_masuk" => $row['jam_masuk'],
    "jam_keluar" => $row['jam_keluar'],
    "status" => ucfirst($row['status'])
  ];
}

echo json_encode(["success" => true, "data" => $data]);
?>
