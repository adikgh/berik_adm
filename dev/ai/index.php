<?php include "../config/core.php";
	dev_only('ai');
	$menu = 'ai'; $page_title = t::w('AI costs', 'ЖИ шығыны'); $page_sub = t::w('Every Claude call in all companies: model, tokens, price', 'Барлық компанияда Claude әр шақырылғанда: модель, токен, баға');
	// Кезең: ?m=2026-11 (ай), ?y=2026 (жыл), ?all=1
	if (isset($_GET['all'])) { $from = '2000-01-01'; $to = '2100-01-01'; $title = t::w('All time', 'Барлық уақыт'); $prev = $next = null; $mode = 'all'; }
	elseif (isset($_GET['y'])) {
		$y = max(2020, min(2100, (int)$_GET['y'])); $from = "$y-01-01"; $to = ($y + 1) . "-01-01"; $title = "$y"; $mode = 'y';
		$prev = '?y=' . ($y - 1); $next = '?y=' . ($y + 1);
	} else {
		$m = preg_match('/^\d{4}-\d{2}$/', (string)@$_GET['m']) ? $_GET['m'] : date('Y-m'); $mode = 'm';
		$from = "$m-01"; $to = date('Y-m-01', strtotime("$from +1 month"));
		$title = dev::month(date('n', strtotime($from))) . ' ' . date('Y', strtotime($from));
		$prev = '?m=' . date('Y-m', strtotime("$from -1 month")); $next = '?m=' . date('Y-m', strtotime("$from +1 month"));
	}
	// Доллар бағамы (тек осы бетте көрсету үшін), сессияда сақталады
	if (isset($_GET['rate'])) $_SESSION['usd_rate'] = max(1, (float)str_replace(',', '.', $_GET['rate']));
	$rate = (float)($_SESSION['usd_rate'] ?? 520);
	$w = "l.ins_dt >= '$from' and l.ins_dt < '$to'";
	$tot = dev::row("select count(*) n, coalesce(sum(usd),0) usd, coalesce(sum(input_tokens + cache_read + cache_write),0) tin, coalesce(sum(output_tokens),0) tout from company_ai_log l where $w");
	$by_c = dev::rows("select l.company_id, c.name, count(*) n, sum(usd) usd, sum(input_tokens + cache_read + cache_write) tin, sum(output_tokens) tout, max(l.ins_dt) last_dt
		from company_ai_log l left join company c on c.id = l.company_id where $w group by l.company_id order by usd desc");
	$by_f = dev::rows("select feature, count(*) n, sum(usd) usd from company_ai_log l where $w group by feature order by usd desc");
	$by_m = dev::rows("select model, count(*) n, sum(usd) usd, sum(input_tokens + cache_read + cache_write) tin, sum(output_tokens) tout from company_ai_log l where $w group by model order by usd desc");
	$last = dev::rows("select l.*, c.name cname, u.name uname from company_ai_log l left join company c on c.id = l.company_id left join user u on u.id = l.user_id where $w order by l.id desc limit 50");
	$feat = ['waybill' => t::w('Reading waybills', 'Накладной оқу'), 'tpl_ai' => t::w('Recipe templates (AI)', 'Граммовка шаблондары (ЖИ)'), 'other' => t::w('Other', 'Басқа')];
	$usd = function ($x) { return '$' . number_format((float)$x, (float)$x < 1 ? 4 : 2, '.', ' '); };
	$kzt = function ($x) use ($rate) { return dev::money((float)$x * $rate); };
	$tk = function ($x) { $x = (int)$x; return $x >= 1e6 ? round($x / 1e6, 2) . ' M' : ($x >= 1e3 ? round($x / 1e3, 1) . ' K' : $x); };
?>
<? include "../block/header.php"; ?>
	<div class="tools">
		<form class="tabs" method="get" style="align-items:center;padding:5px 14px">
			<? if ($mode != 'all'): ?><input type="hidden" name="<?=$mode?>" value="<?=dev::h($mode == 'm' ? $m : $y)?>"><? endif ?>
			<label style="display:flex;gap:8px;align-items:center;color:var(--tx);font-size:15px">1 $ =<input name="rate" value="<?=$rate?>" style="width:90px;margin:0;min-height:40px" inputmode="decimal"> ₸</label>
		</form>
		<div class="tabs">
			<a href="?m=<?=date('Y-m')?>" class="<?=$mode == 'm' ? 'on' : ''?>"><?=t::w('Month', 'Ай')?></a>
			<a href="?y=<?=date('Y')?>" class="<?=$mode == 'y' ? 'on' : ''?>"><?=t::w('Year', 'Жыл')?></a>
			<a href="?all=1" class="<?=$mode == 'all' ? 'on' : ''?>"><?=t::w('All', 'Барлығы')?></a>
		</div>
		<div class="tabs">
			<? if ($prev): ?><a href="<?=$prev?>">‹</a><? endif ?>
			<a class="on"><?=$title?></a>
			<? if ($next): ?><a href="<?=$next?>">›</a><? endif ?>
		</div>
	</div>

	<div class="kpis">
		<div class="kpi o"><small><?=t::w('Cost', 'Шығын')?></small><b><?=$usd($tot['usd'])?></b></div>
		<div class="kpi"><small><?=t::w('In tenge', 'Теңгемен')?></small><b><?=$kzt($tot['usd'])?></b></div>
		<div class="kpi"><small><?=t::w('Number of calls', 'Шақыру саны')?></small><b><?=number_format($tot['n'], 0, '.', ' ')?></b></div>
		<div class="kpi"><small><?=t::w('Tokens: input / output', 'Токен: кіріс / шығыс')?></small><b style="font-size:20px"><?=$tk($tot['tin'])?> / <?=$tk($tot['tout'])?></b></div>
		<div class="kpi"><small><?=t::w('Average per call', 'Бір шақыру орташа')?></small><b><?=$tot['n'] ? $usd($tot['usd'] / $tot['n']) : '—'?></b></div>
	</div>

	<div class="card">
		<div class="card_h"><h2><?=t::w('By company', 'Компаниялар бойынша')?></h2></div>
		<? if (!$by_c): ?><div class="empty"><?=t::w('No AI use in this period', 'Бұл кезеңде ЖИ қолданылмаған')?></div><? else: ?>
		<div class="tbl_w"><table class="tbl">
			<thead><tr><th><?=t::w('Company', 'Компания')?></th><th class="num"><?=t::w('Calls', 'Шақыру')?></th><th class="num"><?=t::w('Input tokens', 'Кіріс токен')?></th><th class="num"><?=t::w('Output tokens', 'Шығыс токен')?></th><th class="num">$</th><th class="num">₸</th><th><?=t::w('Last', 'Соңғы')?></th></tr></thead>
			<tbody><? foreach ($by_c as $r): ?>
				<tr class="<?=$r['company_id'] ? 'lnk' : ''?>" <?=$r['company_id'] ? 'data-href="/companies/item.php?id=' . (int)$r['company_id'] . '"' : ''?>>
					<td><b><?=dev::h($r['name'] ?: t::w('No company', 'Компаниясыз'))?></b></td>
					<td class="num"><?=(int)$r['n']?></td><td class="num"><?=$tk($r['tin'])?></td><td class="num"><?=$tk($r['tout'])?></td>
					<td class="num"><b><?=$usd($r['usd'])?></b></td><td class="num"><?=$kzt($r['usd'])?></td>
					<td><?=dev::ago($r['last_dt'])?></td>
				</tr>
			<? endforeach ?></tbody>
		</table></div>
		<? endif ?>
	</div>

	<div class="grid g2 mt">
		<div class="card">
			<div class="card_h"><h2><?=t::w('By feature', 'Функция бойынша')?></h2></div>
			<? if (!$by_f): ?><div class="empty">—</div><? else: ?>
			<table class="tbl"><tbody><? foreach ($by_f as $r): ?>
				<tr><td><b><?=dev::h($feat[$r['feature']] ?? $r['feature'])?></b></td><td class="num"><?=(int)$r['n']?>×</td><td class="num"><b><?=$usd($r['usd'])?></b></td></tr>
			<? endforeach ?></tbody></table>
			<? endif ?>
		</div>
		<div class="card">
			<div class="card_h"><h2><?=t::w('By model', 'Модель бойынша')?></h2></div>
			<? if (!$by_m): ?><div class="empty">—</div><? else: ?>
			<table class="tbl"><tbody><? foreach ($by_m as $r): ?>
				<tr><td><span class="code"><?=dev::h($r['model'])?></span></td><td class="num"><?=(int)$r['n']?>×</td><td class="num"><?=$tk($r['tin'])?> / <?=$tk($r['tout'])?></td><td class="num"><b><?=$usd($r['usd'])?></b></td></tr>
			<? endforeach ?></tbody></table>
			<? endif ?>
		</div>
	</div>

	<div class="card mt">
		<div class="card_h"><h2><?=t::w('Latest calls', 'Соңғы шақырулар')?></h2><small><?=t::w('up to 50', '50-ге дейін')?></small></div>
		<? if (!$last): ?><div class="empty">—</div><? else: ?>
		<div class="tbl_w"><table class="tbl">
			<thead><tr><th><?=t::w('Time', 'Уақыты')?></th><th><?=t::w('Company', 'Компания')?></th><th><?=t::w('Who', 'Кім')?></th><th><?=t::w('Feature', 'Функция')?></th><th><?=t::w('Model', 'Модель')?></th><th class="num"><?=t::w('Input', 'Кіріс')?></th><th class="num"><?=t::w('From cache', 'Кэштен')?></th><th class="num"><?=t::w('Output', 'Шығыс')?></th><th class="num">$</th></tr></thead>
			<tbody><? foreach ($last as $r): ?>
				<tr>
					<td><?=date('d.m H:i', strtotime($r['ins_dt']))?></td><td><?=dev::h($r['cname'] ?: '—')?></td><td><?=dev::h($r['uname'] ?: '—')?></td>
					<td><?=dev::h($feat[$r['feature']] ?? $r['feature'])?></td><td><span class="code"><?=dev::h($r['model'])?></span></td>
					<td class="num"><?=$tk($r['input_tokens'] + $r['cache_write'])?></td><td class="num"><?=$tk($r['cache_read'])?></td><td class="num"><?=$tk($r['output_tokens'])?></td>
					<td class="num"><b><?=$usd($r['usd'])?></b></td>
				</tr>
			<? endforeach ?></tbody>
		</table></div>
		<? endif ?>
	</div>
	<p class="note"><?=t::w('Logged since 2026-11-10 (earlier calls were not saved). Prices use the Anthropic rates; the real bill is in console.anthropic.com.', 'Есеп 2026-11-10-нан бастап жазылады (бұрынғы шақырулар сақталмаған). Баға Anthropic тарифімен есептеледі, нақты шот console.anthropic.com-да.')?></p>
<? include "../block/footer.php"; ?>
