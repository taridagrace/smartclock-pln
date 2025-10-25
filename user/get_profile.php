<?php
header('Content-Type: application/json');
require 'config.php'; 

if (!isset($_GET['id'])) {
  echo json_encode(['success' => false, 'message' => 'ID karyawan tidak diberikan']);
  exit;
}

$id = intval($_GET['id']);

$sql = "SELECT id, nama, email, jabatan FROM karyawan WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan']);
  exit;
}

$karyawan = $result->fetch_assoc();

// --- Hitung total kehadiran (hadir + terlambat) ---
$sql_hadir = "
  SELECT COUNT(*) AS total_hadir
  FROM absensi
  WHERE karyawan_id = ?
    AND status IN ('hadir', 'terlambat')
    AND MONTH(tanggal) = MONTH(CURDATE())
    AND YEAR(tanggal) = YEAR(CURDATE())
";
$stmt_hadir = $conn->prepare($sql_hadir);
$stmt_hadir->bind_param('i', $id);
$stmt_hadir->execute();
$total_hadir = $stmt_hadir->get_result()->fetch_assoc()['total_hadir'] ?? 0;

// --- Hitung total izin ---
$sql_izin = "
  SELECT COUNT(*) AS total_izin
  FROM absensi
  WHERE karyawan_id = ?
    AND status = 'izin'
    AND MONTH(tanggal) = MONTH(CURDATE())
    AND YEAR(tanggal) = YEAR(CURDATE())
";
$stmt_izin = $conn->prepare($sql_izin);
$stmt_izin->bind_param('i', $id);
$stmt_izin->execute();
$total_izin = $stmt_izin->get_result()->fetch_assoc()['total_izin'] ?? 0;

// --- Hitung total alfa ---
$sql_alpha = "
  SELECT COUNT(*) AS total_alpha
  FROM absensi
  WHERE karyawan_id = ?
    AND status = 'alpha'
    AND MONTH(tanggal) = MONTH(CURDATE())
    AND YEAR(tanggal) = YEAR(CURDATE())
";
$stmt_alpha = $conn->prepare($sql_alpha);
$stmt_alpha->bind_param('i', $id);
$stmt_alpha->execute();
$total_alpha = $stmt_alpha->get_result()->fetch_assoc()['total_alpha'] ?? 0;

$karyawan['total_kehadiran'] = $total_hadir;
$karyawan['total_izin'] = $total_izin;
$karyawan['total_alpha'] = $total_alpha;

echo json_encode([
  'success' => true,
  'data' => $karyawan
]);
?>
