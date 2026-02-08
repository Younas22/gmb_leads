Hostinger Cron Job Setup
Step 1: Hostinger cPanel mein Login karein
Step 2: Cron Jobs section kholen
cPanel mein "Advanced" section dekhen
"Cron Jobs" par click karein
Step 3: Cron Job Add karein
Command to run:


cd /home/u123456789/domains/yourdomain.com/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
Important:

/home/u123456789/domains/yourdomain.com/public_html ko apne actual path se replace karein
Path janney ke liye cPanel file manager mein dekhen ya pwd command chalayein
Frequency:


* * * * *
(Yeh har minute chalega)

Step 4: Verify karein
Aapka scheduled command (subscriptions:check-expired) automatically daily at midnight (12:00 AM) chalega.

Alternative: Direct Command (Agar schedule:run kaam na kare)

0 0 * * * cd /home/u123456789/domains/yourdomain.com/public_html && /usr/bin/php artisan subscriptions:check-expired >> /dev/null 2>&1
Yeh directly command ko daily midnight par chalayega.

Testing ke liye:
Current path check karne ke liye terminal mein:


pwd
Chahein to main aapke liye exact command likh sakta hoon - bas apna Hostinger path bataein?