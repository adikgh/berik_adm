<div class="wb_bar">
	<div class="wb_tabs">
		<a class="wb_tab <?=($wb_tab == 'list' ? 'wb_tab_act' : '')?>" href="/waybill/"><i class="fal fa-file-invoice"></i> Накладнойлар</a>
		<a class="wb_tab <?=($wb_tab == 'group' ? 'wb_tab_act' : '')?>" href="/waybill/group.php"><i class="fal fa-layer-group"></i> Топтап</a>
		<a class="wb_tab <?=($wb_tab == 'prices' ? 'wb_tab_act' : '')?>" href="/waybill/prices.php"><i class="fal fa-chart-line"></i> Бағалар</a>
		<a class="wb_tab <?=($wb_tab == 'suppliers' ? 'wb_tab_act' : '')?>" href="/waybill/suppliers.php"><i class="fal fa-truck"></i> Жеткізушілер</a>
		<a class="wb_tab <?=($wb_tab == 'stock' ? 'wb_tab_act' : '')?>" href="/waybill/stock.php"><i class="fal fa-boxes-alt"></i> Шикізат қалдығы</a>
		<a class="wb_tab <?=($wb_tab == 'revision' ? 'wb_tab_act' : '')?>" href="/waybill/revision.php"><i class="fal fa-clipboard-list-check"></i> Ревизия</a>
	</div>
	<div class="wb_bar_btns">
		<a class="wb_btn wb_btn_light" href="/waybill/item.php?import=1"><i class="fal fa-file-import"></i> Файлдан жүктеу</a>
		<a class="wb_btn wb_btn_green" href="/waybill/item.php"><i class="fal fa-plus"></i> Жаңа накладной</a>
	</div>
</div>
