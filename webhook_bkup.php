<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
//class UserList {
//
//  const UserList = ["U00fc5f8bdd3019bf36092adbf3e1daf1" => "NXTG京都事務所", "U17c03ab252d228fc92e722d8387807fc" => "Matsushita"];
//
//}
require_once('./LINEBotTiny.php');

$channelAccessToken = 'N+8wkIQrRD8aBK7irMLAZDqxAPhsQnyW+BGmp0KWu7orZ9Q15diLX/e+vNioMzgJW9LBubaqW4irFai1TC2xhEDaiBa2VS95H0nL2ntOjd/sE3SGjtaWsjWw4g7GwLDVrJUbie+J2Co0UVpOdGf0ngdB04t89/1O/w1cDnyilFU=';
$channelSecret = '13c4e0e2a0f28630341bf3dc2e3cd725';

$client = new LINEBotTiny($channelAccessToken, $channelSecret);

foreach ($client->parseEvents() as $event) {
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];

            $url = parse_url(getenv("DATABASE_URL"));
            $con = pg_connect("host=" . $url['host'] . " port="
                    . $url['port'] . " dbname=" . substr($url['path'], 1)
                    . " user=" . $url['user'] . " password=" . $url['pass']);

            //LINEIDの取得
            $lineid = $event['source']['userId'];

            //既にDBに登録されているかチェック
            $res = pg_query_params(
                    $con, "select name from users where id = $1", array($lineid)
            );
            while ($row = pg_fetch_row($res)) {
                $username = $row[0];
            }

            //登録されていないとき
            if ($username == "") {
                //アクセストークン取得
                $postData = array(
                    'grant_type' => 'client_credentials',
                    'client_id' => '1642535603',
                    'client_secret' => '13c4e0e2a0f28630341bf3dc2e3cd725'
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
                curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/v2/oauth/accessToken');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //あとでさくじょ
                $response = curl_exec($ch);
                curl_close($ch);
                $json = json_decode($response);
                $accessToken = $json->access_token;

                //ユーザー情報取得
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
                curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/v2/bot/profile/' . $event['source']['userId']);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //あとでさくじょ
                $response = curl_exec($ch);
                curl_close($ch);
                $json = json_decode($response);
                $displayName = $json->displayName; //名前の取得
                // データを登録するためのSQLを作成
                $sql = "INSERT INTO users (id, name) VALUES ($1, $2)";

                // SQLクエリ実行
                $res = pg_query_params($con, $sql, array($event['source']['userId'], $displayName));
                $username = $displayName;
            }
            //接続を閉じる
            pg_close($con);

            switch ($message['type']) {
                case 'text': //テキストメッセージのとき
                    //
		  $client->replyMessage(array(
                        'replyToken' => $event['replyToken'],
                        'messages' => array(
                            array(
                                'type' => 'text',
//					  'text' => UserList::UserList[$event['source']['userId']] . "さん、こんにちは☆" . $message['text'],
                                'text' => $username . "さん、こんにちは☆",
                            )
                        )
                    ));
                    break;
                case 'sticker': //スタンプのとき
                    //
		  $client->replyMessage(array(
                        'replyToken' => $event['replyToken'],
                        'messages' => array(
                            array(
                                'type' => 'text',
//					  'text' => UserList::UserList[$event['source']['userId']] . "さん、かわいいスタンプですね",
                                'text' => $username . "さん、かわいいスタンプですね",
                            )
                        )
                    ));
                    break;
                default:
                    error_log("Unsupporeted message type: " . $message['type']);
                    break;
            }
            break;
        default:
            error_log("Unsupporeted event type: " . $event['type']);
            break;
    }
};
