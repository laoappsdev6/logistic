<?php

class CaseController
{
    public function __construct()
    {
        $user_id = (int) $_SESSION['uid'];
        $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';

        $type = isset($_GET['type']) ? (int) $_GET['type'] : (int) $_POST['type'];
        $time_zone = (float)$_SESSION['timezone'];
        $unit_speed = $_SESSION['unit_speed'];
        $unit_dist = $_SESSION['unit_distance'];
        $unit_fuel = $_SESSION['unit_fuel'];
        $unit_temp = $_SESSION['unit_temperature'];
    }
    public function case13()
    {

        $sql = "select t1.object_id id,t1.object_flag c,t1.device_no n,t1.device_sim sim,t1.group_name gname,ds.lng x,ds.lat y,ds.speed s,ds.angle d,ds.valid v,ds.sta_table e,ds.ios_table q, 
        convert(varchar(20), dbo.fn_to_client_time(ds.gps_time, $time_zone*60), 120) g,convert(varchar(20), dbo.fn_to_client_time(ds.rcv_time, $time_zone*60), 120) r 
        from ( 
            select o.object_id,o.object_flag,d.device_no, d.device_sim, g.group_name
            from dbo.cfg_object o,dbo.cfg_device d, cfg_group g
            where o.group_id in (select group_id from dbo.fn_group4user($user_id)) and o.object_id = d.object_id
            and datediff(ss, d.last_stamp, getdate()) <= 0
            and o.group_id = g.group_id
             )t1 
        left join dbo.cfg_device_state ds on ds.device_no = t1.device_no order by t1.object_id asc";
        $db = new db_mssql($db_host, $db_dbms, $db_user, $db_pass);
        $data = $db->query($sql);
        if (!empty($data)) {
            $datetime_fmt = $_SESSION['datetime_fmt'];
            foreach ($data as $row) {
                //speed unit
                if ($unit_speed == 1 && $row['s'] >= 0) {
                    //mph(英里/小时)
                    $row['s'] = round($row['s'] * 0.6213712, 0);
                }
                $pid = $deviceinfo[$row['id']]['pid'];
                $row['g'] = $row['g'] == null ? "" : $row['g'];
                $row['r'] = $row['r'] == null ? "" : $row['r'];
                if ($row['e'] != '')
                    $row['e'] = getDeviceStatus($row['e']);
                if ($row['q'] != '')
                    $row['e'] = strlen($row['e']) > 0 ? $row['e'] . ',</br>' . getDeviceIoParam($ioparams[$lang][$pid], $row['q'], 1, $ioparams[$lang]['command']) : getDeviceIoParam($ioparams[$lang][$pid], $row['q'], 1, $ioparams[$lang]['command']);
                unset($row['id']);
                $output[] = $row;
            }
            $json = array2json($output);
            echo $json;
        }
    }

    public function case1()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
        $pid = $deviceinfo[$objid]['pid'];
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
        $sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

