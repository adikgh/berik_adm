<? include "../config/core.php";
	include "lib.php";

	if (!$user_id || @$user_right['positions_id'] == 3) { header('location: /'); exit; }

	wb::install();
	$cmp = (int)$company;
	$w = wb::get((int)@$_GET['id'], $cmp);
	if (!$w) { header('location: /waybill/'); exit; }
	$items = wb::items($w['id']);
	$cname = mysqli_fetch_assoc(db::query("select name from company where id = '$cmp'"));
?><!DOCTYPE html>
<html lang="kk">
<head>
	<meta charset="UTF-8">
	<title>Накладной № <?=$w['number']?></title>
	<style>
		body{ font-family: Arial, sans-serif; font-size: 13px; color: #111; margin: 24px; }
		h1{ font-size: 20px; margin: 0 0 4px; }
		.sub{ color: #555; margin-bottom: 16px; }
		.meta{ display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin-bottom: 16px; }
		table{ width: 100%; border-collapse: collapse; }
		th, td{ border: 1px solid #999; padding: 6px 8px; text-align: left; }
		th{ background: #f0f0f0; }
		td.r, th.r{ text-align: right; }
		.tot td{ font-weight: bold; }
		.sign{ display: flex; justify-content: space-between; margin-top: 40px; }
		.sign div{ width: 45%; border-top: 1px solid #333; padding-top: 4px; color: #555; }
		.np{ margin-bottom: 16px; }
		@media print{ .np{ display: none; } body{ margin: 0; } }
	</style>
</head>
<body>
	<div class="np"><button onclick="window.print()">Басып шығару</button></div>
	<h1>Накладной № <?=$w['number']?></h1>
	<div class="sub"><?=wb::h(@$cname['name'])?> · <?=wb::dt($w['doc_date'])?> · <?=wb::status($w)[1]?></div>
	<div class="meta">
		<div>Жеткізуші: <b><?=wb::h($w['supplier_name'] ?: '—')?></b></div>
		<div>Жеткізушінің нөмірі: <b><?=wb::h($w['doc_no'] ?: '—')?></b></div>
		<? if ($w['comment']): ?><div>Түсініктеме: <?=wb::h($w['comment'])?></div><? endif ?>
	</div>
	<table>
		<tr><th>#</th><th>Атауы</th><th>Өлшемі</th><th class="r">Саны</th><th class="r">Бағасы</th><th class="r">Сомасы</th></tr>
		<? foreach ($items as $k => $i): ?>
			<tr>
				<td><?=$k + 1?></td>
				<td><?=wb::h($i['name'])?></td>
				<td><?=wb::h(fun::unit_norm($i['unit']))?></td>
				<td class="r"><?=wb::qty($i['quantity'])?></td>
				<td class="r"><?=wb::sum($i['price'])?></td>
				<td class="r"><?=wb::sum($i['sum'])?></td>
			</tr>
		<? endforeach ?>
		<tr class="tot"><td colspan="5" class="r">Барлығы</td><td class="r"><?=wb::sum($w['total'])?></td></tr>
		<tr><td colspan="5" class="r">Төленді</td><td class="r"><?=wb::sum($w['paid'])?></td></tr>
		<tr><td colspan="5" class="r">Қарыз</td><td class="r"><?=wb::sum(wb::debt($w))?></td></tr>
	</table>
	<div class="sign"><div>Тапсырды</div><div>Қабылдады</div></div>
</body>
</html>
