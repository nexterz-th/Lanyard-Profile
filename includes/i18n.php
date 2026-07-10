<?php
declare(strict_types=1);

const SUPPORTED_LANGS = ['en', 'th'];

function detect_default_lang(): string {
    $country = strtoupper($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '');
    return $country === 'TH' ? 'th' : 'en';
}

function current_lang(): string {
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS, true)) {
        $lang = $_GET['lang'];
        if (!headers_sent()) {
            setcookie('lang', $lang, time() + 60 * 60 * 24 * 365, '/', '', false, true);
        }
        return $lang;
    }

    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], SUPPORTED_LANGS, true)) {
        $lang = $_COOKIE['lang'];
        return $lang;
    }

    $lang = detect_default_lang();
    return $lang;
}

/** Builds a URL for the current request with ?lang=<lang> set, preserving other query params. */
function lang_url(string $lang): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $query = $_GET;
    $query['lang'] = $lang;
    return $path . '?' . http_build_query($query);
}

function lang_switcher_html(): string {
    $current = current_lang();
    $en = $current === 'en' ? 'active' : '';
    $th = $current === 'th' ? 'active' : '';
    return '<div class="lang-switch">'
        . '<a href="' . e(lang_url('en')) . '" class="lang-btn ' . $en . '">EN</a>'
        . '<a href="' . e(lang_url('th')) . '" class="lang-btn ' . $th . '">TH</a>'
        . '</div>';
}

/**
 * Dark/light theme toggle button (top-left). Dark is the default; the choice
 * is stored in localStorage and applied by the inline <head> script before
 * paint. The trailing script only syncs the icon to whatever theme is active.
 */
function theme_toggle_html(): string {
    return '<button type="button" class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark/light mode" title="Toggle dark/light mode">'
        . '<i class="fa-solid fa-sun"></i>'
        . '</button>'
        . '<script>'
        . 'function toggleTheme(){'
        . 'var cur=document.documentElement.getAttribute("data-theme")==="light"?"light":"dark";'
        . 'var next=cur==="light"?"dark":"light";'
        . 'document.documentElement.setAttribute("data-theme",next);'
        . 'try{localStorage.setItem("theme",next);}catch(e){}'
        . 'syncThemeIcon();'
        . '}'
        . 'function syncThemeIcon(){'
        . 'var t=document.documentElement.getAttribute("data-theme")==="light"?"light":"dark";'
        . 'var i=document.querySelector(".theme-toggle i");'
        . 'if(i){i.className=t==="light"?"fa-solid fa-moon":"fa-solid fa-sun";}'
        . '}'
        . 'syncThemeIcon();'
        . '</script>';
}

