<?php // if (!defined('SYS_PATH')) exit('Access denied');

/*
 * Шлях: SYS_PATH/libraries/db.php
 *
 * Робота з базою даних.
 * 1.0.1    25.04.2013 - додано getAllData(), getAllDataByFieldInArray(), language(), latterUAtoEN())
 * 1.0.2    16.07.2014 - додано getCount(), register(); розширено (можуть приймати в якості умови масив): getAllDataById(), getAllDataByFieldInArray(+сорутвання)
 * 1.0.3    06.11.2014 - додано getQuery(), (12.11.2014) до getAllDataById(), getAllDataByFieldInArray() додано авто підтримку до умови IN
 * 2.0      28.09.2015 - переписано код getAllDataById(), getAllDataByFieldInArray(), getCount(). Додано службову функцію makeWhere(). Додано запити по конструкції: prefix(), select(), join(), order(), limit(), get().
 * 2.0.1    26.03.2016 - до get() додано параметр debug, що дозволяє бачити кінцевий запит перед запуском, виправлено помилку декількаразового запуску get()
 * 2.0.2    01.04.2016 - до makeWhere() додано параметр сортування НЕ '!'
 * 2.0.3    26.07.2016 - адаптовано до php7
 * 2.0.4    12.09.2016 - додано getAliasImageSizes()
 * 2.1      22.09.2016 - updateRow(), deleteRow() адаптовано через makeWhere(); у makeWhere() виправлено роботу з нульовими значеннями; до getRows() додати перевірку на тип single
 * 2.1.1    27.09.2016 - до makeWhere() додано повторюване поле через "+"
 * 2.2      19.12.2016 - додано sitemap_add(), sitemap_redirect(), sitemap_update(), sitemap_index(), sitemap_remove(), cache_clear()
 * 2.2.1    08.02.2017 - додано "chaining methods";
 * 2.2.2    05.09.2017 - у випадку успіху insertRow() повернає getLastInsertedId(); fix getRows('single')
 * 2.2.3    29.10.2017 - додано count_db_queries, showDBdump, виправлено помилку у where() для пустого/нульового значення
 * 2.2.4    01.11.2017 - додано group(), оптимізовано роботу getAliasImageSizes()
 * 2.2.5    01.12.2017 - amp версію виключено з індексації
 * 2.3      17.03.2018 - додано insertRows() - мультивставка рядків
 * 2.4      10.09.2019 - додано cache_add(), cache_get(), cache_delete(), cache_delete_all() - робота з файловим кешем
 * 2.4.1    19.11.2019 - Перейменовано cache_clear() => sitemap_cache_clear(). Інтегровано всередину онулення загального індексу по сайту
 * 2.4.2    26.11.2019 - до makeWhere() додано keyValue #! ['1c_status' => '#!c.status']
 * 2.4.3    11.12.2019 - до cache_add(), cache_get(), cache_delete(), cache_delete_all() - додано мультимовність
 * 2.4.5    16.12.2019 - до getAliasImageSizes() додано кешування у сесії
 * 2.4.6    20.02.2020 - до get('count') додано (виправлено) коректно роботу параметрів $debug, $get
 * 2.5      20.02.2020 - до makeWhere() додано keyValue & (&, &&, &&&, &&&&+) ['&' => 'p.old_price > p.price || p.promo > 0'] - дозволяє додати "складні" sql умови до запиту
 * 2.6      12.06.2020 - ініціалізація першого технічного запиту до БД 'SET NAMES utf8', тільки перед першим реальним запитом. Мінімізація пустих запитів.
                            додано public $this->saveDBlog. оновлено showTime()
                            до insertRows() доопрацьовано default значення у keys
 * 2.7      19.06.2020 - оновлено sitemap_*() - зміни системної таблиці wl_sitemap:link_sha1
                           оновлено getAliasImageSizes(), sitemap_cache_clear() => html_cache_clear() - робота з файловим кешем
                           додано getHTMLCacheKey(), getCacheContentKey(), $this->version
 * 2.8      05.08.2020 - додано redis_set(), redis_get(), redis_del(), redis_delByKey(), redis_ping(), redis_do(), $this->html_cache_in_redis
 * 2.8.1    09.12.2020 - updateRow() values can set NULL, numeric format. select(.., .., .., clear = true) add default clear param
 * 2.8.2    15.01.2021 - makeWhere() масив значень з одного елементу {key} IN ({value}) => {key} = {value}
 * 2.9      15.02.2021 - makeWhere() підтримка FULLTEXT пошуку. Ключ ~
 * 2.9.1    26.08.2021 - insertRows(.., $field_type = array())
 * 2.9.2    09.06.2022 - updateRow(.., $field_type = array())
 * 2.9.3 - add $cfg['port'], support php 7.4+
 * 2.9.4 - add getEnumList(), getTableFields() @author Oleh Holovkin
 * 2.9.5 - getRows() support arrayIndexed завжди масив об'єктів [id] => {}
 * 2.9.6 - getRows() arrayIndexed support {$key} [$key] => {}
 * 2.9.7 - add addWhereAmp(), makeWhere() private => public, get(type:'query')
 * 2.9.8    23.08.2023 - addWhereAmp $where as reference
 * 3.0 - fulltext search support '-' in words, support own prefix in makeWhere(), fix sitemap dublicates
 * 3.1 - add getGroupCount() by Oleh Holovkin
 * 3.2 - add saveDBlog_more_sec to db.log if query time > $saveDBlog_more_sec
 * 3.3      20.06.2024 - add redis_exists(), cache_exists()
 * 3.3.1    25.09.2024 - add executeMultiQuery(), fix array_values in makeWhere()
 * 3.3.2    20.11.2024 - fix numeric with 'E'
 * 3.3.3    Oleh Holovkin: add getDBName(), checkTable()
 * 3.3.4	**.02.2025 - add setConnect(); update affectedRows(); register() use user_id
 * 3.3.5 query prefix
 * 3.3.6    31.03.2025  updateRow() return true if row updated, false if not exists.
						getRows() support array:id_key as synomic to arrayIndexed:id_key
*/

class Db {

    private $connects = array();
    private $current = 0;
    private $cfg;
    public $result;
    public $redis = false;
    public $html_cache_in_redis = false;
    public $version = '3.3.5';
    public $imageReSizes = array();
    public $count_db_queries = 0;
    public $showDBdump = false;
    public $saveDBlog = false; // to db.log
    public $saveDBlog_more_sec = 3; // to db.log if query time > $saveDBlog_more_sec
    public $db_log_path = 'db.log';

