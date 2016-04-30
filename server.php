<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//phpinfo();die;

require('vendor/autoload.php');

use RedBeanPHP\R;

class Database {

    public function __construct()
    {
    	R::setup();
    }

    /**
	 * Add two operands
	 * @param interge 
	 * @return interge
	 */
    public function add($table, $data = [])
    {
		$row = R::dispense($table);
		$row->import($data);
		R::store($row);

        return R::exportAll($row->fresh(), true)[0];
    }    
    
    /**
	 * Add two operands
	 * @param interge 
	 * @return interge
	 */
    public function all($table)
    {
        return R::exportAll(R::findAll($table), true);
    }

}

$server = new Yar_Server(new Database());
$server->handle();
