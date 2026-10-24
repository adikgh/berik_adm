<? include "../../config/core.php";

	//
	if (!$user_id) { header('location: /'); exit(); }

	// каталогтар: компанияның өзінікі, архивтегілер шықпайды, number бойынша ретпен
	$catalog = db::query("select * from product_catalog where company_id = '$company' and (arh is null or arh = 0) order by number asc, id asc");

	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'catalog';
	$page_name = 'Каталог';
	$page_key = 'Catalog';
	$css = ['products/main', 'products/catalog'];
	$js = ['products/main', 'products/catalog'];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c">

		<div class="ucours_t">
			<div class="ucours_tl">
				<div class="ucours_tm">
					<div class="btn btn_cl ctg_add_pop"><i class="fal fa-plus"></i><span>Каталог қосу</span></div>
				</div>
			</div>
		</div>

		<!-- a header -->
		<? include "../aheader.php"; ?>

		<div class="ctg_c">
			<div class="ctg_hint"><i class="fal fa-arrows-alt-v"></i><span>Ретін ауыстыру үшін жолды сүйреңіз немесе ↑ ↓ басыңыз. Рет өзі сақталады.</span></div>

			<? if (mysqli_num_rows($catalog)): ?>
				<div class="ctg_list">
					<? $n = 0; while ($c = mysqli_fetch_assoc($catalog)): $n++; ?>
						<? $pr_ct = mysqli_fetch_assoc(db::query("select count(*) c from product where catalog_id = '$c[id]' and arh = 0"))['c']; ?>
						<div class="ctg_i" draggable="true" data-id="<?=$c['id']?>">
							<div class="ctg_drag"><i class="fal fa-grip-vertical"></i></div>
							<div class="ctg_num"><?=$n?></div>
							<div class="ctg_clr" style="background:<?=htmlspecialchars($c['color'] ?: '#666')?>"></div>
							<div class="ctg_name">
								<div class="ctg_name_kz"><?=htmlspecialchars($c['name_kz'])?></div>
								<? if ($c['name_ru'] && $c['name_ru'] != $c['name_kz']): ?><div class="ctg_name_ru"><?=htmlspecialchars($c['name_ru'])?></div><? endif ?>
							</div>
							<div class="ctg_cnt"><?=$pr_ct?> тағам</div>
							<div class="ctg_btns">
								<div class="ctg_b ctg_up" title="Жоғары"><i class="fal fa-arrow-up"></i></div>
								<div class="ctg_b ctg_down" title="Төмен"><i class="fal fa-arrow-down"></i></div>
								<div class="ctg_b ctg_edit" title="Өзгерту"
									data-name-kz="<?=htmlspecialchars($c['name_kz'])?>"
									data-name-ru="<?=htmlspecialchars($c['name_ru'])?>"
									data-color="<?=htmlspecialchars($c['color'] ?: '#666666')?>"><i class="fal fa-pen"></i></div>
								<div class="ctg_b ctg_b_red ctg_del" title="Архивке" data-cnt="<?=$pr_ct?>"><i class="fal fa-archive"></i></div>
							</div>
						</div>
					<? endwhile ?>
				</div>
			<? else: ?>
				<div class="ds_nr"><i class="fal fa-ghost"></i><p>Каталог әлі жоқ</p></div>
			<? endif ?>
		</div>

	</div>

	<!-- add / edit popup -->
	<div class="pop_bl pop_bl2 ctg_block">
		<div class="pop_bl_a ctg_back"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4 class="ctg_title">Каталог қосу</h4>
				<div class="btn btn_dd ctg_back"><i class="fal fa-times"></i></div>
			</div>
			<div class="pop_bl_cl">
				<div class="form_c">
					<div class="form_im">
						<div class="form_span">Атауы (қазақша):</div>
						<input type="text" class="form_txt ctg_name_kz_in" placeholder="Мысалы: Сеттер">
						<i class="fal fa-text form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span">Атауы (орысша):</div>
						<input type="text" class="form_txt ctg_name_ru_in" placeholder="Бос қалса, қазақшасы жазылады">
						<i class="fal fa-text form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span">Түсі (кассадағы батырма):</div>
						<div class="ctg_colors">
							<? foreach (['#698cc8','#6ac0f3','#7d6dad','#a2d246','#e7707d','#E3B505','#0EA768','#1f75a8','#666666'] as $clr): ?>
								<div class="ctg_color" data-color="<?=$clr?>" style="background:<?=$clr?>"></div>
							<? endforeach ?>
							<input type="color" class="ctg_color_in" value="#698cc8">
						</div>
					</div>
				</div>
				<div class="form_c">
					<div class="form_im">
						<div class="btn ctg_save" data-id=""><i class="far fa-check"></i><span>Сақтау</span></div>
					</div>
				</div>
			</div>
		</div>
	</div>

<? include "../../block/footer.php"; ?>
