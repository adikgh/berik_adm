<? include "../config/core.php";
	include "../config/cfile.php";

	// Файлды ашу: /files/?id=N (тек өз компанияның файлы). Drive-тан ағынмен беріледі, хостингте сақталмайды.
	if (!$user_id) { http_response_code(403); exit('Кіру рұқсаты жоқ'); }
	$f = cfile::get((int)@$_GET['id'], (int)$company);
	if (!$f) { http_response_code(404); exit('Файл табылмады'); }
	if (isset($_GET['drive']) && $f['drive_id']) { header('Location: https://drive.google.com/file/d/'.rawurlencode($f['drive_id']).'/view'); exit; }
	cfile::output($f, isset($_GET['dl']));
