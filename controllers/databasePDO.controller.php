<?php

class PDODBController
{
    private static function connection()
    {
        try {
            $options = [PDO::MYSQL_ATTR_INIT_COMMAND => dbsetname];
            $conn = new PDO("mysql:host=" . dbhost . ";dbname=" . dbname, dbuser, dbpass, $options);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return  $conn;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            return null;
        }
    }

    public static function query(string $sql)
    {
        try {
            $conn = PDODBController::connection();
            if (empty($sql)) {
                return false;
            }
            if (!$conn) {
                return false;
            }

            $results = $conn->query($sql);

            if (!$results) {
                $conn = null;
                return false;
            }

            if (!(preg_match("/select/i", $sql) || preg_match("/show/i", $sql))) {
                $conn = null;
                return true;
            } else {
                $rows = $results->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) === 0) {
                    $conn = null;
                    return [];
                } else {
                    $conn = null;
                    return $rows;
                }
            }
        } catch (PDOException $e) {
            echo "Query failed: " . $e->getMessage();
            return false;
        }
    }

    public static function insert(string $sql): Response
    {
        try {
            $data = PDODBController::query($sql);
            if ($data) {
                return getRes([], Message::addSuccess, Status::success);
            } else {
                return getRes([], Message::addFail, Status::fail);
            }
        } catch (Exception $e) {
            return getRes([], $e->getMessage(), Status::fail);
        }
    }

    public static function update(string $sql): Response
    {
        try {
            $data = PDODBController::query($sql);
            if ($data) {
                return getRes([], Message::updateSuccess, Status::success);
            } else {
                return getRes([], Message::updateFail, Status::fail);
            }
        } catch (Exception $e) {
            return getRes([], $e->getMessage(), Status::fail);
        }
    }

    public static function delete(string $sql): Response
    {
        try {
            $data = PDODBController::query($sql);
            if ($data) {
                return getRes([], Message::deleteSuccess, Status::success);
            } else {
                return getRes([], Message::deleteFail, Status::fail);
            }
        } catch (Exception $e) {
            return getRes([], $e->getMessage(), Status::fail);
        }
    }

    public static function selectOne(string $sql): Response
    {
        try {
            $data = PDODBController::query($sql);
            return getRes($data, Message::listOne, Status::success);
        } catch (Exception $e) {
            return getRes([], $e->getMessage(), Status::fail);
        }
    }

    public static function selectPage(object $data, string $sqlCount, string $sqlPage, string $orderBy): Response
    {
        try {
            $page = $data->page;
            $limit = $data->limit;

            $dataCount = $data = PDODBController::query($sqlCount);
            $numRow = $dataCount[0]['num'];

            if ($numRow > 0) {

                $offset = (($page - 1) * $limit);

                $by = " order by {$orderBy} desc limit $limit offset $offset";

                $data = $data = PDODBController::query($sqlPage . $by);
                $dataList = $data;
            } else {
                $dataList = [];
            }
            $myPage = Pagination($numRow, $dataList, $limit, $page);

            return getRes($myPage, Message::listPage, Status::success);
        } catch (Exception $e) {
            return getRes([], $e->getMessage(), Status::fail);
        }
    }

    public static function selectAll(string $sql): Response
    {
        try {
            $data = PDODBController::query($sql);
            return getRes($data, Message::listAll, Status::success);
        } catch (Exception $e) {
            return getRes([], $e->getMessage(), Status::fail);
        }
    }

    public function lastID()
    {
        return PDODBController::connection()->lastInsertId();
    }

    public function beginTran()
    {
        return PDODBController::connection()->beginTransaction();
    }

    public function execut($sql)
    {
        return PDODBController::connection()->exec($sql);
    }

    public function commit()
    {
        return PDODBController::connection()->commit();
    }

    public function rollback()
    {
        return PDODBController::connection()->rollback();
    }

    public function closed()
    {
        return PDODBController::connection() == null;
    }
}
