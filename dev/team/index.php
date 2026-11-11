<?php include "../config/core.php";
	dev_only('team');
	$menu = 'team'; $page_title = t::w('Dev team', 'Команда'); $page_sub = t::w('Who can enter this panel. A separate list from company staff.', 'Бұл панельге кім кіреді. Компания жұмысшыларынан бөлек тізім.');
	$st = dev::rows("select d.*, u.name, u.surname, u.phone, r.nick from dev_staff d left join user u on u.id = d.user_id left join company_ref r on r.id = d.ref_id order by d.arh, d.role, d.id");
	$refs = dev::rows("select r.id, r.nick, r.name, (select count(*) from company_sub s where s.ref_id = r.id) n from company_ref r where r.arh = 0 order by n desc, r.nick");
	$what = [
		2 => t::w('Sees companies (no payments), AI costs, recipe templates', 'Компанияларды көреді (төлем енгізбейді), ЖИ шығыны, граммовка шаблондары'),
		3 => t::w('Companies, registering, subscriptions and payments, payment list', 'Компаниялар, тіркеу, жазылым мен төлем енгізу, төлемдер тізімі'),
		4 => t::w('Only the companies they brought, their bonus and link', 'Тек өзі әкелген компаниялар, бонусы мен сілтемесі'),
		5 => t::w('Sees companies, creates referrers and links', 'Компанияларды көреді, шақырушылар мен сілтемелер жасайды'),
	];
?>
<? include "../block/header.php"; ?>
	<div class="grid g12">
		<div class="card">
			<div class="card_h"><h2><?=t::w('Who has access', 'Кіре алатындар')?></h2></div>
			<div class="tbl_w"><table class="tbl">
				<thead><tr><th><?=t::w('Person', 'Адам')?></th><th><?=t::w('Role', 'Рөлі')?></th><th></th></tr></thead>
				<tbody><? foreach ($st as $s): ?>
					<tr class="<?=$s['arh'] ? 'off' : ''?>">
						<td class="nm"><b><?=dev::h(trim($s['name'] . ' ' . $s['surname']))?></b><small><?=dev::h($s['phone'])?><?=$s['nick'] ? ' · ' . t::w('code', 'код') . ' ' . dev::h($s['nick']) : ''?></small></td>
						<td><?=dev::h(dev::$roles[$s['role']] ?? '?')?></td>
						<td class="num"><? if ((int)$s['user_id'] !== $user_id): ?><button class="btn btn_s <?=$s['arh'] ? 'btn_l' : 'btn_r'?>" data-act="get.php?arh" data-id="<?=$s['id']?>" data-ask="<?=$s['arh'] ? t::w('Give access back?', 'Қайта кіргіземіз бе?') : t::w('Close access?', 'Кіруін жабамыз ба?')?>"><?=$s['arh'] ? t::w('Give back', 'Қайтару') : t::w('Close', 'Жабу')?></button><? endif ?></td>
					</tr>
				<? endforeach ?></tbody>
			</table></div>
		</div>
		<div class="card">
			<div class="card_h"><h2><?=t::w('Add a person', 'Адам қосу')?></h2></div>
			<form data-post="get.php?add">
				<label class="mb"><?=t::w('Phone', 'Телефон')?><input type="tel" name="phone" class="fr_phone" inputmode="tel" required placeholder="8 (700) 000-00-00"></label>
				<label class="mb"><?=t::w('Role', 'Рөлі')?><select name="role" class="tm_role bsel_f">
					<? foreach (dev::$roles as $k => $r): if ($k == 1) continue; // иесі біреу, қосылмайды ?><option value="<?=$k?>"><?=$r?></option><? endforeach ?>
				</select></label>
				<label class="mb tm_ref" hidden><?=t::w('Referral code', 'Шақыру коды')?><select name="ref_id" class="bsel_f"><option value="">—</option>
					<? foreach ($refs as $r): ?><option value="<?=$r['id']?>"><?=dev::h($r['nick'] . ($r['name'] ? ' · ' . $r['name'] : ''))?> · <?=(int)$r['n']?> <?=t::w('companies', 'компания')?><?=$r['n'] >= dev::$top_partner ? ' ★' : ''?></option><? endforeach ?>
				</select></label>
				<p class="note" style="margin:0 0 10px"><?=t::w('If the number is new, a new person is created: enter a name and a password.', 'Нөмір базада жоқ болса, жаңа адам ашылады: атын және парольін жазыңыз.')?></p>
				<div class="f_row">
					<label><?=t::w('Name', 'Аты')?><input name="name"></label>
					<label><?=t::w('Password', 'Пароль')?><input name="password" type="text" autocomplete="new-password" minlength="6"></label>
				</div>
				<button class="btn btn_g" type="submit"><i class="fal fa-plus"></i><?=t::w('Add', 'Қосу')?></button>
			</form>
			<dl class="dl" style="margin-top:16px;font-size:13px">
				<? foreach ($what as $k => $w): ?><dt><?=dev::$roles[$k]?></dt><dd style="font-weight:600"><?=$w?></dd><? endforeach ?>
			</dl>
			<p class="note"><?=sprintf(t::w('Top partner: brought more than %d companies (★).', 'Топ серіктес: %d-нан көп компания әкелгендер (★).'), dev::$top_partner)?></p>
		</div>
	</div>
<script>
// безендірілген тізім (bsel) 'change'-ті jQuery арқылы жібереді
$('.tm_role').on('change', function () { $('.tm_ref').prop('hidden', this.value !== '4'); });
</script>
<? include "../block/footer.php"; ?>
