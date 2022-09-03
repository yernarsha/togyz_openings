<?php
# extension=sqlite3
# extension=pdo_sqlite

require 'lib.php';

$working_mode = 0;

if ($working_mode == 0) {
    read_sqlite();
} else {
    read_sqlite_PDO();
}

function addPlayer($playerName, &$players) {
    if (array_key_exists($playerName, $players)) {
        $players[$playerName] = $players[$playerName] + 1;
    } else {
        $players[$playerName] = 1;
    }
}

function addOpening($res, &$openings) {
    $res = str_replace("x", "", $res);

    if (array_key_exists($res, $openings)) {
        $openings[$res] = $openings[$res] + 1;
    } else {
        $openings[$res] = 1;
    }
}

function togyzSequence($notat, &$openings) {
    $res = "";
    $seq = explode("\n", $notat);

    $i = 1;
    foreach ($seq as $value) {
        $v = explode(" ", $value);
        if (count($v) >= 2) {
            $res .= $v[1];
            addOpening($res, $openings);
        }

        if (count($v) >= 3) {
            $res .= $v[2];
            addOpening($res, $openings);
        }

        $i++;
        if ($i > 10) {
            break;
        }
    }
}

function savePlayers($players) {
    arsort($players);
    $player_file = fopen("players.txt", "w");
    foreach ($players as $player=>$value) {
        fwrite($player_file, $player . " - " . $value . "\n");
    }
    fclose($player_file);
/*
    $flattened = $players;
    arsort($flattened);
    array_walk($flattened, function(&$value, $key) {
        $value = "{$key}:{$value}";
    });
    echo "Players:\n" . implode("\n", $flattened);
 */
}

function togNormalize($s) {
    $res = "";
    $len = strlen($s) / 2;
    for ($i=0; $i<$len; $i++) {
        $res .= substr($s, $i*2, 2) . ",";
    }
    return $res;
}

function saveOpenings($openings) {
    arsort($openings);
    for ($i=1; $i<=10; $i++) {
        $opening_file = fopen("opening" . $i . ".txt", "w");
        foreach ($openings as $op=>$value) {
            if (strlen($op) == $i*2) {
                fwrite($opening_file, togNormalize($op) . ": " . $value . "\n");
            }
        }
        fclose($opening_file);
    }
}

function read_sqlite() {
    $players = [];
    $openings = [];
    $db = new SQLite3('y.sqlite');
    $res = $db->query('SELECT * from games');

    while ($row = $res->fetchArray()) {
//        echo "{$row['id']} {$row['_WhiteName']} {$row['_BlackName']}\n";
        addPlayer($row['_WhiteName'], $players);
        addPlayer($row['_BlackName'], $players);

        togyzSequence($row['_Notation'], $openings);
    }

    savePlayers($players);
    saveOpenings($openings);
}

function read_sqlite_PDO() {
    $pdo = new PDO('sqlite:y.sqlite');
    $stm = $pdo->query('SELECT * from games');
    $rows = $stm->fetchAll(PDO::FETCH_NUM);
    echo "PDO" . PHP_EOL;

    foreach ($rows as $row) {
        echo "$row[0] $row[1] $row[2]\n";
    }
}

/*
select player, count(*) as gg  
from (
select _WhiteName as player from games
union all
select _BlackName from games
) g
group by player order by gg desc
*/
