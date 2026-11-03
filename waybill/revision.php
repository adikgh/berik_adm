<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Ревизия: санап шыққан қалдықты жазу. «Аяқтау» басылғанда айырмасы қалдыққа түзету болып жазылады.
	wb::install();
	$cmp = (int)$company;
	$br = wb::branch($cmp, $branch);
	if (empty($_SESSION['wb_csrf'])) $_SESSION['wb_csrf'] = bin2hex(random_bytes(16));
	$csrf_ok = isset($_POST['csrf']) && hash_equals((string)$_SESSION['wb_csrf'], (string)$_POST['csrf']);
	$now = date('Y-m-d H:i:s');

	$rev_get = function ($id) use ($cmp) {
		$id = (int)$id;
		$q = db::query("select * from revision where id = '$id' and company_id = '$cmp' limit 1");
		return $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
	};
	$stock_now = function ($raw_id, $br) use ($cmp) {
		$r = mysqli_fetch_assoc(db::query("select quantity from product_raw_stock where company_id = '$cmp' and branch_id = '".(int)$br."' and raw_id = '".(int)$raw_id."'"));
		return $r ? (float)$r['quantity'] : 0;
	};

	// жаңа ревизия: компанияның барлық шикізаты, күтілетін қалдығымен
	if (isset($_POST['create']) && $csrf_ok) {
		wb::sales_sync($cmp);
		$num = (int)mysqli_fetch_assoc(db::query("select IFNULL(MAX(number), 0) + 1 n from revision where company_id = '$cmp'"))['n'];
		db::query("insert into revision (company_id, branch_id, number, dt, status, user_id) values ('$cmp', '$br', '$num', '$now', 0, '".(int)$user_id."')");
		$id = (int)mysqli_insert_id(db::$connect);
		if ($id) db::query("insert into revision_item (revision_id, raw_id, expected, price)
			select '$id', r.id, IFNULL(st.quantity, 0), IFNULL(r.price, 0) from product_raw r
			left join product_raw_stock st on st.raw_id = r.id and st.company_id = '$cmp' and st.branch_id = '$br'
			where r.company_id = '$cmp'");
		header('location: /waybill/revision.php?id='.$id); exit;
	}

	// сақтау / аяқтау / жою
	if (isset($_POST['id']) && $csrf_ok) {
		$rv = $rev_get($_POST['id']);
		if ($rv && $rv['status'] == 0) {
			$id = (int)$rv['id'];
			if (isset($_POST['delete'])) {
				db::query("delete from revision_item where revision_id = '$id'");
				db::query("delete from revision where id = '$id' and status = 0");
				header('location: /waybill/revision.php'); exit;
			}
			foreach ((array)@$_POST['c'] as $rid => $v) {
				$v = trim((string)$v);
				$val = $v === '' ? 'NULL' : "'".round(wb::num($v), 3)."'";
				db::query("update revision_item set counted = $val where revision_id = '$id' and raw_id = '".(int)$rid."'");
			}
			db::query("update revision set comment = '".wb::esc(@$_POST['comment'])."' where id = '$id'");
			if (isset($_POST['finish'])) {
				// соңғы сатылымдар есепке кірсін, айырма — қазіргі қалдыққа қарай
				wb::sales_sync($cmp);
				mysqli_begin_transaction(db::$connect);
				$ok = true;
				$iq = db::query("select * from revision_item where revision_id = '$id' and counted is not null");
				while ($ok && $iq && $i = mysqli_fetch_assoc($iq)) {
					$exp = $stock_now($i['raw_id'], $rv['branch_id']);
					$diff = round((float)$i['counted'] - $exp, 3);
					$ok = db::query("update revision_item set expected = '$exp', diff = '$diff' where id = '".(int)$i['id']."'");
					if ($ok && $diff != 0) $ok = wb::move($cmp, $rv['branch_id'], $i['raw_id'], $diff, 'rev', $i['price'], 'revision', $id, null, 'Ревизия №'.$rv['number'], $now);
				}
				if ($ok) $ok = db::query("update revision set status = 1, done_dt = '$now' where id = '$id' and status = 0");
				if ($ok) mysqli_commit(db::$connect); else mysqli_rollback(db::$connect);
			}
			header('location: /waybill/revision.php?id='.$id.(isset($_POST['finish']) ? '' : '&saved=1')); exit;
		}
	}

	$id = (int)@$_GET['id'];
	$rv = $id ? $rev_get($id) : null;
	$items = [];
	if ($rv) {
		$q = db::query("select i.*, r.name_kz, r.name_ru, r.unit from revision_item i join product_raw r on r.id = i.raw_id
			where i.revision_id = '".(int)$rv['id']."' order by (i.counted is not null) desc, (i.expected <> 0) desc, r.name_kz");
		while ($q && $i = mysqli_fetch_assoc($q)) $items[] = $i;
	} else {
		$list = db::query("select v.*, u.name u_name, u.surname u_surname,
				(select COUNT(*) from revision_item i where i.revision_id = v.id and i.counted is not null) counted_n,
				(select IFNULL(SUM(IF(i.diff < 0, i.diff * i.price, 0)), 0) from revision_item i where i.revision_id = v.id) short,
				(select IFNULL(SUM(IF(i.diff > 0, i.diff * i.price, 0)), 0) from revision_item i where i.revision_id = v.id) plus
			from revision v left join user u on u.id = v.user_id
			where v.company_id = '$cmp' order by v.dt desc, v.id desc limit 100");
	}

	// site setting
	$menu_name = 'waybill';
	$page_name = 'Ревизия';
	$wb_tab = 'revision';
	$css = ['waybill'];
	$js = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page" data-ajx-nav=".wb_page">

	<? include "tabs.php"; ?>

	<? if (!$rv): ?>
		<div class="wb_bar">
			<div class="wb_small">Ревизия кезінде әр шикізатты санап, нақты қалдығын жазасыз. Аяқтағанда жүйедегі қалдық соған теңеледі.</div>
			<form method="post" data-ajx=".wb_page">
				<input type="hidden" name="csrf" value="<?=wb::h($_SESSION['wb_csrf'])?>">
				<button class="wb_btn wb_btn_green" name="create" value="1"><i class="fal fa-clipboard-list-check"></i> Жаңа ревизия</button>
			</form>
		</div>
		<div class="wb_box wb_table">
			<div class="wb_tr wb_th">
				<div class="wb_td wb_td_n">№</div>
				<div class="wb_td">Күні</div>
				<div class="wb_td wb_td_name">Кім</div>
				<div class="wb_td">Саналды</div>
				<div class="wb_td">Кем шықты</div>
				<div class="wb_td">Артық</div>
				<div class="wb_td wb_td_st">Күйі</div>
			</div>
			<? $n = 0; while ($list && $v = mysqli_fetch_assoc($list)): $n++; ?>
				<a class="wb_tr" href="/waybill/revision.php?id=<?=$v['id']?>">
					<div class="wb_td wb_td_n"><b><?=$v['number']?></b></div>
					<div class="wb_td" data-h="Күні"><?=wb::dt($v['dt'], true)?></div>
					<div class="wb_td wb_td_name"><div class="wb_name"><?=wb::h(trim($v['u_name'].' '.$v['u_surname']) ?: '—')?></div><? if ($v['comment']): ?><div class="wb_small"><?=wb::h(mb_strimwidth($v['comment'], 0, 50, '…'))?></div><? endif ?></div>
					<div class="wb_td" data-h="Саналды"><?=$v['counted_n']?> түрі</div>
					<div class="wb_td" data-h="Кем шықты"><?=($v['short'] < 0 ? '<b class="wb_c_red">'.wb::sum(-$v['short']).'</b>' : '<span class="wb_small">—</span>')?></div>
					<div class="wb_td" data-h="Артық"><?=($v['plus'] > 0 ? '<b class="wb_c_green">'.wb::sum($v['plus']).'</b>' : '<span class="wb_small">—</span>')?></div>
					<div class="wb_td wb_td_st"><span class="wb_st wb_st_<?=($v['status'] ? 'ok' : 'draft')?>"><?=($v['status'] ? 'Аяқталды' : 'Жоба')?></span></div>
				</a>
			<? endwhile ?>
			<? if (!$n): ?><div class="wb_empty"><i class="fal fa-clipboard-list"></i><div>Әлі ревизия жасалмаған</div></div><? endif ?>
		</div>

	<? else: $done = $rv['status'] == 1; ?>
		<a class="wb_back" href="/waybill/revision.php"><i class="far fa-arrow-left"></i> Ревизиялар</a>
		<form method="post" class="wb_box wb_table wbv" id="wbv" data-ajx=".wb_page">
			<input type="hidden" name="csrf" value="<?=wb::h($_SESSION['wb_csrf'])?>">
			<input type="hidden" name="id" value="<?=$rv['id']?>">
			<div class="wb_box_h">
				<span>Ревизия № <?=$rv['number']?> <span class="wb_st wb_st_<?=($done ? 'ok' : 'draft')?>"><?=($done ? 'Аяқталды' : 'Жоба')?></span></span>
				<span class="wb_small"><?=wb::dt($rv['dt'], true)?><?=($done ? ' · аяқталды '.wb::dt($rv['done_dt'], true) : '')?><?=(@$_GET['saved'] ? ' · сақталды' : '')?></span>
			</div>
			<div class="wb_tr wb_th">
				<div class="wb_td wb_td_name">Шикізат</div>
				<div class="wb_td">Жүйеде</div>
				<div class="wb_td">Санағанда</div>
				<div class="wb_td">Айырма</div>
				<div class="wb_td">Сомасы</div>
			</div>
			<? foreach ($items as $i): $u = wb::h(fun::unit_norm($i['unit'])); $d = $i['counted'] !== null ? ($done ? (float)$i['diff'] : (float)$i['counted'] - (float)$i['expected']) : null; ?>
				<div class="wb_tr wbv_row" data-exp="<?=(float)$i['expected']?>" data-price="<?=(float)$i['price']?>">
					<div class="wb_td wb_td_name"><div class="wb_name"><?=wb::h($i['name_kz'] ?: $i['name_ru'])?></div><div class="wb_small"><?=wb::up($i['price'])?> / <?=$u?></div></div>
					<div class="wb_td" data-h="Жүйеде"><?=wb::qty($i['expected'])?> <span class="wb_small"><?=$u?></span></div>
					<div class="wb_td" data-h="Санағанда">
						<? if ($done): ?><?=($i['counted'] !== null ? '<b>'.wb::qty($i['counted']).'</b> <span class="wb_small">'.$u.'</span>' : '<span class="wb_small">саналмады</span>')?>
						<? else: ?><input type="text" inputmode="decimal" class="wb_inp wbv_inp" name="c[<?=$i['raw_id']?>]" value="<?=($i['counted'] !== null ? wb::qty($i['counted']) : '')?>" placeholder="<?=$u?>" autocomplete="off"><? endif ?>
					</div>
					<div class="wb_td wbv_d" data-h="Айырма"><?=($d !== null && $d != 0 ? '<b class="'.($d < 0 ? 'wb_c_red' : 'wb_c_green').'">'.($d > 0 ? '+' : '').wb::qty($d).'</b>' : '<span class="wb_small">—</span>')?></div>
					<div class="wb_td wbv_s" data-h="Сомасы"><?=($d ? '<span class="'.($d < 0 ? 'wb_c_red' : 'wb_c_green').'">'.wb::sum($d * $i['price']).'</span>' : '<span class="wb_small">—</span>')?></div>
				</div>
			<? endforeach ?>
			<div class="wb_foot wbv_foot">
				<div class="wb_total">Кем: <b class="wb_c_red" id="wbv_short">0 ₸</b> &nbsp; Артық: <b class="wb_c_green" id="wbv_plus">0 ₸</b></div>
				<? if (!$done): ?>
					<div class="wb_actions">
						<input type="text" name="comment" class="wb_inp" value="<?=wb::h($rv['comment'])?>" placeholder="Түсініктеме" style="width:220px">
						<button class="wb_btn wb_btn_light" name="save" value="1"><i class="fal fa-save"></i> Сақтау</button>
						<button class="wb_btn wb_btn_green" name="finish" value="1" data-ask="Ревизияны аяқтайсыз ба?<br>Саналған шикізаттың қалдығы осы санға теңеледі."><i class="fal fa-check"></i> Аяқтау</button>
						<button class="wb_btn wb_btn_red" name="delete" value="1" data-ask="Ревизия жобасы өшірілсін бе?" data-ask-danger><i class="fal fa-trash"></i></button>
					</div>
				<? endif ?>
			</div>
		</form>
	<? endif ?>
		<script>
		// ревизия: AJAX-пен ашылса да істейді (оқиғалар document-те, fun.js ajx:done кейін қайта есептейді)
		(function () {
			var f;
			var fmt = function (v) { return Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₸'; };
			var q = function (v) { return (Math.round(v * 1000) / 1000).toString(); };
			function calc() {
				f = document.getElementById('wbv'); if (!f) return;
				var sh = 0, pl = 0;
				f.querySelectorAll('.wbv_row').forEach(function (r) {
					var inp = r.querySelector('.wbv_inp'), d = null, price = +r.dataset.price;
					if (inp) { var v = inp.value.replace(/\s/g, '').replace(',', '.'); if (v !== '' && !isNaN(v)) d = +v - +r.dataset.exp; }
					else { var b = r.querySelector('.wbv_d b'); if (b) d = +b.textContent.replace(/[^\d.\-+]/g, ''); }
					if (inp) {
						r.querySelector('.wbv_d').innerHTML = d ? '<b class="' + (d < 0 ? 'wb_c_red' : 'wb_c_green') + '">' + (d > 0 ? '+' : '') + q(d) + '</b>' : '<span class="wb_small">—</span>';
						r.querySelector('.wbv_s').innerHTML = d ? '<span class="' + (d < 0 ? 'wb_c_red' : 'wb_c_green') + '">' + fmt(d * price) + '</span>' : '<span class="wb_small">—</span>';
					}
					if (d) { if (d < 0) sh += -d * price; else pl += d * price; }
				});
				document.getElementById('wbv_short').textContent = fmt(sh);
				document.getElementById('wbv_plus').textContent = fmt(pl);
			}
			document.addEventListener('input', function (e) { if (e.target.classList.contains('wbv_inp')) calc(); });
			// Enter — келесі жолға өту (форма жіберілмейді)
			document.addEventListener('keydown', function (e) {
				if (e.key !== 'Enter' || !e.target.classList.contains('wbv_inp')) return;
				e.preventDefault();
				var all = [].slice.call(f.querySelectorAll('.wbv_inp')), i = all.indexOf(e.target);
				if (all[i + 1]) { all[i + 1].focus(); all[i + 1].select(); }
			});
			$(document).on('ajx:done', calc);
			calc();
		})();
		</script>

</div>

<? include "../block/footer.php"; ?>
