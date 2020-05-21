<?php

require_once 'config.php';
//use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
//use \LINE\LINEBot;
//use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
//use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
//use \LINE\LINEBot\Constant\HTTPHeader;
//LINESDKの読み込み
require_once(__DIR__ . "/vendor/autoload.php");

require_once('./LINEBotTiny.php');
$client = new LINEBotTiny(ACCESS_TOKEN, SECRET_TOKEN);

$json_string = file_get_contents('php://input');
//受け取ったJSON文字列をデコード
$json_obj = json_decode($json_string);
//このイベントへの応答に使用するトークン
$reply_token = $json_obj->{'events'}[0]->{'replyToken'};
//LINEUserIDの取得
$userId = $json_obj->{'events'}[0]->{'source'}->{'userId'};

//DisplayNameの取得
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(ACCESS_TOKEN);
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => SECRET_TOKEN]);
$response = $bot->getProfile($userId);
$displayName = '';
if ($response->isSucceeded()) {
    $profile = $response->getJSONDecodedBody();
//    echo $profile['displayName'];
//    echo $profile['pictureUrl'];
//    echo $profile['statusMessage'];
    $displayName = $profile['displayName'];
}

$yahooAppId = "dj00aiZpPU1VblJoaUpLeTBCUCZzPWNvbnN1bWVyc2VjcmV0Jng9ZDc-";

////連携トークン取得
//$response = $httpClient->post("https://api.line.me/v2/bot/user/" . $userId . "/linkToken", []);
//$rowBody = $response->getRawBody();
//$responseObject = json_decode($rowBody);
////$linkToken = object_get($responseObject, "linkToken");
//$linkToken = $responseObject->linkToken;
//イベントの処理
foreach ($client->parseEvents() as $event) {

    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text': //テキストメッセージのとき
                    switch ($message["text"]) {
                        case '現在地の降水強度':
                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'template',
                                        'altText' => '送信確認',
                                        'template' => ['type' => 'confirm', 'text' => '現在地を送信しますか？',
                                            'actions' => [
                                                ['type' => 'uri', 'label' => '送信する', 'uri' => 'line://nv/location'],
                                                ['type' => 'postback', 'label' => 'しない', 'data' => 'action=cancel'],
                                            ]
                                        ]
                                    )
                                )
                            ));
                            break;
                        case 'ボタン':
                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'template',
                                        'altText' => 'ボタン',
                                        'template' => [
                                            'type' => 'buttons',
                                            'title' => 'タイトルです',
                                            'text' => '選択してね',
                                            'actions' => [
                                                [
                                                    'type' => 'postback',
                                                    'label' => 'webhookにpost送信',
                                                    'data' => 'value'
                                                ],
                                                [
                                                    'type' => 'uri',
                                                    'label' => 'googleへ移動',
                                                    'uri' => "https://map.yahooapis.jp/js/V1/jsapi?appid={$yahooAppId}"
                                                ]
                                            ]
                                        ]
                                    )
                                )
                            ));
                            break;
                        case 'アカウント連携':
                            //連携トークン取得
                            $response = $httpClient->post("https://api.line.me/v2/bot/user/" . $userId . "/linkToken", []);
                            $rowBody = $response->getRawBody();
                            $responseObject = json_decode($rowBody);
                            //$linkToken = object_get($responseObject, "linkToken");
                            $linkToken = $responseObject->linkToken;

                            //DBに接続し、アカウント連携されているか確認する
                            $url = parse_url(getenv("DATABASE_URL"));
                            $con = pg_connect("host=" . $url['host'] . " port="
                                    . $url['port'] . " dbname=" . substr($url['path'], 1)
                                    . " user=" . $url['user'] . " password=" . $url['pass']);
                            $res = pg_query_params(
                                    $con, "select count(*) from users where uuid = $1", array($event['source']['userId'],)
                            );
                            pg_close($con);
                            $count = '';
                            while ($row = pg_fetch_row($res)) {
                                $count = $row[0];
                            }

                            if ($count == '0') { //アカウント連携されていないとき
                                $client->replyMessage(array(
                                    'replyToken' => $event['replyToken'],
                                    'messages' => array(
                                        array(
                                            'type' => 'template',
                                            'altText' => '確認ダイアログ',
                                            'template' => ['type' => 'confirm', 'text' => 'アカウント連携しますか？',
                                                'actions' => [
                                                    ['type' => 'uri', 'label' => '連携する', 'uri' => 'https://nxtg-linebot.herokuapp.com/login.php?linkToken=' . $linkToken . '&uuid=' . $event['source']['userId'],],
                                                    ['type' => 'postback', 'label' => 'しない', 'data' => 'action=cancel'],
                                                ]
                                            ]
                                        )
                                    )
                                ));
                            } else { //アカウント連携されているとき
                                $client->replyMessage(array(
                                    'replyToken' => $event['replyToken'],
                                    'messages' => array(
                                        array(
                                            'type' => 'template',
                                            'altText' => '確認ダイアログ',
                                            'template' => ['type' => 'confirm', 'text' => '連携を解除しますか？',
                                                'actions' => [
                                                    ['type' => 'postback', 'label' => '解除する', 'data' => 'action=unlink&userId=' . $event['source']['userId'],],
                                                    ['type' => 'postback', 'label' => 'しない', 'data' => 'action=cancel'],
                                                ]
                                            ]
                                        )
                                    )
                                ));
                            }
                            break;
                        case 'カルーセル' :
                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'template',
                                        'altText' => 'カルーセル',
                                        'template' => [
                                            'type' => 'carousel',
                                            'columns' => [
                                                [
                                                    'title' => 'カルーセル1',
                                                    'text' => 'カルーセル1です',
                                                    'actions' => [
                                                        [
                                                            'type' => 'postback',
                                                            'label' => 'webhookにpost送信',
                                                            'data' => 'value'
                                                        ],
                                                        [
                                                            'type' => 'uri',
                                                            'label' => 'googleへ移動',
                                                            'uri' => 'https://nxtg-linebot.herokuapp.com/'
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'title' => 'カルーセル2',
                                                    'text' => 'カルーセル2です',
                                                    'actions' => [
                                                        [
                                                            'type' => 'postback',
                                                            'label' => 'webhookにpost送信',
                                                            'data' => 'value'
                                                        ],
                                                        [
                                                            'type' => 'uri',
                                                            'label' => 'googleへ移動',
                                                            'uri' => 'https://nxtg-linebot.herokuapp.com/index.php?userId=test'
                                                        ]
                                                    ]
                                                ],
                                            ]
                                        ]
                                    )
                                )
                            ));
                            break;
                        case '確認' :
                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'template',
                                        'altText' => '確認ダイアログ',
                                        'template' => ['type' => 'confirm', 'text' => '元気ですかー？',
                                            'actions' => [
                                                ['type' => 'message', 'label' => '元気です', 'text' => '元気です'],
                                                ['type' => 'message', 'label' => 'まあまあです', 'text' => 'まあまあです'],
                                            ]
                                        ]
                                    )
                                )
                            ));
                            break;
                        case '天気教えて':
                        case '滋賀の天気':
                            $url = "http://weather.livedoor.com/forecast/webservice/json/v1?city=250010";
                            $json = file_get_contents($url);
                            $array = json_decode($json, true);
							$weatherForecast = $array["description"]["text"];

							$url = "http://geoapi.heartrails.com/api/json?method=getStations&postal=5250036";
                            $json = file_get_contents($url);
                            $array = json_decode($json, true);
