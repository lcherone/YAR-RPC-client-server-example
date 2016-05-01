<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//phpinfo();die;

require('vendor/autoload.php');

use RedBeanPHP\R;

class Hosts {

	public function __construct()
	{
		R::setup('sqlite:/tmp/hosts1.db');
		
		R::ext('xdispense', function($type) { 
		  return R::getRedBean()->dispense($type); 
		});
	}
	
	public function get_machine_id()
    {
        if (file_exists('./machine-id')) {
            return file_get_contents('./machine-id');
        }

        if (file_exists('/var/lib/dbus/machine-id')) {
            $id = trim(`cat /var/lib/dbus/machine-id`);
            file_put_contents('./machine-id', $id);
            return $id;
        }

        if (file_exists('/etc/machine-id')) {
            $id = trim(`cat /etc/machine-id`);
            file_put_contents('./machine-id', $id);
            return $id;
        }

        $id = sha1(uniqid(true));
        file_put_contents('./machine-id', $id);
        return $id;
    }

	/**
     * Inspect table
     * 
     * @usage: 
     *  $client = new yar_client("http://example.com/hosts.php");
     *  $client->inspect();
     *
     * @param string $table
     * @return array
     */
	public function inspect($table = '')
	{
		if (!empty($table)) {
			return R::inspect($table);
		} else {
			return R::inspect();
		}
	}

	/**
     * Create bean
     *
     * json $plink->create(string, array);
     *
     * @param array $params
     */
	public function create($table, $data = [])
	{
		$row = R::xdispense($table);
		$row->import($data);
		R::store($row);

		return R::exportAll($row);
	}


	/**
     * Create bean
     *
     * json $plink->exec(string);
     *
     * @param array $params
     */
	public function exec($table, $query = [], $params = [])
	{
		return R::exec($table);
	}

	/**
     * Find all
     *
     * json $plink->findAll(string, string, array);
     *
     * @link http://www.redbeanphp.com/index.php?p=/finding#find_all
     * @param array $params
     */
	public function findAll($table, $query = [], $params = [])
	{
		if (!empty($query) && !empty($params)) {
			$result = R::findAll($table, $query, $params);
		} elseif (!empty($query) && empty($params)) {
			$result = R::findAll($table, $query);
		} else {
			$result = R::findAll($table);
		}

		return R::exportAll($result);
	}

	/**
     * Get all - multidimensional array
     *
     * json $plink->getAll(string, string, array);
     *
     * @link http://www.redbeanphp.com/index.php?p=/querying
     * @param array $params
     */
	public function getAll($table, $query = [], $params = [])
	{
		if (!empty($query)) {
			return R::getAll($table, $query);
		} else {
			return R::getAll($table);
		}
	}

	/**
     * Get row - fetch a single row
     *
     * json $plink->getRow(string, string, array);
     *
     * @link http://www.redbeanphp.com/index.php?p=/querying
     * @param array $params
     */
	public function getRow($table, $query = [], $params = [])
	{
		if (!empty($query)) {
			return R::getRow($table, $query);
		} else {
			return R::getRow($table);
		}
	}

	/**
     * Get col - fetch a single column
     *
     * json $plink->getRow(string, string, array);
     *
     * @link http://www.redbeanphp.com/index.php?p=/querying
     * @param array $params
     */
	public function getCol($table, $query = [], $params = [])
	{
		if (!empty($query)) {
			return R::getCol($table, $query);
		} else {
			return R::getCol($table);
		}
	}

	/**
     * Get cell - fetch a single cell
     *
     * json $plink->getRow(string, array);
     *
     * @link http://www.redbeanphp.com/index.php?p=/querying
     * @param array $params
     */
	public function getCell($table, $query = [], $params = [])
	{
		if (!empty($query)) {
			return R::getCell($table, $query);
		} else {
			return R::getCell($table);
		}
	}

	/**
     * Get associative col - fetch a associative array cell
     *
     * json $plink->getAssoc(string, array);
     *
     * @link http://www.redbeanphp.com/index.php?p=/querying
     * @param array $params
     */
	public function getAssoc($table, $query = [], $params = [])
	{
		if (!empty($query)) {
			return R::getCell($table, $query);
		} else {
			return R::getCell($table);
		}
	}

	/**
     * Get associative row - fetch a associative array row
     *
     * json $plink->getAssocRow(string, array);
     *
     * @link http://www.redbeanphp.com/index.php?p=/querying
     * @param array $params
     */
	public function getAssocRow($table, $query = [], $params = [])
	{
		if (!empty($query)) {
			return R::getAssocRow($table, $query);
		} else {
			return R::getAssocRow($table);
		}
	}

	/**
     * Find one
     *
     * json $plink->findAll(string, string, array);
     *
     * @link http://www.redbeanphp.com/index.php?p=/finding#find_one
     * @param array $params
     */
	public function findOne($table, $query = '', $params = [])
	{
		if (!empty($query) && !empty($params)) {
			$result = R::findOne($table, $query, $params);
		} elseif (!empty($query) && empty($params)) {
			$result = R::findOne($table, $query);
		} else {
			$result = R::findOne($table);
		}

		return R::exportAll($result);
	}

	/**
     * Find Like
     *
     * json $plink->findAll(string, array, string);
     *
     * @link http://www.redbeanphp.com/index.php?p=/finding#find_like
     * @param array $params
     */
	public function findLike($table, $query = [], $params = [])
	{
		$result = R::findLike($table, $query, $params);
		return R::exportAll($result);
	}

	
	/**
     * Load
     *
     * json $plink->load(string $table, int $id);
     *
     * @link http://www.redbeanphp.com/index.php?p=/finding#find_create
     * @param array $params
     */
	public function load($table, $id)
	{
		$result = R::load($table, $id);
		return R::exportAll($result)[0];
	}

	/**
     * Get most recent row
     *
     * json $plink->mostRecentRow(string);
     *
     * @param array $params
     */
	public function mostRecentRow($table, $query = [], $params = [])
	{
		$result = R::findOne($table, ' ORDER BY id DESC LIMIT 1 ');
		return R::exportAll($result);
	}

	/**
     * Update bean
     * json $plink->update(string, string, array);
     *
     * @param array $params
     */
	public function update($table, $id, $params = [])
	{
		$result = R::load($table, $id);
		$result->import($params);

		R::store($result);
		return R::exportAll($result);
	}

	/**
     * Count beans
     * json $plink->count(string [, array]);
     *
     * @param array $params
     */
	public function count($table, $query = [], $params = [])
	{
		if (!empty($query)) {
			$result = R::count($table, $query);
		} else {
			$result = R::count($table);
		}

		return $result;
	}

	/**
     * Save bean - alias of update()
     * json $plink->save(string, string, array);
     *
     * @param array $params
     */
	public function save($table, $query = [], $params = [])
	{
		return $this->update($params);
	}

	/**
     * Delete bean
     *
     * json $plink->delete(string, int);
     *
     * @param array $params
     */
	public function delete($table, $id)
	{
		$result = R::load($table, $id);
		return R::trash($result);
	}
	
	/**
     * Wipe bean
     *
     * json $plink->wipe(string, int);
     *
     * @param array $params
     */
	public function wipe($table)
	{
		return R::wipe($table);
	}

}

$server = new Yar_Server(new Hosts());
$server->handle();
