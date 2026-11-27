</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col footer-about">
                <div class="footer-brand">
                    <img src="<?= BASE_URL ?>/assets/images/favicon.png" alt="ChickChow">
                    <span>Chick<b>Chow</b></span>
                </div>
                <p>ChickChow is an online food ordering website. Pick your favourite food from the best restaurants in Dhaka and get it delivered fast to your door.</p>
                <p class="footer-tag">#GoodFood #HappyPeople</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <a href="<?= BASE_URL ?>/index.php">Home</a>
                <a href="<?= BASE_URL ?>/menu.php">Menu</a>
                <a href="<?= BASE_URL ?>/restaurants.php">Restaurants</a>
                <a href="<?= BASE_URL ?>/menu.php?deals=1">Deals</a>
                <a href="<?= BASE_URL ?>/orders.php">My Orders</a>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <a href="<?= BASE_URL ?>/login.php">Login</a>
                <a href="<?= BASE_URL ?>/register.php">Register</a>
                <a href="<?= BASE_URL ?>/profile.php">My Profile</a>
                <a href="<?= BASE_URL ?>/profile.php?tab=password">Change Password</a>
                <a href="<?= BASE_URL ?>/cart.php">My Cart</a>
            </div>
            <div class="footer-col footer-contact">
                <h4>Contact</h4>
                <span class="fc-label">Customer Care (24/7)</span>
                <a class="fc-phone" href="tel:+8801700000000">+880 1700-000000</a>
                <span class="fc-label">Email Support</span>
                <a href="mailto:support@chickchow.com">support@chickchow.com</a>
                <span class="fc-label">Office</span>
                <span class="fc-text">Dhaka, Bangladesh</span>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> ChickChow. All rights reserved.</span>
            <span>Developed by <b>Nusrat Jahan Labonno</b></span>
        </div>
    </div>
</footer>

<?php if (!isset($no_drawer)): ?>
<div class="drawer-overlay" id="drawerOverlay" hidden></div>
<aside class="cart-drawer" id="cartDrawer" aria-hidden="true" aria-label="Your cart">
    <div class="drawer-head">
        <h2>Your Cart <span class="muted" id="drawerCount"></span></h2>
        <button type="button" class="drawer-close" id="drawerClose" aria-label="Close cart">&times;</button>
    </div>
    <div class="drawer-body" id="drawerBody"></div>
</aside>
<?php endif; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
