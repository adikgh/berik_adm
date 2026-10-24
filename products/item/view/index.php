<? include "apcore.php";

   // 
	$pod_menu_name = 'main';
?>
<? include "../../../block/header.php"; ?>

	<div class="pitem">

      <? include "aheader.php"; ?>
		
      <div class="item_c">
			<div class="uc_u">
				<div class="uc_uh">
					<div class="uc_uh2">
						<div class="uc_uh_number">#</div>
						<div class="uc_uh_name">Склад</div>
						<div class="uc_uh_other">Количество</div>
						<div class="uc_uh_other">Комментарий</div>
					</div>
					<div class="uc_uh_cn"></div>
					<? if (mysqli_num_rows($pitem) > 1): ?> <div class="uc_uh_cn"></div> <? endif ?>
				</div>
				<div class="uc_uc">
					<? while ($buy_d = mysqli_fetch_assoc($pitem)): ?>
						<? $sum++; ?>

						<div class="uc_ui">
							<div class="uc_uil">
								<div class="uc_ui_number"><?=$sum?></div>
								<div class="uc_uiln">
									<div class="uc_uinu">
										<div class="uc_ui_name"><?=(product::pr_warehouses($buy_d['warehouses_id']))['name']?></div>
									</div>
								</div>
								<div class="uc_uin_other">
                           <input type="tel" class="uc_uin_calc_q fr_number3 view_updq_qn" data-id="<?=$buy_d['id']?>" value="<?=$buy_d['quantity']?>" data-lenght="1" />
                        </div>
								<div class="uc_uin_other"><?=$buy_d['comment']?></div>
							</div>
							<div class="uc_uin_cn view_upd_pop" data-id="<?=$buy_d['id']?>"><i class="fal fa-pen"></i></div>
							<? if (mysqli_num_rows($pitem) > 1): ?> <div class="uc_uin_cn pitem_btn_delete" data-id="<?=$buy_d['id']?>"><i class="fal fa-trash-alt"></i></div> <? endif ?>
						</div>
					<? endwhile ?>
				</div>
				<div class="uc_ub">
					<div class="btn btn_k view_add_pop">
						<i class="far fa-layer-plus"></i>
						<span>Добавить количество на новый склад</span>
					</div>
				</div>
			</div>
		</div>

	</div>

<? include "../../../block/footer.php"; ?>
	<? include "pop_add.php"; ?>