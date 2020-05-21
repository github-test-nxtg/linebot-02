<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"content="width=320,height=480,initial-scale=1.0,minimum-scale=1.0,maximum-scale=2.0,user-scalable=yes" />
        <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
        <link href="css/mycss.css" rel="stylesheet">
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
        <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
        <script src="js/myjs.js"></script>
        <title>ログイン画面</title>
    </head>
    <body>
        <div class="container">
            <div class="login-container">
                <div id="output"></div>
                <div class="avatar"></div>
                <div class="form-box">
                    <form action="" method="POST">
                        <input name="user" type="text" placeholder="username">
                        <input name="password" type="password" placeholder="password">
                        <input type="hidden" value="<?php echo $_GET['linkToken'] ?>" name="linkToken" /> 
                        <input type="hidden" value="<?php echo $_GET['uuid'] ?>" name="uuid" />
                        <button class="btn btn-info btn-block login" type="submit" name="login">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uuid = $_POST['uuid'];
    $linkToken = $_POST['linkToken'];
    $loginId = $_POST['user'];
    $password = $_POST['password'];
    $nonce = md5(uniqid(rand(), true));

    $url = parse_url(getenv("DATABASE_URL"));
    $conInfo = "host=" . $url['host'] . " port=" . $url['port'] . " dbname=" . substr($url['path'], 1) . " user=" . $url['user'] . " password=" . $url['pass'];

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

    if ($count !== '0') { //ログイン成功
        try {
            // LINEのデータを登録する
            $con = pg_connect($conInfo);
            $sql = "UPDATE users SET uuid = $1, nonce = $2 WHERE email = $3";
            $res = pg_query_params($con, $sql, array($uuid, $nonce, $loginId,));
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            exit;
        } finally {
            pg_close($con);
        }
        header("Location: https://access.line.me/dialog/bot/accountLink?linkToken=" . $linkToken . "&nonce=" . $nonce);
        exit;
    } else {
        echo <<< EOM
        <script type="text/javascript">$("#output").addClass("alert alert-danger animated fadeInUp").html("Could not login");</script>
EOM;
    }
}
?>

