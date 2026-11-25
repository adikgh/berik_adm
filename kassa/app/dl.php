<?php
// Кассаның Windows программасын жүктеу: әрқашан соңғы release-тегі BerikPOS-Setup-x.y.z.exe (config/poswin.php).
// Сілтеме тұрақты (/kassa/app/dl.php), жаңа нұсқа шыққанда өзі ауысады.
require_once __DIR__.'/../../config/poswin.php';
header('Cache-Control: no-store');
header('Location: '.poswin::download_url(), true, 302);
exit;
