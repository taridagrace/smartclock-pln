<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hrd') {
  http_response_code(403); echo json_encode(['success'=>false]); exit;
}
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

$month = intval($_GET['month'] ?? date('n'));
$year  = intval($_GET['year'] ?? date('Y'));

$DAILY_OT_CAP = 4;
$MONTHLY_LIMIT = 40.0; 

$sql = "SELECT k.nama, SUM( LEAST( GREATEST(TIME_TO_SEC(TIMEDIFF(a.jam_keluar,a.jam_masuk))/3600 - 8, 0), ?)) AS ot_sum
        FROM karyawan k
        LEFT JOIN absensi a ON a.karyawan_id = k.id AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=?
        WHERE k.role != 'hrd'
        GROUP BY k.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param('dii',$DAILY_OT_CAP,$month,$year);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while($r=$res->fetch_assoc()){
  $data[] = ['nama'=>$r['nama'],'ot'=>round(floatval($r['ot_sum']),2)];
}
echo json_encode(['success'=>true,'data'=>$data,'monthly_limit'=>$MONTHLY_LIMIT]);
