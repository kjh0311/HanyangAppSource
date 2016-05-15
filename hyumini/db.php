<?php

	/* 
		Author: ������ 
		Description:
		db.php�� PDO�� ����Ͽ� Databaseó���� �������� CRUD �⺻��ɵ��� �Ϲ�ȭ�Ͽ� ����ϱ� ���ϰ� ������ ���̺귯���Դϴ�. �ظ��� �κп����� prepare�� ����Ͽ� �⺻���� Injection�� ��� ������, �׷����� �ұ��ϰ� ���ȿ� �ټ� ����� �� ������ �⺻���� �Է°������� �̸� �Ͻð� ����Ͻñ� �ٶ��ϴ�.
	*/

	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	
	//assert_option�� �ڵ鷯�ڵ�� php.net�� �������� �����Խ��ϴ�.
	assert_options(ASSERT_ACTIVE, 1);
	assert_options(ASSERT_WARNING, 0);
	assert_options(ASSERT_QUIET_EVAL, 1);
	// Create a handler function
	function my_assert_handler($file, $line, $code, $desc = null)
	{
		echo "Assertion failed at $file:$line: $code";
		if ($desc) {
			echo ": $desc";
		}
		echo "\n";
	}

	// Set up the callback
	assert_options(ASSERT_CALLBACK, 'my_assert_handler');



	//DB ������ ���� attribute��
	$dbkind = "mysql";
	$dbhost = "localhost";
	$dbname = "testDB";
	$id = "root";
	$pwd = "root";
	$conf = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
	$pdo = new PDO($dbkind.":host=".$dbhost.";dbname=".$dbname, $id, $pwd, $conf);

	/* 
	 * Author : ������ 
	 * @Params
	 * string table : table�̸�
	 * [array columns : �ʵ� �̸���]
	 * array params : insert�� ���ڵ��� �Ķ���͵�
	 *
	 * @Return
	 * OnSuccess: 0
	 * OnFailure: -1
	 *
	 * @Description
	 * �⺻���� insert ������ �����մϴ�.
	 * �÷������� �����Ѵٸ� Insert into tableName(�÷���) values(�Ķ���͵�) ������ �����մϴ�.
	 * �������� �ʰ� �Ķ���͵鸸 �����Ѵٸ� Insert into tableName values(�Ķ���͵�) ������ �����մϴ�.
	 * ���� ������ ������ �����޽����� ����մϴ�.
	 */
	function insert(){

		//Assertions - �����ε��� ���� assertion��... ������ �������ϳ׿�..
		assert(func_num_args()>1, "Number of parameters must be more than 1.");
		assert(gettype(func_get_arg(0))=="string", "The type of first parameter must be string.");
		assert(gettype(func_get_arg(1))=="array", "The type of second parameter must be array.");
		if(func_num_args()==3){
			assert(gettype(func_get_arg(2))=="array", "The type of third parameter must be array.");
		}
		assert(func_num_args()<4, "Number of parameters must be less than 4.");

		//Implementation
		//PHP���� �Ķ���� ������ �����ε� ����..

		global $pdo;

		$num = func_num_args();
		$args = func_get_args();

		$table = $args[0];
		$params = $args[$num-1];

		//prepare statement build
		$prepare = "INSERT INTO ".$table;
		if($num==3){
			$columns = $args[1];
			$prepare.=" (".implode(",",$columns).")";
		}
		$prepare.=" VALUES(";
		$count = count($params);
		$arr = Array();
		for($i=0;$i<$count;$i++){
			$arr[$i]="?";
		}
		$prepare.=implode(",",$arr);
		$prepare.=")";
	
		$stmt = $pdo->prepare($prepare);
		$stmt->execute($params);

		//������ �����޽���
		$err = $stmt->errorInfo();
		if(isset($err[2])){
			print_r($err);
			return -1;
		}
		return 0;
	}


	/* 
	 * Author : ������ 
	 * @Params
	 * string table : table�̸�
	 * string column : ���� �ʵ� �̸�
	 * string/array clauses : where, order by ���� ������ ��
	 *
	 * @Return
	 * OnSuccess: result value
	 * OnEmptyResult: null
	 * OnFailure: -1
	 *
	 * @Description
	 * �� �Լ���
	 * SELECT column FROM table clauses LIMIT 1
	 * ������ �����ϰ�,
	 * ������ ����� �ϳ����� �����մϴ�. (���ڵ� ������ �ƴ� ����� �ϳ��Դϴ�.)
	 *
	 * ���� ���, SELECT name from test where id=1; 
	 * �̷����� ������ ��û�� ���ڵ� ��ü�� �ƴ� "������" �ϳ����� �����մϴ�.
	 * ���ǻ� ���� �Լ��̱� ������ ���� ���ڵ带 ������ �ʿ䰡 ���� ������ selectAll �Լ��� ȣ�����ּ���.
	 *
	 * clauses�� ����,
	 * key=>value array�� ���� ���,
	 * SELECT column FROM table key0 value0 key1 value1 ... LIMIT 1
	 * ������ �����մϴ�.
	 *
	 * ���� ������ ������ �����޽����� ����մϴ�.
	 */
	function selectOne($table, $column, $clauses=""){

		//Assertions - �Է°� ����
		assert(gettype($table)=="string", "The type of first parameter must be string.");
		assert(isset($column) || gettype($column)=="string", "The type of second parameter must be string.");
		assert(gettype($clauses)=="array" || gettype($clauses)=="string", "The type of third parameter must be string or array.");

		global $pdo;

		$query = "SELECT ";
		$query.=$column;
		$query.=(" FROM ".$table);

		$query.=clauseBuild($clauses);

		$query.=" LIMIT 1";
		//print($query);
		$stmt = rawQuery($query);
		$result = $stmt->fetchAll();
		if(count($result)==0){
			return null;
		}
		return $result[0][0];
		
	}

	/* 
	 * Author : ������ 
	 * @Params
	 * string table : table�̸�
	 * string/array columns : �ʵ�� �̸�, default "*"
	 * string/array clauses : where, order by ���� ������ ��
	 *
	 * @Return
	 * OnSuccess: ��� ���ڵ���� �����迭
	 * OnEmptySet: null
	 * OnFailure: -1
	 *
	 * @Description
	 * ������ �˻��� ���ڵ� ���θ� �����մϴ�.
	 * SELECT columns FROM table clauses ������ �����մϴ�.
	 * 
	 * clauses�� ����,
	 * key=>value array�� ���� ���,
	 * SELECT columns FROM table key0 value0 key1 value1 ...
	 * ������ �����մϴ�.
	 * 
	 * ���� ������ ������ �����޽����� ����մϴ�.
	 */
	function selectAll($table, $columns="*", $clauses=""){
		//Assertions - �Է°� ����
		assert(gettype($table)=="string", "The type of first parameter must be string.");
		assert(gettype($columns)=="array" || gettype($columns)=="string", "The type of second parameter must be string or array.");
		assert(gettype($clauses)=="array" || gettype($clauses)=="string", "The type of third parameter must be string or array.");

		global $pdo;

		$query = "SELECT ";
		if(gettype($columns)=="array"){
			$columns=implode(",",$columns);
		}
		$query.=$columns;
		
		$query.=(" FROM ".$table);

		$query.=clauseBuild($clauses);

		$stmt = rawQuery($query);

		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($result)==0){
			return null;
		}
		return $result;
	}

	/* 
	 * Author : ������ 
	 * @Params
	 * string table : table�̸�
	 * string/array set : ������Ʈ �Ϸ��� �ʵ�=>�� �����迭
	 * string/array clauses : where, order by ���� ������ ��
	 * set��
	 * Array("num"=>1,"name"=>"ȫ�浿","age"=>30) Ȥ��,
	 * "num=1, name='ȫ�浿', age=30" 
	 * �̷��� �� ���ڿ��� �޸��� �����Ͽ� �ּŵ� �����ϴ�.
	 *
	 * @Return
	 * OnSuccess: update���� ���� ������� ���ڵ� ���� (>=0)
	 * OnFailure: -1
	 *
	 * @Description
	 * UPDATE table SET setkey0=setVal0, ... clauses ������ �����մϴ�.
	 * 
	 * clauses�� ����,
	 * key=>value array�� ���� ���,
	 * UPDATE ... key0 value0 key1 value1 ...
	 * ������ �����մϴ�.
	 * 
	 * ���� ������ ������ �����޽����� ����մϴ�.
	 */
	function update($table, $set, $clauses=""){
		assert(gettype($table)=="string", "The type of first parameter must be string.");
		assert(gettype($set)=="array" || gettype($set)=="string", "The type of second parameter must be string or string or array.");
		assert(gettype($clauses)=="array" || gettype($clauses)=="string", "The type of third parameter must be string or array.");

		global $pdo;

		//prepare statement build
		$prepare = "UPDATE ".$table." SET ";
		if(gettype($set)=="array"){
			$setter=Array();
			foreach($set as $column=>$value){
				array_push($setter, $column."=?");
			}
			$prepare .= implode(", ",$setter);
		}else{
			$prepare .= $set;
		}

		$prepare.=clauseBuild($clauses);
//		print("<br/>".$prepare."<br/>");

		$stmt = $pdo->prepare($prepare);
		$stmt->execute(array_values($set));

		//������ �����޽���
		$err = $stmt->errorInfo();
		if(isset($err[2])){
			print_r($err);
			return -1;
		}
		return $stmt->rowCount();

	}

	/* 
	 * Author : ������ 
	 * @Params
	 * string table : table�̸�
	 * string/array clauses : where, order by ���� ������ ��
	 *
	 * @Return
	 * OnSuccess: Delete���� ���� ������� ���ڵ� ���� (>=0)
	 * OnFailure: -1
	 *
	 * @Description
	 * DELETE FROM table clauses ������ �����մϴ�.
	 * �̹� delete��� �Լ����� php�� �����ϱ� ������ remove��� �Լ����� ����մϴ�.
	 * 
	 * clauses�� ����,
	 * key=>value array�� ���� ���,
	 * DELETE FROM table key0 value0 key1 value1 ...
	 * ������ �����մϴ�.
	 * 
	 * ���� ������ ������ �����޽����� ����մϴ�.
	 */
	function remove($table, $clauses){
		assert(gettype($table)=="string", "The type of first parameter must be string.");
		assert(gettype($clauses)=="array" || gettype($clauses)=="string", "The type of second parameter must be string or array.");

		global $pdo;

		//prepare statement build
		$prepare = "DELETE FROM ".$table." ";
		$prepare.=clauseBuild($clauses);
//		print("<br/>".$prepare."<br/>");
		$stmt = rawQuery($prepare);
		return $stmt->rowCount();
	}


	/* 
	 * Author : ������ 
	 * @Params
	 * string table : table�̸�
	 * string/array clauses : where, order by ���� ������ ��
	 *
	 * @Return
	 * OnSuccess: �˻��� ���ڵ� ����
	 * OnFailure: -1
	 *
	 * @Description
	 * SELECT count(*) FROM table clauses ������ �����մϴ�.
	 * �̹� count��� �Լ��� php�� �����ϱ� ������ counts��� �Լ����� ����մϴ�.
	 * 
	 * clauses�� ����,
	 * key=>value array�� ���� ���,
	 * SELECT count(*) FROM table key0 value0 key1 value1 ...
	 * ������ �����մϴ�.
	 * 
	 * ���� ������ ������ �����޽����� ����մϴ�.
	 */
	function counts($table, $clauses){
		assert(gettype($table)=="string", "The type of first parameter must be string.");
		assert(gettype($clauses)=="array" || gettype($clauses)=="string", "The type of second parameter must be string or array.");
		return selectOne($table,"count(*)",$clauses);
	}

	/* 
	 * Author : ������ 
	 * @Params
	 * string table : table�̸�
	 * string column : column�̸�
	 * string/array clauses : where, order by ���� ������ ��
	 *
	 * @Return
	 * OnSuccess: ���ǿ� �����ϴ� ���ڵ���� ��
	 * OnFailure: -1
	 *
	 * @Description
	 * SELECT sum(*) FROM table clauses ������ �����մϴ�.
	 *
	 * clauses�� ����,
	 * key=>value array�� ���� ���,
	 * SELECT sum(*) FROM table key0 value0 key1 value1 ...
	 * ������ �����մϴ�.
	 * 
	 * ���� ������ ������ �����޽����� ����մϴ�.
	 */
	function sum($table, $column, $clauses){
		assert(gettype($table)=="string", "The type of first parameter must be string.");
		assert(gettype($column)=="string", "The type of second parameter must be string.");
		assert(gettype($clauses)=="array" || gettype($clauses)=="string", "The type of third parameter must be string or array.");
		return selectOne($table,"sum(".$column.")",$clauses);
	}

	/* 
	 * Author : ������ 
	 * @Params
	 * string table : table�̸�
	 * string column : column�̸�
	 * string/array clauses : where, order by ���� ������ ��
	 *
	 * @Return
	 * OnSuccess: ���ǿ� �����ϴ� ���ڵ���� ��հ�
	 * OnFailure: -1
	 *
	 * @Description
	 * SELECT avg(*) FROM table clauses ������ �����մϴ�.
	 * 
	 * clauses�� ����,
	 * key=>value array�� ���� ���,
	 * SELECT avg(*) FROM table key0 value0 key1 value1 ...
	 * ������ �����մϴ�.
	 * 
	 * ���� ������ ������ �����޽����� ����մϴ�.
	 */
	function avg($table, $column, $clauses){
		assert(gettype($table)=="string", "The type of first parameter must be string.");
		assert(gettype($column)=="string", "The type of second parameter must be string.");
		assert(gettype($clauses)=="array" || gettype($clauses)=="string", "The type of third parameter must be string or array.");
		return selectOne($table,"avg(".$column.")",$clauses);
	}

	/* 
	 * @Params
	 * password: �н����� �ؽ��ϰ��� �ϴ� ���ڿ�
	 * @Return
	 * �н����� �ؽ� ��
	 * @Description
	 * �н����带 �޾Ƽ� �н����� �ؽð��� �����մϴ�.
	 * �н����� �ؽð� �ʿ��� ��� �������� PASSWORD()���� ���� �� �Լ��� ���ּ���.
	 */
	function pwd($password){
		return password_hash($password, PASSWORD_DEFAULT);
	}

	/*
	 * @Params
	 * param: ���� �ϰ��� �ϴ� ���ڿ�
	 * @Return
	 * 'param'
	 * @Description
	 * PDO�� quote�Լ��� �����մϴ�. ���� �Ķ���Ϳ� ' ' ������������ �� �Լ��� ���ּ���.
	 */
	function quote($param){
		global $pdo;
		return $pdo->quote($param);
	}

	/*
	 * Author: ������
	 * @Params
	 * param: ������ ���ڿ�
	 * @Return
	 * statement ������Ʈ.
	 * @Description
	 * �������� �����մϴ�. �������� ������Ʈ�� �����մϴ�.
	 */
	function rawQuery($query){
		global $pdo;
		$stmt = $pdo->prepare($query);
		$stmt->execute();
		//������ �����޽���
		$err = $stmt->errorInfo();
		if(isset($err[2])){
			print_r($err);
			return -1;
		}
		return $stmt;
	}

	/* 
	 * Author : ������ 
	 * @Params
	 * string/array clauses : where, order by ���� ������ ��
	 * string�� clause=>condition�� �����迭�� �޽��ϴ�.
	 * �Ķ���� ����1: Array("where"=>"id=1 and name LIKE ������","LIMIT"=>3,"ORDER BY"=>"desc")
	 * �Ķ���� ����2: "where id=1 and name like ������ limit 3 ..."
	 *
	 * @Return
	 * OnSuccess: Parsed clauses
	 *
	 * @Description
	 * ���������� �޾Ƽ� ���ڿ��� �����մϴ�.
	 * ���� db.php �������� ����ϱ� ���� �Լ��Դϴ�.
	 * 
	 */
	function clauseBuild($clauses){
		$result = "";
		if(gettype($clauses)=="array"){
			foreach($clauses as $clause => $condition){
				$result.=(" ".$clause." ".$condition);
			}
		}else{
			$result.=(" ".$clauses);
		}
		return $result;
	}


	//�׽�Ʈ�ڵ�
	//print("<br/>insert:<br/>");
	//insert("testTB",Array("name","age"),Array("Ahn2",221));
	//print("<br/>selectOne:<br/>");
	//print(selectOne("testTB","name","where age=3"));
	//print("<br/>selectAll:<br/>");
	//print_r(selectAll("testTB","name, num"));
	//print("<br/>update:<br/>");
	//print(update("testTB",Array("name"=>"AAAbbb","age"=>1),Array("where"=>"num=2")));
	//print("<br/>remove:<br/>");
	//print(remove("testTB","where age=221 limit 2"));
	//print("<br/>counts:<br/>");
	//print(counts("testTB","where age=3"));

?>