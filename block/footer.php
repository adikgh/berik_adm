	<!-- body end -->
	</div>

	<? if ($site_set['footer']): ?>
		<footer class="footer">
			<div class="bl_c">
				<div class="footer_b">
					<div class="footer_bl">© <?=$site['name']?>, 2026</div>
					<div class="footer_br">
						<a href="https://gprog.kz" target="_blank" class="gprog_bl">
							<span>#gprog-та</span>
							<div class="gprog_heart"><i class="fas fa-heart"></i></div>
							<span>жасалған</span>
							<div class="gprog_ab">
								<div class="gprog_lg"><div class="lazy_img" data-src="https://gprog.kz/assets/img/logo/logo.png"></div></div>
								<div class="gprog_h">G prog</div>
								<div class="gprog_p">Кәсіпті басқару, автоматтандыру</div>
							</div>
						</a>
					</div>
				</div>
			</div>
		</footer>
	<? endif ?>

	<!-- main js -->
	<? foreach ($sjs as $i): ?> <script src="/assets/js/<?=$i?>.js?v=<?=$ver?>"></script> <? endforeach ?>
	<? foreach ($js as $i): ?> <script src="/assets/js/<?=$i?>.js?v=<?=$ver?>"></script> <? endforeach ?>
	<script>window.ui_lang = <?=json_encode($ui_lang ?? 'kz')?>; window.bw = <?=json_encode(t::all(), JSON_UNESCAPED_UNICODE)?>;</script>
	<script src="/assets/js/berik_admin.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/berik_admin.js')?>"></script>
	<script src="/assets/js/berik_mobile.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/berik_mobile.js')?>"></script>
		
</body>
</html>

	<? include "modal.php"; ?>