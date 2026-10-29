<? 

	class fun {
		
		function __construct() {}

		// кестеде баған бар ма (SQL әлі қосылмаса, бет құламасын)
		public static $cols = [];
		public static function has_col($table, $col) {
			$k = $table.'.'.$col;
			if (!isset(self::$cols[$k])) {
				$t = str_replace('`', '', $table); $c = mysqli_real_escape_string(db::$connect, $col);
				$q = @db::query("SHOW COLUMNS FROM `$t` LIKE '$c'");
				self::$cols[$k] = $q && mysqli_num_rows($q) > 0;
			}
			return self::$cols[$k];
		}


		

		// курьер ақшаны қалай алды: батырмалар (заказ карточкасы мен кассада)
		public static function cpay_block($o) {
			if (!self::has_col('retail_orders', 'courier_method')) return '';
			$sum = intval($o['pay_cash']);
			if ($sum <= 0) return '';
			$m = $o['courier_method'] ?? '';
			$c = intval($o['courier_cash'] ?? 0); $k = intval($o['courier_kaspi'] ?? 0);
			$f = function($v) { return number_format($v, 0, '', ' '); };
			$h = '<div class="cpay'.($m ? ' cpay_done' : '').'" data-id="'.$o['id'].'" data-sum="'.$sum.'">';
			$h .= '<div class="cpay_h"><i class="fal fa-hand-holding-usd"></i><span>Курьер '.$f($sum).' тг қалай алды?</span></div>';
			$h .= '<div class="cpay_b">';
			foreach (['cash' => ['Қолма-қол', 'fa-money-bill-wave'], 'kaspi' => ['Kaspi', 'fa-mobile-alt'], 'mix' => ['Аралас', 'fa-random']] as $key => $v)
				$h .= '<div class="cpay_i'.($m == $key ? ' cpay_act' : '').'" data-m="'.$key.'"><i class="fal '.$v[1].'"></i><span>'.$v[0].'</span></div>';
			$h .= '</div>';
			$h .= '<div class="cpay_r">'.($m ? 'Қолма-қол: <b>'.$f($c).' тг</b> · Kaspi: <b>'.$f($k).' тг</b>' : 'Әлі белгіленбеген').'</div>';
			$h .= '</div>';
			return $h;
		}

		// retail_order
		public static function retailo($id) {
			$sql = db::query("select * from retail_orders where id = '$id'");
			return mysqli_fetch_array($sql);
		}
		public static function retailo_quantity($id) {
			$sql = db::query("select * from retail_orders_products where order_id = '$id'"); $q = 0;
			if (mysqli_num_rows($sql)) { while ($res = mysqli_fetch_array($sql)) $q = $q + $res['quantity']; }
			return $q;
		}

		// retail_orders_products
		public static function retailop($id) {
			$sql = db::query("select * from retail_orders_products where id = '$id'");
			return mysqli_fetch_array($sql);
		}


		

		
		// retail_order
		public static function order_sts($id) {
			$sql = db::query("select * from retail_orders_status where id = '$id'");
			return mysqli_fetch_array($sql);
		}



		// retail_order
		public static function retail_returns($id) {
			$sql = db::query("select * from retail_returns where id = '$id'");
			return mysqli_fetch_array($sql);
		}
		public static function retail_returns_total($id) {
			$sql = db::query("select * from retail_returns_products where return_id = '$id'"); $q = 0;
			if (mysqli_num_rows($sql)) { while ($res = mysqli_fetch_array($sql)) $q = $q + ($res['quantity'] * $res['price']); }
			return $q;
		}
		public static function retail_returns_quantity($id) {
			$sql = db::query("select * from retail_returns_products where return_id = '$id'"); $q = 0;
			if (mysqli_num_rows($sql)) { while ($res = mysqli_fetch_array($sql)) $q = $q + $res['quantity']; }
			return $q;
		}

		// retail_orders_products
		public static function retail_returnsp($id) {
			$sql = db::query("select * from retail_returns_products where id = '$id'");
			return mysqli_fetch_array($sql);
		}












		// 
		public static function report_сourier($id, $uid) {
			$sql = db::query("select * from report_сourier where report_id = '$id' and сourier_id = '$uid'");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}






		// user
		public static function user($id) {
			$sql = db::query("select * from user where id = '$id'");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}
		public static function user_phone($phone) {
			$sql = db::query("select * from user where phone = '$phone'");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}

		// user management
		public static function user_staffw($id) {
			$sql = db::query("select * from user_staff where user_id = '$id' limit 1");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}


		// user staff
		public static function user_staff($id) {
			$sql = db::query("select * from user_staff where id = '$id' limit 1");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}
		public static function user_staff_name($id) {
			$sql = db::query("select * from user_staff where id = '$id'");
			return (mysqli_fetch_array($sql))['name_ru'];
		}
		public static function user_staff_positions($id) {
			$sql = db::query("select * from user_staff_positions where id = '$id'");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}

		// autor
		// public static function autor($id) {
		// 	$sql = db::query("select * from u_autor where id = '$id'");
		// 	return mysqli_fetch_array($sql);
		// }











		//  chat
		public static function chat($id) {
			$sql = db::query("select * from h_chat where user_id = '$id'");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}
		public static function chat2($id) {
			$sql = db::query("select * from h_chat where id = '$id'");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}
		public static function chat_txt_end($id) {
			$sql = db::query("select * from h_chat_item where chat_id = '$id' order by ins_dt desc limit 1");
			if (mysqli_num_rows($sql)) return mysqli_fetch_array($sql); else return 0;
		}
		public static function chat_view_sum() {
			$sql = db::query("select * from h_chat where view is null");
			return mysqli_num_rows($sql);
		}
		public static function chat_item_view_sum($id) {
			$sql = db::query("select * from h_chat_item where chat_id = '$id' and user_id is null and view is null");
			return mysqli_num_rows($sql);
		}
		public static function chat_view_sum2($id) {
			$sql = db::query("select * from h_chat where user_id = '$id'");
			if (mysqli_num_rows($sql)) {
				$chat_d = mysqli_fetch_array($sql); 
				$chat_id = $chat_d['id'];
				$chad_i = db::query("select * from h_chat_item where chat_id = '$chat_id' and user_id is not null and view is null");
				return mysqli_num_rows($chad_i);
			} else return 0;
		}




		






		







		// mall send
		public static function send_mail($mail, $txt) {
			$from = "info@aruacademy.kz";
			$subject = "Aru Academy";
			$headers = "From:" . $from. "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8";
		$mess = "<html>
							<head><title>$subject</title></head>
							<body>
								<div><b>$txt<b></div>
							</body>
						</html>";
			return mail($mail, $subject, $mess, $headers);
		}

















	}