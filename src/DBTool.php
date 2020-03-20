<?php


namespace Sayid\Table2Model;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class DBTool
{
    /**
     * 返回查询构造器
     * @param $table
     * @return \Illuminate\Database\Query\Builder
     */
    public static function getBuilder($table, string $entityClass) : \Illuminate\Database\Query\Builder
    {
        $mysqlConnection = DB::connection();
        $dbBuilder = $mysqlConnection->table($table);
        Event::listen ( StatementPrepared::class, function ($event) use ($entityClass) {
            //监听事件 将查询结果设置为数组
            $event->statement->setFetchMode(\PDO::FETCH_CLASS, $entityClass);
        });
        if (app()->bound('events')) {
            //绑定事件
            $mysqlConnection->setEventDispatcher(app()['events']);
        }
        return $dbBuilder;
    }

    public static function doMybatis()
    {

        app()->configure("mybatis");
        $mybatisConfig = config("mybatis");
        $tables = $mybatisConfig['tables'];
        foreach ($tables as $tableinfo) {
            mkdir($mybatisConfig['output']);
            $table = $tableinfo['table'];
            echo "正在生成".$table.".......\r\n";
            $sql = "desc ".env('DB_DATABASE').".".$table;
            $rows=DB::select($sql, []);
            $num = count($rows);
            if ($rows) {
                $entityFields = [];
                $entityGetterSetter = [];
                foreach ($rows as $row) {
                    $field = $row->Field;
                    $type =  $row->Type;
                    $typeStr = "";
                    if (strpos($type, "int") !== false) {
                        $typeStr = "int";
                    } else if (strpos($type, "varchar") !== false) {
                        $typeStr = "string";
                    }
                    $entityFields[] = "\tpublic $typeStr \$$field;";
                    $entityGetterSetter[] = self::getEntityGetter($field, $typeStr);
                    $entityGetterSetter[] = self::getEntitySetter($field, $typeStr);
                }
                $entityTpl = file_get_contents($mybatisConfig['tpl']."/EntityTemplate");
                $entityTpl = str_replace("#{EntityMameSpace}", $mybatisConfig['namespace'],  $entityTpl);
                $entityTpl = str_replace("#{EntityName}", $tableinfo['EntityName'],  $entityTpl);
                $entityTpl = str_replace("#{Fileds}", join("\r\n", $entityFields),  $entityTpl);
                $entityTpl = str_replace("#{GetterAndSetter}", join("\r\n", $entityGetterSetter),  $entityTpl);
                file_put_contents($mybatisConfig['output']."/".$tableinfo['EntityName'].".php", $entityTpl);

                $exampleTpl = file_get_contents($mybatisConfig['tpl']."/ExampleTemplate");
                $exampleTpl = str_replace("#{EntityMameSpace}", $mybatisConfig['namespace'],  $exampleTpl);
                $exampleTpl = str_replace("#{EntityName}", $tableinfo['EntityName'],  $exampleTpl);
                $exampleTpl = str_replace("#{PriKey}", $tableinfo['PriKey'],  $exampleTpl);
                $exampleTpl = str_replace("#{TableName}", $tableinfo['table'],  $exampleTpl);

                file_put_contents($mybatisConfig['output']."/".$tableinfo['EntityName']."Example.php", $exampleTpl);
                echo "正在生成".$table."生成完毕.......\r\n";

            }
        }
    }

    public static function getEntityGetter(string $field, string $typeStr)
    {
        return "\tpublic function get".ucfirst($field)."() : ".$typeStr."\r\n\t{\r\n\treturn\t\$this->".$field.";\r\n\t}";
    }

    public static function getEntitySetter(string $field, string $typeStr)
    {
        return "\tpublic function set".ucfirst($field)."($typeStr \$$field)\r\n\t{\r\n\t\$this->$field\t=\t\$$field;\r\n\t}";
    }
}

