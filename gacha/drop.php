<?php
/**
 * ガチャAPI
 *
 */

// 以下のコメントを外すと実行時エラーが発生した際にエラー内容が表示される
// ini_set('display_errors', 'On');
// ini_set('error_reporting', E_ALL);

//-------------------------------------------------
// 定数
//-------------------------------------------------
// キャラクター数
define('MAX_CHARA', 10);

// ガチゃ1回の価格
define('GACHA_PRICE', 300);


//-------------------------------------------------
// 引数を受け取る
//-------------------------------------------------
// ユーザーIDを受け取る
$uid = isset($_GET['uid'])?  $_GET['uid']:null;

// Validation
if( ($uid === null) || (!is_numeric($uid)) ){
  sendResponse(false, 'Invalid uid');
  exit(1);
}

//-------------------------------------------------
// 準備
//-------------------------------------------------
$dsn  = 'mysql:dbname=sgrpg;host=127.0.0.1';  // 接続先を定義
$user = 'senpai';      // MySQLのユーザーID
$pw   = 'indocurry';   // MySQLのパスワード

//---------------------------
// 実行したいSQL
//---------------------------
// Userテーブルから所持金を取得
$sql1 = 'SELECT money FROM users WHERE id=:userid';

// Userテーブルの所持金を減産
$sql2 = 'UPDATE users SET money=money-:price';

// UserCharaテーブルにキャラクターを追加
$sql3 = 'INSERT INTO user_characters(user_id, chara_id) VALUES(:userid,:charaid)';

// Charaテーブルから1レコード取得
$sql4 = 'SELECT * FROM characters WHERE id=:charaid';


//-------------------------------------------------
// SQLを実行
//-------------------------------------------------
try{
  $dbh = new PDO($dsn, $user, $pw);   // 接続
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // エラーモード

  // トランザクション開始
  $dbh->beginTransaction();

  //---------------------------
  // 所持金の残高を取得
  //---------------------------
  $sth = $dbh->prepare($sql1);
  $sth->bindValue(':userid', $uid, PDO::PARAM_INT);
  $sth->execute();
  $buff = $sth->fetch(PDO::FETCH_ASSOC);

  // ユーザーが存在しているかチェック
  if( $buff === false ){
    sendResponse(false, 'Not Found User');
    exit(1);
  }

  // 残高が足りているかチェック
  if( $buff['money'] < GACHA_PRICE ){
    sendResponse(false, 'The balance is not enough');
    exit(1);
  }

  //---------------------------
  // 残高を減らす
  //---------------------------
  $sth = $dbh->prepare($sql2);
  $sth->bindValue(':price', GACHA_PRICE, PDO::PARAM_INT);
  $sth->execute();

  //---------------------------
  // キャラクターを抽選
  //---------------------------
  $charaid = random_int(1, MAX_CHARA);

  //---------------------------
  // キャラクターを所有
  //---------------------------
  $sth = $dbh->prepare($sql3);
  $sth->bindValue(':userid',  $uid,     PDO::PARAM_INT);
  $sth->bindValue(':charaid', $charaid, PDO::PARAM_INT);
  $sth->execute();

  //---------------------------
  // キャラクター情報を取得
  //---------------------------
  $sth = $dbh->prepare($sql4);
  $sth->bindValue(':charaid', $charaid, PDO::PARAM_INT);
  $sth->execute();
  $chara = $sth->fetch(PDO::FETCH_ASSOC);

  //---------------------------
  // トランザクション確定
  //---------------------------
  $dbh->commit();
}
catch( PDOException $e ) {
  // ロールバック
  $dbh->rollBack();

  sendResponse(false, 'Database error: '.$e->getMessage());  // 本来エラーメッセージはサーバ内のログへ保存する(悪意のある人間にヒントを与えない)
  exit(1);
}

//-------------------------------------------------
// 実行結果を返却
//-------------------------------------------------
// データが0件
if( $buff === false ){
  sendResponse(false, 'System Error');
}
// データを正常に取得
else{
  sendResponse(true, $chara);
}


/**
 * 実行結果をJSON形式で返却する
 *
 * @param boolean $status
 * @param array   $value
 * @return void
 */
function sendResponse($status, $value=[]){
  header('Content-type: application/json');
  echo json_encode([
    'status' => $status,
    'result' => $value
  ]);
}

