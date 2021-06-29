<?php

return [
    'driver' => Hyperf\DbConnection\Db::class,
    "namespace" => "App\Entities\Base",  //生成类的命名空间
    "output" => BASE_PATH . "app/Entitiy/Base", //生成类输出到什么地方
    "tables" => [
        ["table" => "user", "PriKey" => "user_id", "EntityName" => "User"],//table=表名 PriKey=主键  EntityName=实体类名称
        ['table' => 'memorial_member', "PriKey" => "id", "EntityName" => "Memorial"],
        ['table' => 'tribute', "PriKey" => "id", "EntityName" => "Tribute"],
    ],
    'db-components' => [
        'Builder' => Hyperf\Database\Query\Builder::class,
        'DB' => Hyperf\DbConnection\Db::class,
        'Collection' => Hyperf\DbConnection\Connection::class
    ]
];