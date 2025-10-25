<?php
include 'config.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? 0;
$alasan = $_POST['alasan'] ?? ''; 
$tanggal = $_POST['tanggal'] ?? ''; 

if (!$id || !$alasan || !$tanggal) {
    echo json_encode([
        "success" => false,
        "message" => "Data izin tidak lengkap."
    ]);
    exit;
}

$allowed_reasons = ['Sakit', 'Izin Pribadi', 'Cuti', 'Dinas'];
if (!in_array($alasan, $allowed_reasons)) {
    echo json_encode([
        "success" => false,
        "message" => "Alasan izin tidak valid."
    ]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO izin (karyawan_id, tanggal, alasan, status) VALUES (?, ?, ?, 'pending')");
$stmt->bind_param("iss", $id, $tanggal, $alasan);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Pengajuan izin berhasil dikirim dan menunggu persetujuan HRD."
    ]);
} else {
    if ($conn->errno == 1062) {
        echo json_encode([
            "success" => false,
            "message" => "Anda sudah mengajukan izin untuk tanggal ini."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Gagal menyimpan izin: " . $conn->error
        ]);
    }
}
?>
