<? 

   require 'db.php';
   require 'fun.php';
   require 'product.php';
   require 't.php';
   // require 'smsc_api.php';
   require 'var.php';
   require 'auth.php'; // пароль, cookie, WhatsApp коды

   class core {
      public static $user_ph = false;
      public static $user_ps = false;
      public static $user_data = array();

      public function __construct() {
         new db; new t; new fun; new product;
         $time = 3600 * 24 * 365;
         // ini_set('session.gc_maxlifetime', $time);
         // ini_set('session.cookie_lifetime', $time);
         session_set_cookie_params($time);
         session_start();
         date_default_timezone_set('Asia/Aqtau');
         $this->authorize();
      }

      private function authorize() {
         $user_ph = false;
         $user_ps = false;

         if (isset($_COOKIE['uph']) && isset($_COOKIE['ups'])) {
            $user_ph = substr(preg_replace('/\D/', '', (string)$_COOKIE['uph']), -10);
            $user_ps = $_COOKIE['ups'];
         }
         if ($user_ph && $user_ps) {
            $user = db::query("SELECT * FROM user WHERE `phone` = '$user_ph'");
            if (mysqli_num_rows($user)) {
               $user_data = mysqli_fetch_assoc($user);
               if ($user_data['right'] && auth_check($user_data, $user_ps)) {
                  self::$user_ph = $user_ph;
                  self::$user_data = $user_data;
               } else $this->user_unset();
            } else $this->user_unset();
         }
      }
   
      public function user_unset() {
         self::$user_ph = false;
         self::$user_data = array();
         setcookie('uph', '', time(), '/');
         setcookie('ups', '', time(), '/');
      }

   }


   // data
   $core = new core;
   $user = core::$user_data;
   $user_id = @$user['id'];
   $user_right = fun::user_staffw($user_id);

   // company: адам кіре алатын компаниялар. Создатель (1) — бәрі; басқалар — company_staff-тағы өз жолдары.
   // 2 не одан көп болса, шапкада таңдағыш шығады (block/menu.php), біреу болса — жасырын.
   $my_companies = [];
   if ($user_id) {
      if (@$user_right['positions_id'] == 1) $cq = db::query("select id, name, city, code from company order by number is null, number, id");
      else $cq = db::query("select c.id, c.name, c.city, c.code from company c join company_staff s on s.company_id = c.id
         where s.user_id = '".(int)$user_id."' and s.archive = 0 group by c.id order by c.number is null, c.number, c.id");
      while ($r = mysqli_fetch_assoc($cq)) $my_companies[(int)$r['id']] = $r;
   }
   $company = (int)@$user_right['company_id'];
   if (count($my_companies) > 1) {
      // сілтемеде компания id емес, code арқылы: ?company=n990 (базада #n990)
      if (isset($_GET['company'])) {
         $gc = '#'.preg_replace('/[^a-z0-9]/', '', strtolower((string)$_GET['company']));
         foreach ($my_companies as $cid => $cd) if (strtolower((string)$cd['code']) === $gc) { $_SESSION['company'] = $cid; break; }
      }
      if (isset($_SESSION['company']) && isset($my_companies[(int)$_SESSION['company']])) $company = (int)$_SESSION['company'];
      elseif (!isset($my_companies[$company])) $company = (int)key($my_companies);
      // таңдалған компаниядағы өз рөлі мен филиалы (Создательде компания жоқ, өзгермейді)
      if (@$user_right['company_id'] && $user_right['company_id'] != $company) {
         $rq = db::query("select * from company_staff where user_id = '".(int)$user_id."' and company_id = '$company' and archive = 0 order by positions_id is null, positions_id limit 1");
         if (mysqli_num_rows($rq)) $user_right = mysqli_fetch_array($rq);
      }
   } elseif (!$company && $my_companies) $company = (int)key($my_companies);

   // branch
   $branch = 1; 
   if (@$user_right['branch_id']) $branch = $user_right['branch_id'];
   else {
      if (isset($_GET['branch'])) if ($_GET['branch'] == 1 || $_GET['branch'] == 2) $_SESSION['branch'] = $_GET['branch'];
      if (isset($_SESSION['branch'])) $branch = $_SESSION['branch'];
   }
   
   // setting
   $site = mysqli_fetch_array(db::query("select * from `site` where id = 1"));






   // күн: ?time= болмаса, бүгінгі жұмыс күні (06:00-ден 06:00-ге дейін)
   $time_sort = date('Y-m-d', time() - 6 * 3600);
   $start_cdate = date('Y-m-d 06:00:00', strtotime($time_sort));
   $end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));

   if (@$_GET['time'] && strtotime($_GET['time'])) {
		$time_sort = date('Y-m-d', strtotime($_GET['time']));
		$start_cdate = date('Y-m-d 06:00:00', strtotime("$time_sort"));
		$end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));
	}

   // Касса: аяқталған жұмыс күнінің (06:00–06:00) толық есебі өзі сақталады — kassa/ksnap_lib.php, kassa/cron.php
   $ksnap_done = [];
   try { if ((include_once __DIR__.'/../kassa/ksnap_lib.php') && class_exists('ksnap', false)) $ksnap_done = ksnap::auto(); } catch (Throwable $e) { error_log('ksnap: '.$e->getMessage()); }
