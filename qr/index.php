<? include "../config/core.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// QR меню: компания өзі баптайды (company_menu), сілтемелер мен QR кодтар осында
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	@include_once "../config/client_link.php";

	$has = mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_menu'")) > 0;
	$c = mysqli_fetch_assoc(db::query("select id, name, code, delivery, yourself, coffee, delivery_price from company where id = '$cmp'"));
	$m = ['is_on' => 0, 'order_on' => 1, 'delivery_on' => 1, 'pickup_on' => 1, 'table_on' => 1, 'tables' => 0, 'min_sum' => 0, 'note' => ''];
	if ($has) { $r = mysqli_fetch_assoc(db::query("select * from company_menu where company_id = '$cmp'")); if ($r) $m = array_merge($m, $r); }

	$branches = [];
	$bq = db::query("select id, name from company_branch where company_id = '$cmp' order by number asc, id asc");
	while ($b = mysqli_fetch_assoc($bq)) $branches[$b['id']] = $b['name'];

	// Мәзір ашылатын сайттар: QR меню (qr.* алиасы) және жеке кабинет
	$live = preg_match('~gprog\.kz$~i', (string)@$_SERVER['HTTP_HOST']);
	$qr_base = $live ? 'https://qrb.gprog.kz' : 'http://qr.berik.c';
	$my_base = defined('CABINET_URL') ? CABINET_URL : 'http://my.berik.c';
	$code = ltrim(strtolower((string)$c['code']), '#');

	// QR дизайны: компанияның өз файлында (assets/uploads/qr/c<id>.json), логотип сол папкада
	$st = ['color' => '#111111', 'bg' => '#ffffff', 'frame_color' => '#0EA768', 'dots' => 'round', 'eyes' => 'round', 'frame' => 1,
		'title' => (string)$c['name'], 'text' => 'Мәзір · сканерлеңіз', 'logo' => ''];
	$sf = $_SERVER['DOCUMENT_ROOT'].'/assets/uploads/qr/c'.$cmp.'.json';
	if (is_file($sf) && is_array($sj = json_decode((string)file_get_contents($sf), true))) $st = array_merge($st, array_intersect_key($sj, $st));
	$logo = ($st['logo'] && is_file($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/qr/'.basename($st['logo']))) ? '/assets/uploads/qr/'.basename($st['logo']) : '';

	// Мәзір көрінісі (company_menu.look, JSON)
	$look_col = $has && mysqli_num_rows(db::query("SHOW COLUMNS FROM company_menu LIKE 'look'")) > 0;
	$look = ['accent' => '#0ea768', 'layout' => 'list', 'photos' => 1, 'search' => 1, 'card' => 1, 'upsell' => 1, 'upsell_ids' => []];
	if (!empty($m['look']) && is_array($lj = json_decode((string)$m['look'], true))) $look = array_merge($look, array_intersect_key($lj, $look));
	$up_ids = array_map('intval', (array)$look['upsell_ids']);
	$up_cats = [];
	$uq = db::query("select p.id, coalesce(nullif(p.name_kz, ''), p.name_ru) name, p.price, k.id kid, coalesce(nullif(k.name_kz, ''), k.name_ru) kname
		from product p join product_catalog k on k.id = p.catalog_id
		where k.company_id = '$cmp' and k.type = 'main' and k.arh is null and p.arh = 0 and p.sale_online = 1
		order by k.number, k.id, p.number is null, p.number, p.name_kz");
	while ($r = mysqli_fetch_assoc($uq)) {
		if (!isset($up_cats[$r['kid']])) $up_cats[$r['kid']] = ['name' => strip_tags((string)$r['kname']), 'items' => []];
		$up_cats[$r['kid']]['items'][] = $r;
	}

	$dishes = (int)@mysqli_fetch_row(db::query("select count(*) from product p join product_catalog k on k.id = p.catalog_id
		where k.company_id = '$cmp' and k.type = 'main' and k.arh is null and p.arh = 0 and p.sale_online = 1"))[0];

	// site setting
	$menu_name = 'qr';
	$page_name = 'QR меню';
	$css = ['clients', 'qr'];
	$js = ['qr'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page qr_page" data-csrf="<?=$_SESSION['csrf']?>" data-qr="<?=htmlspecialchars($qr_base)?>" data-my="<?=htmlspecialchars($my_base)?>" data-code="<?=htmlspecialchars($code)?>" data-name="<?=htmlspecialchars($c['name'])?>">

	<? if (!$has): ?>
		<div class="cl_box cl_empty">Базада company_menu кестесі жоқ. Алдымен berik_004_menu.sql іске қосу керек.</div>
	<? else: ?>

	<div class="qr_grid">
		<form class="cl_box qr_form" autocomplete="off">
			<div class="cl_box_h"><div class="qr_h">Баптау</div></div>

			<label class="qr_sw"><input type="checkbox" name="is_on" value="1" <?=($m['is_on'] ? 'checked' : '')?>><span></span><b>Мәзір клиенттерге ашық</b></label>
			<label class="qr_sw"><input type="checkbox" name="order_on" value="1" <?=($m['order_on'] ? 'checked' : '')?>><span></span><b>Мәзірден тапсырыс қабылдау</b></label>

			<div class="qr_sub">
				<div class="cl_small">Тапсырыс түрлері</div>
				<label class="qr_sw qr_sw_s"><input type="checkbox" name="delivery_on" value="1" <?=($m['delivery_on'] ? 'checked' : '')?>><span></span><b>Жеткізу</b><? if (!$c['delivery']): ?> <i class="cl_small">(компанияда өшірулі)</i><? endif ?></label>
				<label class="qr_sw qr_sw_s"><input type="checkbox" name="pickup_on" value="1" <?=($m['pickup_on'] ? 'checked' : '')?>><span></span><b>Өзі алып кету</b><? if (!$c['yourself']): ?> <i class="cl_small">(компанияда өшірулі)</i><? endif ?></label>
				<label class="qr_sw qr_sw_s"><input type="checkbox" name="table_on" value="1" <?=($m['table_on'] ? 'checked' : '')?>><span></span><b>Үстелге (QR үстелде)</b><? if (!$c['coffee']): ?> <i class="cl_small">(компанияда «Кафе» өшірулі)</i><? endif ?></label>
			</div>

			<div class="qr_fields">
				<label><span>Үстел саны</span><input type="number" name="tables" class="cl_inp" min="0" max="300" value="<?=(int)$m['tables']?>"></label>
				<label><span>Жеткізуге ең аз сома, тг</span><input type="number" name="min_sum" class="cl_inp" min="0" step="100" value="<?=(int)$m['min_sum']?>"></label>
			</div>
			<label class="qr_note"><span>Мәзір үстіндегі хабарлама</span><textarea name="note" class="cl_inp" rows="2" maxlength="500" placeholder="Мысалы: Жұмыс уақыты 10:00–23:00"><?=htmlspecialchars((string)$m['note'])?></textarea></label>

			<div class="cl_small">Мәзірде <b><?=$dishes?></b> тағам. Тағам мен бағасы «Тағамдар» бөлімінен алынады (тек «онлайн сатылады» белгісі барлары).</div>
			<div class="qr_b"><button type="submit" class="cl_btn cl_btn_green qr_save"><i class="fal fa-check"></i> Сақтау</button></div>
		</form>

		<div class="cl_box qr_links">
			<div class="cl_box_h"><div class="qr_h">QR код дизайны</div></div>
			<div class="qr_main">
				<div class="qr_prev"><canvas class="qr_cv" data-url="<?=htmlspecialchars($qr_base.'/?c='.$code)?>"></canvas></div>
				<form class="qr_design" autocomplete="off">
					<div class="qr_row">
						<label><span>QR түсі</span><input type="color" name="color" value="<?=htmlspecialchars($st['color'])?>"></label>
						<label><span>Жақтау түсі</span><input type="color" name="frame_color" value="<?=htmlspecialchars($st['frame_color'])?>"></label>
						<label><span>Фон</span><input type="color" name="bg" value="<?=htmlspecialchars($st['bg'])?>"></label>
					</div>
					<div class="qr_opt"><span>Нүкте пішіні</span>
						<? foreach (['square' => 'Шаршы', 'round' => 'Жұмсақ', 'dot' => 'Дөңгелек'] as $k => $v): ?>
							<label><input type="radio" name="dots" value="<?=$k?>" <?=($st['dots'] == $k ? 'checked' : '')?>><i><?=$v?></i></label>
						<? endforeach ?>
					</div>
					<div class="qr_opt"><span>Бұрыштары</span>
						<? foreach (['square' => 'Шаршы', 'round' => 'Дөңгеленген', 'circle' => 'Дөңгелек'] as $k => $v): ?>
							<label><input type="radio" name="eyes" value="<?=$k?>" <?=($st['eyes'] == $k ? 'checked' : '')?>><i><?=$v?></i></label>
						<? endforeach ?>
					</div>
					<label class="qr_sw qr_sw_s"><input type="checkbox" name="frame" value="1" <?=($st['frame'] ? 'checked' : '')?>><span></span><b>Жақтау мен жазу</b></label>
					<div class="qr_row qr_frame_f">
						<label><span>Үстіңгі жазу</span><input type="text" name="title" class="cl_inp" maxlength="40" value="<?=htmlspecialchars($st['title'])?>"></label>
						<label><span>Астыңғы жазу</span><input type="text" name="text" class="cl_inp" maxlength="40" value="<?=htmlspecialchars($st['text'])?>"></label>
					</div>
					<div class="qr_logo_r">
						<span class="cl_small">Ортадағы логотип</span>
						<label class="cl_btn cl_btn_light qr_logo_up"><i class="fal fa-image"></i> <?=($logo ? 'Ауыстыру' : 'Жүктеу')?><input type="file" accept="image/png,image/jpeg,image/webp" hidden></label>
						<button type="button" class="cl_btn cl_btn_light qr_logo_del <?=($logo ? '' : 'dsp_n')?>"><i class="fal fa-trash-alt"></i></button>
						<input type="hidden" name="logo" value="<?=htmlspecialchars($logo)?>">
					</div>
					<div class="qr_btns">
						<button type="submit" class="cl_btn cl_btn_green qr_dsave"><i class="fal fa-check"></i> Дизайнды сақтау</button>
						<button type="button" class="cl_btn cl_btn_light qr_png"><i class="fal fa-download"></i> PNG</button>
					</div>
				</form>
			</div>
			<div class="qr_urls">
				<div><div class="cl_small">QR меню (кірусіз ашылады)</div><a class="qr_url" href="<?=htmlspecialchars($qr_base.'/?c='.$code)?>" target="_blank"><?=htmlspecialchars($qr_base.'/?c='.$code)?></a></div>
				<div><div class="cl_small">Жеке кабинетте</div><a class="qr_url" href="<?=htmlspecialchars($my_base.'/?c='.$code)?>" target="_blank"><?=htmlspecialchars($my_base.'/?c='.$code)?></a></div>
				<button type="button" class="cl_btn cl_btn_light qr_copy" data-url="<?=htmlspecialchars($qr_base.'/?c='.$code)?>"><i class="fal fa-copy"></i> Көшіру</button>
			</div>
		</div>
	</div>

	<form class="cl_box qr_look" autocomplete="off">
		<div class="cl_box_h"><div class="qr_h">Мәзір көрінісі</div><a class="cl_btn cl_btn_light" href="<?=htmlspecialchars($qr_base.'/?c='.$code)?>" target="_blank"><i class="fal fa-external-link"></i> Мәзірді ашу</a></div>
		<? if (!$look_col): ?>
			<div class="cl_small">Бұл баптау үшін базаға <b>berik_004_menu_look.sql</b> іске қосу керек.</div>
		<? else: ?>
		<div class="qr_look_g">
			<div class="qr_look_c">
				<div class="qr_row qr_row2">
					<label><span>Негізгі түс (батырмалар)</span><input type="color" name="accent" value="<?=htmlspecialchars($look['accent'])?>"></label>
					<div class="qr_opt"><span>Тағамдар</span>
						<? foreach (['list' => 'Тізім', 'grid' => 'Тор (2 бағана)'] as $k => $v): ?>
							<label><input type="radio" name="layout" value="<?=$k?>" <?=($look['layout'] == $k ? 'checked' : '')?>><i><?=$v?></i></label>
						<? endforeach ?>
					</div>
				</div>
				<label class="qr_sw qr_sw_s"><input type="checkbox" name="photos" value="1" <?=($look['photos'] ? 'checked' : '')?>><span></span><b>Тағам фотолары</b></label>
				<label class="qr_sw qr_sw_s"><input type="checkbox" name="search" value="1" <?=($look['search'] ? 'checked' : '')?>><span></span><b>Мәзірден іздеу</b></label>
				<label class="qr_sw qr_sw_s"><input type="checkbox" name="card" value="1" <?=($look['card'] ? 'checked' : '')?>><span></span><b>Тағамды басқанда карточкасы ашылады</b> <i class="cl_small">(өшірулі болса, бірден себетке қосылады)</i></label>
				<label class="qr_sw qr_sw_s"><input type="checkbox" name="upsell" value="1" <?=($look['upsell'] ? 'checked' : '')?>><span></span><b>«Тағы қосасыз ба?» ұсынысы</b></label>
			</div>
			<div class="qr_up">
				<div class="cl_small">Ұсынылатын тағамдар (12-ге дейін). Ешнәрсе белгіленбесе, басқа санаттан арзан тағамдар өзі таңдалады.</div>
				<div class="qr_up_l">
					<? foreach ($up_cats as $k): ?>
						<div class="qr_up_k"><?=htmlspecialchars($k['name'])?></div>
						<? foreach ($k['items'] as $i): ?>
							<label><input type="checkbox" name="upsell_ids[]" value="<?=$i['id']?>" <?=(in_array((int)$i['id'], $up_ids, true) ? 'checked' : '')?>><span><?=htmlspecialchars(trim(html_entity_decode(strip_tags((string)$i['name']), ENT_QUOTES, 'UTF-8')))?></span><i><?=number_format((int)preg_replace('/\D/', '', (string)$i['price']), 0, '', ' ')?> тг</i></label>
						<? endforeach ?>
					<? endforeach ?>
				</div>
				<div class="cl_small">Белгіленді: <b class="qr_up_n"><?=count($up_ids)?></b></div>
			</div>
		</div>
		<div class="qr_b"><button type="submit" class="cl_btn cl_btn_green qr_lsave"><i class="fal fa-check"></i> Сақтау</button></div>
		<? endif ?>
	</form>

	<div class="cl_box qr_tables">
		<div class="cl_box_h">
			<div class="qr_h">Үстелдердің QR кодтары</div>
			<div class="qr_tb">
				<? if (count($branches) >= 2): ?>
					<select class="cl_inp qr_branch">
						<? foreach ($branches as $bid => $bn): ?><option value="<?=$bid?>"><?=htmlspecialchars($bn)?></option><? endforeach ?>
					</select>
				<? endif ?>
				<button type="button" class="cl_btn cl_btn_green qr_print"><i class="fal fa-print"></i> Басып шығару</button>
			</div>
		</div>
		<div class="cl_small qr_tables_e">Үстел санын жазып, сақтаңыз. Әр үстелдің QR коды осы жерде шығады: клиент сканерлесе, тапсырыс сол үстелге түседі.</div>
		<div class="qr_list" data-tables="<?=(int)$m['tables']?>"></div>
	</div>

	<? endif ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<? include "../block/footer.php"; ?>
