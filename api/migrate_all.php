<?php
require_once '../includes/config.php';
$pdo->exec("TRUNCATE TABLE consulting_questions");
ob_start();
include 'consulting_questions_api.php';
$oldOutput = ob_get_clean();
$data = json_decode($oldOutput, true);

// insert DISC
foreach ($data['discData'] as $g => $qs) {
    foreach ($qs as $idx => $q) {
        $pdo->prepare("INSERT INTO consulting_questions (question_code, test_type, group_code, content) VALUES (?, 'DISC', ?, ?)")->execute(["D".($idx+1), $g, $q]);
    }
}
// insert MTVT
foreach ($data['mtvtDb'] as $g => $hl) {
    foreach ($hl['h'] as $idx => $q) {
        $pdo->prepare("INSERT INTO consulting_questions (question_code, test_type, group_code, content) VALUES (?, 'MTVT', ?, ?)")->execute(["H".($idx+1), $g, $q]);
    }
    foreach ($hl['l'] as $idx => $q) {
        $pdo->prepare("INSERT INTO consulting_questions (question_code, test_type, group_code, content) VALUES (?, 'MTVT', ?, ?)")->execute(["L".($idx+1), $g, $q]);
    }
}
// insert HOLLAND
foreach ($data['hollandData'] as $g => $qs) {
    foreach ($qs as $idx => $q) {
        $pdo->prepare("INSERT INTO consulting_questions (question_code, test_type, group_code, content) VALUES (?, 'HOLLAND', ?, ?)")->execute(["H".($idx+1), $g, $q]);
    }
}
// insert MI
foreach ($data['miData'] as $g => $qs) {
    foreach ($qs as $idx => $q) {
        $pdo->prepare("INSERT INTO consulting_questions (question_code, test_type, group_code, content) VALUES (?, 'MI', ?, ?)")->execute(["M".($idx+1), $g, $q]);
    }
}
echo "Migrated all to DB!";
?>
