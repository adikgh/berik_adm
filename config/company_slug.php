<?
// Компанияның ашық сілтеме атауы (company.slug): QR меню мен жеке кабинет сілтемесі ?c=yaki.
// company.code — құпия, тек касса мен админге кіру үшін; клиентке көрсетілмейді.
// Дәл осы ереже my.berik.c/config/shop.php-те (slug_of, slug_from_name) — өзгертсеңіз екеуін бірге.

function slug_col() {
	static $on = null;
	if ($on === null) $on = fun::has_col('company', 'slug');
	return $on;
}

function slug_clean($s) {
	return trim(preg_replace('/[^a-z0-9-]+/', '', strtolower((string)$s)), '-');
}

// «Яки» → yaki
function slug_from_name($name) {
	$map = ['а'=>'a','ә'=>'a','б'=>'b','в'=>'v','г'=>'g','ғ'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'i',
		'к'=>'k','қ'=>'q','л'=>'l','м'=>'m','н'=>'n','ң'=>'n','о'=>'o','ө'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u',
		'ұ'=>'u','ү'=>'u','ф'=>'f','х'=>'h','һ'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sh','ъ'=>'','ы'=>'y','і'=>'i','ь'=>'',
		'э'=>'e','ю'=>'yu','я'=>'ya'];
	$s = strtr(mb_strtolower((string)$name, 'UTF-8'), $map);
	$s = trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
	return substr($s, 0, 30);
}

// Атау бос емес пе, басқа компанияның атауы не коды емес пе. Қате мәтінін, дұрыс болса '' қайтарады.
function slug_check($slug, $cid) {
	if (!preg_match('/^[a-z0-9]([a-z0-9-]{1,28}[a-z0-9])$/', $slug)) return 'Атау 3–30 таңба: латын әріп, сан, сызықша (-)';
	if (in_array($slug, ['menu', 'admin', 'api', 'pay', 'login', 'www', 'qr', 'my'], true)) return 'Бұл атау бос емес, басқасын жазыңыз';
	$s = mysqli_real_escape_string(db::$connect, $slug);
	$cid = (int)$cid;
	if (mysqli_num_rows(db::query("select id from company where id <> '$cid' and (slug = '$s' or lower(code) = concat('#', '$s')) limit 1")))
		return 'Бұл атау бос емес, басқасын жазыңыз';
	return '';
}

// Компанияның атауы. Бос болса, компания атынан жасалып сақталады.
function slug_of($cid) {
	$cid = (int)$cid;
	$c = mysqli_fetch_assoc(db::query("select id, name, code".(slug_col() ? ", slug" : "")." from company where id = '$cid'"));
	if (!$c) return '';
	if (!slug_col()) return ltrim(strtolower((string)$c['code']), '#'); // база әлі жаңартылмаған: бұрынғыдай код
	if (!empty($c['slug'])) return (string)$c['slug'];
	$base = slug_from_name($c['name']);
	if (strlen($base) < 3) $base = 'menu'.$cid;
	for ($i = 1, $s = $base; slug_check($s, $cid) !== ''; $s = $base.'-'.++$i) if ($i > 50) { $s = 'menu'.$cid; break; }
	$e = mysqli_real_escape_string(db::$connect, $s);
	db::query("update company set slug = '$e' where id = '$cid' and (slug is null or slug = '')");
	return (string)@mysqli_fetch_row(db::query("select slug from company where id = '$cid'"))[0];
}
