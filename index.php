<?php
//@session_start();
error_reporting(E_ALL);
header("Content-Type: text/html; charset=utf-8;");

function my_str_to_lower($str) {
    $ru_array = array('А' => 'а','Б' => 'б','В' => 'в','Г' => 'г','Д' => 'д', 'Е' => 'е','Ё' => 'ё','Й' => 'й','Ж' => 'ж','З' => 'з','И' => 'и','К' => 'к','Л' => 'л','М' => 'м','Н' => 'н','О' => 'о','П' => 'п','Р' => 'р','С' => 'с','Т' => 'т','У' => 'у','Ф' => 'ф','Х' => 'х','Ц' => 'ц','Ч' => 'ч','Ш' => 'ш','Щ' => 'щ','Ъ' => 'ъ','Ы' => 'ы','Ь' => 'ь','Э' => 'э','Ю' => 'ю','Я' => 'я');
    return strtr($str, $ru_array ); }

function get_cities_from_csv($file)
{
    $arr = [];
    if ($file = fopen($file, 'r')) {
//        [[:punct:]] любые знаки пунктуации
        while ($city = preg_split("/[[:punct:]]/", fgetcsv($file)[0])[0]) {
            $arr[] = $city;
        }
    }
    fclose($file);
    return $arr;
}

function is_already_used($city) {
    global $used_cities;
    return in_array($city, $used_cities);
}

function generate_next_city($cur_city, $all, $used)
{
    global $available;
    $available = available_cities($cur_city, $all, $used);
    return sizeof($available)!=0 ? $available[array_rand($available)] : null;
}

function available_cities($cur_city, $all_cities, $used_cities) {
    $available = [];
    $invalid = array("ы","ь","ъ","й");
    echo $cur_city;
//    TODO
    $letter = in_array(iconv_substr($cur_city, -1, 1), $invalid) ? iconv_substr($cur_city, -2, 1) : iconv_substr($cur_city, -1, 1);
//    echo "letter is " . $letter;
    foreach ($all_cities as $city) {
//        echo $city."<br>";
        if (iconv_substr(my_str_to_lower($city), 0, 1)==$letter) {
            if (!in_array($city, $used_cities))
                $available[] = $city;
        }
    }
    return $available;
}

$cities = get_cities_from_csv("cities.csv");
$used_cities = [];
$available = [];
$game_over = false;
$message = "";
$user_city = "";
$server_city = "";

if (isset($_GET["concede"])) {
    $game_over = true;
    $message = "You lose, try again";
}

elseif (isset($_GET["new-game"])) {
//    $game_over = false;
}

elseif (isset($_REQUEST["submit"])) {
    if (isset($_GET["user"])) {
        $user_city = $_GET["user"];
        $used_cities = isset($_GET["used"]) ? $_GET["used"] : [];
        $server_city = $_GET["server"];
        if (is_already_used($user_city)) {
            $message = "City was already used";
        }
        else {
            $server_city = generate_next_city($user_city, $cities, $used_cities);
            if (is_null($server_city)) {
                $game_over = true;
                $message = "You won!";
            } else {
                array_push($used_cities, $user_city, $server_city);
            }
        }
    }
}
else {
    $message = "Enter a city";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>The Cities Game</title>
</head>
<body>
<p style="color: red"><?php echo $message?></p>
<form method="get" action="">
    <div class="user-input">
        <label for="user">Ваш город: </label><input type="text" max="30" id="user" name="user" value="<?php
        echo $user_city;
        ?>">
    </div>
    <div class="server-input">
        <label for="server">Ответ сервера: </label><input type="text" max="30" id="server" name="server" value="<?php
        echo $server_city;
        ?>" readonly>
    </div>
    <?php
    if ($game_over)
        echo "<button type='submit' id='new-game' name='new-game' value='1'>Новая игра</button>";
    else
        echo '<button type="submit" id="submit" name="submit" value="1">Отправить</button>
              <button type="submit" id="concede" name="concede" value="1">Сдаться</button>';
    ?>
    <div>
        <h3>Использованные города: </h3>
        <?php
        foreach ($used_cities as $city)
            echo "<input type='text' name='used[]' value='$city' readonly/><br>";

        if (sizeof($used_cities) && !$game_over) {
        ?>
        <h3>Сервер выбрал: </h3>
        <?php
            foreach ($available as $city) {
                if ($city===$server_city) echo "<span style='color: red'>$city</span>";
                else echo $city;
                echo "<br>";
            }
        }
        ?>
    </div>
</form>
</body>
</html>
