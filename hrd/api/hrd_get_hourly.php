<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hrd') {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'Akses ditolak']);
  exit;
}
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

$month = intval($_GET['month'] ?? date('n'));
$year  = intval($_GET['year'] ?? date('Y'));

$hours = [7,8];

$data = [];
foreach ($hours as $h) {
  $label = sprintf('%02d:00', $h);
  $stmt = $conn->prepare("SELECT GROUP_CONCAT(CONCAT(k.nama,' (',TIME_FORMAT(a.jam_masuk,'%H:%i'),')') SEPARATOR '||') AS names, COUNT(*) AS cnt
    FROM absensi a
    JOIN karyawan k ON k.id = a.karyawan_id
    WHERE a.jam_masuk IS NOT NULL
      AND HOUR(a.jam_masuk)=? 
      AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=?");
  $stmt->bind_param('iii', $h, $month, $year);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $names = $row['names'] ? explode('||', $row['names']) : [];
  $data[] = ['hour'=>$h, 'label'=>$label, 'count'=>intval($row['cnt']), 'names'=>$names];
}
echo json_encode(['success'=>true,'data'=>$data]);
