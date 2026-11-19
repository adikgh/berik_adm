<? include "../../config/core.php";
	if (!$user_id) { header('location: /'); exit(); }
	require_once __DIR__.'/../../config/claude.php';
	// ЖИ мәзір импорты: фото / PDF / Instagram → тек жоқ тағамдар қосылады → сегмент бойынша шикізат, дайындама, граммовка, сет құрамы
	$ai_on = claude::ready();
	$apify_on = defined('APIFY_TOKEN') && APIFY_TOKEN !== '';
	$cnt = (int)@mysqli_fetch_row(db::query("select count(*) from product where company_id = '".(int)$company."' and arh = 0"))[0];

	$menu_name = 'products';
	$pod_menu_name = 'main';
	$page_name = t::w('imp Menu import', 'Мәзірді ЖИ арқылы қосу');
	$page_key = 'Menu import';
	$css = ['products/import'];
	$js = ['products/import'];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c mi_page" data-ai="<?=$ai_on ? 1 : 0?>">
		<a class="mi_back" href="/products/"><i class="fal fa-arrow-left"></i> <?=t::w('imp Back to dishes', 'Тағамдарға қайту')?></a>
		<h1 class="mi_h"><i class="fal fa-magic"></i> <?=t::w('imp Menu import', 'Мәзірді ЖИ арқылы қосу')?></h1>
		<p class="mi_sub">ЖИ мәзірді толық оқиды. Бар <?=$cnt?> тағамға тимейді, тек жоқтарын қосады. Содан кейін таңдаған сегмент бойынша шикізатты, дайындамаларды, тағам граммовкасын және сеттердің ішін өзі толтырады.</p>
		<? if (!$ai_on): ?><div class="mi_warn">ЖИ қосылмаған: config/ai.php ішінде ANTHROPIC_API_KEY жоқ.</div><? endif ?>

		<!-- 1. қайдан -->
		<div class="mi_card mi_step1">
			<div class="mi_t"><span class="mi_n">1</span> Мәзір қайдан?</div>
			<div class="mi_seg mi_src">
				<button type="button" class="mi_seg_b mi_on" data-src="files"><i class="fal fa-image"></i> Фото / PDF</button>
				<button type="button" class="mi_seg_b" data-src="insta"><i class="fab fa-instagram"></i> Instagram</button>
				<button type="button" class="mi_seg_b" data-src="web"><i class="fal fa-globe"></i> Сайт / 2GIS / Wolt</button>
			</div>
			<div class="mi_box" data-box="files">
				<label class="mi_drop">
					<input type="file" class="mi_file" accept="image/*,application/pdf" multiple hidden>
					<i class="fal fa-cloud-upload"></i>
					<b>Мәзір фотоларын не PDF таңдаңыз</b>
					<small>Бірнеше бет болса бәрін бірге. PDF беттері суретке айналады (тағам фотосын қию үшін).</small>
				</label>
				<div class="mi_thumbs"></div>
			</div>
			<div class="mi_box" data-box="insta" hidden>
				<div class="form_im"><input type="text" class="form_txt mi_insta" placeholder="@akkaunt не instagram.com/akkaunt"><i class="fab fa-instagram form_icon"></i></div>
				<small class="mi_hint"><?=$apify_on ? 'Соңғы 40 пост пен фотолары оқылады (Apify, бір аккаунтқа шамамен 0.1 $). Аккаунт ашық болу керек.' : 'Apify қосылмаған: config/ai.php ішіне APIFY_TOKEN жазыңыз. Әзірге «Меню» хайлайтсының скриндерін Фото ретінде салыңыз.'?></small>
			</div>
			<div class="mi_box" data-box="web" hidden>
				<div class="form_im"><input type="text" class="form_txt mi_web" placeholder="Сайт сілтемесі не «Яки суши Шымкент»"><i class="fal fa-globe form_icon"></i></div>
				<small class="mi_hint">ЖИ интернеттен (сайт, Taplink, 2GIS, Wolt, Glovo, Яндекс Еда) мәзірді табады.</small>
			</div>
		</div>

		<!-- 2. сегмент -->
		<div class="mi_card">
			<div class="mi_t"><span class="mi_n">2</span> Қай сегмент? <small>граммовка мен шикізат сапасы осыған қарай</small></div>
			<div class="mi_seg mi_var">
				<button type="button" class="mi_seg_b" data-v="1"><b>Эконом</b><small>арзан шикізат, аз граммовка</small></button>
				<button type="button" class="mi_seg_b mi_on" data-v="2"><b>Орта</b><small>типтік граммовка</small></button>
				<button type="button" class="mi_seg_b" data-v="3"><b>Жоғары</b><small>премиум шикізат</small></button>
			</div>
			<label class="mi_ck"><input type="checkbox" class="mi_fill" checked> Қосқаннан кейін шикізат, дайындама, граммовка және сет құрамын автомат толтыру</label>
			<label class="mi_ck"><input type="checkbox" class="mi_photo" checked> Мәзір суретінен тағам фотосын қиып қою</label>
			<div class="mi_act"><button type="button" class="btn mi_read"<?=$ai_on ? '' : ' disabled'?>><i class="fal fa-magic"></i><span>ЖИ мәзірді оқысын</span></button></div>
		</div>

		<!-- 3. тексеру -->
		<div class="mi_card mi_step3" hidden>
			<div class="mi_t"><span class="mi_n">3</span> Тексеру <small class="mi_sum"></small></div>
			<div class="mi_note"></div>
			<div class="mi_tools">
				<label class="mi_ck"><input type="checkbox" class="mi_all" checked> Жаңаларының бәрі</label>
			</div>
			<div class="mi_tbl_w"><table class="mi_tbl"><thead><tr><th></th><th>Атауы</th><th>Каталог</th><th>Бағасы</th><th>Түрі</th><th>Күйі</th></tr></thead><tbody></tbody></table></div>
			<div class="mi_act"><button type="button" class="btn mi_add"><i class="fal fa-plus"></i><span>Мәзірге қосу</span></button></div>
		</div>

		<!-- 4. толтыру -->
		<div class="mi_card mi_step4" hidden>
			<div class="mi_t"><span class="mi_n">4</span> Толтыру</div>
			<div class="mi_bar"><div class="mi_bar_i"></div></div>
			<div class="mi_stat"></div>
			<div class="mi_log"></div>
			<div class="mi_done" hidden>
				<a class="btn" href="/products/"><i class="fal fa-list"></i><span>Тағамдар</span></a>
				<a class="btn btn_cl" href="/products/cost2/"><i class="fal fa-table"></i><span>Себес-2</span></a>
			</div>
		</div>
	</div>

<? include "../../block/footer.php"; ?>
