<?php include "../config/core.php";
	dev_only('ref');
	$menu = 'ref';
	$ref = dev::row("select * from company_ref where id = '" . (int)@$_GET['id'] . "'");
	if (!$ref) { header('location: /ref/'); exit(); }
	$page_title = $ref['nick'];
	$page_back = ['/ref/', t::w('Referrers', 'Шақырушылар')];
	$page_sub = dev::h(trim($ref['name'] . ' · ' . $ref['phone'], ' ·')) . ($ref['arh'] ? ' · ' . t::w('deleted', 'өшірілген') : '');
	$cs = dev::rows("select id, name from company order by name");
	$edit = true;
?>
<? include "../block/header.php"; ?>
	<? include "../block/ref_view.php"; ?>
	<div class="card mt">
		<div class="card_h"><h2><?=t::w('Edit', 'Өзгерту')?></h2></div>
		<form data-post="get.php?ref_save">
			<input type="hidden" name="id" value="<?=$ref['id']?>">
			<div class="f_row">
				<label><?=t::w('Code', 'Код')?><input name="nick" value="<?=dev::h($ref['nick'])?>" required pattern="[A-Za-z0-9_]+"></label>
				<label><?=t::w('Name', 'Аты')?><input name="name" value="<?=dev::h($ref['name'])?>"></label>
				<label><?=t::w('Phone', 'Телефон')?><input type="tel" name="phone" class="fr_phone" inputmode="tel" placeholder="8 (700) 000-00-00" value="<?=dev::h($ref['phone'])?>"></label>
				<label><?=t::w('Own company', 'Өз компаниясы')?><select name="company_id" class="bsel_f"><option value="">— <?=t::w('none', 'жоқ')?> —</option>
					<? foreach ($cs as $c): ?><option value="<?=$c['id']?>" <?=$c['id'] == $ref['company_id'] ? 'selected' : ''?>><?=dev::h($c['name'])?></option><? endforeach ?>
				</select></label>
			</div>
			<label class="mb"><?=t::w('Note', 'Ескертпе')?><input name="comment" value="<?=dev::h($ref['comment'])?>"></label>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
				<button class="btn btn_l" type="submit"><?=t::w('Save', 'Сақтау')?></button>
				<button class="btn btn_r" type="button" data-act="get.php?ref_arh" data-id="<?=$ref['id']?>" data-ask="<?=$ref['arh'] ? t::w('Restore?', 'Қайта қосамыз ба?') : t::w('Delete the referrer? Bonus history is kept.', 'Шақырушыны өшіреміз бе? Бонус тарихы сақталады.')?>"><?=$ref['arh'] ? t::w('Restore', 'Қайта қосу') : t::w('Delete', 'Өшіру')?></button>
			</div>
		</form>
	</div>
<? include "../block/footer.php"; ?>
