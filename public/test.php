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
    $result = [];
    foreach ($second as $v) {
        $result[$v] = [
            similar_text($first, $v),
            levenshtein($first, $v)
        ];
    }
    array_multisort($result, SORT_DESC, $edition, SORT_ASC , $result);
    arsort($similarArr);
    print_r(array_slice($similarArr, 0, 10));
    asort($levArr);
    print_r(array_slice($levArr, 0, 10));
    return;
    echo $similar;
    echo '<br>';
    echo $similarRes;
    echo '<br>';
    echo $len;
    echo '<br>';
    echo $lenRes;
    echo '<br>';
    return 0.0;
}

similarCarNumber('贵A25C56', $all_car_number);
