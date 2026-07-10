# Lanyard Profile — ระบบหน้าโปรไฟล์ออนไลน์ + Discord Presence แบบเรียลไทม์

ระบบสร้างหน้าโปรไฟล์ส่วนตัว (bio / link-in-bio) เขียนด้วย **PHP ล้วน + MySQL** ผู้ใช้สมัครสมาชิก ปรับแต่งโปรไฟล์ของตัวเอง แล้วแชร์ลิงก์สวย ๆ เช่น `https://example.com/u/username`

จุดเด่นคือแสดง **สถานะ Discord แบบเรียลไทม์** (กำลังเล่นเกมอะไร ฟังเพลงอะไร ออนไลน์/ออฟไลน์) ผ่าน [Lanyard API](https://github.com/Phineas/lanyard) พร้อมระบบนับผู้เข้าชม โซเชียลลิงก์ และพื้นหลังแบบกำหนดเอง รองรับ 2 ภาษา (ไทย/อังกฤษ)

---

## สารบัญ

- [ฟีเจอร์](#ฟีเจอร์)
- [เทคโนโลยีที่ใช้](#เทคโนโลยีที่ใช้)
- [ความต้องการของระบบ](#ความต้องการของระบบ)
- [การติดตั้ง](#การติดตั้ง)
- [โครงสร้างโปรเจกต์](#โครงสร้างโปรเจกต์)
- [โครงสร้างฐานข้อมูล](#โครงสร้างฐานข้อมูล)
- [การใช้งาน](#การใช้งาน)
- [ระบบความปลอดภัย](#ระบบความปลอดภัย)
- [ระบบหลายภาษา (i18n)](#ระบบหลายภาษา-i18n)
- [คำถามที่พบบ่อย](#คำถามที่พบบ่อย)

---

## ฟีเจอร์

### สำหรับผู้เยี่ยมชม (หน้าโปรไฟล์)
- 🟢 **Discord Presence แบบเรียลไทม์** — แสดงสถานะออนไลน์, เกมที่เล่น, เพลงที่ฟัง (Spotify) ผ่าน WebSocket ของ Lanyard อัปเดตทันทีโดยไม่ต้องรีเฟรช
- 🔗 **โซเชียลลิงก์** — รวมลิงก์ Facebook, X (Twitter), Instagram, YouTube, TikTok, Twitch, GitHub, Telegram, Discord และลิงก์กำหนดเอง พร้อมไอคอนสวยงาม
- 👁️ **ตัวนับผู้เข้าชม** — นับยอดเข้าชมทั้งหมด และเก็บสถิติผู้เข้าชมแบบไม่ซ้ำต่อวัน (ด้วยการ hash IP)
- 🎨 **ดีไซน์ปรับตามพื้นหลัง** — ถ้าตั้งพื้นหลัง จะเป็นธีมกระจกโปร่งแสงสีเข้ม (glassmorphism) ถ้าไม่ตั้งจะเป็นธีมขาวสะอาดตา

### สำหรับผู้ใช้ (แดชบอร์ด)
- 🔐 **ระบบสมาชิก** — สมัคร / เข้าสู่ระบบ / ออกจากระบบ
- 🆔 **เชื่อม Discord ID** — ใส่ Discord User ID เพื่อดึง Presence
- 📇 **ข้อมูลติดต่อ** — ตั้งชื่อที่แสดง (display name) และอีเมลติดต่อ
- ➕ **จัดการโซเชียลลิงก์** — เพิ่ม/ลบลิงก์ พร้อมจัดลำดับอัตโนมัติ
- 🖼️ **พื้นหลังกำหนดเอง** — อัปโหลดรูป (JPG / PNG / WebP สูงสุด 5MB) หรือใส่ URL รูปภาพ
- 🎨 **สีข้อความ & สีตัวนับ** — ปรับสีตัวอักษรและตัวนับได้ตามใจ

### ทั่วไป
- 🌏 **รองรับ 2 ภาษา** — ไทย / อังกฤษ (เลือกอัตโนมัติจากประเทศผ่าน Cloudflare หรือเลือกเองได้)
- 🔗 **URL สวยงาม** — `/u/username` แทน `profile.php?u=username`

---

## เทคโนโลยีที่ใช้

| ส่วน | เทคโนโลยี |
|------|-----------|
| Backend | PHP 8+ (ใช้ `declare(strict_types=1)`, PDO) |
| ฐานข้อมูล | MySQL / MariaDB (InnoDB, utf8mb4) |
| Frontend | HTML, CSS, JavaScript (Vanilla) |
| Realtime | Lanyard API (REST + WebSocket) |
| ไอคอน | Font Awesome 6 |
| ฟอนต์ | IBM Plex Sans Thai (Google Fonts) |
| ตัวนับรูป | PHP GD (สร้างภาพ PNG) |
| เว็บเซิร์ฟเวอร์ | Apache (ใช้ `.htaccess` + mod_rewrite) |

---

## ความต้องการของระบบ

- **PHP 8.0 ขึ้นไป** พร้อมเอ็กซ์เทนชัน:
  - `pdo_mysql`
  - `gd` (สำหรับสร้างภาพตัวนับ)
- **MySQL 5.7+ / MariaDB 10.2+**
- **Apache** ที่เปิดใช้ `mod_rewrite` (สำหรับ URL สวยงาม)
- ผู้ใช้ต้องมี **Discord ID** และเข้าร่วม [Lanyard Discord Server](https://discord.gg/lanyard) เพื่อให้ API ดึง Presence ได้

---

## การติดตั้ง

### 1. ดาวน์โหลดโค้ด
```bash
git clone https://github.com/nexterz-th/Lanyard-Profile.git
cd Lanyard-Profile
```

### 2. สร้างฐานข้อมูลและนำเข้า schema
สร้างฐานข้อมูลใน MySQL แล้วนำเข้าไฟล์ `database.sql`:
```bash
mysql -u root -p profile_db < database.sql
```
> ไฟล์ `database.sql` จะสร้างตาราง `users`, `socials` และ `visitor_logs` ให้อัตโนมัติ

### 3. ตั้งค่าการเชื่อมต่อฐานข้อมูล
คัดลอกไฟล์ตัวอย่างแล้วแก้ไขข้อมูลจริง:
```bash
cp config/config.sample.php config/config.php
```
เปิด `config/config.php` แล้วแก้ไข:
```php
return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'profile_db',
        'user'    => 'your_db_user',
        'pass'    => 'your_db_password',
        'charset' => 'utf8mb4',
    ],
    'site' => [
        'base_url'           => 'https://example.com',
        'default_background' => 'https://your-cdn.com/default-bg.png',
    ],
];
```
> ⚠️ **สำคัญ:** ไฟล์ `config/config.php` เก็บรหัสผ่านฐานข้อมูล **ห้าม commit ขึ้น Git** (ควรใส่ไว้ใน `.gitignore`)

### 4. ตั้งค่าสิทธิ์โฟลเดอร์อัปโหลด
ให้เว็บเซิร์ฟเวอร์เขียนไฟล์ในโฟลเดอร์นี้ได้ (สำหรับพื้นหลังที่อัปโหลด):
```bash
chmod -R 755 uploads/backgrounds
```

### 5. รันเว็บไซต์
ชี้ Document Root ของ Apache มาที่โฟลเดอร์โปรเจกต์ แล้วเปิดผ่านเบราว์เซอร์ได้เลย

> 💡 สำหรับทดสอบในเครื่อง PHP built-in server ใช้ได้ แต่ **URL สวยงาม `/u/username` จะไม่ทำงาน** เพราะต้องใช้ mod_rewrite ของ Apache — แนะนำให้ใช้ Apache หรือ XAMPP/Laragon

---

## โครงสร้างโปรเจกต์

```
.
├── index.php              # หน้าแรก (landing page)
├── register.php           # สมัครสมาชิก
├── login.php              # เข้าสู่ระบบ
├── logout.php             # ออกจากระบบ
├── dashboard.php          # แดชบอร์ดจัดการโปรไฟล์
├── profile.php            # หน้าโปรไฟล์สาธารณะ (แสดง Discord Presence)
├── index.html             # (ไฟล์ HTML ต้นแบบ/สำรอง)
├── database.sql           # โครงสร้างฐานข้อมูล
├── .htaccess              # Apache rewrite rules + ป้องกันการเข้าถึงไฟล์ภายใน
│
├── config/
│   ├── config.sample.php  # ไฟล์ตั้งค่าตัวอย่าง (copy ไปเป็น config.php)
│   ├── config.php         # ตั้งค่าจริง (gitignored)
│   └── db.php             # เชื่อมต่อฐานข้อมูล (PDO)
│
├── includes/
│   ├── auth.php           # ระบบ session, login, CSRF
│   ├── functions.php      # ฟังก์ชันช่วยเหลือ + รายชื่อ social platforms
│   └── i18n.php           # ระบบหลายภาษา (ไทย/อังกฤษ)
│
├── counter/
│   └── counter.php        # สร้างภาพ PNG ตัวนับผู้เข้าชม (GD)
│
├── uploads/
│   └── backgrounds/       # เก็บรูปพื้นหลังที่ผู้ใช้อัปโหลด
│
└── assets/
    └── css/
        └── theme.css      # สไตล์หลักของเว็บ
```

---

## โครงสร้างฐานข้อมูล

### ตาราง `users`
เก็บข้อมูลผู้ใช้และการตั้งค่าโปรไฟล์
| คอลัมน์ | ชนิด | คำอธิบาย |
|---------|------|----------|
| `id` | INT | Primary Key |
| `username` | VARCHAR(20) | ชื่อผู้ใช้ (ไม่ซ้ำ, ใช้ใน URL) |
| `email` | VARCHAR(191) | อีเมลสมัคร (ไม่ซ้ำ) |
| `password_hash` | VARCHAR(255) | รหัสผ่านที่ hash แล้ว |
| `display_name` | VARCHAR(64) | ชื่อที่แสดง |
| `discord_id` | VARCHAR(25) | Discord User ID |
| `contact_email` | VARCHAR(191) | อีเมลติดต่อ (แสดงในโปรไฟล์) |
| `background_image` | VARCHAR(255) | ชื่อไฟล์พื้นหลังที่อัปโหลด |
| `background_url` | VARCHAR(500) | URL พื้นหลังจากภายนอก |
| `text_color` / `counter_color` | VARCHAR(7) | สี hex ที่กำหนดเอง |
| `hit_count` | INT | ยอดเข้าชมทั้งหมด |
| `created_at` | TIMESTAMP | วันที่สมัคร |

### ตาราง `socials`
เก็บโซเชียลลิงก์ของผู้ใช้ (ผูกกับ `users` แบบ `ON DELETE CASCADE`)

### ตาราง `visitor_logs`
บันทึกผู้เข้าชมแบบไม่ซ้ำต่อวัน โดยเก็บ **hash ของ IP** (SHA-256) ไม่เก็บ IP จริง เพื่อความเป็นส่วนตัว มี `UNIQUE KEY` ป้องกันการนับซ้ำในวันเดียวกัน

---

## การใช้งาน

1. **สมัครสมาชิก** ที่ `/register.php` (ชื่อผู้ใช้ 3–20 ตัว a-z, 0-9, `_`, `-` / รหัสผ่านอย่างน้อย 8 ตัว)
2. **เข้าสู่ระบบ** ที่ `/login.php`
3. ในหน้า **แดชบอร์ด** (`/dashboard.php`):
   - ใส่ **Discord ID** ของคุณ (วิธีหา: เปิด Developer Mode ใน Discord → คลิกขวาที่ชื่อตัวเอง → Copy User ID)
   - ตั้งชื่อที่แสดงและอีเมลติดต่อ
   - เพิ่มโซเชียลลิงก์
   - อัปโหลดหรือใส่ URL พื้นหลัง และปรับสีข้อความ
4. **แชร์โปรไฟล์** ของคุณที่ `https://your-domain.com/u/your-username`

> 📌 หากต้องการให้ Discord Presence แสดงผล ต้องเข้าร่วม [Lanyard Discord Server](https://discord.gg/lanyard) ด้วยบัญชี Discord ของคุณก่อน

---

## ระบบความปลอดภัย

โปรเจกต์นี้ให้ความสำคัญกับความปลอดภัย:

- ✅ **Prepared Statements (PDO)** — ป้องกัน SQL Injection ทุกจุด (ปิด emulate prepares)
- ✅ **CSRF Protection** — ทุกฟอร์มมี token ตรวจสอบด้วย `hash_equals()`
- ✅ **Password Hashing** — เก็บรหัสผ่านแบบ hash (ไม่เก็บ plain text)
- ✅ **XSS Prevention** — escape output ทุกจุดด้วยฟังก์ชัน `e()` (`htmlspecialchars`)
- ✅ **Session ปลอดภัย** — cookie ตั้งค่า `HttpOnly` + `SameSite=Lax`
- ✅ **ตรวจสอบไฟล์อัปโหลด** — เช็ค MIME จริงด้วย `getimagesize()`, จำกัดขนาด 5MB, ตั้งชื่อไฟล์แบบสุ่ม
- ✅ **ความเป็นส่วนตัว IP** — เก็บเฉพาะ SHA-256 hash ของ IP ไม่เก็บ IP จริง
- ✅ **ป้องกันการเข้าถึงไฟล์ภายใน** — `.htaccess` บล็อกโฟลเดอร์ `config/`, `includes/` และไฟล์ `.sql`, `config.php`

---

## ระบบหลายภาษา (i18n)

รองรับ **ไทย (th)** และ **อังกฤษ (en)** โดยเลือกภาษาตามลำดับความสำคัญ:

1. พารามิเตอร์ `?lang=th` หรือ `?lang=en` ใน URL (จะบันทึกลง cookie)
2. Cookie ภาษาที่เคยเลือก
3. ตรวจจับอัตโนมัติจากประเทศผ่าน Cloudflare (`HTTP_CF_IPCOUNTRY`) — ประเทศไทย = ไทย, อื่น ๆ = อังกฤษ

---

## คำถามที่พบบ่อย

**Q: Discord Presence ไม่แสดง?**
A: ตรวจสอบว่า (1) ใส่ Discord ID ถูกต้อง (2) เข้าร่วม [Lanyard Discord Server](https://discord.gg/lanyard) แล้ว (3) เปิดสถานะออนไลน์ใน Discord

**Q: URL `/u/username` เข้าไม่ได้ (404)?**
A: ต้องใช้ Apache ที่เปิด `mod_rewrite` — ตรวจสอบว่าไฟล์ `.htaccess` ทำงานและเปิด `AllowOverride All` ใน config ของ Apache

**Q: อัปโหลดพื้นหลังไม่ได้?**
A: ตรวจสอบสิทธิ์เขียนของโฟลเดอร์ `uploads/backgrounds/` และค่า `upload_max_filesize` / `post_max_size` ใน `php.ini` (ต้อง ≥ 5MB)

**Q: เจอข้อความ "Missing config/config.php"?**
A: ยังไม่ได้คัดลอก `config/config.sample.php` เป็น `config/config.php` — ทำตามขั้นตอนที่ 3 ในการติดตั้ง

---

## License

โปรเจกต์นี้เผยแพร่ภายใต้เงื่อนไขที่เจ้าของกำหนด — ระบุ License ของคุณที่นี่ (เช่น MIT)

---

<div align="center">

พัฒนาโดย **NEXTERZ** · หากพบปัญหาหรือมีข้อเสนอแนะ เปิด [Issue](../../issues) ได้เลย

</div>