    /*
     * Отримуємо дані для з'єднання з конфігураційного файлу
     */
    function __construct($cfg)
    {
        $port = $cfg['port'] ?? 3306;
        $this->newConnect($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database'], $port);
		$this->cfg = $cfg;
        if(!empty($cfg['redis_host']))
        {
            $this->redis = new Redis();
            $this->redis->connect($cfg['redis_host'], $cfg['redis_port']);
            if(!empty($cfg['redis_auth']))
                $this->redis->auth($cfg['redis_auth']);
            if(!empty($cfg['html_cache_in_redis']))
                $this->html_cache_in_redis = true;
        }

        if($this->saveDBlog || $this->saveDBlog_more_sec) {
            $path = APP_PATH . 'logs/db';
            if(!file_exists($path))
                mkdir($path, 0777, true);
            $this->db_log_path = $path . '/db_'.date('Y-m-d').'.log';
        }
    }

    /**
     * Створюємо з'єднання
     *
     * @param <string> $host назва серверу
     * @param <string> $user ім'я користувача
     * @param <string> $password пароль
     * @param <string> $database назва бази даних
     */
    public function newConnect($host, $user, $password, $database, $port = 3306)
    {
        $this->connects[] = new mysqli($host, $user, $password, $database, $port);
        $this->current = count($this->connects) - 1;

        /* check connection */
        if ($this->connects[$this->current]->connect_errno) {
            printf("DB Connect failed: %s\n", $this->connects[$this->current]->connect_error);
            exit();
        }
    }

	// можна змінити підключення до бд
	public function setConnect($index = 0)
	{
		if(count($this->connects) > 1 && isset($this->connects[$index]))
			$this->current = $index;
	}
	/**
	 *
	*	return currect connection db name
	*/
    public function name($index = 0)
    {
        return $this->names[$index] ?? NULL;
    }

    /**
	 *  Виконуємо запит
     *
	 * @param string $query запит
	 * @param bool $multi
	 *
	 * @return void
     */
    public function executeQuery($query, $multi = false )
    {
        if($this->count_db_queries === 0)
        {
            $this->connects[$this->current]->query('SET NAMES utf8');
            
            if ($this->saveDBlog)
                file_put_contents($this->db_log_path, PHP_EOL.$this->count_db_queries.': SET NAMES utf8'.PHP_EOL, FILE_APPEND);
            if ($this->showDBdump)
                echo $this->count_db_queries.': SET NAMES utf8 <hr>';

            $this->count_db_queries++;
        }

        if ($this->showDBdump || $this->saveDBlog || $this->saveDBlog_more_sec)
        {
            $this->time_start = microtime(true);
            $this->mem_start = memory_get_usage();
            
            if ($this->saveDBlog)
                file_put_contents($this->db_log_path, $this->count_db_queries.': '.$query.PHP_EOL, FILE_APPEND);
            if ($this->showDBdump)
                echo $this->count_db_queries.': '.$query;
            // if($this->count_db_queries == 11)
            // {
            //     echo "<pre>";
            //     debug_print_backtrace();
            //     echo "</pre>";
            // }
        }
	    if ( $multi ) {
		    $this->result = [];
		    $this->connects[ $this->current ]->multi_query( $query );
		    do {
			    if ( $result = $this->connects[ $this->current ]->store_result() ) {
				    $this->result[] = $result;
				    // Process the result set here if needed
				    $result->free();
			    }
		    }
		    while ( $this->connects[ $this->current ]->more_results() && $this->connects[ $this->current ]->next_result() );
	    } else {
		    $result = $this->connects[ $this->current ]->query( $query );

		    if ( !$result ) {
			    echo $this->connects[ $this->current ]->error;
		    } else {
			    $this->result = $result;
		    }
	    }
        $this->count_db_queries++;

        if($this->saveDBlog_more_sec) {
            $time = microtime(true) - $this->time_start;
            if($time > $this->saveDBlog_more_sec) {
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                file_put_contents($this->db_log_path, date('Y-m-d H:i:s') . ' '.$uri.PHP_EOL, FILE_APPEND);
                file_put_contents($this->db_log_path, $this->count_db_queries.': '.$query.PHP_EOL, FILE_APPEND);
                file_put_contents($this->db_log_path, $this->count_db_queries.': '.$this->showTime(true).PHP_EOL, FILE_APPEND);
                file_put_contents($this->db_log_path, debug_string_backtrace().PHP_EOL, FILE_APPEND);
                file_put_contents($this->db_log_path, '-------------------------------------------------------------'.PHP_EOL.PHP_EOL, FILE_APPEND);
            }
        }

        if ($this->saveDBlog)
            file_put_contents($this->db_log_path, $this->showTime(true).PHP_EOL, FILE_APPEND);
        if($this->showDBdump)
            $this->showTime();
    }

    public function executeMultiQuery($query) {
        return $this->executeQuery($query, true);
    }

    public function updateRow($table, $changes, $key, $row_key = 'id', $field_type = array()): bool
    {
        if($where = $this->makeWhere($key, $row_key)) {
            $update = "UPDATE `".$table."` SET ";
            foreach ($changes as $key => $value) {
                if(!empty($field_type[$key])) {
                    switch ($field_type[$key]) {
                        case 'number':
                        case 'int':
                        case 'integer':
                        case 'float':
                        case 'NULL':
                        case 'null':
                            $update .= "`{$key}` = {$value}, ";
                            break;

                        case 'text':
                        case 'string':
                            $value = $this->sanitizeString($value);
                            $update .= "`{$key}` = '{$value}', ";
                            break;
                        
                        default:
                            $update .= "`{$key}` = '{$value}', ";
                            break;
                    }
                }
                else
                {
                    if($value === NULL) {
                        $update .= "`{$key}` = NULL,";
                    }
                    else if (is_numeric($value) && strpos(strtoupper($value), 'E') === false) {
                        $update .= "`{$key}` = {$value},";
                    } else {
                        $value = $this->sanitizeString($value);
                        $update .= "`{$key}` = '{$value}',";
                    }
                }
            }
            $update = substr($update, 0, -1);
            $update .= " WHERE ".$where;

            $this->executeQuery($update);
            if($this->affectedRows() > 0)
                return true;
			$fields = implode(',', array_keys($changes));
			if($rows = $this->getQuery("SELECT {$fields} FROM `{$table}` WHERE {$where}", 'array')) {
				foreach ($rows as $row) {
					foreach ($changes as $key => $value) {
						if($row->$key != $value) {
							return false;
						}
					}
				}
				return true;
			}
        }
        return false;
    }

    public function insertRow($table, $changes)
    {
        $update = "INSERT INTO `".$table."` ( ";
        $values = '';
        foreach ($changes as $key => $value) {
            $value = $this->sanitizeString($value);
            $update .= '`' . $key . '`, ';
            $values .= "'{$value}', ";
        }
        $update = substr($update, 0, -2);
        $values = substr($values, 0, -2);
        $update .= ' ) VALUES ( ' . $values . ' ) ';
        $this->executeQuery($update);
        if($this->affectedRows() > 0)
            return $this->getLastInsertedId();
        return false;
    }

    public function insertRows($table, $keys = array(), $data = array(), $perQuery = 50, $field_type = array())
    {
        if(empty($keys) || empty($data))
            return false;

        $insert = "INSERT INTO `".$table."` ( ";
        foreach ($keys as $key => $default) {
            if(is_numeric($key))
                $key = $default;
            if(is_numeric($key))
                continue;
            $insert .= '`' . $key . '`, ';
        }
        $insert = substr($insert, 0, -2);
        $insert .= ' ) VALUES ';
        $inserted = $i = 0; $query = '';
        foreach ($data as $row) { 
            $values = '';
            foreach ($keys as $key => $default) {
                $value = '';
                if(is_numeric($key))
                    $key = $default;
                else
                    $value = $default;
                if(is_numeric($key))
                    continue;
                if(isset($row[$key]))
                    $value = $this->sanitizeString($row[$key]);
                if(!empty($field_type[$key]))
                {
                    switch ($field_type[$key]) {
                        case 'number':
                        case 'int':
                        case 'integer':
                        case 'float':
                            $values .= "{$value}, ";
                            break;

                        case 'text':
                        case 'string':
                            $values .= "'{$value}', ";
                            break;
                        
                        default:
                            $values .= "'{$value}', ";
                            break;
                    }
                }
                else
                {
                    if(is_numeric($value) && strpos(strtoupper($value), 'E') === false)
                        $values .= "{$value}, ";
                    else
                        $values .= "'{$value}', ";
                }
            }
            $values = substr($values, 0, -2);
            $query .= '( ' . $values . ' ), ';
            if(++$i > $perQuery)
            {
                $i = 0;
                $query = $insert . substr($query, 0, -2) . ';';
                $this->executeQuery($query);
                $inserted += $this->affectedRows();
                $query = '';
            }
        }
        if($i > 0)
        {
            $query = $insert . substr($query, 0, -2) . ';';
            $this->executeQuery($query);
            $inserted += $this->affectedRows();
        }

        return true;
    }

    public function getLastInsertedId()
    {
        return $this->connects[$this->current]->insert_id;
    }

    public function deleteRow($table, $id, $row_key = 'id')
    {
        $where = $this->makeWhere($id, $row_key);
        if($where != '')
        {
            $this->executeQuery("DELETE FROM `{$table}` WHERE {$where}");
            if($this->affectedRows() > 0)
                return true;
        }
        return false;
    }

    /**
     * Отримуємо рядки
     *
     * @return <array>
     */
    public function getRows($type = '')
    {
        if($type == 'single' && $this->result->num_rows != 1)
            return false;
        
        $type = explode(':', $type);
        if($this->result->num_rows > 1 || $type[0] == 'array' || $type[0] == 'arrayIndexed') {
            $objects = [];
            if(($type[0] == 'array' && !empty($type[1])) || $type[0] == 'arrayIndexed') {
                $arrayIndexed_key = $type[1] ?? 'id';
                while($obj = $this->result->fetch_object()) {
                    if(isset($obj->$arrayIndexed_key)) {
                        $objects[$obj->$arrayIndexed_key] = $obj;
                    }
                    else {
                        array_push($objects, $obj);
                    }
                }
            }
            else {
                while($obj = $this->result->fetch_object()){
                    array_push($objects, $obj);
                }
            }
            return $objects;
        }
        return $this->result->fetch_object();
    }

    /**
     * Отримуємо кількість рядків
     *
     * @return <int>
     */
    public function numRows()
    {
        return $this->result->num_rows;
    }

    /**
     * Отримуємо кількість задіяних рядків
     *
     * @return <int>
     */
    public function affectedRows()
    {
		return $this->connects[$this->current]->affected_rows;
    }

    /**
     * Очистити рядок
     *
     * @param <string> $data дані
     *
     * @return <string>
     */
    public function sanitizeString($data)
    {
		if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            if(get_magic_quotes_gpc())
                $data = stripslashes($data);
        }
        return $this->connects[$this->current]->escape_string($data);
    }

