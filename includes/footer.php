<?php
/**
 * COLDCONNECT - Global Footer Component
 */
?>
</main>

<footer class="footer">
    <div class="container">
        <div class="grid-3">
            <div>
                <div class="brand-logo" style="margin-bottom: 1rem;">
                    <span class="brand-icon"><i class="fa-solid fa-wheat-awn"></i></span>
                    <span style="color: white;">Agri<span class="brand-accent">Storage</span></span>
                </div>
                <p style="color: #94a3b8; line-height: 1.6; font-size: 0.9rem;">
                    Solving agricultural perishability and distress selling by connecting farmers directly with certified cold storage facilities using transparent Smart Match recommendations.
                </p>
            </div>

            <div>
                <h4>Quick Navigation</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo base_url('index.php'); ?>"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="<?php echo base_url('search.php'); ?>"><i class="fa-solid fa-magnifying-glass"></i> Find Cold Storage</a></li>
                    <li><a href="<?php echo base_url('login.php'); ?>"><i class="fa-solid fa-arrow-right-to-bracket"></i> Farmer / Owner Login</a></li>
                    <li><a href="<?php echo base_url('register.php'); ?>"><i class="fa-solid fa-user-plus"></i> Create Account</a></li>
                </ul>
            </div>

            <div>
                <h4>Hackathon Scope</h4>
                <p style="color: #94a3b8; line-height: 1.6; font-size: 0.88rem; margin-bottom: 0.75rem;">
                    Built for <strong>KALPVRUKSH 2.0 Mini Hackathon</strong> at Silver Oak University.
                </p>
                <div class="hackathon-badge">
                    <span><i class="fa-solid fa-trophy"></i> Problem P21 – AgriTech EASY</span>
                </div>
            </div>
        </div>

        <!-- Team Members Attribution Line (Single-line Compact) -->
        <div class="footer-team-line">
            <span class="team-label"><i class="fa-solid fa-users" style="color: #38bdf8; margin-right: 6px;"></i><strong>Team Members:</strong></span>
            <span class="team-names">1. Askari Mirza &nbsp;&bull;&nbsp; 2. Maazz Shaikh &nbsp;&bull;&nbsp; 3. Dipak Gohel &nbsp;&bull;&nbsp; 4. Prajapati Chirag</span>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?php echo date('Y'); ?> Agri Storage. All rights reserved. Designed for local prototype demonstration.
            </div>
            <div>
                <span style="color: #64748b;">Stack: PHP 8.2 &bull; MySQL &bull; Vanilla JS &bull; CSS3</span>
            </div>
        </div>
    </div>
</footer>

<!-- Vanilla JS Application Scripts -->
<script src="<?php echo base_url('js/script.js'); ?>"></script>
</body>
</html>
