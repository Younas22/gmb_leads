# Composer Commands Guide (اردو/हिंदी)

## کمپوزر کمانڈز کی تفصیل / Composer Commands Ki Tafseel

---

## 1. **Composer Install**
### کب استعمال کریں / Kab Use Karein:
- ✅ **پہلی بار پروجیکٹ سیٹ اپ** - Jab pehli baar project setup kar rahe hon
- ✅ **Git pull کے بعد** - Jab kisi ne dependencies update ki hon
- ✅ **Production deployment** - Live server par lagane se pehle
- ✅ **Vendor folder delete کرنے کے بعد** - Jab vendor folder delete ho gaya ho

### کیا کرتا ہے / Kya Karta Hai:
- `composer.lock` فائل سے exact versions install کرتا ہے
- Packages کو update **نہیں** کرتا
- `vendor/` directory بناتا ہے

---

## 2. **Composer Update**
### کب استعمال کریں / Kab Use Karein:
- ✅ **تمام packages update کرنے کے لیے** - Latest versions chahiye
- ✅ **Security fixes کے لیے** - Security updates ke liye
- ✅ **composer.json change کرنے کے بعد** - Manual edit ke baad
- ❌ **Production پر نہ چلائیں** - Testing ke baghair production par mat chalao

### کیا کرتا ہے / Kya Karta Hai:
- تمام packages کو latest version میں update کرتا ہے
- `composer.lock` فائل update کرتا ہے
- ⚠️ **خطرہ**: Breaking changes آ سکتے ہیں

---

## 3. **Composer Dump-Autoload**
### کب استعمال کریں / Kab Use Karein:
- ✅ **نئی class بنانے کے بعد** - Jab "Class not found" error aaye
- ✅ **Files move/rename کرنے کے بعد** - Files ki location change karne ke baad
- ✅ **نئے namespace add کرنے کے بعد** - Naya PSR-4 namespace add kiya ho
- ✅ **Performance optimize کرنے کے لیے** - Production ke liye optimization

### کیا کرتا ہے / Kya Karta Hai:
- `vendor/autoload.php` فائل regenerate کرتا ہے
- PHP classes کا نیا map بناتا ہے
- Packages download **نہیں** کرتا

**مثال / Misal**: Naya Controller banaya lekin "Class not found" aa raha hai

---

## 4. **Composer Clear Cache**
### کب استعمال کریں / Kab Use Karein:
- ✅ **Download errors آ رہے ہوں** - Package download fail ho raha ho
- ✅ **Corrupted cache ہو** - Ajeeb errors aa rahe hon
- ✅ **Disk space کم ہو** - Storage clean karni ho
- ✅ **Fresh download چاہیے** - Dobara se download karna ho

### کیا کرتا ہے / Kya Karta Hai:
- Composer کا cache clear کرتا ہے
- اگلے install/update میں fresh download ہوگا
- Cached packages کو delete کرتا ہے

---

## 5. **Composer Diagnose**
### کب استعمال کریں / Kab Use Karein:
- ✅ **Problems troubleshoot کرنے کے لیے** - Jab composer theek se kaam nahi kar raha
- ✅ **System check کرنے کے لیے** - Installation verify karna ho
- ✅ **Network issues** - Internet connection check karna ho
- ✅ **Permission problems** - File permissions verify karni hon

### کیا کرتا ہے / Kya Karta Hai:
- PHP version aur extensions check کرتا ہے
- Network connectivity test کرتا ہے
- Git configuration verify کرتا ہے
- Composer installation validate کرتا ہے

---

## 6. **Composer Validate**
### کب استعمال کریں / Kab Use Karein:
- ✅ **Git commit سے پہلے** - Code commit karne se pehle
- ✅ **composer.json edit کرنے کے بعد** - Manual changes ke baad
- ✅ **Syntax errors check کرنے کے لیے** - File validate karni ho

### کیا کرتا ہے / Kya Karta Hai:
- `composer.json` کی syntax check کرتا ہے
- Required fields verify کرتا ہے
- Version constraints validate کرتا ہے
- Errors aur warnings dikhata ہے

---

## عام استعمال / Aam Istemaal

### 🆕 نیا پروجیکٹ سیٹ اپ / Naya Project Setup
```bash
1. git clone <repository>
2. composer install چلائیں
3. .env فائل بنائیں
4. php artisan key:generate
```

### 🔄 Git Pull کے بعد / Git Pull Ke Baad
```bash
1. git pull origin main
2. composer install
3. php artisan migrate
```

### 📝 نئی Classes بنانے کے بعد / Nayi Classes Banane Ke Baad
```bash
1. Controller/Model بنائیں
2. composer dump-autoload
3. Cache clear کریں
```

### 🔧 Problems Fix کرنا / Problems Fix Karna
```bash
1. composer diagnose
2. composer clear-cache
3. composer install
```

---

## ہر کمانڈ کی خلاصہ جدول / Summary Table

| کمانڈ | مقصد | کب استعمال | خطرہ |
|--------|------|------------|------|
| **Install** | Dependencies نصب کرنا | Git pull کے بعد | کم |
| **Update** | Packages update کرنا | ماہانہ | درمیانہ |
| **Dump-Autoload** | Classes refresh کرنا | نئی files کے بعد | کم |
| **Clear Cache** | Cache صاف کرنا | جب ضرورت ہو | کم |
| **Diagnose** | مسائل check کرنا | Troubleshooting | کم |
| **Validate** | Files verify کرنا | Commit سے پہلے | کم |

---

## اہم نوٹس / Ahem Notes

### ✅ کریں / Karein:
- Production میں `composer install` استعمال کریں (update نہیں)
- نئی class کے بعد `dump-autoload` ضرور چلائیں
- Commit سے پہلے `validate` ضرور کریں
- `composer.lock` کو git میں رکھیں

### ❌ نہ کریں / Na Karein:
- Production میں `composer update` بغیر testing کے نہ چلائیں
- `composer.lock` delete نہ کریں
- `vendor/` folder manually edit نہ کریں
- Warnings کو ignore نہ کریں

---

## Performance Tab میں کیا Add ہوا / Performance Tab Mein Kya Add Hua

آپ کے **Admin → Settings → Performance** tab میں اب یہ سب کچھ موجود ہے:

1. ✅ **Cache Management** - Laravel cache clear
2. ✅ **Database Optimization** - Database optimize
3. ✅ **Composer Install** - Dependencies install
4. ✅ **Composer Update** - Packages update
5. ✅ **Dump Autoload** - Classes refresh
6. ✅ **Composer Clear Cache** - Composer cache clear
7. ✅ **Composer Diagnose** - System check
8. ✅ **Composer Validate** - Files validate

**ہر کمانڈ کے ساتھ**:
- Clear description (کب استعمال کرنی ہے)
- One-click button
- Real-time output display
- Success/error messages

---

## مدد کے لیے / Madad Ke Liye

**Documentation File**: `COMPOSER_COMMANDS_GUIDE.md` (تفصیلی انگلش میں)

**استعمال کا طریقہ / Istemaal Ka Tareeqa**:
1. Admin panel میں login کریں
2. Settings → Performance tab میں جائیں
3. جو کمانڈ چاہیے اس کا button click کریں
4. نتیجہ screen پر دیکھیں

---

**آخری تبدیلی / Last Update**: فروری 2026 / February 2026
