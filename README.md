# E-Commerce-Miniprogram-Server
电子商务小程序——饮料点单服务器后端傻瓜式搭建

建议搭配[E-Commerce-Miniprogram](https://github.com/Siruirui/E-Commerce-Miniprogram)使用。

推荐使用PHPStudy搭建Apache+PHP+MySQL环境，将thinkphp文件夹直接放到PHPStudy安装目录下的WWW文件夹内，设置控制文件为controller.hitaccess，并在thinkphp\Application\Home\Conf\config.php中配置自己的小程序AppID和AppSecret，配置完即可使用。

部分数据库结构参考：

account | products | cart | order
----- | ----- | ----- | -----
wx_openid | product_id | wx_openid | id
unionid | type | product_id | wx_openid
nickname | title | count | product_id
balance | desc | add_time | count
session_key | tag | selected | price
user_session | price | / | purchase_time
/ | sold | / | order_id
/ | storage | / | statu
/ | picurl | / | deleted
