<?
	// 06:00 тапсырмасы: аяқталған жұмыс күнінің толық касса есебін сақтайды (kassa/ksnap_lib.php).
	// Шақыру: Windows тапсырмасы (tools/kassa_6am_task.bat) не серверде cron:
	//   0 6 * * *  php /path/adm.berik.c/kassa/cron.php
	// Қайта шақырса зиян жоқ: жабылған күн қайта жабылмайды.
	$cli = PHP_SAPI == 'cli';
	if ($cli) { chdir(__DIR__); $_SERVER['REQUEST_URI'] = '/kassa/cron.php'; $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__); }

	include "../config/core.php"; // core.php ішінде ksnap::auto() бір рет жүреді
	include_once "ksnap_lib.php";
	$done = array_merge(isset($ksnap_done) ? $ksnap_done : [], ksnap::auto());

	if (!$cli) header('Content-Type: text/plain; charset=utf-8');
	echo date('Y-m-d H:i:s').' ok'.($done ? ' · жабылды: '.implode(', ', $done) : ' · жабатын күн жоқ')."\n";
