<?php
$dbFile = __DIR__ . '/university.db';

try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка базы данных: " . htmlspecialchars($e->getMessage()));
}

$groupsStmt = $db->query(
    "SELECT id, number FROM Groups WHERE graduation_year >= strftime('%Y', 'now') ORDER BY number"
);
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedGroupId = $_GET['group_id'] ?? null;

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
if ($selectedGroupId !== null && $selectedGroupId !== '') {
    $query .= " AND g.id = ?";
    $params[] = (int)$selectedGroupId;
}

$query .= " ORDER BY g.number, s.full_name";

$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список студентов</title>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .controls { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; }
        button { cursor: pointer; padding: 5px 10px; }
        select { padding: 5px; }
    </style>
</head>
<body>

    <h1>Список действующих студентов</h1>

    <div class="controls">
        <form action="" method="GET">
            <label for="group_id">Фильтр по группе:</label>
            <select name="group_id" id="group_id">
                <option value="">-- Все группы --</option>
                <?php foreach ($groups as $grp): ?>
                    <option value="<?= htmlspecialchars($grp['id']) ?>" 
                        <?= (string)$selectedGroupId === (string)$grp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($grp['number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Показать</button>
            <?php if ($selectedGroupId): ?>
                <a href="index.php" style="margin-left: 10px;">Сбросить</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($students)): ?>
        <p>Студенты не найдены.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Группа</th>
                    <th>Направление</th>
                    <th>ФИО</th>
                    <th>Пол</th>
                    <th>Дата рождения</th>
                    <th>№ Зачетки</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['group_number']) ?></td>
                        <td><?= htmlspecialchars($student['major']) ?></td>
                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                        <td><?= htmlspecialchars($student['gender']) ?></td>
                        <td><?= htmlspecialchars($student['birth_date']) ?></td>
                        <td><?= htmlspecialchars($student['student_ticket_number']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>