//							$stationName = $array["response"]["station"]["name"];

                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'location',
                                        'title' => 'title',
                                        'address' => 'address',
                                        'latitude' => 35.004389,
                                        'longitude' => 135.864432,
                                    ),
//                                    array(
//                                        'type' => 'image',
//                                        'originalContentUrl' => 'https://nxtg-linebot.herokuapp.com/image/1.jpg',
//                                        'previewImageUrl' => 'https://nxtg-linebot.herokuapp.com/image/2.jpg',
//                                    ),
                                    array(
                                        'type' => 'text',
                                        'text' => $weatherForecast,
                                    ),
                                    array(
                                        'type' => 'text',
                                        'text' => $stationName,
                                    ),
                                )
                            ));
                            break;
                        case '京都の天気' :
                            $url = "http://weather.livedoor.com/forecast/webservice/json/v1?city=260010";
                            $json = file_get_contents($url);
                            $array = json_decode($json, true);
                            
                            $weatherForecast = $array["description"]["text"];
                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'text',
                                        'text' => $weatherForecast,
                                    )
                                )
                            ));
                            break;
                        default:
                            break;
                    }
                    break;
                case 'sticker': //スタンプのとき
                    $client->replyMessage(array(
                        'replyToken' => $event['replyToken'],
                        'messages' => array(
                            array(
                                'type' => 'text',
                                'text' => $displayName . "さん、かわいいスタンプですね",
                            )
                        ),
                        'messages' => array(
                            array(
                                'type' => 'text',
                                'text' => $displayName . "さん、かわいいスタンプですね",
                            )
                        )
                    ));
                    break;
                case 'location': //位置情報のとき

                    $latitude = $message['latitude'];
                    $longitude = $message['longitude'];

                    //YahooApi
                    $url = "https://map.yahooapis.jp/weather/V1/place?coordinates={$longitude},{$latitude}&appid={$yahooAppId}&output=json";
                    $ch = curl_init();
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
                    $results = curl_exec($ch);
                    curl_close($ch);
                    $arrayYahoo = json_decode($results, true);
                    $format = "YmdHi";
                    $date = Datetime::createFromFormat($format, $arrayYahoo["Feature"]["0"]["Property"]["WeatherList"]["Weather"]["0"]["Date"]);

                    //LivedoorApi
