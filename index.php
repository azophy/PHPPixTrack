<?php

$config_file_name = 'config.php';
if (file_exists($config_file_name))
    include $config_file_name;

$save_type      = getenv('SAVE_TYPE') ?: 'CSV'; 
$storage_path   = getenv("STORAGE_PATH" ) ?: 'logs.csv';
$table_name     = getenv("TABLE_NAME") ?: 'track_log';
$csv_delimiter = "\t";

/* === MAIN FUNCTION FOR LOGGING FUNCTION ===*/
function store_log($data) {
    global $save_type, $storage_path, $table_name, $csv_delimiter;

    //error_log( 'save_type '. $save_type);
    switch ($save_type) {
    case 'CSV': 
        error_log('masuk csv');
        
        if (!file_exists($storage_path)) 
            file_put_contents($storage_path,  implode($csv_delimiter, ['time','url','ip','referer','useragent','browser']).PHP_EOL );
        file_put_contents($storage_path,  implode($csv_delimiter, array_values($data)).PHP_EOL , FILE_APPEND | LOCK_EX );
        break;
    case 'POSTGRESQL' : 
        error_log('masuk pgsql');
        // ref: https://phpdelusions.net/pdo
        // ref : https://devcenter.heroku.com/articles/heroku-postgresql#connecting-with-pdo
        //echo 'storage_url:',$storage_path;
        $db = parse_url($storage_path);
        if ($db['scheme'] == 'postgres') $db['scheme'] = 'pgsql';
        print_r($db);

        $pdo = new PDO(sprintf(
            "%s:host=%s;port=%s;user=%s;password=%s;dbname=%s",
            $db["scheme"],
            $db["host"],
            $db["port"],
            $db["user"],
            $db["pass"],
            ltrim($db["path"], "/")
        ));
        $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        
        // init : create table
        try {
            $pdo->exec("CREATE TABLE $table_name(
                    ID              SERIAL PRIMARY KEY,
                    time            CHAR( 20 ) NOT NULL, 
                    ip              CHAR( 20 ) NOT NULL, 
                    url             CHAR( 250 ) NOT NULL,
                    useragent       TEXT DEFAULT NULL, 
                    referer         TEXT DEFAULT NULL, 
                    browser         TEXT DEFAULT NULL);" );
            /*$pdo->exec("CREATE TABLE IF NOT EXISTS $table_name(
                 ID INT( 11 ) AUTO_INCREMENT PRIMARY KEY,
                 time VARCHAR( 20 ) NOT NULL, 
                 ip VARCHAR( 20 ) NOT NULL, 
                 url VARCHAR( 250 ) NOT NULL,
                 useragent TEXT DEFAULT NULL, 
                 referer TEXT DEFAULT NULL, 
                 browser TEXT DEFAULT NULL);" );*/
            error_log("init table $table_name");
        } catch(PDOException $e) {
            error_log('error on creating table'. $e->getMessage() );
        }

        // actual logging process
        try {
            $result = $pdo->prepare("INSERT INTO $table_name (time, url, ip, referer, 
                useragent, browser) VALUES (?,?,?,?,?,?);" )
            ->execute([
                $data['time'],
                $data['url'],
                $data['ip'],
                $data['referer'],
                $data['useragent'],
                $data['browser'],
            ]);
            print_r($result);
        } catch(PDOException $e) {
            error_log( 'error on logging'. $e->getMessage() );
        }
        break;
    }
}

/* === INDEX PAGE === */
function index_page() {
    global $save_type, $storage_path, $table_name, $csv_delimiter;

    $current_log = [];
    switch ($save_type) {
        case 'CSV':
            if (file_exists($storage_path)) {
                foreach (file($storage_path) as $row_number => $line) {
                    if ($row_number < 1) continue;
                    $current_log[] = str_getcsv($line, $csv_delimiter); 
                }
                //ref: https://stackoverflow.com/a/30931557/2496217
            }
            break;
    }
?>
<h1>PHPPixTracker</h1>
url for pixel tracker : <?= sprintf("http://%s%s?action=pixel&tags=your-own-tags",$_SERVER[HTTP_HOST], strtok($_SERVER[REQUEST_URI],'?') ) ?>

<h2>Latest pixel tracker log</h2>
<?php //print_r($current_log); ?>
<style>
table {border:1px solid #000; border-collapse: collapse;}
table tr, table td, table th {border:1px solid #000; border-collapse: collapse;}
</style>
<table style=>
    <tr>
        <th>No</th>
        <th>Access Time</th>
        <th>IP</th>
        <th>Optional Tag</th>
        <th>User Agent</th>
    </tr>
    <?php foreach ($current_log as $row => $line): ?>
    <tr>
        <td><?=$row+1?></td>
        <td><?=$line[0]?></td>
        <td><?=$line[2]?></td>
        <td><?=$line[6]?></td>
        <td><?=$line[4]?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?= (empty($current_log)) ? "<strong>There are no access yet</strong>" : '' ?>
<?php
}

/* === INDEX PAGE === */
function pixel_page() {
    $data = [
        'time'      => date('m/d/Y H:i:s'),
        'url'       => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
        'ip'        => $_SERVER['REMOTE_ADDR'],
        'referer'   => $_SERVER['HTTP_REFERER'],
        'useragent' => $_SERVER['HTTP_USER_AGENT'],
        'browser'   => get_browser(null, true),
        'tags'       => (isset($_GET['tags']))?$_GET['tags']:'',
    ];
    store_log($data);

    // from https://stackoverflow.com/a/18852257/2496217
    // return 1x1 pixel transparent gif
    header("Content-type: image/gif");
    // needed to avoid cache time on browser side
    header("Content-Length: 42");
    header("Cache-Control: private, no-cache, no-cache=Set-Cookie, proxy-revalidate");
    header("Expires: Wed, 11 Jan 2000 12:59:00 GMT");
    header("Last-Modified: Wed, 11 Jan 2006 12:59:00 GMT");
    header("Pragma: no-cache");

    // from https://stackoverflow.com/a/51021174/2496217
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
}

/* ==== ROUTER SECTION  ===*/
//if (realpath($_SERVER["SCRIPT_FILENAME"]) == __FILE__) { // ref: https://stackoverflow.com/a/47174787/2496217
if ( count(get_included_files()) <= 1 ) { // https://stackoverflow.com/a/23722089/2496217
    if (!isset($_GET['action'])) {
        index_page();
    } else
        switch ($_GET['action']) {
        case 'pixel':
            pixel_page();
            break;
        case 'link':
            break;
        case 'index':
        default:
            index_page();
            break;
        }
}
