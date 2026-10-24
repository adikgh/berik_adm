		<div class="">

            <div class="uc_ui uc_ui69">

                <div class="uc_uin_other">
                    <div class="form_im">
                    </div>
                </div>

				<!-- <div class="uc_uin_other">
					<select name="status" class="on_sort_catalog" >
                        <option data-id="" value="" <?=(!@$sort_catalog?'selected':'')?>>Все</option>
                        <? $catl = db::query("select * from product_catalog where company_id = '$company' and type = 'main'"); ?>
                        <? while ($catl_d = mysqli_fetch_assoc($catl)): ?>
                            <option data-id="" value="<?=$catl_d['id']?>" <?=(@$sort_catalog == $catl_d['id']?'selected':'')?>><?=$catl_d['name_kz']?></option>
                        <? endwhile ?>
					</select>
				</div> -->

			</div>

		</div>