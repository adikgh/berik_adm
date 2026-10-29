<?php
// Client cabinet personal link. The same file (same secret) lives in
// my.berik.c/config and admin.pf.c/config. Changing the secret turns off all old links.
const CABINET_URL = 'http://my.berik.c';
const CLIENT_LINK_SECRET = '';

function client_link_sig(int $uid): string {
	return substr(hash_hmac('sha256', 'client-link|' . $uid, CLIENT_LINK_SECRET), 0, 32);
}

function client_link(int $uid): string {
	return CABINET_URL . '/in.php?u=' . $uid . '&t=' . client_link_sig($uid);
}