$GLOBALS['__i18n'] = [
    'en' => [
        'nav.dashboard' => 'Dashboard',
        'nav.view_profile' => 'My Profile',
        'nav.logout' => 'Log out',
        'nav.login' => 'Log in',
        'nav.register' => 'Register',
        'nav.hello' => 'Hi, :name',

        'home.badge' => 'Discord Presence · Real-time',
        'home.desc' => 'Build your own profile page showing live Discord presence, social links, contact info, and a visitor counter.',
        'home.cta_dashboard' => 'Go to Dashboard',
        'home.cta_view_profile' => 'View My Profile',
        'home.cta_register' => 'Register',
        'home.cta_login' => 'Log in',

        'register.title' => 'Register',
        'register.username' => 'Username',
        'register.username_placeholder' => 'e.g. nexterz',
        'register.email' => 'Email',
        'register.password' => 'Password',
        'register.confirm_password' => 'Confirm Password',
        'register.submit' => 'Register',
        'register.have_account' => 'Already have an account?',
        'register.login_link' => 'Log in',
        'register.err_username' => 'Username must be lowercase letters, numbers, _ or -, 3-20 characters',
        'register.err_email' => 'Invalid email address',
        'register.err_password_len' => 'Password must be at least 8 characters',
        'register.err_password_match' => 'Passwords do not match',
        'register.err_taken' => 'That username or email is already taken',
        'register.success' => 'Registration successful! Please log in.',

        'login.title' => 'Log in',
        'login.field' => 'Username or Email',
        'login.password' => 'Password',
        'login.submit' => 'Log in',
        'login.no_account' => "Don't have an account?",
        'login.register_link' => 'Register',
        'login.err_required' => 'Please enter your username/email and password',
        'login.err_invalid' => 'Incorrect username/email or password',

        'dash.hello' => 'Hi, :name',
        'dash.view_profile' => 'View my profile',
        'dash.logout' => 'Log out',

        'dash.counter.title' => 'Visitor Counter',
        'dash.counter.total' => 'Total Views',
        'dash.counter.unique_all' => 'Unique Visitors (All time)',
        'dash.counter.unique_today' => 'Unique Visitors (Today)',
        'dash.counter.hint' => 'The counter increases automatically every time someone opens your profile at :url',

        'dash.discord.title' => 'Discord ID',
        'dash.discord.label' => 'Discord ID (used to fetch data via the Lanyard API)',
        'dash.discord.placeholder' => 'e.g. 552404470068150302',
        'dash.discord.note' => 'Note: you must join the Lanyard Discord server (https://discord.gg/lanyard) before your status can be fetched.',
        'dash.save' => 'Save',
        'dash.err_discord' => 'Invalid Discord ID (must be 15-25 digits, or leave blank)',
        'dash.success_discord' => 'Discord ID updated successfully',

        'dash.social.title' => 'Social Links',
        'dash.social.empty' => 'No social links yet — add one below',
        'dash.social.delete' => 'Delete',
        'dash.social.confirm_delete' => 'Delete this link?',
        'dash.social.platform' => 'Platform',
        'dash.social.custom_label' => 'Display name (Custom Link only)',
        'dash.social.custom_label_placeholder' => 'e.g. My Website',
        'dash.social.url' => 'URL',
        'dash.social.add' => 'Add Social Link',
        'dash.err_url' => 'Invalid URL (must start with http:// or https://)',
        'dash.success_social_add' => 'Social link added successfully',
        'dash.success_social_delete' => 'Social link removed successfully',

        'dash.contact.title' => 'Contact Us / Send Email',
        'dash.contact.display_name' => 'Display name shown on your profile',
        'dash.contact.email' => 'Contact Email (the Send Email button will go here)',
        'dash.err_contact_email' => 'Invalid contact email',
        'dash.success_contact' => 'Contact info updated successfully',

        'dash.bg.title' => 'Profile Background',
        'dash.bg.source_file' => 'Uploaded file',
        'dash.bg.source_url' => 'URL link',
        'dash.bg.source' => 'Current source:',
        'dash.bg.default' => 'Currently using the default background',
        'dash.bg.upload_label' => 'Upload a new image (JPG/PNG/WEBP, max 5MB)',
        'dash.bg.upload' => 'Upload',
        'dash.bg.or_url' => 'Or use an image URL directly',
        'dash.bg.use_url' => 'Use this URL as background',
        'dash.bg.remove' => 'Remove custom background',
        'dash.bg.confirm_remove' => 'Remove custom background and use the default?',
        'dash.err_no_file' => 'Please choose an image file',
        'dash.err_upload_failed' => 'Upload failed',
        'dash.err_file_too_big' => 'File is too large (max 5MB)',
        'dash.err_file_type' => 'Only JPG, PNG, WEBP files are supported',
        'dash.err_save_failed' => 'Failed to save the file',
        'dash.err_bg_url' => 'Invalid background image URL (must start with http:// or https://)',
        'dash.success_bg' => 'Background updated successfully',
        'dash.success_bg_url' => 'Background updated from URL successfully',
        'dash.success_bg_removed' => 'Custom background removed (back to default)',

        'dash.color.title' => 'Profile Text Color',
        'dash.color.desc' => 'Set the main text color shown on your profile page (:url). If left unset, it is chosen automatically based on your background (no background = black text on white, background set = white text).',
        'dash.color.label' => 'Text color',
        'dash.color.default_suffix' => ' (default)',
        'dash.color.reset' => 'Reset to default',
        'dash.err_color' => 'Invalid color code (must be #RRGGBB)',
        'dash.success_color' => 'Text color updated successfully',
        'dash.success_color_reset' => 'Text color reset to default successfully',
        'dash.counter_color.label' => 'Visitor Counter number color (defaults to the text color above if unset)',
        'dash.success_counter_color' => 'Visitor Counter color updated successfully',
        'dash.success_counter_color_reset' => 'Visitor Counter color reset to default successfully',

        'profile.no_discord' => 'This user has not set up a Discord ID yet.',
        'profile.status' => 'Status',
        'profile.discord_profile' => 'Discord Profile',
        'profile.contact_us' => 'Contact Us',
        'profile.send_email' => 'Send Email',
        'profile.visitor_counter' => 'Visitor Counter',
        'profile.lanyard_api' => 'Lanyard API',
        'profile.activity' => 'Activity',
        'profile.activity_desc' => 'Live status and activity',
        'profile.discord_id' => 'Discord ID:',
        'profile.now_watching' => 'Now Watching',
        'profile.now_listening' => 'Now Listening',
        'profile.js.status_online' => 'Online',
        'profile.js.status_idle' => 'Idle',
        'profile.js.status_dnd' => 'Busy',
        'profile.js.status_offline' => 'Offline',
        'profile.js.resting_title' => 'Resting… no activity',
        'profile.js.resting_desc' => 'No Discord or Spotify activity showing right now',
        'profile.js.playing' => 'Playing',
        'profile.js.update' => 'Update: :time',
        'profile.js.connecting' => 'Connecting…',
        'profile.js.connected_rest' => 'Connected (via REST)',
        'profile.js.rest_error' => 'REST fetch error: :msg',
        'profile.js.connecting_ws' => 'Connecting Web Socket…',
        'profile.js.ws_connected' => 'Web Socket Connected',
        'profile.js.ws_closed' => 'Connection lost, retrying… (data may be outdated)',
    ],
    'th' => [
        'nav.dashboard' => 'Dashboard',
        'nav.view_profile' => 'ดูโปรไฟล์ของฉัน',
        'nav.logout' => 'ออกจากระบบ',
        'nav.login' => 'เข้าสู่ระบบ',
        'nav.register' => 'สมัครสมาชิก',
        'nav.hello' => 'สวัสดี, :name',

        'home.badge' => 'Discord Presence · เรียลไทม์',
        'home.desc' => 'สร้างหน้าโปรไฟล์ของคุณเอง แสดง Discord Presence, Social Links, Contact และ Visitor Counter แบบเรียลไทม์',
        'home.cta_dashboard' => 'ไปที่ Dashboard',
        'home.cta_view_profile' => 'ดูโปรไฟล์ของฉัน',
        'home.cta_register' => 'สมัครสมาชิก',
        'home.cta_login' => 'เข้าสู่ระบบ',

        'register.title' => 'สมัครสมาชิก',
        'register.username' => 'Username',
        'register.username_placeholder' => 'เช่น nexterz',
        'register.email' => 'Email',
        'register.password' => 'Password',
        'register.confirm_password' => 'Confirm Password',
        'register.submit' => 'สมัครสมาชิก',
        'register.have_account' => 'มีบัญชีอยู่แล้ว?',
        'register.login_link' => 'เข้าสู่ระบบ',
        'register.err_username' => 'Username ต้องเป็นตัวอักษรเล็ก a-z, ตัวเลข, _ หรือ - ความยาว 3-20 ตัวอักษร',
        'register.err_email' => 'อีเมลไม่ถูกต้อง',
        'register.err_password_len' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
        'register.err_password_match' => 'รหัสผ่านยืนยันไม่ตรงกัน',
        'register.err_taken' => 'Username หรือ Email นี้ถูกใช้งานแล้ว',
        'register.success' => 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ',

        'login.title' => 'เข้าสู่ระบบ',
        'login.field' => 'Username หรือ Email',
        'login.password' => 'Password',
        'login.submit' => 'เข้าสู่ระบบ',
        'login.no_account' => 'ยังไม่มีบัญชี?',
        'login.register_link' => 'สมัครสมาชิก',
        'login.err_required' => 'กรุณากรอก Username/Email และ Password',
        'login.err_invalid' => 'Username/Email หรือ Password ไม่ถูกต้อง',

        'dash.hello' => 'สวัสดี, :name',
        'dash.view_profile' => 'ดูโปรไฟล์ของฉัน',
        'dash.logout' => 'ออกจากระบบ',

        'dash.counter.title' => 'Visitor Counter',
        'dash.counter.total' => 'Total Views',
        'dash.counter.unique_all' => 'Unique Visitors (All time)',
        'dash.counter.unique_today' => 'Unique Visitors (Today)',
        'dash.counter.hint' => 'ตัวนับจะเพิ่มขึ้นอัตโนมัติทุกครั้งที่มีคนเปิดโปรไฟล์ของคุณที่ :url',

        'dash.discord.title' => 'Discord ID',
        'dash.discord.label' => 'Discord ID (ใช้ดึงข้อมูลผ่าน Lanyard API)',
        'dash.discord.placeholder' => 'เช่น 552404470068150302',
        'dash.discord.note' => 'หมายเหตุ: ต้องเข้าร่วม Discord server ของ Lanyard (https://discord.gg/lanyard) ก่อน ระบบถึงจะดึงสถานะได้',
        'dash.save' => 'บันทึก',
        'dash.err_discord' => 'Discord ID ไม่ถูกต้อง (ต้องเป็นตัวเลข 15-25 หลัก หรือเว้นว่าง)',
        'dash.success_discord' => 'อัปเดต Discord ID สำเร็จ',

        'dash.social.title' => 'Social Links',
        'dash.social.empty' => 'ยังไม่มี Social Link — เพิ่มด้านล่างได้เลย',
        'dash.social.delete' => 'ลบ',
        'dash.social.confirm_delete' => 'ลบลิงก์นี้?',
        'dash.social.platform' => 'แพลตฟอร์ม',
        'dash.social.custom_label' => 'ชื่อที่แสดง (สำหรับ Custom Link เท่านั้น)',
        'dash.social.custom_label_placeholder' => 'เช่น My Website',
        'dash.social.url' => 'URL',
        'dash.social.add' => 'เพิ่ม Social Link',
        'dash.err_url' => 'URL ไม่ถูกต้อง (ต้องขึ้นต้นด้วย http:// หรือ https://)',
        'dash.success_social_add' => 'เพิ่ม Social Link สำเร็จ',
        'dash.success_social_delete' => 'ลบ Social Link สำเร็จ',

        'dash.contact.title' => 'Contact Us / Send Email',
        'dash.contact.display_name' => 'ชื่อที่แสดงบนโปรไฟล์',
        'dash.contact.email' => 'Contact Email (ปุ่ม Send Email จะส่งไปที่นี่)',
        'dash.err_contact_email' => 'Contact Email ไม่ถูกต้อง',
        'dash.success_contact' => 'อัปเดตข้อมูลติดต่อสำเร็จ',

        'dash.bg.title' => 'พื้นหลังโปรไฟล์',
        'dash.bg.source_file' => 'ไฟล์ที่อัปโหลด',
        'dash.bg.source_url' => 'ลิงก์ URL',
        'dash.bg.source' => 'แหล่งที่มา:',
        'dash.bg.default' => 'ตอนนี้ใช้พื้นหลังเริ่มต้นอยู่',
        'dash.bg.upload_label' => 'อัปโหลดรูปใหม่ (JPG/PNG/WEBP, สูงสุด 5MB)',
        'dash.bg.upload' => 'อัปโหลด',
        'dash.bg.or_url' => 'หรือใส่ URL รูปภาพโดยตรง',
        'dash.bg.use_url' => 'ใช้ URL นี้เป็นพื้นหลัง',
        'dash.bg.remove' => 'ลบพื้นหลังกำหนดเอง',
        'dash.bg.confirm_remove' => 'ลบพื้นหลังกำหนดเองและกลับไปใช้ค่าเริ่มต้น?',
        'dash.err_no_file' => 'กรุณาเลือกไฟล์รูปภาพ',
        'dash.err_upload_failed' => 'อัปโหลดไฟล์ไม่สำเร็จ',
        'dash.err_file_too_big' => 'ไฟล์ใหญ่เกินไป (สูงสุด 5MB)',
        'dash.err_file_type' => 'รองรับเฉพาะไฟล์ JPG, PNG, WEBP เท่านั้น',
        'dash.err_save_failed' => 'บันทึกไฟล์ไม่สำเร็จ',
        'dash.err_bg_url' => 'URL รูปพื้นหลังไม่ถูกต้อง (ต้องขึ้นต้นด้วย http:// หรือ https://)',
        'dash.success_bg' => 'เปลี่ยนพื้นหลังสำเร็จ',
        'dash.success_bg_url' => 'เปลี่ยนพื้นหลังจาก URL สำเร็จ',
        'dash.success_bg_removed' => 'ลบพื้นหลังกำหนดเองสำเร็จ (กลับไปใช้ค่าเริ่มต้น)',

        'dash.color.title' => 'สีตัวหนังสือโปรไฟล์',
        'dash.color.desc' => 'กำหนดสีตัวหนังสือหลักที่แสดงบนหน้าโปรไฟล์ (:url) ถ้าไม่ตั้งค่า ระบบจะเลือกให้อัตโนมัติตามพื้นหลัง (ไม่มีพื้นหลัง = ตัวหนังสือสีดำบนพื้นขาว, มีพื้นหลัง = ตัวหนังสือสีขาว)',
        'dash.color.label' => 'สีตัวหนังสือ',
        'dash.color.default_suffix' => ' (ค่าเริ่มต้น)',
        'dash.color.reset' => 'รีเซ็ตเป็นค่าเริ่มต้น',
        'dash.err_color' => 'รหัสสีไม่ถูกต้อง (ต้องเป็นรูปแบบ #RRGGBB)',
        'dash.success_color' => 'อัปเดตสีตัวหนังสือสำเร็จ',
        'dash.success_color_reset' => 'รีเซ็ตสีตัวหนังสือเป็นค่าเริ่มต้นสำเร็จ',
        'dash.counter_color.label' => 'สีตัวเลข Visitor Counter (ถ้าไม่ตั้งจะใช้สีเดียวกับสีตัวหนังสือด้านบน)',
        'dash.success_counter_color' => 'อัปเดตสีตัวเลข Visitor Counter สำเร็จ',
        'dash.success_counter_color_reset' => 'รีเซ็ตสีตัวเลข Visitor Counter เป็นค่าเริ่มต้นสำเร็จ',

        'profile.no_discord' => 'ผู้ใช้นี้ยังไม่ได้ตั้งค่า Discord ID',
        'profile.status' => 'สถานะ',
        'profile.discord_profile' => 'โปรไฟล์ Discord',
        'profile.contact_us' => 'ติดต่อเรา',
        'profile.send_email' => 'ส่ง E-mail',
        'profile.visitor_counter' => 'ผู้เข้าชมเว็บไซต์',
        'profile.lanyard_api' => 'Lanyard API',
        'profile.activity' => 'กิจกรรม',
        'profile.activity_desc' => 'แสดงสถานะและกิจกรรมแบบสด',
        'profile.discord_id' => 'Discord ID:',
        'profile.now_watching' => 'กำลังดู',
        'profile.now_listening' => 'กำลังฟัง',
        'profile.js.status_online' => 'ออนไลน์',
        'profile.js.status_idle' => 'ไม่อยู่',
        'profile.js.status_dnd' => 'ไม่ว่าง',
        'profile.js.status_offline' => 'ออฟไลน์',
        'profile.js.resting_title' => 'พักผ่อน... ไม่มีกิจกรรม',
        'profile.js.resting_desc' => 'ไม่มีกิจกรรม Discord หรือ Spotify ที่กำลังแสดงอยู่ ณ ขณะนี้',
        'profile.js.playing' => 'กำลังเล่น',
        'profile.js.update' => 'อัปเดต: :time',
        'profile.js.connecting' => 'กำลังเชื่อมต่อ…',
        'profile.js.connected_rest' => 'เชื่อมต่อแล้ว (REST)',
        'profile.js.rest_error' => 'เชื่อมต่อ REST ผิดพลาด: :msg',
        'profile.js.connecting_ws' => 'กำลังเชื่อมต่อ Web Socket…',
        'profile.js.ws_connected' => 'เชื่อมต่อ Web Socket แล้ว',
        'profile.js.ws_closed' => 'การเชื่อมต่อขาดหาย กำลังลองใหม่… (ข้อมูลอาจไม่อัปเดต)',
    ],
];

function t(string $key, array $params = []): string {
    $lang = current_lang();
    $str = $GLOBALS['__i18n'][$lang][$key] ?? $GLOBALS['__i18n']['en'][$key] ?? $key;
    foreach ($params as $name => $value) {
        $str = str_replace(':' . $name, $value, $str);
    }
    return $str;
}
