<footer id="main-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <div class="footer-logo-group">
                <img src="assets/images/logo.png" alt="Mendly Logo" class="footer-logo">
            </div>
            <p class="footer-tagline">Giving your cherished items a second life with care and craftsmanship.</p>
            <p class="footer-address">
                📍 <strong>Mendly Studio</strong><br>
                Lot 2.01, Central Market (Pasar Seni),<br>
                Jalan Hang Kasturi, 50050 Kuala Lumpur, Malaysia
            </p>
        </div>

        <div class="footer-links">
            <h4 class="footer-heading">Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="pages/browse-services.php">Repair Services</a></li>
                <li><a href="pages/experts.php">Find Experts</a></li>
                <li><a href="pages/about.php">About Us</a></li>
                <li><a href="pages/contact.php">Contact Us</a></li>
            </ul>
        </div>

        <div class="footer-social">
            <h4 class="footer-heading">Follow Us</h4>
            <div class="social-icons">
                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer"><img src="assets/images/facebook.png" alt="Facebook" class="social-icon"></a>
                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer"><img src="assets/images/instagram.png" alt="Instagram" class="social-icon"></a>
                <a href="https://xiaohongshu.com" target="_blank" rel="noopener noreferrer"><img src="assets/images/redNote.png" alt="Red Note" class="social-icon"></a>
                <a href="https://x.com" target="_blank" rel="noopener noreferrer"><img src="assets/images/x.png" alt="X (Twitter)" class="social-icon"></a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; 2026 Mendly. All rights reserved. | Handcrafted with Care</p>
    </div>
</footer>

<!-- Site-wide floating chat widget (only rendered for logged-in users).
     The page that includes this footer closes </body></html> itself. -->
<link rel="stylesheet" href="assets/css/chat.css">
<?php include __DIR__ . '/chat-widget.php'; ?>

<!-- "Ask Mendly" help assistant, for everyone (guests included) -->
<?php include __DIR__ . '/assistant-widget.php'; ?>