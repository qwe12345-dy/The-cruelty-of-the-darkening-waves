/* ============================================================
 * i18n.js  多语言核心
 * 支持：简体中文(zh-CN) / 繁体中文(zh-TW) / English(en)
 * 用法：
 *   1) 静态文本：给元素加 data-i18n="key"，placeholder 用 data-i18n-placeholder
 *   2) 动态文本：t('key') 返回当前语言字符串
 *   3) 切换语言：setLanguage('zh-CN') 自动重渲染 + localStorage 持久化
 * ============================================================ */
(function (global) {
    'use strict';

    var LANG_KEY = 'app_lang';
    var currentLang = localStorage.getItem(LANG_KEY) || 'zh-CN';

    /* ---------- 词典 ---------- */
    var dict = {
        'zh-CN': {
            /* ===== 登录页 index.html ===== */
            login_title: '登录',
            or_login_with: '或使用以下方式登录',
            github_login: 'GitHub 登录',
            discord_login: 'Discord 登录',
            gitee_login: 'Gitee 登录',
            register_title: '注册',
            label_email: '📬 邮箱',
            label_password: '🔑 密码',
            label_username: '👤 用户名',
            label_code: '📧 验证码',
            ph_email: '输入邮箱...',
            ph_password: '输入密码...',
            ph_username: '输入用户名...',
            ph_code: '输入6位验证码...',
            ph_password_reg: '至少6位...',
            btn_login: '登录',
            btn_register: '注册',
            btn_get_code: '获取验证码',
            btn_sending: '⏳ 发送中...',
            code_retry: 's 后重试',
            agreement_tip: '注册或登录代表着你同意 ',
            agreement_link: '协议',
            no_account: '还没有账号？ ',
            register_now: '立即注册',
            has_account: '已有账号？ ',
            go_login: '去登录',
            msg_fill_all: '请填写完整',
            msg_fill_fields: '请填写所有字段',
            msg_email_first: '请先输入邮箱',
            msg_email_format: '邮箱格式错误',
            msg_code_sent: '✅ 验证码已发送',
            msg_login_success: '✅ 登录成功',
            msg_register_success: '✅ 注册成功，请登录',
            msg_username_len: '用户名必须为2-20个字符',
            msg_password_len: '密码至少6位',

            /* ===== 主界面 dashboard.html ===== */
            app_name: '龙黑化',
            app_sub: '社区',
            menu_edit_profile: '编辑资料',
            menu_mails: '邮件',
            menu_badge: '勋章设置',
            menu_logout: '退出登录',
            tab_hot: '首页',
            tab_new: '新品',
            search_placeholder: '搜索作品名称或作者...',
            btn_search: '搜索',
            loading: '加载中...',
            loading_hot: '加载热度榜...',
            loading_new: '加载新品...',
            no_games: '暂无作品',
            no_search: '没有找到相关作品',
            nav_home: '首页',
            nav_friends: '好友',
            nav_create: '创作',
            nav_profile: '我的',

            /* 个人主页 */
            no_cover: '暂无封面',
            profile_loading: '加载中...',
            credit_score: '信用分 ',
            bio_empty: '这个人很懒，什么都没写...',
            btn_edit_profile: '编辑资料',
            btn_change_cover: '更换封面',
            btn_mail: '邮件',
            stat_games: '作品',
            stat_likes: '获赞',
            stat_collects: '收藏',
            stat_following: '关注',
            stat_followers: '粉丝',
            badge_title: '我的勋章',
            my_games_title: '我的作品',
            long_press_tip: '（长按作品可删除）',

            /* 编辑资料弹窗 */
            edit_profile_title: '编辑资料',
            change_avatar: '更换头像（≤10MB）',
            ph_username_edit: '用户名（2-20个字符）',
            ph_bio: '个人介绍...',
            btn_save: '保存',
            btn_cancel: '取消',
            lang_setting: '语言设置',
            lang_zh_cn: '简体中文',
            lang_zh_tw: '繁体中文',
            lang_en: 'English',
            img_too_large: '图片不能超过10MB',
            save_success: '✅ 保存成功',
            save_failed: '❌ 保存失败',

            /* 封面弹窗 */
            cover_title: '更换封面',
            cover_preview_text: '选择图片后预览',
            cover_select: '选择封面图片（≤10MB）',
            btn_upload: '上传',

            /* 创作弹窗 */
            create_title: '发布作品',
            ph_game_title: '作品名称（2-20字）',
            upload_icon: '上传作品图标（≤5MB）',
            ph_game_code: '输入HTML游戏代码...',
            btn_publish: '发布',
            btn_enter_editor: '进入编辑器',
            editor_mode_title: '选择创作方式',
            editor_mode_direct: '直接粘贴代码',
            editor_mode_editor: '在线编辑器',

            /* 详情弹窗 */
            play_btn: '▶ 进入游戏',
            comment_title: '评论',
            ph_comment: '说点什么...',
            btn_send: '发送',
            btn_like: '赞',
            btn_collect: '收藏',
            btn_follow: '关注',
            btn_followed: '已关注',
            btn_yourself: '自己',
            play_count: '游玩次数：',
            no_comments: '暂无评论，快来抢沙发！',
            comment_delete: '删除',

            /* 好友 */
            friend_title: '好友',
            add_friend: '添加好友',
            more_login_methods: '更多登录方式',
            account_binding_title: '账号绑定管理',
            binding_hint: '绑定第三方账号后，可以使用该账号快速登录。每个第三方账号只能绑定到一个本站账号。',
            binding_footer_tip: '如果您的账号没有设置密码，取消唯一的登录方式绑定前请先设置密码',
            search_type: '选择搜索方式',
            friend_request: '请求',
            no_friends: '还没有好友',
            chat_placeholder: '输入消息（最多500字）',
            btn_send_msg: '发送',

            /* 邮件 */
            mail_title: '我的邮件',
            no_mails: '暂无邮件',
            btn_close: '关闭',

            /* 勋章 */
            badge_manager_title: '勋章管理',
            badge_obtained: '已获得勋章',
            badge_progress: '勋章进度',

            /* 通用 */
            confirm: '确认',
            cancel: '取消',
            delete_confirm: '确定要删除这个作品吗？',
            delete_success: '删除成功',
            logout_confirm: '确定要退出登录吗？',
            edit_game_title: '编辑作品',
            ph_game_desc: '作品描述（可选）',
            ph_game_code_edit: 'HTML游戏代码...',
            btn_save_update: '保存更新',
            no_desc: '暂无介绍',
            his_games: '他的作品',
            friend_request_title: '好友请求',
            msg_recall: '撤回',
            msg_copy: '复制',
            msg_report: '举报',
            report_title: '举报',
            report_label_title: '举报标题',
            ph_report_title: '请输入举报标题（最多50字）',
            report_label_content: '举报内容',
            ph_report_content: '请详细描述举报原因（最多500字）',
            report_label_contact: '联系方式（选填）',
            ph_report_contact: '方便我们联系你核实情况',
            btn_submit_report: '提交举报',
            play_title: '游戏',
            lang_hint: '选择后立即生效',

            /* 修改密码 */
            change_password_title: '🔑 修改密码',
            change_password_email_hint: '验证码将发送到您的注册邮箱：',
            change_password_code_ph: '请输入6位验证码',
            send_code: '发送验证码',
            new_password_ph: '请输入新密码（6-20位）',
            confirm_password_ph: '请再次输入新密码',
            confirm_change_password: '确认修改密码',
            editor_info_title: '2.0 可视化编辑器',
            editor_info_desc: '使用可视化编辑器创作游戏，支持文件管理、属性编辑',
            editor_info_tip: '需要先创建游戏仓库，再进入编辑器',
            no_messages: '暂无消息',
            play_count_text: '人玩过 · ',
            playing_count_text: '人在玩',
            comment_delete: '删除',
            no_comments: '暂无评论，快来抢沙发！',
            btn_followed: '已关注',
            btn_yourself: '自己',
            delete_confirm_game: '确定要删除这个作品吗？',
            logout_confirm_text: '确定要退出登录吗？',
            img_too_large_5mb: '图片不能超过5MB',
            publish_success: '✅ 发布成功',
            publish_failed: '❌ 发布失败',
            save_profile_success: '✅ 保存成功',
            save_profile_failed: '❌ 保存失败',
            title_required: '请输入作品名称',
            code_required: '请输入游戏代码',
            title_too_long: '作品名称不能超过20字',
            comment_required: '请输入评论内容',
            comment_too_long: '评论不能超过200字',
            report_title_required: '请输入举报标题',
            report_content_required: '请输入举报内容',
            report_submit_success: '✅ 举报已提交',
            report_submit_failed: '❌ 举报提交失败',
            chat_msg_required: '请输入消息内容',
            chat_msg_too_long: '消息不能超过500字',
            recall_success: '撤回成功',
            copy_success: '已复制',
            no_friends_text: '还没有好友',
            no_mails_text: '暂无邮件',
            loading_friends: '加载中...',
            accept_friend: '接受',
            reject_friend: '拒绝',
            already_friends: '已是好友',
            pending: '待审核',
            no_hot_games: '暂无热门作品',
            load_failed: '加载失败',
            no_new_games: '暂无新品，去创作吧！',
            searching: '搜索中...',
            search_failed: '搜索失败',
            profile_page_title: '个人主页',
            title_length_error: '名称必须为2-20个字符',
            icon_required: '请上传作品图标',
            submitting: '提交中',
            game_online: '作品已上线',
            under_review: '审核中，请稍后查看',
            creating_repo: '创建游戏仓库',
            repo_created: '仓库创建成功，正在进入编辑器',
            delete_comment_confirm: '确定要删除这条评论吗？',
            username_required: '请输入用户名',
            saving: '保存中',
            save_success_review: '保存成功！介绍正在审核（30秒）',
            btn_enter_editor: '进入编辑器',
            no_friends: '还没有好友',
            friend_accept: '接受',
            friend_reject: '拒绝',
            chat_no_messages: '暂无消息',
            mail_no_mails: '暂无邮件',
            report_submitting: '提交中...',
            logout_text: '退出登录',

            /* ===== 协议页 pls.html ===== */
            pls_title: '用户服务条款 与 隐私政策',
            pls_btn_close: '关闭并返回登录',
            pls_part1: '第一部分 用户服务条款',
            pls_part2: '第二部分 隐私政策',
            pls_effective: '生效日期：2026年09月06日'
        },

        'zh-TW': {
            /* ===== 登入頁 index.html ===== */
            login_title: '登入',
            or_login_with: '或使用以下方式登入',
            github_login: 'GitHub 登入',
            discord_login: 'Discord 登入',
            gitee_login: 'Gitee 登入',
            register_title: '註冊',
            label_email: '📬 電子郵件',
            label_password: '🔑 密碼',
            label_username: '👤 使用者名稱',
            label_code: '📧 驗證碼',
            ph_email: '輸入電子郵件...',
            ph_password: '輸入密碼...',
            ph_username: '輸入使用者名稱...',
            ph_code: '輸入6位驗證碼...',
            ph_password_reg: '至少6位...',
            btn_login: '登入',
            btn_register: '註冊',
            btn_get_code: '取得驗證碼',
            btn_sending: '⏳ 傳送中...',
            code_retry: 's 後重試',
            agreement_tip: '註冊或登入代表著你同意 ',
            agreement_link: '協議',
            no_account: '還沒有帳號？ ',
            register_now: '立即註冊',
            has_account: '已有帳號？ ',
            go_login: '去登入',
            msg_fill_all: '請填寫完整',
            msg_fill_fields: '請填寫所有欄位',
            msg_email_first: '請先輸入電子郵件',
            msg_email_format: '電子郵件格式錯誤',
            msg_code_sent: '✅ 驗證碼已傳送',
            msg_login_success: '✅ 登入成功',
            msg_register_success: '✅ 註冊成功，請登入',
            msg_username_len: '使用者名稱必須為2-20個字元',
            msg_password_len: '密碼至少6位',

            /* ===== 主介面 dashboard.html ===== */
            app_name: '龍黑化',
            app_sub: '社群',
            menu_edit_profile: '編輯資料',
            menu_mails: '郵件',
            menu_badge: '勳章設定',
            menu_logout: '登出登入',
            tab_hot: '首頁',
            tab_new: '新品',
            search_placeholder: '搜尋作品名稱或作者...',
            btn_search: '搜尋',
            loading: '載入中...',
            loading_hot: '載入熱度榜...',
            loading_new: '載入新品...',
            no_games: '暫無作品',
            no_search: '沒有找到相關作品',
            nav_home: '首頁',
            nav_friends: '好友',
            nav_create: '創作',
            nav_profile: '我的',

            /* 個人主頁 */
            no_cover: '暫無封面',
            profile_loading: '載入中...',
            credit_score: '信用分 ',
            bio_empty: '這個人很懶，什麼都沒寫...',
            btn_edit_profile: '編輯資料',
            btn_change_cover: '更換封面',
            btn_mail: '郵件',
            stat_games: '作品',
            stat_likes: '獲讚',
            stat_collects: '收藏',
            stat_following: '關注',
            stat_followers: '粉絲',
            badge_title: '我的勳章',
            my_games_title: '我的作品',
            long_press_tip: '（長按作品可刪除）',

            /* 編輯資料彈窗 */
            edit_profile_title: '編輯資料',
            change_avatar: '更換頭像（≤10MB）',
            ph_username_edit: '使用者名稱（2-20個字元）',
            ph_bio: '個人介紹...',
            btn_save: '儲存',
            btn_cancel: '取消',
            lang_setting: '語言設定',
            lang_zh_cn: '簡體中文',
            lang_zh_tw: '繁體中文',
            lang_en: 'English',
            img_too_large: '圖片不能超過10MB',
            save_success: '✅ 儲存成功',
            save_failed: '❌ 儲存失敗',

            /* 封面彈窗 */
            cover_title: '更換封面',
            cover_preview_text: '選擇圖片後預覽',
            cover_select: '選擇封面圖片（≤10MB）',
            btn_upload: '上傳',

            /* 創作彈窗 */
            create_title: '發布作品',
            ph_game_title: '作品名稱（2-20字）',
            upload_icon: '上傳作品圖示（≤5MB）',
            ph_game_code: '輸入HTML遊戲程式碼...',
            btn_publish: '發布',
            btn_enter_editor: '進入編輯器',
            editor_mode_title: '選擇創作方式',
            editor_mode_direct: '直接貼上代碼',
            editor_mode_editor: '線上編輯器',

            /* 詳情彈窗 */
            play_btn: '▶ 進入遊戲',
            comment_title: '評論',
            ph_comment: '說點什麼...',
            btn_send: '傳送',
            btn_like: '讚',
            btn_collect: '收藏',
            btn_follow: '關注',
            btn_followed: '已關注',
            btn_yourself: '自己',
            play_count: '遊玩次數：',
            no_comments: '暫無評論，快來搶沙發！',
            comment_delete: '刪除',

            /* 好友 */
            friend_title: '好友',
            add_friend: '添加好友',
            more_login_methods: '更多登入方式',
            account_binding_title: '帳號綁定管理',
            binding_hint: '綁定第三方帳號後，可以使用該帳號快速登入。每個第三方帳號只能綁定到一個本站帳號。',
            binding_footer_tip: '如果您的帳號沒有設置密碼，取消唯一的登入方式綁定前請先設置密碼',
            search_type: '選擇搜索方式',
            friend_request: '請求',
            no_friends: '還沒有好友',
            chat_placeholder: '輸入訊息（最多500字）',
            btn_send_msg: '傳送',

            /* 郵件 */
            mail_title: '我的郵件',
            no_mails: '暫無郵件',
            btn_close: '關閉',

            /* 勳章 */
            badge_manager_title: '勳章管理',
            badge_obtained: '已獲得勳章',
            badge_progress: '勳章進度',

            /* 通用 */
            confirm: '確認',
            cancel: '取消',
            delete_confirm: '確定要刪除這個作品嗎？',
            delete_success: '刪除成功',
            logout_confirm: '確定要登出嗎？',
            edit_game_title: '編輯作品',
            ph_game_desc: '作品描述（可選）',
            ph_game_code_edit: 'HTML遊戲程式碼...',
            btn_save_update: '儲存更新',
            no_desc: '暫無介紹',
            his_games: '他的作品',
            friend_request_title: '好友請求',
            msg_recall: '撤回',
            msg_copy: '複製',
            msg_report: '檢舉',
            report_title: '檢舉',
            report_label_title: '檢舉標題',
            ph_report_title: '請輸入檢舉標題（最多50字）',
            report_label_content: '檢舉內容',
            ph_report_content: '請詳細描述檢舉原因（最多500字）',
            report_label_contact: '聯絡方式（選填）',
            ph_report_contact: '方便我們聯絡你核實情況',
            btn_submit_report: '提交檢舉',
            play_title: '遊戲',
            lang_hint: '選擇後立即生效',

            /* 修改密碼 */
            change_password_title: '🔑 修改密碼',
            change_password_email_hint: '驗證碼將發送到您的註冊郵箱：',
            change_password_code_ph: '請輸入6位驗證碼',
            send_code: '發送驗證碼',
            new_password_ph: '請輸入新密碼（6-20位）',
            confirm_password_ph: '請再次輸入新密碼',
            confirm_change_password: '確認修改密碼',
            editor_info_title: '2.0 可視化編輯器',
            editor_info_desc: '使用可視化編輯器創作遊戲，支援檔案管理、屬性編輯',
            editor_info_tip: '需要先建立遊戲倉庫，再進入編輯器',
            no_messages: '暫無訊息',
            play_count_text: '人玩過 · ',
            playing_count_text: '人在玩',
            comment_delete: '刪除',
            no_comments: '暫無評論，快來搶沙發！',
            btn_followed: '已關注',
            btn_yourself: '自己',
            delete_confirm_game: '確定要刪除這個作品嗎？',
            logout_confirm_text: '確定要登出嗎？',
            img_too_large_5mb: '圖片不能超過5MB',
            publish_success: '✅ 發布成功',
            publish_failed: '❌ 發布失敗',
            save_profile_success: '✅ 儲存成功',
            save_profile_failed: '❌ 儲存失敗',
            title_required: '請輸入作品名稱',
            code_required: '請輸入遊戲程式碼',
            title_too_long: '作品名稱不能超過20字',
            comment_required: '請輸入評論內容',
            comment_too_long: '評論不能超過200字',
            report_title_required: '請輸入檢舉標題',
            report_content_required: '請輸入檢舉內容',
            report_submit_success: '✅ 檢舉已提交',
            report_submit_failed: '❌ 檢舉提交失敗',
            chat_msg_required: '請輸入訊息內容',
            chat_msg_too_long: '訊息不能超過500字',
            recall_success: '撤回成功',
            copy_success: '已複製',
            no_friends_text: '還沒有好友',
            no_mails_text: '暫無郵件',
            loading_friends: '載入中...',
            accept_friend: '接受',
            reject_friend: '拒絕',
            already_friends: '已是好友',
            pending: '待審核',
            no_hot_games: '暫無熱門作品',
            load_failed: '載入失敗',
            no_new_games: '暫無新品，去創作吧！',
            searching: '搜尋中...',
            search_failed: '搜尋失敗',
            profile_page_title: '個人主頁',
            title_length_error: '名稱必須為2-20個字元',
            icon_required: '請上傳作品圖示',
            submitting: '提交中',
            game_online: '作品已上線',
            under_review: '審核中，請稍後查看',
            creating_repo: '建立遊戲倉庫',
            repo_created: '倉庫建立成功，正在進入編輯器',
            delete_comment_confirm: '確定要刪除這條評論嗎？',
            username_required: '請輸入使用者名稱',
            saving: '儲存中',
            save_success_review: '儲存成功！介紹正在審核（30秒）',
            btn_enter_editor: '進入編輯器',
            no_friends: '還沒有好友',
            friend_accept: '接受',
            friend_reject: '拒絕',
            chat_no_messages: '暫無訊息',
            mail_no_mails: '暫無郵件',
            report_submitting: '提交中...',
            logout_text: '登出登入',

            /* ===== 協議頁 pls.html ===== */
            pls_title: '用戶服務條款 與 隱私政策',
            pls_btn_close: '關閉並返回登入',
            pls_part1: '第一部分 用戶服務條款',
            pls_part2: '第二部分 隱私政策',
            pls_effective: '生效日期：2026年09月06日'
        },

        'en': {
            /* ===== Login index.html ===== */
            login_title: 'Login',
            or_login_with: 'Or login with',
            github_login: 'GitHub Login',
            discord_login: 'Discord Login',
            gitee_login: 'Gitee Login',
            register_title: 'Register',
            label_email: '📬 Email',
            label_password: '🔑 Password',
            label_username: '👤 Username',
            label_code: '📧 Code',
            ph_email: 'Enter email...',
            ph_password: 'Enter password...',
            ph_username: 'Enter username...',
            ph_code: 'Enter 6-digit code...',
            ph_password_reg: 'At least 6 chars...',
            btn_login: 'Login',
            btn_register: 'Register',
            btn_get_code: 'Get Code',
            btn_sending: '⏳ Sending...',
            code_retry: 's to retry',
            agreement_tip: 'By registering or logging in, you agree to our ',
            agreement_link: 'Terms',
            no_account: "Don't have an account? ",
            register_now: 'Sign up',
            has_account: 'Already have an account? ',
            go_login: 'Sign in',
            msg_fill_all: 'Please fill in all fields',
            msg_fill_fields: 'Please fill in all fields',
            msg_email_first: 'Please enter email first',
            msg_email_format: 'Invalid email format',
            msg_code_sent: '✅ Code sent',
            msg_login_success: '✅ Login successful',
            msg_register_success: '✅ Registered, please login',
            msg_username_len: 'Username must be 2-20 characters',
            msg_password_len: 'Password must be at least 6 characters',

            /* ===== Dashboard ===== */
            app_name: 'Dragon',
            app_sub: 'Community',
            menu_edit_profile: 'Edit Profile',
            menu_mails: 'Messages',
            menu_badge: 'Badges',
            menu_logout: 'Log Out',
            tab_hot: 'Home',
            tab_new: 'New',
            search_placeholder: 'Search games or authors...',
            btn_search: 'Search',
            loading: 'Loading...',
            loading_hot: 'Loading trending...',
            loading_new: 'Loading new releases...',
            no_games: 'No games yet',
            no_search: 'No games found',
            nav_home: 'Home',
            nav_friends: 'Friends',
            nav_create: 'Create',
            nav_profile: 'Me',

            /* Profile page */
            no_cover: 'No cover',
            profile_loading: 'Loading...',
            credit_score: 'Credit: ',
            bio_empty: 'This user is too lazy to write anything...',
            btn_edit_profile: 'Edit Profile',
            btn_change_cover: 'Change Cover',
            btn_mail: 'Messages',
            stat_games: 'Games',
            stat_likes: 'Likes',
            stat_collects: 'Favs',
            stat_following: 'Following',
            stat_followers: 'Followers',
            badge_title: 'My Badges',
            my_games_title: 'My Games',
            long_press_tip: '(Long press to delete)',

            /* Edit profile modal */
            edit_profile_title: 'Edit Profile',
            change_avatar: 'Change Avatar (≤10MB)',
            ph_username_edit: 'Username (2-20 chars)',
            ph_bio: 'About me...',
            btn_save: 'Save',
            btn_cancel: 'Cancel',
            lang_setting: 'Language',
            lang_zh_cn: '简体中文',
            lang_zh_tw: '繁體中文',
            lang_en: 'English',
            img_too_large: 'Image must be ≤10MB',
            save_success: '✅ Saved',
            save_failed: '❌ Save failed',

            /* Cover modal */
            cover_title: 'Change Cover',
            cover_preview_text: 'Preview after selecting',
            cover_select: 'Select cover image (≤10MB)',
            btn_upload: 'Upload',

            /* Create modal */
            create_title: 'Publish Game',
            ph_game_title: 'Game title (2-20 chars)',
            upload_icon: 'Upload game icon (≤5MB)',
            ph_game_code: 'Paste HTML game code...',
            btn_publish: 'Publish',
            btn_enter_editor: 'Open Editor',
            editor_mode_title: 'Choose Creation Mode',
            editor_mode_direct: 'Paste Code',
            editor_mode_editor: 'Online Editor',

            /* Detail modal */
            play_btn: '▶ Play Game',
            comment_title: 'Comments',
            ph_comment: 'Say something...',
            btn_send: 'Send',
            btn_like: 'Like',
            btn_collect: 'Fav',
            btn_follow: 'Follow',
            btn_followed: 'Following',
            btn_yourself: 'You',
            play_count: 'Plays: ',
            no_comments: 'No comments yet. Be the first!',
            comment_delete: 'Delete',

            /* Friends */
            friend_title: 'Friends',
            add_friend: 'Add Friend',
            more_login_methods: 'More Login Methods',
            account_binding_title: 'Account Binding',
            binding_hint: 'After binding a third-party account, you can use it to log in quickly. Each third-party account can only be bound to one account on this site.',
            binding_footer_tip: 'If your account has no password set, please set a password before unbinding the only login method',
            search_type: 'Search Type',
            friend_request: 'Requests',
            no_friends: 'No friends yet',
            chat_placeholder: 'Type a message (max 500 chars)',
            btn_send_msg: 'Send',

            /* Mail */
            mail_title: 'My Messages',
            no_mails: 'No messages',
            btn_close: 'Close',

            /* Badge */
            badge_manager_title: 'Badge Manager',
            badge_obtained: 'Obtained Badges',
            badge_progress: 'Badge Progress',

            /* Common */
            confirm: 'OK',
            cancel: 'Cancel',
            delete_confirm: 'Delete this game?',
            delete_success: 'Deleted',
            logout_confirm: 'Log out?',
            edit_game_title: 'Edit Game',
            ph_game_desc: 'Game description (optional)',
            ph_game_code_edit: 'HTML game code...',
            btn_save_update: 'Save Changes',
            no_desc: 'No description',
            his_games: 'His Games',
            friend_request_title: 'Friend Requests',
            msg_recall: 'Recall',
            msg_copy: 'Copy',
            msg_report: 'Report',
            report_title: 'Report',
            report_label_title: 'Report Title',
            ph_report_title: 'Enter report title (max 50 chars)',
            report_label_content: 'Report Content',
            ph_report_content: 'Describe the reason in detail (max 500 chars)',
            report_label_contact: 'Contact (optional)',
            ph_report_contact: 'So we can reach you for verification',
            btn_submit_report: 'Submit Report',
            play_title: 'Game',
            lang_hint: 'Takes effect immediately',

            /* Change Password */
            change_password_title: '🔑 Change Password',
            change_password_email_hint: 'Verification code will be sent to your registered email:',
            change_password_code_ph: 'Enter 6-digit code',
            send_code: 'Send Code',
            new_password_ph: 'Enter new password (6-20 chars)',
            confirm_password_ph: 'Confirm new password',
            confirm_change_password: 'Confirm Change Password',
            editor_info_title: '2.0 Visual Editor',
            editor_info_desc: 'Create games with visual editor, file manager & property editing',
            editor_info_tip: 'Create game repo first, then enter editor',
            no_messages: 'No messages yet',
            play_count_text: ' plays · ',
            playing_count_text: ' playing',
            comment_delete: 'Delete',
            no_comments: 'No comments yet. Be the first!',
            btn_followed: 'Following',
            btn_yourself: 'You',
            delete_confirm_game: 'Delete this game?',
            logout_confirm_text: 'Log out?',
            img_too_large_5mb: 'Image must be ≤5MB',
            publish_success: '✅ Published',
            publish_failed: '❌ Publish failed',
            save_profile_success: '✅ Saved',
            save_profile_failed: '❌ Save failed',
            title_required: 'Please enter game title',
            code_required: 'Please enter game code',
            title_too_long: 'Title must be ≤20 chars',
            comment_required: 'Please enter comment',
            comment_too_long: 'Comment must be ≤200 chars',
            report_title_required: 'Please enter report title',
            report_content_required: 'Please enter report content',
            report_submit_success: '✅ Report submitted',
            report_submit_failed: '❌ Report failed',
            chat_msg_required: 'Please enter message',
            chat_msg_too_long: 'Message must be ≤500 chars',
            recall_success: 'Message recalled',
            copy_success: 'Copied',
            no_friends_text: 'No friends yet',
            no_mails_text: 'No messages',
            loading_friends: 'Loading...',
            accept_friend: 'Accept',
            reject_friend: 'Reject',
            already_friends: 'Already friends',
            pending: 'Pending',
            no_hot_games: 'No trending games',
            load_failed: 'Load failed',
            no_new_games: 'No new games, go create!',
            searching: 'Searching...',
            search_failed: 'Search failed',
            profile_page_title: 'My Profile',
            title_length_error: 'Title must be 2-20 chars',
            icon_required: 'Please upload game icon',
            submitting: 'Submitting',
            game_online: 'Game is online',
            under_review: 'Under review, check later',
            creating_repo: 'Creating game repo',
            repo_created: 'Repo created, entering editor',
            delete_comment_confirm: 'Delete this comment?',
            username_required: 'Please enter username',
            saving: 'Saving',
            save_success_review: 'Saved! Bio under review (30s)',
            btn_enter_editor: 'Open Editor',
            no_friends: 'No friends yet',
            friend_accept: 'Accept',
            friend_reject: 'Reject',
            chat_no_messages: 'No messages',
            mail_no_mails: 'No messages',
            report_submitting: 'Submitting...',
            logout_text: 'Log Out',

            /* ===== Terms pls.html ===== */
            pls_title: 'Terms of Service & Privacy Policy',
            pls_btn_close: 'Close & Back to Login',
            pls_part1: 'Part 1: Terms of Service',
            pls_part2: 'Part 2: Privacy Policy',
            pls_effective: 'Effective: September 6, 2026'
        }
    };

    /* ---------- 核心函数 ---------- */
    function t(key) {
        var langDict = dict[currentLang] || dict['zh-CN'];
        return langDict[key] !== undefined ? langDict[key] : (dict['zh-CN'][key] || key);
    }

    function getLang() {
        return currentLang;
    }

    function setLanguage(lang) {
        if (!dict[lang]) lang = 'zh-CN';
        currentLang = lang;
        localStorage.setItem(LANG_KEY, lang);
        applyLanguage();
        /* 触发自定义事件，方便页面内其他脚本监听 */
        try {
            var ev = new CustomEvent('languagechange', { detail: { lang: lang } });
            document.dispatchEvent(ev);
        } catch (e) {}
    }

    function applyLanguage() {
        /* 1) 处理 data-i18n 文本 */
        var nodes = document.querySelectorAll('[data-i18n]');
        for (var i = 0; i < nodes.length; i++) {
            var key = nodes[i].getAttribute('data-i18n');
            if (key) nodes[i].textContent = t(key);
        }
        /* 2) 处理 data-i18n-placeholder */
        var phNodes = document.querySelectorAll('[data-i18n-placeholder]');
        for (var j = 0; j < phNodes.length; j++) {
            var pkey = phNodes[j].getAttribute('data-i18n-placeholder');
            if (pkey) phNodes[j].placeholder = t(pkey);
        }
        /* 3) 处理 data-i18n-html（允许含标签） */
        var htmlNodes = document.querySelectorAll('[data-i18n-html]');
        for (var k = 0; k < htmlNodes.length; k++) {
            var hkey = htmlNodes[k].getAttribute('data-i18n-html');
            if (hkey) htmlNodes[k].innerHTML = t(hkey);
        }
        /* 4) 更新 <title> */
        var titleKey = document.documentElement.getAttribute('data-i18n-title');
        if (titleKey) document.title = t(titleKey);
    }

    /* ---------- 语言选择器UI生成 ---------- */
    function renderLangSelector(containerId, options) {
        options = options || {};
        var container = document.getElementById(containerId);
        if (!container) return;
        var langs = [
            { code: 'zh-CN', labelKey: 'lang_zh_cn' },
            { code: 'zh-TW', labelKey: 'lang_zh_tw' },
            { code: 'en', labelKey: 'lang_en' }
        ];
        var html = '';
        for (var i = 0; i < langs.length; i++) {
            var active = langs[i].code === currentLang ? ' active' : '';
            html += '<button type="button" class="lang-btn' + active + '" data-lang="' + langs[i].code + '" onclick="I18N.setLanguage(\'' + langs[i].code + '\')">' + t(langs[i].labelKey) + '</button>';
        }
        container.innerHTML = html;
    }

    /* ---------- 页面初始化 ---------- */
    function init() {
        applyLanguage();
        /* 监听语言变化，刷新所有语言选择器的active状态 */
        document.addEventListener('languagechange', function () {
            var btns = document.querySelectorAll('.lang-btn');
            for (var i = 0; i < btns.length; i++) {
                if (btns[i].getAttribute('data-lang') === currentLang) {
                    btns[i].classList.add('active');
                } else {
                    btns[i].classList.remove('active');
                }
                /* 刷新按钮文字（主要是中文/繁体标签不变，英文也不变，但保险起见） */
                var code = btns[i].getAttribute('data-lang');
                var labelKey = code === 'zh-CN' ? 'lang_zh_cn' : code === 'zh-TW' ? 'lang_zh_tw' : 'lang_en';
                btns[i].textContent = t(labelKey);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ---------- 暴露全局API ---------- */
    global.I18N = {
        t: t,
        getLang: getLang,
        setLanguage: setLanguage,
        applyLanguage: applyLanguage,
        renderLangSelector: renderLangSelector
    };
})(window);
