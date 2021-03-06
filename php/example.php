<?php
include_once(__DIR__.DIRECTORY_SEPARATOR."Validator.php");

$data = [
	'email' => 'binsarjr121@gmail.com',
	'nik'	=> '1234567890123456',
	'password' => "coded",
	'c_password' => "coded",
];
$rules = [
	'email' => 'required|email',
	'nik'	=> 'digits:16',
	'c_password' => 'same:password'
];

$validator = Validator::make($data, $rules);
var_dump($validator->errors());