    public function mysql_real_escape_string($q)
    {
        return $this->connects[$this->current]->real_escape_string($q);
    }

    public function getQuery($query = false, $getRows = '')
    {
        if($query)
        {
            $this->executeQuery($query);
            if($this->numRows() > 0)
                return $this->getRows($getRows);
        }
        return false;
    }

    /**
     * Допоміжні функції
     */
    public function getAllData($table = false, $order = '')
    {
        if($table)
        {
            if($order != '') $order = ' ORDER BY '.$order;
            $this->executeQuery("SELECT * FROM `{$table}` {$order}");
            if($this->numRows() > 0)
                return $this->getRows('array');
        }
        return false;
    }

	/**
	 * @param string           $table
	 * @param int|string|array $key
	 * @param string           $row_key
	 *
	 * @return array|false
	 */
    public function getAllDataById($table, $key, $row_key = 'id')
    {
        if($table != '')
        {
            $where = $this->makeWhere($key, $row_key);
            if($where != '')
            {
                $this->executeQuery("SELECT * FROM `{$table}` WHERE {$where}");
                if($this->numRows() == 1)
                    return $this->getRows();
            }
        }
        return false;
    }

    public function getAllDataByFieldInArray($table, $key, $row_key = 'id', $order = '')
    {
        if($table != '')
        {
            $where = $this->makeWhere($key, $row_key);
            if($where != '')
            {
                if(is_array($key) && $row_key != '') $where .= ' ORDER BY '.$row_key;
                elseif($order != '') $where .= ' ORDER BY '.$order;
                $this->executeQuery("SELECT * FROM `{$table}` WHERE {$where}");
                if($this->numRows() > 0)
                    return $this->getRows('array');
            }
        }
        return false;
    }

    public function getCount($table = '', $key = '', $row_key = 'id' )
    {
        if($table != ''){
            $where = $this->makeWhere($key, $row_key);
            if($where != '')
                $where = "WHERE {$where}";

            $this->executeQuery("SELECT count(*) as count FROM `{$table}` {$where}");

            if($this->numRows() == 1)
            {
                $count = $this->getRows();
                return $count->count;
            }
        }
        return null;
    }

	public function getGroupCount($table = '', $key = '', $row_key = 'id', $group = false, $joinAr = []){
		if($table != ''){
			$where = $this->makeWhere($key, $row_key);
			if($where != '')
				$where = "WHERE {$where}";
			if(!empty($group)){
				$where .= (empty($where) ? '' : ' ').'GROUP BY '.$group;
			}
			$joinString = '';
			if ( !empty( $joinAr ) ) {
				$joins = [];
				foreach ($joinAr as $join){
					$jItem  = "LEFT JOIN `{$join['table']}` ON ";
					$iWhere = [];
					foreach ( $join['where'] as $c => $w ){
						$iWhere[] = "{$join['table']}.id = ".(str_replace('#', '', $w));
					}
					$jItem .= implode(' && ', $iWhere);
					$joins[] = $jItem;
				}

				$joinString .= implode(' ', $joins ).' ';
			}

			$this->executeQuery("SELECT COUNT(*) AS count FROM (SELECT count(*) as count FROM `{$table}` {$joinString}{$where}) AS grouped_counts;");

			if($this->numRows() == 1)
			{
				$count = $this->getRows();
				return $count->count;
			}
		}
		return null;
	}

