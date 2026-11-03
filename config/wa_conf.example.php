<?php
// Үлгі. Нағыз баптау config/wa_conf.php файлында (git-ке кірмейді), оны админдегі «WhatsApp» беті өзі жазады.
return [
	'channel' => 'off',            // off | test | green | meta
	'green_url' => 'https://api.green-api.com',
	'green_instance' => '',        // idInstance
	'green_token' => '',           // apiTokenInstance
	'meta_token' => '',            // Meta System User access token
	'meta_phone_id' => '',         // Phone number ID
	'meta_template' => '',         // Authentication шаблон аты
	'meta_lang' => 'ru',
	'meta_button' => 1,            // шаблонда «Кодты көшіру» батырмасы бар
	'login' => ['admin' => 1, 'delivery' => 0, 'dist' => 0, 'ktch' => 0],
	'trust_days' => 365,
	'text' => 'BERIK POS: растау коды %s. Ешкімге айтпаңыз.',
];
