<?php
/**
 * 测试脚本
 */
$a = array(
    'persons' => array(
        'phones' => array(
            '1232132312',
            '12321323121',
            '12321323122',
        ),
        'emails' => array(
            '32321@qq.com',
            '3213@qq.com',
            '432432@qq.com'
        )
    ),
    'test' => 1
);

var_dump(array_filter($a, 'is_array'));