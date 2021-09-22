<?php

class Case1TripLogReportController
{
    public static function reportList(Object $obj): Response
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
}
