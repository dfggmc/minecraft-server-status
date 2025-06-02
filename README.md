# minecraft-server-status  
我的世界服务器状态查询  

## Server_Ping  
通过 `UDP/TCP` 协议获取MC服务器状态，支持批量查询多服务器，自动识别Java/基岩版服务器。  


### 请求方式  
- **单服务器查询**：`GET`  
- **批量服务器查询**：`POST`  


### **单服务器查询（GET请求）**  
#### 请求参数  
| 参数   | 示例               | 描述                          |
| ------ | ------------------ | ----------------------------- |
| `ip`   | play.dfggmc.eu.org | 服务器IP地址（必选）          |
| `port` | 25565              | 服务器端口（可选，默认25565） |
| `raw`  | true/false         | 输出原始信息（可选）          |

#### 返回参数  
| 参数                  | 示例                                | 描述                 |
| --------------------- | ----------------------------------- | -------------------- |
| `queryTime`           | 1.2566s                             | 查询耗时             |
| `code`                | 200                                 | 接口响应状态码       |
| `data.motd`           | <div>§2花风服务器-dfgg Server</div> | 转译为HTML的MOTD     |
| `data.protocol`       | 498                                 | 协议版本             |
| `data.version`        | 1.14.30                             | 服务器版本           |
| `data.players.online` | 3                                   | 在线人数             |
| `data.players.max`    | 10                                  | 人数上限             |
| `data.favicon`        | data:image/png;base64               | 服务器图标（Base64） |

#### 请求示例  
- 单服务器查询：  
  ```  
  GET /?ip=play.dfggmc.eu.org  
  ```  
- 带端口查询：  
  ```  
  GET /?ip=play.dfggmc.eu.org&port=25565  
  ```  
- 返回原始数据：  
  ```  
  GET /?ip=play.dfggmc.eu.org&raw=true  
  ```  


### **批量服务器查询（POST请求）**  
#### 请求参数（JSON格式）  
| 参数      | 示例                                                               | 描述                                      |
| --------- | ------------------------------------------------------------------ | ----------------------------------------- |
| `servers` | `[{"ip":"mc1.example.com","port":25565},{"ip":"mc2.example.com"}]` | 服务器列表（必选，`port`可选，默认25565） |
| `raw`     | true/false                                                         | 输出原始信息（可选）                      |

#### 请求示例  
```json  
POST /  
Content-Type: application/json  

{  
  "servers": [  
    {"ip": "play.dfggmc.eu.org", "port": 25565},  
    {"ip": "mc.test.com"}  // 端口默认25565  
  ],  
  "raw": false  
}  
```  

#### 返回参数（批量结果）  
```json  
[  
  {  
    "server": {"ip": "play.dfggmc.eu.org", "port": 25565},  
    "queryTime": "0.321s",  
    "code": 200,  
    "data": {  
      "motd": "<div>§2花风服务器-dfgg Server</div>",  
      "version": "1.19.4",  
      "players": { "online": 5, "max": 20, "list": ["Player1", "Player2"] },  
      "favicon": "data:image/png;base64,..."  
    }  
  },  
  {  
    "server": {"ip": "mc.test.com", "port": 25565},  
    "queryTime": "0.456s",  
    "code": 204,  
    "data": { "motd": null, "players": { "online": null, "max": null } }  
  }  
]  
```  


### 状态码  
| 状态码 | 描述                               |
| ------ | ---------------------------------- |
| `200`  | 正常（单/批量查询成功）            |
| `204`  | 服务器查询成功但未获取到信息       |
| `400`  | 无效请求（如IP为空）               |
| `500`  | 服务器查询失败（如超时、协议错误） |


### 项目说明  
- 基于 [PHP-Minecraft-Query](https://github.com/xPaw/PHP-Minecraft-Query) 开发。  
- 支持跨域请求（`Access-Control-Allow-Origin: *`）。  
- 部署建议：推荐使用Vercel、Fly.io等支持PHP的无服务器平台。  

本项目由 [dfgg Studio](http://mscpo.netlify.app/) 维护。