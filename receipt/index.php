<? include "../config/core.php";
	require_once "../config/receipt.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Чек редакторы: кассадағы чектің (pos.berik.c/orders/order_print.php) көрінісі, әр компанияға бөлек
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	$rc = receipt::get($cmp);
	$logo = $rc['logo']; unset($rc['logo']);

	// Алдын ала көрініс үшін бірінші филиал (атауы, ені, нөмір коды)
	$br = mysqli_fetch_assoc(db::query("select * from company_branch where company_id = '$cmp' order by number asc, id asc limit 1")) ?: [];
	$br_title = trim((string)@$br['print_title']) ?: 'SUSHI - PIZZA';
	$br_w = (int)@$br['print_width'] == 58 ? 58 : 80;
	$nbr = (int)@mysqli_fetch_row(db::query("select count(*) from company_branch where company_id = '$cmp'"))[0];

	$chk = function ($k) use ($rc) { return $rc[$k] ? 'checked' : ''; };
	$sw = function ($k, $t, $s = 1) use ($chk) { return '<label class="qr_sw'.($s ? ' qr_sw_s' : '').'"><input type="checkbox" name="'.$k.'" value="1" '.$chk($k).'><span></span><b>'.$t.'</b></label>'; };
	$opt = function ($k, $list) use ($rc) {
		$h = '';
		foreach ($list as $v => $t) $h .= '<label><input type="radio" name="'.$k.'" value="'.$v.'" '.((string)$rc[$k] === (string)$v ? 'checked' : '').'><i>'.$t.'</i></label>';
		return $h;
	};
	$inp = function ($k, $t, $max = 30) use ($rc) { return '<label><span>'.$t.'</span><input type="text" name="'.$k.'" class="cl_inp" maxlength="'.$max.'" autocomplete="off" value="'.htmlspecialchars($rc[$k]).'"></label>'; };

	// site setting
	$menu_name = 'receipt';
	$page_name = 'Чек';
	$css = ['clients', 'qr', 'receipt'];
	$js = ['receipt'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page rc_page" data-csrf="<?=$_SESSION['csrf']?>" data-title="<?=htmlspecialchars($br_title)?>" data-bw="<?=$br_w?>" data-code="<?=htmlspecialchars((string)@$br['code'])?>">

	<div class="rc_grid">
		<form class="rc_form" autocomplete="off">

			<div class="cl_box rc_sec">
				<div class="cl_box_h"><div class="qr_h">Қағаз және жазу</div></div>
				<div class="qr_opt"><span>Чек ені</span><?=$opt('width', ['0' => 'Филиал бойынша ('.$br_w.' мм)', '80' => '80 мм', '58' => '58 мм'])?></div>
				<div class="qr_opt"><span>Тағамдар жазуының өлшемі</span><?=$opt('font', ['s' => 'Кіші', 'm' => 'Орташа', 'l' => 'Үлкен'])?></div>
				<? if ($nbr > 1): ?><div class="cl_small">Баптау компанияның барлық филиалына ортақ.</div><? endif ?>
			</div>

			<div class="cl_box rc_sec">
				<div class="cl_box_h"><div class="qr_h">Чектің басы</div></div>
				<?=$sw('logo_on', 'Логотип')?>
				<div class="rc_sub rc_logo_f">
					<div class="qr_logo_r">
						<img class="rc_logo_i <?=($logo ? '' : 'dsp_n')?>" src="<?=htmlspecialchars($logo)?>" alt="">
						<label class="cl_btn cl_btn_light qr_logo_up"><i class="fal fa-image"></i> <span><?=($logo ? 'Ауыстыру' : 'Жүктеу')?></span><input type="file" class="rc_logo_up" accept="image/png,image/jpeg,image/webp" hidden></label>
						<button type="button" class="cl_btn cl_btn_light rc_logo_del <?=($logo ? '' : 'dsp_n')?>"><i class="fal fa-trash-alt"></i></button>
						<input type="hidden" name="logo" value="<?=htmlspecialchars($logo)?>">
					</div>
					<label class="rc_range"><span>Логотип ені: <b class="rc_lw"><?=$rc['logo_w']?></b>%</span><input type="range" name="logo_w" min="20" max="100" step="5" value="<?=$rc['logo_w']?>"></label>
					<div class="cl_small">Термопринтер ақ-қара басады: қара-ақ, анық логотип жақсы шығады.</div>
				</div>
				<?=$sw('title_on', 'Тақырып')?>
				<div class="rc_sub">
					<div class="qr_fields"><label><span>Тақырып мәтіні</span><input type="text" name="rtitle" class="cl_inp" maxlength="40" autocomplete="off" placeholder="<?=htmlspecialchars($br_title)?>" value="<?=htmlspecialchars($rc['rtitle'])?>"></label></div>
					<div class="qr_opt"><span>Тақырып өлшемі</span><?=$opt('title_size', ['s' => 'Кіші', 'm' => 'Орташа', 'l' => 'Үлкен'])?></div>
				</div>
				<label class="qr_note"><span>Тақырып астындағы жазу (мекенжай, телефон, Instagram)</span><textarea name="head_text" autocomplete="off" class="cl_inp" rows="2" maxlength="300" placeholder="Мысалы: Ақтау, 12 мкр, 5 үй&#10;+7 700 000 00 00"><?=htmlspecialchars($rc['head_text'])?></textarea></label>
				<?=$sw('show_status', '«РАСПЕЧАТАНО» / «ИЗМЕНЕНО» белгісі')?>
				<?=$sw('show_type', 'Тапсырыс түрі (Собой, Кафе)')?>
				<?=$sw('show_number', 'Тапсырыс нөмірі')?>
				<?=$sw('show_date', 'Күні мен уақыты')?>
				<?=$sw('show_additional', 'Тапсырысқа түсініктеме')?>
			</div>

			<div class="cl_box rc_sec">
				<div class="cl_box_h"><div class="qr_h">Тағамдар және сома</div></div>
				<div class="qr_opt"><span>Тағам атауы</span><?=$opt('item_lang', ['ru' => 'Орысша', 'kz' => 'Қазақша'])?></div>
				<?=$sw('item_num', 'Тағамдарды нөмірлеу (1. 2. 3.)')?>
				<div class="qr_opt"><span>«Доставка» жолы</span><?=$opt('delivery', ['always' => 'Әрқашан', 'nonzero' => 'Сомасы болса', 'never' => 'Жоқ'])?></div>
				<div class="qr_opt"><span>«Предоплата» жолы</span><?=$opt('prepay', ['always' => 'Әрқашан', 'nonzero' => 'Сомасы болса', 'never' => 'Жоқ'])?></div>
				<?=$sw('show_topay', '«К ОПЛАТЕ» жолы')?>
			</div>

			<div class="cl_box rc_sec">
				<div class="cl_box_h"><div class="qr_h">Клиент және соңы</div></div>
				<?=$sw('show_client', 'Клиент бөлімі')?>
				<div class="rc_sub rc_cl_f">
					<?=$sw('show_phone', 'Телефон нөмірі')?>
					<?=$sw('show_address', 'Мекенжай (тек жеткізуде)')?>
					<?=$sw('show_cashback', 'Кэшбэк және баланс')?>
				</div>
				<label class="qr_note"><span>Чектің соңындағы жазу</span><textarea name="footer" autocomplete="off" class="cl_inp" rows="3" maxlength="500" placeholder="Мысалы: Рахмет! Тағы күтеміз :)"><?=htmlspecialchars($rc['footer'])?></textarea></label>
			</div>

			<div class="cl_box rc_sec">
				<div class="cl_box_h">
					<div class="qr_h">Жазулар</div>
					<div class="rc_lang"><button type="button" class="cl_btn cl_btn_light rc_lng" data-l="kz">Қазақша</button><button type="button" class="cl_btn cl_btn_light rc_lng" data-l="ru">Орысша</button></div>
				</div>
				<div class="rc_labels">
					<?=$inp('l_name', 'Тағам бағаны')?><?=$inp('l_qty', 'Саны бағаны')?><?=$inp('l_sum', 'Сома бағаны')?>
					<?=$inp('l_delivery', 'Жеткізу')?><?=$inp('l_total', 'Жалпы')?><?=$inp('l_prepay', 'Алдын ала төлем')?>
					<?=$inp('l_topay', 'Төлеуге')?><?=$inp('l_client', 'Клиент бөлімі')?><?=$inp('l_ph', 'Нөмір жолы')?><?=$inp('l_adr', 'Мекенжай жолы')?>
				</div>
			</div>

			<div class="rc_bar">
				<button type="button" class="cl_btn cl_btn_light rc_reset"><i class="fal fa-undo"></i> Әдепкі чек</button>
				<button type="submit" class="cl_btn cl_btn_green rc_save"><i class="fal fa-check"></i> Сақтау</button>
			</div>
		</form>

		<div class="rc_side">
			<div class="cl_box rc_pv_box">
				<div class="cl_box_h">
					<div class="qr_h">Алдын ала көрініс</div>
					<div class="qr_opt rc_pv_t">
						<label><input type="radio" name="pv_type" value="1" checked><i>Жеткізу</i></label>
						<label><input type="radio" name="pv_type" value="2"><i>Собой</i></label>
					</div>
				</div>
				<div class="rc_pv_wrap"><div class="rc_paper"></div></div>
				<div class="cl_small">Мысал тапсырыс. Нақты чек кассада осы баптаумен шығады.</div>
			</div>
		</div>
	</div>

</div>

<? include "../block/footer.php"; ?>
