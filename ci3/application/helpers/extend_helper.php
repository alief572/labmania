<?php
function akses_server_side(){
    if (function_exists('mysqli_report')) {
        @mysqli_report(MYSQLI_REPORT_OFF);
    }
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'db_labmania';
    $port = 3306;

    if (function_exists('get_instance')) {
        $CI =& get_instance();
        if (isset($CI->db)) {
            $host = $CI->db->hostname ?: $host;
            $user = $CI->db->username ?: $user;
            $pass = $CI->db->password !== null ? $CI->db->password : $pass;
            $db   = $CI->db->database ?: $db;
            $port = (int)($CI->db->port ?: $port);
        }
    }
    $host = getenv('DB_HOST') ?: $host;
    $user = getenv('DB_USER') ?: $user;
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : $pass;
    $db   = getenv('DB_NAME') ?: $db;
    $port = (int)(getenv('DB_PORT') ?: $port);

	$Arr_Balik = array(
		'hostname' => $host,
		'hostuser' => $user,
		'hostpass' => $pass,
		'hostdb'   => $db,
		'port'     => $port
	);
	return $Arr_Balik;
}
