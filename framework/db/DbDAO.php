<?php
//直接操作数据库的基类
abstract class DbDAO
{
    /**
     * @var PDO
     */
    protected $_db = null;
	protected $_table = null; //具体表

    public $_structure; //表结构

    protected static $_instance;

    protected $_parts = array();

	protected $_config = array(); //配置相关
	//table指定表  dbindex指定数据库配置
    function __construct()
	{
        $dbprefix = CFactory::loadConfig('db_table_perfix');
        $this->_table = $dbprefix.$this->_table;
		$this->initDb();
    }

	function initDb()
	{
		$dbconfig = CFactory::loadConfig('db');
		$this->_config = array_merge($this->_config, $dbconfig);
		$this->_db = $this->getDb($dbconfig);
	}

	/**
	 * 重新实例化db
	 */
	function reConnectDb()
	{
		$dbconfig = CFactory::loadConfig('db');
		$this->_db = null;
		$this->_db = $this->getDb($dbconfig,true);
	}

    /**
     * 实例化db
     * @param $config
	 * @param $instanceNow 是否直接实例化
     * @return mixed
     */
    protected function getDb($config, $instanceNow = false)
    {
        $insancenameset =  hash('crc32',serialize($config));
        if(!isset(self::$_instance[$insancenameset]) || $instanceNow){
            $dsn = "mysql:dbname={$config['dbname']};host={$config['host']}";
            try{
                if( isset($config['charset']) && $config['charset']!='utf8' )
                    $alertcommd = "set NAMES '".$config['charset']."'";
                else
                    $alertcommd ="set NAMES 'utf8'";

                $option = array(PDO::ATTR_AUTOCOMMIT=>1, PDO::ATTR_CASE=>PDO::CASE_NATURAL,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::MYSQL_ATTR_INIT_COMMAND=>$alertcommd);
                self::$_instance[$insancenameset] = new PDO($dsn, $config['username'], $config['password'], $option);
            }catch (PDOException $e){
				var_dump($e);
            }catch (Exception $e){
				var_dump($e);
			}
        }
        return self::$_instance[$insancenameset];
    }

    /**
     * 解析where语句
     * @param $cond array('id' => 1)  array(0 => '1', 1 => '2')
     * @return mixed|string
     */
    protected function _parseWhere($cond)
	{
		$where = '';
		if (is_array($cond)) {
			foreach ($cond as $field => $value) {
				if (strpos($field, '?') === false) {
					if (preg_match('/^\d$/i', $field))
					{
						$quote  = trim($value);
						if( strtolower(substr($quote,0,3))=="and" )
						    $where .= $quote;
						else
						    $where .= ' AND '.$quote;
					}
					else
					{
						$text = is_array($value) ? "$field IN (?)" : "`$field` = ?";
						$quote  = trim($this->quoteInto($text, $value));
						$where .= preg_match('/^OR /i', $field) ? ' '.$quote : ' AND '.$quote;
					}
				}
				else {
					$text = $field;
                    if( is_array($value) )
                    {
                        $quote = array();
                        foreach($value as $v)
                        {
                            if( $v )
                                $quote[] = trim($this->quoteInto($text, $v));
                        }
                        $quote = $quote?implode(' AND ',$quote):'';
                    }
                    else
					    $quote  = trim($this->quoteInto($text, $value));
					$where .= preg_match('/^OR /i', $field) ? ' '.$quote : ' AND '.$quote;
				}
			}
			$where = preg_replace('/^(OR|AND)\s+/i', '', trim($where));
		} else if ($cond) {
			$where = strval($cond);
		}
		return $where;
	}

