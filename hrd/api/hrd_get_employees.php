<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hrd') {
  http_response_code(403); echo json_encode(['success'=>false]); exit;
}
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

$res = $conn->query("SELECT id,nama,email,jabatan FROM karyawan WHERE role!='hrd' ORDER BY nama ASC");
$data = [];
while($r=$res->fetch_assoc()) $data[]=$r;
echo json_encode(['success'=>true,'data'=>$data]);
 