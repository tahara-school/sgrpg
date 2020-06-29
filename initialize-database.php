<?php
function initializeDatabase()
{
    $dbh = new PDO(Define::$dsn, Define::$user, Define::$pw);   // 接続
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // エラーモード
    return $dbh;
}
