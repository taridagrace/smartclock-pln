<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hrd') {
  http_response_code(403); echo json_encode(['success'=>false]); exit;
}
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

$month = intval($_GET['month'] ?? date('n'));
$year  = intval($_GET['year'] ?? date('Y'));

$resK = $conn->query("SELECT id,nama FROM karyawan WHERE role!='hrd' ORDER BY nama ASC");
$labels=[]; $tepat=[]; $telat=[];
while($k = $resK->fetch_assoc()){
  $kid = intval($k['id']);
  $labels[] = $k['nama'];

  // Menghitung karyawan yang tepat waktu (<=08:00)
  $stmt1 = $conn->prepare("SELECT COUNT(*) c FROM absensi WHERE karyawan_id=? AND jam_masuk IS NOT NULL AND TIME(jam_masuk) <= '08:00:00' AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
  $stmt1->bind_param('iii',$kid,$month,$year);
  $stmt1->execute();
  $c1 = intval($stmt1->get_result()->fetch_assoc()['c']);

  // Menghitung karyawan yang telat (>08:00 or status='terlambat')
  $stmt2 = $conn->prepare("SELECT COUNT(*) c FROM absensi WHERE karyawan_id=? AND ((jam_masuk IS NOT NULL AND TIME(jam_masuk) > '08:00:00') OR status='terlambat') AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
  $stmt2->bind_param('iii',$kid,$month,$year);
  $stmt2->execute();
  $c2 = intval($stmt2->get_result()->fetch_assoc()['c']);

  $tepat[] = $c1;
  $telat[] = $c2;
}

echo json_encode(['success'=>true,'data'=>['labels'=>$labels,'tepat'=>$tepat,'telat'=>$telat]]);
