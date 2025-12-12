<?php

$dbFile = __DIR__ . '/university.db';

try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Ошибка подключения к БД: " . $e->getMessage() . "\n";
    exit(1);
}


$groupsQuery = "SELECT id, number FROM Groups WHERE graduation_year >= strftime('%Y', 'now') ORDER BY number";
$groups = $db->query($groupsQuery)->fetchAll(PDO::FETCH_ASSOC);

if (empty($groups)) {
    echo "Активных групп не найдено.\n";
    exit(0);
}

echo "\nДоступные группы (Год выпуска >= " . date('Y') . "):\n";
foreach ($groups as $grp) {
    echo sprintf(" %d: Группа %s\n", $grp['id'], $grp['number']);
}

echo "\nВведите ID группы (или нажмите Enter, чтобы вывести всех): ";
$input = trim(fgets(STDIN));

$groupId = null;
if ($input !== '') {
    if (!ctype_digit($input)) {
        echo "Ошибка: Введите число.\n";
        exit(1);
    }
    
    $groupId = (int)$input;
    
    $exists = $db->prepare("SELECT COUNT(*) FROM Groups WHERE id = ?");
    $exists->execute([$groupId]);
    if ($exists->fetchColumn() == 0) {
        echo "Группа с ID $groupId не найдена.\n";
        exit(1);
    }
}


$query = "
    SELECT 
        g.number as group_number,
        g.major,
        s.full_name,
        s.gender,
        s.birth_date,
        s.student_ticket_number
    FROM Students s
    JOIN Groups g ON s.group_id = g.id
    WHERE g.graduation_year >= strftime('%Y', 'now')
";

$params = [];
if ($groupId !== null) {
    $query .= " AND g.id = ?";
    $params[] = $groupId;
}

$query .= " ORDER BY g.number, s.full_name";

$stmt = $db->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

displayTable($results);


function displayTable($data) {
    if (empty($data)) {
        echo "\nСтуденты не найдены.\n";
        return;
    }

    $colWidths = [8, 10, 35, 10, 15, 15];
    
    echo "\n";
    printRow(['Группа', 'Напр.', 'ФИО', 'Пол', 'Дата рожд.', 'Зачетка'], $colWidths);
    printSeparator($colWidths);
    
    foreach ($data as $row) {
        printRow([
            $row['group_number'],
            $row['major'],
            $row['full_name'], 
            $row['gender'],
            $row['birth_date'],
            $row['student_ticket_number']
        ], $colWidths);
    }
    
    printSeparator($colWidths);
    echo "\n";
}

function mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = NULL)
{
    $encoding = $encoding === NULL ? mb_internal_encoding() : $encoding;
    $padBefore = $dir === STR_PAD_BOTH || $dir === STR_PAD_LEFT;
    $padAfter = $dir === STR_PAD_BOTH || $dir === STR_PAD_RIGHT;
    $pad_len -= mb_strlen($str, $encoding);
    $target_len = $padBefore && $padAfter ? $pad_len / 2 : $pad_len;
    $str_to_repeat_len = mb_strlen($pad_str, $encoding);
    $repeat_times = ceil($target_len / $str_to_repeat_len);
    $repeated_string = str_repeat($pad_str, max(0, $repeat_times));
    $before = $padBefore ? mb_substr($repeated_string, 0, floor($target_len), $encoding) : '';
    $after = $padAfter ? mb_substr($repeated_string, 0, ceil($target_len), $encoding) : '';
    return $before . $str . $after;
}

function printRow($cols, $widths) {
    echo "| ";
    for ($i = 0; $i < count($cols); $i++) {
        echo mb_str_pad($cols[$i], $widths[$i] - 1) . " | ";
    }
    echo "\n";
}

function printSeparator($widths) {
    echo "|-";
    foreach ($widths as $w) {
        echo str_repeat("-", $w) . "-|";
    }
    echo "\n";
}