        $data_device_id = $db->query($sql_query_device_id);
        if (!empty($data_device_id)) {
            $track_table_name = $data_device_id[0]['table_name'];

            $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
									@last_stamp datetime,
									@total int
									
							select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
							if datediff(ss, @last_stamp, getdate()) <= 0
							begin
								select @total = count(*) from " . $track_table_name . " 
								where gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
								
								if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
								begin							
									select distinct x, y, s, d, v, e, q, g, r from (
									select distinct lng x, lat y, round(speed/1,0) s, angle d, valid v, sta_table e, ios_table q,
									convert(varchar(20), dbo.fn_to_client_time(gps_time, $time_zone*60), 120) g,convert(varchar(20), dbo.fn_to_client_time(rcv_time, $time_zone*60), 120) r 
									from " . $track_table_name . " h
									where (lat <> 0 and lng <> 0)
									and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
									) g
									order by g						
								end
							end";
            try {
                $data = $db->query($sql);
                if (!empty($data)) {
                    $datetime_fmt = $_SESSION['datetime_fmt'];
                    foreach ($data as $row) {
                        //speed unit
                        if ($unit_speed == 1 && $row['s'] >= 0) {
                            //mph(英里/小时)
                            $row['s'] = round($row['s'] * 0.6213712, 0);
                        }
                        //$row['g'] = $row['g'] == null ? "" : toCustomTime(new DateTime($row['g']), $time_zone, $datetime_fmt);
                        //$row['r'] = $row['r'] == null ? "" :toCustomTime(new DateTime($row['r']), $time_zone, $datetime_fmt);
                        if ($row['e'] != '')
                            $row['e'] = getDeviceStatus($row['e']);
                        if ($row['q'] != '')
                            $row['e'] = strlen($row['e']) > 0 ? $row['e'] . ',</br>' . getDeviceIoParam($ioparams[$lang][$pid], $row['q'], 1, $ioparams[$lang]['command']) : getDeviceIoParam($ioparams[$lang][$pid], $row['q'], 1, $ioparams[$lang]['command']);
                        unset($row['q']);
                        $output[] = $row;
                    }
                    $json = array2json($output);
                    echo $json;
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }

    public function case2()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);

        $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
								
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							declare @total int
							select @total = count(*) from dat_photo 
							where device_no = @device_no 					
							and take_time >= convert(datetime, '$time1', 20) and take_time < convert(datetime, '$time2', 20)					

							if @total <= 200
							begin
								select device_no n, camera_id id, event_type e, convert(varchar(20), dbo.fn_to_client_time(take_time, $time_zone*60), 120) t, photo p 
								from dat_photo
								where device_no = @device_no 
								and take_time >= convert(datetime, '$time1', 20) and take_time < convert(datetime, '$time2', 20)
							end
						end";
        try {
            $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
            $data = $db->query($sql);
            if (!empty($data)) {
                $datetime_fmt = $_SESSION['datetime_fmt'];

                foreach ($data as $row) {
                    //$row['t'] = $row['t'] == null ? "" : toCustomTime(new DateTime($row['t']), $time_zone, $datetime_fmt);
                    $row['p'] = $row['p'] == null ? "" : '<img src="data:image/jpeg;base64,' . base64_encode($row['p']) . '"/>';
                    $output[] = $row;
                }
                $json = array2json($output);
                echo $json;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function case3()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);

        $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
								
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							declare @total int
							select @total = count(*) from dat_rfid_history  
							where device_no = @device_no 
							and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
							
							if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
							begin
								select d.job_number j, d.driver_name n, d.license l, d.phone p, convert(varchar(20), dbo.fn_to_client_time(gps_time, $time_zone*60), 120) t from dat_rfid_history r,cfg_driver d
								where r.device_no = @device_no and r.rfid = d.rfid 
								and r.gps_time >= convert(datetime, '$time1', 20) and r.gps_time < convert(datetime, '$time2', 20)
								order by r.gps_time
							end
						end";


        try {
            $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
            $data = $db->query($sql);
            if (!empty($data)) {
                $datetime_fmt = $_SESSION['datetime_fmt'];
                $json = array2json($data);
                echo $json;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function case5()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
        $sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

        $data_device_id = $db->query($sql_query_device_id);
        if (!empty($data_device_id)) {
            $track_table_name = $data_device_id[0]['table_name'];

            $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
									@last_stamp datetime,
									@total int
									
							select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
							if datediff(ss, @last_stamp, getdate()) <= 0
							begin
								select @total = count(*) from " . $track_table_name . " 
								where gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
								
								if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
								begin							
									select round(speed/1,0) s, convert(varchar(20), dbo.fn_to_client_time(gps_time, $time_zone*60), 120) t
									from " . $track_table_name . "
									where (lat <> 0 and lng <> 0)
									and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)									
									order by t						
								end
							end";
            try {
                $data = $db->query($sql);
                if (!empty($data)) {
                    foreach ($data as $row) {
                        if ($row != null) {
                            //speed unit
                            if ($unit_speed == 1 && $row['s'] >= 0) {
                                //mph(英里/小时)
                                $row['s'] = round($row['s'] * 0.6213712, 0);
                            }
                            $output[] = $row;
                        }
                    }
                    $json = array2json($output);
                    echo $json;
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }

    public function case46()
    {
        $objid = $_GET['objid'];
        $eventtype = $_GET['eventtype'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);

        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);

        try {
            $sql = null;
            if ($type == 6) {
                if ($objid == -1) {
                    $sql = "declare @total int
									select @total = count(*) from cfg_device d, cfg_object o, dat_alarm a
									where o.group_id in (select group_id from dbo.fn_group4user($user_id)) 
									and d.object_id = o.object_id and a.device_no = d.device_no
									and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
									
									if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
									begin
										select o.object_flag c, a.alarm_id n, a.alarm_type a, g.group_name gn, convert(varchar(20), dbo.fn_to_client_time(a.alarm_time, $time_zone*60), 120) t, a.lng x, a.lat y, a.speed s, a.angle d, a.valid v,
										convert(varchar(20), dbo.fn_to_client_time(a.gps_time, $time_zone*60), 120) g, 
										convert(varchar(20), dbo.fn_to_client_time(a.alarm_time, $time_zone*60), 120) r, 
										a.sta_table e, a.ios_table q, dt.protocol_id pid
										from cfg_device d, cfg_object o, sys_device_type dt, dat_alarm a, cfg_group g
										where o.group_id in(select group_id from dbo.fn_group4user($user_id)) 
										and d.object_id = o.object_id and dt.dtype_id = d.dtype_id
										and a.device_no = d.device_no 
										and a.gps_time >= convert(datetime, '$time1', 20) and a.gps_time < convert(datetime, '$time2', 20)
										and o.group_id = g.group_id
										and datediff(ss, d.last_stamp, getdate()) <= 0
										order by c asc
									end";
                } else {
                    $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
									@last_stamp datetime
									
									select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
									if datediff(ss, @last_stamp, getdate()) <= 0
									begin
										declare @total int
										select @total = count(*) from dat_alarm  
										where device_no = @device_no 
										and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
										
										if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
										begin
											select o.object_flag c, a.alarm_id n, a.alarm_type a, g.group_name gn, convert(varchar(20), dbo.fn_to_client_time(a.alarm_time, $time_zone*60), 120) t, a.lng x, a.lat y, a.speed s, a.angle d, a.valid v,
											convert(varchar(20), dbo.fn_to_client_time(a.gps_time, $time_zone*60), 120) g, 
											convert(varchar(20), dbo.fn_to_client_time(a.alarm_time, $time_zone*60), 120) r, 
											a.sta_table e, a.ios_table q, dt.protocol_id pid
											from cfg_device d, cfg_object o, sys_device_type dt, dat_alarm a, cfg_group g
											where d.object_id = o.object_id and dt.dtype_id = d.dtype_id
											and a.device_no = d.device_no and o.object_id = $objid
											and a.gps_time >= convert(datetime, '$time1', 20) and a.gps_time < convert(datetime, '$time2', 20)
											and o.group_id = g.group_id
										end						
									end";
                }
            } else {
                if ($objid == -1) {
                    $sql = "declare @total int
									select @total = count(*) from cfg_device d, cfg_object o, dat_alarm a
									where o.group_id in (select group_id from dbo.fn_group4user($user_id)) 
									and d.object_id = o.object_id and a.device_no = d.device_no and a.alarm_type = '$eventtype'
									and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
									
									if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
									begin
										select o.object_flag c, a.alarm_id n, a.alarm_type a, g.group_name gn, convert(varchar(20), dbo.fn_to_client_time(a.alarm_time, $time_zone*60), 120) t, a.lng x, a.lat y, a.speed s, a.angle d, a.valid v,
										convert(varchar(20), dbo.fn_to_client_time(a.gps_time, $time_zone*60), 120) g, 
										convert(varchar(20), dbo.fn_to_client_time(a.alarm_time, $time_zone*60), 120) r, 
										a.sta_table e, a.ios_table q, dt.protocol_id pid
										from cfg_device d, cfg_object o, sys_device_type dt, dat_alarm a, cfg_group g
										where o.group_id in(select group_id from dbo.fn_group4user($user_id)) 
										and d.object_id = o.object_id and dt.dtype_id = d.dtype_id
										and a.device_no = d.device_no
										and a.alarm_type = '$eventtype' and a.gps_time >= convert(datetime, '$time1', 20) and a.gps_time < convert(datetime, '$time2', 20)
										and datediff(ss, d.last_stamp, getdate()) <= 0
										and o.group_id = g.group_id
									end";
                } else {
                    $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
									@last_stamp datetime
									
									select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
									if datediff(ss, @last_stamp, getdate()) <= 0
									begin
										declare @total int
										select @total = count(*) from dat_alarm  
										where device_no = @device_no 
										and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
										
										if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
										begin
											select o.object_flag c, a.alarm_id n, a.alarm_type a, g.group_name gn, convert(varchar(20), dbo.fn_to_client_time(a.alarm_time, $time_zone*60), 120) t, a.lng x, a.lat y, a.speed s, a.angle d, a.valid v,
											convert(varchar(20), dbo.fn_to_client_time(a.gps_time, $time_zone*60), 120) g, 
											convert(varchar(20), dbo.fn_to_client_time(a.alarm_time, $time_zone*60), 120) r, 
											a.sta_table e, a.ios_table q, dt.protocol_id pid
											from cfg_device d, cfg_object o, sys_device_type dt, dat_alarm a, cfg_group g
											where d.object_id = o.object_id and dt.dtype_id = d.dtype_id
											and a.device_no = d.device_no and o.object_id = $objid
											and a.alarm_type = '$eventtype' and a.gps_time >= convert(datetime, '$time1', 20) and a.gps_time < convert(datetime, '$time2', 20)
											and o.group_id = g.group_id
										end						
									end";
                }
            }


            $data = $db->query($sql);
            if (!empty($data)) {
                $datetime_fmt = $_SESSION['datetime_fmt'];
                foreach ($data as $row) {
                    if ($row != null) {
                        //speed unit
                        if ($unit_speed == 1 && $row['s'] >= 0) {
                            //mph(英里/小时)
                            $row['s'] = round($row['s'] * 0.6213712, 0);
                        }
                        //$row['t'] = $row['t'] == null ? "" : toCustomTime(new DateTime($row['t']), $time_zone, $datetime_fmt);
                        $alarm = strtoupper(dechex($row['a']));
                        $row['a'] = $GLOBALS['TEXT'][$alarm];
                        if ($row['e'] != '')
                            $row['e'] = getDeviceStatus($row['e']);
                        if ($row['q'] != '')
                            $row['e'] = strlen($row['e']) > 0 ? $row['e'] . ',</br>' . getDeviceIoParam($ioparams[$lang][$row['pid']], $row['q'], 1, $ioparams[$lang]['command']) : getDeviceIoParam($ioparams[$lang][$pid], $row['q'], 1, $ioparams[$lang]['command']);
                        unset($row['q']);
                        unset($row['pid']);
                        $output[] = $row;
                    }
                }
                $json = array2json($output);
                echo $json;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function case7()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
        $sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

        $data_device_id = $db->query($sql_query_device_id);
        if (!empty($data_device_id)) {
            $track_table_name = $data_device_id[0]['table_name'];

            $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
									@last_stamp datetime,
									@total int
									
							select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
							if datediff(ss, @last_stamp, getdate()) <= 0
							begin
								select @total = count(*) from " . $track_table_name . " 
								where charindex('1e:',ios_table) > 0 
								and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
								
								if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
								begin							
									select round(speed/1,0) s, dbo.fn_io4value('1e',ios_table) f, convert(varchar(20), dbo.fn_to_client_time(gps_time, $time_zone*60), 120) t
									from " . $track_table_name . "
									where charindex('1e:',ios_table) > 0
									and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)									
									order by t						
								end
							end";
            try {
                $data = $db->query($sql);
                if (!empty($data)) {
                    $datetime_fmt = $_SESSION['datetime_fmt'];
                    foreach ($data as $row) {
                        if ($row != null) {
                            //fuel unit
                            if ($unit_fuel == 1 && $row['f'] >= 0) {
                                //Gallon(加仑)
                                $row['f'] = round($row['f'] * 0.2199692, 0);
                            }
                            $output[] = $row;
                        }
                    }
                    $fuel = array2json($output);

                    $sql = "exec dbo.p_collect_fuel_event $objid, '$time1', '$time2', $GLOBAL_REFUEL_RATE, $GLOBAL_STEALFUEL_RATE, $GLOBAL_FUEL_EVENT_TIME_DIFFERENCE, 0, 0";
                    $fuel_event = $db->query($sql);
                    if (!empty($fuel_event)) {
                        foreach ($fuel_event as $fuel_event_row) {
                            if ($fuel_event_row != null) {
                                $fuel_event_row['GPS_TIME'] = toCustomTime($fuel_event_row['GPS_TIME'], $time_zone, $datetime_fmt);
                                //fuel unit
                                if ($unit_fuel == 1) {
                                    //Gallon(加仑)
                                    $fuel_event_row['FBEFORE'] = round($fuel_event_row['FBEFORE'] * 0.2199692, 0);
                                    $fuel_event_row['FAFTER'] = round($fuel_event_row['FAFTER'] * 0.2199692, 0);
                                }
                                if ($fuel_event_row['CHANGE_TYPE'] == 1) {
                                    $refuel_output[] = $fuel_event_row;
                                } else if ($fuel_event_row['CHANGE_TYPE'] == 2) {
                                    $sfuel_output[] = $fuel_event_row;
                                }
                            }
                        }
                    }
                    $rfuel = array2json($refuel_output);
                    $sfuel = array2json($sfuel_output);

                    echo "{'fuel':$fuel, 'rfuel':$rfuel, 'sfuel': $sfuel}";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }

    public function case8()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
        $sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

        $data_device_id = $db->query($sql_query_device_id);
        if (!empty($data_device_id)) {
            $track_table_name = $data_device_id[0]['table_name'];

            $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
								
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							declare @total int
							select @total = count(*) from " . $track_table_name . "  
							where charindex('1e:',ios_table) > 0 
							and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)				

							if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
							begin
								exec dbo.p_collect_fuel_event $objid, '$time1', '$time2', $GLOBAL_REFUEL_RATE, $GLOBAL_STEALFUEL_RATE, $GLOBAL_FUEL_EVENT_TIME_DIFFERENCE, 1, 0
							end
						end";
            try {
                $data = $db->query($sql);
                if (!empty($data)) {
                    $datetime_fmt = $_SESSION['datetime_fmt'];
                    foreach ($data as $row) {
                        if ($row != null) {
                            $row['SENSOR_ID'] = hexdec($row['SENSOR_ID']) - 29;
                            $row['GPS_TIME'] = toCustomTime($row['GPS_TIME'], $time_zone, $datetime_fmt);
                            //fuel unit
                            if ($unit_fuel == 1) {
                                //Gallon(加仑)
                                $row['FBEFORE'] = round($row['FBEFORE'] * 0.2199692, 0);
                                $row['FAFTER'] = round($row['FAFTER'] * 0.2199692, 0);
                            }
                            $row['r'] = $row['FAFTER'] - $row['FBEFORE'];
                            if ($row['r'] > 0) {
                                $output[] = $row;
                            }
                        }
                    }
                    $json = array2json($output);
                    echo $json;
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }

    public function case9()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
        $sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

        $data_device_id = $db->query($sql_query_device_id);
        if (!empty($data_device_id)) {
            $track_table_name = $data_device_id[0]['table_name'];

            $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
								
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							declare @total int
							select @total = count(*) from " . $track_table_name . "  
							where charindex('1e:',ios_table) > 0 
							and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)				

							if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
							begin
								exec dbo.p_collect_fuel_event $objid, '$time1', '$time2', $GLOBAL_REFUEL_RATE, $GLOBAL_STEALFUEL_RATE, $GLOBAL_FUEL_EVENT_TIME_DIFFERENCE, 2, 0
							end
						end";
            try {
                $data = $db->query($sql);
                if (!empty($data)) {
                    $datetime_fmt = $_SESSION['datetime_fmt'];
                    foreach ($data as $row) {
                        if ($row != null) {
                            $row['SENSOR_ID'] = hexdec($row['SENSOR_ID']) - 29;
                            $row['GPS_TIME'] = toCustomTime($row['GPS_TIME'], $time_zone, $datetime_fmt);
                            //fuel unit
                            if ($unit_fuel == 1) {
                                //Gallon(加仑)
                                $row['FBEFORE'] = round($row['FBEFORE'] * 0.2199692, 0);
                                $row['FAFTER'] = round($row['FAFTER'] * 0.2199692, 0);
                            }
                            $row['s'] = $row['FBEFORE'] - $row['FAFTER'];
                            if ($row['s'] > 0) {
                                $output[] = $row;
                            }
                        }
                    }
                    $json = array2json($output);
                    echo $json;
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }

    public function case10()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
        $sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

        $data_device_id = $db->query($sql_query_device_id);
        if (!empty($data_device_id)) {
            $track_table_name = $data_device_id[0]['table_name'];

            $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
									@last_stamp datetime,
									@total int
									
							select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
							if datediff(ss, @last_stamp, getdate()) <= 0
							begin
								select @total = count(*) from " . $track_table_name . " 
								where charindex('48:',ios_table) > 0 
								and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
								
								if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
								begin							
									select dbo.fn_io4value('48',ios_table) w, convert(varchar(20), dbo.fn_to_client_time(gps_time, $time_zone*60), 120) t
									from " . $track_table_name . "
									where charindex('48:',ios_table) > 0
									and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)									
									order by t						
								end
							end";
            try {
                $data = $db->query($sql);
                if (!empty($data)) {
                    foreach ($data as $row) {
                        if ($row != null) {
                            //temp unit
                            if ($unit_temp == 1) {
                                //Fahrenheit
                                $row['w'] = round(($row['w'] / 10.0) * 1.8 + 32, 1);
                            } else {
                                //Celsius
                                $row['w'] = round($row['w'] / 10.0, 1);
                            }
                            $output[] = $row;
                        }
                    }

                    $json = array2json($output);
                    echo $json;
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }

    public function case11()
    {
        $objids = $_POST['objids'];
        $time1 = toServerTime(strtotime($_POST['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_POST['etime']), $time_zone);
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);

        $sql = "select o.OBJECT_ID, convert(nvarchar(10) ,COLLECT_DATE,120) COLLECT_DATE, MILEAGE, DRIVING_TIME, STOP_TIME, AVG_SPEED, MAX_SPEED, IDLE_TIME,
						isnull(sensor_fuel,0) SENSOR_FUEL, isnull(estimate_fuel,0) ESTIMATE_FUEL, isnull(can_fuel,0) CAN_FUEL, isnull(speeding_dist,0) SPEEDING_DIST,
						isnull(speeding_time,0) SPEEDING_TIME, isnull(speeding_count,0) SPEEDING_COUNT, isnull(engine_on_count,0) ENGINE_ON_COUNT from dbo.cfg_device d, dbo.cfg_object o, dbo.rpt_usage r where 
						d.object_id = o.object_id and o.object_id = r.object_id and datediff(ss, d.last_stamp, getdate()) <= 0 and
					   ";
        $where = "";
        foreach ($objids as $o) {
            $where .= " or r.object_id=" . $o;
        }
        $sql .= "(" . substr($where, 4) . ")" . " and r.collect_date between '$time1' and '$time2'";

        try {
            $data = $db->query($sql);
            if (!empty($data)) {
                $datetime_fmt = $_SESSION['datetime_fmt'];
                foreach ($data as $row) {
                    if ($row != null) {
                        //speed unit
                        if ($unit_speed == 1 && $row['AVG_SPEED'] >= 0) {
                            //mph(英里/小时)
                            $row['AVG_SPEED'] = round($row['AVG_SPEED'] * 0.6213712, 0);
                        }
                        if ($unit_speed == 1 && $row['MAX_SPEED'] >= 0) {
                            //mph(英里/小时)
                            $row['MAX_SPEED'] = round($row['MAX_SPEED'] * 0.6213712, 0);
                        }

                        //distance unit
                        if ($unit_dist == 1) {
                            $row['MILEAGE'] = round($row['MILEAGE'] * 0.6213712, 0);
                            $row['OVER_SPEED_DIST'] = round($row['OVER_SPEED_DIST'] * 0.6213712, 0);
                        } else if ($unit_dist == 2) {
                            $row['MILEAGE'] = round($row['MILEAGE'] * 0.5399568, 0);
                            $row['OVER_SPEED_DIST'] = round($row['OVER_SPEED_DIST'] * 0.5399568, 0);
                        }

                        //fuel unit
                        if ($unit_fuel == 1) {
                            $row['SENSOR_FUEL'] = round($row['SENSOR_FUEL'] * 0.2199692, 0);
                            $row['ESTIMATE_FUEL'] = round($row['ESTIMATE_FUEL'] * 0.2199692, 0);
                            $row['CAN_FUEL'] = round($row['CAN_FUEL'] * 0.2199692, 0);
                        }

                        //$row['COLLECT_DATE'] = toCustomTime($row['COLLECT_DATE'], $time_zone, $datetime_fmt);
                        $output[] = $row;
                    }
                }
                $json = array2json($output);
                echo $json;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function case12()
    {
        $objid = $_GET['objid'];
        $time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
        $time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
        $sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

        $data_device_id = $db->query($sql_query_device_id);
        if (!empty($data_device_id)) {
            $track_table_name = $data_device_id[0]['table_name'];

            $sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
									@last_stamp datetime
									
							select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
							if datediff(ss, @last_stamp, getdate()) <= 0
							begin
								exec dbo.p_collect_travel $objid, '$time1', '$time2', 0
							end";
            try {
                $data = $db->query($sql);
                if (!empty($data)) {
                    foreach ($data as $row) {
                        if ($row != null) {
                            //speed unit
                            if ($unit_speed == 1 && $row['AVG_SPEED'] >= 0) {
                                //mph(英里/小时)
                                $row['AVG_SPEED'] = round($row['AVG_SPEED'] * 0.6213712, 0);
                            }
                            if ($unit_speed == 1 && $row['MAX_SPEED'] >= 0) {
                                //mph(英里/小时)
                                $row['MAX_SPEED'] = round($row['MAX_SPEED'] * 0.6213712, 0);
                            }

                            //distance unit
                            if ($unit_dist == 1) {
                                $row['MILEAGE'] = round($row['MILEAGE'] * 0.6213712, 0);
                                $row['OVER_SPEED_DIST'] = round($row['OVER_SPEED_DIST'] * 0.6213712, 0);
                            } else if ($unit_dist == 2) {
                                $row['MILEAGE'] = round($row['MILEAGE'] * 0.5399568, 0);
                                $row['OVER_SPEED_DIST'] = round($row['OVER_SPEED_DIST'] * 0.5399568, 0);
                            }

                            $output[] = $row;
                        }
                    }

                    $json = array2json($output);
                    echo $json;
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }

    public function case29(){
        $objid = $_GET['objid'];
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
				$datetime_fmt = $_SESSION['datetime_fmt'];
				$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
				
				if($objid == -1){
					$sql = "exec dbo.p_collect_max_speed_info 1, 0, $user_id, '$time1', '$time2'";					
				}else{
					$sql = "exec dbo.p_collect_max_speed_info 0, $objid, 0, '$time1', '$time2'";					
				}
							
				try{
					$data = $db->query($sql);					
					if (!empty($data)) {
						foreach ($data as $row) {
							//speed unit
							if($unit_speed == 1 && $row['MAX_SPEED'] >= 0){
								//mph(英里/小时)
								$row['MAX_SPEED'] = round($row['MAX_SPEED'] * 0.6213712,0);
							}
							//$row['GPS_TIME'] = toCustomTime($row['GPS_TIME'], $time_zone, $datetime_fmt);
							$output[] = $row;
						}
						$json = array2json($output);
						echo $json;
					}	
				}catch(Exception $e){
					return $e->getMessage();
				}
    }

    public function case30(){
        $objid = $_GET['objid'];
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
				$datetime_fmt = $_SESSION['datetime_fmt'];
				$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);				
				$sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
									
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							exec dbo.p_collect_alcohol_ad $objid, '$time1', '$time2'
						end";					
	
				try{
					$data = $db->query($sql);
					if (!empty($data)) {	
						foreach ($data as $row) {
							$output[] = $row;
						}
						$json = array2json($output);
						echo $json;
					}	
				}catch(Exception $e){
					return $e->getMessage();
				}
    }

    public function case33(){
        $objid = $_GET['objid'];
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
				$datetime_fmt = $_SESSION['datetime_fmt'];
				$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
				
				$sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
									
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							exec dbo.p_collect_speeding_time $objid, '$time1', '$time2'
						end";					
							
				try{
					$data = $db->query($sql);					
					if (!empty($data)) {
						foreach ($data as $row) {
							//speed unit
							if($unit_speed == 1 && $row['AVG_SPEED'] >= 0){
								//mph(英里/小时)
								$row['AVG_SPEED'] = round($row['AVG_SPEED'] * 0.6213712,0);
							}
							if($unit_speed == 1 && $row['MAX_SPEED'] >= 0){
								//mph(英里/小时)
								$row['MAX_SPEED'] = round($row['MAX_SPEED'] * 0.6213712,0);
							}
							
							//distance unit
							if($unit_dist == 1){
								$row['DISTANCE'] = round($row['DISTANCE'] * 0.6213712,0);
							}else if($unit_dist == 2){
								$row['DISTANCE'] = round($row['DISTANCE'] * 0.5399568,0);
							}
							//$row['GPS_TIME_START'] = toCustomTime($row['GPS_TIME_START'], $time_zone, $datetime_fmt);
							//$row['GPS_TIME_END'] = toCustomTime($row['GPS_TIME_END'], $time_zone, $datetime_fmt);
							$output[] = $row;
						}
						$json = array2json($output);
						echo $json;
					}	
				}catch(Exception $e){
					return $e->getMessage();
				}
    }

    public function case34(){
        $objid = $_GET['objid'];
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
				$rduration = $_GET['rduration'];
				$datetime_fmt = $_SESSION['datetime_fmt'];
				$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
				$itime_zone = $time_zone*60;
				$sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
									
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							exec dbo.p_collect_stops_detail $objid, '$time1', '$time2', $rduration, 1, $itime_zone
						end";					
							
				try{
					$data = $db->query($sql);					
					if (!empty($data)) {
						foreach ($data as $row) {
							//$row['START_TIME'] = toCustomTime($row['START_TIME'], $time_zone, $datetime_fmt);
							//$row['END_TIME'] = toCustomTime($row['END_TIME'], $time_zone, $datetime_fmt);
							$output[] = $row;
						}
						$json = array2json($output);
						echo $json;
					}	
				}catch(Exception $e){
					return $e->getMessage();
				}
    }

    public function case38(){
        $objid = $_GET['objid'];
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
				$distance = $_GET['distance'];
				$datetime_fmt = $_SESSION['datetime_fmt'];
				$mtime_zone = $time_zone * 60;
				$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
				
				$sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
									
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							exec dbo.p_collect_moves_detail $objid, '$time1', '$time2', $distance, $mtime_zone
						end";					
							
				try{
					$data = $db->query($sql);					
					if (!empty($data)) {
						$pid = $deviceinfo[$objid]['pid'];	
						foreach ($data as $row) {
							if($row['STA_TABLE_START'] != '')
								$row['e1'] = getDeviceStatus($row['STA_TABLE_START']);
							if($row['STA_TABLE_END'] != '')
								$row['e2'] = getDeviceStatus($row['STA_TABLE_END']);
								
							if($row['IOS_TABLE_START'] != '')
								$row['e1'] = strlen($row['e1']) > 0 ? $row['e1'] . ',</br>'. getDeviceIoParam($ioparams[$lang][$pid], $row['IOS_TABLE_START'], 1, $ioparams[$lang]['command']) : getDeviceIoParam($ioparams[$lang][$pid], $row['IOS_TABLE_START'], 1, $ioparams[$lang]['command']);
							if($row['IOS_TABLE_END'] != '')
								$row['e2'] = strlen($row['e2']) > 0 ? $row['e2'] . ',</br>'. getDeviceIoParam($ioparams[$lang][$pid], $row['IOS_TABLE_END'], 1, $ioparams[$lang]['command']) : getDeviceIoParam($ioparams[$lang][$pid], $row['IOS_TABLE_END'], 1, $ioparams[$lang]['command']);
							
							unset($row['STA_TABLE_START']);
							unset($row['STA_TABLE_END']);
							unset($row['IOS_TABLE_START']);
							unset($row['IOS_TABLE_END']);
							$output[] = $row;
						}
						$json = array2json($output);
						echo $json;
					}	
				}catch(Exception $e){
					return $e->getMessage();
				}
    }

    public function case39(){
        $objid = $_GET['objid'];
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
				$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
				$sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

				$data_device_id = $db->query($sql_query_device_id);
				if (!empty($data_device_id)) {
					$track_table_name = $data_device_id[0]['table_name'];
					
					$sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
									@last_stamp datetime,
									@total int
									
							select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
							if datediff(ss, @last_stamp, getdate()) <= 0
							begin
								select @total = count(*) from ".$track_table_name." 
								where gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
								
								if @total <= $GLOBAL_DOWNLOAD_MAX_POINTS
								begin							
									declare @code int,
											@track_name nvarchar(200),
											@driv_time int,
											@mileage int,
											@stop_time int,
											@idle_time int,
											@avg_speed int,
											@max_speed int,
											@sensor_fuel_consumption int,
											@estimate_fuel_consumption int,
											@can_fuel_consumption int,
											@speeding_dist int,
											@speeding_time int,
											@speeding_count int,
											@engine_count int,
											@can_len int,
											@object_id     int	
											
									create table #temp_over_speed(
										object_id          int,		
										object_flag        nvarchar(50),
										group_name         nvarchar(50),
										driver_name        nvarchar(50),
										userdef_flag       nvarchar(50),
										lng                int,
										lat                int,
										av_speed           int,        --平均速度
										max_speed          int,        --最大速度
										distance           float,      --单位:km
										gps_time_start     datetime, 
										gps_time_end       datetime,
										last_time_second   int,        --持续时间(秒)
										last_time_format   varchar(20) --持续时间(hh:mm:ss)
									)
											
									set @track_name = 'track_' + @device_no
									select @can_len = dt.can_mileage 
								    from dbo.cfg_device d,dbo.sys_device_type dt 
								    where d.dtype_id = dt.dtype_id and d.device_no = @device_no
									
									truncate table #temp_over_speed
									select @object_id = object_id from dbo.cfg_device where device_no = @device_no
									insert into #temp_over_speed(object_id,object_flag,group_name,driver_name,userdef_flag,lng,lat, av_speed, max_speed,distance,gps_time_start,gps_time_end,last_time_second,last_time_format) exec dbo.p_collect_speeding_time @object_id, '$time1', '$time2'
						   
									
									exec @mileage = dbo.p_collect_mileage '$time1', '$time2', @track_name, @can_len
									exec @driv_time = dbo.p_collect_drivtime '$time1', '$time2', @track_name	 
									exec @stop_time = dbo.p_collect_stoptime '$time1', '$time2', @track_name
									exec @idle_time = dbo.p_collect_idletime '$time1', '$time2', @track_name
									exec @avg_speed = dbo.p_collect_avg_speed '$time1', '$time2', @track_name
									exec @max_speed = dbo.p_collect_max_speed '$time1', '$time2', @track_name
									exec @sensor_fuel_consumption = dbo.p_collect_sensor_fuel '$time1', '$time2', @track_name
								    exec @estimate_fuel_consumption = dbo.p_collect_estimate_fuel '$time1', '$time2', @track_name
									exec @can_fuel_consumption = dbo.p_collect_can_fuel '$time1', '$time2', @track_name
								    select @speeding_dist = isnull(sum(distance),0) from #temp_over_speed
								    select @speeding_time = isnull(sum(last_time_second),0) from #temp_over_speed
								    select @speeding_count = count(*) from #temp_over_speed
								    exec @engine_count = dbo.p_collect_engine_count '$time1','$time2',@track_name 
									
									select convert(decimal(18,0), @mileage / 1000.0) m, dbo.fn_sec2time(@driv_time, null) as dt, 
									dbo.fn_sec2time(@stop_time, null) as st, @avg_speed as s, @max_speed ms, 
									dbo.fn_sec2time(@idle_time, null) as it, round(isnull(@sensor_fuel_consumption,0)/100.0,2) sfc, round(isnull(@estimate_fuel_consumption,0)/100.0,2) efc, round(isnull(@can_fuel_consumption,0),2) cfc, @speeding_dist spd, dbo.fn_sec2time(@speeding_time, null) spt, @speeding_count spc, @engine_count engc
						   
								end
							end";							
					try{
						$data = $db->queryLastDS($sql);
						if (!empty($data)) {
							$datetime_fmt = $_SESSION['datetime_fmt'];
							foreach ($data as $row) {
								if ($row != null) {
									//speed unit
									if($unit_speed == 1 && $row['s'] >= 0){
										//mph(英里/小时)
										$row['s'] = round($row['s'] * 0.6213712,0);
									}
									if($unit_speed == 1 && $row['ms'] >= 0){
										//mph(英里/小时)
										$row['ms'] = round($row['ms'] * 0.6213712,0);
									}
									
									//distance unit
									if($unit_dist == 1){
										$row['m'] = round($row['m'] * 0.6213712,0);
										$row['spd'] = round($row['spd'] * 0.6213712,0);
									}else if($unit_dist == 2){
										$row['m'] = round($row['m'] * 0.5399568,0);
										$row['spd'] = round($row['spd'] * 0.5399568,0);
									}
									
									//fuel unit
									if($unit_fuel == 1){
										$row['sfc'] = round($row['sfc'] * 0.2199692,2);
										$row['efc'] = round($row['efc'] * 0.2199692,2);
										$row['cfc'] = round($row['cfc'] * 0.2199692,2);
									}else{
										$row['sfc'] = round($row['sfc'],2);
										$row['efc'] = round($row['efc'],2);
										$row['cfc'] = round($row['cfc'],2);
									}
									
									$output[] = $row;
								}
							}
							$json = array2json($output);
							echo $json;
						}
					}catch(Exception $e){
						return $e->getMessage();
					}
				}
    }

    public function case41(){
        $objid = $_GET['objid'];
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
				$datetime_fmt = $_SESSION['datetime_fmt'];				
				$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
				
				$sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
									
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no							  
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							exec dbo.p_collect_place_event_detail $objid, '$time1', '$time2'
						end";					
				
				try{
					$data = $db->query($sql);					
					if (!empty($data)) {
							$datetime_fmt = $_SESSION['datetime_fmt'];
							
							foreach ($data as $row) {
								if ($row != null) {
									$row['IN_TIME'] = toCustomTime($row['IN_TIME'], $time_zone, $datetime_fmt);
									$row['OUT_TIME'] = toCustomTime($row['OUT_TIME'], $time_zone, $datetime_fmt);
									
									//speed unit
									if($unit_speed == 1 && $row['AVG_SPEED'] >= 0){
										//mph(英里/小时)
										$row['AVG_SPEED'] = round($row['AVG_SPEED'] * 0.6213712,0);
									}
									if($unit_speed == 1 && $row['MAX_SPEED'] >= 0){
										//mph(英里/小时)
										$row['MAX_SPEED'] = round($row['MAX_SPEED'] * 0.6213712,0);
									}
									
									//distance unit
									if($unit_dist == 1){
										$row['MILEAGE'] = round($row['MILEAGE'] * 0.6213712,0);
										$row['OVER_SPEED_DIST'] = round($row['OVER_SPEED_DIST'] * 0.6213712,0);
									}else if($unit_dist == 2){
										$row['MILEAGE'] = round($row['MILEAGE'] * 0.5399568,0);
										$row['OVER_SPEED_DIST'] = round($row['OVER_SPEED_DIST'] * 0.5399568,0);
									}
									
									//fuel unit
									if($unit_fuel == 1){
										$row['SENSOR_FUEL'] = round($row['SENSOR_FUEL'] * 0.2199692,0);
										$row['ESTIMATE_FUEL'] = round($row['ESTIMATE_FUEL'] * 0.2199692,0);
										$row['CAN_FUEL'] = round($row['ESTIMATE_FUEL'] * 0.2199692,0);
									}
									
									$output[] = $row;
								}
							}
							$json = array2json($output);
							echo $json;
						}	
				}catch(Exception $e){
					return $e->getMessage();
				}
    }

    public function case42(){
        $uname = $_GET['uname'];				
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);
				$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
				
				if(empty($uname) or mb_strlen($uname) == 0){
					$sql = "select user_id uid, session_id sid, user_name un, login_name ln, login_time lt, leave_time vt, login_ip ip, time_zone zo from dbo.web_login_log where user_id in (select user_id from dbo.fn_user_tree($user_id)) and login_time between '$time1' and '$time2' order by login_time";
				}else{
					$sql = "select user_id uid, session_id sid, user_name un, login_name ln, login_time lt, leave_time vt, login_ip ip, time_zone zo from dbo.web_login_log where user_name = '$uname' and login_time between '$time1' and '$time2' order by login_time";
				}
				

				try{						
					$data = $db->query($sql);
					if (!empty($data)) {
						$datetime_fmt = $_SESSION['datetime_fmt'];
						foreach ($data as $row) {
							if ($row != null) {
								$row['lt'] = toCustomTime($row['lt'], $time_zone, $datetime_fmt);
								$row['vt'] = toCustomTime($row['vt'], $time_zone, $datetime_fmt);
								$output[] = $row;
							}
						}
						$json = array2json($output);
						echo $json;
					}
				}catch(Exception $e){
					return $e->getMessage();
				}
							
    }

    public function case43(){
        $objid = $_GET['objid'];				
				$time1 = toServerTime(strtotime($_GET['stime']), $time_zone);
				$time2 = toServerTime(strtotime($_GET['etime']), $time_zone);

				$sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($objid),
								@last_stamp datetime
								
						select @last_stamp = last_stamp from cfg_device where device_no = @device_no
						if datediff(ss, @last_stamp, getdate()) <= 0
						begin
							declare @total int
							select @total = count(*) from dat_voice_record 
							where device_no = @device_no 					
							and rcv_time >= convert(datetime, '$time1', 20) and rcv_time < convert(datetime, '$time2', 20)	
							and voice is not null

							if @total <= 20
							begin
								select device_no n, convert(varchar(20), dbo.fn_to_client_time(rcv_time, $time_zone*60), 120) t, voice v 
								from dat_voice_record
								where device_no = @device_no 
								and rcv_time >= convert(datetime, '$time1', 20) and rcv_time < convert(datetime, '$time2', 20)
								and voice is not null
							end
						end";
				try{
					$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
					$data = $db->query($sql);
					if (!empty($data)) {
						$datetime_fmt = $_SESSION['datetime_fmt'];
						
						foreach ($data as $row) {
							//$row['t'] = $row['t'] == null ? "" : toCustomTime(new DateTime($row['t']), $time_zone, $datetime_fmt);
							$row['v'] = $row['v'] == null ? "" : '<audio controls="controls" preload="metadata">
																	<source src="data:audio/mpeg;base64,'.base64_encode( $row['v'] ).'"/>;
																  </audio>';
							$output[] = $row;
						}
						$json = array2json($output);
						echo $json;
					}
				}catch(Exception $e){
					return $e->getMessage();
				}
    }

}
