<?php
require_once FRAMEWORK_ROOT.'/db/DbDAO.php';
/**
 * DbCrudDAO 操作类， 主要就行单表操作
 * Class DbCrudDAO
 */
abstract class DbCrudDAO extends DbDAO
{
    /**
     * 当前 DAO 对应的默认表的主键，多关键字则使用数组。继承类可直接覆写此属性默认值达到设置主关键字的目的。
     * @var string|array
     */
    protected $_pk;

    protected $_table;
    function __construct()
    {
        parent::__construct();
    }

    /**
     * @param null $cond
     * @param null $order
     * @param null $count
     * @param null $offset
     * @param null $cols
     * @return mixed
     */
    function get($cond = null, $cols = null,$order = null, $count = null, $offset = null)
    {
        $passtime = microtime(true);
        if (is_null($order) && isset($this->_pk) && is_string($this->_pk))
            $order = array($this->_pk . ' desc');
        return $this->_select($cond, $cols,$order, $count, $offset);
    }

    /**
     * @param array $cond
     * @return int
     */
    function getCount($cond=null)
    {
        return $this->_count($cond);
    }

    /**
     * @param $id
     * @param null $cols
     * @return null
     */
    function getByPk($id,$cols=null)
    {
        if (is_string($this->_pk)) {
            $item = $this->_getByFieldOne($this->_pk, $id,$cols);
        } else {
            $pk = $this->_pk;
            if (is_array($id)) {
                if (isset($id[0]))
                    $cond = array_combine($pk, $id);
                else
                    $cond = array_intersect_key($id, array_flip($pk));
            } else
                $cond = array_combine($pk, array($id));

            $item = $this->_selectOne($cond,$cols);
        }
        return $item;
    }

    /**
     * @param $cond
	 * @param null $cols
     * @return null
     */
    function getByFk($cond,$cols=null)
    {
        return $this->_selectOne($cond,$cols);
    }

    /**
     * @param $datas
     * @return mixed
     */
    public function addinstant($datas)
    {
        return $this->_insert($datas);
    }

    /**
     * @param $datas
     * @return mixed
     */
    function add($datas)
    {
        return $this->_insertGetLid($datas);
    }

    /**
     * @param $datas
     * @param null $cond
     * @return mixed
     */
    function update($datas, $cond)
    {
        return $this->_update($datas, $cond);
    }

    /**
     * @param $datas
     * @return mixed
     * @throws Exception
     */
    function updateByPk($datas)
    {
        if (!is_array($this->_pk))
            $cond = array($this->_pk => $datas[$this->_pk]);
        else
            $cond = array_intersect_key($datas, array_fill_keys($this->_pk, null));
        if (!$cond)
            throw new Exception('Cound Find _pk in ' . get_class($this) . '::updateByPk:datas!');
        if (!is_array($this->_pk))
            unset($datas[$this->_pk]);
        else
            $datas = array_diff_key($datas, $cond);
        return $this->update($datas, $cond);
    }

    /**
     * @param array $cond
     * @return mixed
     */
    function delete($cond = array())
    {
        return $this->_delete($cond);
    }

    /**
     * @param $ids
     * @return bool|mixed
     * @throws Exception
     */
    function deleteByPk($ids)
    {
        if (empty($ids))
            return false;
        if (!is_array($this->_pk) && !is_array($ids))
            $cond = array($this->_pk => $ids);
        else {
            $pk = is_array($this->_pk) ? $this->_pk : array($this->_pk);
            if (is_array($ids)) {
                if (isset($ids[0]))
                    $cond = array_combine($pk, $ids);
                else
                    $cond = $ids;
            } else {
                $cond = array_combine($pk, array($ids));
            }
        }
        if (!$cond)
            throw new Exception('Cound Find _pk in ' . get_class($this) . '::deleteByPk:datas!');
        return $this->delete($cond);
    }

    /**
     * @param $fk
     * @param $value
     * @return mixed
     */
    function deleteByFk($fk, $value)
    {
        return $this->delete(array($fk => $value));
    }

    /**
     * @param $sql
     * @param null $params
     * @return int
     */
    function queryGetCount($sql, $params = null)
    {
        $stmp = $this->query($sql, $params);
        return $stmp->rowCount();
    }

    /**
     * @param $sql
     * @param null $params
     * @return array
     */
    function queryFetchAll($sql, $params = null)
    {
        $stmp = $this->query($sql, $params);
        return $stmp->fetchAll(PDO::FETCH_ASSOC);
    }

	/**
	 * 开启事务
	 * @return mixed
	 */
	function beginTransaction()
	{
		return $this->_beginTransaction();
	}

	/**
	 * 提交事务
	 * @return mixed
	 */
	function commit()
	{
		return $this->_commit();
	}

	/**
	 * 回滚事务
	 * @return mixed
	 */
	function rollback()
	{
		return $this->_rollback();
	}

	/**
	 * 批量添加
	 * @param $arr
	 * @return mixed
	 */
	function addBatch($arr)
	{
		$sql = "INSERT INTO {$this->_table} (";
		$colStr = '';
		$firstArr = reset($arr);
//		$colStr = implode(',',array_keys($firstArr));
		foreach (array_keys($firstArr) as $key){
			$colStr .= ",`{$key}`";
		}
		$colStr .= ",`raw_add_time`";
		$colStr = substr($colStr,1);
		$sql .= $colStr. ' ) VALUES ';
		$valStr = '';
		foreach ($arr as $ea){
			$valStr .= ',(';
			$tmpStr = '';
			foreach ($ea as $val){
				$tmpStr .= ",'{$val}'";
			}
			$tmpStr .= ',NOW()';
			$valStr .= substr($tmpStr,1);
			$valStr .= ')';
		}
		$valStr = substr($valStr,1);
		$sql .= $valStr;
		$stmt = $this->query($sql);
		return $stmt->rowCount();
	}

	/**
	 * 获取表名---暴露给外部
	 * @return mixed
	 */
	function getTableName()
	{
		return $this->_table;
	}
}