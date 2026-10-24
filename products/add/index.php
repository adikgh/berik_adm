<?php include "../../../config/acore.php";

	// 
	if (!$user_id) header('location: /admin/');


	// site setting
	$menu_name = 'product_add';
	$site_set['header'] = true;
	$site_set['menu'] = true;
	$site_set['footer'] = false;
	$css = ['admin/add'];
	$js = ['admin/add'];
?>
<?php include "../../block/header.php"; ?>

	<div class="add">
		<div class="head_c"><h4>Добавить новый товар</h4></div>
		<div class="add_c">
			<div class="add_i">
				<div class="form_c">
					<div class="form_head">Общие данные:</div>
					<div class="form_im">
						<div class="">Артикул товара:</div>
						<i class="far fa-text form_icon"></i>
						<input type="text" class="form_im_txt name" placeholder="Введите артикул" data-lenght="3">
					</div>
					<div class="form_im">
						<div class="">Цена:</div>
						<i class="far fa-text form_icon"></i>
						<input type="text" class="form_im_txt name" placeholder="Введите артикул" data-lenght="3">
					</div>
				</div>
			</div>
			<div class="add_i">
				<div class="form_c">
					<div class="form_head">Количества товара:</div>
					<div class="form_im">
						<div class="">Артикул товара:</div>
						<i class="far fa-text form_icon"></i>
						<input type="text" class="form_im_txt name" placeholder="Введите артикул" data-lenght="3">
					</div>
				</div>
			</div>
		</div>
		<div class="add_b">
			<div class="btn product_add" data-cours-id="<?=$cours_id?>"><span>Добавить товара</span></div>
		</div>
	</div>

<?php include "../../block/footer.php"; ?>