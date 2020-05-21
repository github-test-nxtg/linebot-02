<?php
session_start();
require_once 'config.php';
$state = base_convert(mt_rand(pow(36, 8 - 1), pow(36, 8) - 1), 10, 36);

$url = "https://map.yahooapis.jp/weather/V1/place?coordinates=139.732293,35.663613&appid=" . YAHOO_APPID . "&output=json";

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
$array = json_decode($results, true);
$format = "YmdHi";
$date = Datetime::createFromFormat($format, $array["Feature"]["0"]["Property"]["WeatherList"]["Weather"]["0"]["Date"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$loginId = $_POST['lg_username'];
	$password = $_POST['lg_password'];
//		$loginId = filter_input(INPUT_POST,"lg_password");
//		$password = filter_input(INPUT_POST,"lg_password");

	$url = parse_url(getenv("DATABASE_URL"));
	//heroku
//    $conInfo = "host=" . $url['host'] . " port=" . $url['port'] . " dbname=" . substr($url['path'], 1) . " user=" . $url['user'] . " password=" . $url['pass'];
	//localhost
	$conInfo = "host=ec2-75-101-131-79.compute-1.amazonaws.com" . " port=5432" . " dbname=d2sju3heccusbp" . " user=zliasmbvrlvsqa" . " password=7c98e160ad33185fc374869b3d474d74a89cc8c0c7d861acca70d4ffbf01262b";
	//ログインチェック
	try {
		$con = pg_connect($conInfo);
		$sql = "select count(*) from users where email = $1 and password = $2";
		$res = pg_query_params($con, $sql, array($loginId, $password));
	} catch (Exception $exc) {
		echo $exc->getTraceAsString();
		exit;
	} finally {
		pg_close($con);
	}

	$count = '0';
	while ($row = pg_fetch_row($res)) {
		$count = $row[0];
	}

	if ($count !== '0') {
		try {
			$con = pg_connect($conInfo);
			$sql = "select * from users where email = $1 and password = $2";
			$res = pg_query_params($con, $sql, array($loginId, $password));

			while ($row = pg_fetch_row($res)) {
				$displayName = $row[7];
			}
		} catch (Exception $exc) {
			echo $exc->getTraceAsString();
			exit;
		} finally {
			pg_close($con);
		}
		$_SESSION['username'] = $displayName;
		$result["transitionFlg"] = 1;
		header("Content-type: application/json; charset=UTF-8");
		$json = json_encode($result);
		echo $json;
//        header("Location: https://nxtg-linebot.herokuapp.com/afterLogin.php");
//        header("Location: ". (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . "/LINEBot/afterLoginNotLine.php");
	} else {
//        echo <<< EOM
//        <script type="text/javascript">$("#output").addClass("alert alert-danger animated fadeInUp").html("Could not login");</script>
//EOM;
		header("Content-type: application/json; charset=UTF-8");
		$result["message"] = 'ログイン情報が間違っています。';
		$result["transitionFlg"] = 0;
		$json = json_encode($result);
		echo $json;
//        echo <<< EOM
//        <script type="text/javascript"$('.login-form-main-message').html("tes");</script>
//EOM;
	}
}

exit;
$url = "http://weather.livedoor.com/forecast/webservice/json/v1?city=250010";
$json = file_get_contents($url);
//                            $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
$array = json_decode($json, true);
//                            $weatherForecast = $array["pref"]["area"]["info"]["weather"];
//$weatherForecast = $array["pref"]["@attributes"]["id"] . $array["pref"]["area"][0]["@attributes"]["id"] . $array["pref"]["area"][0]["info"][0]["@attributes"]["date"];

?>
<html>

	<head>

	</head>
	<body>
		<?php echo (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"]; ?>
		<?php echo (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]; ?>
	</body>
	
</html>