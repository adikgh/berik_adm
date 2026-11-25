<?php
// Кассаның Windows программасы (github.com/adikgh/berik_poswin): соңғы нұсқа (2026-11-25).
// Release-тегі файл аты нұсқамен өзгереді (BerikPOS-Setup-1.0.24.exe), сондықтан GitHub API-ден
// соңғы release-ті сұрап, .exe файлын табамыз. Жауап 30 минут кэште тұрады (API шегі сағатына 60 сұрау).
// API жауап бермесе: releases/latest беті (сол жерден қолмен жүктейді).
class poswin {

	const REPO = 'adikgh/berik_poswin';
	const TTL = 1800;

	public static function page_url() {
		return 'https://github.com/'.self::REPO.'/releases/latest';
	}

	private static function cache_file() {
		$d = @sys_get_temp_dir();
		if (!$d || !@is_writable($d)) $d = @session_save_path();
		if (!$d || !@is_writable($d)) return null;
		return rtrim($d, '/\\').'/berik_poswin_latest.json';
	}

	// ['ver' => '1.0.24', 'url' => '...exe', 'size' => байт, 'date' => 'Y-m-d'] немесе null
	public static function latest() {
		static $mem = false;
		if ($mem !== false) return $mem;
		$f = self::cache_file();
		if ($f && is_file($f) && filemtime($f) > time() - self::TTL) {
			$c = json_decode((string)@file_get_contents($f), true);
			if (is_array($c)) return $mem = ($c['url'] ?? '') ? $c : null;
		}
		$r = self::fetch();
		// сәтсіз болса 5 минуттан кейін қайта сұраймыз (бос жазба, бірақ ескісі бар болса соны қалдырамыз)
		if ($f) {
			if ($r) @file_put_contents($f, json_encode($r));
			else {
				$old = is_file($f) ? json_decode((string)@file_get_contents($f), true) : null;
				if (is_array($old) && !empty($old['url'])) { @touch($f, time() - self::TTL + 300); return $mem = $old; }
				@file_put_contents($f, '{}'); @touch($f, time() - self::TTL + 300);
			}
		}
		return $mem = $r;
	}

	private static function fetch() {
		$api = 'https://api.github.com/repos/'.self::REPO.'/releases/latest';
		$body = null;
		if (function_exists('curl_init')) {
			$ch = curl_init($api);
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 5,
				CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json', 'User-Agent: berik-admin'],
			]);
			$body = curl_exec($ch);
			if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) $body = null;
			curl_close($ch);
		} else {
			$ctx = stream_context_create(['http' => ['timeout' => 5, 'header' => "Accept: application/vnd.github+json\r\nUser-Agent: berik-admin\r\n"]]);
			$body = @file_get_contents($api, false, $ctx);
		}
		$j = $body ? json_decode($body, true) : null;
		if (!is_array($j) || empty($j['assets'])) return null;
		foreach ($j['assets'] as $a) {
			if (preg_match('~\.exe$~i', (string)@$a['name']) && !empty($a['browser_download_url'])) {
				return [
					'ver' => ltrim((string)@$j['tag_name'], 'vV'),
					'url' => $a['browser_download_url'],
					'size' => (int)@$a['size'],
					'date' => substr((string)@$j['published_at'], 0, 10),
				];
			}
		}
		return null;
	}

	// жүктеу сілтемесі: .exe табылса тура файл, болмаса releases/latest беті
	public static function download_url() {
		$l = self::latest();
		return $l ? $l['url'] : self::page_url();
	}
}
