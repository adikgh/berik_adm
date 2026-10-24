<? include "../config/core.php";
	include "cday_lib.php";

	if (!$user_id) { header('location: /'); exit(); }

	$cmp = (int)$company;
	$ready = cday::ready();
	$couriers = cday::couriers($cmp);

	// сүзгі: ай және курьер
	$month = (@$_GET['m'] && preg_match('/^\d{4}-\d{2}$/', $_GET['m'])) ? $_GET['m'] : date('Y-m', time() - 6 * 3600);
	$cid = (int)@$_GET['c'];
	$m_start = $month.'-01';
	$m_end = date('Y-m-t', strtotime($m_start));

	$rows = []; $by_c = []; $sum = [];
	$f = ['orders', 'total', 'pay_qr', 'pay_cash', 'salary', 'to_cashbox', 'expenses', 'cash', 'kaspi'];
	foreach ($f as $k) $sum[$k] = 0;
	$sum['days'] = 0;

	if ($ready) {
		$cc = $cid ? " and r.courier_id = '$cid'" : "";
		$q = db::query("select r.*, u.name as c_name, a.name as a_name from courier_day_report r
			left join user u on u.id = r.courier_id left join user a on a.id = r.accepted_by
			where r.company_id = '$cmp' and r.day between '$m_start' and '$m_end' $cc order by r.day desc, u.name asc");
		while ($r = mysqli_fetch_assoc($q)) {
			$rows[] = $r;
			$k = $r['courier_id'];
			if (!isset($by_c[$k])) { $by_c[$k] = ['name' => $r['c_name'], 'days' => 0]; foreach ($f as $x) $by_c[$k][$x] = 0; }
			$by_c[$k]['days']++; $sum['days']++;
			foreach ($f as $x) { $by_c[$k][$x] += $r[$x]; $sum[$x] += $r[$x]; }
		}
	}

	$cols = [
		'orders' => t::w('Qty', 'Саны'), 'total' => t::w('Total', 'Жалпы'), 'pay_qr' => t::w('Prepaid QR', 'Алдын ала (QR)'),
		'pay_cash' => t::w('Courier collected', 'Курьер алды'), 'salary' => t::w('Salary', 'Жалақы'), 'to_cashbox' => t::w('To cashbox', 'Кассаға'),
		'expenses' => t::w('Expenses', 'Шығын'), 'cash' => t::w('Cash', 'Қолма-қол'), 'kaspi' => t::w('Kaspi', 'Kaspi'),
	];
	$cell = function($k, $v) { return $k == 'orders' ? intval($v) : cday::fmt($v); };

	// site setting
	$menu_name = 'kassa';
	$page_name = 'Курьер есептері';
	$page_key = 'Courier reports';
	$css = ['kassa', 'kassa_cday'];
	$js = ['kassa_cday'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="uc_ui69 cd_top">
			<a class="btn btn_cl cd_back" href="/kassa/"><i class="fal fa-arrow-left"></i><span><?=t::w('Cashbox', 'Касса')?></span></a>
			<form class="cd_flt" method="get">
				<input class="form_dt" type="month" name="m" value="<?=$month?>" onchange="this.form.submit()">
				<select class="form_txt" name="c" onchange="this.form.submit()">
					<option value="0"><?=t::w('All couriers', 'Барлық курьер')?></option>
					<? foreach ($couriers as $k => $n): ?><option value="<?=$k?>" <?=($k == $cid ? 'selected' : '')?>><?=htmlspecialchars($n)?></option><? endforeach ?>
				</select>
			</form>
		</div>

		<? if (!$ready): ?>
			<div class="cd_note"><?=t::w('Table not created yet', 'Есеп кестесі әлі жасалмаған. sql/courier_day_01.sql файлын іске қосу керек.')?></div>
		<? elseif (!$rows): ?>
			<div class="cd_note"><?=t::w('No saved reports this month', 'Бұл айда сақталған есеп жоқ. Касса бетінде «Сақтау» батырмасын басыңыз.')?></div>
		<? else: ?>

			<div class="cd_h"><?=t::w('Month total by courier', 'Ай бойынша курьерлер')?></div>
			<div class="table4">
				<table>
					<thead>
						<tr>
							<td><b><?=t::w('Courier', 'Курьер')?></b></td>
							<td><?=t::w('Days', 'Күн')?></td>
							<? foreach ($cols as $k => $n): ?><td><?=$n?></td><? endforeach ?>
						</tr>
					</thead>
					<tbody>
						<? foreach ($by_c as $k => $c): ?>
							<tr>
								<td><a class="cd_lnk" href="?m=<?=$month?>&c=<?=$k?>"><?=htmlspecialchars($c['name'])?></a></td>
								<td><?=$c['days']?></td>
								<? foreach ($cols as $x => $n): ?><td><?=$cell($x, $c[$x])?></td><? endforeach ?>
							</tr>
						<? endforeach ?>
						<tr class="k_row_all">
							<td><?=t::w('All', 'Барлығы')?></td>
							<td><?=$sum['days']?></td>
							<? foreach ($cols as $x => $n): ?><td><?=$cell($x, $sum[$x])?></td><? endforeach ?>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="cd_h"><?=t::w('By day', 'Күндер бойынша')?></div>
			<div class="table4">
				<table>
					<thead>
						<tr>
							<td><b><?=t::w('Date', 'Күні')?></b></td>
							<td><?=t::w('Courier', 'Курьер')?></td>
							<? foreach ($cols as $k => $n): ?><td><?=$n?></td><? endforeach ?>
							<td><?=t::w('Accepted', 'Қабылдады')?></td>
						</tr>
					</thead>
					<tbody>
						<? foreach ($rows as $r): ?>
							<tr>
								<td><a class="cd_lnk" href="/kassa/?time=<?=$r['day']?>"><?=date('d.m.Y', strtotime($r['day']))?></a></td>
								<td><?=htmlspecialchars($r['c_name'])?></td>
								<? foreach ($cols as $x => $n): ?><td><?=$cell($x, $r[$x])?></td><? endforeach ?>
								<td class="cd_by"><?=htmlspecialchars((string)$r['a_name'])?> <span><?=date('d.m H:i', strtotime($r['upd_dt'] ?: $r['ins_dt']))?></span></td>
							</tr>
						<? endforeach ?>
					</tbody>
				</table>
			</div>

		<? endif ?>

		<br><br><br>
	</div>

<? include "../block/footer.php"; ?>
