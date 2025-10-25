<?php
require_once("../config.php");
header("Content-Type: application/json");

try {
  $sql = "
    SELECT 
      a.karyawan_id, 
      k.nama, 
      a.tanggal, 
      a.jam_masuk, 
      a.jam_keluar, 
      a.status
    FROM absensi a
    JOIN karyawan k ON a.karyawan_id = k.id
    ORDER BY a.tanggal ASC
  ";

  $result = $conn->query($sql);

  if (!$result) {
    throw new Exception("Query gagal: " . $conn->error);
  }

  $rows = [];
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
  }

  echo json_encode(["success" => true, "data" => $rows]);
} catch (Exception $e) {
  echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

$conn->close();
?>
