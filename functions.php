<?php 

//データベース接続
function dbc() {
  $host = DB_HOST;
  $db   = DB_NAME;
  $user = DB_USER;
  $pass = DB_PASS;

  $dsn = "mysql:host=$host;dbname=$db;charset=utf8";

  try {
    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
  } catch(Execption $e) {
    echo '接続に失敗しました' . $e->getMessage();
    exit();
  }
  return $pdo;
}

//日付を日本語に変換
function timeFormat($date) {
  $format_date = null;
  $week = ['日', '月', '火', '水', '木', '金', '土'];

  if($date) {
    $format_date = date('j('.$week[date('w', strtotime($date))].')', strtotime($date));
  }

  return $format_date;
}

//xss対策
function h($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'utf-8');
}

//csrf対策
function setToken() {
  $csrf_token = sha1(uniqid(mt_rand(), true));
  $_SESSION['csrf_token'] = $csrf_token;

}

//トークンをチェックする処理
function check_token() {
  if(empty($_SESSION['csrf_token']) || ($_SESSION['csrf_token'] != $_POST['csrf_token'])) {
    unset($pdo);
    header ('Location: /error.php');
    exit;
  }
}

//時間形式チェック
function check_time_format($time) {
  if(preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/', $time)) {
    return true;
  } else {
    return false;
  }
}