<?php
// Client cabinet personal link. The same file (same secret) lives in
// my.berik.c/config and adm.berik.c/config. Changing the secret turns off all old links.
// Address follows the host: live (*.gprog.kz) -> myb.gprog.kz, local -> my.berik.c
define('CABINET_URL', preg_match('~gprog\.kz$~i', (string)($_SERVER['HTTP_HOST'] ?? '')) ? 'https://myb.gprog.kz' : 'http://my.berik.c');
const CLIENT_LINK_SECRET = '';

function client_link_sig(int $uid): string {
	return substr(hash_hmac('sha256', 'client-link|' . $uid, CLIENT_LINK_SECRET), 0, 32);
}

function client_link(int $uid): string {
	return CABINET_URL . '/in.php?u=' . $uid . '&t=' . client_link_sig($uid);
}
