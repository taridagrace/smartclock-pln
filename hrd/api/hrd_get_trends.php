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

$sql = "SELECT 
          TIME_FORMAT(a.jam_masuk, '%H:%i') AS jam_hhmm,
          COUNT(DISTINCT a.karyawan_id) AS jumlah,
          GROUP_CONCAT(DISTINCT k.nama ORDER BY k.nama SEPARATOR '||') AS names
        FROM absensi a
        JOIN karyawan k ON k.id = a.karyawan_id
        WHERE a.jam_masuk IS NOT NULL
          AND MONTH(a.tanggal)=? 
          AND YEAR(a.tanggal)=?
        GROUP BY jam_hhmm
        ORDER BY jam_hhmm ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii',$month,$year);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while($r = $res->fetch_assoc()){
  $data[] = [
    'jam_masuk' => $r['jam_hhmm'],
    'jumlah' => intval($r['jumlah']),
    'names' => $r['names']
  ];
}

echo json_encode(['success'=>true,'data'=>$data]);
