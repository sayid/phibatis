<?php


namespace Sayid\Phibatis;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class DBTool
{
    public static string $rootDir;

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

    /**
     * app()->configure("mybatis");
    $mybatisConfig = config("mybatis");
     * @param array $mybatisConfig
     */
    public static function doMybatis(array $mybatisConfig)
    {
        $tables = $mybatisConfig['tables'];
        if (!is_dir($mybatisConfig['output'])) {
            if (!mkdir($mybatisConfig['output'], 0777, true)) {
                throw new \Exception("mkdir ". $mybatisConfig['output']. " failed");
            }
        }
        $DB = $mybatisConfig['driver'];

        foreach ($tables as $tableinfo) {
            $table = $tableinfo['table'];
            echo "正在生成".$table.".......\r\n";
            $sql = "desc ".env('DB_DATABASE').".".$table;
            $rows= $DB::select($sql, []);
            $num = count($rows);
            if ($rows) {
                $entityFields = [];
                $entityGetterSetter = [];
                $where = [];
                foreach ($rows as $row) {
                    $field = $row->Field;
                    $type =  $row->Type;
                    $typeStr = "string";
                    if (strpos($type, "int") !== false) {
                        $typeStr = "int";
                    } else if (strpos($type, "varchar") !== false) {
                        $typeStr = "string";
                    }  else if (strpos($type, "double") !== false) {
                        $typeStr = "double";
                    }  else if (strpos($type, "float") !== false) {
                        $typeStr = "float";
                    }
                    $entityFields[] = self::getEntityField($field, $typeStr);
                    $entityGetterSetter[] = self::getEntityGetterSetter($field, $typeStr);
                    $where[] = self::getWhere($field, $typeStr);
                }
                $exampleDriver = [];
                foreach ($mybatisConfig['db-components'] as $dbcomponent) {
                    $exampleDriver[] = "use " . $dbcomponent;
                }
                if (empty($tableinfo['EntityType']) || $tableinfo['EntityType'] != 'trait') {
                    $tableinfo['EntityType'] = 'class';
                }
                $entityTpl = file_get_contents(self::$rootDir . "/vendor/sayid/phibatis/src/Mybatis/EntityTemplate");
                $entityTpl = str_replace("#{EntityMameSpace}", $mybatisConfig['namespace'],  $entityTpl);
                $entityTpl = str_replace("#{EntityName}", $tableinfo['EntityName'],  $entityTpl);
                $entityTpl = str_replace("#{EntityType}", $tableinfo['EntityType'],  $entityTpl);
                $entityTpl = str_replace("#{Fileds}", join("\r\n", $entityFields),  $entityTpl);
                $entityTpl = str_replace("#{GetterAndSetter}", join("\r\n", $entityGetterSetter),  $entityTpl);
                file_put_contents($mybatisConfig['output']."/".$tableinfo['EntityName'].".php", $entityTpl);

                $exampleTpl = file_get_contents(self::$rootDir . "/vendor/sayid/phibatis/src/Mybatis/ExampleTemplate");
                $exampleTpl = str_replace("#{EntityMameSpace}", $mybatisConfig['namespace'],  $exampleTpl);
                $exampleTpl = str_replace("#{EntityName}", $tableinfo['EntityName'],  $exampleTpl);
                $exampleTpl = str_replace("#{PriKey}", $tableinfo['PriKey'],  $exampleTpl);
                $exampleTpl = str_replace("#{TableName}", $tableinfo['table'],  $exampleTpl);
                $exampleTpl = str_replace("#{Where}", join("\r\n", $where),  $exampleTpl);
                $exampleTpl = str_replace("#{ExampleDriver}", join("\r\n", $exampleDriver),  $exampleTpl);

                file_put_contents($mybatisConfig['output']."/".$tableinfo['EntityName']."Example.php", $exampleTpl);
                echo "正在生成".$table."生成完毕.......\r\n";

            }
        }
    }

    static function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    public static function getEntityGetterSetter(string $field, string $typeStr)
    {
        $func = ucfirst(self::convertUnderline($field));
        $tpl = file_get_contents(self::$rootDir . "/vendor/sayid/phibatis/src/Mybatis/EntityGetterSetterTpl");
        $tpl = str_replace("#{FieldFunc}", $func,  $tpl);
        $tpl = str_replace("#{TypeStr}", $typeStr,  $tpl);
        $tpl = str_replace("#{Field}", $field,  $tpl);
        return $tpl;
    }

    public static function getEntityField(string $field, string $typeStr)
    {
        $func = ucfirst(self::convertUnderline($field));
        $tpl = file_get_contents(self::$rootDir . "/vendor/sayid/phibatis/src/Mybatis/EntityFieldTpl");
        $tpl = str_replace("#{FieldFunc}", $func,  $tpl);
        $tpl = str_replace("#{TypeStr}", $typeStr,  $tpl);
        $tpl = str_replace("#{Field}", $field,  $tpl);
        return $tpl;
    }

    public static function getWhere(string $field, string $typeStr) : string
    {
        $func = ucfirst(self::convertUnderline($field));
        $whereAndTpl = file_get_contents(self::$rootDir . "/vendor/sayid/phibatis/src/Mybatis/WhereAndTpl");
        $whereAndTpl = str_replace("#{FieldFunc}", $func,  $whereAndTpl);
        $whereAndTpl = str_replace("#{TypeStr}", $typeStr,  $whereAndTpl);
        $whereAndTpl = str_replace("#{Field}", $field,  $whereAndTpl);

        $whereOrTpl = file_get_contents(self::$rootDir . "/vendor/sayid/phibatis/src/Mybatis/WhereOrTpl");
        $whereOrTpl = str_replace("#{FieldFunc}", $func,  $whereOrTpl);
        $whereOrTpl = str_replace("#{TypeStr}", $typeStr,  $whereOrTpl);
        $whereOrTpl = str_replace("#{Field}", $field,  $whereOrTpl);
        return $whereAndTpl.$whereOrTpl;
    }
}