    /**
     * makeWhere extra params
     * key rules: 
     * &, &&, &&&, &&&&+ - conditions as is: $where .= "({$value}) AND ";
     * +key: additional value with same key: `key`={$value_1} AND `key`={$value_2}
     * #key: without prefix key as is: "{key}={$value}" else "{$prefix}.{$key}={$value}"
     * 
     * value rules (on start):
     * word - "{key}='{$value}'" or "{$prefix}.{$key}='{$value}'"
     * ~word - fulltext search if several words, else LIKE %word%
     * %word - search " LIKE '%{$word}%' AND ";
     * @word - search " LIKE '{$word}%' AND ";
     * [word1, word2] - search IN ('word1', 'word2')
     * >word - search > word [for numbers]
     * <word - search < word [for numbers]
     * >=word - search >= word [for numbers]
     * <=word - search <= word [for numbers]
     * !word - search "{$key} != '{$value}' AND "
     * #!word - search "{$key} != {$value} AND "
     * #word - search "{$key} = {$value} AND "
     * " - "{$key} = '' AND ";
     * 0 - "{$key} = 0 AND ";
     *
     * @param  array|string $data - array [key => value] or string value
     * @param  string $row_key - row key for (string) $data: "{$prefix}.{$row_key} = '{$data}'"
     * @param  string $prefix - table prefix
     * @return string $where - sql where
     */
    public function makeWhere($data, $row_key = 'id', $prefix = false)
    {
        $where = '';
        if(is_array($data))
        {
            foreach ($data as $key => $value) {
                if($key != '' && $key[0] == '&')
                {
                    if($value[0] != '(')
                        $value = "({$value})";
                    $where .= $value.' AND ';
                }
                else if(!is_numeric($key) && $key != '')
                {
                    if($key[0] == '+')
                        $key = substr($key, 1);
                    if(is_string($value) && !empty($value) && $value[0] == '~')
                    {
                        $value = substr($value, 1);
                        $words = explode(' ', $value);
                        if(count($words) == 1)
                            $value = '%'.$value;
                        else
                        {
                            foreach ($words as &$w) {
                                if (strpos($w, '-') !== false) {
                                    // '-' is found in $w
                                    $w = '+"' . $w . '"';
                                } else {
                                    // '-' is not found in $w
                                    $w = '+'.$w;
                                }
                            }
                            $words = implode(' ', $words);
                            $where .= "MATCH ({$key}) AGAINST ('$words' IN BOOLEAN MODE) AND ";
                            continue;
                        }
                    }
                    
                    if($prefix && $key[0] != '#')
                        $where .= "{$prefix}.{$key}";
                    elseif($key[0] == '#')
                    {
                        $key = substr($key, 1);
                        $where .= $key;
                    }
                    else
                        $where .= "`{$key}`";

                    if(is_array($value))
                    {
                        if(count($value) == 1)
                        {
                            $val = array_values($value)[0];
                            $where .= (is_numeric($val) && strpos(strtoupper($val), 'E') === false) ? " = {$val} AND " : " = '{$val}' AND ";
                        }
                        else
                        {
                            $where .= " IN ( ";
                            foreach ($value as $v) {
                                $where .= "'{$v}', ";
                            }
                            $where = substr($where, 0, -2);
                            $where .= ') AND ';
                        }
                    }
                    elseif(is_numeric($value) && strpos(strtoupper($value), 'E') === false)
                        $where .= " = {$value} AND ";
                    elseif($value != '' || $value == 0)
                    {
                        $value = $this->sanitizeString($value);
                        if($value == '0')
                            $where .= " = 0 AND ";
                        elseif($value == '')
                            $where .= " = '' AND ";
                        elseif($value[0] == '%')
                            $where .= " LIKE '{$value}%' AND ";
                        elseif($value[0] == '@')
                        {
                            $value = substr($value, 1);
                            $where .= " LIKE '{$value}%' AND ";
                        }
                        elseif($value[0] == '>')
                        {
                            if($value[1] == '=')
                            {
                                $value = substr($value, 2);
                                $where .= " >= {$value} AND ";
                            }
                            else
                            {
                                $value = substr($value, 1);
                                $where .= " > {$value} AND ";
                            }
                        }
                        elseif($value[0] == '<')
                        {
                            if($value[1] == '=')
                            {
                                $value = substr($value, 2);
                                $where .= " <= {$value} AND ";
                            }
                            else
                            {
                                $value = substr($value, 1);
                                $where .= " < {$value} AND ";
                            }
                        }
                        else
                        {
                            if($value[0] == '#' && $value[1] != '!')
                            {
                                $value = substr($value, 1);
                                $where .= " = {$value} AND ";
                            }
                            elseif($value[0] == '#' && $value[1] == '!')
                            {
                                $value = substr($value, 2);
                                $where .= " != {$value} AND ";
                            }
                            elseif($value[0] == '!')
                            {
                                $value = substr($value, 1);
                                if(is_numeric($value) && strpos(strtoupper($value), 'E') === false)
                                    $where .= " != {$value} AND ";
                                else
                                    $where .= " != '{$value}' AND ";
                            }
                            else
                                $where .= " = '{$value}' AND ";
                        }
                    }
                    else
                        $where .= " = '' AND ";
                }
            }
            if($where != '')
                $where = substr($where, 0, -4);
        }
        else
        {
            $data = (string) $data;
            if($data != '')
            {
                if($prefix)
                    $row_key = "{$prefix}.{$row_key}";
                else
                    $row_key = "`{$row_key}`";
                $data = $this->sanitizeString($data);
                if($data[0] == '#')
                {
                    $data = substr($data, 1);
                    $where = "{$row_key} = {$data}";
                }
                elseif(is_numeric($data) && strpos(strtoupper($data), 'E') === false)
                    $where .= "{$row_key} = {$data}";
                else
                    $where = "{$row_key} = '{$data}'";
            }
        }
        return $where;
    }

    public function addWhereAmp(array &$where, string $request)
    {
        $key = '&';
        while (isset($where[$key])) {
            $key .= '&';
        }
        $where[$key] = $request;
    }

    private $query_table = false;
    private $query_prefix = false;
    private $query_fields = '*';
    private $query_where = false;
    private $query_join = array();
    private $query_group = false;
    private $query_group_prefix = false;
    private $query_order = false;
    private $query_order_prefix = true;
    private $query_limit = false;

    public function prefix($prefix)
    {
        if($this->query_prefix == false)
            $this->query_prefix = $prefix;
        else
            exit('Work with DB. Prefix of table name has to be set before function select!');
    }

    public function select($table, $fields = '*', $key = '', $row_key = 'id', $clear = true)
    {
        if($clear)
            $this->clear();
        $table = preg_replace("|[\s]+|", " ", $table);
        $table = explode(' ', $table);
        if(count($table) == 3 && ($table[1] == 'as' || $table[1] == 'AS' || $table[1] == 'As'))
            $this->query_prefix = $table[2];
        $this->query_table = $table[0];
        $this->query_fields = $fields;
        if($this->query_prefix == false)
            $this->query_prefix = $table[0];
        $this->query_where = $this->makeWhere($key, $row_key, $this->query_prefix);
        return $this;
    }

    public function join($table, $fields, $key = '', $row_key = 'id', $type = 'LEFT')
    {
        $table = preg_replace("|[\s]+|", " ", $table);
        $table = explode(' ', $table);
        $prefix = $table[0];
        if(count($table) == 3 && ($table[1] == 'as' || $table[1] == 'AS' || $table[1] == 'As'))
            $prefix = $table[2];
        $join = new stdClass();
        $join->table = $table[0];
        $join->prefix = $prefix;
        $join->fields = $fields;
        $join->where = $this->makeWhere($key, $row_key, $prefix);
        $join->type = $type;
        $this->query_join[] = $join;
        return $this;
    }

