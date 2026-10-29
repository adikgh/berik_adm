<? include "../config/core.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Жеткізу аймақтары: company_delivery_zone (branch_id бос болса барлық точкаға жарайды)
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

	$branches = [];
	$bq = db::query("select id, name from company_branch where company_id = '$cmp' order by number asc");
	while ($b = mysqli_fetch_assoc($bq)) $branches[$b['id']] = $b['name'];

	$f_branch = (int)@$_GET['branch'];
	if ($f_branch && !isset($branches[$f_branch])) $f_branch = 0;

	$where = "company_id = '$cmp'".($f_branch ? " and (branch_id = '$f_branch' or branch_id is null or branch_id = 0)" : "");
	$zones = [];
	$zq = db::query("select id, branch_id, name, price, sort from company_delivery_zone where $where order by sort asc, name asc");
	while ($z = mysqli_fetch_assoc($zq)) $zones[] = $z;

	// site setting
	$menu_name = 'delivery';
	$page_name = 'Жеткізу аймақтары';
	$css = ['clients', 'delivery'];
	$js = ['delivery'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page dz_page" data-csrf="<?=$_SESSION['csrf']?>">

	<div class="cl_bar">
		<? if (count($branches) >= 2): ?>
			<div class="cl_tabs">
				<a class="cl_tab <?=(!$f_branch ? 'cl_tab_act' : '')?>" href="/delivery/">Барлық точка</a>
				<? foreach ($branches as $bid => $bn): ?>
					<a class="cl_tab <?=($f_branch == $bid ? 'cl_tab_act' : '')?>" href="/delivery/?branch=<?=$bid?>"><?=htmlspecialchars($bn)?></a>
				<? endforeach ?>
			</div>
		<? else: ?><div></div><? endif ?>
		<div class="cl_range">
			<input type="search" class="cl_inp cl_search dz_q" placeholder="Көшені іздеу">
			<button class="cl_btn cl_btn_green dz_add" type="button"><i class="fal fa-plus"></i> Қосу</button>
		</div>
	</div>

	<!-- Қосу / өңдеу формасы -->
	<form class="cl_box dz_form dsp_n" autocomplete="off">
		<div class="cl_box_h"><div class="dz_form_h">Жаңа көше</div><button type="button" class="cl_btn cl_btn_light dz_cancel"><i class="fal fa-times"></i></button></div>
		<input type="hidden" name="id" value="0">
		<div class="dz_fields">
			<label class="dz_f dz_f_name">
				<span>Көше / аудан</span>
				<textarea name="name" class="cl_inp" rows="3" required placeholder="Әр жолға бір көше. Бірнеше көшені бірден қосуға болады"></textarea>
			</label>
			<label class="dz_f">
				<span>Жеткізу бағасы, тг</span>
				<input type="number" name="price" class="cl_inp" min="0" step="50" required>
			</label>
			<label class="dz_f">
				<span>Точка</span>
				<select name="branch_id" class="cl_inp">
					<option value="0">Барлық точка</option>
					<? foreach ($branches as $bid => $bn): ?>
						<option value="<?=$bid?>" <?=($f_branch == $bid ? 'selected' : '')?>><?=htmlspecialchars($bn)?></option>
					<? endforeach ?>
				</select>
			</label>
			<label class="dz_f dz_f_sort">
				<span>Реті</span>
				<input type="number" name="sort" class="cl_inp" value="0">
			</label>
		</div>
		<div class="dz_form_b">
			<button type="submit" class="cl_btn cl_btn_green dz_save"><i class="fal fa-check"></i> Сақтау</button>
		</div>
	</form>

	<!-- Тізім -->
	<div class="cl_box cl_table">
		<div class="cl_tr cl_th">
			<div class="cl_td cl_td_n">#</div>
			<div class="cl_td cl_td_name">Көше / аудан</div>
			<div class="cl_td">Точка</div>
			<div class="cl_td">Баға</div>
			<div class="cl_td dz_td_act"></div>
		</div>
		<? foreach ($zones as $n => $z): $bid = (int)$z['branch_id']; ?>
			<div class="cl_tr dz_i" data-id="<?=$z['id']?>" data-name="<?=htmlspecialchars($z['name'])?>" data-price="<?=(int)$z['price']?>" data-branch="<?=$bid?>" data-sort="<?=(int)$z['sort']?>">
				<div class="cl_td cl_td_n"><?=$n + 1?></div>
				<div class="cl_td cl_td_name"><div class="cl_name"><?=htmlspecialchars($z['name'])?></div></div>
				<div class="cl_td" data-h="Точка"><?=($bid && isset($branches[$bid]) ? htmlspecialchars($branches[$bid]) : '<span class="cl_small">Барлық точка</span>')?></div>
				<div class="cl_td" data-h="Баға"><b><?=number_format((int)$z['price'], 0, '', ' ')?> тг</b></div>
				<div class="cl_td dz_td_act">
					<button type="button" class="dz_ib dz_edit" title="Өзгерту"><i class="fal fa-pen"></i></button>
					<button type="button" class="dz_ib dz_del" title="Өшіру"><i class="fal fa-trash-alt"></i></button>
				</div>
			</div>
		<? endforeach ?>
		<div class="cl_empty dz_empty <?=($zones ? 'dsp_n' : '')?>"><?=($zones ? 'Көше табылмады' : 'Әзірге көше қосылмаған')?></div>
	</div>
	<div class="cl_small dz_total">Барлығы: <span><?=count($zones)?></span></div>

</div>

<? include "../block/footer.php"; ?>
