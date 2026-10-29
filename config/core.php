<? 

   require 'db.php';
   require 'fun.php';
   require 'product.php';
   require 't.php';
   // require 'smsc_api.php';
   require 'var.php';

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
               if ($user_ps == $user_data['password'] && $user_data['right']) {
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

   // company
   $company = 0;
   if (@$user_right['company_id']) $company = $user_right['company_id'];
   else {
      if (isset($_GET['company'])) $_SESSION['company'] = $_GET['company'];
      if (isset($_SESSION['company'])) $company = $_SESSION['company'];
   }

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