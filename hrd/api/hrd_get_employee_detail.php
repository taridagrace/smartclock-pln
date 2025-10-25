<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hrd') {
  http_response_code(403); echo json_encode(['success'=>false]); exit;
}
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

$user_id = intval($_GET['user_id'] ?? 0);
$month = intval($_GET['month'] ?? date('n'));
$year  = intval($_GET['year'] ?? date('Y'));
if (!$user_id) { echo json_encode(['success'=>false,'message'=>'user_id kosong']); exit; }

$stmt = $conn->prepare("SELECT
  SUM(status IN ('hadir','terlambat')) AS hadir,
  SUM(status='terlambat') AS telat,
  SUM(status='izin') AS izin,
  SUM(status='alpha') AS alpha,
  SUM( LEAST( GREATEST( TIME_TO_SEC(TIMEDIFF(jam_keluar,jam_masuk))/3600 - 8, 0), 4) ) AS ot_sum
  FROM absensi WHERE karyawan_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
$stmt->bind_param('iii',$user_id,$month,$year);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
echo json_encode(['success'=>true,'data'=>[
  'hadir'=>intval($row['hadir']),
  'telat'=>intval($row['telat']),
  'izin'=>intval($row['izin']),
  'alpha'=>intval($row['alpha']),
  'ot'=>round(floatval($row['ot_sum']),2)
]]);
