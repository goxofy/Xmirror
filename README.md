# Xmirror

一个轻量级的、自托管的 PHP 应用程序，用于镜像和展示特定 X 用户的推文。它具有现代化的开发者风格暗色主题、强大的媒体处理能力和简单的身份验证功能。

## 功能特性

*   **现代极客主题**：灵感来自 VS Code 和 Dracula 主题的简洁暗色界面，专为开发者设计。
*   **丰富的媒体支持**：
    *   **图片**：支持带有模糊渐变动画的懒加载、多图网格布局，以及全屏查看的灯箱效果。
    *   **视频**：自动检测视频内容，显示缩略图预览和播放按钮（点击即可打开高质量 MP4）。
    *   **代理**：内置图片代理功能，有效绕过防盗链保护和 Referrer 检查。
*   **智能内容处理**：
    *   **链接展开**：实时将 `t.co` 短链接解析并展开为原始 URL。
    *   **纯净文本**：自动从推文正文中移除多余的媒体链接，保持阅读体验整洁。
    *   **自动更新**：集成 RapidAPI (Twitter API 45) 自动获取并同步最新推文。
*   **安全保护**：提供简单的密码保护功能，基于 Cookie 的身份验证（支持 30 天免登录）。
*   **轻量级**：完全使用原生 PHP、SQLite 和原生 JavaScript 构建。无需繁重的框架或复杂的构建步骤。

## 环境要求

*   PHP 7.4 或更高版本
*   已启用 SQLite3 扩展
*   已启用 cURL 扩展
*   Web 服务器 (Apache, Nginx, 或 PHP 内置服务器)
*   [Twitter API 45](https://rapidapi.com/alexanderxbx/api/twitter-api45) 的 RapidAPI Key

## 安装步骤

1.  **克隆仓库**
    ```bash
    git clone https://github.com/goxofy/Xmirror.git
    cd Xmirror
    ```

2.  **配置应用**
    复制示例配置文件：
    ```bash
    cp src/config.php.example src/config.php
    ```
    编辑 `src/config.php` 并填入你的信息：
    *   `RAPID_API_KEY`: 你的 RapidAPI Key。
    *   `TWITTER_USERNAME`: 你想要镜像的 X 用户名 (例如 `elonmusk`)。
    *   `ACCESS_PASSWORD`: 访问站点所需的密码。

3.  **设置权限**
    确保 Web 服务器用户对 `db` 目录有写入权限，因为 SQLite 数据库和锁文件将在此处创建。
    ```bash
    chmod 775 db
    ```

4.  **运行**
    将你的 Web 服务器根目录指向 `public` 目录。
    
    使用 PHP 内置服务器进行本地测试：
    ```bash
    cd public
    php -S localhost:8000
    ```
    在浏览器中访问 `http://localhost:8000`。

## 导入历史推文

如果你有 X 官方导出的存档文件 (`tweets.js`)，可以使用内置的导入工具将其导入数据库：

1.  确保你已经登录了 Xmirror（访问首页并输入密码）。
2.  访问 `http://your-site/importer_ui.php`。
3.  选择你的 `tweets.js` 文件（通常在存档的 `data` 文件夹中）。
4.  点击 "Start Import"。
5.  工具会自动将数据分块上传并存入数据库。

## 目录结构

*   `public/`: Web 根目录。包含 `index.php` (前端界面) 和代理脚本。
*   `src/`: 后端逻辑和配置文件。
*   `db/`: SQLite 数据库存储目录。

## 许可证

MIT
