<? include "../../config/core.php"; ?>
   
   <!--  -->
   <? if (isset($_GET['pitem_d'])): ?>
		<? $id = strip_tags($_POST['id']); ?>

      <? $item_img = db::query("SELECT * FROM `product_img` WHERE product_item_id = '$id'"); ?>
      <? while ($item_img_d = mysqli_fetch_array($item_img)): ?>
         <div class="upl_logo">
            <div class="upl_logo_img lazy_img upl_logo_img_del" data-id="<?=$item_img_d['id']?>" style="background-image: url(/assets/uploads/products/<?=$item_img_d['img']?>)" data-src="/assets/uploads/products/<?=$item_img_d['img']?>"></div>
         </div>
      <? endwhile ?>

		<? exit(); ?>
	<? endif ?>