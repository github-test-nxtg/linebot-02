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
    $loginId = $_POST['reg_username'];
    $password = $_POST['reg_password'];
    $gender = $_POST['reg_gender'];
    $fullName = $_POST['reg_fullname'];

    var_dump($_POST);
    
    $url = parse_url(getenv("DATABASE_URL"));
    //heroku
//    $conInfo = "host=" . $url['host'] . " port=" . $url['port'] . " dbname=" . substr($url['path'], 1) . " user=" . $url['user'] . " password=" . $url['pass'];
    //localhost
    $conInfo = "host=ec2-75-101-131-79.compute-1.amazonaws.com" . " port=5432" . " dbname=d2sju3heccusbp" . " user=zliasmbvrlvsqa" . " password=7c98e160ad33185fc374869b3d474d74a89cc8c0c7d861acca70d4ffbf01262b";
    //ログインチェック
    try {
        $con = pg_connect($conInfo);
        $sql = "select count(*) from users where email = $1";
        $res = pg_query_params($con, $sql, array($loginId, ));
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

    if ($count !== '0') { //データあり
        echo "そのアドレスは既に登録されています。";
//        try {
//            // LINEのデータを登録する
//            $con = pg_connect($conInfo);
//            $sql = "UPDATE users SET uuid = $1, nonce = $2 WHERE email = $3";
//            $res = pg_query_params($con, $sql, array($uuid, $nonce, $loginId,));
//        } catch (Exception $exc) {
//            echo $exc->getTraceAsString();
//            exit;
//        } finally {
//            pg_close($con);
//        }
//        header("Location: https://nxtg-linebot.herokuapp.com/afterLogin.php");
//        exit;
//        
    } else { //登録
//        echo <<< EOM
//        <script type="text/javascript">$("#output").addClass("alert alert-danger animated fadeInUp").html("Could not login");</script>
//EOM;
        try {
            // ログインデータを登録する
            $con = pg_connect($conInfo);
            $sql = "INSERT INTO users (loginid,uuid,nonce,email,password,gender,fullname) VALUES($1 , '', '', $1, $2,$3,$4)";
            $res = pg_query_params($con, $sql, array($loginId, $password,$gender,$fullName));
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            exit;
        } finally {
            pg_close($con);
        }
echo "登録しました。";
//        header("Location: https://nxtg-linebot.herokuapp.com/index.php");
//        exit;
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"content="width=320,height=480,initial-scale=1.0,minimum-scale=1.0,maximum-scale=2.0,user-scalable=yes" />

        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
        <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
        <!------ Include the above in your HEAD tag ---------->

        <!-- All the files that are required -->
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <link href='http://fonts.googleapis.com/css?family=Varela+Round' rel='stylesheet' type='text/css'>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.13.1/jquery.validate.min.js"></script>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

        <link href="css/mycss.css" rel="stylesheet" type="text/css"/>
        <title>ログイン画面</title>


    </head>
    <body>

        <!-- Where all the magic happens -->
        <!-- REGISTRATION FORM -->
        <div class="text-center" style="padding:50px 0">
            <div class="logo">register</div>
            <!--Main Form--> 
            <div class="login-form-1">
                <form id="register-form" class="text-left" method="POST">
                    <div class="login-form-main-message"></div>
                    <div class="main-login-form">
                        <div class="login-group">
                            <div class="form-group">
                                <label for="reg_username" class="sr-only">Email address</label>
                                <input type="text" class="form-control" id="reg_username" name="reg_username" placeholder="username">
                            </div>
                            <div class="form-group">
                                <label for="reg_password" class="sr-only">Password</label>
                                <input type="password" class="form-control" id="reg_password" name="reg_password" placeholder="password">
                            </div>
                            <div class="form-group">
                                <label for="reg_password_confirm" class="sr-only">Password Confirm</label>
                                <input type="password" class="form-control" id="reg_password_confirm" name="reg_password_confirm" placeholder="confirm password">
                            </div>

<!--                            <div class="form-group">
                                <label for="reg_email" class="sr-only">Email</label>
                                <input type="text" class="form-control" id="reg_email" name="reg_email" placeholder="email">
                            </div>-->
                            <div class="form-group">
                                <label for="reg_fullname" class="sr-only">Full Name</label>
                                <input type="text" class="form-control" id="reg_fullname" name="reg_fullname" placeholder="full name">
                            </div>

                            <div class="form-group login-group-checkbox">
                                <input type="radio" class="" name="reg_gender" id="male" placeholder="username" value="male">
                                <label for="male">male</label>

                                <input type="radio" class="" name="reg_gender" id="female" placeholder="username" value="female">
                                <label for="female">female</label>
                            </div>

                            <div class="form-group login-group-checkbox">
                                <input type="checkbox" class="" id="reg_agree" name="reg_agree">
                                <label for="reg_agree">i agree with <a href="#">terms</a></label>
                            </div>
                        </div>
                        <button type="submit" class="login-button"><i class="fa fa-chevron-right"></i></button>
                    </div>
                    <div class="etc-login-form">
                        <p>already have an account? <a href="index.php">login here</a></p>
                    </div>
                </form>
            </div>
            <!--end:Main Form--> 
        </div>

        <!--        <div>
                    <img width="400" height="300" src="https://map.yahooapis.jp/course/V1/routeMap?appid=<?= YAHOO_APPID ?>&route=34.985849,135.7587667,34.9881938,135.7597494|color:0000ffff&width=400&height=300">   
                </div>
                <div>
                    <a href="https://map.yahooapis.jp/search/local/V1/localSearch?appid=<?= YAHOO_APPID ?>&query=%E3%82%AB%E3%83%95%E3%82%A7%0A&lat=34.985849&lon=135.7587667&dist=3">test</a>  
                </div>
                <div>
                    <a href="https://map.yahooapis.jp/weather/V1/place?coordinates=139.732293,35.663613&appid=<?= YAHOO_APPID ?>&output=json">test</a>
                </div>-->

        <!--<div id="map" style="width:400px; height:300px"></div>-->
        <script type="text/javascript" charset="utf-8" src="https://map.yahooapis.jp/js/V1/jsapi?appid=<?= YAHOO_APPID ?>"></script>
        <script type="text/javascript">
            window.onload = function () {
//                var ymap = new Y.Map("map");
                var ymap = new Y.Map("map", {
                    configure: {
                        weatherOverlay: true
                    }
                });
                ymap.drawMap(new Y.LatLng(35.66572, 139.73100), 17, Y.LayerSetId.NORMAL);
            }

        </script>
        <!--<script src="js/myjs.js" type="text/javascript"></script>-->
    </body>
</html>