    public function order($order, $prefix = true)
    {
        $this->query_order_prefix = $prefix;
        $this->query_order = $order;
        return $this;
    }

    public function group($group, $prefix = false)
    {
        $this->query_group_prefix = $prefix;
        $this->query_group = $group;
        return $this;
    }

	public function limit(int $limit,int $offset = 0)
	{
		if ($limit > 0)
		{
			$this->query_limit = 'LIMIT ';
			if($offset > 0)
				$this->query_limit .= $offset.', ';

			$this->query_limit .= $limit;
		}
		return $this;
	}

    /**
     * Виконати запит до БД
     *
     * @param <string> $type - тип запиту:
     *                       auto   якщо один рядок об'єкт, якщо декілька - масив об'єктів
     *                       single тільки один об'єкт. Якщо більше ніж один - false
     *                       array  завжди масив об'єктів
     *                       arrayIndexed завжди масив об'єктів [id] => {}
     *                       arrayIndexed:key завжди масив об'єктів [key] => {}
     *                       count  повертає кількість знайдених рядків згідно запиту
     *                       query повертає запит
     * @param <bool> $clear очистити дані запиту (для нового)
     *
     * @return <object>
     */
    public function get($type = 'auto', $clear = true, $debug = false, $get = true)
    {
        if($this->query_table)
        {
            $data = NULL;
            if($type == 'count')
            {
                $data = 0;
                $where = $prefix = '';
                if($this->query_prefix && $this->query_table != $this->query_prefix) {
                    $where = "AS {$this->query_prefix} ";
                    $prefix = $this->query_prefix;
                }
                //join
                if(!empty($this->query_join))
                    foreach ($this->query_join as $join) {
                        $where .= "{$join->type} JOIN `{$join->table}` ";
                        if($join->prefix != $join->table)
                            $where .= "AS {$join->prefix} ";
                        $where .= "ON {$join->where} ";
                    }
                if($this->query_where != '')
                    $where .= 'WHERE '.$this->query_where;

                //group
                if($this->query_group)
                {
                    if($this->query_prefix || $this->query_group_prefix)
                    {
                        if($this->query_group_prefix == false)
                            $this->query_group_prefix = $this->query_prefix;
                        $where .= "GROUP BY {$this->query_group_prefix}.{$this->query_group} ";
                    }
                    else
                        $where .= "GROUP BY {$this->query_group} ";
                }

                $fields = '';
                if(!is_array($this->query_fields)) {
                    $this->query_fields = explode(',', $this->query_fields);
                }
                $use_fields = false;
                foreach ($this->query_fields as $field) {
                    $field = trim($field);
                    if($field != '') {
                        if($field[0] == '#') {
                            $fields .= substr($field, 1).', ';
                            $use_fields = true;
                        } else if(strpos($field, '.') === false)
                            $fields .= $prefix.'.'.$field.', ';
                        else
                            $fields .= $field.', ';
                    }
                }
                $fields = $use_fields ? substr($fields, 0, -2) : '*';
                $query = "SELECT count({$fields}) as count FROM `{$this->query_table}` {$where}";

                if($debug)
                    echo($query);

                if($get)
                {
                    $row = $this->getQuery($query);
                    if(is_object($row))
                        $data = $row->count;
                }
            }
            else
            {
                $query = "SELECT ";
                // fields
                if(!empty($this->query_join))
                {
                    if(!is_array($this->query_fields))
                        $this->query_fields = explode(',', $this->query_fields);
                    $prefix = $this->query_table;
                    if($this->query_prefix && $this->query_table != $this->query_prefix)
                        $prefix = $this->query_prefix;
                    foreach ($this->query_fields as $field) {
                        if($field != '')
                        {
                            $field = trim($field);
                            if($field[0] == '#')
                                $query .= substr($field, 1).', ';
                            else if(strpos($field, '.') === false)
                                $query .= $prefix.'.'.$field.', ';
                            else
                                $query .= $field.', ';
                        }
                    }
                    foreach ($this->query_join as $join) {
                        if(!is_array($join->fields))
                            $join->fields = explode(',', $join->fields);
                        foreach ($join->fields as $field) {
                            if($field != '')
                            {
                                $field = trim($field);
                                $query .= $join->prefix.'.'.$field.', ';
                            }
                        }
                    }
                    $query = substr($query, 0, -2);
                }
                else {
                    if(is_array($this->query_fields)) {
                        $query .= implode(',', $this->query_fields);
                    }
                    else {
                        $query .= $this->query_fields;
                    }
                }

                //from
                $query .= " FROM `{$this->query_table}` ";
                if($this->query_prefix && $this->query_table != $this->query_prefix)
                    $query .= "AS {$this->query_prefix} ";

                //join
                if(!empty($this->query_join))
                    foreach ($this->query_join as $join) {
                        $query .= "{$join->type} JOIN `{$join->table}` ";
                        if($join->prefix != $join->table)
                            $query .= "AS {$join->prefix} ";
                        $query .= "ON {$join->where} ";
                    }

                //where
                if($this->query_where)
                    $query .= "WHERE {$this->query_where} ";

                //group
                if($this->query_group)
                {
                    if($this->query_prefix || $this->query_group_prefix)
                    {
                        if($this->query_group_prefix == false)
                            $this->query_group_prefix = $this->query_prefix;
                        $query .= "GROUP BY {$this->query_group_prefix}.{$this->query_group} ";
                    }
                    else
                        $query .= "GROUP BY {$this->query_group} ";
                }

                //order
                if($this->query_order)
                {
                    if($this->query_order_prefix)
                    {
                        if($this->query_order_prefix === true)
                            $this->query_order_prefix = $this->query_prefix;
                        $query .= "ORDER BY {$this->query_order_prefix}.{$this->query_order} ";
                    }
                    else
                        $query .= "ORDER BY {$this->query_order} ";
                }

                //limit
                if($this->query_limit)
                    $query .= $this->query_limit;

                if($debug)
                    echo($query);

                if($type == 'query')
                    return $query;

                if($get)
                    $data = $this->getQuery($query, $type);
            }
            if($clear)
                $this->clear();

            return $data;
        }
        return false;
    }

    public function clear()
    {
        $this->query_table = false;
        $this->query_prefix = false;
        $this->query_fields = '*';
        $this->query_where = false;
        $this->query_join = array();
        $this->query_group = false;
        $this->query_order = false;
        $this->query_limit = false;
    }

    // $do - `name` (register do command) from `wl_user_register_do`
    public function register(string $do, string $additionally = '', int $user_id = 0): bool
    {
        if($register = $this->getAllDataById('wl_user_register_do', $do, 'name')) {
            $data = ['do' => $register->id, 'additionally' => $additionally, 'user_id' => $user_id, 'date' => time()];
            if($user_id == 0)
                $data['user_id'] = $_SESSION['user']->id;
            if($this->insertRow('wl_user_register', $data))
                return true;
        }
        return false;
    }

