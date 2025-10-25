<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hrd') {
  http_response_code(403); echo json_encode(['success'=>false]); exit;
}
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

$month = intval($_GET['month'] ?? date('n'));
$year  = intval($_GET['year'] ?? date('Y'));

$sql = "SELECT TIME_FORMAT(a.jam_keluar, '%H:%i') AS jam_keluar, COUNT(*) AS jumlah, GROUP_CONCAT(DISTINCT k.nama SEPARATOR '||') AS names
        FROM absensi a
        JOIN karyawan k ON a.karyawan_id = k.id
        WHERE a.jam_keluar IS NOT NULL 
          AND a.jam_keluar > '16:00:00'
          AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=?
        GROUP BY jam_keluar
        ORDER BY jam_keluar ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii',$month,$year);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while($r=$res->fetch_assoc()){
  $data[] = ['jam_keluar'=>$r['jam_keluar'],'jumlah'=>intval($r['jumlah']),'names'=>$r['names']];
}
echo json_encode(['success'=>true,'data'=>$data]);
