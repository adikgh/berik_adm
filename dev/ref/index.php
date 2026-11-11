<?php include "../config/core.php";
	dev_only('ref');
	$menu = 'ref'; $page_title = t::w('Referrers', 'Шақырушылар'); $page_sub = t::w('People who bring companies: personal link, bonus 30% (months 1-3), 10% (4-12), 5% (13-24), 1% (25+)', 'Компания әкелген адамдар: жеке сілтеме, бонус 30% (1-3 ай), 10% (4-12), 5% (13-24), 1% (25+)');
	$refs = dev::rows("select r.*, c.name cname from company_ref r left join company c on c.id = r.company_id order by r.arh, r.id desc");
	$cnt = dev::rows("select ref_id, count(*) n from company_sub where ref_id is not null group by ref_id", 'ref_id');
	$cs = dev::rows("select id, name from company order by name");
?>
<? include "../block/header.php"; ?>
	<div class="grid g12">
		<div class="card">
			<div class="card_h"><h2><?=t::w('List', 'Тізім')?></h2><input type="search" placeholder="<?=t::w('Search', 'Іздеу')?>" data-filter=".rf_t tbody tr" style="max-width:220px"></div>
			<? if (!$refs): ?><div class="empty"><?=t::w('No referrers yet. Add one on the right.', 'Әзірге шақырушы жоқ. Оң жақтан қосыңыз.')?></div><? else: ?>
			<div class="tbl_w"><table class="tbl rf_t">
				<thead><tr><th><?=t::w('Code', 'Код')?></th><th><?=t::w('Name', 'Аты')?></th><th class="num"><?=t::w('Company', 'Компания')?></th><th class="num"><?=t::w('Earned', 'Түсті')?></th><th class="num"><?=t::w('Paid out', 'Берілді')?></th><th class="num"><?=t::w('Balance', 'Қалдығы')?></th></tr></thead>
				<tbody><? foreach ($refs as $r): $b = dev::ref_balance($r['id']); $n = (int)@$cnt[$r['id']]['n']; ?>
					<tr class="lnk <?=$r['arh'] ? 'off' : ''?>" data-href="item.php?id=<?=$r['id']?>">
						<td><span class="code"><?=dev::h($r['nick'])?></span><?=$n >= dev::$top_partner ? ' <span class="st st_paid">' . t::w('Top', 'Топ') . '</span>' : ''?></td>
						<td class="nm"><b><?=dev::h($r['name'] ?: '—')?></b><small><?=dev::h($r['cname'] ? t::w('own company', 'өз компаниясы') . ': ' . $r['cname'] : $r['phone'])?></small></td>
						<td class="num"><?=$n?></td><td class="num"><?=dev::money($b['in'])?></td><td class="num"><?=dev::money($b['out'])?></td>
						<td class="num"><b><?=dev::money($b['left'])?></b></td>
					</tr>
				<? endforeach ?></tbody>
			</table></div>
			<? endif ?>
		</div>
		<div class="card">
			<div class="card_h"><h2><?=t::w('New referrer', 'Жаңа шақырушы')?></h2></div>
			<form data-post="get.php?ref_save">
				<label class="mb"><?=t::w('Code (nickname)', 'Код (никнейм)')?><input name="nick" required maxlength="40" placeholder="aidos" pattern="[A-Za-z0-9_]+" title="<?=t::w('Latin letters, digits and _ only', 'Тек латын әріптері, сан және _')?>"></label>
				<div class="f_row">
					<label><?=t::w('Name', 'Аты')?><input name="name" maxlength="250"></label>
					<label><?=t::w('Phone', 'Телефон')?><input type="tel" name="phone" class="fr_phone" inputmode="tel" placeholder="8 (700) 000-00-00"></label>
				</div>
				<label class="mb"><?=t::w('Own company (can pay with bonus)', 'Өз компаниясы (бонуспен төлей алады)')?><select name="company_id" class="bsel_f"><option value="">— <?=t::w('none', 'жоқ')?> —</option>
					<? foreach ($cs as $c): ?><option value="<?=$c['id']?>"><?=dev::h($c['name'])?></option><? endforeach ?>
				</select></label>
				<label class="mb"><?=t::w('Note', 'Ескертпе')?><input name="comment" maxlength="250"></label>
				<button class="btn btn_g" type="submit"><i class="fal fa-plus"></i><?=t::w('Add', 'Қосу')?></button>
				<p class="note"><?=t::w('Link', 'Сілтемесі')?>: <?=dev::h(dev::$sale_url)?>?code=… <?=t::w('Codes are unique.', 'Код қайталанбайды.')?></p>
			</form>
		</div>
	</div>
<? include "../block/footer.php"; ?>
