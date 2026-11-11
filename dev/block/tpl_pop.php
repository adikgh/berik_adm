<?
// Граммовка шаблонын өңдеу терезесі (админдегі pop_bl стилі). assets/js/dev_tpl.js толтырады.
?>
	<div class="pop_bl pop_bl2 gt_pop">
		<div class="pop_bl_a pop_close"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4 class="gt_ph"><?=t::w('Template', 'Шаблон')?></h4>
				<div class="btn btn_dd pop_close"><i class="fal fa-times"></i></div>
			</div>
			<div class="pop_bl_cl">
				<p class="gt_note"></p>
				<div class="form_c gt_f">
					<div class="form_im">
						<div class="form_span"><?=t::w('Title', 'Атауы')?>:</div>
						<input type="text" class="form_txt t_n" maxlength="250" placeholder="Филадельфия">
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('Category', 'Санаты')?>:</div>
						<input type="text" class="form_txt t_c" maxlength="100" list="gt_cats" placeholder="Роллы">
						<datalist id="gt_cats"></datalist>
					</div>
					<div class="form_im gt_fk">
						<div class="form_span"><?=t::w('Search words (separated by |)', 'Іздеу сөздері (| арқылы)')?>:</div>
						<input type="text" class="form_txt t_k" maxlength="500" placeholder="филадельфия|philadelphia">
					</div>
				</div>
				<div class="gt_iw">
					<table class="gt_it">
						<thead><tr><th><?=t::w('Ingredient', 'Құрам')?></th><th><?=t::w('Unit', 'Бірлік')?></th><th><?=t::w('Economy', 'Эконом')?></th><th><?=t::w('Standard', 'Стандарт')?></th><th><?=t::w('Premium', 'Премиум')?></th><th></th></tr></thead>
						<tbody></tbody>
						<tfoot><tr><td colspan="2" class="mut"><?=t::w('Portion weight', 'Порция салмағы')?></td><td class="gt_s0"></td><td class="gt_s1"></td><td class="gt_s2"></td><td></td></tr></tfoot>
					</table>
				</div>
				<button type="button" class="gt_add"><i class="fal fa-plus"></i> <?=t::w('Ingredient', 'Құрам')?></button>
				<div class="gt_btns">
					<button type="button" class="btn btn_g gt_sv"><?=t::w('Save', 'Сақтау')?></button>
					<button type="button" class="btn btn_g gt_ok"><i class="fal fa-check"></i><?=t::w('Approve', 'Бекіту')?></button>
					<button type="button" class="btn btn_r gt_dl"><?=t::w('Delete', 'Өшіру')?></button>
				</div>
			</div>
		</div>
	</div>
	<style>
		.gt_pop .pop_bl_c{ width: 860px; max-width: calc(100% - 24px); }
		.gt_pop .pop_bl_cl{ max-height: calc(100% - 84px); overflow-y: auto; }
		.gt_note{ margin: 0 0 12px; padding: 10px 14px; border-radius: 12px; background: #f0f7ff; color: #1e4f8a; font-size: 13px; line-height: 1.4; display: none; }
		.gt_note.on{ display: block; }
		.gt_f{ display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px; }
		.gt_f .form_im{ margin-top: 0; margin-bottom: 12px; }
		.gt_f .gt_fk{ grid-column: 1 / -1; }
		.gt_iw{ overflow-x: auto; }
		.gt_it{ width: 100%; border-collapse: collapse; font-size: 14px; min-width: 560px; }
		.gt_it th{ text-align: left; font-size: 12px; color: var(--bp-muted, #8a8f98); font-weight: 600; padding: 6px 4px; }
		.gt_it td{ padding: 3px 4px; }
		.gt_it td:nth-child(n+3){ width: 92px; }
		.gt_it td:nth-child(2){ width: 72px; }
		.gt_it td:last-child{ width: 30px; text-align: center; }
		.gt_it input, .gt_it select{ width: 100%; height: 40px; border: 1px solid #ececec; border-radius: 10px; padding: 0 10px; font: inherit; background: #fafafa; }
		.gt_it input:focus, .gt_it select:focus{ outline: 0; border-color: var(--bp-green, #16a34a); background: #fff; }
		.gt_it .i_q{ text-align: right; }
		.gt_it tfoot td{ font-size: 13px; font-weight: 700; text-align: right; padding-top: 8px; }
		.gt_it tfoot td.mut{ text-align: left; font-weight: 500; }
		.gt_x{ cursor: pointer; color: #c44; font-size: 18px; }
		.gt_add{ margin-top: 8px; border: 0; background: none; color: var(--bp-green, #16a34a); font: inherit; font-weight: 600; cursor: pointer; padding: 6px 0; }
		.gt_btns{ display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
		.gt_btns .btn{ border: 0; font: inherit; cursor: pointer; }
		.gt_btns .gt_dl{ background: #fdecec; color: #c62828; }
		.gt_btns .gt_dl:hover{ background: #f9d6d6; }
		@media (max-width: 760px){
			.gt_f{ grid-template-columns: 1fr; }
			.gt_it{ min-width: 0; }
			.gt_it thead{ display: none; }
			.gt_it tr{ display: grid; grid-template-columns: 64px 1fr 1fr 1fr 24px; gap: 4px; align-items: center; padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
			.gt_it td{ padding: 0; width: auto !important; }
			.gt_it tbody td:first-child{ grid-column: 1 / -1; }
			.gt_it tfoot td.mut{ grid-column: 1 / -1; }
			.gt_it tfoot td.gt_s0{ grid-column: 2; }
			.gt_it tfoot td:last-child{ display: none; }
		}
	</style>
