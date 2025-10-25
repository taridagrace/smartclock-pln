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

$WORK_START = '08:00:00';
$CONSISTENCY_PERCENT = 0.75;
$DAILY_OT_CAP = 4;
$MONTHLY_LIMIT = 40; 

$totalEmployees = intval($conn->query("SELECT COUNT(*) c FROM karyawan WHERE role!='hrd'")->fetch_assoc()['c']);

// rata-rata jam masuk
$stmt = $conn->prepare("SELECT AVG(TIME_TO_SEC(jam_masuk)) AS avg_sec FROM absensi WHERE jam_masuk IS NOT NULL AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
$stmt->bind_param('ii',$month,$year); $stmt->execute();
$avg_sec = $stmt->get_result()->fetch_assoc()['avg_sec'];
$avg_time = $avg_sec ? gmdate('H:i', round($avg_sec)) : null; 

$sql_emp = "
SELECT k.id,k.nama,
  AVG(TIME_TO_SEC(a.jam_masuk)) AS avg_arrival_sec,
  SUM(a.status IN ('hadir','terlambat')) AS total_hadir,
  SUM(a.status = 'izin') AS total_izin,
  SUM(a.status = 'alpha') AS total_alpha,
  SUM( LEAST( GREATEST( TIME_TO_SEC(TIMEDIFF(a.jam_keluar,a.jam_masuk))/3600 - 8, 0), ?) ) AS total_overtime
FROM karyawan k
LEFT JOIN absensi a ON a.karyawan_id = k.id AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=?
WHERE k.role!='hrd'
GROUP BY k.id
";
$stmt2 = $conn->prepare($sql_emp);
$stmt2->bind_param('dii',$DAILY_OT_CAP,$month,$year);
$stmt2->execute();
$res2 = $stmt2->get_result();
$employees = [];
$jumlah_karyawan_lembur = 0;
while($r = $res2->fetch_assoc()){
  $r['avg_arrival'] = $r['avg_arrival_sec'] ? gmdate('H:i', round($r['avg_arrival_sec'])) : null;
  $r['total_overtime'] = round($r['total_overtime'],2);
  if ($r['total_overtime'] > 0) $jumlah_karyawan_lembur++;
  $employees[] = $r;
}

// konsistensi
$workdays = cal_days_in_month(CAL_GREGORIAN,$month,$year);
$min_days_for_consistency = ceil($workdays * $CONSISTENCY_PERCENT);

$sql_ce = "SELECT COUNT(DISTINCT karyawan_id) AS c FROM (SELECT karyawan_id, COUNT(*) AS cnt FROM absensi WHERE jam_masuk < ? AND MONTH(tanggal)=? AND YEAR(tanggal)=? GROUP BY karyawan_id) t WHERE t.cnt >= ?";
$stmt3 = $conn->prepare($sql_ce);
$stmt3->bind_param('siii',$WORK_START,$month,$year,$min_days_for_consistency);
$stmt3->execute(); $consistent_early = intval($stmt3->get_result()->fetch_assoc()['c']);

$sql_cl = "SELECT COUNT(DISTINCT karyawan_id) AS c FROM (SELECT karyawan_id, COUNT(*) AS cnt FROM absensi WHERE (jam_masuk >= ? OR status='terlambat') AND MONTH(tanggal)=? AND YEAR(tanggal)=? GROUP BY karyawan_id) t WHERE t.cnt >= ?";
$stmt4 = $conn->prepare($sql_cl);
$stmt4->bind_param('siii',$WORK_START,$month,$year,$min_days_for_consistency);
$stmt4->execute(); $consistent_late = intval($stmt4->get_result()->fetch_assoc()['c']);

// overtime under/over
$sql_each_ot = "SELECT karyawan_id, SUM( LEAST( GREATEST( TIME_TO_SEC(TIMEDIFF(jam_keluar,jam_masuk))/3600 - 8, 0), ?)) AS ot_sum FROM absensi WHERE MONTH(tanggal)=? AND YEAR(tanggal)=? GROUP BY karyawan_id";
$stmt6 = $conn->prepare($sql_each_ot);
$stmt6->bind_param('dii',$DAILY_OT_CAP,$month,$year);
$stmt6->execute(); $res6 = $stmt6->get_result();
$under=0;$over=0;
while($r=$res6->fetch_assoc()){
  if (floatval($r['ot_sum']) > $MONTHLY_LIMIT) $over++; else if (floatval($r['ot_sum']) > 0) $under++;
}

// jumlah karyawan yang sering absen (alpha >=3)
$sql_absen = "SELECT k.id,k.nama, SUM(a.status='alpha') AS alpha_count FROM karyawan k LEFT JOIN absensi a ON a.karyawan_id=k.id AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=? WHERE k.role!='hrd' GROUP BY k.id HAVING alpha_count >= 3";
$stmt7 = $conn->prepare($sql_absen);
$stmt7->bind_param('ii',$month,$year);
$stmt7->execute(); $res7 = $stmt7->get_result();
$frequent = [];
while($r=$res7->fetch_assoc()) $frequent[] = $r['nama']." ({$r['alpha_count']})";

// Top 5 karyawan yang hadir paling banyak 
$sql_top5 = "SELECT k.nama, COUNT(a.id) AS total_hadir FROM karyawan k LEFT JOIN absensi a ON a.karyawan_id=k.id AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=? WHERE k.role!='hrd' GROUP BY k.id ORDER BY total_hadir DESC LIMIT 5";
$stmt8 = $conn->prepare($sql_top5);
$stmt8->bind_param('ii',$month,$year);
$stmt8->execute(); $res8 = $stmt8->get_result();
$top5 = [];
while($r=$res8->fetch_assoc()) $top5[] = ['nama'=>$r['nama'],'total_hadir'=>intval($r['total_hadir'])];

// Mencari karyawan yang paling sering lembur (jam_keluar > '16:00:00')
$sql_most_ot = "
  SELECT k.nama, COUNT(*) AS total_hari
  FROM absensi a
  JOIN karyawan k ON k.id = a.karyawan_id
  WHERE a.jam_keluar IS NOT NULL AND TIME(a.jam_keluar) > '16:00:00'
    AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=?
    AND k.role!='hrd'
  GROUP BY k.id
  ORDER BY total_hari DESC
  LIMIT 1
";
$stmt10 = $conn->prepare($sql_most_ot);
$stmt10->bind_param('ii',$month,$year);
$stmt10->execute();
$res10 = $stmt10->get_result();
$most_overtime = $res10->fetch_assoc() ?: null;


echo json_encode([
  'success'=>true,
  'data'=>[
    'total_employees'=>$totalEmployees,
    'avg_arrival_time'=>$avg_time,
    'employees'=>$employees,
    'jumlah_karyawan_lembur'=>$jumlah_karyawan_lembur,
    'consistent_early'=>['count'=>$consistent_early,'min_days'=>$min_days_for_consistency],
    'consistent_late'=>['count'=>$consistent_late,'min_days'=>$min_days_for_consistency],
    'overtime'=>['under'=>$under,'over'=>$over,'monthly_limit'=>$MONTHLY_LIMIT],
    'frequent_absent'=>$frequent,
    'top5'=>$top5,
    'most_overtime'=>$most_overtime  
  ]
]);

