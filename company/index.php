<? include "../config/core.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Компания баптаулары: company және company_branch кестелеріндегі баптаулар бөлім-бөлім.
	// Әр бөлімнің өз «Сақтау» батырмасы. Негізгі (атауы, қала, код) тек көрінеді. Тек базада бар бағандар көрінеді (live мен локал әртүрлі болса да істейді).
	// Әзірлеуші баптаулары мұнда жоқ: code, number, ya_city, тариф/мерзім (start_dt, end_dt), филиалдың offline белгісі.
	// Кэшбэк, сыйлық, чек, QR, акциялар — өз беттерінде (төмендегі «Басқа баптаулар» сілтемелері).
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	mysqli_report(MYSQLI_REPORT_OFF);

	// Аудармалар: бірінші ашылғанда site_word-қа 5 тілде жазылады (бар сөз өзгермейді)
	$cs_ver = 'cs:_v4';
	$W = include __DIR__.'/words.php';
	$wl = ['kz', 'en', 'uz', 'ky', 'ru'];
	if (mysqli_num_rows(db::query("SHOW TABLES LIKE 'site_word'")) && !mysqli_num_rows(db::query("select id from site_word where name = '$cs_ver' and lang = 'kz'"))) {
		$vals = [];
		foreach ($W as $k => $tr) foreach ($wl as $i => $lg) if (isset($tr[$i]) && $tr[$i] !== '')
			$vals[] = "('".mysqli_real_escape_string(db::$connect, $k)."', '$lg', '".mysqli_real_escape_string(db::$connect, $tr[$i])."')";
		$vals[] = "('$cs_ver', 'kz', '1')";
		foreach (array_chunk($vals, 100) as $ch) db::query("INSERT IGNORE INTO site_word (`name`, `lang`, `txt`) VALUES ".implode(', ', $ch));
		t::$words = null; // жаңа сөздерді қайта оқысын
	}
	$w = function ($k) use ($W) { return t::w($k, isset($W[$k]) ? $W[$k][0] : $k); };
	$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };

	// Базада бар бағандар
	$cols = function ($tb) { $r = []; $q = db::query("SHOW COLUMNS FROM `$tb`"); while ($q && $x = mysqli_fetch_assoc($q)) $r[$x['Field']] = 1; return $r; };
	$cc = $cols('company');
	$bc = $cols('company_branch');
	$has = function ($c) use ($cc) { return isset($cc[$c]); };
	$bhas = function ($c) use ($bc) { return isset($bc[$c]); };

	$types = [1 => ['delivery', 'cs:Delivery', 'fa-car'], 2 => ['yourself', 'cs:Takeaway', 'fa-walking'], 4 => ['coffee', 'cs:Cafe', 'fa-utensils'], 3 => ['service', 'cs:Service', 'fa-backpack']];
	$pays = ['transfer' => 'cs:Transfer', 'qr' => 'Kaspi QR', 'cash' => 'cs:Cash', 'mix' => 'cs:Mixed', 'unpaid' => 'cs:Unpaid'];
	$reds = [0, 5, 10];

	$msg = ''; $err = '';
	$sw = function ($n) { return !empty($_POST[$n]) ? 1 : 0; };
	$e = function ($v) { return mysqli_real_escape_string(db::$connect, (string)$v); };

	if ($cmp && $_SERVER['REQUEST_METHOD'] == 'POST') {
		$sec = preg_replace('/[^a-z]/', '', (string)@$_POST['sec']);
		if (!hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) $err = $w('cs:Reload');
		else {
			$set = [];
			// Компания атауы, қаласы, коды тек әзірлеуші (dev) панелінде өзгереді, мұнда сақталмайды
			if ($sec == 'types') {
				$on = [];
				foreach ($types as $tid => $t) if ($has($t[0])) { $v = $sw($t[0]); $set[] = "`$t[0]` = '$v'"; if ($v) $on[] = $tid; }
				if (!$on) { $err = $w('cs:At least one type'); $set = []; }
				elseif ($has('type_def')) { $td = (int)@$_POST['type_def']; if (!in_array($td, $on)) $td = $on[0]; $set[] = "type_def = '$td'"; }
			} elseif ($sec == 'delivery') {
				if ($has('delivery_price')) $set[] = "delivery_price = '".max(0, min(1000000, (int)preg_replace('/\D+/', '', (string)@$_POST['delivery_price'])))."'";
				if ($has('kv')) $set[] = "kv = '".$sw('kv')."'";
			} elseif ($sec == 'pos') {
				if ($has('print')) $set[] = "`print` = '".$sw('print')."'";
				if ($has('red')) { $r = (int)@$_POST['red']; $set[] = "red = '".(in_array($r, $reds) ? $r : 0)."'"; }
				if ($has('show_branch')) $set[] = "show_branch = '".$sw('show_branch')."'";
				if ($has('multi_cart')) $set[] = "multi_cart = '".$sw('multi_cart')."'";
				if ($has('multi_cart_max') && isset($_POST['multi_cart_max'])) $set[] = "multi_cart_max = '".max(2, min(15, (int)$_POST['multi_cart_max']))."'";
			// Telegram (chat_send, chat_id) — тек dev өзгертеді, мұнда сақталмайды
			} elseif ($sec == 'xpay') {
				// Kaspi онлайн төлем: тек төлем түрлері мен предоплата сомасы (кілт, касса аты — dev панелінде)
				if (mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_xpay'")) && mysqli_num_rows(db::query("select company_id from company_xpay where company_id = '$cmp'"))) {
					$pre = max(0, min(1000000, (int)preg_replace('/\D+/', '', (string)@$_POST['prepay_sum'])));
					$ap = $sw('allow_prepay'); $af = $sw('allow_full'); $al = $sw('allow_later');
					if ($ap && !$pre) $err = t::w('xp:Need prepay', 'Предоплата сомасын жазыңыз.');
					elseif (!$ap && !$af && !$al) $err = t::w('xp:Need option', 'Кемінде бір төлем түрін қосыңыз.');
					else db::query("update company_xpay set prepay_sum = '$pre', allow_prepay = '$ap', allow_full = '$af', allow_later = '$al', upd_dt = now() where company_id = '$cmp'");
				}
			} elseif ($sec == 'branch') {
				$bid = (int)@$_POST['id'];
				$own = $bid ? mysqli_num_rows(db::query("select id from company_branch where id = '$bid' and company_id = '$cmp'")) : 0;
				$bset = [];
				if ($own) {
					$bn = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)@$_POST['name']))), 0, 100);
					if ($bn !== '') $bset[] = "name = '".$e($bn)."'";
					if ($bhas('address')) $bset[] = "address = '".$e(mb_substr(trim(strip_tags((string)@$_POST['address'])), 0, 255))."'";
					if ($bhas('pay_types')) {
						$pt = array_values(array_intersect(array_keys($pays), (array)@$_POST['pay_types']));
						if (!$pt) $err = $w('cs:Pay one');
						else {
							$bset[] = "pay_types = ".(count($pt) == count($pays) ? 'NULL' : "'".implode(',', $pt)."'"); // бәрі белгіленсе — бос (барлығы)
							if ($bhas('pay_default')) { $pd = (string)@$_POST['pay_default']; $bset[] = "pay_default = ".(in_array($pd, $pt, true) ? "'$pd'" : 'NULL'); }
						}
					}
					if ($bhas('print_width')) { $pw = (int)@$_POST['print_width']; $bset[] = "print_width = ".(in_array($pw, [58, 80]) ? "'$pw'" : 'NULL'); }
					if ($bhas('print_title')) { $ptl = mb_substr(trim(strip_tags((string)@$_POST['print_title'])), 0, 100); $bset[] = "print_title = ".($ptl === '' ? 'NULL' : "'".$e($ptl)."'"); }
					if (!$err && $bset) db::query("update company_branch set ".implode(', ', $bset)." where id = '$bid' and company_id = '$cmp'");
				}
				if (!$err) { header('location: /company/?saved=branch'.$bid.'#br'.$bid); exit; }
			}
			if (!$err && $set) db::query("update company set ".implode(', ', $set)." where id = '$cmp'");
			if (!$err) { header('location: /company/?saved='.$sec.'#'.$sec); exit; }
		}
	}
	if (@$_GET['saved']) $msg = $w('cs:Saved');
	if ($err) $msg = $err;

	$c = $cmp ? mysqli_fetch_assoc(db::query("select * from company where id = '$cmp'")) : null;
	$xp = ($cmp && mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_xpay'"))) ? mysqli_fetch_assoc(db::query("select * from company_xpay where company_id = '$cmp'")) : null;
	$xp_on = $xp && (int)$xp['is_on'] && $xp['api_key'] !== '';
	$branches = [];
	if ($c) { $q = db::query("select * from company_branch where company_id = '$cmp' order by number is null, number, id"); while ($q && $r = mysqli_fetch_assoc($q)) $branches[] = $r; }

	// Қате болса — жазылғанын қайта көрсетеміз (бет тазаланбасын)
	if ($err && $c && @$sec != 'branch') foreach ($_POST as $k => $v) if (isset($cc[$k]) && is_scalar($v)) $c[$k] = $v;

	// Ауыстырғыш (lc_sw стилі loyalty.css-тен)
	$swh = function ($name, $on, $title, $note = '', $tg = false) use ($w) {
		return '<label class="cmp_opt">'
			.'<span class="cmp_opt_t"><b>'.$title.'</b>'.($note !== '' ? '<small>'.$note.'</small>' : '').'</span>'
			.'<span class="lc_sw"><input type="hidden" name="'.$name.'" value="0"><input type="checkbox" name="'.$name.'" value="1"'.($on ? ' checked' : '').($tg ? ' data-tg="'.$tg.'"' : '').'><i></i>'
			.'<span class="lc_sw_t">'.($on ? $w('cs:On') : $w('cs:Off')).'</span></span>'
			.'</label>';
	};
	$form = function ($sec) use ($h) {
		return '<form class="cl_box lc_set cmp_box" id="'.$sec.'" method="post" autocomplete="off" data-ajx=".cmp_page" data-ajx-msg=".cmp_msg">'
			.'<input type="hidden" name="csrf" value="'.$h($_SESSION['csrf']).'"><input type="hidden" name="sec" value="'.$sec.'">';
	};
	$save = '<div class="lc_save"><button type="submit" class="cl_btn cl_btn_green"><i class="fal fa-check"></i> '.$w('cs:Save').'</button></div>';

	// site setting
	$menu_name = 'company';
	$page_name = $W['Company settings'][0];
	$page_key = 'Company settings';
	$css = ['clients', 'loyalty', 'company'];
	$js = ['company'];
?>
<? include "../block/header.php"; ?>

<style>
.cmp_cats{position:sticky;top:0;z-index:5;display:flex;gap:4px;overflow-x:auto;margin:0 0 14px;padding:6px;background:#fff;border:1px solid #e6e8e4;border-radius:16px;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.cmp_cats::-webkit-scrollbar{display:none}
.cmp_cats a{flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;height:44px;padding:0 16px;border-radius:11px;color:#545a56;font-size:15px;font-weight:600;white-space:nowrap;border:0;outline:none;text-decoration:none;transition:background-color .2s cubic-bezier(.22,1,.36,1),color .2s cubic-bezier(.22,1,.36,1)}
.cmp_cats a i{font-size:16px;color:#767c78;transition:color .2s}
.cmp_cats a:hover{background:#f1f3f0;color:#1c1f1d}
.cmp_cats a.on{background:#e7f5ee;color:#08794b}
.cmp_cats a.on i{color:#0c8f59}
.cmp_cats a:focus-visible{box-shadow:0 0 0 2px #0c8f59 inset}
@media (max-width:760px){.cmp_cats{margin:0 -16px 12px;border-radius:0;border-left:0;border-right:0;padding:6px 12px}.cmp_cats a{height:40px;padding:0 12px;font-size:14px}}
.cmp_page .cmp_h2{scroll-margin-top:64px}
.cmp_tab>.cmp_h2{display:none}
.cmp_tab[hidden]{display:none}
.cmp_tab{padding-top:8px}
</style>
<div class="bl_c cl_page lc_page cmp_page">

	<? if (!$c): ?>
		<div class="cl_box"><div class="cl_empty"><?=$w('cs:No company')?></div></div>
	<? else: ?>

	<? if ($msg): ?><div class="lc_msg cmp_msg<?=($err ? ' cmp_msg_err' : '')?>"><?=$h($msg)?></div><? endif ?>

	<nav class="cmp_cats"><a href="#c_main"><i class="fal fa-building"></i><?=t::w('xp:cat Main', 'Негізгі')?></a><a href="#c_pos"><i class="fal fa-cash-register"></i><?=$w('cs:Cashbox')?></a><a href="#c_pay"><i class="fal fa-credit-card"></i><?=t::w('xp:cat Pay', 'Онлайн төлем')?></a><a href="#c_dlv"><i class="fal fa-car"></i><?=$w('cs:Delivery')?></a><a href="#c_tg"><i class="fal fa-bell"></i><?=t::w('xp:cat Notify', 'Хабарламалар')?></a><a href="#branches"><i class="fal fa-store"></i><?=$w('cs:Branches')?></a><a href="#c_other"><i class="fal fa-sliders-h"></i><?=$w('cs:Other settings')?></a></nav>

	<section class="cmp_tab" data-tab="c_main">
	<div class="cmp_h2" id="c_main"><i class="fal fa-building"></i> <?=t::w('xp:cat Main', 'Негізгі')?></div>
	<div class="cmp_grid">

		<? // 1. Негізгі ?>
		<div class="cl_box lc_set cmp_box" id="main">
			<div class="cl_box_h"><div><i class="fal fa-building"></i> <?=$w('cs:General')?></div></div>
			<div class="lc_body">
				<div class="cmp_info">
					<div><span><?=$w('cs:Company name')?></span><b><?=$h($c['name'] ?: '—')?></b></div>
					<? if ($has('city')): ?><div><span><?=$w('cs:City')?></span><b><?=$h(mb_convert_case((string)$c['city'], MB_CASE_TITLE, 'UTF-8') ?: '—')?></b></div><? endif ?>
					<? if ($has('code')): ?><div><span><?=$w('cs:Code')?></span><b><?=$h(ltrim((string)$c['code'], '#') ?: '—')?></b></div><? endif ?>
				</div>
				<div class="cl_small"><?=$w('cs:info_note')?></div>
			</div>
		</div>

		<? // 2. Тапсырыс түрлері ?>
		<? $t_has = false; foreach ($types as $t) if ($has($t[0])) $t_has = true; ?>
		<? if ($t_has): ?>
		<?=$form('types')?>
			<div class="cl_box_h"><div><i class="fal fa-list-ul"></i> <?=$w('cs:Order types')?></div></div>
			<div class="lc_body cmp_types">
				<? foreach ($types as $tid => $t): if (!$has($t[0])) continue; ?>
					<?=$swh($t[0], (int)$c[$t[0]], '<i class="far '.$t[2].' cmp_ti"></i>'.$w($t[1]), '', 't'.$tid)?>
				<? endforeach ?>
				<? if ($has('type_def')): // кассадағыдай: стандарт түр өшік болса — бірінші қосулысы
					$tdc = (int)$c['type_def']; $t_on = []; foreach ($types as $tid => $t) if ($has($t[0]) && (int)$c[$t[0]]) $t_on[] = $tid;
					if (!in_array($tdc, $t_on) && $t_on) $tdc = $t_on[0]; ?>
				<div class="lc_row">
					<label class="cl_lbl"><?=$w('cs:Default type')?></label>
					<select name="type_def" class="cl_inp cmp_inp cmp_tdef">
						<? foreach ($types as $tid => $t): if (!$has($t[0])) continue; ?>
							<option value="<?=$tid?>"<?=($tdc == $tid ? ' selected' : '')?><?=(!(int)$c[$t[0]] ? ' disabled' : '')?>><?=$w($t[1])?></option>
						<? endforeach ?>
					</select>
					<div class="cl_small"><?=$w('cs:types_note')?></div>
				</div>
				<? endif ?>
			</div>
			<?=$save?>
		</form>
		<? endif ?>

	</div>

	</section>

	<section class="cmp_tab" data-tab="c_pos">
	<div class="cmp_h2" id="c_pos"><i class="fal fa-cash-register"></i> <?=$w('cs:Cashbox')?></div>
	<div class="cmp_grid">
		<? // 3. Касса ?>
		<?=$form('pos')?>
			<div class="cl_box_h"><div><i class="fal fa-cash-register"></i> <?=$w('cs:Cashbox')?></div></div>
			<div class="lc_body">
				<? if ($has('print')): ?><?=$swh('print', (int)$c['print'] == 1, $w('cs:Auto print'), $w('cs:auto_print_note'))?><? endif ?>
				<? if ($has('show_branch')): ?><?=$swh('show_branch', (int)$c['show_branch'], $w('cs:Show branch'), $w('cs:show_branch_note'))?><? endif ?>
				<? if ($has('red')): $rv = (int)$c['red']; if (!in_array($rv, $reds)) $reds[] = $rv; sort($reds); ?>
				<div class="lc_row">
					<label class="cl_lbl"><?=$w('cs:RED')?></label>
					<div class="lc_chips cmp_chips">
						<input type="hidden" name="red" value="<?=$rv?>">
						<? foreach ($reds as $v): ?><button type="button" class="lc_chip<?=($rv == $v ? ' lc_chip_on' : '')?>" data-v="<?=$v?>"><?=($v ? $v.'%' : $w('cs:Hidden'))?></button><? endforeach ?>
					</div>
					<div class="cl_small"><?=$w('cs:red_note')?></div>
				</div>
				<? endif ?>
				<? if ($has('multi_cart')): $mc = (int)$c['multi_cart']; ?>
				<div class="lc_tg cmp_sub<?=($mc ? '' : ' lc_off')?>">
					<?=$swh('multi_cart', $mc, $w('cs:Multi cart'), $w('cs:multi_cart_note'))?>
					<? if ($has('multi_cart_max')): ?>
					<div class="lc_body cmp_sub_b">
						<div class="lc_row">
							<label class="cl_lbl"><?=$w('cs:Max carts')?></label>
							<div class="lc_num"><input type="number" name="multi_cart_max" class="cl_inp" min="2" max="15" value="<?=max(2, min(15, (int)$c['multi_cart_max'] ?: 15))?>"></div>
						</div>
					</div>
					<? endif ?>
				</div>
				<? endif ?>
			</div>
			<?=$save?>
		</form>

	</div>

	</section>

	<section class="cmp_tab" data-tab="c_pay">
	<div class="cmp_h2" id="c_pay"><i class="fal fa-credit-card"></i> <?=t::w('xp:cat Pay', 'Онлайн төлем')?></div>
	<div class="cmp_grid">
		<? if ($xp_on): ?>
		<?=$form('xpay')?>
			<div class="cl_box_h"><div><i class="fal fa-credit-card"></i> Kaspi</div><a class="cmp_lnk" href="/kaspi/"><?=t::w('xp:Payments', 'Онлайн төлемдер')?> <i class="far fa-angle-right"></i></a></div>
			<div class="lc_body">
				<?=$swh('allow_prepay', (int)$xp['allow_prepay'], t::w('xp:Prepay', 'Предоплата'), t::w('xp:Prepay note', 'Клиент төмендегі соманы төлейді, қалғанын алғанда. Тек жеткізу мен өзі алып кетуге.'))?>
				<div class="lc_row">
					<label class="cl_lbl"><?=t::w('xp:Prepay sum', 'Предоплата сомасы')?></label>
					<div class="lc_num lc_num_sum"><input type="number" name="prepay_sum" class="cl_inp" min="0" step="100" value="<?=(int)$xp['prepay_sum']?>"><span>тг</span></div>
				</div>
				<?=$swh('allow_full', (int)$xp['allow_full'], t::w('xp:Full', 'Толық төлеу'), t::w('xp:Full note', 'Клиент бүкіл соманы бірден төлейді.'))?>
				<?=$swh('allow_later', (int)$xp['allow_later'], t::w('xp:Later', 'Алғанда төлеу (Kaspi-сіз)'), t::w('xp:Later note', 'Өшік болса, тапсырыс төлемсіз берілмейді.'))?>
			</div>
			<?=$save?>
		</form>
		<? else: ?>
		<div class="cl_box lc_set cmp_box">
			<div class="cl_box_h"><div><i class="fal fa-credit-card"></i> Kaspi</div></div>
			<div class="lc_body"><div class="cl_small"><?=t::w('xp:Not connected', 'Мәзірден Kaspi арқылы онлайн төлем әлі қосылмаған. Қосу үшін бізге жазыңыз.')?></div></div>
		</div>
		<? endif ?>
	</div>

	</section>

	<section class="cmp_tab" data-tab="c_dlv">
	<div class="cmp_h2" id="c_dlv"><i class="fal fa-car"></i> <?=$w('cs:Delivery')?></div>
	<div class="cmp_grid">
		<? // 4. Жеткізу ?>
		<? if ($has('delivery_price') || $has('kv')): ?>
		<?=$form('delivery')?>
			<div class="cl_box_h"><div><i class="fal fa-car"></i> <?=$w('cs:Delivery')?></div><a class="cmp_lnk" href="/delivery/"><?=$w('cs:Delivery zones')?> <i class="far fa-angle-right"></i></a></div>
			<div class="lc_body">
				<? if ($has('kv')): ?><?=$swh('kv', (int)$c['kv'], $w('cs:Address details'), $w('cs:kv_note'))?><? endif ?>
				<? if ($has('delivery_price')): ?>
				<div class="lc_row">
					<label class="cl_lbl"><?=$w('cs:Delivery price')?></label>
					<div class="lc_num lc_num_sum"><input type="number" name="delivery_price" class="cl_inp" min="0" step="50" value="<?=(int)$c['delivery_price']?>"><span>тг</span></div>
					<div class="cl_small"><?=$w('cs:delivery_price_note')?></div>
				</div>
				<? endif ?>
			</div>
			<?=$save?>
		</form>
		<? endif ?>

	</div>

	</section>

	<section class="cmp_tab" data-tab="c_tg">
	<div class="cmp_h2" id="c_tg"><i class="fal fa-bell"></i> <?=t::w('xp:cat Notify', 'Хабарламалар')?></div>
	<div class="cmp_grid">
		<? // 5. Telegram ?>
		<? if ($has('chat_send') || $has('chat_id')): ?>
		<div class="cl_box lc_set cmp_box" id="tg">
			<div class="cl_box_h"><div><i class="fab fa-telegram-plane"></i> <?=$w('cs:Telegram')?></div></div>
			<div class="lc_body">
				<div class="cmp_info">
					<? if ($has('chat_send')): $tgo = (int)$c['chat_send'] == 1 && trim((string)@$c['chat_id']) !== ''; ?><div><span><?=$w('cs:Send orders')?></span><b class="<?=($tgo ? 'cl_c_green' : '')?>"><?=($tgo ? $w('cs:On') : $w('cs:Off'))?></b></div><? endif ?>
					<? if ($has('chat_id')): ?><div><span><?=$w('cs:Chat ID')?></span><b><?=$h(trim((string)$c['chat_id']) ?: '—')?></b></div><? endif ?>
				</div>
				<div class="cl_small"><?=$w('cs:tg_note')?></div>
			</div>
		</div>
		<? endif ?>

	</div>

	<? // 6. Точкалар ?>
	</section>

	<section class="cmp_tab" data-tab="branches">
	<div class="cmp_h2" id="branches"><i class="fal fa-store"></i> <?=$w('cs:Branches')?></div>
	<? if (!$branches): ?><div class="cl_box"><div class="cl_empty"><?=$w('cs:No branches')?></div></div><? endif ?>
	<div class="cmp_grid">
	<? foreach ($branches as $b): $bid = (int)$b['id'];
		$bpt = trim((string)@$b['pay_types']); $bpt = $bpt === '' ? array_keys($pays) : array_map('trim', explode(',', $bpt)); ?>
		<form class="cl_box lc_set cmp_box cmp_br" id="br<?=$bid?>" method="post" autocomplete="off" data-ajx=".cmp_page" data-ajx-msg=".cmp_msg">
			<input type="hidden" name="csrf" value="<?=$h($_SESSION['csrf'])?>"><input type="hidden" name="sec" value="branch"><input type="hidden" name="id" value="<?=$bid?>">
			<div class="cl_box_h"><div><i class="fal fa-map-marker-alt"></i> <?=$h($b['name'])?></div></div>
			<div class="lc_body">
				<div class="cmp_2">
					<div class="lc_row"><label class="cl_lbl"><?=$w('cs:Branch name')?></label><input type="text" name="name" class="cl_inp cmp_inp" maxlength="100" value="<?=$h($b['name'])?>" required></div>
					<? if ($bhas('address')): ?><div class="lc_row"><label class="cl_lbl"><?=$w('cs:Address')?></label><input type="text" name="address" class="cl_inp cmp_inp" maxlength="255" value="<?=$h(@$b['address'])?>"></div><? endif ?>
				</div>
				<? if ($bhas('pay_types')): ?>
				<div class="lc_row">
					<label class="cl_lbl"><?=$w('cs:Pay types')?></label>
					<div class="cmp_checks">
						<? foreach ($pays as $pk => $pn): ?>
							<label class="cmp_chk"><input type="checkbox" name="pay_types[]" value="<?=$pk?>"<?=(in_array($pk, $bpt) ? ' checked' : '')?>><span><?=(strpos($pn, 'cs:') === 0 ? $w($pn) : $pn)?></span></label>
						<? endforeach ?>
					</div>
					<div class="cl_small"><?=$w('cs:pay_note')?></div>
				</div>
				<? if ($bhas('pay_default')): $bpd = (string)@$b['pay_default']; ?>
				<div class="lc_row">
					<label class="cl_lbl"><?=$w('cs:Pay default')?></label>
					<select name="pay_default" class="cl_inp cmp_inp cmp_pdef">
						<option value=""><?=$w('cs:Not selected')?></option>
						<? foreach ($pays as $pk => $pn): ?><option value="<?=$pk?>"<?=($bpd === $pk ? ' selected' : '')?><?=(!in_array($pk, $bpt) ? ' disabled' : '')?>><?=(strpos($pn, 'cs:') === 0 ? $w($pn) : $pn)?></option><? endforeach ?>
					</select>
				</div>
				<? endif ?>
				<? endif ?>
				<div class="cmp_2">
					<? if ($bhas('print_width')): $bpw = (int)@$b['print_width']; ?>
					<div class="lc_row">
						<label class="cl_lbl"><?=$w('cs:Print width')?></label>
						<select name="print_width" class="cl_inp cmp_inp">
							<option value="0"<?=(!in_array($bpw, [58, 80]) ? ' selected' : '')?>><?=$w('cs:Auto')?></option>
							<option value="80"<?=($bpw == 80 ? ' selected' : '')?>>80 мм</option>
							<option value="58"<?=($bpw == 58 ? ' selected' : '')?>>58 мм</option>
						</select>
					</div>
					<? endif ?>
					<? if ($bhas('print_title')): ?>
					<div class="lc_row">
						<label class="cl_lbl"><?=$w('cs:Print title')?></label>
						<input type="text" name="print_title" class="cl_inp cmp_inp" maxlength="100" value="<?=$h(@$b['print_title'])?>" placeholder="SUSHI - PIZZA">
					</div>
					<? endif ?>
				</div>
				<? if ($bhas('print_title')): ?><div class="cl_small"><?=$w('cs:print_title_note')?></div><? endif ?>
			</div>
			<?=$save?>
		</form>
	<? endforeach ?>
	</div>

	<? // 7. Басқа беттердегі баптаулар ?>
	</section>

	<section class="cmp_tab" data-tab="c_other">
	<div class="cmp_h2" id="c_other"><i class="fal fa-sliders-h"></i> <?=$w('cs:Other settings')?></div>
	<div class="cmp_links">
		<a href="/team/"><i class="fal fa-user-headset"></i><span><?=$w('cs:Staff')?></span><i class="far fa-angle-right"></i></a>
		<a href="/delivery/"><i class="fal fa-map-marked-alt"></i><span><?=$w('cs:Delivery zones')?></span><i class="far fa-angle-right"></i></a>
		<a href="/clients/cashback.php"><i class="fal fa-coins"></i><span><?=$w('cs:Cashback')?></span><i class="far fa-angle-right"></i></a>
		<a href="/clients/gift.php"><i class="fal fa-gift"></i><span><?=$w('cs:Gift')?></span><i class="far fa-angle-right"></i></a>
		<a href="/promo/"><i class="fal fa-tags"></i><span><?=$w('cs:Promotions')?></span><i class="far fa-angle-right"></i></a>
		<a href="/receipt/"><i class="fal fa-print"></i><span><?=$w('cs:Receipt')?></span><i class="far fa-angle-right"></i></a>
		<a href="/qr/"><i class="fal fa-qrcode"></i><span><?=$w('cs:QR menu')?></span><i class="far fa-angle-right"></i></a>
		<a href="/kaspi/"><i class="fal fa-credit-card"></i><span><?=t::w('xp:Payments', 'Онлайн төлемдер')?></span><i class="far fa-angle-right"></i></a>
	</div>
	</section>

	<? endif ?>
</div>
<script>
// Компания баптаулары: санаттар — бөлек табтар, URL #хэште сақталады
(function () {
	var cur = '';
	function show(h, scroll) {
		var tabs = document.querySelectorAll('.cmp_tab'); if (!tabs.length) return;
		var t = h ? document.querySelector('.cmp_tab[data-tab="' + h + '"]') : null;
		if (!t && h) { var el = document.getElementById(h); if (el && el.closest) t = el.closest('.cmp_tab'); }
		if (!t) t = document.querySelector('.cmp_tab[data-tab="' + cur + '"]') || tabs[0];
		cur = t.getAttribute('data-tab');
		tabs.forEach(function (x) { x.hidden = x !== t; x.setAttribute('data-ok', '1'); });
		document.querySelectorAll('.cmp_cats a').forEach(function (a) { a.classList.toggle('on', a.getAttribute('href') === '#' + cur); });
		if (scroll) window.scrollTo(0, 0);
	}
	document.addEventListener('click', function (e) {
		var a = e.target.closest && e.target.closest('.cmp_cats a'); if (!a) return;
		e.preventDefault();
		var h = a.getAttribute('href').slice(1);
		try { history.replaceState(null, '', '#' + h); } catch (er) {}
		show(h, true);
	});
	window.addEventListener('hashchange', function () { show(location.hash.slice(1), false); });
	show(location.hash.slice(1), false);
	// AJAX сақтаудан кейін бет ішкі HTML-і ауысады: сол табты қайта ашамыз
	if (window.MutationObserver) new MutationObserver(function () {
		if (document.querySelector('.cmp_tab:not([data-ok])')) show(cur, false);
	}).observe(document.body, { childList: true, subtree: true });
})();
</script>

<? include "../block/footer.php"; ?>
