<? include "../config/core.php";
	include "pay_lib.php";
	include "../waybill/lib.php"; // кезең, сілтеме, күн форматы

	// Жалақы: тек басшылық (1 құрушы, 2 директор)
	if (!$user_id) { header('location: /'); exit(); }
	if (!zp::can()) { header('location: /team/'); exit(); }
	zp::install();
	$cmp = (int)$company;

	$per = wb::period();
	$pf = $per['from']; $pt = $per['to'];
	$p_names = ['month' => 'Бұл ай', 'prev' => 'Өткен ай', '3m' => '3 ай', 'year' => 'Бұл жыл'];

	// жұмысшылар топтар бойынша
	$staff = zp::staff($cmp);
	$paid = zp::paid_map($cmp, $pf, $pt);
	$earn = zp::courier_earn($cmp, $pf, $pt);
	$grp = array_fill_keys(array_keys(zp::$groups), []);
	foreach ($staff as $uid => $s) $grp[zp::group_of($s['positions_id'])][$uid] = $s;
	// тізімнен шыққан, бірақ осы кезеңде төлем алған адамдар да көрінсін
	$gone = array_diff(array_keys($paid), array_keys($staff));
	if ($gone) {
		$q = db::query("select p.user_id, max(p.positions_id) positions_id, u.name, u.surname, u.phone from staff_pay p left join user u on u.id = p.user_id
			where p.company_id = '$cmp' and p.user_id in (".implode(',', array_map('intval', $gone)).") group by p.user_id");
		while ($r = mysqli_fetch_assoc($q)) { $r['gone'] = 1; $grp[zp::group_of($r['positions_id'])][$r['user_id']] = $r; }
	}

	// жиындар
	$tot = array_fill_keys(array_keys(zp::$groups), 0);
	foreach ($grp as $g => $list) foreach ($list as $uid => $s) $tot[$g] += (int)@$paid[$uid]['sum'];
	$courier_auto = 0;
	foreach ($earn as $uid => $e) $courier_auto += $e['sum'];
	$tot['courier'] += $courier_auto;
	$all = array_sum($tot);

	// журнал
	$log = db::query("select p.*, u.name, u.surname from staff_pay p left join user u on u.id = p.user_id
		where p.company_id = '$cmp' and p.pay_date between '$pf' and '$pt' order by p.pay_date desc, p.id desc limit 300");

	$menu_name = 'team';
	$page_name = 'Жалақы';
	$page_key = 'Salary';
	$tm_tab = 'pay';
	$css = ['team', 'waybill', 'pay'];
	$js = ['pay'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c">
	<? include "tabs.php"; ?>
</div>

<div class="bl_c wb_page zp_page">

	<!-- Кезең -->
	<div class="wb_bar">
		<div class="wb_tabs">
			<? foreach ($p_names as $k => $v): ?>
				<a class="wb_tab <?=($per['p'] == $k ? 'wb_tab_act' : '')?>" href="<?=wb::url('/team/pay.php', ['p' => $k, 'from' => null, 'to' => null])?>"><?=$v?></a>
			<? endforeach ?>
		</div>
		<form class="wb_range" method="get" action="/team/pay.php">
			<input type="date" name="from" value="<?=($per['p'] == 'custom' ? $pf : '')?>" class="wb_inp">
			<span>—</span>
			<input type="date" name="to" value="<?=($per['p'] == 'custom' ? $pt : '')?>" class="wb_inp">
			<button class="wb_btn" type="submit"><i class="fal fa-check"></i></button>
		</form>
	</div>
	<div class="wb_sub"><?=wb::dt($pf)?> — <?=wb::dt($pt)?></div>

	<!-- Карточкалар -->
	<div class="wb_cards">
		<div class="wb_card">
			<div class="wb_card_h">Барлық жалақы</div>
			<div class="wb_card_v"><?=zp::money($all)?></div>
			<div class="wb_card_s">ОПИУ-ға осы сома түседі</div>
		</div>
		<? foreach (['oper', 'cook', 'courier'] as $g): ?>
			<a class="wb_card wb_card_link" href="#zp_<?=$g?>">
				<div class="wb_card_h"><?=zp::$groups[$g]['name']?></div>
				<div class="wb_card_v"><?=zp::money($tot[$g])?></div>
				<div class="wb_card_s"><?=($g == 'courier' ? 'жеткізу бойынша, өзі есептеледі' : count($grp[$g]).' адам')?></div>
			</a>
		<? endforeach ?>
	</div>

	<div class="zp_top">
		<div class="wb_btn wb_btn_green zp_add" data-user=""><i class="fal fa-plus"></i> Төлем жазу</div>
	</div>

	<!-- Топтар -->
	<? foreach (zp::$groups as $g => $gd): if ($g == 'other' && !$grp[$g]) continue; ?>
		<div class="wb_box wb_table" id="zp_<?=$g?>">
			<div class="wb_box_h">
				<span><i class="fal <?=$gd['icon']?>"></i> <?=$gd['name']?></span>
				<span class="zp_gsum"><?=zp::money($tot[$g])?></span>
			</div>
			<? if ($g == 'courier'): ?>
				<div class="zp_note"><i class="fal fa-info-circle"></i> Курьерлер ақшасын жеткізу бойынша (по факту) алады: тапсырыстағы жеткізу ақысы + компанияның жеткізу бағасы. Бұл сома ОПИУ-ға өзі түседі. Бонус не қосымша берсеңіз ғана «Төлем» басыңыз.</div>
			<? endif ?>
			<div class="wb_tr wb_th">
				<div class="wb_td wb_td_name">Жұмысшы</div>
				<? if ($g == 'courier'): ?>
					<div class="wb_td">Жеткізу</div>
					<div class="wb_td">Жеткізу бойынша</div>
					<div class="wb_td">Қосымша</div>
				<? else: ?>
					<div class="wb_td">Төлем саны</div>
				<? endif ?>
				<div class="wb_td">Барлығы</div>
				<div class="wb_td wb_td_act"></div>
			</div>
			<? foreach ($grp[$g] as $uid => $s):
				$p = (int)@$paid[$uid]['sum']; $e = $g == 'courier' ? (int)@$earn[$uid]['sum'] : 0; ?>
				<div class="wb_tr <?=(!empty($s['gone']) ? 'wb_tr_mut' : '')?>">
					<div class="wb_td wb_td_name">
						<div class="wb_name"><?=zp::h(trim($s['name'].' '.$s['surname']))?></div>
						<div class="wb_small"><?=(!empty($s['gone']) ? 'тізімнен шыққан' : '<span class="fr_phone">'.zp::h($s['phone']).'</span>')?></div>
					</div>
					<? if ($g == 'courier'): ?>
						<div class="wb_td" data-h="Жеткізу"><?=(int)@$earn[$uid]['orders']?></div>
						<div class="wb_td" data-h="Жеткізу бойынша"><?=zp::money($e)?></div>
						<div class="wb_td" data-h="Қосымша"><?=($p ? zp::money($p) : '<span class="wb_small">—</span>')?></div>
					<? else: ?>
						<div class="wb_td" data-h="Төлем саны"><?=(int)@$paid[$uid]['cnt']?></div>
					<? endif ?>
					<div class="wb_td" data-h="Барлығы"><b><?=zp::money($p + $e)?></b></div>
					<div class="wb_td wb_td_act">
						<? if (empty($s['gone'])): ?><div class="wb_btn wb_btn_light zp_add" data-user="<?=$uid?>"><i class="fal fa-plus"></i> Төлем</div><? endif ?>
					</div>
				</div>
			<? endforeach ?>
			<? if (!$grp[$g]): ?>
				<div class="wb_empty"><i class="fal <?=$gd['icon']?>"></i><div>Бұл лауазымда жұмысшы жоқ. <a href="/team/">Жұмысшылар</a> бетінен қосыңыз.</div></div>
			<? endif ?>
		</div>
	<? endforeach ?>

	<!-- Журнал -->
	<div class="wb_box wb_table">
		<div class="wb_box_h"><span><i class="fal fa-list-ul"></i> Берілген төлемдер</span></div>
		<div class="wb_tr wb_th">
			<div class="wb_td">Күні</div>
			<div class="wb_td wb_td_name">Жұмысшы</div>
			<div class="wb_td">Түрі</div>
			<div class="wb_td">Сома</div>
			<div class="wb_td wb_td_x"></div>
		</div>
		<? $n = 0; while ($r = mysqli_fetch_assoc($log)): $n++; ?>
			<div class="wb_tr" data-id="<?=$r['id']?>">
				<div class="wb_td" data-h="Күні"><?=wb::dt($r['pay_date'])?></div>
				<div class="wb_td wb_td_name">
					<div class="wb_name"><?=zp::h(trim($r['name'].' '.$r['surname']))?></div>
					<div class="wb_small"><?=zp::$groups[zp::group_of($r['positions_id'])]['name']?><?=($r['comment'] ? ' · '.zp::h($r['comment']) : '')?></div>
				</div>
				<div class="wb_td" data-h="Түрі"><?=@zp::$types[$r['type']]?><div class="wb_small"><?=@zp::$methods[$r['method']]?></div></div>
				<div class="wb_td" data-h="Сома"><b><?=zp::money($r['amount'])?></b></div>
				<div class="wb_td wb_td_x"><div class="wb_x zp_del" title="Өшіру"><i class="fal fa-trash-alt"></i></div></div>
			</div>
		<? endwhile ?>
		<? if (!$n): ?>
			<div class="wb_empty"><i class="fal fa-wallet"></i><div>Осы кезеңде төлем жазылмаған</div></div>
		<? endif ?>
	</div>

</div>

<!-- төлем жазу -->
<div class="pop_bl pop_bl2 zp_block">
	<div class="pop_bl_a zp_back"></div>
	<div class="pop_bl_c">
		<div class="head_c">
			<h4>Төлем жазу</h4>
			<div class="btn btn_dd zp_back"><i class="fal fa-times"></i></div>
		</div>
		<div class="pop_bl_cl">
			<div class="form_c">
				<div class="form_im">
					<div class="form_span">Жұмысшы:</div>
					<select class="form_txt zp_in_user">
						<option value="">Таңдаңыз</option>
						<? foreach (zp::$groups as $g => $gd): if (!$grp[$g]) continue; ?>
							<optgroup label="<?=$gd['name']?>">
								<? foreach ($grp[$g] as $uid => $s): if (!empty($s['gone'])) continue; ?>
									<option value="<?=$uid?>"><?=zp::h(trim($s['name'].' '.$s['surname']))?></option>
								<? endforeach ?>
							</optgroup>
						<? endforeach ?>
					</select>
				</div>
				<div class="form_im">
					<div class="form_span">Сома:</div>
					<input type="tel" class="form_txt zp_in_amount" placeholder="0">
					<i class="fal fa-tenge form_icon"></i>
				</div>
				<div class="form_im">
					<div class="form_span">Түрі:</div>
					<div class="tm_chips zp_in_type">
						<? foreach (zp::$types as $k => $v): ?><div class="tm_chip <?=($k == 1 ? 'tm_chip_act' : '')?>" data-val="<?=$k?>"><?=$v?></div><? endforeach ?>
					</div>
				</div>
				<div class="form_im">
					<div class="form_span">Қалай берілді:</div>
					<div class="tm_chips zp_in_method">
						<? foreach (zp::$methods as $k => $v): ?><div class="tm_chip <?=($k == 'cash' ? 'tm_chip_act' : '')?>" data-val="<?=$k?>"><?=$v?></div><? endforeach ?>
					</div>
				</div>
				<div class="form_im">
					<div class="form_span">Күні:</div>
					<input type="date" class="form_txt zp_in_date" value="<?=date('Y-m-d', time() - 6 * 3600)?>">
				</div>
				<div class="form_im">
					<div class="form_span">Түсініктеме:</div>
					<input type="text" class="form_txt zp_in_comment" placeholder="Мысалы: қазан айының жалақысы">
				</div>
			</div>
			<div class="form_c">
				<div class="form_im">
					<div class="btn zp_save"><i class="far fa-check"></i><span>Сақтау</span></div>
				</div>
			</div>
		</div>
	</div>
</div>

<? include "../block/footer.php"; ?>
