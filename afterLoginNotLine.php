<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION["username"])) {
	header("Location: " . (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . "/LINEBot/index.html");
	exit;
}

$x = filter_input(INPUT_POST, "x");
if ($x === "logout") {
//  if (ini_get("session.use_cookies")) {
//    $params = session_get_cookie_params();
//    setcookie(session_name(), '', time() - 42000);
//  }
	session_destroy();
	unset($_SESSION["username"]);
//    header("Location: https://nxtg-linebot.herokuapp.com/index.php");
	header("Location: " . (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . "/LINEBot/index.html");
	exit;
}

const API_KEY = "84ca081ad20de5447c472ac3391f0e20";
const BASE_URL = "http://api.openweathermap.org/data/2.5/forecast";

$url = BASE_URL . "?q=Kyoto,jp&APPID=" . API_KEY . "&units=metric";
$json = file_get_contents($url);
$forecastList = json_decode($json, true);
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"content="width=320,height=480,initial-scale=1.0,minimum-scale=1.0,maximum-scale=2.0,user-scalable=yes" />
		<style>
			.google-visualization-tooltip {
				opacity: 0.9;  /* 透明度を変えてみる */
			}
		</style>
		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<script type="text/javascript">
            google.load('visualization', '1', {packages: ['corechart']});
            google.charts.setOnLoadCallback(drawChart);
            function drawChart() {

            // 表示するデータの設定
            var data = new google.visualization.DataTable();
            data.addColumn('string', '時間');
            data.addColumn('number', '気温(℃)');
            data.addColumn('number', '降雨量(mm)');
            data.addRows([
<?php foreach ($forecastList["list"] as $key => $value) { ?>
	            ['<?php echo date("H:i", strtotime($value["dt_txt"])) ?>', <?php echo $value["main"]["temp"] ?>, <?php echo isset($value["rain"]["3h"]) ? $value["rain"]["3h"] : 0 ?>],
	<?php if ($key === 16) break; ?>
<?php } ?>
            ]);
            // グラフの設定
            var option = {
            title: '<?php echo $forecastList["city"]["name"] ?>',
                    width: '1000',
                    height: '600',
                    series: [
                    {type: 'line', targetAxisIndex: 0},
                    {type: 'bars', targetAxisIndex: 1},
//                        {type: 'area', targetAxisIndex: 2}
                    ],
                    vAxes: [
//                        {title: '気温(℃)', minValue: 10, maxValue: 35, gridlined: {count: 5}},
                    {minValue: 10, maxValue: 35, gridlined: {count: 5}},
//                        {title: '降雨量(mm)'},
//                        {title: '消費カロリー（kcal）'}
                    ],
                    xAxes:[
                            barPercentage: 50,
							categoryPercentage: 50,
                    ],
                    focusTarget: 'category',
                    curveType: 'function',
                    fontSize: 10,
                    legend: {position: 'in'}
            };
            var chart = new google.visualization.ComboChart(document.getElementById('curve_chart'));
            chart.draw(data, option);
            }
		</script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
        <title>ログイン中</title>
    </head>
    <body style='font-family: "游ゴシック Medium","Yu Gothic Medium","游ゴシック体",YuGothic,sans-serif;'>
		<!--<div style="text-align: center">-->

		<form method="post">
			<?php echo $_SESSION['username'] ?>さん、ログイン中
			<input type="submit" name="x" value="logout" />
		</form>
		<canvas id="myChart"></canvas>
		<div class="weather-forecast-icons" style="display:flex;padding-left: 40px">
			<?php
			foreach ($forecastList["list"] as $key => $value) {
				?>
				<div class="weather-forecast-icons-item" style=""><img src="//openweathermap.org/img/w/<?php echo $value["weather"][0]["icon"] ?>.png" alt="forecast"></div>
				<?php if ($key === 16) break; ?>
				<?php
			}
			?>
		</div>
		<div class="forecast" style="margin: 0 auto">
			<div id="curve_chart" style=""></div>
			<div style="width:625px">

				<div class="weather-forecast-icons" style="display:flex;justify-content: space-between;">
					<?php
					foreach ($forecastList["list"] as $key => $value) {
						?>
						<div class="weather-forecast-icons-item" style=""><img src="//openweathermap.org/img/w/<?php echo $value["weather"][0]["icon"] ?>.png" alt="forecast"></div>
							<?php if ($key === 16) break; ?>
							<?php
						}
						?>
				</div>
			</div>
		</div>
<!--		<table style="border: solid 2px orange;margin: 0 auto">
		<?php
//			foreach ($forecastList["list"] as $key => $value) {
		?>
				<tr>
					<td style="border: dashed 1px orange;"><?php // echo date('Y/m/d H:i:s', $value["dt"])         ?></td>
					<td style="border: dashed 1px orange;"><?php // echo $value["weather"][0]["description"]         ?></td>
					<td style="border: dashed 1px orange;"><img src="//openweathermap.org/img/w/<?php // echo $value["weather"][0]["icon"]         ?>.png" alt="forecast"></td>
				</tr>
		<?php
//			}
		?>
		</table>-->
		<script>
            var ctx = document.getElementById('myChart');
//            var myChart = new Chart(ctx, {
//                type: 'bar',
//                data: {
//                    labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
//                    datasets: [{
//                            label: '# of Votes',
//                            data: [12, 19, 3, 5, 2, 3],
//                            backgroundColor: [
//                                'rgba(255, 99, 132, 0.2)',
//                                'rgba(54, 162, 235, 0.2)',
//                                'rgba(255, 206, 86, 0.2)',
//                                'rgba(75, 192, 192, 0.2)',
//                                'rgba(153, 102, 255, 0.2)',
//                                'rgba(255, 159, 64, 0.2)'
//                            ],
//                            borderColor: [
//                                'rgba(255, 99, 132, 1)',
//                                'rgba(54, 162, 235, 1)',
//                                'rgba(255, 206, 86, 1)',
//                                'rgba(75, 192, 192, 1)',
//                                'rgba(153, 102, 255, 1)',
//                                'rgba(255, 159, 64, 1)'
//                            ],
//                            borderWidth: 1
//                        }]
//                },
//                options: {
//                    scales: {
//                        yAxes: [{
//                                ticks: {
//                                    beginAtZero: true
//                                }
//                            }]
//                    }
//                }
//            });




            var myMixedChart = new Chart(ctx, {
            type: 'bar',
                    data: {
						labels: [

<?php foreach ($forecastList["list"] as $key => $value) { ?>
	                    '<?php echo date("H:i", strtotime($value["dt_txt"])) ?>',
	<?php if ($key === 16) break; ?>
<?php } ?>
                    ],
                            datasets: [{
                            //棒グラフ
                            label: "降水量(mm)",
                                    data: [
//										30, 45, 40, 35, 30, 40, 30
<?php foreach ($forecastList["list"] as $key => $value) { ?>
	<?php echo isset($value["rain"]["3h"]) ? $value["rain"]["3h"] : 0 ?>,
	<?php if ($key === 16) break; ?>
<?php } ?>
                                    ],
									
                                    backgroundColor: "rgba(255,153,153,0.5)",
                                    yAxisID: 'left-y-axis',
                            }, {
                            //折れ線グラフ
                            label: "気温(℃)",
                                    type: 'line',
                                    data: [
//								1400, 1550, 1470, 1390, 1500, 1480, 1420
<?php foreach ($forecastList["list"] as $key => $value) { ?>
	<?php echo $value["main"]["temp"] ?>,
	<?php if ($key === 16) break; ?>
<?php } ?>
                                    ],
                                    borderColor: "rgba(153,153,255,1)",
                                    backgroundColor: "rgba(0,0,0,0)",
                                    yAxisID: 'right-y-axis'
                            }]
                    },
                    options: {
						title: {
						display: true,
						text: '天気予報'
						},
                            scales: {
                            yAxes: [
                            {
                            id: 'left-y-axis',
                                    position: 'left',
                                    ticks: {
//                                    suggestedMax: 50,
                                    suggestedMax: 10,
                                            suggestedMin: 0,
                                            stepSize: 5,
                                            callback: function (value, index, values) {
                                            return  value + 'mm'
                                            }
                                    },
						
                            }, {
                            id: 'right-y-axis',
                                    position: 'right',
                                    ticks: {
                                    suggestedMax: 40,
                                            suggestedMin: 10,
                                            stepSize: 5,
                                            callback: function (value, index, values) {
                                            return  value + '℃'
                                            }
                                    },
                                    // グリッドラインを消す
                                    gridLines: {
                                    drawOnChartArea: false,
                                    },
                            }
                            ],
//							xAxes[{
//								id : 'left-x-axis',
//								 position: 'left',
//								 barThickness: 6,
//							}]
                            }
                    }

            });

		</script>
    </body>
</html>