	public function getAliasImageSizes (int $alias_id = 0) {
		$alias_link = 'wl_aliases';
		if ($alias_id == 0 && isset($_SESSION['alias']->id)) {
			$alias_id = $_SESSION['alias']->id;
			$alias_link = $_SESSION['alias']->alias;
		}
		if (isset($this->imageReSizes[$alias_id]))
			return $this->imageReSizes[$alias_id];
		if ($alias_id != $_SESSION['alias']->id) {
			if ($a = $this->getAllDataById('wl_aliases', $alias_id))
				$alias_link = $a->alias;
			else
				return false;
		}
		$reSizes = $this->cache_get('imageReSizes', $alias_link);
		if ($reSizes !== NULL) {
			$this->imageReSizes[$alias_id] = $reSizes;
			return $reSizes;
		}
		$reSizes = array();
		if ($sizes = $this->getAllDataByFieldInArray('wl_images_sizes', array('alias' => array(0, $alias_id), 'active' => 1, 'alias DESC')))
			foreach ($sizes as $size) {
				$key = $size->prefix;
				if (!$size->prefix)
					$key = 0;
				$reSizes[$key] = $size;
			}
		$this->imageReSizes[$alias_id] = $reSizes;
		$this->cache_add('imageReSizes', $reSizes, $alias_link);
		return $reSizes;
	}

    public function sitemap_add($content = NULL, $link = '', $code = 0, $priority = 5, $changefreq = 'daily', $alias = 0)
    {
        $page = new stdClass();
        $page->uniq_link = $_SESSION['language'] ? $_SESSION['language'] .'/'. $link : $link;
        $page->alias = ($alias > 0 || $content === NULL) ? $alias : $_SESSION['alias']->id;
        $page->content = ($content === NULL) ? 0 : $content;
        $page->code = ($code > 0) ? $code : $_SESSION['alias']->code;

        $sitemap = ['link_sha1' => sha1($link)];
        if($row = $this->getAllDataById('wl_sitemap', $sitemap)) {
            $page->id = $row->id;
            foreach (['alias', 'content', 'code'] as $key) {
                if($row->$key != $page->$key) {
                    $sitemap['alias'] = $page->alias;
                    $sitemap['content'] = $page->content;
                    $sitemap['code'] = $page->code;

                    $this->updateRow('wl_sitemap', $sitemap, $row->id);
                    break;
                }
            }
            return $page;
        }
        
        $sitemap['link'] = $link;
        $sitemap['alias'] = $page->alias;
        $sitemap['content'] = $page->content;
        $sitemap['code'] = $page->code;
        $sitemap['data'] = NULL;
        $sitemap['time'] = $_SESSION['option']->sitemap_lastedit = time();
        $sitemap['changefreq'] = (in_array($changefreq, array('always','hourly','daily','weekly','monthly','yearly','never'))) ? $changefreq : 'daily';
        if($priority < 1) $priority *= 10;
        if($_SESSION['amp'] && $priority > 0)
            $priority *= -1;
        $sitemap['priority'] = $priority;
	    if ( $items = $this->getAllDataByFieldInArray( 'wl_sitemap', [ 'link_sha1' => $sitemap[ 'link_sha1' ], 'code' => '404' ] ) ) {
		    foreach ( $items as $item ) {
			    $this->deleteRow( 'wl_sitemap', $item->id );
		    }
	    }
        $page->id = $this->insertRow('wl_sitemap', $sitemap);
        return $page;
    }

    public function sitemap_redirect($to = '')
    {
        $sitemap = array();
        $sitemap['link_sha1'] = sha1($_SESSION['alias']->link);
        $sitemap['link'] = $_SESSION['alias']->link;
        $sitemap['alias'] = $sitemap['content'] = 0;
        $sitemap['code'] = 301;
        $sitemap['data'] = $to;
        $sitemap['time'] = time();
        $sitemap['changefreq'] = 'daily';
        $sitemap['priority'] = -5;
        return $this->insertRow('wl_sitemap', $sitemap);
    }

    public function sitemap_update($content = NULL, $key = 'link', $value = '', $alias = 0)
    {
        $sitemap = $where = array();
        $where['alias'] = ($alias == 0) ? $_SESSION['alias']->id : $alias;
        $where['content'] = ($content === NULL) ? 0 : $content;

        $rows = $this->getAllDataByFieldInArray('wl_sitemap', $where);
        if(empty($rows)) {
            // TODO: sitemap_update rows not found
            return;
        }
        if(count($rows) > 1) {
            foreach ($rows as $i => $row) {
                if($i) {
                    $this->deleteRow('wl_sitemap', $row->id);
                }
            }
        }

        if(is_array($key))
        {
            if(is_numeric($value) && $value > 0)
                $where['alias'] = $value;
            foreach ($key as $k => $v) {
                if($k == 'changefreq')
                    $sitemap['changefreq'] = (in_array($v, array('always','hourly','daily','weekly','monthly','yearly','never'))) ? $v : 'daily';
                elseif($k == 'priority')
                {
                    $sitemap['priority'] = (is_numeric($v) && $v >= 0) ? $v : 5;
                    if($sitemap['priority'] < 1)
                        $sitemap['priority'] *= 10;
                }
                elseif($k == 'redirect' || $k == 301)
                {
                    $sitemap['alias'] = $sitemap['content'] = 0;
                    $sitemap['code'] = 301;
                    $sitemap['data'] = $v;
                    $_SESSION['alias']->redirect = $v;
                }
                else
                    $sitemap[$k] = $v;
            }
        }
        else
        {
            if($key == 'changefreq')
                $sitemap['changefreq'] = (in_array($value, array('always','hourly','daily','weekly','monthly','yearly','never'))) ? $value : 'daily';
            elseif($key == 'priority')
            {
                $sitemap['priority'] = (is_numeric($value) && $value >= 0) ? $value : 5;
                if($sitemap['priority'] < 1)
                    $sitemap['priority'] *= 10;
            }
            elseif($key == 301)
            {
                $sitemap['alias'] = $sitemap['content'] = 0;
                $sitemap['code'] = 301;
                $sitemap['data'] = $value;
                $_SESSION['alias']->redirect = $value;
            }
            elseif ($key == 'link')
            {
                $this->deleteRow('wl_sitemap', ['link_sha1' => sha1($value), 'alias' => '!'.$where['alias'], 'content' => '!'.$where['content']]);
                $sitemap['link'] = $value;
                $sitemap['link_sha1'] = sha1($value);
            }
            else
                $sitemap[$key] = $value;
        }
        if(!empty($sitemap))
        {
            $sitemap['time'] = $_SESSION['option']->sitemap_lastedit = time();
            $this->updateRow('wl_sitemap', $sitemap, $where);
        }
    }

    public function sitemap_index($content = 0, $value = 1, $alias = 0)
    {
        if($alias == 0) $alias = $_SESSION['alias']->id;
        if($value == 0)
            $this->executeQuery("UPDATE `wl_sitemap` SET `priority` = `priority` * -1 WHERE `alias` = {$alias} AND `content` = {$content} AND `priority` > 0");
        else
            $this->executeQuery("UPDATE `wl_sitemap` SET `priority` = `priority` * -1 WHERE `alias` = {$alias} AND `content` = {$content} AND `priority` < 0");
    }

