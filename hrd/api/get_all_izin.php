<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hrd') {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'Akses ditolak']);
  exit;
}
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

$q = "
SELECT i.karyawan_id, k.nama, i.tanggal, i.alasan, i.status
FROM izin i
JOIN karyawan k ON i.karyawan_id = k.id
ORDER BY i.tanggal ASC
";
$res = $conn->query($q);
$data = [];
while($r = $res->fetch_assoc()) $data[] = $r;

echo json_encode(['success'=>true, 'data'=>$data]);
?>
