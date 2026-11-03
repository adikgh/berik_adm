<? include "../config/core.php";
	include "lib.php";
	require_once "../config/loyalty.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Лоялдық баптауы: кэшбэк пайызы және N-ші тапсырысқа сыйлық
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	$ready = loyalty::on();
	$msg = '';

	if ($ready && $_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) $msg = 'Бетті жаңартып, қайта көріңіз';
		else {
			$old = loyalty::cfg($cmp);
			$pct = round(max(0, min(50, (float)str_replace(',', '.', (string)@$_POST['cashback_percent']))), 2);
			$e = function ($v) { return mysqli_real_escape_string(db::$connect, $v); };
			$now = date('Y-m-d H:i:s');
			$set = ["cashback_percent = '$pct'"];
			if ($pct > 0 && !$old['cashback']) $set[] = "cashback_from = '$now'"; // 0-ден қосылса — осы сәттен бастап
			// Сатылы кэшбэк: тапсырыс сомасы «асса» қанша пайыз (бос жолдар ескерілмейді)
			if (loyalty::steps_on() && isset($_POST['step'])) {
				$rows = [];
				foreach ((array)$_POST['step'] as $st) {
					$fs = max(0, (int)preg_replace('/\D+/', '', (string)@$st['from']));
					$sp = round(max(0, min(50, (float)str_replace(',', '.', (string)@$st['percent']))), 2);
					if ($sp > 0 && trim((string)@$st['percent']) !== '') $rows[$fs] = $sp;
				}
				ksort($rows);
				db::query("delete from company_cashback_step where company_id = '$cmp'");
				foreach ($rows as $fs => $sp) db::query("insert into company_cashback_step (company_id, from_sum, percent) values ('$cmp', '$fs', '$sp')");
			}
			$gift_on = false;
			if (loyalty::tiers_on()) {
				// Сыйлық деңгейлері: әр жол — әр N-ші тапсырысқа не беріледі
				foreach ((array)@$_POST['tier'] as $t) {
					$tid = (int)@$t['id'];
					$every = max(0, min(500, (int)@$t['every']));
					$name = mb_substr(trim(strip_tags((string)@$t['name'])), 0, 255);
					$pid = (int)@$t['product_id'];
					if ($pid && !mysqli_num_rows(db::query("select id from product where id = '$pid' and company_id = '$cmp'"))) $pid = 0;
					$qty = max(1, min(20, (int)@$t['quantity']));
					$dp = max(0, min(100, (int)@$t['discount_percent']));
					$dm = max(0, (int)@$t['discount_max']);
					$del = !empty($t['del']) || $every < 2 || $name === '' || (!$pid && !$dp);
					if ($tid) {
						if ($del) db::query("update company_gift_tier set active = 0 where id = '$tid' and company_id = '$cmp'");
						else db::query("update company_gift_tier set every = '$every', name = '".$e($name)."', product_id = ".($pid ?: 'NULL').", quantity = '".($pid ? $qty : 0)."',
							discount_percent = '".($pid ? 0 : $dp)."', discount_max = '".($pid ? 0 : $dm)."', active = 1 where id = '$tid' and company_id = '$cmp'");
					} elseif (!$del) {
						db::query("insert into company_gift_tier (company_id, every, name, product_id, quantity, discount_percent, discount_max)
							values ('$cmp', '$every', '".$e($name)."', ".($pid ?: 'NULL').", '".($pid ? $qty : 0)."', '".($pid ? 0 : $dp)."', '".($pid ? 0 : $dm)."')");
					}
				}
				$gift_on = mysqli_num_rows(db::query("select id from company_gift_tier where company_id = '$cmp' and active = 1 and every > 1")) > 0;
			} else {
				$every = max(0, min(100, (int)@$_POST['gift_every']));
				if ($every == 1) $every = 2; // әр тапсырысқа сыйлық болмайды
				$gname = mb_substr(trim(strip_tags((string)@$_POST['gift_name'])), 0, 255);
				if ($gname === '') $every = 0;
				$set[] = "gift_every = '$every'"; $set[] = "gift_name = ".($gname !== '' ? "'".$e($gname)."'" : "NULL");
				$gift_on = $every > 1;
			}
			if ($gift_on && (!$old['gift'] || @$_POST['gift_reset'])) $set[] = "gift_from = '$now'"; // қосылғанда не «бүгіннен бастау»
			db::query("update company set ".implode(', ', $set)." where id = '$cmp'");
			header('location: /clients/loyalty.php?saved=1'); exit;
		}
	}
	if (@$_GET['saved']) $msg = 'Сақталды';

	$cfg = loyalty::cfg($cmp);
	if ($ready) loyalty::sync($cmp);
	$prods = [];
	if ($ready && loyalty::tiers_on()) {
		$q = db::query("select id, name_kz, name_ru, price from product where company_id = '$cmp' and arh = 0 order by name_kz, name_ru");
		while ($q && $p = mysqli_fetch_assoc($q)) $prods[] = $p;
	}

	// Қысқа есеп
	$st = ['balance' => 0, 'clients' => 0, 'earn_m' => 0, 'spend_m' => 0, 'gifts_m' => 0, 'gifts' => 0];
	$moves = false;
	if ($ready) {
		$m0 = date('Y-m-01 00:00:00');
		$r = mysqli_fetch_assoc(db::query("select coalesce(sum(cashback_balance), 0) b, sum(cashback_balance > 0) n from company_client where company_id = '$cmp'"));
		$st['balance'] = (int)$r['b']; $st['clients'] = (int)$r['n'];
		$r = mysqli_fetch_assoc(db::query("select coalesce(sum(case when type in ('earn','cancel') then amount end), 0) e, coalesce(-sum(case when type in ('spend','refund') then amount end), 0) s from company_client_cashback where company_id = '$cmp' and ins_dt >= '$m0'"));
		$st['earn_m'] = (int)$r['e']; $st['spend_m'] = (int)$r['s'];
		$r = mysqli_fetch_assoc(db::query("select count(*) n, sum(ins_dt >= '$m0') m from company_client_gift where company_id = '$cmp'"));
		$st['gifts'] = (int)$r['n']; $st['gifts_m'] = (int)$r['m'];
		$moves = db::query("select k.*, c.name, c.phone, o.number from company_client_cashback k join company_client c on c.id = k.client_id left join retail_orders o on o.id = k.order_id
			where k.company_id = '$cmp' order by k.id desc limit 50");
	}
	$tnames = ['earn' => 'Түсті', 'spend' => 'Жұмсалды', 'cancel' => 'Қайтарылды (статус)', 'refund' => 'Клиентке қайтты', 'manual' => 'Қолмен'];

	// site setting
	$menu_name = 'clients';
	$page_name = 'Лоялдық: кэшбэк және сыйлық';
	$page_back = ['/clients/', 'Клиенттер'];
	$css = ['clients', 'loyalty'];
	$js = [];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page lc_page">


	<? if (!$ready): ?>
		<div class="cl_box"><div class="cl_empty">Базаға кэшбэк кестелері әлі қосылмаған (berik_cashback.sql).</div></div>
	<? else: ?>

	<? if ($msg): ?><div class="lc_msg"><?=htmlspecialchars($msg)?></div><? endif ?>

	<form class="lc_grid" method="post" autocomplete="off" data-ajx=".lc_page" data-ajx-msg=".lc_msg">
		<input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>">

		<div class="cl_box lc_set">
			<div class="cl_box_h"><div><i class="fal fa-coins"></i> Кэшбэк</div><span class="cl_seg <?=($cfg['cashback'] ? 'cl_seg_loyal' : 'cl_seg_archive')?>"><?=($cfg['cashback'] ? 'Қосулы' : 'Өшірулі')?></span></div>
			<label class="cl_lbl">Әр тапсырыстан қанша пайыз</label>
			<div class="lc_inp_w"><input type="number" name="cashback_percent" class="cl_inp" min="0" max="50" step="0.5" value="<?=rtrim(rtrim(number_format($cfg['percent'], 2, '.', ''), '0'), '.')?>"><span>%</span></div>
			<div class="cl_small">0 болса кэшбэк мүлде жоқ: жиналмайды, кассада да, клиент кабинетінде де көрінбейді.
				Тағам сомасынан есептеледі (доставка кірмейді), тапсырыс «Аяқталды» болғанда түседі. Кэшбэкті тек осы компанияда жұмсауға болады.</div>
			<? if (loyalty::steps_on()): $sl = $cfg['steps']; $sl[] = ['from' => '', 'percent' => '']; $sl[] = ['from' => '', 'percent' => '']; ?>
				<label class="cl_lbl">Сома бойынша сатылар (толтырылса, жоғарғы пайыздың орнына осы қолданылады)</label>
				<div class="lc_steps">
					<? foreach ($sl as $i => $sv): ?>
					<div class="lc_step">
						<span>Тағам сомасы</span>
						<div class="lc_inp_w"><input type="number" name="step[<?=$i?>][from]" class="cl_inp" min="0" value="<?=$sv['from']?>" placeholder="0"><span>тг-дан бастап</span></div>
						<div class="lc_inp_w"><input type="number" name="step[<?=$i?>][percent]" class="cl_inp" min="0" max="50" step="0.5" value="<?=($sv['percent'] === '' ? '' : rtrim(rtrim(number_format($sv['percent'], 2, '.', ''), '0'), '.'))?>"><span>%</span></div>
					</div>
					<? endforeach ?>
				</div>
				<div class="cl_small">Мысалы: 0 → 1%, 7 990 → 3%, 9 990 → 5%, 14 990 → 7%, 29 990 → 10%. Сома осы санға жетсе, сол пайыз беріледі. Жолды өшіру үшін пайызын тазалаңыз.</div>
			<? endif ?>
			<? if ($cfg['cashback'] && $cfg['from']): ?><div class="cl_small">Басталды: <?=clients::dt($cfg['from'])?></div><? endif ?>
		</div>

		<? if (loyalty::tiers_on()): $tl = array_reverse($cfg['tiers']); $tl[] = ['id' => 0, 'every' => '', 'name' => '', 'product_id' => 0, 'quantity' => 1, 'discount_percent' => 0, 'discount_max' => 0];
			// Қазақша реттік жұрнақ: 5-ші, 6-шы, 10-шы, 20-шы, 50-ші
			$ord = function ($n) { $n = (int)$n; if ($n <= 0) return '-ші'; if ($n % 100 == 0) return '-ші'; $d = $n % 10;
				if ($d) return in_array($d, [6, 9]) ? '-шы' : '-ші'; return in_array(($n % 100) / 10, [5, 7, 8]) ? '-ші' : '-шы'; }; ?>
		<div class="cl_box lc_set lc_set_wide">
			<div class="cl_box_h"><div><i class="fal fa-gift"></i> Әр N-ші тапсырысқа сыйлық</div><span class="cl_seg <?=($cfg['gift'] ? 'cl_seg_loyal' : 'cl_seg_archive')?>"><?=($cfg['gift'] ? 'Қосулы' : 'Өшірулі')?></span></div>
			<div class="lc_tiers">
				<div class="lc_tier lc_tier_h"><div>Нешінші тапсырыс</div><div>Атауы (кассада көрінеді)</div><div>Тегін тағам (0 тг)</div><div>Саны</div><div>Не жеңілдік</div><div>Ең көбі (тг)</div><div></div></div>
				<? foreach ($tl as $i => $t): $hp = $t['product_id'] > 0; ?>
				<? if (!$t['id']): ?><div class="lc_tier_cap"><i class="fal fa-plus"></i> Жаңа сыйлық қосу</div><? endif ?>
				<div class="lc_tier<?=($t['id'] ? '' : ' lc_tier_new')?><?=($hp ? ' lc_tier_prod' : '')?>">
					<input type="hidden" name="tier[<?=$i?>][id]" value="<?=$t['id']?>">
					<div class="lc_inp_w"><input type="number" name="tier[<?=$i?>][every]" class="cl_inp lc_t_every" min="2" max="500" value="<?=$t['every']?>" placeholder="N"><span class="lc_t_ord"><?=$ord($t['every'])?></span></div>
					<input type="text" name="tier[<?=$i?>][name]" class="cl_inp" maxlength="255" value="<?=htmlspecialchars($t['name'])?>" placeholder="<?=($t['id'] ? '' : 'Мысалы: Цезарь тегін')?>">
					<select name="tier[<?=$i?>][product_id]" class="cl_inp lc_t_prod">
						<option value="0">— тағамсыз (жеңілдік) —</option>
						<? if ($t['product_id'] && !in_array($t['product_id'], array_column($prods, 'id'))): $pa = mysqli_fetch_assoc(db::query("select name_kz, name_ru from product where id = '".(int)$t['product_id']."'")); ?>
							<option value="<?=$t['product_id']?>" selected><?=htmlspecialchars(($pa ? ($pa['name_kz'] ?: $pa['name_ru']) : '#'.$t['product_id']).' (мәзірде жоқ)')?></option>
						<? endif ?>
						<? foreach ($prods as $p): ?><option value="<?=$p['id']?>"<?=($p['id'] == $t['product_id'] ? ' selected' : '')?>><?=htmlspecialchars($p['name_kz'] ?: $p['name_ru'])?> · <?=(int)$p['price']?> тг</option><? endforeach ?>
					</select>
					<input type="number" name="tier[<?=$i?>][quantity]" class="cl_inp lc_t_q" min="1" max="20" value="<?=max(1, (int)$t['quantity'])?>"<?=($hp ? '' : ' disabled')?>>
					<div class="lc_inp_w"><input type="number" name="tier[<?=$i?>][discount_percent]" class="cl_inp lc_t_d" min="0" max="100" value="<?=($t['discount_percent'] ?: '')?>"<?=($hp ? ' disabled' : '')?>><span>%</span></div>
					<input type="number" name="tier[<?=$i?>][discount_max]" class="cl_inp lc_t_d" min="0" value="<?=($t['discount_max'] ?: '')?>" placeholder="шексіз"<?=($hp ? ' disabled' : '')?>>
					<? if ($t['id']): ?><label class="lc_del" title="Бұл сыйлықты өшіру"><input type="checkbox" name="tier[<?=$i?>][del]" value="1"> <i class="fal fa-trash"></i></label><? else: ?><div></div><? endif ?>
				</div>
				<? endforeach ?>
			</div>
			<label class="lc_chk"><input type="checkbox" name="gift_reset" value="1"> Санауды бүгіннен бастау (жаңа ай)</label>
			<ul class="lc_help">
				<li>Клиенттің барлық тапсырысы саналады, тек отказдар саналмайды.</li>
				<li>Бір тапсырыс бірнеше сыйлыққа сәйкес келсе (мысалы 10-шы әрі 5-ші), үлкені беріледі.</li>
				<li>Тағам таңдалса, кассада «Сыйлықты беру» басылғанда ол тапсырысқа 0 тг болып қосылады.</li>
				<li>Тағам таңдалмаса, жеңілдік тағам сомасынан есептеледі; «Ең көбі» толтырылса, сол сомаға дейін ғана.</li>
				<li>Сыйлықты өшіру үшін себетті белгілеп, «Сақтау» басыңыз.</li>
			</ul>
			<? if ($cfg['gift'] && $cfg['gift_from']): ?><div class="cl_small">Санау басталды: <?=clients::dt($cfg['gift_from'])?></div><? endif ?>
		</div>
		<script>
			// Тағам таңдалса — саны ашылады, жеңілдік жабылады; нөмір өзгерсе — жұрнақ (-ші/-шы) жаңарады
			(function () {
				if (window.lc_tier_js) return; window.lc_tier_js = 1
				var ord = function (n) { n = parseInt(n) || 0; if (n <= 0 || n % 100 == 0) return '-ші'; var d = n % 10
					if (d) return (d == 6 || d == 9) ? '-шы' : '-ші'; return [5, 7, 8].indexOf((n % 100) / 10) >= 0 ? '-ші' : '-шы' }
				document.addEventListener('change', function (e) {
					if (!e.target.classList.contains('lc_t_prod')) return
					var r = e.target.closest('.lc_tier'), hp = e.target.value != '0'
					r.classList.toggle('lc_tier_prod', hp)
					r.querySelector('.lc_t_q').disabled = !hp
					r.querySelectorAll('.lc_t_d').forEach(function (x) { x.disabled = hp; if (hp) x.value = '' })
				})
				document.addEventListener('input', function (e) {
					if (!e.target.classList.contains('lc_t_every')) return
					e.target.closest('.lc_tier').querySelector('.lc_t_ord').textContent = ord(e.target.value)
				})
			})()
		</script>
		<? else: $t0 = @$cfg['tiers'][0]; ?>
		<div class="cl_box lc_set">
			<div class="cl_box_h"><div><i class="fal fa-gift"></i> N-ші тапсырысқа сыйлық</div><span class="cl_seg <?=($cfg['gift'] ? 'cl_seg_loyal' : 'cl_seg_archive')?>"><?=($cfg['gift'] ? 'Қосулы' : 'Өшірулі')?></span></div>
			<label class="cl_lbl">Нешінші тапсырыс тегін сыйлықпен</label>
			<div class="lc_inp_w"><input type="number" name="gift_every" class="cl_inp" min="0" max="100" value="<?=(int)@$t0['every']?>"><span>-ші</span></div>
			<label class="cl_lbl">Сыйлық</label>
			<input type="text" name="gift_name" class="cl_inp" maxlength="255" value="<?=htmlspecialchars((string)@$t0['name'])?>" placeholder="Мысалы: Пепперони тегін">
			<label class="lc_chk"><input type="checkbox" name="gift_reset" value="1"> Санауды бүгіннен бастау (жаңа ай)</label>
			<div class="cl_small">0 болса сыйлық жүйесі жоқ.</div>
		</div>
		<? endif ?>

		<div class="lc_save"><button type="submit" class="cl_btn cl_btn_green"><i class="fal fa-check"></i> Сақтау</button></div>
	</form>

	<div class="cl_cards cl_cards_3">
		<div class="cl_card"><div class="cl_card_h">Клиенттердегі кэшбэк</div><div class="cl_card_v cl_c_green"><?=clients::sum($st['balance'])?></div><div class="cl_card_s"><?=$st['clients']?> клиентте</div></div>
		<div class="cl_card"><div class="cl_card_h">Осы айда түсті / жұмсалды</div><div class="cl_card_v cl_v_sm"><?=clients::sum($st['earn_m'])?> / <?=clients::sum($st['spend_m'])?></div></div>
		<div class="cl_card"><div class="cl_card_h">Берілген сыйлық</div><div class="cl_card_v"><?=$st['gifts_m']?></div><div class="cl_card_s">осы айда · барлығы <?=$st['gifts']?></div></div>
	</div>

	<div class="cl_box cl_table">
		<div class="cl_box_h"><div>Соңғы қозғалыстар</div></div>
		<div class="cl_tr cl_th">
			<div class="cl_td">Күні</div>
			<div class="cl_td cl_td_name">Клиент</div>
			<div class="cl_td">Тапсырыс</div>
			<div class="cl_td">Түрі</div>
			<div class="cl_td">Сома</div>
			<div class="cl_td">Балансы</div>
		</div>
		<? $has = false; while ($moves && $k = mysqli_fetch_assoc($moves)): $has = true; ?>
			<div class="cl_tr">
				<div class="cl_td" data-h="Күні"><?=clients::dt($k['ins_dt'])?></div>
				<div class="cl_td cl_td_name"><a href="/clients/item.php?id=<?=$k['client_id']?>"><?=($k['name'] ? htmlspecialchars($k['name']) : clients::phone_view($k['phone']))?></a></div>
				<div class="cl_td" data-h="Тапсырыс"><?=($k['number'] ? '#'.$k['number'] : '—')?></div>
				<div class="cl_td" data-h="Түрі"><?=@$tnames[$k['type']] ?: htmlspecialchars($k['type'])?></div>
				<div class="cl_td" data-h="Сома"><b class="<?=($k['amount'] > 0 ? 'cl_c_green' : '')?>"><?=($k['amount'] > 0 ? '+' : '−').clients::sum(abs($k['amount']))?></b></div>
				<div class="cl_td" data-h="Балансы"><?=clients::sum($k['balance_after'])?></div>
			</div>
		<? endwhile ?>
		<? if (!$has): ?><div class="cl_empty">Әзірге жоқ</div><? endif ?>
	</div>

	<? endif ?>
</div>

<? include "../block/footer.php"; ?>
