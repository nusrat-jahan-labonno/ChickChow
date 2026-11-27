# ChickChow - Online Food Ordering System

ChickChow is an online food ordering website built for the course
**Web Technologies and Programming Lab (CSE 06132228)** at
World University of Bangladesh.

## Features
- User registration, login and profile management (change password, saved addresses)
- Browse restaurants and menu, search and filter food
- Cart, coupons and discounts, checkout with delivery charge by area
- Order tracking (Pending, Preparing, Out for Delivery, Delivered)
- Reviews after delivery
- Admin panel: food, restaurants, categories, orders, coupons, users, change password
- Secure login (hashed passwords, CSRF protection, prepared statements)
- Responsive design for laptop, tablet and phone

## Built With
PHP 8, MySQL (PDO), HTML, CSS, AJAX (fetch)

## Run Locally (XAMPP)
1. Start Apache and MySQL in XAMPP.
2. Copy the project folder to `C:\xampp\htdocs\chickchow`.
3. Open phpMyAdmin and import `database/chickchow.sql`.
4. Open `http://localhost/chickchow`.

## Run Online (Vercel + cloud MySQL)
1. Create a free MySQL database (for example TiDB Cloud) and run `database/chickchow_cloud.sql` in its SQL Editor.
2. Import this GitHub repository in Vercel.
3. In Vercel, add these Environment Variables:
   `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL` = `1`
4. Deploy. No password is stored in the code.

## Developer
Nusrat Jahan Labonno
Roll 4572, ID WUB 03/25/69/4572, Batch 69C, Dept. of CSE, World University of Bangladesh

Submitted to: Kh. Mustafizur Rahman, Senior Lecturer, Dept. of CSE
