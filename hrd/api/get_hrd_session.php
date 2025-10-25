<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hrd') {
  echo json_encode(['success'=>false]);
  exit;
}
echo json_encode(['success'=>true,'id'=>$_SESSION['user']['id'],'nama'=>$_SESSION['user']['nama']]);
?>