    public function sitemap_remove($content = 0, $alias = 0)
    {
        if($alias == 0) $alias = $_SESSION['alias']->id;
        $this->deleteRow('wl_sitemap', ['alias' => $alias, 'content' => $content]);
        return true;
    }


    public function redis_exists($key)
    {
        if(is_object($this->redis))
        {
            $key = str_replace('/', DIRSEP, $key);
            return $this->redis->exists($key);
        }
        return false;
    }

    public function redis_set($key, $data)
    {
        if(is_object($this->redis))
        {
            $key = str_replace('/', DIRSEP, $key);
            $this->redis->set($key, $data);
            return true;
        }
        return false;
    }

    public function redis_get($key)
    {
        if(is_object($this->redis))
        {
            $key = str_replace('/', DIRSEP, $key);
            if($this->redis->exists($key) > 0)
                return $this->redis->get($key);
        }
        return NULL;
    }

    public function redis_del($key)
    {
        if(is_object($this->redis))
        {
            $key = str_replace('/', DIRSEP, $key);
            $this->redis->del($key);
            return true;
        }
        return false;
    }

    public function redis_delByKey($key = '')
    {
        if(is_object($this->redis))
        {
            $key = str_replace('/', DIRSEP, $key);

            if($allKeys = $this->redis->keys($key.'*'))
            {
	            $this->redis->del($allKeys);
                return true;
            }
        }
        return false;
    }
    public function redis_delByMaskKey($key = '')
    {
        if(is_object($this->redis))
        {
            $key = str_replace('/', DIRSEP, $key);

            if($allKeys = $this->redis->keys($key))
            {
	            $this->redis->del($allKeys);
                return true;
            }
        }
        return false;
    }

    public function redis_ping()
    {
        if(is_object($this->redis))
            return $this->redis->ping();
        return false;
    }

    public function redis_do($command = false, $value = NULL)
    {
        if(is_object($this->redis) && $command)
            return $this->redis->$command($value);
        return false;
    }

    public function cache_exists($key, $alias = false, $json = true) {
        if(!$alias)
            $alias = $_SESSION['alias']->alias;
        if($_SESSION['language'] && $_SESSION['language'] != $_SESSION['all_languages'][0])
            $alias .= '_'.$_SESSION['language'];
        if($json)
        {
            if(is_object($this->redis)) {
                return $this->redis_exists($alias . DIRSEP . $key . '.json');
            }

            $path = CACHE_PATH . $alias . DIRSEP . $key . '.json';
            return file_exists($path);
        }
        else
        {
            if($this->html_cache_in_redis && is_object($this->redis))
                return $this->redis_exists($alias . DIRSEP . $key . '.html');

            $path = CACHE_PATH . $alias . DIRSEP . $key . '.html';
            return file_exists($path);
        }
        return false;
    }

    public function cache_add($key, $data, $alias = false, $json = true)
    {
        if(!$alias)
            $alias = $_SESSION['alias']->alias;
        if($_SESSION['language'] && $_SESSION['language'] != $_SESSION['all_languages'][0])
            $alias .= '_'.$_SESSION['language'];

        if(($json || $this->html_cache_in_redis) && is_object($this->redis))
        {
            $redis_key = $alias . DIRSEP . $key . '.json';
            if($json)
                $data = serialize($data);
            else
                $redis_key = $alias . DIRSEP . $key . '.html';
            $this->redis_set($redis_key, $data);
            return true;
        }

        $key = str_replace('/', DIRSEP, $key);
        $path = $alias . DIRSEP . $key . '.json';
        $dirs = explode(DIRSEP, $path);
        array_pop($dirs);
        $dirPath = CACHE_PATH;
        $dirPath = explode(DIRSEP, $dirPath);
        array_pop($dirPath);
        $dirPath = implode(DIRSEP, $dirPath);
        if(!is_dir($dirPath))
            mkdir($dirPath, 0755);
        foreach ($dirs as $dir) {
            $dirPath .= DIRSEP . $dir;
            if(!is_dir($dirPath))
                mkdir($dirPath, 0755);
        }
        if($json)
        {
            $path = CACHE_PATH . $alias . DIRSEP . $key . '.json';
            file_put_contents($path, serialize($data));
        }
        else
        {
            $path = CACHE_PATH . $alias . DIRSEP . $key . '.html';
            file_put_contents($path, $data);
        }
    }

    public function cache_get($key, $alias = false, $json = true)
    {
        if(!$alias)
            $alias = $_SESSION['alias']->alias;
        if($_SESSION['language'] && $_SESSION['language'] != $_SESSION['all_languages'][0])
            $alias .= '_'.$_SESSION['language'];
        if($json)
        {
            if(is_object($this->redis))
            {
                $data = $this->redis_get($alias . DIRSEP . $key . '.json');
                if($data === NULL)
                    return NULL;
                return unserialize($data);
            }

            $path = CACHE_PATH . $alias . DIRSEP . $key . '.json';
            if(file_exists($path))
                return unserialize(file_get_contents($path));
        }
        else
        {
            if($this->html_cache_in_redis && is_object($this->redis))
                return $this->redis_get($alias . DIRSEP . $key . '.html');

            $path = CACHE_PATH . $alias . DIRSEP . $key . '.html';
            if(file_exists($path))
                return file_get_contents($path);
        }
        return NULL;
    }

    public function cache_delete($key, $alias = false, $json = true)
    {
        if($alias === false)
            $alias = $_SESSION['alias']->alias;
        if($_SESSION['language'])
        {
            foreach ($_SESSION['all_languages'] as $language) {
                $alias_lang = $alias;
                if($language != $_SESSION['all_languages'][0])
                    $alias_lang = $alias .'_'.$language;

                if(($json || $this->html_cache_in_redis) && is_object($this->redis))
                {
                    if($json)
                        $this->redis_del($alias_lang . DIRSEP . $key . '.json');
                    else
                        $this->redis_del($alias_lang . DIRSEP . $key . '.html');
                }
                else
                {
                    $path = CACHE_PATH . $alias_lang . DIRSEP . $key . '.json';
                    if(!$json)
                        $path = CACHE_PATH . $alias_lang . DIRSEP . $key . '.html';
                    if(file_exists($path))
                        unlink($path);
                }
            }
            return true;
        }
        else
        {
            if(($json || $this->html_cache_in_redis) && is_object($this->redis))
            {
                if($json)
                    $this->redis_del($alias . DIRSEP . $key . '.json');
                else
                    $this->redis_del($alias . DIRSEP . $key . '.html');
            }
            else
            {
                $path = CACHE_PATH . $alias . DIRSEP . $key . '.json';
                if(!$json)
                    $path = CACHE_PATH . $alias_lang . DIRSEP . $key . '.html';
                if(file_exists($path))
                    return unlink($path);
            }
        }
        
        return false;
    }

