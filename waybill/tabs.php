<div class="wb_bar">
	<div class="wb_tabs">
		<a class="wb_tab <?=($wb_tab == 'list' ? 'wb_tab_act' : '')?>" href="/waybill/"><i class="fal fa-file-invoice"></i> Накладнойлар</a>
		<a class="wb_tab <?=($wb_tab == 'suppliers' ? 'wb_tab_act' : '')?>" href="/waybill/suppliers.php"><i class="fal fa-truck"></i> Жеткізушілер</a>
		<a class="wb_tab <?=($wb_tab == 'stock' ? 'wb_tab_act' : '')?>" href="/waybill/stock.php"><i class="fal fa-boxes-alt"></i> Шикізат қалдығы</a>
	</div>
	<div class="wb_bar_btns">
		<a class="wb_btn wb_btn_light" href="/waybill/item.php?import=1"><i class="fal fa-file-import"></i> Файлдан жүктеу</a>
		<a class="wb_btn wb_btn_green" href="/waybill/item.php"><i class="fal fa-plus"></i> Жаңа накладной</a>
	</div>
</div>
