<?php
session_start();
require_once 'config.php';

$postData = array(
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => 'https://nxtg-linebot.herokuapp.com/afterLogin.php',
    'client_id' => LOGINAPP_CHANNEL_ID,
    'client_secret' => LOGINAPP_SECRET_TOKEN
);

$ch = curl_init();

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/oauth2/v2.1/token');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response);
$accessToken = $json->access_token;


$ch = curl_init();

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/v2/profile');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response);
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"content="width=320,height=480,initial-scale=1.0,minimum-scale=1.0,maximum-scale=2.0,user-scalable=yes" />
        <title>ログイン中</title>
    </head>
    <body>
    test
        <div><?php echo $_SESSION['username'] ?>さん、ログイン中</div>
        <form method="POST">
            <input type="button" value="ログアウト" onclick="location.href = 'index.php'"/>

        </form>
    </body>
</html>
