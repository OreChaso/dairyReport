<?php
require_once ('./functions.php');
require_once ('../config/config.php');
ini_set('display_errors', true);

try {

session_start();

//ログイン状態をチェック、ユーザー情報をセッションから取得
if (!isset($_SESSION['USER'])) {
  //ログイン済みの場合
  header('Location: ./login.php');
  exit;
}
//ユーザーの情報を取得
$session_user = $_SESSION['USER'];

$err = [];

$pdo = dbc();

$target_date = date('Y-m-d');

//モーダルの自動表示判定
$modal_view_flg = true;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  //日報登録処理

  //入力値をpostから取得
  $target_date = $_POST['target_date'];
  $modal_start_time = $_POST['modal_start_time'];
  $modal_end_time = $_POST['modal_end_time'];
  $modal_break_time = $_POST['modal_break_time'];
  $modal_comment = $_POST['modal_comment'];

  //出勤時間の入力チェック
  if(!$modal_start_time) {
    $err['modal_start_time'] = '時間を入力してください';
  } elseif(!check_time_format($modal_start_time)) {
    $modal_start_time = '';
    $err['modal_start_time'] = '時間を正しく入力してください';
  }

  //退勤時間の入力チェック
  if(!check_time_format($modal_end_time)) {
    $modal_end_time = '';
    $err['modal_end_time'] = '時間を正しく入力してください';
  }

  //休憩時間の入力チェック
  if(!check_time_format($modal_end_time)) {
    $modal_break_time = '';
    $err['modal_break_time'] = '時間を正しく入力してください';
  }

  //業務内容2000文字
  if(mb_strlen($modal_comment,'utf8') > 1000) {
    $err['modal_comment'] = '1000文字以内に収めてください';
  }

  //バリデーションチェック
  if(empty($err)) {

    //データベースに対処日のデータがあるかチェック
    $sql = "SELECT id FROM works WHERE users_id = :users_id AND date = :date LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':users_id',(int)$session_user['id'],PDO::PARAM_INT);
    $stmt->bindValue(':date',$target_date,PDO::PARAM_STR);
    $stmt->execute();
    $res_work = $stmt->fetch();

    if($res_work) {
      //対象日のデータがあればupdate
      $sql = "UPDATE works SET start_time = :start_time, end_time = :end_time, break_time = :break_time, comment = :comment WHERE id = :id";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':id',(int)$res_work['id'],PDO::PARAM_INT);
      $stmt->bindValue(':start_time',$modal_start_time,PDO::PARAM_STR);
      $stmt->bindValue(':end_time',$modal_end_time,PDO::PARAM_STR);
      $stmt->bindValue(':break_time',$modal_break_time,PDO::PARAM_STR);
      $stmt->bindValue(':comment',$modal_comment,PDO::PARAM_STR);
      $stmt->execute();
    } else {
      //データがなければinsert
      $sql = "INSERT INTO works (users_id, date, start_time, end_time, break_time, comment) VALUES(:users_id, :date, :start_time, :end_time, :break_time, :comment)";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':users_id',(int)$session_user['id'],PDO::PARAM_INT);
      $stmt->bindValue(':date',$target_date,PDO::PARAM_STR);
      $stmt->bindValue(':start_time',$modal_start_time,PDO::PARAM_STR);
      $stmt->bindValue(':end_time',$modal_end_time,PDO::PARAM_STR);
      $stmt->bindValue(':break_time',$modal_break_time,PDO::PARAM_STR);
      $stmt->bindValue(':comment',$modal_comment,PDO::PARAM_STR);
      $stmt->execute();
    }

    $modal_view_flg = false;
  }
} else {
  //当日のデータがあるかチェック
  $sql = "SELECT id, start_time, end_time, break_time, comment FROM works WHERE users_id = :users_id AND date = :date LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':users_id',(int)$session_user['id'],PDO::PARAM_INT);
  $stmt->bindValue(':date',date('Y-m-d'),PDO::PARAM_STR);
  $stmt->execute();
  $res_today_work = $stmt->fetch();

  if($res_today_work) {
    $modal_start_time = $res_today_work['start_time'];
    $modal_end_time = $res_today_work['end_time'];
    $modal_break_time = $res_today_work['break_time'];
    $modal_comment = $res_today_work['comment'];

    if($modal_start_time && $modal_end_time) {
      $modal_view_flg = false;
    }
  }else  {
    //業務日報データをリスト表示
    $modal_start_time = '';
    $modal_end_time = '';
    $modal_break_time = '01:00';
    $modal_comment = '';
  }
}

