第一步：安装
 "sayid/table2model" : "dev-master"
 
第二步：
config/mybatis.php 中配置

第三步：
执行vendor/bin/mybatis 自动生成entity类和example类

第四步：
编写业务逻辑
```sql
$userExample = new UserExample();
$data = $userExample->andUserIdGt(0)->andAvatarNotEQ(0)->getList();
echo $userExample->getBuilder()->toSql();
var_dump($data);
```
