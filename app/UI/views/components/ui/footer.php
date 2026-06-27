<?php
/**
 * Footer component view.
 * @file resources/components/ui/footer.php
 * 
 */
?>
<footer class="footer">
    <nav>
        <ul class="footer__menu">
            <li><a href="#" class="footer__link">Privacy Policy</a></li>
            <li><a href="#" class="footer__link">Terms of Service</a></li>
            <li><a href="#" class="footer__link">Support</a></li>
        </ul>
    </nav>
    <p class="footer__copy">&copy; <?= date('Y') ?><?= slot("footer_copy") ?></p>
</footer>