//                    $url = "http://weather.livedoor.com/forecast/webservice/json/v1?city=260010";
//                    $ch = curl_init();
//                    $options = array(
//                        // 取得するURLを指定
//                        CURLOPT_URL => $url,
//                        // trueの場合、curl_exec() の戻り値が文字列
//                        CURLOPT_RETURNTRANSFER => true,
//                        // 接続の試行を待ち続ける秒数を指定
//                        CURLOPT_CONNECTTIMEOUT => 5,
//                        // falseの場合、サーバー証明書の検証をしない。HTTPS接続がエラーの際に設定
//                        CURLOPT_SSL_VERIFYPEER => false
//                    );
//                    curl_setopt_array($ch, $options);
//                    $results = curl_exec($ch);
//                    curl_close($ch);
//                    $arrayLivedoor = json_decode($results, true);




                    $client->replyMessage(array(
//                        'replyToken' => $event['replyToken'],
//                        'messages' => array(
//                            array(
//                                'type' => 'image',
//                                "originalContentUrl" => "https://map.yahooapis.jp/search/local/V1/localSearch?appid=dj00aiZpPU1VblJoaUpLeTBCUCZzPWNvbnN1bWVyc2VjcmV0Jng9ZDc-&query=%E3%83%A9%E3%83%BC%E3%83%A1%E3%83%B3",
//                                "previewImageUrl" => "https://example.com/preview.jpg"
//                            )
//                        )
                        'replyToken' => $event['replyToken'],
                        'messages' => array(
                            array(
                                'type' => 'text',
                                "text" => "緯度：" . $latitude . "\n経度：" . $longitude . "\n" . $date->format("Y年m月d日H時i分")
                                . "現在の降水強度は" . $arrayYahoo["Feature"]["0"]["Property"]["WeatherList"]["Weather"]["0"]["Rainfall"] . "mm/hです。",
                            ),
                            array(
                                'type' => 'text',
                                "text" => "30分後の降水強度は" . $arrayYahoo["Feature"]["0"]["Property"]["WeatherList"]["Weather"]["3"]["Rainfall"] . "mm/hです。",
                            )
                        )
                    ));




                    break;
                default:
                    error_log("Unsupporeted message type: " . $message['type']);
                    break;
            }
            break;
        case 'postback': // ポストバック受け取り時
            // 送られたデータ
            $postback = $json_obj->{'events'}[0]->{'postback'}->{'data'};

            parse_str($postback, $data);
            $userId = $data['userId'];
            $action = $data['action'];

            if ($action === 'unlink') {
                // 連携解除
                $url = parse_url(getenv("DATABASE_URL"));
                $con = pg_connect("host=" . $url['host'] . " port="
                        . $url['port'] . " dbname=" . substr($url['path'], 1)
                        . " user=" . $url['user'] . " password=" . $url['pass']);

                //uuidとnonceを削除
                $sql = "UPDATE users SET uuid = NULL, nonce = NULL WHERE uuid = $1";
                // SQLクエリ実行
                $res = pg_query_params($con, $sql, array($event['source']['userId']));
                //接続を閉じる
                pg_close($con);

                $message = array(
                    'type' => 'text',
                    'text' => $displayName . 'さんのアカウント連携を解除しました'
                );
            } elseif ($action === 'cancel') {
                $message = array(
                    'type' => 'text',
                    'text' => 'キャンセルしました',
                );
            } else {
                $message = array(
                    'type' => 'text',
                    'text' => '',
                );
            }

            $post_data = array(
                'replyToken' => $reply_token,
                'messages' => array($message)
            );

            // CURLでメッセージを返信する
            $ch = curl_init('https://api.line.me/v2/bot/message/reply');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charser=UTF-8',
                'Authorization: Bearer ' . ACCESS_TOKEN
            ));
            $result = curl_exec($ch);
            curl_close($ch);

            break;
        default:
            error_log("Unsupporeted event type: " . $event['type']);
            break;
    }
};
