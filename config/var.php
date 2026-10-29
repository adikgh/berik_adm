<?

    // setting
    $ver = 4.009;

    // 
    $site_set = [
        'menu' => true,
        'search' => true,
        'footer' => true,
        // 'swiper' => false,
        // 'plyr' => false,
        // 'aos' => false,
    ];
    $scss = ['norm', 'main'];
    $sjs = ['norm', 'main'];
    $css = [];
    $js = [];
    $code = rand(1000, 9999);

    // 
	if (isset($_SESSION['ubd_menu']) == false) $_SESSION['ubd_menu'] = false;

    // деректер тілі (name_ru / name_kz): бұрынғыдай ru
    $lang = 'ru';

    // интерфейс тілі ($ui_lang): kz (стандарт), en, uz, ky, ru — POS-тағыдай, сөздер site_word кестесінде
    $langs = ['kz' => 'Қазақша', 'en' => 'English', 'uz' => 'O‘zbekcha', 'ky' => 'Кыргызча', 'ru' => 'Русский'];
    $langs_short = ['kz' => 'ҚАЗ', 'en' => 'ENG', 'uz' => 'UZB', 'ky' => 'KGZ', 'ru' => 'РУС'];
    $ui_lang = 'kz';
    if (isset($_GET['lang']) && isset($langs[$_GET['lang']])) {
        setcookie('ui_lang', $_GET['lang'], time() + 3600 * 24 * 365, '/');
        // ?lang= параметрін алып тастап, сол бетке қайта жібереміз
        $q = $_GET; unset($q['lang']);
        $back = explode('?', $_SERVER['REQUEST_URI'])[0].($q ? '?'.http_build_query($q) : '');
        if (!headers_sent()) { header('Location: '.$back); exit; }
        $ui_lang = $_GET['lang'];
    } elseif (isset($_COOKIE['ui_lang']) && isset($langs[$_COOKIE['ui_lang']])) $ui_lang = $_COOKIE['ui_lang'];

    // 
    $view_pr = null;
    if (isset($_GET['view_pr'])) $_SESSION['view_pr'] = $_GET['view_pr'];
    if (isset($_SESSION['view_pr']) && $_SESSION['view_pr'] == 'list') $view_pr = 2; else $view_pr = null;


    // date - time
    $date = date("Y-m-d", time());
    $time = date("H:i:s", time());
    $datetime = date('Y-m-d H:i:s', time());

    if ($time > "00:00:00" && $time < "06:00:00") {
        $start_cdate = date('Y-m-d 06:00:00', strtotime("$date -1 day"));
        $end_cdate = date('Y-m-d 06:00:00');
    } else {
        $start_cdate = date('Y-m-d 06:00:00');
        $end_cdate = date("Y-m-d 06:00:00", strtotime("$start_cdate +1 day"));
    }
  
    //  $start_cdate = date('Y-m-d 06:00:00', strtotime("$date -1 day"));
    //  $end_cdate = date("Y-m-d 06:00:00", strtotime("$start_cdate +1 day"));
  


    // url
    $url = $url_full = $_SERVER['REQUEST_URI'];
    $url = explode('?', $url);
    $url = $url[0];


    // 
    $token = "1581082911:AAEKW20w_-5V0Wx9tzhyThV2pjCgZtCjyc8";
    // $chat_id = "-1002461390168";