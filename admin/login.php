<?php
require_once ('../../config/config.php');
require_once ('../functions.php');
require ('../../vendor/autoload.php');
// ini_set('display_errors', true);

try {
  session_start();

  $err = [];

  if (!isset($_SESSION['USER']) && $_SESSION['USER']['auth_type'] == 1) {
    //ログイン済みの場合
    header('Location: /admin/user_lists.php');
    exit;
  }

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //入力値を取得
    $user_no = $_POST['user_no'];
    $password = $_POST['password'];

    //バリデーション
    if (!$user_no) {
      $err['$user_no'] = '社員番号を入力してください';
    } elseif(!preg_match('/^[0-9]+$/', $user_no)) {
      $err['$user_no'] = '社員番号を正しく入力してください';
    } elseif(mb_strlen($user_no,'utf8') > 20) {
      $err['$user_no'] = '20文字以内にしてください';
    } 
    if (!$password) {
      $err['$password'] = 'パスワードを入力してください';
    }

    //データベースに保存
    if (empty($err)) {
      //データベース照会
      $sql = "SELECT * FROM users WHERE user_no = :user_no AND auth_type = 1 LIMIT 1";

      $stmt = dbc()->prepare($sql);
      $stmt->bindValue(':user_no',$user_no,PDO::PARAM_STR);
      $stmt->execute();
      $res = $stmt->fetch();

      // var_dump($res);
      // exit;

      //ログイン処理
      if ($res && password_verify($password, $res['password'])) {
        //ログイン処理
        $_SESSION['USER'] = $res;

        //ホーム画面に遷移
        header ('Location: /admin/user_lists.php');
        exit;
      } else {
        $err['$password'] = '認証に失敗しました。';
      }
    }
  } else {
    //初回ログイン時
    $user_no = '';
    $password = '';
  }
} catch(Exception $e) {
  header('Location: ../error.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="text-center bg-success">
<form class="border rounded bg-white form-login" method="post">
    <h1 class="h3 my-3">Login</h1>
  <div class="mb-3 pt-3">
    <input type="text" class="form-control rounded-pill <?php if (isset($err['$user_no'])) echo 'is-invalid'; ?>" name="user_no" value="<?= $user_no ?>" placeholder = "社員番号">
    <div class="invalid-feedback"><?= $err['$user_no']; ?></div>
  </div>
  <div class="mb-3">
    <input type="password" class="form-control rounded-pill <?php if (isset($err['$password'])) echo 'is-invalid'; ?>" name="password" placeholder = "パスワード">
    <div class="invalid-feedback"><?= $err['$password']; ?></div>
  </div>
  <button type="submit" class="btn btn-primary rounded-pill px-5 my-4">ログイン</button>
  <a href="/login.php"><button type="button" class="btn btn-secondary rounded-pill px-3">社員側</button></a>
</form>
</body>
</html>