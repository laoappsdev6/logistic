<?php

class Case1TripLogReportController
{
    public static function reportList(Object $obj): Response
    {
        // $memcache = memcache_connect($GLOBAL_HOST, $GLOBAL_PORT);
        // $online = memcache_get($memcache, $GLOBAL_USER);
        // //memcache_set($memcache, $GLOBAL_UNIT, $s_id, 0, 0);
        // $deviceinfo = memcache_get($memcache, $GLOBAL_UNIT);
        // $ioparams = memcache_get($memcache, $GLOBAL_IOSP);
        // memcache_close($memcache);
        // $deviceinfo = [];!!!!!!!!!!!!!!!!!!!!!!!!
        $GLOBAL_DOWNLOAD_MAX_POINTS = 20000;
        $objid = $obj->objid;
        $time_zone = (float)$_SESSION['timezone'];
        $time1 = toServerTime(strtotime($obj->stime), $time_zone);
        $time2 = toServerTime(strtotime($obj->etime), $time_zone);
        $unit_speed = $_SESSION['unit_speed'];
        // $pid = $deviceinfo[$objid]['pid'];!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

        // $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);

        $sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($objid)) as table_name";

        $data_device_id = QueryClient::query($sql_query_device_id);
        echo json_encode($data_device_id);
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
                $data = QueryClient::query($sql);
                echo json_encode($data);
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
                        if ($row['e'] != '') {
                            $row['e'] = getDeviceStatus($row['e']);
                        }
                        if ($row['q'] != '') {
                            // !!!!!!!!!!!!!!!!!!!!!!!!!$row['e'] = strlen($row['e']) > 0 ? $row['e'] . ',</br>' . getDeviceIoParam($ioparams[$lang][$pid], $row['q'], 1, $ioparams[$lang]['command']) : getDeviceIoParam($ioparams[$lang][$pid], $row['q'], 1, $ioparams[$lang]['command']);
                        }
                        unset($row['q']);
                        $output[] = $row;
                    }
                    // $json = array2json($output);
                    // echo $json;

                    return getRes($output, Message::listReport, Status::success);
                }
            } catch (Exception $e) {
                return getRes([], $e->getMessage(), Status::fail);
            }
        }
        return getRes([], Message::listReport, Status::fail);
    }
}
