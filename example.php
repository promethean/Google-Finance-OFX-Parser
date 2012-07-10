#!/usr/bin/env php
<?
require_once "ofx.php";

$data = file_get_contents('data.ofx');

$ofx = new Google_Finance_OFX_parser($data);

var_dump($ofx->parse());
