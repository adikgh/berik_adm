<? include "../config/core.php";
   
   // returns
	if(isset($_GET['return_sn'])) {
		$id = strip_tags($_POST['id']);
      $order_d = fun::retailo($id);
      $total = $order_d['total'];
      $quantity_all = $order_d['quantity'];

      $return_id = (mysqli_fetch_assoc(db::query("SELECT * FROM `retail_returns` order by id desc")))['id'] + 1;
      $ins = db::query("INSERT INTO `retail_returns`(`id`, `user_id`, `returns`, `total`, `quantity`) VALUES ('$return_id', '$user_id', 1, '$total', '$quantity_all')");
      $upd = db::query("UPDATE retail_orders SET returned = 1 WHERE id = '$id'");

      $op = db::query("select * from retail_orders_products where order_id = '$id'");
		if (mysqli_num_rows($op)) {
         while ($op_d = mysqli_fetch_array($op)) {
            $product_id = $op_d['product_id'];
            $item_id = $op_d['product_item_id'];
            $quantity = $op_d['quantity'];
            $price = $op_d['price'];
            $ins = db::query("INSERT INTO `retail_returns_products`(`return_id`, `product_id`, `product_item_id`, `quantity`, `price`) VALUES ('$return_id', '$product_id', '$item_id', '$quantity', '$price')");
            
            $pq_id = (mysqli_fetch_array(db::query("select * from product_item_quantity where item_id = '$item_id' limit 1")))['id'];
            $upd = db::query("UPDATE `product_item_quantity` SET quantity = quantity + '$quantity' WHERE id = '$pq_id'");
         
         }

         $arr = array(
            'Номер возврата: ' => $return_id,
            'Сумма: ' => $total.' тг',
            'Количество: '	=> $quantity_all.' шт',
            'Продавец: ' => $user['name'],
         );
         foreach($arr as $key => $value) {$txt .= "<b>".$key."</b> ".$value."%0A";};
         $sendToTelegram = fopen("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&parse_mode=html&text={$txt}","r");
         // if ($sendToTelegram) echo "yes"; else echo "error";

         echo 'yes';
      } else echo 'none';

      exit();
	}
   
   
   
   // Статус тарихы (кассадағыдай): Статус / Өзгерістер
   if(isset($_GET['status_log'])) {
      if (session_status() == PHP_SESSION_ACTIVE) session_write_close(); // сессия бұғаттамасын (басқа сұраныстарды күтпейді)
      $oid = (int)@$_POST['id'];
      $o = mysqli_fetch_assoc(db::query("select * from retail_orders where id = '$oid' and company_id = '".(int)$company."' limit 1"));
      if (!$o) exit();
      $lg_on = (bool)mysqli_num_rows(db::query("SHOW TABLES LIKE 'retail_orders_log'"));
      if (!isset($st_colors_db)) { $st_colors_db = []; $stc_q = db::query("select name_ru, clr from retail_orders_status"); while ($stc = mysqli_fetch_assoc($stc_q)) $st_colors_db[trim($stc["name_ru"])] = $stc["clr"]; } $st_colors = $st_colors_db; // түстер базадан: retail_orders_status.clr
      $last = [];
      if ($lg_on) {
         $lg = db::query("select new_value sid, max(ins_dt) dt from retail_orders_log where order_id = '$oid' and field = 'status' group by new_value");
         while ($l = mysqli_fetch_assoc($lg)) $last[(int)$l['sid']] = $l['dt'];
      }
      $sts = db::query("select * from retail_orders_status order by number asc");
      $first = true;
      $cur = (int)$o['order_status'];
      $rows = []; $st_names = [];
      while ($s = mysqli_fetch_assoc($sts)) {
         $sid = (int)$s['id']; $nm = trim($s['name_ru']); $st_names[$sid] = $nm;
         $dt = @$last[$sid];
         if ($first) $dt = $o['ins_dt']; // бірінші статус (Жаңа): әрқашан тапсырыс жасалған уақыт
         $first = false;
         if ($nm == 'Вопрос' && !$dt && $cur != $sid) continue;
         if (!in_array((string)@$o['order_type'], explode(',', (string)@$s['types'])) && !$dt && $cur != $sid) continue; // тапсырыс түріне жатпайтын статустар жоқ
         $clr = isset($st_colors[$nm]) ? $st_colors[$nm] : ($s['clr'] ?: '#9aa0a6');
         $rows[] = ['nm' => t::w('st:'.$nm, $nm), 'raw' => $nm, 'clr' => $clr, 'dt' => $dt, 'on' => ($dt || $cur == $sid), 'cur' => $cur == $sid];
      }
      // Қазіргі этаптан кейінгілер өтпеген болып саналады (кейін қайта ауыстырылса да)
      $ci = -1; foreach ($rows as $k => $r) if ($r['cur']) $ci = $k;
      if ($ci >= 0) foreach ($rows as $k => $r) if ($k > $ci) { $rows[$k]['on'] = false; $rows[$k]['dt'] = null; }
      // Аттап кеткен этаптар сызылады: қазіргіге дейінгі өтпегендер, ал аяқталса/отказ болса — қалғанның бәрі
      $last_on = -1; foreach ($rows as $k => $r) if ($r['on']) $last_on = $k;
      $cur_nm = trim((string)@$st_names[$cur]);
      $final = in_array($cur, [4, 5, 6, 10]); // Аяқталды / барлық отказ
      foreach ($rows as $k => $r) $rows[$k]['skip'] = !$r['on'] && ($k < $last_on || $final);
      if ($o['preorder_dt']) $rows[] = ['nm' => t::w('Preorder', 'Предзаказ'), 'clr' => '#e5484d', 'dt' => $o['preorder_dt'], 'on' => true, 'cur' => false];

      // 2-бөлім: өзгерістер (қысқаша)
      $ch_rows = [];
      if ($lg_on) {
         $lbl = ['created' => t::w('Created', 'Жасалды'), 'order_type' => t::w('Type', 'Түрі'), 'courier' => t::w('Courier', 'Курьер'), 'branch_id' => t::w('Branch', 'Точка'), 'phone' => t::w('Phone', 'Номер'), 'address' => t::w('Address', 'Адрес'), 'floor_apt' => t::w('Floor / apt', 'Этаж / кв'), 'additional' => t::w('Notes', 'Қосымша'), 'preorder_dt' => t::w('Preorder', 'Предзаказ'), 'total' => t::w('Total', 'Жалпы'), 'pay_delivery' => t::w('Delivery', 'Доставка'), 'pay_qr' => t::w('Prepayment', 'Предоплата'), 'pay_cash' => t::w('Cash', 'Нал'), 'pay_prepay' => t::w('Prepayment', 'Предоплата'), 'prepay_method' => t::w('Prepay type', 'Предоплата түрі'), 'payment_method' => t::w('Payment', 'Төлем'), 'pay_transfer' => t::w('Transfer', 'Аударым'), 'pay_red' => 'RED', 'service' => t::w('Service', 'Сервис'), 'items' => t::w('Items', 'Тағамдар'), 'refuse_reason' => t::w('Refusal reason', 'Отказ себебі'), 'kept_sum' => t::w('Kept money', 'Бізде қалды')];
         $pm = ['transfer' => t::w('Transfer', 'Аударым'), 'qr' => 'Kaspi QR', 'cash' => t::w('Cash', 'Қолма-қол'), 'mixed' => t::w('Mixed', 'Аралас'), 'unpaid' => t::w('Unpaid', 'Төленбеген'), 'red' => 'RED', 'mix' => t::w('Mixed', 'Аралас')];
         $types = []; $tq = db::query("select id, name from retail_orders_type"); while ($t = mysqli_fetch_assoc($tq)) $types[$t['id']] = $t['name'];
         $hv = function ($f, $v) use ($pm, $types) {
            if ($v === null || $v === '') return '—';
            $v = trim(html_entity_decode(strip_tags((string)$v))); // атауларда <b> тегтері болуы мүмкін
            if ($f == 'order_type') return @$types[$v] ?: $v;
            if ($f == 'courier') { $u = fun::user($v); return @$u['name'] ?: $v; }
            if ($f == 'branch_id') { $b = mysqli_fetch_assoc(db::query("select name from company_branch where id = '".(int)$v."'")); return @$b['name'] ?: $v; }
            if ($f == 'payment_method' || $f == 'prepay_method') return @$pm[$v] ?: $v;
            if (in_array($f, ['total','pay_delivery','pay_qr','pay_cash','pay_prepay','pay_transfer','pay_red','kept_sum'])) return number_format((float)$v, 0, '', ' ').' тг';
            if ($f == 'phone' && strlen(preg_replace('/\D/', '', $v)) == 10) { $p = preg_replace('/\D/', '', $v); return '8 ('.substr($p,0,3).') '.substr($p,3,3).'-'.substr($p,6,2).'-'.substr($p,8,2); }
            return $v;
         };
         // Тағамдар: тек айырмасы — «+Месси», «−Кола», «Мия 1→2»
         $parse = function ($v) { $m = []; foreach (array_filter(array_map('trim', explode(';', trim(html_entity_decode(strip_tags((string)$v)))))) as $it) { if (preg_match('/^(.*?)\s*×(\d+)$/u', $it, $x)) $m[trim($x[1])] = (int)$x[2]; else $m[$it] = 1; } return $m; };
         $idiff = function ($old, $new) use ($parse) {
            $a = $parse($old); $b = $parse($new); $out = [];
            foreach ($b as $n => $q) if (!isset($a[$n])) $out[] = ['add', '+'.$n.($q > 1 ? ' ×'.$q : '')]; elseif ($a[$n] != $q) $out[] = ['qn', $n.' '.$a[$n].'→'.$q];
            foreach ($a as $n => $q) if (!isset($b[$n])) $out[] = ['del', '−'.$n];
            return $out;
         };
         $ch = db::query("select * from retail_orders_log where order_id = '$oid' and field <> 'status' order by id desc limit 60");
         $all = []; while ($c = mysqli_fetch_assoc($ch)) $all[] = $c;
         // Тағам өзгергенде сома (Жалпы, нал) өзі қайта есептеледі: оларды бөлек жол етпей, тағамдар жолына қосамыз
         $it_at = []; foreach ($all as $c) if ($c['field'] == 'items') $it_at[$c['ins_dt']] = true;
         $sum_at = []; foreach ($all as $c) if ($c['field'] == 'total' && isset($it_at[$c['ins_dt']])) $sum_at[$c['ins_dt']] = $c;
         foreach ($all as $c) {
            $f = $c['field']; $u = $c['user_id'] ? fun::user($c['user_id']) : null;
            if (($f == 'total' || $f == 'pay_cash') && isset($it_at[$c['ins_dt']])) continue;
            if ($f == 'created') $val = '<span class="oh_new">'.htmlspecialchars((string)$c['new_value']).'</span>';
            elseif ($f == 'items') {
               $val = ''; foreach ($idiff($c['old_value'], $c['new_value']) as $d) $val .= '<span class="oh_it oh_it_'.$d[0].'">'.htmlspecialchars($d[1]).'</span>';
               if (isset($sum_at[$c['ins_dt']])) { $t = $sum_at[$c['ins_dt']]; $val .= '<span class="oh_sum">'.t::w('Total', 'Жалпы').': '.((string)$t['old_value'] !== '' ? '<s>'.htmlspecialchars($hv('total', $t['old_value'])).'</s> → ' : '').'<b>'.htmlspecialchars($hv('total', $t['new_value'])).'</b></span>'; }
               if ($val === '') continue;
            }
            else $val = ((string)$c['old_value'] === '' ? '' : '<s>'.htmlspecialchars($hv($f, $c['old_value'])).'</s> → ').'<b>'.htmlspecialchars($hv($f, $c['new_value'])).'</b>';
            $ch_rows[] = '<div class="oh_cl"><div class="oh_cl_t"><span class="oh_cl_f">'.htmlspecialchars(@$lbl[$f] ?: $f).'</span><small>'.date('H:i', strtotime($c['ins_dt'])).($u ? ' · '.htmlspecialchars((string)@$u['name']) : '').'</small></div><div class="oh_cl_v">'.$val.'</div></div>';
         }
      }

      echo '<div class="oh_tabs"><span class="oh_tab oh_tab_act" data-t="st">'.t::w('Status', 'Статус').'</span><span class="oh_tab" data-t="ch">'.t::w('Changes', 'Өзгерістер').($ch_rows ? ' <i>'.count($ch_rows).'</i>' : '').'</span></div>';
      echo '<div class="oh_pane" data-p="st"><div class="oh_list">';
      foreach ($rows as $i => $r) {
         $nx = @$rows[$i + 1];
         $link = $r['on'] && $nx && $nx['on'] && !$r['cur']; // келесі этап та өткен болса — жолақ градиентпен боялады
         $dash = $nx && (!empty($r['skip']) || !empty($nx['skip']));
         echo '<div class="oh_row'.($r['cur'] ? ' oh_cur' : '').($r['on'] ? ' oh_on' : '').($link ? ' oh_link' : '').(!empty($r['skip']) ? ' oh_skip' : '').($dash ? ' oh_dash' : '').'" style="--c:'.htmlspecialchars($r['clr']).';--n:'.htmlspecialchars($nx ? $nx['clr'] : $r['clr']).';--i:'.$i.'"><em class="oh_dot"></em><span class="oh_nm">'.htmlspecialchars($r['nm']).'</span>'
            .($r['dt'] ? '<span class="oh_dt"><b>'.date('H:i', strtotime($r['dt'])).'</b><small>'.date('d.m.Y', strtotime($r['dt'])).'</small></span>' : '').'</div>';
      }
      echo '</div><div class="oh_foot">';
      if (@$o['upd_dt']) echo '<div class="oh_row"><span class="oh_nm">'.t::w('Last change', 'Соңғы өзгеріс').'</span><span class="oh_dt"><b>'.date('H:i', strtotime($o['upd_dt'])).'</b><small>'.date('d.m.Y', strtotime($o['upd_dt'])).'</small></span></div>';
      $au = $o['user_id'] ? fun::user($o['user_id']) : null;
      echo '<div class="oh_row"><span class="oh_nm">'.t::w('Order author', 'Тапсырыс авторы').'</span><span class="oh_dt"><b>'.htmlspecialchars((string)@$au['name']).'</b></span></div>';
      echo '</div></div>';
      echo '<div class="oh_pane dsp_n" data-p="ch">'.($ch_rows ? implode('', $ch_rows) : '<div class="oh_empty">'.t::w('No changes yet', 'Әзірге өзгеріс жоқ').'</div>').'</div>';
      exit();
   }

   // 
	if(isset($_GET['change_staff'])) {
		$id = strip_tags($_POST['id']);
		$order_id = strip_tags($_POST['order_id']);

      $id = (int)$id; $order_id = (int)$order_id;
      $old = mysqli_fetch_assoc(db::query("select сourier_id from retail_orders where id = '$order_id' and company_id = '".(int)$company."' limit 1"));
      if (!$old) { echo 'none'; exit(); }
      $upd = db::query("UPDATE `retail_orders` SET сourier_id = ".($id ? "'$id'" : "null").", upd_dt = '".date('Y-m-d H:i:s')."' WHERE id = '$order_id'");
      if ((int)$old['сourier_id'] != $id && mysqli_num_rows(db::query("SHOW TABLES LIKE 'retail_orders_log'"))) db::query("INSERT INTO `retail_orders_log` (order_id, field, old_value, new_value, user_id, ins_dt) VALUES ('$order_id', 'courier', '".(int)$old['сourier_id']."', '".($id ?: '')."', '".(int)@$user_id."', '".date('Y-m-d H:i:s')."')");
      echo 'yes';

      exit();
	}

   // 
	if(isset($_GET['delete'])) {
      $id = strip_tags($_POST['id']);
      $del = db::query("DELETE FROM `retail_orders` where id = '$id'");
      if ($del) echo 'yes';

      exit();
	}



   // 
	if(isset($_GET['change_status'])) { // Admin: статусты қолмен өзгерту (касса/курьер құлыптары мұнда жоқ)
		$id = (int)@$_POST['id'];
		$order_id = (int)@$_POST['order_id'];
      if (@$user_right['positions_id'] == 3) { echo 'none'; exit(); } // кассир admin-да статус өзгертпейді
      $old = mysqli_fetch_assoc(db::query("select order_status, сourier_id from retail_orders where id = '$order_id' and company_id = '".(int)$company."' limit 1"));
      $ok = mysqli_num_rows(db::query("select id from retail_orders_status where id = '$id'"));
      if (!$old || !$ok) { echo 'none'; exit(); }
      $reason = mb_substr(trim(strip_tags((string)@$_POST['reason'])), 0, 255);
      if (in_array($id, [5, 6, 10]) && $reason === '') { echo 'need_reason'; exit(); } // отказ себебі міндетті
      if (in_array($id, [5, 6, 10])) { $rs = mysqli_real_escape_string(db::$connect, $reason); db::query("UPDATE `retail_orders` SET refuse_reason = '$rs' WHERE id = '$order_id'"); db::query("INSERT INTO `retail_orders_log` (order_id, field, old_value, new_value, user_id, ins_dt) VALUES ('$order_id', 'refuse_reason', NULL, '$rs', '".(int)@$user_id."', '".date('Y-m-d H:i:s')."')"); }
      if ($id == 10 && isset($_POST['kept']) && $_POST['kept'] !== '') { $kp = max(0, (int)preg_replace('/\D/', '', (string)$_POST['kept'])); db::query("UPDATE `retail_orders` SET kept_sum = '$kp' WHERE id = '$order_id'"); db::query("INSERT INTO `retail_orders_log` (order_id, field, old_value, new_value, user_id, ins_dt) VALUES ('$order_id', 'kept_sum', NULL, '$kp', '".(int)@$user_id."', '".date('Y-m-d H:i:s')."')"); } // Отказ вне: бізде қалған сома
      if ($id == 5 && !empty($old['сourier_id']) && in_array((int)$old['order_status'], [1, 8, 2, 11])) { db::query("UPDATE `retail_orders` SET сourier_id = null WHERE id = '$order_id'"); db::query("INSERT INTO `retail_orders_log` (order_id, field, old_value, new_value, user_id, ins_dt) VALUES ('$order_id', 'courier', '".(int)$old['сourier_id']."', '', '".(int)@$user_id."', '".date('Y-m-d H:i:s')."')"); } // Жолдаға дейін отказ: курьер алынады
      $upd = db::query("UPDATE `retail_orders` SET order_status = '$id', upd_dt = '".date('Y-m-d H:i:s')."' WHERE id = '$order_id'");
      db::query("INSERT INTO `retail_orders_log` (order_id, field, old_value, new_value, user_id, ins_dt) VALUES ('$order_id', 'status', '".(int)$old['order_status']."', '$id', '".(int)@$user_id."', '".date('Y-m-d H:i:s')."')");
      echo 'yes';

      exit();
	}

   // 
	if(isset($_GET['change_type'])) {
		$id = strip_tags($_POST['id']);
		$order_id = strip_tags($_POST['order_id']);

      if ($id == 2) $upd = db::query("UPDATE `retail_orders` SET total = total - pay_delivery, pay_delivery = 0 WHERE id = '$order_id'");
      $upd = db::query("UPDATE `retail_orders` SET order_type = '$id' WHERE id = '$order_id'");

      echo 'yes';

      exit();
	}















   // курьер ақшаны қалай алды: cash (қолма-қол), kaspi, mix (аралас), '' (белгіні алу)
	if(isset($_GET['courier_pay'])) {
		if (!$user_id || !fun::has_col('retail_orders', 'courier_method')) { echo json_encode(['ok' => 0, 'err' => 'sql']); exit(); }
		$order_id = intval(@$_POST['id']);
		$method = @$_POST['method'];
		$order_d = mysqli_fetch_assoc(db::query("select * from retail_orders where id = '$order_id' and company_id = '$company'"));
		if (!$order_d) { echo json_encode(['ok' => 0]); exit(); }
		$sum = intval($order_d['pay_cash']);
		if ($method == 'cash') { $c = $sum; $k = 0; }
		elseif ($method == 'kaspi') { $c = 0; $k = $sum; }
		elseif ($method == 'mix') { $c = max(0, min($sum, intval(@$_POST['cash']))); $k = $sum - $c; }
		else { $method = ''; $c = 0; $k = 0; }
		$m = $method ? "'$method'" : "NULL";
		$upd = db::query("UPDATE `retail_orders` SET courier_method = $m, courier_cash = '$c', courier_kaspi = '$k' WHERE id = '$order_id'");
		echo json_encode(['ok' => $upd ? 1 : 0, 'method' => $method, 'cash' => $c, 'kaspi' => $k]);
		exit();
	}



   // cashbox_pay
	if(isset($_GET['cashbox_pay'])) {
		$cashbox_number = strip_tags($_POST['number']);
		$total = strip_tags($_POST['total']);
		$delivery = strip_tags($_POST['delivery']);
		$qr = strip_tags($_POST['qr']);
		$cash = $total - $qr;
		$branch = strip_tags($_POST['branch']);

      if ($delivery) $ins = db::query("INSERT INTO `retail_orders`(`user_id`, `number`, `paid`, `total`, `pay_qr`, `pay_cash`, `branch_id`, `pay_delivery`, `upd_dt`) VALUES (1, '$cashbox_number', 1, '$total', '$qr', '$cash', '$branch', '$delivery', '$datetime')");
      else $ins = db::query("INSERT INTO `retail_orders`(`user_id`, `number`, `paid`, `total`, `pay_qr`, `pay_cash`, `order_status`, `branch_id`, `upd_dt`) VALUES (1, '$cashbox_number', 1, '$total', '$qr', '$cash', 2, '$branch', '$datetime')");
      if ($ins) echo 'yes';
      else echo 'none';

      exit();
	}








   // on_info