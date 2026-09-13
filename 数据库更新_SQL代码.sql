-- ============================================
-- 龙黑化创作工坊 - 数据库更新SQL代码
-- 功能：首次进入权限引导 + GitHub登录 + Discord登录 + Gitee登录
-- ============================================

-- 1. 在 users 表中添加 onboarding_done 字段
-- 用于标记用户是否已完成首次进入的权限引导
-- 0 = 未完成（需要显示引导）
-- 1 = 已完成（不再显示引导）
ALTER TABLE users ADD COLUMN onboarding_done TINYINT DEFAULT 0;

-- 2. 在 users 表中添加 GitHub 登录相关字段
-- github_id: GitHub 用户唯一ID（数字），用于绑定账号
-- github_access_token: GitHub access_token（可用于后续API调用）
-- github_username: GitHub 用户名
ALTER TABLE users ADD COLUMN github_id VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN github_access_token TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN github_username VARCHAR(100) DEFAULT NULL;

-- 3. 在 users 表中添加 Discord 登录相关字段
-- discord_id: Discord 用户唯一ID，用于绑定账号
-- discord_access_token: Discord access_token（可用于后续API调用）
-- discord_username: Discord 用户名
ALTER TABLE users ADD COLUMN discord_id VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN discord_access_token TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN discord_username VARCHAR(100) DEFAULT NULL;

-- 4. 在 users 表中添加 Gitee 登录相关字段
-- gitee_id: Gitee 用户唯一ID，用于绑定账号
-- gitee_access_token: Gitee access_token（可用于后续API调用）
-- gitee_username: Gitee 用户名
ALTER TABLE users ADD COLUMN gitee_id VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN gitee_access_token TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN gitee_username VARCHAR(100) DEFAULT NULL;

-- 5. 通用字段
-- avatar: 用户头像URL（GitHub/Discord/Gitee登录时自动同步）
-- last_login: 用户最后登录时间
ALTER TABLE users ADD COLUMN avatar VARCHAR(500) DEFAULT NULL;
ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL;

-- 6. （兼容旧版）如果之前已经添加过 TapTap 字段，可以保留或删除
-- 以下是 TapTap 字段（如果之前没添加过可以忽略）
-- ALTER TABLE users ADD COLUMN taptap_openid VARCHAR(100) DEFAULT NULL;
-- ALTER TABLE users ADD COLUMN taptap_access_token TEXT DEFAULT NULL;

-- 7. 验证字段是否添加成功
-- 执行以下SQL查看字段是否存在
-- DESCRIBE users;

-- 8. （可选）如果需要删除字段，执行以下SQL
-- ALTER TABLE users DROP COLUMN onboarding_done;
-- ALTER TABLE users DROP COLUMN github_id;
-- ALTER TABLE users DROP COLUMN github_access_token;
-- ALTER TABLE users DROP COLUMN github_username;
-- ALTER TABLE users DROP COLUMN discord_id;
-- ALTER TABLE users DROP COLUMN discord_access_token;
-- ALTER TABLE users DROP COLUMN discord_username;
-- ALTER TABLE users DROP COLUMN gitee_id;
-- ALTER TABLE users DROP COLUMN gitee_access_token;
-- ALTER TABLE users DROP COLUMN gitee_username;
-- ALTER TABLE users DROP COLUMN avatar;
-- ALTER TABLE users DROP COLUMN last_login;

-- ============================================
-- 说明：
-- - onboarding_done: 记录用户是否已经完成首次进入的权限引导（存储权限+位置权限）
-- - github_id: GitHub 用户唯一ID，用户通过GitHub登录时用于查找/创建账号
-- - github_access_token: GitHub access_token，保存后可用于后续调用GitHub API
-- - github_username: GitHub 用户名（login字段）
-- - discord_id: Discord 用户唯一ID，用户通过Discord登录时用于查找/创建账号
-- - discord_access_token: Discord access_token，保存后可用于后续调用Discord API
-- - discord_username: Discord 用户名
-- - gitee_id: Gitee 用户唯一ID，用户通过Gitee登录时用于查找/创建账号
-- - gitee_access_token: Gitee access_token，保存后可用于后续调用Gitee API
-- - gitee_username: Gitee 用户名（login字段）
-- - avatar: 用户头像URL，GitHub/Discord/Gitee登录时自动同步头像
-- - last_login: 用户最后登录时间
-- - 如果需要让某个用户重新显示引导，可以执行：
--   UPDATE users SET onboarding_done = 0 WHERE id = 用户ID;
-- - 代码已做兼容处理：如果github_id/discord_id/gitee_id字段不存在，会自动尝试用taptap_openid字段
-- ============================================
