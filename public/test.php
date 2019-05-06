<?php

date_default_timezone_set('PRC');

define('APPLICATION_PATH', dirname(__DIR__));
define('APPLICATION_URL', rtrim(implode('', [$_SERVER['REQUEST_SCHEME'], '://', $_SERVER['HTTP_HOST'], str_replace('index.php', '', $_SERVER['SCRIPT_NAME'])]), '/'));
define('TIMESTAMP', $_SERVER['REQUEST_TIME']);
define('MICROTIME', microtime(true));
define('DEBUG_PASS', '__debug');
define('DEBUG_LEVEL', 3);
if (isset($_SERVER['HTTP_APIVERSION'])) {
    define('APIVERSION', 'v' . intval($_SERVER['HTTP_APIVERSION']));
} else if (isset($_POST['apiversion'])) {
    define('APIVERSION', 'v' . intval($_POST['apiversion']));
} else if (isset($_GET['apiversion'])) {
    define('APIVERSION', 'v' . intval($_GET['apiversion']));
} else {
    define('APIVERSION', 'v1');
}

$composerPath = APPLICATION_PATH . '/vendor/autoload.php';
if (file_exists($composerPath)) {
    require $composerPath;
}
require APPLICATION_PATH . '/application/library/Common.php';
require APPLICATION_PATH . '/application/library/Init.php';

$all_car_number = \app\library\DB::getInstance()->table('car_number')->select();
$all_car_number = array_column($all_car_number, 'car_number');
function similarCarNumber ($first, $second)
{
    $weight = [
        '贵' => ['鲁'],
        '鲁' => ['贵'],
        '0' => ['D', '6'],
        '1' => ['J', 'L'],
        '2' => ['Z'],
        '3' => ['8'],
        '5' => ['B'],
        '6' => ['C', '8', '0'],
        '8' => ['B', '3', '6'],
        'B' => ['5', '8'],
        'C' => ['G'],
        'D' => ['0'],
        'G' => ['C'],
        'J' => ['1'],
        'L' => ['1'],
        'Z' => ['2']
    ];
    $firstWord = mb_str_split($first);
    $firstWordLength = count($firstWord);
    $secondLength = count($second);
    $weightVector = round(1 / $firstWordLength, $firstWordLength);
    $firstNumber = substr($first, 3);
    $firstNumberLength = strlen($firstNumber);
    $minMatchPercent = 2;
    $similarResult = [];
    // 编辑距离
    foreach ($second as $v) {
        $secondNumber = substr($v, 3);
        if (strlen($secondNumber) == $firstNumberLength) {
            $similarPercent = levenshtein($firstNumber, $secondNumber);
            if ($similarPercent >= $minMatchPercent) {
                $similarResult[$v] = $similarPercent;
            }
        }
    }
    unset($second);
    if (empty($similarResult)) {
        return error('共查询' . $secondLength . '个车牌，未发现编辑距离大于' . $minMatchPercent . '的相似车牌');
    }
    asort($similarResult);
    $similarResult = array_slice($similarResult, 0, 10);
    // 相似度
    foreach ($similarResult as $k => $v) {
        similar_text($first, $k, $similarPercent);
        $similarResult[$k] = [
            $k, $v, round($similarPercent, $firstWordLength)
        ];
    }
    $similarResult = array_values($similarResult);
    // 权重
    foreach ($similarResult as $k => $v) {
        $splitCarNumber = mb_str_split($v[0]);
        $weightValue = [];
        foreach ($splitCarNumber as $kk => $vv) {
            $char = $firstWord[$kk];
            if (!isset($char)) {
                continue;
            }
            $weightScore = 0;
            if ($vv == $char) {
                $weightScore = 1;
            } else if (isset($weight[$char])) {
                if (false !== ($weightKey = array_search($vv, $weight[$char]))) {
                    $weightScore = (99 - $weightKey) / 100;
                }
            }
            $weightValue[] = round($weightScore * $weightVector, $firstWordLength);
        }
        $similarResult[$k][] = array_sum($weightValue);
        $similarResult[$k][] = implode(' + ', $weightValue);
    }
    array_multisort(array_column($similarResult, 3), SORT_DESC, SORT_NUMERIC, $similarResult);
    // 权重大于0.85
    if ($similarResult[0][2] < 0.85) {
        return error($similarResult, '共查询' . $secondLength . '个车牌，最大权重' . $similarResult[0][2] . '不达标');
    }
    return success($similarResult, '共查询' . $secondLength . '个车牌，找到相似车牌“' . $similarResult[0][0] . '”，相似度' . $similarResult[0][2] . '，权重值' . $similarResult[0][3]);
}

print_r(array_intersect(['NO_ENTRY', 'NO_START_NODE', 1,2], [3]));
//print_r(similarCarNumber('云A25JJJ', $all_car_number));