    public function cache_delete_all($key = false, $alias = false)
    {
        if(!$alias)
            $alias = $_SESSION['alias']->alias;
        if($_SESSION['language'])
        {
            foreach ($_SESSION['all_languages'] as $language) {
                $alias_lang = $alias;
                if($language != $_SESSION['all_languages'][0])
                    $alias_lang = $alias .'_'.$language;
                $path = $alias_lang;
                if($key)
                    $path .= DIRSEP . $key;

                if(is_object($this->redis))
                    $this->redis_delByKey($path);

                $path = CACHE_PATH . $alias_lang;
                if($key)
                    $path .= DIRSEP . $key;

                if(is_dir($path))
                {
                    $data = new data();
                    $data->removeDirectory($path);
                }
            }
            return true;
        }
        else
        {
            $path = CACHE_PATH . $alias;
            if($key)
                $path .= DIRSEP . $key;

            if(is_object($this->redis))
                $this->redis_delByKey($path);

            if(is_dir($path))
            {
                $data = new data();
                $data->removeDirectory($path);
                return true;
            }
        }
        return false;
    }

    public function html_cache_clear($content = NULL, $alias = 0)
    {
        if($content === NULL)
            return false;
        
        if($_SESSION['cache'])
        {
            $alias_link = $_SESSION['alias']->alias;
            if($alias != $_SESSION['alias']->id)
            {
                if($a = $this->getAllDataById('wl_aliases', $alias))
                    $alias_link = $a->alias;
                else
                    return false;
            }

            $this->cache_delete($this->getHTMLCacheKey($content, $alias_link), 'html', false);
        }

        $_SESSION['option']->sitemap_lastedit = time();
        $this->updateRow('wl_options', array('value' => $_SESSION['option']->sitemap_lastedit), array('service' => 0, 'alias' => 0, 'name' => 'sitemap_lastedit'));
        return true;
    }

    public function getHTMLCacheKey($content = 0, $alias_link = false)
    {
        if(!$alias_link)
            $alias_link = $_SESSION['alias']->alias;
        $depth = 2;
        if($content < 0)
            $depth = 1;
        return $alias_link.DIRSEP.$this->getCacheContentKey($alias_link.'_', $content, $depth);
    }

    public function getCacheContentKey($pre = '', $content = 0, $depth = 1)
    {
        if($depth == 0 || $content == 0 || !is_numeric($content))
            return $pre.$content;
        if($content < 0)
        {
            $content *= -1;
            $pre .= '-';
        }
        $p_100 = ceil($content / 100) * 100;
        if($depth == 1)
            return $p_100.DIRSEP.$pre.$content;
        if($depth == 2)
        {
            $p_1000 = ceil($content / 1000) * 1000;
            return $p_1000.DIRSEP.$p_100.DIRSEP.$pre.$content;
        }
    }

    public function showTime($return = false)
    {
        $mem_end = memory_get_usage();
        $time_end = microtime(true);

        if ($this->showDBdump || $this->saveDBlog)
        {
            $time = $time_end - $this->time_start;
            $mem = $mem_end - $this->mem_start;
            $mem = round($mem/1024, 5);
            if($mem > 1024)
            {
                $mem = round($mem/1024, 5);
                $mem = (string) $mem . ' Мб';
            }
            else
                $mem = (string) $mem . ' Кб';
        }

        $timeGlobe = $time_end - $GLOBALS['time_start'];
        $memGlobe = $mem_end - $GLOBALS['mem_start'];
        $memGlobe = round($memGlobe/1024, 5);
        if($memGlobe > 1024)
        {
            $memGlobe = round($memGlobe/1024, 5);
            $memGlobe = (string) $memGlobe . ' Мб';
        }
        else
            $memGlobe = (string) $memGlobe . ' Кб';

        if($return)
        {
            $text = '';
            if(isset($this->result->num_rows))
                $text = "Результатів: ".$this->result->num_rows;
            if ($this->showDBdump || $this->saveDBlog)
                $text .= ' Час виконання: '.round($time, 5).' сек. Використано памяті: '.$mem.'. Від старту: ';
            $text .= 'Час виконання: '.round($timeGlobe, 5).' сек. Використано памяті: '.$memGlobe;
            return $text;
        }
        else
        {
            if($this->showDBdump && isset($this->result->num_rows))
                echo "<br> Результатів: ".$this->result->num_rows;
            else
                echo '<br>';
            if ($this->showDBdump || $this->saveDBlog)
                echo ' Час виконання: '.round($time, 5).' сек. Використано памяті: '.$mem.'. Від старту: Час виконання: '.round($timeGlobe, 5).' сек. Використано памяті: '.$memGlobe.' <hr>';
            else
                echo ' Від старту: Час виконання: '.round($timeGlobe, 5).' сек. Використано памяті: '.$memGlobe.'. Запитів до БД: '.$this->count_db_queries.' <hr>';
        }
    }

	/**
	 * Get the value of a field of the list type from the database
	 *
	 * @param string $table     table name
	 * @param string $fieldName field name
	 *
	 * @return false|string[]|void
	 *
	 * @author Oleh Holovkin
	 */
	public function getEnumList ( string $table, string $fieldName )
	{
		if ( empty( $table ) || empty( $fieldName ) ) {
			return;
		}
		$bdField = $this->getQuery( "SHOW COLUMNS FROM {$table} WHERE field = '{$fieldName}'" );
		if ( $bdField ) {
			$bdField->Type = str_replace( '\'', '', str_replace( ')', '', str_replace( 'enum(', '', $bdField->Type ) ) );
			$variable = explode( ',', $bdField->Type );

			return $variable;
		}

		return false;
	}

	/**
	 * Get a list of all fields from the database
	 *
	 * @param string $table table name
	 *
	 * @return array|false|void
	 *
	 * @author Oleh Holovkin
	 */
	public function getTableFields ( string $table )
	{
		if ( empty( $table ) ) {
			return;
		}

		$bdFields = $this->getQuery( "SHOW COLUMNS FROM {$table}" );

		if ( $bdFields ) {
			foreach ( $bdFields as $field ) {
				$field->cType = strtolower( $field->Type );
				$field->cMax = null;

				if ( preg_match( "/^(.*)\((.*)\)/i", $field->Type, $type ) ) {
					$field->cType = strtolower( $type[ 1 ] );
					if ( $field->cType == 'enum' ) {
						$field->variable = explode( ',', $type[ 2 ] );
						$field->variable = array_diff( $field->variable, [ '' ] );
					} elseif ( $type[ 2 ] ) {
						$field->cMax = intval( $type[ 2 ] );
					}
				}
			}
		}

		return $bdFields;
	}

	public function getDBName ()
	{
		// Query to get the name of the current database
		$dbName = $this->getQuery( 'SELECT DATABASE() as dbName;' );

		// Return the name of the current database
		return $dbName;

	}
	/**
	 * Check if a table exists in the current database.
	 *
	 * @param string $table The name of the table to check.
	 * @return bool True if the table exists, false otherwise.
	 * @throws \Exception If the database name is empty.
	 */
	public function checkTable($table)
	{
		// Get the current database name
		$_db = $this->getDBName();

		// Check if the database name is empty
		if (empty($_db->dbName)) {
			throw new \Exception('DB is empty!');
		}

		// Query to check the existence of the table
		$_table = $this->getQuery(
			"SELECT COUNT(*) as count FROM information_schema.tables 
             WHERE table_schema = '{$_db->dbName}' AND table_name = '{$table}';"
		);

		// Return true if the table exists, false otherwise
		return !empty($_table->count);
	}
}

?>