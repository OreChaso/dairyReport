<?php
require_once ('../../config/config.php');
require_once ('../functions.php');
ini_set('display_errors', true);

try{
    
  session_start();

  if (!isset($_SESSION['USER']) || $_SESSION['USER']['auth_type'] != 1) {
    //ログインされてない場合ログイン画面へ
    header('Location: /admin/login.php');
    exit;
  }

  $pdo = dbc();

  $sql = "SELECT * FROM users";
  $stmt = $pdo->query($sql);
  $user_list = $stmt->fetchAll();
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
  <script src="https://kit.fontawesome.com/fe2ae2a4f6.js" crossorigin="anonymous"></script>
  <title>社員一覧</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="text-center bg-success">
<form class="border rounded bg-white form-user-list" action="./index.php">
  <h1 class="h3 my-3">月別リスト</h1>
  
  <table class="table table-bordered">
    <thead>
      <tr class="bg-green ">
        <th scope="col">社員番号</th>
        <th scope="col">社員名</th>
        <th scope="col">管理者</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($user_list as $user): ?>
      <tr>
        <td scope="row"><?= $user['user_no'] ?></td>
        <td><a href="./user_result.php?id=<?= $user['id'] ?>"><?= $user['name'] ?></a></td>
        <td scope="row"><?php if($user['auth_type'] == 1) echo '管理者' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <a href="/admin/login.php"><button type="button" class="btn btn-secondary rounded-pill px-5">ログイン画面に戻る</button></a>
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>
</body>
</html>