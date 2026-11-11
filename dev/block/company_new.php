<?
// «Компания тіркеу» терезесі (админдегі pop_bl стилі). Ашу: data-pop=".cn_block" батырмасы не /companies/?add=1
$cn_tar = dev::tariffs();
$cn_refs = dev::rows("select id, nick, name from company_ref where arh = 0 order by nick");
$cn_def = key($cn_tar);
?>
	<div class="pop_bl pop_bl2 cn_block">
		<div class="pop_bl_a pop_close"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4><?=t::w('Register company', 'Компания тіркеу')?></h4>
				<div class="btn btn_dd pop_close"><i class="fal fa-times"></i></div>
			</div>
			<form class="pop_bl_cl" data-post="/companies/get.php?company_add" autocomplete="off">
				<div class="form_c">
					<div class="form_im">
						<div class="form_span"><?=t::w('Company name', 'Компания атауы')?>:</div>
						<input type="text" name="name" class="form_txt" required maxlength="250" placeholder="Yaki Sushi">
						<i class="fal fa-building form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('City', 'Қала')?>:</div>
						<input type="text" name="city" class="form_txt" required maxlength="250" placeholder="Ақтау">
						<i class="fal fa-map-marker-alt form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('First branch name', 'Алғашқы филиал атауы')?>:</div>
						<input type="text" name="branch" class="form_txt" maxlength="250" placeholder="<?=dev::h(t::w('Same as company', 'Компания атауымен'))?>">
						<i class="fal fa-store form_icon"></i>
					</div>
				</div>
				<div class="form_c">
					<div class="form_im">
						<div class="form_span"><?=t::w('Director', 'Директор')?> · <?=t::w('Phone', 'Телефон')?>:</div>
						<input type="tel" name="phone" class="form_txt fr_phone" required placeholder="8 (700) 000-00-00">
						<i class="fal fa-phone form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('Name', 'Аты')?>:</div>
						<input type="text" name="uname" class="form_txt" maxlength="250">
						<i class="fal fa-user form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('Password', 'Пароль')?>:</div>
						<input type="text" name="password" class="form_txt" autocomplete="new-password" minlength="6">
						<i class="fal fa-lock form_icon"></i>
					</div>
					<p class="cn_note"><?=t::w('If the number already exists, that person becomes the director and the name and password are not changed.', 'Нөмір базада бар болса, сол адам директор болады, аты мен паролі өзгермейді.')?></p>
				</div>
				<div class="form_c">
					<div class="form_im">
						<div class="form_span"><?=t::w('Tariff', 'Тариф')?>:</div>
						<select name="tariff_id" class="form_txt cn_tar">
							<option value="trial" selected><?=t::w('Free trial', 'Тегін сынақ')?> · <?=sprintf(t::w('%d days', '%d күн'), dev::$trial_days)?> · 0 ₸</option>
							<? foreach ($cn_tar as $t): ?><option value="<?=$t['id']?>"><?=dev::h($t['name'])?> · <?=dev::money($t['price'])?>/<?=t::w('mo.', 'ай')?></option><? endforeach ?>
							<option value="">— <?=t::w('without tariff', 'тарифсіз')?> —</option>
						</select>
						<i class="fal fa-wallet form_icon"></i>
					</div>
					<div class="form_im cn_trial">
						<div class="form_span"><?=t::w('Trial with which tariff', 'Сынақ қай тарифпен')?>:</div>
						<select name="trial_tariff" class="form_txt">
							<? foreach ($cn_tar as $t): ?><option value="<?=$t['id']?>" <?=$t['id'] == $cn_def ? 'selected' : ''?>><?=dev::h($t['name'])?></option><? endforeach ?>
						</select>
						<i class="fal fa-gift form_icon"></i>
					</div>
					<p class="cn_note cn_trial"><?=sprintf(t::w('Free from today until %s. Given once per company.', 'Бүгіннен %s дейін тегін. Әр компанияға бір рет беріледі.'), date('d.m.Y', strtotime('+' . (dev::$trial_days - 1) . ' days')))?></p>
					<div class="form_im">
						<div class="form_span"><?=t::w('Who referred', 'Кім шақырды')?>:</div>
						<select name="ref_id" class="form_txt"><option value="">— <?=t::w('nobody', 'ешкім')?> —</option>
							<? foreach ($cn_refs as $r): ?><option value="<?=$r['id']?>"><?=dev::h($r['nick'] . ($r['name'] ? ' · ' . $r['name'] : ''))?></option><? endforeach ?>
						</select>
						<i class="fal fa-handshake form_icon"></i>
					</div>
				</div>
				<div class="form_c">
					<div class="form_im">
						<button type="submit" class="btn"><i class="far fa-check"></i><span><?=t::w('Register company', 'Компания тіркеу')?></span></button>
					</div>
				</div>
			</form>
		</div>
	</div>
	<script>
	(function () {
		var s = document.querySelector('.cn_tar');
		function t() { document.querySelectorAll('.cn_trial').forEach(function (x) { x.style.display = s.value === 'trial' ? '' : 'none'; }); }
		s.addEventListener('change', t); t();
	})();
	</script>
