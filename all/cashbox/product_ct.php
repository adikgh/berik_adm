   
<? include "../config/core.php"; ?>

<? if (isset($_GET['catalog'])): ?>
    <? 
        $catalog_id = @strip_tags($_POST['catalog_id']); 
        $_SESSION['catalog_id'] = $catalog_id;
    ?>
    <? $cashbox_id = @strip_tags($_POST['cashbox_id']); ?>

    <? $product = db::query("select * from product where catalog_id = '$catalog_id'"); ?>
    <? while ($product_d = mysqli_fetch_assoc($product)): ?>
        <div class="hup_rcbi cashbox_add" data-oid="<?=$cashbox_id?>" data-id="<?=$product_d['id']?>">
            <div class=""><?=$product_d['name_'.$lang]?></div>
            <div class=""><?=$product_d['price']?> тг</div>
        </div>
    <? endwhile ?>

    <? exit(); ?>
<? endif ?>
   
