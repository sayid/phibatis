第一步：安装

 `"sayid/table2model" : "dev-master"`
 
第二步：
 `config/mybatis.php` 中配置
    
    ```
    return [
        "namespace" => "App\Entities\Base",  //生成类的命名空间
        "output" => app()->basePath("app/Entities/Base"), //生成类输出到什么地方
        "tables" => [
            ["table" => "user", "PriKey" => "user_id", "EntityName" => "User"],//table=表名 PriKey=主键  EntityName=实体类名称
            ['table'=>'memorial_member', "PriKey" => "id", "EntityName" => "Memorial"],
            ['table'=>'tribute', "PriKey" => "id", "EntityName" => "Tribute"],
        ]
    ];
    ```
    
第三步：
执行`vendor/bin/mybatis` 自动生成entity类和example类


第四步：
编写业务逻辑

```sql
$userExample = new UserExample();
$data = $userExample->andUserIdGt(0)->andAvatarNotEQ(0)->getList();
echo $userExample->getBuilder()->toSql();
var_dump($data);
```