//ユーザーの業務日報データを取得
if(isset($_GET['m'])) {
  $yyyymm = $_GET['m'];
  $day_count = date('t', strtotime($yyyymm));

  if(count(explode('-', $yyyymm)) != 2) {
    throw new Exception('日付の指定が不正', 500);
  }

  //過去12ヶ月の範囲内かどうか
  $check_date = new Datetime($yyyymm.'-01');
  $start_date = new Datetime('first day of -11 month 00:00');
  $end_date = new Datetime('first day of this month 00:00');

  if($check_date < $start_date || $end_date < $check_date) {
    throw new Exception('日付の範囲が不正', 500);
  }

  if($check_date != $end_date) {
    //チェックしているデータが当月でないときモーダル自動表示しない
    $modal_view_flg = false;
  }
} else {
  $yyyymm = date('Y-m');
  $day_count = date('t');
}

$sql = "SELECT date, id, start_time, end_time, break_time, comment FROM works WHERE users_id = :users_id AND DATE_FORMAT(date,'%Y-%m') = :date";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':users_id', (int)$session_user['id'], PDO::PARAM_STR);
$stmt->bindValue(':date', $yyyymm, PDO::PARAM_STR);
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_UNIQUE);

} catch(Exception $e) {
  header('Location: /error.php');
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
  <title>日報登録</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
  <link rel="stylesheet" href="./css/style.css">
</head>
<body class="text-center bg-light">
<form class="border rounded bg-white time-table" action="./index.php">
  <h1 class="h3 my-3">月別リスト</h1>
  
  <select class="form-control rounded-pill mb-3" name="m" onchange="submit(this.form);">
    <option value="<?= date('Y-m') ?>"><?= date('Y/m') ?></option>
    <?php for($i = 1; $i < 12; $i++): ?>
      <?php $target_yyyymm = strtotime("-{$i}months"); ?>
    <option value="<?= date('Y-m', $target_yyyymm) ?>"<?php if($yyyymm == date('Y-m', $target_yyyymm)) echo 'selected'?>><?= date('Y/m', $target_yyyymm) ?></option>
    <?php endfor; ?>
  </select>
  <a href="/logout.php"><button type="button" class="btn btn-secondary rounded-pill px-3">ログアウト</button></a>

  <table class="table table-bordered">
    <thead>
      <tr class="bg-light">
        <th class="fix-col">日</th>
        <th class="fix-col">出勤</th>
        <th class="fix-col">退勤</th>
        <th class="fix-col">休憩</th>
        <th>業務内容</th>
        <th class="fix-col"></th>
      </tr>
    </thead>
    <tbody>
      <?php for($i = 1; $i <= $day_count; $i++): ?>
        <?php
          $start_time = '';
          $end_time = '';
          $break_time = '';
          $comment = '';

          if (isset($res[date('Y-m-d', strtotime($yyyymm . '-' . $i))])) {

            $work = $res[date('Y-m-d', strtotime($yyyymm . '-' . $i))];

            if($work['start_time']) {
              $start_time = date('H:i', strtotime($work['start_time']));              
            }
            if($work['end_time']) {
              $end_time = date('H:i', strtotime($work['end_time']));              
            }
            if($work['break_time']) {
              $break_time = date('H:i', strtotime($work['break_time']));             
            }
            if($work['comment']) {
              $comment = mb_strimwidth($work['comment'], 0, 40, '...');             
            }
          }
        ?>
      <tr>
        <th scope="row"><?= timeFormat($yyyymm . '-' . $i); ?></th>
        <td><?=$start_time;?></td>
        <td><?=$end_time;?></td>
        <td><?=$break_time;?></td>
        <td><?=h($comment);?></td>
        <td><button type="button" class="btn btn_default h-auto py-0" data-toggle="modal" data-target="#inputModal" data-day="<?= $yyyymm . '-' . sprintf('%02d', $i); ?>"><i class="fas fa-pencil-alt"></i></button></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>
</form>



<!-- Modal -->
<form method="post">
  <div class="modal fade" id="inputModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <p></p>
          <h5 class="modal-title" id="exampleModalLabel">日報登録</h5>
          <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        <div class="alert alert-primary" role="alert">
          <?= date('n', strtotime($target_date))?>/<span id="modal_day"><?= timeFormat($target_date); ?></span>
        </div>
        <div class="container">
          <div class="row">
            <div class="col-sm">
              <div class="input-group">
                <input type="text" class="form-control<?php if (isset($err['modal_start_time'])) echo 'is-invalid'; ?>" placeholder="出勤" id="modal_start_time" name="modal_start_time" value="<?= $modal_start_time ?>" required>
                <div class="input-group-prepend">
                <button type="button" class="input-group-text" id="start_btn">打刻</button>
                </div>
                <div class="invalid-feedback"><?= $err['$modal_start_time']; ?></div>
              </div>
            </div>
            <div class="col-sm">
            　<div class="input-group">
                <input type="text" class="form-control<?php if (isset($err['modal_end_time'])) echo 'is-invalid'; ?>" placeholder="退勤" id="modal_end_time" name="modal_end_time" value="<?= $modal_end_time ?>">
                <div class="input-group-prepend">
                <button type="button" class="input-group-text" id="end_btn">打刻</button>
                </div>
                <div class="invalid-feedback"><?= $err['$modal_end_time']; ?></div>
              </div>
            </div>
            <div class="col-sm">
            <input type="text" class="form-control<?php if (isset($err['modal_break_time'])) echo 'is-invalid'; ?>" placeholder="休憩" id="modal_break_time" name="modal_break_time" value="<?= $modal_break_time ?>">
            <div class="invalid-feedback"><?= $err['$modal_break_time']; ?></div>
            </div>
          </div>
        </div>
        <div class="form-group pt-3">
          <textarea class="form-control<?php if (isset($err['modal_comment'])) echo 'is-invalid'; ?>" id="modal_comment" name="modal_comment" rows="5" placeholder="業務内容"><?= $modal_comment ?></textarea>
          <div class="invalid-feedback"><?= $err['$modal_comment']; ?></div>
        </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary text-white rounded-pill px-5">保存</button>
        </div>
      </div>
    </div>
  </div>
  <input type="hidden" id="target_date" name="target_date">
</form>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script>
  <?php if($modal_view_flg): ?>
  var inputModal = new bootstrap.Modal(document.getElementById('inputModal'));
  inputModal.toggle();
  <?php endif; ?>

  $('#start_btn').click(function() {
    const now = new Date();
    const hour = now.getHours().toString().padStart(2, '0');
    const minute = now.getMinutes().toString().padStart(2, '0');
    $('#modal_start_time').val(hour+':'+minute);
  })

  $('#end_btn').click(function() {
    const now = new Date();
    const hour = now.getHours().toString().padStart(2, '0');
    const minute = now.getMinutes().toString().padStart(2, '0');
    $('#modal_end_time').val(hour+':'+minute);
  })

  $('#inputModal').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var target_day = button.data('day');

    //押された編集ボタンの対象日データの取得
    var day = button.closest('tr').children('th')[0].innerText
    var start_time = button.closest('tr').children('td')[0].innerText
    var end_time = button.closest('tr').children('td')[1].innerText
    var break_time = button.closest('tr').children('td')[2].innerText
    var comment = button.closest('tr').children('td')[3].innerText

    //取得したデータを表示
    $('#modal_day').text(day)
    $('#modal_start_time').val(start_time)
    $('#modal_end_time').val(end_time)
    $('#modal_break_time').val(break_time)
    $('#modal_comment').val(comment)
    $('#target_date').val(target_day)

    //エラー表示をクリア
    $('#modal_start_time').removeClass('is-invalid')
    $('#modal_end_time').removeClass('is-invalid')
    $('#modal_break_time').removeClass('is-invalid')
    $('#modal_comment').removeClass('is-invalid')
  })
</script>
</body>
</html>