    /*CURD================================START*/
    /**
     * 查询
     * @param null $cond
     * @param null $order
     * @param null $count
     * @param null $offset
     * @param null $cols
     * @return mixed
     */
    protected function _select($cond = null,$cols = null,$order = null, $count = null, $offset = null)
	{
        // initial SELECT [DISTINCT] [FOR UPDATE]
        $sql = "SELECT";
        if (!empty($this->_parts['distinct'])) {
            $sql .= " DISTINCT";
        }
        if (!empty($this->_parts['forUpdate'])) {
            $sql .= " FOR UPDATE";
        }
        $sql .= "\n\t";

        // add columns
        if (is_string($cols)){
            $sql .= "{$cols}\n";
        }else {
            if (empty($cols)){
                $cols = $this->_structure;
            }
            $sql .= implode(",\n\t", $cols) . "\n";
        }

        // from these tables
        if ($this->_table) {
            $sql .= "FROM ";
            $sql .= $this->_table . "\n";
        }

        // with these where conditions
        if ($cond) {
            $sql .= "WHERE\n\t";
            $sql .= $this->_parseWhere($cond) . "\n";
        }

        // grouped by these columns
        if (!empty($this->_parts['group'])) {
            $sql .= "GROUP BY\n\t";
            $sql .= implode(",\n\t", $this->_parts['group']) . "\n";
        }

        // having these conditions
        if (!empty($this->_parts['having'])) {
            $sql .= "HAVING\n\t";
            $sql .= implode("\n\t", $this->_parts['having']) . "\n";
        }

        // ordered by these columns
        if ($order) {
            $sql .= "ORDER BY\n\t";
            $sql .= implode(",\n\t", $order) . "\n";
        }

        if (!empty($count)){
            $sql .= " LIMIT {$count}";
        }
        if (!empty($offset)){
            $sql .= " OFFSET {$offset}";
        }
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 查询结果集中第一条记录
     * @param null $cond
     * @param null $order
     * @param null $count
     * @param null $offset
     * @param null $cols
     * @return null
     */
    protected function _selectOne($cond = null, $cols = null,$order = null, $count = null, $offset = null)
	{
		$rows = $this->_select($cond, $cols,$order, $count, $offset);
		return isset($rows[0]) ? $rows[0] : null;
	}


    /**
     * 通过某个字段值获取记录
     * @param $field
     * @param $value
     * @param null $cols
     * @return mixed
     */
    protected function _getByField($field, $value, $cols = null)
	{
		return $this->_select(array($field=>$value), $cols, null, null, null);
	}

    /**
     * 通过某个字段值获取第一条记录
     * @param $field
     * @param $value
     * @param null $cols
     * @return null
     */
    protected function _getByFieldOne($field, $value, $cols = null)
	{
		$rows = $this->_getByField($field, $value, $cols);
		return isset($rows[0]) ? $rows[0] : null;
	}

    /**
     * 获取满足条件的记录数
     * @param $cond
     * @return int
     */
    protected function _count($cond=null)
	{
        $result = $this->_select($cond, 'COUNT(*)', null, null, null);
        return intval($result[0]['COUNT(*)']);
	}

    /**
     * @param $sql
     * @param array $bind
     * @return PDOSTATEMENT
     * @throws PDOException
     */
    function query($sql, $bind = array())
    {
		$this->log("sql----{$sql}");
		$this->log("bind----{$bind}");
        try
        {
            $stmt = $this->_db->prepare($sql);
			$stmt->execute((array) $bind);
        }
        catch ( PDOException $e) {
			if ($this->_config['reconnect']){
				$this->reConnectDb();
				if($this->checkDbStatus()){ //重新执行一次查询
					return $this->query($sql,$bind,false);
				}else {
					$errs = $this->_db->errorInfo();
					$this->log("pdo reconnect error----code:{$errs[1]},msg:{$errs[2]}");
				}
			}else{
				throw new PDOException($e);
			}
        }
        return $stmt;
    }

	/**
	 * 检测db的链接状态
	 * @return bool
	 */
	function checkDbStatus()
	{
		$flag = true;
		$status = $this->_db->getAttribute(PDO::ATTR_SERVER_INFO);
		if (($status == 'MySQL server has gone away') || ($status == 'Lost connection to MySQL server during query')){
			$flag = false;
		}
		return $flag;
	}

    /**
     * 插入一条记录
     * @param $datas
     * @return mixed
     */
    protected function _insert($datas)
	{
        return $this->__insert($this->_table, $datas);
	}

    protected function __insert($table, $bind)
    {
        // col names come from the array keys
        $cols = array_keys($bind);

        // build the statement
        $sql = "INSERT INTO $table "
            . '(`' . implode('`, `', $cols) . '`, `raw_add_time`) '
            . 'VALUES (:' . implode(', :', $cols) . ', NOW()'.
            ')';
        // execute the statement and return the number of affected rows
        $result = $this->query($sql, $bind);
        return $result->rowCount();
    }

    /**
     * 插入一条记录，返回lastid
     * @param $datas
     * @return mixed
     */
    protected function _insertGetLid($datas)
	{
		$this->__insert($this->_table, $datas);
		return $this->_db->lastInsertId();
	}

    /**
     * 更新记录
     * @param $datas
     * @param null $cond
     * @return mixed
     */
    protected function _update($datas, $cond = null)
	{
		return $this->__update($this->_table, $datas, $this->_parseWhere($cond));
	}

    /**
     * @param $table
     * @param $bind
     * @param $where
     * @return int
     */
    protected function __update($table, $bind, $where)
    {
        // build "col = :col" pairs for the statement
        $set = array();
        foreach ($bind as $col => $val) {
            $set[] = "`$col` = :$col";
        }

        // build the statement
        // $sql = 'UPDATE '.$table.' SET '.implode(', ', $set).(($where) ? " WHERE $where" : '');
        $sql = "UPDATE $table "
            . 'SET ' . implode(', ', $set)
            . (($where) ? " WHERE $where" : '');
        // execute the statement and return the number of affected rows
        $result = $this->query($sql, $bind);
        return $result->rowCount();
    }

    /**
     * 删除记录
     * @param array $cond
     * @return mixed
     */
    protected function _delete($cond = array())
	{
		return $this->__delete($this->_table, $this->_parseWhere($cond));
	}


    /**
     * Deletes table rows based on a WHERE clause.
     *
     * @param string $table The table to udpate.
     * @param string $where DELETE WHERE clause.
     * @return int The number of affected rows.
     */
    protected function __delete($table, $where)
    {
        // build the statement
        $sql = "DELETE FROM $table"
            . (($where) ? " WHERE $where" : '');

        // execute the statement and return the number of affected rows
        $stmt = $this->query($sql);
        return $stmt->rowCount();
    }
    /*CURD================================END*/
    
    function quoteInto($text, $value)
    {
        return str_replace('?', $this->quote($value), $text);
    }

	/**
	 * Safely quotes a value for a SQL stament
	 * @param $value
	 * @return string
	 */
	function quote($value)
	{
		if (is_array($value)){
			foreach ($value as &$val){
				$val = $this->quote($val);
			}
			return implode(', ',$value);
		} else {
			return $this->_db->quote($value);
		}
	}

	/**
	 * 开启事务
	 * @return bool
	 */
	function _beginTransaction()
	{
		return $this->_db->beginTransaction();
	}

	/**
	 * 提交事务
	 * @return bool
	 */
	function _commit()
	{
		return $this->_db->commit();
	}

	/**
	 * 回滚事务
	 * @return bool
	 */
	function _rollback()
	{
		return $this->_db->rollBack();
	}

	/**
	 * 日志
	 * @param $log
	 * @return mixed
	 */
	function log($log)
	{
		if (!empty($this->_config['logFile']))
			error_log(date('Y-m-d H:i:s')."\t\t{$log}\n", 3,$this->_config['logFile']);
	}
}