<?php

require_once 'config.php';
$state = base_convert(mt_rand(pow(36, 8 - 1), pow(36, 8) - 1), 10, 36);

$url = "https://map.yahooapis.jp/weather/V1/place?coordinates=139.732293,35.663613&appid=" . YAHOO_APPID . "&output=json";

//$xml = file_get_contents($url);
//$obj = simplexml_load_string($xml);
//$json = json_encode($obj);
//$array = json_decode($json, true);
//  cURLセッションを初期化
$ch = curl_init();
// 転送時のオプションを配列で設定
$options = array(
    // 取得するURLを指定
    CURLOPT_URL => $url,
    // trueの場合、curl_exec() の戻り値が文字列
    CURLOPT_RETURNTRANSFER => true,
    // 接続の試行を待ち続ける秒数を指定
    CURLOPT_CONNECTTIMEOUT => 5,
    // falseの場合、サーバー証明書の検証をしない。HTTPS接続がエラーの際に設定
    CURLOPT_SSL_VERIFYPEER => false
);
curl_setopt_array($ch, $options);

// 転送を実行
$results = curl_exec($ch);

// cURLセッションを終了
curl_close($ch);

//$obj = simplexml_load_string($results);
//$json = json_encode($results);
$array = json_decode($results, true);

$format = "YmdHi";
$date = Datetime::createFromFormat($format, $array["Feature"]["0"]["Property"]["WeatherList"]["Weather"]["0"]["Date"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loginId = $_POST['fp_email'];

    $url = parse_url(getenv("DATABASE_URL"));
    //heroku
//    $conInfo = "host=" . $url['host'] . " port=" . $url['port'] . " dbname=" . substr($url['path'], 1) . " user=" . $url['user'] . " password=" . $url['pass'];
    //localhost
    $conInfo = "host=ec2-75-101-131-79.compute-1.amazonaws.com" . " port=5432" . " dbname=d2sju3heccusbp" . " user=zliasmbvrlvsqa" . " password=7c98e160ad33185fc374869b3d474d74a89cc8c0c7d861acca70d4ffbf01262b";
    //データがあるかチェック
    try {
        $con = pg_connect($conInfo);
        $sql = "select password from users where email = $1";
        $res = pg_query_params($con, $sql, array($loginId,));
    } catch (Exception $exc) {
        echo $exc->getTraceAsString();
        exit;
    } finally {
        pg_close($con);
    }

    $password = "";
    while ($row = pg_fetch_row($res)) {
        $password = $row[0];
    }

    if ($password !== "") { //データあり
//        mb_language("Japanese");
//        mb_internal_encoding("UTF-8");
        $subject = "パスワードのお知らせ";     // 題名
        $body = $password; // 本文
        $to = 'kaori.matsushita@nxtg.co.jp';          // 送信先
        $headers = 'From: kaori.matsushita@nxtg.co.jp' . "\r\n";
//        $result = mb_send_mail($to, $subject, $body, $headers);
//        if ($result) {
//            echo 'パスワードを送信しました。';
//        } else {
//            echo '送信に失敗しました。';
//        }
//        echo "あなたのパスワードは{$password}です。";
        $data["message"] = "あなたのパスワードは{$password}です。";
    } else {
//        echo "メールアドレスが存在しません。";
        $data["message"] = "メールアドレスが存在しません。";
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
