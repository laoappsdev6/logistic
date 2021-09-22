<?php

class LoginController
{
    public static function authorize(LoginModel $obj): Response
    {
        try {

            $iduser = trim($obj->username);
            $idpass = $obj->password;
            $time_zone = isset($_POST['timezone']) ? (float)$_POST['timezone'] : 7;
            $sql = "select u.user_id uid, u.user_name uname, u.login_pass pass, u.email, 
                    convert(varchar(5), convert(time, dbo.fn_to_client_time(dateadd(mi, isnull(u.mail_offset,0), 0), $time_zone*60),20)) rtime, 
                    isnull(u.mail_report,0) rmail, mail_type mtype, u.valid valid, rr.role_name rname,
                    w.def_lat lat, w.def_lng lng, isnull(w.def_zoom, 5) zoom, isnull(w.def_page,0) page, isnull(w.def_show,0) show, isnull(w.show_zone,0) zone, isnull(w.show_marker,0) marker, w.def_date_fmt date_fmt, w.def_time_fmt time_fmt, isnull(w.def_sound_alarm,0) sond_alarm, isnull(w.def_popup_alarm,0) popup_alarm,
                    isnull(w.unit_distance,0) ud, isnull(w.unit_fuel,0) uf, isnull(w.unit_temperature,0) ut, isnull(w.unit_speed,0) us, isnull(w.unit_altitude,0) ua
                    from sys_user u
                    left join web_default w on u.user_id = w.user_id
                    left join (select ur.user_id, r.role_name from sys_role r, sys_user_role ur
                            where r.role_id = ur.role_id) rr on rr.user_id = u.user_id
                    where u.login_name = '$iduser'";

            $data = QueryClient::query($sql);
            echo json_encode($data);
            $sql = "select count(*) okind from sys_object_kind";
            $okind = QueryClient::query($sql);
            if (!empty($data) and !empty($okind)) {
                $row = $data[0];
                $object_kind = $okind[0];
                if ((int) $row['valid'] != 1) {
                    return getRes([], "stopped", 0);
                } else if ($row['pass'] != $idpass) {
                    return getRes([], "invalid", 0);
                } else {
                    $_SESSION['uid'] = $row['uid'];
                    $_SESSION['pass'] = $row['pass'];
                    $_SESSION['maptype'] = $_POST['idmap'];
                    $_SESSION['uname'] = $row['uname'];
                    $_SESSION['rname'] = $row['rname'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['rtime'] = $row['rtime'];
                    $_SESSION['rmail'] = $row['rmail'];
                    $_SESSION['mtype'] = $row['mtype'];
                    $_SESSION['timezone'] = $time_zone;
                    $_SESSION['logined'] = true;
                    // $_SESSION['lat'] = empty($row['lat']) ? $default_latlng['lat'] : number_format(((float) $row['lat'] / 1000000), 5);
                    // $_SESSION['lng'] = empty($row['lng']) ? $default_latlng['lng'] : number_format(((float) $row['lng'] / 1000000), 5);
                    // $_SESSION['zoom'] = empty($row['zoom']) ? $default_latlng['zoom'] : $row['zoom'];
                    // $_SESSION['page'] = $row['page'];
                    // $_SESSION['show'] = $row['show'];
                    // $_SESSION['zone'] = $row['zone'];
                    // $_SESSION['marker'] = $row['marker'];
                    // $_SESSION['date_fmt'] = empty($row['date_fmt']) ? $support_datefmt['yyyy-MM-dd'] : $support_datefmt[$row['date_fmt']];
                    // $_SESSION['time_fmt'] = empty($row['time_fmt']) ? $support_timefmt['HH:mm:ss'] : $support_timefmt[$row['time_fmt']];
                    //$_SESSION['datetime_fmt'] = $_SESSION['date_fmt'] . ' ' . $_SESSION['time_fmt'];
                    $date_fmt_js = empty($row['date_fmt']) ? 'yyyy-MM-dd' : $row['date_fmt'];
                    $time_fmt = empty($row['time_fmt']) ? 'HH:mm:ss' : $row['time_fmt'];
                    $_SESSION['date_fmt_js'] = $date_fmt_js;
                    $_SESSION['time_fmt_js'] = $time_fmt;
                    $_SESSION['datetime_fmt_js'] = $date_fmt_js . ' ' . $time_fmt;
                    // $_SESSION['lang'] = $_SESSION['lang'];
                    $_SESSION['sond_alarm'] = $row['sond_alarm'];
                    $_SESSION['popup_alarm'] = $row['popup_alarm'];
                    $_SESSION['object_kind'] = $object_kind['okind'];

                    $_SESSION['unit_distance'] = $row['ud'];
                    $_SESSION['unit_fuel'] = $row['uf'];
                    $_SESSION['unit_temperature'] = $row['ut'];
                    $_SESSION['unit_speed'] = $row['us'];
                    $_SESSION['unit_altitude'] = $row['ua'];

                    $user_id = $row['uid'];
                    $user_name = $row['uname'];
                    $login_name = $iduser;
                    $session_id = session_id();
                    $login_time = date('Y-m-d H:i:s', time());
                    $_SESSION['login_time'] = $login_time;
                    // $ip = getIp();
                    // $sql = "insert into dbo.web_login_log (user_id, session_id, user_name, login_name, login_time, login_ip, time_zone) values ($user_id, '$session_id', '$user_name', '$login_name', '$login_time', '$ip', $time_zone)";
                    // QueryClient::query($sql);

                    $dataToken = array('token' => registerToken($row));
                    return getRes($dataToken, Message::loginOk, 1);
                }
            } else {
                return getRes([], "error", 0);
            }

            // function getIp()
            // {
            //     $ip = false;
            //     if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            //         $ip = $_SERVER["HTTP_CLIENT_IP"];
            //     }
            //     if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //         $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            //         if ($ip) {
            //             array_unshift($ips, $ip);
            //             $ip = FALSE;
            //         }
            //         for ($i = 0; $i < count($ips); $i++) {
            //             if (!preg_match("/^(10│172.16│192.168).$/", $ips[$i])) {
            //                 $ip = $ips[$i];
            //                 break;
            //             }
            //         }
            //     }
            //     return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
            // }
        } catch (Exception $e) {
            return getRes([], $e->getMessage(), Status::success);
        }
    }
}
