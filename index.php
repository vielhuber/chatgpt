<?php
require_once(__DIR__ . '/vendor/autoload.php');
use vielhuber\dbhelper\dbhelper;
use vielhuber\stringhelper\__;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$db = new dbhelper();
$db->connect('pdo', 'mysql', @$_SERVER['DB_HOST'], @$_SERVER['DB_USERNAME'], @$_SERVER['DB_PASSWORD'], @$_SERVER['DB_DATABASE'], 3306);
$messages = $db->fetch_all('SELECT * FROM messages ORDER BY date DESC, id DESC');

if( @$_POST['create_new'] != '' ) {
    $history = [];
    foreach( $db->fetch_all('SELECT * FROM messages ORDER BY date ASC, id ASC') as $messages__value ) {
        $history[] = ['role' => $messages__value['role'], 'content' => $messages__value['content']];
    }
    $response = __::chatgpt(
        prompt: $_POST['content'],
        history: $history,
        temperature: 0.7,
        model: 'gpt-3.5-turbo',
        api_key: @$_SERVER['OPENAI_API_KEY']
    );
    $db->insert('messages', [
        'role' => 'user',
        'content' => $_POST['content'],
        'date' => date('Y-m-d H:i:s', strtotime('now'))
    ]);
    $db->insert('messages', [
        'role' => 'assistant',
        'content' => $response['response'],
        'date' => date('Y-m-d H:i:s', strtotime('now'))
    ]);
    header("Location: " . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH).'?usage_costs='.$response['usage']['costs']);
    die();
}

echo '
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, minimum-scale=1" />
    <title>chatgpt</title>
    <script>

    </script>
    <style>
    *
    {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    </style>
</head>
<body>
';
    if( @$_GET['usage_costs'] != '' ) {
        echo '<strong>Kosten letzter Aufruf: '.($_GET['usage_costs']*0.92).'€</strong><br/>';
    }
    echo '<form method="post">';
        echo '<textarea name="content"></textarea>';
        echo '<input type="submit" name="create_new" value="Absenden" />';
    echo '</form>';
    echo '<ul>';
        foreach($messages as $messages__value) {
            echo '<li>';
                echo $messages__value['role'].': ';
                echo $messages__value['content'].' ('.date('d.m.Y H:i:s', strtotime($messages__value['date'])).')';
            echo '</li>';
        }
    echo '</ul>';
echo '
</body>
</html>
';