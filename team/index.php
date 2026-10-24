<? include "../config/core.php";

	// жұмысшылар: тек кірген адам, басшылық (1 құрушы, 2 директор)
	if (!$user_id) { header('location: /'); exit(); }
	$can_edit = in_array(@$user_right['positions_id'], [1, 2]);

	// лауазымдар (құрушы тізімде шықпайды)
	$positions = [];
	$pq = db::query("select * from user_staff_positions where id != 1 order by number asc");
	while ($p = mysqli_fetch_assoc($pq)) $positions[$p['id']] = $p;
	// рет: Директор, Басқарушы, Маркетолог, Раздача, Кассир, Аспазшы, Поставщик, Курьер (Менеджер — ескі, тек бар болса шығады)
	$pos_kz = [2 => 'Директор', 3 => 'Басқарушы', 7 => 'Маркетолог', 8 => 'Раздача', 5 => 'Кассир', 9 => 'Аспазшы', 10 => 'Поставщик', 6 => 'Курьер', 4 => 'Менеджер'];
	$pos_icon = [2 => 'fa-user-tie', 3 => 'fa-user-cog', 7 => 'fa-bullhorn', 8 => 'fa-concierge-bell', 5 => 'fa-cash-register', 9 => 'fa-hat-chef', 10 => 'fa-truck', 6 => 'fa-car', 4 => 'fa-user-chart'];
	// базада әлі жоқ лауазымдар болса да, рет осылай тұрады
	$sorted = [];
	foreach ($pos_kz as $pid => $nm) if (isset($positions[$pid])) $sorted[$pid] = $positions[$pid];
	foreach ($positions as $pid => $p) if (!isset($sorted[$pid])) $sorted[$pid] = $p;
	$positions = $sorted;

	// точкалар
	$branches = [];
	$bq = db::query("select * from company_branch where company_id = '$company' order by number asc");
	while ($b = mysqli_fetch_assoc($bq)) $branches[$b['id']] = $b;

	// компания жұмысшылары лауазым бойынша топталады
	$groups = [];
	foreach ($positions as $pid => $p) $groups[$pid] = [];
	$sq = db::query("select s.id staff_id, s.positions_id, s.branch_id, s.ins_dt staff_dt, u.id uid, u.name, u.surname, u.phone, u.img
		from user_staff s inner join user u on u.id = s.user_id
		where s.company_id = '$company' and s.positions_id != 1 order by u.name asc");
	$all_cnt = 0;
	while ($r = mysqli_fetch_assoc($sq)) { $groups[$r['positions_id']][] = $r; $all_cnt++; }
	$has_manager = !empty($groups[4]);


	// site setting
	$menu_name = 'team';
	$page_name = 'Жұмысшылар';
	$page_key = 'Staff';
	$css = ['team'];
	$js = ['team'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<? if ($can_edit): ?>
			<div class="ucours_t">
				<div class="ucours_tl">
					<div class="ucours_tm">
						<div class="btn btn_cl tm_add_pop"><i class="fal fa-user-plus"></i><span>Жұмысшы қосу</span></div>
					</div>
				</div>
			</div>
		<? endif ?>

		<div class="tm_c">
			<? if (!$all_cnt): ?>
				<div class="ds_nr"><i class="fal fa-users"></i><p>Жұмысшы әлі жоқ</p></div>
			<? endif ?>

			<? foreach ($groups as $pid => $list): if (!$list) continue; ?>
				<div class="tm_grp">
					<div class="tm_grp_h"><i class="fal <?=($pos_icon[$pid] ?? 'fa-user')?>"></i><span><?=($pos_kz[$pid] ?? $positions[$pid]['name_ru'])?></span><b><?=count($list)?></b></div>
					<div class="tm_list">
						<? foreach ($list as $r): ?>
							<div class="tm_i" data-id="<?=$r['staff_id']?>">
								<div class="tm_av lazy_img" data-src="/assets/uploads/users/<?=htmlspecialchars($r['img'])?>"><? if (!$r['img']): ?><?=mb_strtoupper(mb_substr($r['name'], 0, 1))?><? endif ?></div>
								<div class="tm_info">
									<div class="tm_name"><?=htmlspecialchars($r['name'].' '.$r['surname'])?></div>
									<div class="tm_meta">
										<? if ($r['phone']): ?><span class="fr_phone"><?=$r['phone']?></span><? endif ?>
										<? if ($r['branch_id'] && isset($branches[$r['branch_id']])): ?><span><i class="fal fa-store"></i> <?=htmlspecialchars($branches[$r['branch_id']]['name'])?></span><? endif ?>
									</div>
								</div>
								<? if ($can_edit): ?>
									<div class="tm_btns">
										<div class="ctg_b tm_edit" title="Өзгерту"
											data-name="<?=htmlspecialchars($r['name'])?>" data-surname="<?=htmlspecialchars($r['surname'])?>"
											data-phone="<?=htmlspecialchars($r['phone'])?>" data-pos="<?=$r['positions_id']?>" data-branch="<?=intval($r['branch_id'])?>"><i class="fal fa-pen"></i></div>
										<div class="ctg_b ctg_b_red tm_del" title="Тізімнен шығару"><i class="fal fa-user-minus"></i></div>
									</div>
								<? endif ?>
							</div>
						<? endforeach ?>
					</div>
				</div>
			<? endforeach ?>
		</div>

	</div>

	<? if ($can_edit): ?>
	<!-- қосу / өзгерту -->
	<div class="pop_bl pop_bl2 tm_block">
		<div class="pop_bl_a tm_back"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4 class="tm_title">Жұмысшы қосу</h4>
				<div class="btn btn_dd tm_back"><i class="fal fa-times"></i></div>
			</div>
			<div class="pop_bl_cl">
				<div class="form_c">
					<div class="form_im">
						<div class="form_span">Аты:</div>
						<input type="text" class="form_txt tm_in_name" placeholder="Мысалы: Асан">
						<i class="fal fa-user form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span">Тегі:</div>
						<input type="text" class="form_txt tm_in_surname" placeholder="Міндетті емес">
						<i class="fal fa-user form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span">Телефон:</div>
						<input type="tel" class="form_txt tm_in_phone" placeholder="87001234567">
						<i class="fal fa-phone form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span">Лауазымы:</div>
						<div class="tm_chips tm_in_pos">
							<? foreach ($positions as $pid => $p): if ($pid == 4 && !$has_manager) continue; ?>
								<div class="tm_chip" data-val="<?=$pid?>"><i class="fal <?=($pos_icon[$pid] ?? 'fa-user')?>"></i> <?=($pos_kz[$pid] ?? $p['name_ru'])?></div>
							<? endforeach ?>
						</div>
					</div>
					<? if ($branches): ?>
						<div class="form_im">
							<div class="form_span">Точка:</div>
							<div class="tm_chips tm_in_branch">
								<div class="tm_chip" data-val="0">Барлығы</div>
								<? foreach ($branches as $bid => $b): ?>
									<div class="tm_chip" data-val="<?=$bid?>"><?=htmlspecialchars($b['name'])?></div>
								<? endforeach ?>
							</div>
						</div>
					<? endif ?>
					<div class="form_im">
						<div class="form_span">Кассаға кіру PIN (4 сан):</div>
						<input type="tel" class="form_txt tm_in_code" maxlength="4" placeholder="Өзгертпесеңіз бос қалдырыңыз">
						<i class="fal fa-key form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span">Админге кіру құпиясөзі:</div>
						<input type="text" class="form_txt tm_in_pass" placeholder="Өзгертпесеңіз бос қалдырыңыз">
						<i class="fal fa-lock form_icon"></i>
					</div>
				</div>
				<div class="form_c">
					<div class="form_im">
						<div class="btn tm_save ctg_save" data-id=""><i class="far fa-check"></i><span>Сақтау</span></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
