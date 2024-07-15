# minecraft-server-status
我的世界服务器状态查询

## Server_Ping

通过`UDP/TCP`协议获取MC的服务器状态，支持Vercel部署。  
自动识别java/基岩版服务器

请求方式：`GET`

### 请求参数 ###

| 参数 | 示例               | 描述                          |
| ---- | ------------------ | ----------------------------- |
| ip   | play.dfggmc.eu.org | 服务器IP地址(必选)            |
| port | 25565              | 服务器端口(可选，默认为25565) |
| raw  | true/false         | 输出原始信息(可选)            |

### 返回参数 ###

| 参数      | 示例                                | 描述                 |
| --------- | ----------------------------------- | -------------------- |
| queryTime | 1.2566s                             | 查询耗时             |
| code      | 200                                 | 接口响应状态码       |
| motd      | <div>§2花风服务器-dfgg Server</div> | 经过转译为HTML的MOTD |
| protocol  | 498                                 | 协议版本             |
| version   | 1.14.30                             | 服务器版本           |
| online    | 3                                   | 服务器在线人数       |
| max       | 10                                  | 服务器人数上限       |
| favicon   | data:image/png;base64               | 获取服务器图标       |

正常请求示例`?ip=play.dfggmc.eu.org`  
使用端口号示例`?ip=play.dfggmc.eu.org&port=25565`  
返回原始数据示例`?ip=play.dfggmc.eu.org&raw=true`

### 状态码 ###

| 状态码 | 描述                           |
| ------ | ------------------------------ |
| 200    | 正常                           |
| 204    | 服务器查询成功但是未获取到信息 |
| 500    | 服务器查询失败                 |

项目基于 [PHP-Minecraft-Query](https://github.com/xPaw/PHP-Minecraft-Query)

本项目由[dfgg Studio](http://mscpo.netlify.app/)维护制作